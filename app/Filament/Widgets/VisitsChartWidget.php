<?php

namespace App\Filament\Widgets;

use App\Models\Visit;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class VisitsChartWidget extends ChartWidget
{
    protected ?string $heading = 'Visitas por día — últimos 30 días';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $start = now()->subDays(29)->startOfDay();

        $rows = Visit::query()
            ->selectRaw('DATE(check_in) as date, COUNT(*) as total')
            ->where('check_in', '>=', $start)
            ->when(! Gate::allows('is-super-admin'), fn($q) =>
                $q->whereHas('station', fn($q2) =>
                    $q2->where('country_id', $user->country_id)
                )
            )
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date');

        // Rellenar días sin visitas con 0
        $labels = [];
        $data   = [];

        for ($i = 29; $i >= 0; $i--) {
            $date     = now()->subDays($i)->format('Y-m-d');
            $label    = now()->subDays($i)->format('d/m');
            $labels[] = $label;
            $data[]   = $rows->get($date, 0);
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Visitas',
                    'data'            => $data,
                    'backgroundColor' => 'rgba(249, 115, 22, 0.6)',
                    'borderColor'     => 'rgba(249, 115, 22, 1)',
                    'borderWidth'     => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
