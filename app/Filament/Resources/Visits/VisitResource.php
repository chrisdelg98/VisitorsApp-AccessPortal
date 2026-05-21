<?php

namespace App\Filament\Resources\Visits;

use App\Filament\Resources\Visits\Pages\ManageVisits;
use App\Filament\Traits\ScopedByCountry;
use App\Models\Station;
use App\Models\Visit;
use BackedEnum;
use Filament\Actions\ExportAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class VisitResource extends Resource
{
    use ScopedByCountry;

    protected static ?string $model = Visit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Visit History';

    public static function getNavigationGroup(): string
    {
        return 'Records';
    }

    protected static ?string $pluralModelLabel = 'Visit History';

    protected static ?string $modelLabel = 'Visit Record';

    protected static ?int $navigationSort = 10;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return static::applyCountryScope(
            parent::getEloquentQuery()->with(['visitor', 'station.country']),
            'station'
        );
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([

            // ── Fila 1: header completo en una sola section ───────────────
            Section::make()
                ->columns(6)
                ->schema([
                    TextEntry::make('visitor.full_name')
                        ->label('Visitor')
                        ->weight(FontWeight::Bold)
                        ->columnSpan(3),

                    TextEntry::make('station.name')
                        ->label('Station')
                        ->columnSpan(2),

                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->color(fn(string $state): string => match($state) {
                            'active'    => 'success',
                            'completed' => 'gray',
                            default     => 'gray',
                        })
                        ->columnSpan(1),
                ]),

            // ── Fila 2: timeline ──────────────────────────────────────────
            Section::make()
                ->columns(3)
                ->schema([
                    TextEntry::make('check_in')
                        ->label('Check in')
                        ->dateTime('d/m/Y H:i'),

                    TextEntry::make('check_out')
                        ->label('Check out')
                        ->dateTime('d/m/Y H:i')
                        ->placeholder('Still active')
                        ->color('success'),

                    TextEntry::make('duration_in_minutes')
                        ->label('Duration (min)')
                        ->placeholder('—'),
                ]),

            // ── Fila 3: visitor info + visit details (mitad y mitad) ──────
            Grid::make(2)
                ->schema([
                    Section::make('Visitor info')
                        ->columns(2)
                        ->columnSpan(1)
                        ->schema([
                            TextEntry::make('visitor.document_number')
                                ->label('Document')
                                ->placeholder('—'),
                            TextEntry::make('visitor.document_type')
                                ->label('Doc. type')
                                ->badge()
                                ->placeholder('—'),
                            TextEntry::make('visitor.email')
                                ->label('Email')
                                ->placeholder('—'),
                            TextEntry::make('visitor.phone')
                                ->label('Phone')
                                ->placeholder('—'),
                            TextEntry::make('station.country.name')
                                ->label('Country'),
                            TextEntry::make('visitor.company')
                                ->label('Company')
                                ->placeholder('—'),
                        ]),

                    Section::make('Visit details')
                        ->columns(2)
                        ->columnSpan(1)
                        ->schema([
                            TextEntry::make('visitor_type')
                                ->label('Visitor type'),
                            TextEntry::make('visit_reason')
                                ->label('Reason'),
                            TextEntry::make('visiting_person')
                                ->label('Visiting'),
                            TextEntry::make('visit_reason_custom')
                                ->label('Custom reason')
                                ->placeholder('—'),
                            TextEntry::make('notes')
                                ->label('Notes')
                                ->placeholder('—')
                                ->columnSpanFull(),
                        ]),
                ]),

            // ── Fila 4: imágenes (ancho completo, colapsada) ──────────────
            Section::make('Images')
                ->schema([
                    ImageEntry::make('images.proxy_url')
                        ->label('')
                        ->height(180),
                ])
                ->collapsible()
                ->collapsed(true),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('visitor.full_name')
                    ->label('Visitor')
                    ->searchable(['visitors.first_name', 'visitors.last_name'])
                    ->sortable(),

                TextColumn::make('station.code')
                    ->label('Station')
                    ->badge()
                    ->sortable(),

                TextColumn::make('station.country.name')
                    ->label('Country')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('visitor_type')
                    ->label('Type')
                    ->badge()
                    ->toggleable(),

                TextColumn::make('check_in')
                    ->label('Check in')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('check_out')
                    ->label('Check out')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('duration_in_minutes')
                    ->label('Duration (min)')
                    ->placeholder('—')
                    ->alignCenter()
                    ->toggleable(),

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
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active'    => 'Active',
                        'completed' => 'Completed',
                    ]),

                SelectFilter::make('station_id')
                    ->label('Station')
                    ->options(function () {
                        if (Gate::allows('is-super-admin')) {
                            return Station::orderBy('code')->pluck('name', 'id');
                        }

                        /** @var \App\Models\User $user */
                        $user = Auth::user();

                        return Station::query()
                            ->where('country_id', $user->country_id)
                            ->orderBy('code')
                            ->pluck('name', 'id');
                    })
                    ->searchable(),

                SelectFilter::make('visitor_type')
                    ->label('Visitor type')
                    ->options(fn() =>
                        Visit::distinct()->pluck('visitor_type', 'visitor_type')
                    ),

                Filter::make('date_range')
                    ->label('Date range')
                    ->form([
                        DatePicker::make('from')->label('From')->displayFormat('d/m/Y'),
                        DatePicker::make('until')->label('Until')->displayFormat('d/m/Y'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'],  fn($q) => $q->whereDate('check_in', '>=', $data['from']))
                            ->when($data['until'], fn($q) => $q->whereDate('check_in', '<=', $data['until']));
                    }),
            ])
            ->recordActions([
                ViewAction::make()->modalWidth('7xl'),
            ])
            ->toolbarActions([
                ExportAction::make()
                    ->exporter(\App\Filament\Exports\VisitExporter::class)
                    ->visible(fn() => Gate::allows('can-write')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageVisits::route('/'),
        ];
    }
}
