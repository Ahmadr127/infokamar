@extends('layouts.app')

@section('title', 'Management Ruangan ' . $room->code)

@section('content')
<div class="w-full mx-auto">
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 mb-4 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 mb-4 text-sm">{{ session('error') }}</div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
        <h2 class="text-2xl font-bold text-gray-900">Management: {{ $room->name }}
            <span class="text-sm font-mono text-gray-500">{{ $room->code }}</span>
        </h2>
        <div class="flex gap-2">
            <a href="{{ route('rooms.preview', $room) }}" target="_blank"
               class="bg-teal-700 hover:bg-teal-800 text-white px-4 py-2 rounded-md text-sm font-bold">Pratinjau Layar Penuh</a>
            <a href="{{ route('rooms.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md text-sm">Kembali</a>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 items-start">
        <!-- Form -->
        <div class="bg-white shadow-sm sm:rounded-lg p-6">
            <h3 class="font-bold text-gray-900 mb-4">Pengaturan Ruangan</h3>
            @include('rooms._form', ['room' => $room->load('currentAssignment')])

            <hr class="my-6">

            <!-- Perangkat -->
            <h3 class="font-bold text-gray-900 mb-3">Perangkat Terdaftar</h3>
            @forelse($room->displays as $display)
                <div class="border rounded-lg p-3 mb-2 flex flex-wrap items-center justify-between gap-2 {{ $display->isRevoked() ? 'bg-gray-50 opacity-70' : 'bg-white' }}">
                    <div class="text-sm">
                        <p class="font-bold">{{ $display->device_name ?? 'Display #' . $display->id }}</p>
                        <p class="text-xs text-gray-500 font-mono">IP: {{ $display->last_ip ?? '-' }} &bull; aktif: {{ $display->last_seen_at?->diffForHumans() ?? '-' }}</p>
                        @if($display->isRevoked())
                            <span class="text-xs font-bold text-red-700">DICABUT {{ $display->revoked_at?->format('d/m/Y H:i') }}</span>
                        @elseif($display->isOnline())
                            <span class="text-xs font-bold text-teal-700">● ONLINE</span>
                        @else
                            <span class="text-xs font-bold text-amber-700">○ OFFLINE</span>
                        @endif
                    </div>
                    @unless($display->isRevoked())
                        <form method="POST" action="{{ route('rooms.displays.revoke', [$room, $display]) }}"
                              onsubmit="return confirm('Cabut perangkat ini? Token lama tidak berlaku lagi.')">
                            @csrf
                            <button class="bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-md text-xs font-bold">Cabut</button>
                        </form>
                    @endunless
                </div>
            @empty
                <p class="text-sm text-gray-500">Belum ada perangkat terdaftar. Perangkat mendaftar via PIN di halaman display.</p>
            @endforelse

            <!-- Ganti PIN -->
            <form method="POST" action="{{ route('rooms.pin', $room) }}" class="mt-4 flex items-end gap-2">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">PIN Baru (4 digit)</label>
                    <input type="text" name="pin" required inputmode="numeric" maxlength="4" minlength="4"
                           placeholder="••••" class="px-3 py-2 border border-gray-300 rounded-md shadow-sm sm:text-sm tracking-[0.5rem] text-center font-bold w-40">
                </div>
                <button class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-md text-sm font-bold">Ganti PIN</button>
            </form>

            <!-- Hapus -->
            <form method="POST" action="{{ route('rooms.destroy', $room) }}" class="mt-6"
                  onsubmit="return confirm('Hapus ruangan {{ $room->code }} beserta perangkat & riwayatnya?')">
                @csrf @method('DELETE')
                <button class="text-red-700 hover:text-red-900 text-sm font-bold">Hapus ruangan ini</button>
            </form>
        </div>

        <!-- Live Preview -->
        <div class="xl:sticky xl:top-4">
            <h3 class="font-bold text-gray-900 mb-2">Live Preview <span class="text-xs font-normal text-gray-500">(mengikuti formulir, tanpa menyimpan)</span></h3>
            <style>
                [data-theme="light"] { --bg-primary:#F4F2EC; --bg-secondary:#FFFFFF; --panel-bg:#FFFFFF; --text-primary:#15343A; --text-secondary:#647579; --accent-primary:#007774; --accent-secondary:#A06B28; --border-color:#D9DFD9; --side-bg:#15343A; --side-text:#F5F3EC; --side-muted:#B4C6CB; }
                [data-theme="dark"] { --bg-primary:#0B2029; --bg-secondary:#102A34; --panel-bg:#17363C; --text-primary:#F5F3EC; --text-secondary:#B4C6CB; --accent-primary:#4DB9B0; --accent-secondary:#E5B96E; --border-color:#29454E; --side-bg:#102A34; --side-text:#F5F3EC; --side-muted:#B4C6CB; }
                .masthead-mini { border-bottom: 1px solid var(--border-color); }
            </style>
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase mb-1">Light Mode</p>
                    @include('rooms._preview', ['theme' => 'light', 'live' => true])
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase mb-1">Dark Mode</p>
                    @include('rooms._preview', ['theme' => 'dark', 'live' => true])
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase mb-1">Tema Aktif <span class="normal-case font-normal">(mode + jadwal form)</span></p>
                    <div id="pv-active-wrap" data-theme="{{ $room->resolveTheme() }}">
                        @include('rooms._preview', ['theme' => $room->resolveTheme(), 'live' => true])
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const setAll = (key, value) => {
        document.querySelectorAll('[data-pv="' + key + '"]').forEach(el => { el.textContent = value; });
    };
    const bind = (inputId, key, fallback) => {
        const input = document.getElementById(inputId);
        if (!input) return;
        const update = () => setAll(key, input.value.trim() || fallback);
        input.addEventListener('input', update);
    };
    bind('f_name', 'room', '—');
    bind('f_name', 'roomfoot', '—');
    bind('f_bed', 'bed', '');
    bind('f_doctor', 'doctor', '—');
    bind('f_care_class', 'class', '—');
    bind('f_patient_name', 'patient', '—');
    bind('f_greeting', 'greeting', @json(config('room-display.defaults.greeting')));
    bind('f_session_1', 'session1', '—');
    bind('f_session_2', 'session2', '—');

    const rotatorPreview = () => {
        const pick = (id) => (document.getElementById(id)?.value || '').trim();
        const first = pick('f_health_message') || pick('f_announcement') || '';
        setAll('rotator', first);
    };
    ['f_health_message', 'f_announcement'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', rotatorPreview);
    });

    // Tema aktif mengikuti mode + jadwal pada form (tanpa menyimpan).
    const activeWrap = document.getElementById('pv-active-wrap');
    const activeInner = activeWrap?.querySelector('[data-theme]');
    const computeActive = () => {
        const mode = document.getElementById('f_theme_mode')?.value || 'auto';
        if (mode === 'light' || mode === 'dark') return mode;
        const light = document.getElementById('f_light_start')?.value || '06:00';
        const dark = document.getElementById('f_dark_start')?.value || '18:00';
        const hm = new Date().toTimeString().slice(0, 5);
        if (light <= dark) return (hm >= light && hm < dark) ? 'light' : 'dark';
        return (hm >= light || hm < dark) ? 'light' : 'dark';
    };
    const applyActive = () => {
        const theme = computeActive();
        activeWrap?.setAttribute('data-theme', theme);
        activeInner?.setAttribute('data-theme', theme);
    };
    ['f_theme_mode', 'f_light_start', 'f_dark_start'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', applyActive);
        document.getElementById(id)?.addEventListener('change', applyActive);
    });
    applyActive();
    setInterval(applyActive, 30000);

    const tick = () => {
        const now = new Date();
        const t = new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false, timeZone: 'Asia/Jakarta' }).format(now);
        const d = new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', timeZone: 'Asia/Jakarta' }).format(now);
        setAll('clock', t);
        setAll('date', d);
    };
    tick();
    setInterval(tick, 1000);
})();
</script>
@endsection
