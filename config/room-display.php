<?php

return [

    /*------------------------------------------------------------------
    | Konfigurasi Perangkat Display Kamar
    |------------------------------------------------------------------
    */

    // Nama cookie token perangkat (raw token, HttpOnly, persisten).
    'cookie' => env('ROOM_DISPLAY_COOKIE', 'room_display_token'),

    // Masa berlaku cookie token (menit). Default ~5 tahun agar
    // identitas perangkat tetap tersimpan setelah restart.
    'cookie_lifetime_minutes' => (int) env('ROOM_DISPLAY_COOKIE_LIFETIME', 2628000),

    // Interval heartbeat dari landing page (detik).
    'heartbeat_interval_seconds' => (int) env('ROOM_DISPLAY_HEARTBEAT', 60),

    // Batas dianggap online sejak last_seen_at (menit).
    'online_threshold_minutes' => (int) env('ROOM_DISPLAY_ONLINE_THRESHOLD', 5),

    // Rate limit verifikasi PIN.
    'pin_max_attempts' => (int) env('ROOM_DISPLAY_PIN_ATTEMPTS', 5),
    'pin_decay_minutes' => (int) env('ROOM_DISPLAY_PIN_DECAY', 1),

    // Nama rumah sakit untuk header/footer landing page.
    'hospital_name' => env('ROOM_DISPLAY_HOSPITAL_NAME', 'Rumah Sakit'),

    // Tagline di bawah nama rumah sakit.
    'hospital_tagline' => env('ROOM_DISPLAY_HOSPITAL_TAGLINE', 'In-Patient Information System'),

    // Background default area utama (path relatif public/, null = panel token).
    'defaults' => [
        'background' => env('ROOM_DISPLAY_DEFAULT_BG'),
        'greeting' => env(
            'ROOM_DISPLAY_DEFAULT_GREETING',
            'Semoga lekas pulih dan mendapatkan pelayanan terbaik.'
        ),
        'theme' => [
            'primary' => '#007774',
            'background' => '#0b1f2a',
            'accent' => '#f59e0b',
        ],
    ],

    // Edukasi kesehatan global (rotasi sidebar). Format: [['title' => .., 'desc' => ..]].
    'health_tips' => [
        [
            'title' => 'Pentingnya Hidrasi Optimal',
            'desc' => 'Minum air putih 2–2.5 liter per hari (sesuai anjuran dokter) mempercepat pemulihan.',
        ],
        [
            'title' => 'Kualitas Istirahat Malam',
            'desc' => 'Tidur cukup merangsang pemulihan jaringan serta memperkuat imunitas tubuh.',
        ],
        [
            'title' => 'Pencegahan Risiko Terpeleset',
            'desc' => 'Gunakan alas kaki anti-selip dan panggil perawat sebelum beranjak bila merasa limbung.',
        ],
        [
            'title' => 'Nutrisi Terukur untuk Penyembuhan',
            'desc' => 'Menu telah disesuaikan ahli gizi untuk mendukung target pemulihan Anda.',
        ],
    ],

    // Item ticker bawah global (teks saja, digabung dengan pengumuman ruangan).
    'ticker_items' => [
        'Melayani dengan Integritas, Profesionalisme, dan Sepenuh Hati',
        'Layanan Farmasi Rawat Inap tersedia 24 jam non-stop',
    ],

    // Zona waktu tampilan jam/tanggal landing page.
    'display_timezone' => env('ROOM_DISPLAY_TIMEZONE', 'Asia/Jakarta'),
];
