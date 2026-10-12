<?php

namespace Tests\Unit;

use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomThemeTest extends TestCase
{
    use RefreshDatabase;

    private function room(array $attrs = []): Room
    {
        return Room::factory()->create(array_merge([
            'theme_mode' => 'auto',
            'light_start' => '06:00',
            'dark_start' => '18:00',
            'timezone' => 'Asia/Jakarta',
        ], $attrs));
    }

    public function test_forced_modes_ignore_schedule(): void
    {
        $this->assertEquals('light', $this->room(['theme_mode' => 'light'])
            ->resolveTheme(Carbon::parse('2026-10-12 23:00', 'Asia/Jakarta')));
        $this->assertEquals('dark', $this->room(['theme_mode' => 'dark'])
            ->resolveTheme(Carbon::parse('2026-10-12 10:00', 'Asia/Jakarta')));
    }

    public function test_auto_daytime_is_light(): void
    {
        $room = $this->room();

        $this->assertEquals('light', $room->resolveTheme(Carbon::parse('2026-10-12 06:00', 'Asia/Jakarta')));
        $this->assertEquals('light', $room->resolveTheme(Carbon::parse('2026-10-12 12:00', 'Asia/Jakarta')));
        $this->assertEquals('dark', $room->resolveTheme(Carbon::parse('2026-10-12 18:00', 'Asia/Jakarta')));
        $this->assertEquals('dark', $room->resolveTheme(Carbon::parse('2026-10-12 02:00', 'Asia/Jakarta')));
    }

    public function test_auto_range_crossing_midnight(): void
    {
        $room = $this->room(['light_start' => '20:00', 'dark_start' => '05:00']);

        $this->assertEquals('light', $room->resolveTheme(Carbon::parse('2026-10-12 21:00', 'Asia/Jakarta')));
        $this->assertEquals('light', $room->resolveTheme(Carbon::parse('2026-10-13 00:30', 'Asia/Jakarta')));
        $this->assertEquals('light', $room->resolveTheme(Carbon::parse('2026-10-13 04:59', 'Asia/Jakarta')));
        $this->assertEquals('dark', $room->resolveTheme(Carbon::parse('2026-10-13 05:00', 'Asia/Jakarta')));
        $this->assertEquals('dark', $room->resolveTheme(Carbon::parse('2026-10-12 12:00', 'Asia/Jakarta')));
    }

    public function test_rotating_contents_skips_empty(): void
    {
        $room = $this->room([
            'health_message' => '  ',
            'visit_schedule' => 'teks lama diabaikan',
            'announcement' => 'Pengumuman',
        ]);

        $this->assertEquals(['Pengumuman'], $room->rotatingContents());
    }
}
