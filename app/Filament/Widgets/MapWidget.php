<?php

namespace App\Filament\Widgets;

use App\Models\Station;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class MapWidget extends Widget
{
    protected string $view = 'filament.widgets.map-widget';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    protected function getViewData(): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $stations = Station::query()
            ->with('country')
            ->when(! Gate::allows('is-super-admin'), fn($q) =>
                $q->where('country_id', $user->country_id)
            )
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->map(fn(Station $s) => [
                'lat'     => (float) $s->latitude,
                'lng'     => (float) $s->longitude,
                'name'    => $s->name,
                'code'    => $s->code,
                'country' => $s->country?->name ?? '—',
                'status'  => match(true) {
                    $s->is_registered && $s->is_active => 'active',
                    $s->is_active                       => 'no-tablet',
                    default                             => 'inactive',
                },
            ])
            ->values()
            ->toArray();

        return compact('stations');
    }
}
