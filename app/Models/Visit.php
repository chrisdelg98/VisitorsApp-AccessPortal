<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Visit extends Model
{
    protected $connection = 'mysql';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'station_id',
        'visitor_id',
        'visitor_type',
        'visit_reason',
        'visit_reason_custom',
        'visiting_person',
        'check_in',
        'check_out',
        'status',
        'badge_printed',
        'notes',
        'original_visit_id',
        'reentry_from_station_id',
        'reentry_count',
        'last_reentry_at',
        'checkout_type',
    ];

    protected function casts(): array
    {
        return [
            'check_in'        => 'datetime',
            'check_out'       => 'datetime',
            'last_reentry_at' => 'datetime',
            'badge_printed'   => 'boolean',
            'reentry_count'   => 'integer',
        ];
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(VisitImage::class);
    }

    /** Visita ORIGINAL cuando esta visita es una continuación cross-estación. */
    public function originalVisit(): BelongsTo
    {
        return $this->belongsTo(Visit::class, 'original_visit_id');
    }

    /** Visita de CONTINUACIÓN cuando esta visita fue seguida en otra sucursal. */
    public function continuationVisit(): HasOne
    {
        return $this->hasOne(Visit::class, 'original_visit_id', 'id');
    }

    /** Estación de origen cuando esta visita es una continuación cross-estación. */
    public function reentryFromStation(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'reentry_from_station_id');
    }

    /** Returns all proxy URLs for the portal's photo gallery component. */
    public function getPhotoUrlsAttribute(): array
    {
        return $this->images
            ->map(fn(VisitImage $img) => $img->proxy_url)
            ->filter()
            ->values()
            ->all();
    }

    public function getDurationInMinutesAttribute(): ?int
    {
        if (! $this->check_in || ! $this->check_out) {
            return null;
        }

        return (int) $this->check_in->diffInMinutes($this->check_out);
    }

    /** Duración legible: "30 min", "2h 15m", "1d 4h", etc. */
    public function getDurationHumanAttribute(): ?string
    {
        $min = $this->duration_in_minutes;
        if ($min === null) {
            return null;
        }
        if ($min < 60) {
            return "{$min} min";
        }

        $h = intdiv($min, 60);
        $m = $min % 60;

        if ($h < 24) {
            return $m > 0 ? "{$h}h {$m}m" : "{$h}h";
        }

        $d = intdiv($h, 24);
        $h = $h % 24;

        return $h > 0 ? "{$d}d {$h}h" : "{$d}d";
    }
}
