<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomDisplay extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'device_name',
        'last_ip',
        'user_agent',
        'last_seen_at',
        'registered_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'registered_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('revoked_at');
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isOnline(?int $thresholdMinutes = null): bool
    {
        $thresholdMinutes ??= config('room-display.online_threshold_minutes', 5);

        return ! $this->isRevoked()
            && $this->last_seen_at !== null
            && $this->last_seen_at->greaterThanOrEqualTo(now()->subMinutes($thresholdMinutes));
    }
}
