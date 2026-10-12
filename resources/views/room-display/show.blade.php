@php
    $initialTheme = $room->resolveTheme();
    $bgUrl = $room->backgroundUrl();
    $logoUrl = $room->logo_path ? asset('storage/'.$room->logo_path) : null;
    $tips = config('room-display.health_tips', []);
    $tickerGlobals = config('room-display.ticker_items', []);
    $roomTicker = $room->rotatingContents();
@endphp
<!DOCTYPE html>
<html lang="id" data-theme="{{ $initialTheme }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $room->name }} - Informasi Kamar</title>
    @vite(['resources/css/app.css'])
    <style>
        :root, [data-theme="light"] {
            --bg-primary: #F4F2EC;
            --bg-secondary: #FFFFFF;
            --panel-bg: #FFFFFF;
            --text-primary: #15343A;
            --text-secondary: #647579;
            --accent-primary: #007774;
            --accent-secondary: #A06B28;
            --border-color: #D9DFD9;
            --side-bg: #FFFFFF;
            --side-text: #15343A;
            --side-muted: #647579;
            --hero-fallback-a: #007774;
            --hero-fallback-b: #004F4C;
            --ok-bg: #E0F2F0; --ok-text: #007774; --ok-border: #B2DFDB;
            --warn-bg: #FFFBEB; --warn-text: #B45309; --warn-border: #FDE68A;
            --danger-bg: #FEF2F2; --danger-text: #B91C1C; --danger-border: #FECACA;
        }
        [data-theme="dark"] {
            --bg-primary: #0B2029;
            --bg-secondary: #102A34;
            --panel-bg: #17363C;
            --text-primary: #F5F3EC;
            --text-secondary: #B4C6CB;
            --accent-primary: #4DB9B0;
            --accent-secondary: #E5B96E;
            --border-color: #29454E;
            --side-bg: #102A34;
            --side-text: #F5F3EC;
            --side-muted: #B4C6CB;
            --hero-fallback-a: #0E4F4B;
            --hero-fallback-b: #0B2029;
            --ok-bg: #004E49; --ok-text: #7FD1C8; --ok-border: #007774;
            --warn-bg: #451A03; --warn-text: #FDE68A; --warn-border: #92400E;
            --danger-bg: #450A0A; --danger-text: #FECACA; --danger-border: #7F1D1D;
        }
        html, body { height: 100%; }
        body {
            font-family: 'Nunito', system-ui, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            overflow: hidden;
            user-select: none;
            -webkit-font-smoothing: antialiased;
        }
        .hero-fallback { background: linear-gradient(135deg, var(--hero-fallback-a), var(--hero-fallback-b)); }
        .hero-shade { background: linear-gradient(180deg, rgba(0,0,0,0) 30%, rgba(0,0,0,0.55) 100%); }
        .fade-swap { transition: opacity 0.6s ease; }
        .fade-swap.fading { opacity: 0; }
        .font-tabular { font-feature-settings: "tnum" 1; }
        @media (prefers-reduced-motion: reduce) {
            .fade-swap { transition: none; }
        }
    </style>
</head>
<body class="h-screen w-screen flex flex-col">
    <div class="h-1 w-full shrink-0" style="background: var(--border-color)">
        <div class="h-full w-1/4" style="background: var(--accent-primary)"></div>
    </div>

    <!-- ===== L utama: area + sidebar ===== -->
    <div class="flex-1 flex w-full overflow-hidden min-h-0">
        <!-- Area utama -->
        <main class="flex-1 relative h-full flex flex-col justify-end overflow-hidden border-r" style="border-color: var(--border-color)">
            @if($bgUrl)
                <img id="rd-bg" src="{{ $bgUrl }}" alt="" class="absolute inset-0 w-full h-full object-cover object-center">
            @else
                <div class="absolute inset-0 hero-fallback"></div>
            @endif
            <div class="absolute inset-0 hero-shade pointer-events-none"></div>

            <div class="relative px-8 lg:px-10 pb-7">
                <div class="max-w-3xl rounded-xl border p-6 shadow-xl" style="background: var(--panel-bg); border-color: var(--border-color)">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="px-2.5 py-0.5 rounded text-[11px] font-bold tracking-wider uppercase border"
                              style="background: var(--warn-bg); color: var(--warn-text); border-color: var(--warn-border)">
                            Selamat Datang &middot; Rawat Inap
                        </span>
                        <span class="text-xs font-medium" style="color: var(--text-secondary)">Semoga kenyamanan dan ketenangan menyertai masa pemulihan Anda</span>
                    </div>
                    <h1 class="font-extrabold tracking-tight leading-tight text-3xl lg:text-4xl">
                        Bapak / Ibu <span id="rd-patient">{{ $room->currentAssignment?->patient_name ?? '—' }}</span>
                    </h1>
                    <div class="grid grid-cols-3 gap-3 mt-4 pt-4 border-t" style="border-color: var(--border-color)">
                        <div class="rounded-lg px-3 py-2 border" style="background: var(--bg-primary); border-color: var(--border-color)">
                            <span class="text-[10px] uppercase tracking-wider font-bold block" style="color: var(--text-secondary)">Ruang Perawatan</span>
                            <span class="text-sm font-bold mt-0.5 block"><span id="rd-room">{{ $room->name }}</span><span id="rd-bed-wrap"> &middot; Bed <span id="rd-bed">{{ $room->bed ?? '—' }}</span></span></span>
                        </div>
                        <div class="rounded-lg px-3 py-2 border" style="background: var(--bg-primary); border-color: var(--border-color)">
                            <span class="text-[10px] uppercase tracking-wider font-bold block" style="color: var(--text-secondary)">Kelas Layanan</span>
                            <span class="text-sm font-bold mt-0.5 block" id="rd-class">{{ $room->currentAssignment?->care_class ?? $room->care_class }}</span>
                        </div>
                        <div class="rounded-lg px-3 py-2 border" style="background: var(--bg-primary); border-color: var(--border-color)">
                            <span class="text-[10px] uppercase tracking-wider font-bold block" style="color: var(--text-secondary)">Dokter (DPJP)</span>
                            <span class="text-sm font-bold mt-0.5 block truncate" id="rd-doctor">{{ $room->doctor_name ?? '—' }}</span>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t flex items-center justify-between gap-4" style="border-color: var(--border-color)">
                        <p class="italic text-xs leading-relaxed" style="color: var(--text-secondary)">
                            “<span id="rd-greeting">{{ $room->greeting ?? config('room-display.defaults.greeting') }}</span>”
                        </p>
                        <span class="text-[11px] font-mono shrink-0" style="color: var(--text-secondary)">{{ config('app.name') }}</span>
                    </div>
                </div>
            </div>
        </main>

        <!-- Sidebar vertikal -->
        <aside class="w-[400px] xl:w-[430px] shrink-0 flex flex-col justify-start p-5 gap-2 overflow-y-auto border-l"
               style="background: var(--side-bg); color: var(--side-text); border-color: var(--border-color)">
            <!-- Jam + identitas -->
            <div class="rounded-xl p-4 border shadow-sm" style="background: var(--panel-bg); border-color: var(--border-color)">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <div class="font-extrabold tracking-tight leading-none text-4xl xl:text-5xl font-tabular" id="rd-clock">--:--:--</div>
                        <div class="text-xs font-semibold mt-2" style="color: var(--text-secondary)" id="rd-date">Memuat tanggal…</div>
                    </div>
                    <div class="flex flex-col items-end gap-1">
                        <span class="px-2.5 py-0.5 rounded text-[11px] font-bold border" id="rd-tz"
                              style="background: var(--bg-primary); color: var(--text-primary); border-color: var(--border-color)">WIB</span>
                        <span class="text-[11px] font-semibold flex items-center gap-1.5 pt-1" style="color: var(--text-secondary)">
                            <span class="w-2 h-2 rounded-full inline-block" style="background: #10B981"></span> Online
                        </span>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-t flex items-center justify-between gap-2" style="border-color: var(--border-color)">
                    <div>
                        <span class="text-[10px] uppercase tracking-wider font-bold block" style="color: var(--text-secondary)">Lokasi Kamar</span>
                        <span class="text-sm font-bold"><span id="rd-room-side">{{ $room->name }}</span> <span style="color: var(--text-secondary)">/ <span id="rd-code">{{ $room->code }}</span></span></span>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] uppercase tracking-wider font-bold block" style="color: var(--text-secondary)">Status Nurse Call</span>
                        <span class="text-xs font-bold mt-0.5 block" style="color: var(--ok-text)">Siap Sedia 24 Jam</span>
                    </div>
                </div>
            </div>

            <!-- Jadwal kunjungan -->
            <div class="rounded-xl p-4 border shadow-sm" style="background: var(--panel-bg); border-color: var(--border-color)">
                <div class="flex items-center justify-between pb-2 mb-3 border-b" style="border-color: var(--border-color)">
                    <h3 class="font-bold text-xs uppercase tracking-wider">Jadwal Jam Kunjungan</h3>
                    <span id="rd-session-badge" class="px-2 py-0.5 rounded text-[10px] font-bold border hidden"
                          style="background: var(--ok-bg); color: var(--ok-text); border-color: var(--ok-border)">Sesi Aktif</span>
                </div>
                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div class="p-3 rounded-lg border" style="background: var(--bg-primary); border-color: var(--border-color)">
                        <span class="text-[10px] uppercase tracking-wider block font-semibold" style="color: var(--text-secondary)">Sesi Siang</span>
                        <span class="font-extrabold font-mono text-base block mt-0.5" id="rd-session-1">{{ $room->visit_session_1 ?? '—' }}</span>
                        <span class="text-[10px] font-semibold inline-block mt-0.5" id="rd-session-1-state" style="color: var(--text-secondary)">—</span>
                    </div>
                    <div class="p-3 rounded-lg border" style="background: var(--bg-primary); border-color: var(--border-color)">
                        <span class="text-[10px] uppercase tracking-wider block font-semibold" style="color: var(--text-secondary)">Sesi Sore</span>
                        <span class="font-extrabold font-mono text-base block mt-0.5" id="rd-session-2">{{ $room->visit_session_2 ?? '—' }}</span>
                        <span class="text-[10px] font-medium inline-block mt-0.5" id="rd-session-2-state" style="color: var(--text-secondary)">—</span>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-t text-[11px] leading-relaxed" style="border-color: var(--border-color); color: var(--text-secondary)">
                    <span id="rd-visit-note">{{ $room->visit_note ?? 'Maksimal 2 (dua) pengunjung di dalam kamar secara bergantian demi ketenangan pemulihan pasien.' }}</span>
                </div>
            </div>

            <!-- Edukasi -->
            <div class="rounded-xl p-4 border shadow-sm" style="background: var(--panel-bg); border-color: var(--border-color)">
                <div class="flex items-center justify-between pb-2 mb-2 border-b" style="border-color: var(--border-color)">
                    <span class="text-[11px] font-bold uppercase tracking-wider">Edukasi Kesehatan</span>
                    <span class="text-[10px] font-semibold uppercase tracking-wider" style="color: var(--text-secondary)">Tips Harian</span>
                </div>
                <div class="min-h-[58px]">
                    <h4 class="text-xs font-bold fade-swap" id="rd-tip-title">{{ $tips[0]['title'] ?? '' }}</h4>
                    <p class="text-[11px] mt-1 leading-relaxed fade-swap" style="color: var(--text-secondary)" id="rd-tip-desc">{{ $tips[0]['desc'] ?? '' }}</p>
                </div>
            </div>

            <!-- Nurse call (panel statis, tanpa tombol) -->
            <div class="rounded-xl p-5 flex items-center justify-between border" style="background: var(--danger-bg); border-color: var(--danger-border)">
                <div>
                    <p class="text-xs font-bold">Butuh Bantuan Segera?</p>
                    <p class="text-[11px] font-medium" style="color: var(--danger-text)">Tekan tombol bel pada remote ranjang</p>
                </div>
                <span class="text-xs font-bold uppercase px-3.5 py-2 rounded-lg tracking-wide" style="background: var(--danger-text); color: #fff">Nurse Call</span>
            </div>
        </aside>
    </div>

    <!-- ===== Footer ticker ===== -->
    <footer class="h-14 w-full border-t flex items-stretch shrink-0" style="background: var(--bg-secondary); border-color: var(--border-color)">
        <div class="px-6 flex items-center gap-3 shrink-0 border-r" style="border-color: var(--border-color)">
            @if($logoUrl)
                <img id="rd-foot-logo" src="{{ $logoUrl }}" alt="Logo rumah sakit" class="h-7 object-contain">
            @else
                <div class="w-6 h-6 rounded flex items-center justify-center font-bold text-white text-xs" style="background: var(--accent-primary)">+</div>
            @endif
            <div class="leading-tight">
                <span class="font-extrabold text-xs tracking-wider block">{{ config('room-display.hospital_name', 'Rumah Sakit') }}</span>
                <span class="text-[9px] uppercase block font-semibold" style="color: var(--text-secondary)">{{ config('room-display.hospital_tagline') }}</span>
            </div>
        </div>
        <div class="flex-1 overflow-hidden relative flex items-center px-6">
            <p id="rd-ticker" class="fade-swap whitespace-nowrap overflow-hidden text-ellipsis text-xs font-semibold">{{ ($roomTicker[0] ?? null) ?? ($tickerGlobals[0] ?? '') }}</p>
        </div>
        <div class="hidden lg:flex items-center px-5 border-l shrink-0 gap-3 text-xs" style="border-color: var(--border-color)">
            <span class="flex items-center gap-2" style="color: var(--text-secondary)">
                <span class="w-2 h-2 rounded-full inline-block" style="background: #10B981"></span>
                Sistem Perawatan: <strong style="color: var(--text-primary)">Terhubung Aktif</strong>
            </span>
        </div>
    </footer>

    <script>
        const TZ = @json($room->timezone ?: config('room-display.display_timezone', 'Asia/Jakarta'));
        const DATA_URL = @json(route('room-display.data'));
        const HEARTBEAT_URL = @json(route('room-display.heartbeat'));
        const HEARTBEAT_MS = @json($heartbeatSeconds) * 1000;
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;
        const REDUCED_MOTION = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        let lastUpdated = @json($room->updated_at->toIso8601String());
        let currentBg = @json($bgUrl);
        let themeCfg = @json(['mode' => $room->theme_mode ?? 'auto', 'light' => $room->light_start ?? '06:00', 'dark' => $room->dark_start ?? '18:00']);
        let tips = @json(array_values($tips));
        let tipIdx = 0;
        let tickerItems = @json(array_values(array_merge($roomTicker, $tickerGlobals)));
        let tickerIdx = 0;
        const ROTATE_MS = Math.max(5, @json($room->rotation_seconds ?? 20)) * 1000;

        function sapaan(hour) {
            if (hour >= 4 && hour < 11) return 'Selamat Pagi';
            if (hour >= 11 && hour < 15) return 'Selamat Siang';
            if (hour >= 15 && hour < 19) return 'Selamat Sore';
            return 'Selamat Malam';
        }

        function themeFor(hm) {
            if (themeCfg.mode === 'light' || themeCfg.mode === 'dark') return themeCfg.mode;
            const light = themeCfg.light, dark = themeCfg.dark;
            if (light <= dark) return (hm >= light && hm < dark) ? 'light' : 'dark';
            return (hm >= light || hm < dark) ? 'light' : 'dark';
        }

        function applyTheme() {
            const hm = new Intl.DateTimeFormat('en-GB', { hour: '2-digit', minute: '2-digit', hour12: false, timeZone: TZ }).format(new Date());
            document.documentElement.setAttribute('data-theme', themeFor(hm));
        }

        function parseRange(text) {
            const m = String(text || '').match(/(\d{1,2}):(\d{2})\s*[–\-]\s*(\d{1,2}):(\d{2})/);
            if (!m) return null;
            const pad = (h, mi) => String(h).padStart(2, '0') + ':' + mi;
            return { start: pad(m[1], m[2]), end: pad(m[3], m[4]) };
        }

        function sessionState(range, hm) {
            if (!range) return '—';
            if (hm >= range.start && hm < range.end) return 'Sedang Berlangsung';
            if (hm < range.start) return 'Akan Datang';
            return 'Selesai';
        }

        function updateSessions() {
            const hm = new Intl.DateTimeFormat('en-GB', { hour: '2-digit', minute: '2-digit', hour12: false, timeZone: TZ }).format(new Date());
            const r1 = parseRange(document.getElementById('rd-session-1').textContent);
            const r2 = parseRange(document.getElementById('rd-session-2').textContent);
            const s1 = sessionState(r1, hm), s2 = sessionState(r2, hm);
            document.getElementById('rd-session-1-state').textContent = '● ' + s1;
            document.getElementById('rd-session-2-state').textContent = '● ' + s2;
            const active = s1 === 'Sedang Berlangsung' || s2 === 'Sedang Berlangsung';
            document.getElementById('rd-session-badge').classList.toggle('hidden', !active);
        }

        function tickClock() {
            const now = new Date();
            document.getElementById('rd-clock').textContent =
                new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false, timeZone: TZ }).format(now);
            document.getElementById('rd-date').textContent =
                new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', timeZone: TZ }).format(now);
            try {
                document.getElementById('rd-tz').textContent =
                    new Intl.DateTimeFormat('id-ID', { timeZoneName: 'short', timeZone: TZ }).formatToParts(now).find(p => p.type === 'timeZoneName')?.value || TZ;
            } catch (e) { /* biarkan default */ }
            updateSessions();
            applyTheme();
        }

        function fadeSwap(el, text) {
            if (REDUCED_MOTION || !el) { if (el) el.textContent = text; return; }
            el.classList.add('fading');
            setTimeout(() => { el.textContent = text; el.classList.remove('fading'); }, 600);
        }

        function rotateTips() {
            if (tips.length < 2) return;
            tipIdx = (tipIdx + 1) % tips.length;
            fadeSwap(document.getElementById('rd-tip-title'), tips[tipIdx].title);
            fadeSwap(document.getElementById('rd-tip-desc'), tips[tipIdx].desc);
        }

        function rotateTicker() {
            if (tickerItems.length < 2) return;
            tickerIdx = (tickerIdx + 1) % tickerItems.length;
            fadeSwap(document.getElementById('rd-ticker'), tickerItems[tickerIdx]);
        }

        async function refreshData() {
            try {
                const res = await fetch(DATA_URL, { headers: { 'Accept': 'application/json' } });
                if (res.status === 401) { window.location.reload(); return; }
                if (!res.ok) return;
                const json = await res.json();
                themeCfg = { mode: json.room.theme_mode, light: json.room.light_start, dark: json.room.dark_start };
                tips = json.health_tips || tips;
                tickerItems = (json.ticker || []).length ? json.ticker : tickerItems;
                if (json.room.updated_at === lastUpdated) { applyTheme(); return; }
                lastUpdated = json.room.updated_at;
                document.getElementById('rd-patient').textContent = json.patient?.name ?? '—';
                document.getElementById('rd-class').textContent = json.patient?.care_class ?? json.room.care_class;
                document.getElementById('rd-room').textContent = json.room.name;
                document.getElementById('rd-room-side').textContent = json.room.name;
                document.getElementById('rd-code').textContent = json.room.code;
                document.getElementById('rd-bed').textContent = json.room.bed ?? '—';
                document.getElementById('rd-doctor').textContent = json.room.doctor_name ?? '—';
                document.getElementById('rd-greeting').textContent = json.room.greeting;
                document.getElementById('rd-session-1').textContent = json.room.visit_session_1 ?? '—';
                document.getElementById('rd-session-2').textContent = json.room.visit_session_2 ?? '—';
                document.getElementById('rd-visit-note').textContent = json.room.visit_note ?? '';
                if ((json.room.bg_url || null) !== (currentBg || null)) { window.location.reload(); return; }
                const footLogo = document.getElementById('rd-foot-logo');
                if (footLogo && json.room.logo_url) footLogo.src = json.room.logo_url;
                updateSessions();
                applyTheme();
            } catch (e) { /* offline: pakai konfigurasi terakhir */ }
        }

        async function heartbeat() {
            try {
                const res = await fetch(HEARTBEAT_URL, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                });
                if (res.status === 401) window.location.reload();
            } catch (e) { /* abaikan */ }
        }

        tickClock();
        setInterval(tickClock, 1000);
        setInterval(rotateTips, 8000);
        setInterval(rotateTicker, ROTATE_MS);
        @if(!($preview ?? false))
        setInterval(refreshData, 60000);
        if (HEARTBEAT_MS > 0) {
            setInterval(heartbeat, HEARTBEAT_MS);
            heartbeat();
        }
        @endif
    </script>
</body>
</html>
