<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'care_class',
        'bed',
        'doctor_name',
        'is_active',
        'greeting',
        'logo_path',
        'bg_path',
        'theme',
        'theme_mode',
        'light_start',
        'dark_start',
        'timezone',
        'health_message',
        'visit_schedule',
        'visit_session_1',
        'visit_session_2',
        'visit_note',
        'announcement',
        'rotation_seconds',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'theme' => 'array',
            'rotation_seconds' => 'integer',
        ];
    }

    public function displays(): HasMany
    {
        return $this->hasMany(RoomDisplay::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(RoomPatientAssignment::class)->latest('started_at');
    }

    public function currentAssignment(): HasOne
    {
        return $this->hasOne(RoomPatientAssignment::class)
            ->whereNull('ended_at')
            ->latest('started_at');
    }

    public function activeDisplays(): HasMany
    {
        return $this->hasMany(RoomDisplay::class)->whereNull('revoked_at');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function hasPin(): bool
    {
        return ! empty($this->getAttributes()['pin_hash'] ?? null);
    }

    public function themeValue(string $key, mixed $default = null): mixed
    {
        return $this->theme[$key] ?? config("room-display.defaults.theme.{$key}", $default);
    }

    /**
     * Tentukan tema aktif (light|dark) berdasarkan mode + jadwal.
     * Periode light = [light_start, dark_start); selain itu dark.
     * Otomatis benar saat melewati tengah malam.
     */
    public function resolveTheme(?\Carbon\Carbon $now = null): string
    {
        $mode = $this->theme_mode ?? 'auto';
        if ($mode === 'light' || $mode === 'dark') {
            return $mode;
        }

        $tz = $this->timezone ?: 'Asia/Jakarta';
        $now = ($now ? $now->copy() : now())->timezone($tz);
        $hm = $now->format('H:i');

        $light = $this->light_start ?: '06:00';
        $dark = $this->dark_start ?: '18:00';

        if ($light <= $dark) {
            return ($hm >= $light && $hm < $dark) ? 'light' : 'dark';
        }

        // Rentang light melewati tengah malam (cth light 20:00, dark 05:00).
        return ($hm >= $light || $hm < $dark) ? 'light' : 'dark';
    }

    /**
     * Konten bawah untuk ticker (non-kosong saja).
     */
    public function rotatingContents(): array
    {
        return collect([
            $this->health_message,
            $this->announcement,
        ])->map(fn ($v) => trim((string) $v))->filter()->values()->all();
    }

    /**
     * URL background area utama (upload ruangan > default global > null).
     */
    public function backgroundUrl(): ?string
    {
        if ($this->bg_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->bg_path)) {
            return asset('storage/'.$this->bg_path);
        }

        $default = config('room-display.defaults.background');
        if ($default && file_exists(public_path($default))) {
            return asset($default);
        }

        return null;
    }
}
