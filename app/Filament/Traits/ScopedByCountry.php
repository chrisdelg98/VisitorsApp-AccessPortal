<?php

namespace App\Filament\Traits;

use Illuminate\Database\Eloquent\Builder;

trait ScopedByCountry
{
    /**
     * Restringe el query al país del usuario autenticado.
     * Los super_admin ven todo — country_manager y viewer solo su país.
     *
     * @param  string  $relation  Relación que tiene country_id ('station' para visits, directo para stations)
     */
    public static function applyCountryScope(Builder $query, string $relation = 'station'): Builder
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return $query;
        }

        if ($relation === 'direct') {
            return $query->where('country_id', $user->country_id);
        }

        return $query->whereHas($relation, fn(Builder $q) =>
            $q->where('country_id', $user->country_id)
        );
    }
}
