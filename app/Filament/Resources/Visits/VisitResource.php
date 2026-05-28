<?php

namespace App\Filament\Resources\Visits;

use App\Filament\Resources\Visits\Pages\ManageVisits;
use App\Filament\Traits\ScopedByCountry;
use App\Models\Station;
use App\Models\Visit;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use App\Filament\Infolists\Components\PhotoGalleryEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Forms\Components\CheckboxList;
use Symfony\Component\HttpFoundation\StreamedResponse;
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
            parent::getEloquentQuery()->with(['visitor', 'station.country', 'images']),
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

            // ── Top card: summary + timeline stacked in ONE section ──
            Section::make()
                ->columnSpanFull()
                ->schema([
                    // Sub-row 1: 4-col summary
                    Grid::make(4)
                        ->schema([
                            TextEntry::make('visitor.full_name')
                                ->label('Visitor')
                                ->weight(FontWeight::Bold)
                                ->icon(Heroicon::OutlinedUser),

                            TextEntry::make('station.name')
                                ->label('Station')
                                ->icon(Heroicon::OutlinedBuildingOffice2),

                            TextEntry::make('station.country.name')
                                ->label('Country')
                                ->icon(Heroicon::OutlinedGlobeAlt),

                            TextEntry::make('status')
                                ->label('Status')
                                ->badge()
                                ->color(fn(string $state): string => match($state) {
                                    'active'    => 'success',
                                    'completed' => 'gray',
                                    default     => 'gray',
                                }),
                        ]),

                    // Sub-row 2: 4-col timeline + tipo de cierre
                    Grid::make(4)
                        ->schema([
                            TextEntry::make('check_in')
                                ->label('Check in')
                                ->dateTime('d/m/Y H:i')
                                ->icon(Heroicon::OutlinedCalendarDays),

                            TextEntry::make('check_out')
                                ->label('Check out')
                                ->dateTime('d/m/Y H:i')
                                ->placeholder('Not checked out')
                                ->icon(Heroicon::OutlinedCalendarDays),

                            TextEntry::make('duration_in_minutes')
                                ->label('Duration (min)')
                                ->placeholder('In progress')
                                ->icon(Heroicon::OutlinedClock),

                            TextEntry::make('checkout_type')
                                ->label('Closed by')
                                ->badge()
                                ->placeholder('—')
                                ->formatStateUsing(fn (?string $state): string => match ($state) {
                                    'visitor' => 'Visitor',
                                    'auto'    => 'Auto (system)',
                                    'admin'   => 'Admin',
                                    'reentry' => 'Re-entry',
                                    default   => '—',
                                })
                                ->color(fn (?string $state): string => match ($state) {
                                    'visitor' => 'success',
                                    'auto'    => 'warning',
                                    'admin'   => 'info',
                                    'reentry' => 'primary',
                                    default   => 'gray',
                                }),
                        ]),
                ]),

            // ── Recorrido entre sucursales / Re-entradas ──
            Section::make('Re-entradas y recorrido entre sucursales')
                ->icon(Heroicon::OutlinedArrowsRightLeft)
                ->columnSpanFull()
                ->visible(fn ($record) =>
                    $record->reentry_from_station_id !== null
                    || ($record->reentry_count ?? 0) > 0
                    || $record->continuationVisit !== null
                )
                ->schema([
                    // Re-entradas misma estación (mismo día, ej. salir a almorzar)
                    Grid::make(2)
                        ->visible(fn ($record) => ($record->reentry_count ?? 0) > 0)
                        ->schema([
                            TextEntry::make('reentry_count')
                                ->label('Re-entradas mismo día (misma estación)')
                                ->badge()
                                ->color('warning')
                                ->icon(Heroicon::OutlinedArrowPath),

                            TextEntry::make('last_reentry_at')
                                ->label('Último reingreso')
                                ->dateTime('d/m/Y H:i')
                                ->placeholder('—'),
                        ]),

                    // Esta visita VIENE de otra sucursal (B procede de A)
                    Grid::make(3)
                        ->visible(fn ($record) => $record->reentry_from_station_id !== null)
                        ->schema([
                            TextEntry::make('reentryFromStation.name')
                                ->label('← Procedente de la sucursal')
                                ->icon(Heroicon::OutlinedArrowLeftCircle)
                                ->weight(FontWeight::Bold)
                                ->placeholder('—'),

                            TextEntry::make('originalVisit.check_in')
                                ->label('Ingresó allí a las')
                                ->dateTime('d/m/Y H:i')
                                ->placeholder('—'),

                            TextEntry::make('originalVisit.check_out')
                                ->label('Cierre de aquella visita')
                                ->dateTime('d/m/Y H:i')
                                ->placeholder('—'),
                        ]),

                    // Esta visita SE CONTINUÓ en otra sucursal (A se continúa en B)
                    Grid::make(3)
                        ->visible(fn ($record) => $record->continuationVisit !== null)
                        ->schema([
                            TextEntry::make('continuationVisit.station.name')
                                ->label('→ Continuó en la sucursal')
                                ->icon(Heroicon::OutlinedArrowRightCircle)
                                ->weight(FontWeight::Bold)
                                ->placeholder('—'),

                            TextEntry::make('continuationVisit.check_in')
                                ->label('Ingresó allá a las')
                                ->dateTime('d/m/Y H:i')
                                ->placeholder('—'),

                            TextEntry::make('continuationVisit.status')
                                ->label('Estado en destino')
                                ->badge()
                                ->placeholder('—')
                                ->color(fn (?string $state): string => match ($state) {
                                    'active'    => 'success',
                                    'completed' => 'gray',
                                    default     => 'gray',
                                }),
                        ]),
                ]),

            // ── Bottom row: three equal cards ──
            Grid::make(3)
                ->columnSpanFull()
                ->schema([
                    Section::make('Visitor Information')
                        ->columnSpan(1)
                        ->icon(Heroicon::OutlinedUser)
                        ->schema([
                            TextEntry::make('visitor.document_number')->label('Document')->placeholder('—'),
                            TextEntry::make('visitor.document_type')->label('Doc. type')->badge()->placeholder('—'),
                            TextEntry::make('visitor.email')->label('Email')->placeholder('—'),
                            TextEntry::make('visitor.phone')->label('Phone')->placeholder('—'),
                            TextEntry::make('visitor.company')->label('Company')->placeholder('—'),
                        ]),

                    Section::make('Visit Details')
                        ->columnSpan(1)
                        ->icon(Heroicon::OutlinedClipboardDocumentList)
                        ->schema([
                            TextEntry::make('visitor_type')->label('Visitor type'),
                            TextEntry::make('visit_reason')->label('Reason'),
                            TextEntry::make('visiting_person')->label('Visiting'),
                            TextEntry::make('visit_reason_custom')->label('Custom reason')->placeholder('—'),
                            TextEntry::make('notes')->label('Notes')->placeholder('—'),
                        ]),

                    Section::make('Images')
                        ->columnSpan(1)
                        ->icon(Heroicon::OutlinedPhoto)
                        ->collapsible()
                        ->schema([
                            PhotoGalleryEntry::make('photo_urls')->hiddenLabel(),
                        ]),
                ]),
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

                TextColumn::make('checkout_type')
                    ->label('Closed by')
                    ->badge()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'visitor' => 'Visitor',
                        'auto'    => 'Auto',
                        'admin'   => 'Admin',
                        'reentry' => 'Re-entry',
                        default   => '—',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'visitor' => 'success',
                        'auto'    => 'warning',
                        'admin'   => 'info',
                        'reentry' => 'primary',
                        default   => 'gray',
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

                SelectFilter::make('checkout_type')
                    ->label('Closed by')
                    ->options([
                        'visitor' => 'Visitor (marked exit)',
                        'auto'    => 'Auto (system)',
                        'admin'   => 'Admin (manual)',
                        'reentry' => 'Re-entry (continued elsewhere)',
                    ]),

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
                ViewAction::make()
                    ->modalWidth('7xl')
                    ->modalHeading('Visit Record')
                    ->modalDescription('Detailed information about this visit')
                    ->modalIcon(Heroicon::OutlinedIdentification)
                    ->modalIconColor('primary'),
            ])
            ->toolbarActions([
                Action::make('export')
                    ->label('Export')
                    ->icon(Heroicon::OutlinedDocumentArrowDown)
                    ->color('primary')
                    ->modalWidth('3xl')
                    ->modalHeading('Export Visits')
                    ->modalDescription('Select format and columns. The file downloads directly to your computer.')
                    ->modalIcon(Heroicon::OutlinedDocumentArrowDown)
                    ->modalSubmitActionLabel('Download')
                    ->form([
                        Select::make('format')
                            ->label('Format')
                            ->options(['xlsx' => 'Excel (.xlsx)', 'csv' => 'CSV (.csv)'])
                            ->default('xlsx')
                            ->required()
                            ->native(false),

                        CheckboxList::make('columns')
                            ->label('Columns')
                            ->options(static::getExportableColumns())
                            ->default(array_keys(static::getExportableColumns()))
                            ->columns(3)
                            ->bulkToggleable()
                            ->required(),
                    ])
                    ->action(function (array $data, $livewire): StreamedResponse {
                        $selected = array_intersect_key(
                            static::getExportableColumns(),
                            array_flip($data['columns'])
                        );

                        $query = method_exists($livewire, 'getFilteredTableQuery')
                            ? $livewire->getFilteredTableQuery()
                            : static::getEloquentQuery();

                        $query->with(['visitor', 'station.country']);

                        $format    = $data['format'] ?? 'xlsx';
                        $timestamp = now()->format('Y-m-d-His');

                        if ($format === 'xlsx') {
                            return response()->streamDownload(function () use ($query, $selected) {
                                $writer = new \OpenSpout\Writer\XLSX\Writer();
                                $writer->openToFile('php://output');
                                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(array_values($selected)));

                                $query->chunk(500, function ($rows) use ($writer, $selected) {
                                    foreach ($rows as $row) {
                                        $values = [];
                                        foreach (array_keys($selected) as $key) {
                                            $v = data_get($row, $key);
                                            if ($v instanceof \Carbon\CarbonInterface) {
                                                $v = $v->format('d/m/Y H:i');
                                            }
                                            $values[] = $v ?? '';
                                        }
                                        $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues($values));
                                    }
                                });

                                $writer->close();
                            }, "visits-{$timestamp}.xlsx", [
                                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ]);
                        }

                        // CSV fallback
                        return response()->streamDownload(function () use ($query, $selected) {
                            $handle = fopen('php://output', 'w');
                            fwrite($handle, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
                            fputcsv($handle, array_values($selected));

                            $query->chunk(500, function ($rows) use ($handle, $selected) {
                                foreach ($rows as $row) {
                                    $csv = [];
                                    foreach (array_keys($selected) as $key) {
                                        $v = data_get($row, $key);
                                        if ($v instanceof \Carbon\CarbonInterface) {
                                            $v = $v->format('d/m/Y H:i');
                                        }
                                        $csv[] = (string) ($v ?? '');
                                    }
                                    fputcsv($handle, $csv);
                                }
                            });

                            fclose($handle);
                        }, "visits-{$timestamp}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
                    })
                    ->visible(fn() => Gate::allows('can-write')),
            ]);
    }

    /** Single source of truth for CSV export columns: dot-path => label */
    protected static function getExportableColumns(): array
    {
        return [
            'id'                       => 'ID',
            'visitor.full_name'        => 'Visitor',
            'visitor.document_number'  => 'Document',
            'visitor.document_type'    => 'Doc. type',
            'visitor.company'          => 'Company',
            'visitor.email'            => 'Visitor email',
            'visitor.phone'            => 'Visitor phone',
            'station.code'             => 'Station code',
            'station.name'             => 'Station name',
            'station.country.name'     => 'Country',
            'visitor_type'             => 'Visitor type',
            'visit_reason'             => 'Reason',
            'visit_reason_custom'      => 'Custom reason',
            'visiting_person'          => 'Visiting',
            'check_in'                 => 'Check in',
            'check_out'                => 'Check out',
            'duration_in_minutes'      => 'Duration (min)',
            'status'                   => 'Status',
            'badge_printed'            => 'Badge printed',
            'notes'                    => 'Notes',
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageVisits::route('/'),
        ];
    }
}
