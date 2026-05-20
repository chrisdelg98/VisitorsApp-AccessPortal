<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string      $id
 * @property string      $name
 * @property string      $code
 * @property string|null $location
 * @property string|null $country_id
 * @property float|null  $latitude
 * @property float|null  $longitude
 * @property string|null $device_model
 * @property string|null $device_imei
 * @property string|null $device_android_id
 * @property string|null $registered_ip
 * @property \Carbon\Carbon|null $registered_at
 * @property bool        $is_active
 * @property bool        $is_registered
 */
class Station extends Model
{
    protected $connection = 'mysql';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'location',
        'country_id',
        'latitude',
        'longitude',
        'code',
        'api_key',
        'pin',
        'pin_lookup',
        'device_imei',
        'device_android_id',
        'device_model',
        'registered_ip',
        'registered_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active'     => 'boolean',
            'registered_at' => 'datetime',
            'latitude'      => 'decimal:7',
            'longitude'     => 'decimal:7',
        ];
    }

    // Derivado: registrada si tiene device_model y registered_at
    public function getIsRegisteredAttribute(): bool
    {
        return ! is_null($this->device_model) && ! is_null($this->registered_at);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function deviceLogs(): HasMany
    {
        return $this->hasMany(StationDeviceLog::class);
    }

    public function unregisterDevice(string $unregisteredBy = 'admin_reset'): void
    {
        StationDeviceLog::create([
            'id'                => (string) \Illuminate\Support\Str::uuid(),
            'station_id'        => $this->id,
            'device_imei'       => $this->device_imei,
            'device_android_id' => $this->device_android_id,
            'device_model'      => $this->device_model,
            'registered_ip'     => $this->registered_ip,
            'registered_at'     => $this->registered_at,
            'unregistered_by'   => $unregisteredBy,
        ]);

        $this->update([
            'device_imei'       => null,
            'device_android_id' => null,
            'device_model'      => null,
            'registered_ip'     => null,
            'registered_at'     => null,
        ]);
    }
}
