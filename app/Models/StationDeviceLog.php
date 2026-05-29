<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StationDeviceLog extends Model
{
    protected $connection = 'mysql';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'station_id',
        'device_imei',
        'device_android_id',
        'device_model',
        'registered_ip',
        'registered_at',
        'unregistered_by',
        'unregistered_at',
    ];

    protected function casts(): array
    {
        return [
            'registered_at'   => 'datetime',
            'unregistered_at' => 'datetime',
        ];
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }
}
