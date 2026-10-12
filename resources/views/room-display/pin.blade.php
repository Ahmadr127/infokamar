<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Informasi Kamar - Registrasi Perangkat</title>
    @vite(['resources/css/app.css'])
    <style>
        body { font-family: 'Nunito', system-ui, sans-serif; }
        .pin-input { letter-spacing: 1rem; }
    </style>
</head>
<body class="bg-slate-900 min-h-screen flex items-center justify-center p-6">
    <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md text-center">
        <img src="{{ asset('images/logo.png') }}" alt="Logo" class="h-16 mx-auto mb-4 object-contain">
        <h1 class="text-2xl font-extrabold text-slate-800">Registrasi Perangkat</h1>
        <p class="text-slate-500 mt-1 mb-6">Masukkan PIN 4 digit ruangan yang diberikan admin.</p>

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 mb-4 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('room-display.verify') }}">
            @csrf
            <input type="text" name="pin" inputmode="numeric" pattern="[0-9]*" maxlength="4" minlength="4"
                   required autocomplete="off" autofocus
                   class="pin-input w-full text-center text-4xl font-extrabold border-2 border-slate-200 rounded-xl py-4 focus:border-teal-600 focus:outline-none"
                   placeholder="••••" value="{{ old('pin') }}">
            <input type="hidden" name="device_name" id="device_name">
            <button type="submit"
                    class="mt-5 w-full bg-teal-700 hover:bg-teal-800 text-white font-bold py-3 rounded-xl text-lg transition">
                Hubungkan Perangkat
            </button>
        </form>
        <p class="text-xs text-slate-400 mt-4">Perangkat akan mengingat ruangan ini secara otomatis.</p>
    </div>
    <script>
        document.getElementById('device_name').value =
            (navigator.platform || 'Display') + ' / ' + (screen.width + 'x' + screen.height);
    </script>
</body>
</html>
