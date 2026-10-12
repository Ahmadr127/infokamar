<?php

namespace App\Http\Services;

use App\Models\Room;
use App\Services\ActivityLogService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RoomService
{
    public function __construct(
        protected ActivityLogService $activityLogger,
        protected RoomDisplayService $displays,
    ) {}

    public function getRooms(array $filters = [])
    {
        $query = Room::with(['currentAssignment', 'displays'])
            ->withCount(['displays as active_displays_count' => fn ($q) => $q->active()]);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('care_class', 'like', "%{$search}%")
                    ->orWhereHas('currentAssignment', fn ($qa) => $qa->where('patient_name', 'like', "%{$search}%"));
                // NOTE: PIN tidak dicari by hash; pencarian PIN ditangani
                // terpisah tanpa mengekspos kredensial (lihat docs).
            });
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (! empty($filters['connection'])) {
            $threshold = now()->subMinutes(config('room-display.online_threshold_minutes', 5));
            if ($filters['connection'] === 'online') {
                $query->whereHas('displays', fn ($q) => $q->active()->where('last_seen_at', '>=', $threshold));
            } elseif ($filters['connection'] === 'offline') {
                $query->whereDoesntHave('displays', fn ($q) => $q->active()->where('last_seen_at', '>=', $threshold));
            } elseif ($filters['connection'] === 'unregistered') {
                $query->whereDoesntHave('displays', fn ($q) => $q->active());
            }
        }

        $perPage = in_array((int) ($filters['per_page'] ?? 12), [6, 12, 24, 48])
            ? (int) ($filters['per_page'] ?? 12)
            : 12;

        return $query->latest()->paginate($perPage)->withQueryString();
    }

    public function stats(): array
    {
        $threshold = now()->subMinutes(config('room-display.online_threshold_minutes', 5));

        return [
            'total' => Room::count(),
            'active' => Room::active()->count(),
            'inactive' => Room::where('is_active', false)->count(),
            'online_displays' => \App\Models\RoomDisplay::active()
                ->where('last_seen_at', '>=', $threshold)->count(),
            'unregistered_rooms' => Room::whereDoesntHave('displays', fn ($q) => $q->active())->count(),
        ];
    }

    public function createRoom(array $data, ?UploadedFile $logo = null, ?UploadedFile $background = null): Room
    {
        return DB::transaction(function () use ($data, $logo, $background) {
            if ($logo) {
                $data['logo_path'] = $logo->store('room-logos', 'public');
            }
            if ($background) {
                $data['bg_path'] = $background->store('room-backgrounds', 'public');
            }

            $pin = $data['pin'] ?? null;
            unset($data['pin']);

            $room = Room::create($data);

            if ($pin) {
                $this->displays->setPin($room, $pin);
            }

            $this->activityLogger->log(
                $room, 'created', ['attributes' => $room->toArray()], "Ruangan {$room->code} dibuat"
            );

            return $room;
        });
    }

    public function updateRoom(Room $room, array $data, ?UploadedFile $logo = null, ?UploadedFile $background = null): Room
    {
        return DB::transaction(function () use ($room, $data, $logo, $background) {
            if ($logo) {
                if ($room->logo_path) {
                    Storage::disk('public')->delete($room->logo_path);
                }
                $data['logo_path'] = $logo->store('room-logos', 'public');
            }
            if ($background) {
                if ($room->bg_path) {
                    Storage::disk('public')->delete($room->bg_path);
                }
                $data['bg_path'] = $background->store('room-backgrounds', 'public');
            }

            $pin = $data['pin'] ?? null;
            unset($data['pin']);

            // Penempatan pasien: tutup assignment lama bila nama berubah/kosong.
            $patientName = trim($data['patient_name'] ?? '');
            $patientMrn = isset($data['patient_mrn']) && trim((string) $data['patient_mrn']) !== ''
                ? trim((string) $data['patient_mrn'])
                : null;
            unset($data['patient_name'], $data['patient_mrn']);

            $old = $room->toArray();

            $room->update($data);

            $this->syncAssignment($room, $patientName, $patientMrn);

            if ($pin) {
                $this->displays->setPin($room, $pin);
            }

            $this->activityLogger->logUpdated($room, $old, $room->fresh()->toArray());

            return $room->fresh();
        });
    }

    /**
     * Sinkron penempatan pasien. Assignment lama ditutup (ended_at)
     * setiap kali nama berubah atau dikosongkan — tidak ada sisa
     * data pasien sebelumnya yang tampil.
     */
    protected function syncAssignment(Room $room, string $patientName, ?string $mrn): void
    {
        $current = $room->currentAssignment()->first();

        if ($patientName === '') {
            if ($current) {
                $current->update(['ended_at' => now()]);
            }

            return;
        }

        if ($current && $current->patient_name === $patientName) {
            if ($mrn !== null && $current->mrn !== $mrn) {
                $current->update(['mrn' => $mrn, 'care_class' => $room->care_class]);
            }

            return;
        }

        if ($current) {
            $current->update(['ended_at' => now()]);
        }

        $room->assignments()->create([
            'patient_name' => $patientName,
            'mrn' => $mrn,
            'care_class' => $room->care_class,
            'started_at' => now(),
        ]);
    }

    public function deleteRoom(Room $room): void
    {
        $this->activityLogger->logDeleted($room);
        $room->delete();
    }
}
