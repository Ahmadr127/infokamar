<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Room;
use App\Models\RoomDisplay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoomDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function setPin(Room $room, string $pin = '1234'): void
    {
        $room->forceFill(['pin_hash' => Hash::make($pin)])->save();
    }

    private function admin(): User
    {
        $role = Role::create(['name' => 'admin', 'display_name' => 'Admin']);
        $perm = Permission::firstOrCreate(
            ['name' => 'manage_rooms'],
            ['display_name' => 'Kelola Kamar']
        );
        $role->permissions()->attach($perm);

        return User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@test.id',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
        ]);
    }

    public function test_guest_without_token_sees_pin_page(): void
    {
        $response = $this->get('/informasi-kamar');

        $response->assertOk();
        $response->assertSee('Registrasi Perangkat', false);
    }

    public function test_wrong_pin_is_rejected(): void
    {
        $room = Room::factory()->create();
        $this->setPin($room, '1234');

        $response = $this->post('/informasi-kamar/verifikasi', ['pin' => '9999']);

        $response->assertSessionHasErrors('pin');
        $this->assertDatabaseCount('room_displays', 0);
    }

    public function test_correct_pin_registers_device_with_persistent_cookie(): void
    {
        $room = Room::factory()->create();
        $this->setPin($room, '1234');

        $response = $this->post('/informasi-kamar/verifikasi', ['pin' => '1234']);

        $response->assertRedirect(route('room-display.show'));
        $cookie = $response->getCookie(config('room-display.cookie'));
        $this->assertNotNull($cookie);
        // Cookie persisten (bukan session-only).
        $this->assertGreaterThan(86400, $cookie->getExpiresTime() - time());

        $this->assertDatabaseCount('room_displays', 1);
        $display = RoomDisplay::first();
        $this->assertEquals($room->id, $display->room_id);
        // Token mentah tidak tersimpan di DB.
        $this->assertNotEquals($cookie->getValue(), $display->token_hash);
        $this->assertEquals(hash('sha256', $cookie->getValue()), $display->token_hash);
    }

    public function test_registered_device_skips_pin_and_sees_room(): void
    {
        $room = Room::factory()->create();
        $this->setPin($room, '1234');
        \App\Models\RoomPatientAssignment::factory()->create([
            'room_id' => $room->id, 'patient_name' => 'Budi Santoso',
        ]);

        $verify = $this->post('/informasi-kamar/verifikasi', ['pin' => '1234']);
        $token = $verify->getCookie(config('room-display.cookie'))->getValue();

        // Kunjungan berikutnya dengan cookie (simulasi restart browser).
        $response = $this->withCookie(config('room-display.cookie'), $token)
            ->get('/informasi-kamar');

        $response->assertOk();
        $response->assertSee('Budi Santoso', false);
        $response->assertDontSee('Registrasi Perangkat', false);
    }

    public function test_revoked_token_cannot_access_data(): void
    {
        $room = Room::factory()->create();
        $this->setPin($room, '1234');

        $verify = $this->post('/informasi-kamar/verifikasi', ['pin' => '1234']);
        $token = $verify->getCookie(config('room-display.cookie'))->getValue();

        $display = RoomDisplay::first();
        $display->update(['revoked_at' => now()]);

        $this->withCookie(config('room-display.cookie'), $token)
            ->get('/informasi-kamar/data')
            ->assertUnauthorized();

        // Kembali ke halaman PIN.
        $this->withCookie(config('room-display.cookie'), $token)
            ->get('/informasi-kamar')
            ->assertSee('Registrasi Perangkat', false);
    }

    public function test_inactive_room_token_is_rejected(): void
    {
        $room = Room::factory()->create(['is_active' => false]);
        $this->setPin($room, '1234');

        $this->post('/informasi-kamar/verifikasi', ['pin' => '1234'])
            ->assertSessionHasErrors('pin');

        $this->assertDatabaseCount('room_displays', 0);
    }

    public function test_heartbeat_updates_last_seen(): void
    {
        $room = Room::factory()->create();
        $this->setPin($room, '1234');

        $verify = $this->post('/informasi-kamar/verifikasi', ['pin' => '1234']);
        $token = $verify->getCookie(config('room-display.cookie'))->getValue();

        $display = RoomDisplay::first();
        $display->update(['last_seen_at' => now()->subHour()]);

        $this->withCookie(config('room-display.cookie'), $token)
            ->post('/informasi-kamar/heartbeat')
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertTrue($display->fresh()->last_seen_at->greaterThan(now()->subMinute()));
    }

    public function test_data_endpoint_only_returns_bound_room(): void
    {
        $roomA = Room::factory()->create();
        $roomB = Room::factory()->create();
        $this->setPin($roomA, '1111');
        $this->setPin($roomB, '2222');
        \App\Models\RoomPatientAssignment::factory()->create([
            'room_id' => $roomB->id, 'patient_name' => 'Pasien Rahasia B',
        ]);

        $verify = $this->post('/informasi-kamar/verifikasi', ['pin' => '1111']);
        $token = $verify->getCookie(config('room-display.cookie'))->getValue();

        $response = $this->withCookie(config('room-display.cookie'), $token)
            ->get('/informasi-kamar/data');

        $response->assertOk();
        // Tidak ada data ruangan lain (anti-IDOR).
        $response->assertJsonPath('room.code', $roomA->code);
        $response->assertDontSee('Pasien Rahasia B', false);
    }

    public function test_admin_can_open_dashboard_but_guest_cannot_manage(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/informasi-kamar')
            ->assertOk();

        $role = Role::create(['name' => 'user', 'display_name' => 'User']);
        $user = User::create([
            'name' => 'User', 'username' => 'user', 'email' => 'user@test.id',
            'password' => Hash::make('password'), 'role_id' => $role->id,
        ]);

        $this->actingAs($user)->get('/admin/informasi-kamar')->assertForbidden();
    }

    public function test_patient_change_closes_old_assignment(): void
    {
        $admin = $this->admin();
        $room = Room::factory()->create();
        \App\Models\RoomPatientAssignment::factory()->create([
            'room_id' => $room->id, 'patient_name' => 'Pasien Lama',
        ]);

        $this->actingAs($admin)->put("/admin/informasi-kamar/{$room->id}", [
            'code' => $room->code,
            'name' => $room->name,
            'care_class' => $room->care_class,
            'patient_name' => 'Pasien Baru',
        ])->assertRedirect();

        $this->assertNull($room->fresh()->currentAssignment->ended_at);
        $this->assertEquals('Pasien Baru', $room->fresh()->currentAssignment->patient_name);
        // Data lama tertutup, tidak tampil.
        $this->assertNotNull(
            \App\Models\RoomPatientAssignment::where('room_id', $room->id)
                ->where('patient_name', 'Pasien Lama')->first()->ended_at
        );
    }

    public function test_search_and_filter_rooms(): void
    {
        $admin = $this->admin();
        Room::factory()->create(['code' => 'A-101', 'name' => 'Melati 101', 'is_active' => true]);
        Room::factory()->create(['code' => 'B-202', 'name' => 'Mawar 202', 'is_active' => false]);

        $this->actingAs($admin)->get('/admin/informasi-kamar?search=Melati')
            ->assertOk()->assertSee('Melati 101', false)->assertDontSee('Mawar 202', false);

        $this->actingAs($admin)->get('/admin/informasi-kamar?is_active=0')
            ->assertOk()->assertSee('Mawar 202', false)->assertDontSee('Melati 101', false);
    }

    public function test_theme_settings_saved_from_management(): void
    {
        $admin = $this->admin();
        $room = Room::factory()->create();

        $this->actingAs($admin)->put("/admin/informasi-kamar/{$room->id}", [
            'code' => $room->code,
            'name' => $room->name,
            'care_class' => $room->care_class,
            'theme_mode' => 'auto',
            'light_start' => '07:00',
            'dark_start' => '19:00',
            'timezone' => 'Asia/Makassar',
            'health_message' => 'Cuci tangan pakai sabun.',
            'visit_schedule' => 'Kunjungan 10.00-12.00.',
            'announcement' => '',
            'rotation_seconds' => 30,
        ])->assertRedirect();

        $fresh = $room->fresh();
        $this->assertEquals('auto', $fresh->theme_mode);
        $this->assertEquals('07:00', $fresh->light_start);
        $this->assertEquals('Asia/Makassar', $fresh->timezone);
        $this->assertEquals(['Cuci tangan pakai sabun.'], $fresh->rotatingContents());
    }

    public function test_data_endpoint_includes_theme_config(): void
    {
        $room = Room::factory()->create([
            'theme_mode' => 'dark', 'health_message' => 'Tetap semangat.',
        ]);
        $this->setPin($room, '1234');

        $verify = $this->post('/informasi-kamar/verifikasi', ['pin' => '1234']);
        $token = $verify->getCookie(config('room-display.cookie'))->getValue();

        $this->withCookie(config('room-display.cookie'), $token)
            ->get('/informasi-kamar/data')
            ->assertOk()
            ->assertJsonPath('room.theme_mode', 'dark')
            ->assertJsonPath('contents.0', 'Tetap semangat.');
    }

    public function test_landing_renders_initial_theme_without_flash(): void
    {
        $room = Room::factory()->create(['theme_mode' => 'dark']);
        $this->setPin($room, '1234');

        $verify = $this->post('/informasi-kamar/verifikasi', ['pin' => '1234']);
        $token = $verify->getCookie(config('room-display.cookie'))->getValue();

        $this->withCookie(config('room-display.cookie'), $token)
            ->get('/informasi-kamar')
            ->assertOk()
            ->assertSee('data-theme="dark"', false);
    }

    public function test_care_details_saved_and_exposed(): void
    {
        $admin = $this->admin();
        $room = Room::factory()->create();

        \Illuminate\Support\Facades\Storage::fake('public');
        $bg = \Illuminate\Http\UploadedFile::fake()->image('bg.jpg');

        $this->actingAs($admin)->call('PUT', "/admin/informasi-kamar/{$room->id}", [
            'code' => $room->code,
            'name' => $room->name,
            'care_class' => $room->care_class,
            'is_active' => '1',
            'bed' => 'A',
            'doctor_name' => 'dr. Hendra Setiawan, Sp.PD',
            'visit_session_1' => '11:00 – 13:00',
            'visit_session_2' => '17:00 – 20:00',
            'visit_note' => 'Maksimal 2 pengunjung.',
        ], [], ['background' => $bg])->assertRedirect();

        $fresh = $room->fresh();
        $this->assertEquals('A', $fresh->bed);
        $this->assertEquals('dr. Hendra Setiawan, Sp.PD', $fresh->doctor_name);
        $this->assertEquals('11:00 – 13:00', $fresh->visit_session_1);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($fresh->bg_path);

        $this->setPin($fresh, '1234');
        $verify = $this->post('/informasi-kamar/verifikasi', ['pin' => '1234']);
        $token = $verify->getCookie(config('room-display.cookie'))->getValue();

        $this->withCookie(config('room-display.cookie'), $token)
            ->get('/informasi-kamar')
            ->assertOk()
            ->assertSee('dr. Hendra Setiawan, Sp.PD', false)
            ->assertSee('rd-bed">A<', false);

        $this->withCookie(config('room-display.cookie'), $token)
            ->get('/informasi-kamar/data')
            ->assertOk()
            ->assertJsonPath('room.bed', 'A')
            ->assertJsonPath('room.doctor_name', 'dr. Hendra Setiawan, Sp.PD')
            ->assertJsonPath('room.visit_session_1', '11:00 – 13:00');
    }
}
