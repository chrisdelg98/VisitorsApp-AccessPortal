<?php

namespace App\Filament\Resources\Visitors\Pages;

use App\Filament\Resources\Visitors\VisitorResource;
use App\Filament\Resources\Visits\VisitResource;
use App\Models\Visit;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class VisitorHistory extends Page implements HasTable
{
    use InteractsWithRecord;
    use InteractsWithTable;

    protected static string $resource = VisitorResource::class;

    protected string $view = 'filament.resources.visitors.pages.visitor-history';

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string
    {
        return 'Visit history';
    }

    public function getBreadcrumb(): string
    {
        return $this->getRecord()->full_name;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Visit::query()
                    ->where('visitor_id', $this->getRecord()->id)
                    ->with(['station.country', 'images', 'visitor'])
            )
            ->columns([
                TextColumn::make('check_in')
                    ->label('Check in')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('check_out')
                    ->label('Check out')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Still active')
                    ->sortable(),

                TextColumn::make('station.code')
                    ->label('Station')
                    ->badge(),

                TextColumn::make('station.name')
                    ->label('Station name'),

                TextColumn::make('station.country.name')
                    ->label('Country')
                    ->toggleable(),

                TextColumn::make('visitor_type')
                    ->label('Type')
                    ->badge()
                    ->toggleable(),

                TextColumn::make('duration_in_minutes')
                    ->label('Duration (min)')
                    ->placeholder('—')
                    ->alignCenter(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match($state) {
                        'active'    => 'success',
                        'completed' => 'gray',
                        default     => 'gray',
                    }),
            ])
            ->defaultSort('check_in', 'desc')
            ->recordActions([
                ViewAction::make()
                    ->modalWidth('7xl')
                    ->modalHeading('Visit Record')
                    ->modalDescription('Detailed information about this visit')
                    ->modalIcon(Heroicon::OutlinedIdentification)
                    ->modalIconColor('primary')
                    ->schema(fn(Schema $schema): Schema => VisitResource::infolist($schema)),
            ]);
    }
}
