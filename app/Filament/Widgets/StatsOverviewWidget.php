<?php

namespace App\Filament\Widgets;

use App\Models\Station;
use App\Models\Visit;
use App\Models\Visitor;
use Filament\Widgets\StatsOverviewWidget as BaseStatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class StatsOverviewWidget extends BaseStatsOverviewWidget
{
    protected ?string $pollingInterval = '30s';

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $isSuperAdmin = Gate::allows('is-super-admin');

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $visitQuery = Visit::query()
            ->when(! $isSuperAdmin, fn($q) =>
                $q->whereHas('station', fn($q2) =>
                    $q2->where('country_id', $user->country_id)
                )
            );

        $stationQuery = Station::query()
            ->when(! $isSuperAdmin, fn($q) =>
                $q->where('country_id', $user->country_id)
            );

        $activeNow   = (clone $visitQuery)->where('status', 'active')->count();
        $today       = (clone $visitQuery)->whereDate('check_in', today())->count();
        $thisMonth   = (clone $visitQuery)
            ->whereYear('check_in', now()->year)
            ->whereMonth('check_in', now()->month)
            ->count();
        $activeStations = (clone $stationQuery)->where('is_active', true)->count();
        $totalVisitors  = $isSuperAdmin
            ? Visitor::count()
            : Visitor::whereHas('visits', fn($q) =>
                $q->whereHas('station', fn($q2) =>
                    $q2->where('country_id', $user->country_id)
                )
            )->count();

        return [
            Stat::make('Visitas activas', $activeNow)
                ->description('En este momento')
                ->color($activeNow > 0 ? 'success' : 'gray')
                ->icon('heroicon-o-user-group'),

            Stat::make('Visitas hoy', $today)
                ->description(now()->format('d/m/Y'))
                ->color('primary')
                ->icon('heroicon-o-calendar-days'),

            Stat::make('Visitas este mes', $thisMonth)
                ->description(now()->translatedFormat('F Y'))
                ->color('primary')
                ->icon('heroicon-o-chart-bar'),

            Stat::make('Estaciones activas', $activeStations)
                ->color('info')
                ->icon('heroicon-o-device-phone-mobile'),

            Stat::make('Visitantes registrados', $totalVisitors)
                ->color('gray')
                ->icon('heroicon-o-users'),
        ];
    }
}
