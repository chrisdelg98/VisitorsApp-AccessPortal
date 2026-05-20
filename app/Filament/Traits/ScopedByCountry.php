<?php

namespace App\Filament\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

trait ScopedByCountry
{
    /**
     * Restringe el query al país del usuario autenticado.
     * super_admin ve todo — country_manager y viewer solo su país.
     *
     * @param  string  $relation  'direct' para country_id en la tabla raíz, o nombre de relación nested
     */
    public static function applyCountryScope(Builder $query, string $relation = 'station'): Builder
    {
        if (Gate::allows('is-super-admin')) {
            return $query;
        }

        /** @var User $user */
        $user = auth()->user();

        if ($relation === 'direct') {
            return $query->where('country_id', $user->country_id);
        }

        return $query->whereHas($relation, fn(Builder $q) =>
            $q->where('country_id', $user->country_id)
        );
    }
}
