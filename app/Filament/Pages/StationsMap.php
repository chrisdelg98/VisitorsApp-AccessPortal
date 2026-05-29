<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Visits\VisitResource;
use App\Models\Station;
use App\Models\Visit;
use App\Support\TzFormatter;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class StationsMap extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static ?string $navigationLabel = 'Stations Map';

    protected static ?string $title = 'Stations Map';

    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.pages.stations-map';

    public static function getNavigationGroup(): ?string
    {
        return 'Management';
    }

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
                'lat'                 => (float) $s->latitude,
                'lng'                 => (float) $s->longitude,
                'name'                => $s->name,
                'code'                => $s->code,
                'country'             => $s->country?->name ?? '—',
                'status'              => match(true) {
                    $s->is_registered && $s->is_active => 'active',
                    $s->is_active                       => 'no-tablet',
                    default                             => 'inactive',
                },
                'active_visits_count' => (int) ($s->active_visits_count ?? 0),
                'last_activity_at'    => $s->last_activity_at
                    ? TzFormatter::plain(\Carbon\Carbon::parse($s->last_activity_at), $s->country)
                    : null,
                'last_activity_utc'   => $s->last_activity_at
                    ? TzFormatter::utcIso(\Carbon\Carbon::parse($s->last_activity_at))
                    : null,
                'visits_url'          => VisitResource::getUrl('index', [
                    'tableFilters' => ['station_id' => ['value' => $s->id]],
                ]),
            ])
            ->values()
            ->toArray();

        $counts = [
            'active'    => 0,
            'no-tablet' => 0,
            'inactive'  => 0,
        ];
        foreach ($stations as $s) {
            $counts[$s['status']] = ($counts[$s['status']] ?? 0) + 1;
        }
        $counts['total'] = count($stations);

        return compact('stations', 'counts');
    }
}
