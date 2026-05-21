<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    ];

    protected function casts(): array
    {
        return [
            'check_in'      => 'datetime',
            'check_out'     => 'datetime',
            'badge_printed' => 'boolean',
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
}
