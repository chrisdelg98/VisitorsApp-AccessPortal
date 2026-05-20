<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int         $id
 * @property string      $name
 * @property string      $email
 * @property string      $password
 * @property string      $role
 * @property string|null $country_id
 * @property bool        $is_active
 */
class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    protected $connection = 'mysql';
    // id es bigint AUTO_INCREMENT — incrementing por defecto (true)

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'country_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, ['super_admin', 'admin', 'country_manager', 'viewer'])
            && (bool) $this->is_active;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function canWrite(): bool
    {
        return in_array($this->role, ['super_admin', 'admin', 'country_manager']);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
