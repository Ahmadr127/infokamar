<?php

namespace App\Http\Controllers;

use App\Http\Requests\Room\Store;
use App\Http\Requests\Room\Update;
use App\Http\Services\RoomDisplayService;
use App\Http\Services\RoomService;
use App\Models\Room;
use App\Models\RoomDisplay;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function __construct(
        protected RoomService $rooms,
        protected RoomDisplayService $displays,
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'is_active', 'connection', 'per_page']);
        $rooms = $this->rooms->getRooms($filters);
        $stats = $this->rooms->stats();

        return view('rooms.index', compact('rooms', 'stats', 'filters'));
    }

    public function create()
    {
        return view('rooms.create');
    }

    public function store(Store $request)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $room = $this->rooms->createRoom($data, $request->file('logo'), $request->file('background'));

        return redirect()->route('rooms.show', $room)->with('success', 'Ruangan berhasil dibuat!');
    }

    /**
     * Halaman management: form + live preview dalam satu halaman.
     */
    public function show(Room $room)
    {
        $room->load(['currentAssignment', 'assignments' => fn ($q) => $q->latest()->limit(10), 'displays' => fn ($q) => $q->latest()]);

        return view('rooms.show', compact('room'));
    }

    public function update(Update $request, Room $room)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', false);

        $this->rooms->updateRoom($room, $data, $request->file('logo'), $request->file('background'));

        return redirect()->route('rooms.show', $room)->with('success', 'Perubahan berhasil disimpan!');
    }

    public function destroy(Room $room)
    {
        $this->rooms->deleteRoom($room);

        return redirect()->route('rooms.index')->with('success', 'Ruangan berhasil dihapus!');
    }

    public function revokeDisplay(Room $room, RoomDisplay $display)
    {
        abort_unless($display->room_id === $room->id, 404);
        abort_if($display->isRevoked(), 422, 'Perangkat sudah dicabut.');

        $this->displays->revoke($display, request('reason'));

        return redirect()->route('rooms.show', $room)->with('success', 'Perangkat berhasil dicabut! Token lama tidak berlaku lagi.');
    }

    public function rotatePin(Request $request, Room $room)
    {
        $validated = $request->validate(['pin' => 'required|string|digits:4']);

        $this->displays->setPin($room, $validated['pin']);

        return redirect()->route('rooms.show', $room)->with('success', 'PIN ruangan berhasil diganti!');
    }

    /**
     * Pratinjau layar penuh (khusus admin, tanpa token perangkat).
     */
    public function preview(Room $room)
    {
        $room->load('currentAssignment');

        return view('room-display.show', [
            'display' => null,
            'room' => $room,
            'heartbeatSeconds' => 0,
            'preview' => true,
        ]);
    }

    /**
     * Landing page ruangan untuk admin (buka dari management).
     * Render langsung tanpa token perangkat.
     */
    public function openDisplay(Room $room)
    {
        return $this->preview($room);
    }
}
