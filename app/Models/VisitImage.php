<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitImage extends Model
{
    protected $connection = 'mysql';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'visit_id',
        'type',
        'file_path',
        'file_hash',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }
}
