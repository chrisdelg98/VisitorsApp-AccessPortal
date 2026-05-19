<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
