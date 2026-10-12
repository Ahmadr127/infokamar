<?php

namespace App\Http\Services;

use App\Models\Room;
use App\Models\RoomDisplay;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;

class RoomDisplayService
{
    public function __construct(protected ActivityLogService $activityLogger) {}

    public function cookieName(): string
    {
        return config('room-display.cookie', 'room_display_token');
    }

    public function hashToken(string $rawToken): string
    {
        return hash('sha256', $rawToken);
    }

    /**
     * Resolve perangkat dari cookie token. Null bila tidak ada,
     * tidak cocok, dicabut, atau ruangannya nonaktif.
     */
    public function resolveDisplay(Request $request): ?RoomDisplay
    {
        $raw = $request->cookie($this->cookieName());
        if (! is_string($raw) || $raw === '') {
            return null;
        }

        $display = RoomDisplay::active()
            ->where('token_hash', $this->hashToken($raw))
            ->with('room')
            ->first();

        if (! $display || ! $display->room || ! $display->room->is_active) {
            return null;
        }

        return $display;
    }

    /**
     * Cari ruangan aktif berdasarkan PIN 4 digit.
     */
    public function findRoomByPin(string $pin): ?Room
    {
        $rooms = Room::active()->whereNotNull('pin_hash')->get(['id', 'code', 'name', 'pin_hash']);

        foreach ($rooms as $room) {
            if (Hash::check($pin, $room->pin_hash)) {
                return Room::find($room->id);
            }
        }

        return null;
    }

    /**
     * Daftarkan perangkat ke ruangan. Return [display, rawToken].
     * Raw token hanya tersedia saat ini — tidak disimpan mentah.
     */
    public function registerDisplay(Room $room, Request $request, ?string $deviceName = null): array
    {
        return DB::transaction(function () use ($room, $request, $deviceName) {
            $rawToken = Str::random(64);

            // token_hash tidak fillable (anti mass-assignment); isi eksplisit.
            $display = $room->displays()->make([
                'device_name' => $deviceName ?? substr($request->userAgent() ?? 'Display', 0, 100),
                'last_ip' => $request->ip(),
                'user_agent' => substr($request->userAgent() ?? '', 0, 255),
                'last_seen_at' => now(),
                'registered_at' => now(),
            ]);
            $display->forceFill(['token_hash' => $this->hashToken($rawToken)])->save();

            $this->activityLogger->log(
                $display,
                'display_registered',
                ['room_id' => $room->id, 'room_code' => $room->code, 'ip' => $request->ip()],
                "Perangkat didaftarkan ke ruangan {$room->code}"
            );

            return [$display, $rawToken];
        });
    }

    public function makeTokenCookie(string $rawToken): SymfonyCookie
    {
        return Cookie::make(
            $this->cookieName(),
            $rawToken,
            config('room-display.cookie_lifetime_minutes', 2628000),
            '/informasi-kamar',
            null,
            app()->environment('production'),
            true, // httpOnly
            false,
            'Lax'
        );
    }

    public function forgetTokenCookie(): SymfonyCookie
    {
        return Cookie::forget($this->cookieName(), '/informasi-kamar');
    }

    public function touch(RoomDisplay $display, Request $request): void
    {
        $display->update([
            'last_ip' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? '', 0, 255),
            'last_seen_at' => now(),
        ]);
    }

    public function revoke(RoomDisplay $display, ?string $reason = null): void
    {
        $display->update(['revoked_at' => now()]);

        $this->activityLogger->log(
            $display,
            'display_revoked',
            ['room_id' => $display->room_id, 'reason' => $reason],
            "Perangkat ruangan ID {$display->room_id} dicabut"
        );
    }

    public function setPin(Room $room, string $pin): void
    {
        // pin_hash sengaja tidak fillable; isi eksplisit agar
        // tidak bisa di-mass-assign dari request.
        $room->forceFill(['pin_hash' => Hash::make($pin)])->save();

        $this->activityLogger->log(
            $room,
            'pin_changed',
            ['room_code' => $room->code],
            "PIN ruangan {$room->code} diganti"
        );
    }
}
