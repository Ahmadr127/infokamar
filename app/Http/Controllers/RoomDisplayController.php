<?php

namespace App\Http\Controllers;

use App\Http\Requests\Room\VerifyPin;
use App\Http\Services\RoomDisplayService;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class RoomDisplayController extends Controller
{
    public function __construct(
        protected RoomDisplayService $displays,
        protected ActivityLogService $activityLogger,
    ) {}

    /**
     * Landing page perangkat. Tanpa token valid → halaman PIN.
     */
    public function show(Request $request)
    {
        $display = $this->displays->resolveDisplay($request);

        if (! $display) {
            return response()
                ->view('room-display.pin')
                ->withCookie($this->displays->forgetTokenCookie());
        }

        $this->displays->touch($display, $request);

        return view('room-display.show', [
            'display' => $display,
            'room' => $display->room->load('currentAssignment'),
            'heartbeatSeconds' => config('room-display.heartbeat_interval_seconds', 60),
        ]);
    }

    /**
     * Verifikasi PIN 4 digit → registrasi perangkat (cookie persisten).
     */
    public function verify(VerifyPin $request)
    {
        $key = 'room-pin:'.$request->ip();
        $max = config('room-display.pin_max_attempts', 5);
        $decay = config('room-display.pin_decay_minutes', 1) * 60;

        if (RateLimiter::tooManyAttempts($key, $max)) {
            $seconds = RateLimiter::availableIn($key);

            return back()->withErrors([
                'pin' => "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik.",
            ])->withInput();
        }

        $room = $this->displays->findRoomByPin($request->string('pin')->toString());

        if (! $room) {
            RateLimiter::hit($key, $decay);

            $this->activityLogger->log(
                new \App\Models\RoomDisplay,
                'pin_failed',
                ['ip' => $request->ip()],
                'Verifikasi PIN ruangan gagal'
            );

            return back()->withErrors([
                'pin' => 'PIN salah atau ruangan tidak aktif.',
            ])->withInput();
        }

        RateLimiter::clear($key);

        [$display, $rawToken] = $this->displays->registerDisplay(
            $room, $request, $request->input('device_name')
        );

        return redirect()->route('room-display.show')
            ->withCookie($this->displays->makeTokenCookie($rawToken));
    }

    /**
     * Data minimal untuk polling landing page (hanya ruangan terikat).
     */
    public function data(Request $request)
    {
        $display = $this->displays->resolveDisplay($request);

        if (! $display) {
            return response()->json(['message' => 'Perangkat tidak terdaftar.'], 401);
        }

        $this->displays->touch($display, $request);
        $room = $display->room->load('currentAssignment');

        return response()->json([
            'room' => [
                'code' => $room->code,
                'name' => $room->name,
                'care_class' => $room->care_class,
                'bed' => $room->bed,
                'doctor_name' => $room->doctor_name,
                'greeting' => $room->greeting ?? config('room-display.defaults.greeting'),
                'logo_url' => $room->logo_path ? asset('storage/'.$room->logo_path) : null,
                'bg_url' => $room->backgroundUrl(),
                'visit_session_1' => $room->visit_session_1,
                'visit_session_2' => $room->visit_session_2,
                'visit_note' => $room->visit_note,
                'theme' => [
                    'primary' => $room->themeValue('primary'),
                    'background' => $room->themeValue('background'),
                    'accent' => $room->themeValue('accent'),
                ],
                'theme_mode' => $room->theme_mode ?? 'auto',
                'light_start' => $room->light_start ?? '06:00',
                'dark_start' => $room->dark_start ?? '18:00',
                'updated_at' => $room->updated_at->toIso8601String(),
            ],
            'contents' => $room->rotatingContents(),
            'ticker' => array_values(array_merge(
                $room->rotatingContents(),
                config('room-display.ticker_items', [])
            )),
            'health_tips' => array_values(config('room-display.health_tips', [])),
            'patient' => $room->currentAssignment ? [
                'name' => $room->currentAssignment->patient_name,
                'care_class' => $room->currentAssignment->care_class ?? $room->care_class,
            ] : null,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    /**
     * Heartbeat perangkat → penanda online.
     */
    public function heartbeat(Request $request)
    {
        $display = $this->displays->resolveDisplay($request);

        if (! $display) {
            return response()->json(['message' => 'Perangkat tidak terdaftar.'], 401);
        }

        $this->displays->touch($display, $request);

        return response()->json(['ok' => true, 'server_time' => now()->toIso8601String()]);
    }
}
