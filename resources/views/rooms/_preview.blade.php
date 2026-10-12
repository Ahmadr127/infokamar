{{-- Pratinjau mini L-bar. $theme = light|dark, $live = ikut form --}}
<div class="rounded-xl overflow-hidden shadow border border-gray-300" data-theme="{{ $theme }}">
    <div class="text-[10px] leading-tight" style="background: var(--bg-primary); color: var(--text-primary)">
        <div class="flex min-h-0">
            <div class="flex-1 relative flex flex-col justify-end p-2 min-w-0 hero-fallback-mini">
                <div class="rounded-lg border p-2" style="background: var(--panel-bg); border-color: var(--border-color)">
                    <p class="font-extrabold text-[11px] leading-tight">Bapak/Ibu <span data-pv="patient">{{ ($live ?? false) ? '—' : ($room->currentAssignment?->patient_name ?? '—') }}</span></p>
                    <div class="grid grid-cols-3 gap-1 mt-1.5 pt-1.5 border-t" style="border-color: var(--border-color)">
                        <div class="rounded px-1 py-0.5 border" style="background: var(--bg-primary); border-color: var(--border-color)">
                            <span class="block font-bold opacity-70" style="font-size: 7px">RUANGAN</span>
                            <span class="font-bold" data-pv="room">{{ $room->name }}</span>{{ $room->bed ? ' · Bed ' : '' }}<span data-pv="bed">{{ $room->bed ?? '' }}</span>
                        </div>
                        <div class="rounded px-1 py-0.5 border" style="background: var(--bg-primary); border-color: var(--border-color)">
                            <span class="block font-bold opacity-70" style="font-size: 7px">KELAS</span>
                            <span class="font-bold" data-pv="class">{{ $room->care_class }}</span>
                        </div>
                        <div class="rounded px-1 py-0.5 border" style="background: var(--bg-primary); border-color: var(--border-color)">
                            <span class="block font-bold opacity-70" style="font-size: 7px">DPJP</span>
                            <span class="font-bold truncate block" data-pv="doctor">{{ $room->doctor_name ?? '—' }}</span>
                        </div>
                    </div>
                    <p class="mt-1 italic truncate" style="color: var(--text-secondary)">“<span data-pv="greeting">{{ $room->greeting ?? config('room-display.defaults.greeting') }}</span>”</p>
                </div>
            </div>
            <aside class="w-[36%] flex flex-col gap-1.5 p-2 border-l" style="background: var(--side-bg); color: var(--side-text); border-color: var(--border-color)">
                <div class="rounded-lg p-1.5 border" style="background: var(--panel-bg); border-color: var(--border-color)">
                    <div class="font-extrabold tabular-nums text-sm" data-pv="clock">--:--:--</div>
                    <div class="font-semibold opacity-80" data-pv="date">…</div>
                </div>
                <div class="rounded-lg p-1.5 border" style="background: var(--panel-bg); border-color: var(--border-color)">
                    <p class="font-bold opacity-70" style="font-size: 7px">JADWAL KUNJUNGAN</p>
                    <p class="font-mono font-bold"><span data-pv="session1">{{ $room->visit_session_1 ?? '—' }}</span></p>
                    <p class="font-mono"><span data-pv="session2">{{ $room->visit_session_2 ?? '—' }}</span></p>
                </div>
                <div class="rounded-lg p-1.5 border" style="background: var(--panel-bg); border-color: var(--border-color)">
                    <p class="font-bold opacity-70" style="font-size: 7px">EDUKASI</p>
                    <p class="truncate" data-pv="rotator">{{ $room->health_message ?? $room->announcement ?? '' }}</p>
                </div>
            </aside>
        </div>
        <div class="px-2 py-1 border-t truncate font-semibold" style="border-color: var(--border-color); background: var(--bg-secondary)">
            {{ config('room-display.hospital_name', 'Rumah Sakit') }} &middot; <span data-pv="roomfoot">{{ $room->code }}</span>
        </div>
    </div>
</div>
<style>
    [data-theme="light"] .hero-fallback-mini { background: linear-gradient(135deg, #007774, #004F4C); }
    [data-theme="dark"] .hero-fallback-mini { background: linear-gradient(135deg, #0E4F4B, #0B2029); }
</style>
