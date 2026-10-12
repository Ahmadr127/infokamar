<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomPatientAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'patient_name',
        'mrn',
        'care_class',
        'started_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function scopeCurrent($query)
    {
        return $query->whereNull('ended_at');
    }

    public function isCurrent(): bool
    {
        return $this->ended_at === null;
    }
}
