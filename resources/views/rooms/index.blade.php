@extends('layouts.app')

@section('title', 'Informasi Kamar Pasien')

@section('content')
<div class="w-full mx-auto">
    <!-- Statistik -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-200">
            <p class="text-xs font-medium text-gray-500 uppercase">Total Ruangan</p>
            <p class="text-2xl font-extrabold text-gray-900">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-200">
            <p class="text-xs font-medium text-gray-500 uppercase">Aktif</p>
            <p class="text-2xl font-extrabold text-green-700">{{ $stats['active'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-200">
            <p class="text-xs font-medium text-gray-500 uppercase">Nonaktif</p>
            <p class="text-2xl font-extrabold text-gray-500">{{ $stats['inactive'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-200">
            <p class="text-xs font-medium text-gray-500 uppercase">Perangkat Online</p>
            <p class="text-2xl font-extrabold text-teal-700">{{ $stats['online_displays'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-200">
            <p class="text-xs font-medium text-gray-500 uppercase">Belum Terdaftar</p>
            <p class="text-2xl font-extrabold text-amber-600">{{ $stats['unregistered_rooms'] }}</p>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-gray-900">Ruangan</h2>
                <a href="{{ route('rooms.create') }}" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                    Tambah Ruangan
                </a>
            </div>
            <form method="GET" class="flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cari</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Nama / kode / pasien / kelas…"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm">
                </div>
                <div class="w-36">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="is_active" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm sm:text-sm">
                        <option value="">Semua</option>
                        <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>
                <div class="w-44">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Koneksi</label>
                    <select name="connection" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm sm:text-sm">
                        <option value="">Semua</option>
                        <option value="online" {{ request('connection') === 'online' ? 'selected' : '' }}>Online</option>
                        <option value="offline" {{ request('connection') === 'offline' ? 'selected' : '' }}>Offline</option>
                        <option value="unregistered" {{ request('connection') === 'unregistered' ? 'selected' : '' }}>Belum terdaftar</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm">Filter</button>
                    <a href="{{ route('rooms.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md text-sm">Reset</a>
                </div>
            </form>
        </div>

        <!-- Grid Card -->
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @forelse($rooms as $room)
                @php
                    $onlineThreshold = now()->subMinutes(config('room-display.online_threshold_minutes', 5));
                    $activeDisplay = $room->displays->where('revoked_at', null)->sortByDesc('last_seen_at')->first();
                    $isOnline = $activeDisplay && $activeDisplay->last_seen_at && $activeDisplay->last_seen_at->gte($onlineThreshold);
                @endphp
                <a href="{{ route('rooms.show', $room) }}"
                   class="block border rounded-xl p-4 transition shadow-sm hover:shadow-lg hover:-translate-y-0.5
                          {{ $room->is_active ? 'bg-white border-gray-200' : 'bg-gray-50 border-gray-200 opacity-80' }}">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <h3 class="font-extrabold text-gray-900">{{ $room->name }}</h3>
                            <p class="text-xs text-gray-500 font-mono">{{ $room->code }} &bull; Kelas {{ $room->care_class }}</p>
                        </div>
                        <span class="text-xs font-bold px-2 py-1 rounded-full {{ $room->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-600' }}">
                            {{ $room->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>
                    <p class="mt-2 text-sm text-gray-700">
                        Pasien: <strong>{{ $room->currentAssignment?->patient_name ?? '—' }}</strong>
                    </p>
                    <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                        @if($activeDisplay)
                            <span class="font-bold px-2 py-1 rounded-full {{ $isOnline ? 'bg-teal-100 text-teal-800' : 'bg-amber-100 text-amber-800' }}">
                                {{ $isOnline ? '● Online' : '○ Offline' }}
                            </span>
                            <span class="text-gray-500 font-mono">{{ $activeDisplay->last_ip ?? 'IP -' }}</span>
                            <span class="text-gray-400">{{ $activeDisplay->last_seen_at?->diffForHumans() ?? 'belum aktif' }}</span>
                        @else
                            <span class="font-bold px-2 py-1 rounded-full bg-slate-100 text-slate-600">Belum ada perangkat</span>
                        @endif
                    </div>
                </a>
            @empty
                <p class="text-gray-500 col-span-full text-center py-8">Belum ada ruangan. Tambahkan ruangan baru.</p>
            @endforelse
        </div>

        <div class="px-6 pb-6">
            {{ $rooms->links() }}
        </div>
    </div>
</div>
@endsection
