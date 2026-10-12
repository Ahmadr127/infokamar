@php $isEdit = !empty($room); @endphp
<form method="POST" enctype="multipart/form-data"
      action="{{ $isEdit ? route('rooms.update', $room) : route('rooms.store') }}"
      class="grid grid-cols-1 md:grid-cols-2 gap-4">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Kode Ruangan *</label>
        <input type="text" name="code" required maxlength="30" value="{{ old('code', $room->code ?? '') }}"
               placeholder="cth: BGV-304"
               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm sm:text-sm">
        @error('code')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Ruangan *</label>
        <input type="text" name="name" id="f_name" required maxlength="255" value="{{ old('name', $room->name ?? '') }}"
               placeholder="cth: Bougenville 304"
               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm sm:text-sm">
        @error('name')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Kelas Perawatan *</label>
        <input type="text" name="care_class" id="f_care_class" required maxlength="50" value="{{ old('care_class', $room->care_class ?? '') }}"
               placeholder="cth: VIP, I, II, III" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm sm:text-sm">
    </div>
    <div class="flex items-end gap-2 pb-1">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" id="f_is_active"
               {{ old('is_active', $room->is_active ?? true) ? 'checked' : '' }}
               class="h-4 w-4 text-green-600">
        <label for="f_is_active" class="text-sm font-medium text-gray-700">Ruangan aktif</label>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Bed</label>
        <input type="text" name="bed" id="f_bed" maxlength="30" value="{{ old('bed', $room->bed ?? '') }}"
               placeholder="cth: A" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm sm:text-sm">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Dokter DPJP</label>
        <input type="text" name="doctor_name" id="f_doctor" maxlength="255" value="{{ old('doctor_name', $room->doctor_name ?? '') }}"
               placeholder="cth: dr. Hendra Setiawan, Sp.PD" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm sm:text-sm">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Pasien (kosongkan bila kosong)</label>
        <input type="text" name="patient_name" id="f_patient_name" maxlength="255"
               value="{{ old('patient_name', $room->currentAssignment?->patient_name ?? '') }}"
               placeholder="cth: Ahmad Fauzi"
               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm sm:text-sm">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">No. RM (opsional)</label>
        <input type="text" name="patient_mrn" maxlength="50" value="{{ old('patient_mrn', $room->currentAssignment?->mrn ?? '') }}"
               placeholder="cth: 00-11-22-33"
               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm sm:text-sm">
    </div>
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">PIN 4 Digit (kosongkan bila tidak diubah)</label>
        <input type="text" name="pin" inputmode="numeric" maxlength="4" minlength="4" autocomplete="off"
               placeholder="••••" class="w-full md:w-48 px-3 py-2 border border-gray-300 rounded-md shadow-sm sm:text-sm tracking-[0.5rem] text-center font-bold">
        @error('pin')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
    </div>
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Ucapan / Sapaan</label>
        <textarea name="greeting" id="f_greeting" rows="2" maxlength="500"
                  placeholder="cth: Semoga lekas pulih dan nyaman beristirahat."
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm sm:text-sm">{{ old('greeting', $room->greeting ?? '') }}</textarea>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Logo Ruangan (opsional)</label>
        <input type="file" name="logo" accept="image/*" class="w-full text-sm">
        @if($isEdit && $room->logo_path)
            <p class="text-xs text-gray-500 mt-1">Logo saat ini: {{ basename($room->logo_path) }}</p>
        @endif
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Background Area Utama (opsional)</label>
        <input type="file" name="background" accept="image/*" class="w-full text-sm">
        @if($isEdit && $room->bg_path)
            <p class="text-xs text-gray-500 mt-1">Background saat ini: {{ basename($room->bg_path) }}</p>
        @endif
    </div>
    <div class="md:col-span-2 border-t border-gray-200 pt-4 mt-2">
        <h4 class="font-bold text-gray-900 mb-3">Mode Tema Tampilan</h4>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mode Tema</label>
                <select name="theme_mode" id="f_theme_mode" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm sm:text-sm">
                    @foreach(['light' => 'Light', 'dark' => 'Dark', 'auto' => 'Auto (jadwal)'] as $val => $label)
                        <option value="{{ $val }}" {{ old('theme_mode', $room->theme_mode ?? 'auto') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mulai Light</label>
                <input type="time" name="light_start" id="f_light_start" value="{{ old('light_start', $room->light_start ?? '06:00') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm sm:text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mulai Dark</label>
                <input type="time" name="dark_start" id="f_dark_start" value="{{ old('dark_start', $room->dark_start ?? '18:00') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm sm:text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Zona Waktu</label>
                <select name="timezone" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm sm:text-sm">
                    @foreach(['Asia/Jakarta' => 'WIB (Asia/Jakarta)', 'Asia/Makassar' => 'WITA (Asia/Makassar)', 'Asia/Jayapura' => 'WIT (Asia/Jayapura)'] as $val => $label)
                        <option value="{{ $val }}" {{ old('timezone', $room->timezone ?? 'Asia/Jakarta') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Pesan Kesehatan (rotasi bawah)</label>
        <textarea name="health_message" id="f_health_message" rows="2" maxlength="1000"
                  placeholder="cth: Minum air putih 2–2.5 liter per hari sesuai anjuran dokter."
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm sm:text-sm">{{ old('health_message', $room->health_message ?? '') }}</textarea>
    </div>
    <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Jadwal Sesi Siang (cth: 11:00 – 13:00)</label>
            <input type="text" name="visit_session_1" id="f_session_1" maxlength="50" value="{{ old('visit_session_1', $room->visit_session_1 ?? '11:00 – 13:00') }}"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm sm:text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Jadwal Sesi Sore (cth: 17:00 – 20:00)</label>
            <input type="text" name="visit_session_2" id="f_session_2" maxlength="50" value="{{ old('visit_session_2', $room->visit_session_2 ?? '17:00 – 20:00') }}"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm sm:text-sm">
        </div>
    </div>
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Kunjungan</label>
        <textarea name="visit_note" id="f_visit_note" rows="2" maxlength="1000"
                  placeholder="cth: Maksimal 2 pengunjung bergantian demi ketenangan pasien."
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm sm:text-sm">{{ old('visit_note', $room->visit_note ?? '') }}</textarea>
    </div>
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Pengumuman RS (rotasi bawah)</label>
        <textarea name="announcement" id="f_announcement" rows="2" maxlength="1000"
                  placeholder="cth: Pelayanan poliklinik tutup pada hari libur nasional."
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm sm:text-sm">{{ old('announcement', $room->announcement ?? '') }}</textarea>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Interval Rotasi (detik)</label>
        <input type="number" name="rotation_seconds" min="5" max="300" value="{{ old('rotation_seconds', $room->rotation_seconds ?? 20) }}"
               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm sm:text-sm">
    </div>
    <div class="md:col-span-2 flex gap-2 justify-end pt-2">
        <a href="{{ $isEdit ? route('rooms.show', $room) : route('rooms.index') }}"
           class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md text-sm">Batal</a>
        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold px-4 py-2 rounded-md text-sm">
            Simpan Perubahan
        </button>
    </div>
</form>
