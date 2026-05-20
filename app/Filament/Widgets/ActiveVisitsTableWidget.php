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
    protected static ?string $heading = 'Active visits now';

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
                    ->label('Visitor')
                    ->searchable(['visitors.first_name', 'visitors.last_name']),

                TextColumn::make('visitor.company')
                    ->label('Company')
                    ->placeholder('—'),

                TextColumn::make('station.code')
                    ->label('Station')
                    ->badge(),

                TextColumn::make('station.country.name')
                    ->label('Country')
                    ->toggleable(),

                TextColumn::make('visiting_person')
                    ->label('Visiting')
                    ->placeholder('—'),

                TextColumn::make('check_in')
                    ->label('Check in')
                    ->dateTime('d/m/Y H:i')
                    ->description(fn(Visit $record): string =>
                        $record->check_in->diffForHumans()
                    ),
            ])
            ->filters([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
