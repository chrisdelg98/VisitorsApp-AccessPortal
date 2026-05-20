<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Acceso total — ve todos los países
        Gate::define('is-super-admin', fn(User $user) =>
            $user->role === 'super_admin'
        );

        // Puede crear y editar (super_admin + admin + country_manager)
        Gate::define('can-write', fn(User $user) =>
            $user->canWrite()
        );
    }
}
