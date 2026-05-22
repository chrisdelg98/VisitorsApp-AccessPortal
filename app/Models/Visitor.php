<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string      $id
 * @property string      $first_name
 * @property string      $last_name
 * @property string      $full_name
 * @property string|null $document_number
 * @property string|null $document_type
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $company
 */
class Visitor extends Model
{
    protected $connection = 'mysql';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'first_name',
        'last_name',
        'document_number',
        'document_type',
        'email',
        'phone',
        'company',
    ];

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    /** Most recent visit (with eager-load friendly relationship API). */
    public function latestVisit(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Visit::class)->latestOfMany('check_in');
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /** Most recent personal_photo (proxy URL) — uses the latestVisit relation if loaded. */
    public function getFacePhotoUrlAttribute(): ?string
    {
        // Prefer eager-loaded latestVisit if present (avoids extra query)
        $visit = $this->relationLoaded('latestVisit')
            ? $this->latestVisit
            : $this->visits()
                ->orderByDesc('check_in')
                ->first();

        if (! $visit) {
            return null;
        }

        $img = $visit->images
            ->firstWhere('type', 'personal_photo')
            ?? $visit->images()->where('type', 'personal_photo')->first();

        return $img?->proxy_url;
    }
}
