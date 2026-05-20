<?php

namespace App\Filament\Widgets;

use App\Models\Visit;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ActiveVisitsTableWidget extends TableWidget
{
    protected static ?string $heading = 'Visitas activas ahora';

    protected ?string $pollingInterval = '30s';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                /** @var \App\Models\User $user */
                $user = Auth::user();

                return Visit::query()
                    ->with(['visitor', 'station'])
                    ->where('status', 'active')
                    ->when(! Gate::allows('is-super-admin'), fn($q) =>
                        $q->whereHas('station', fn($q2) =>
                            $q2->where('country_id', $user->country_id)
                        )
                    )
                    ->orderBy('check_in', 'desc');
            })
            ->columns([
                TextColumn::make('visitor.full_name')
                    ->label('Visitante')
                    ->searchable(['visitors.first_name', 'visitors.last_name']),

                TextColumn::make('visitor.company')
                    ->label('Empresa')
                    ->placeholder('—'),

                TextColumn::make('station.code')
                    ->label('Estación')
                    ->badge(),

                TextColumn::make('station.country.name')
                    ->label('País')
                    ->toggleable(),

                TextColumn::make('visiting_person')
                    ->label('Visita a')
                    ->placeholder('—'),

                TextColumn::make('check_in')
                    ->label('Entrada')
                    ->dateTime('d/m/Y H:i')
                    ->description(fn(Visit $record): string =>
                        'hace ' . $record->check_in->diffForHumans(now(), true)
                    ),
            ])
            ->filters([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
