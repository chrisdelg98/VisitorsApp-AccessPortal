<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Visits\VisitResource;
use App\Models\Station;
use App\Models\Visit;
use App\Support\TzFormatter;
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
            ->withCount(['visits as active_visits_count' => fn($q) =>
                $q->where('status', 'active')
            ])
            ->addSelect([
                'last_activity_at' => Visit::query()
                    ->selectRaw('MAX(check_in)')
                    ->whereColumn('station_id', 'stations.id'),
            ])
            ->get()
            ->map(fn(Station $s) => [
                'lat'                  => (float) $s->latitude,
                'lng'                  => (float) $s->longitude,
                'name'                 => $s->name,
                'code'                 => $s->code,
                'country'              => $s->country?->name ?? '—',
                'status'               => match(true) {
                    $s->is_registered && $s->is_active => 'active',
                    $s->is_active                       => 'no-tablet',
                    default                             => 'inactive',
                },
                'active_visits_count'  => (int) ($s->active_visits_count ?? 0),
                'last_activity_at'     => $s->last_activity_at
                    ? TzFormatter::plain(\Carbon\Carbon::parse($s->last_activity_at), $s->country)
                    : null,
                'last_activity_utc'    => $s->last_activity_at
                    ? TzFormatter::utcIso(\Carbon\Carbon::parse($s->last_activity_at))
                    : null,
                'visits_url'           => VisitResource::getUrl('index', [
                    'tableFilters' => ['station_id' => ['value' => $s->id]],
                ]),
            ])
            ->values()
            ->toArray();

        return compact('stations');
    }
}
