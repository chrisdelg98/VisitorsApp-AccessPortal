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
use Filament\Schemas\Components\Section;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
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

    protected static ?string $navigationLabel = 'Historial de visitas';

    public static function getNavigationGroup(): string
    {
        return 'Registros';
    }

    protected static ?string $modelLabel = 'Visita';

    protected static ?string $pluralModelLabel = 'Historial de visitas';

    protected static ?int $navigationSort = 1;

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
            Section::make('Visitante')
                ->columns(2)
                ->schema([
                    TextEntry::make('visitor.full_name')->label('Nombre'),
                    TextEntry::make('visitor.document_number')->label('Documento')->placeholder('—'),
                    TextEntry::make('visitor.document_type')->label('Tipo de doc.')->badge()->placeholder('—'),
                    TextEntry::make('visitor.company')->label('Empresa')->placeholder('—'),
                    TextEntry::make('visitor.email')->label('Email')->placeholder('—'),
                    TextEntry::make('visitor.phone')->label('Teléfono')->placeholder('—'),
                ]),

            Section::make('Visita')
                ->columns(2)
                ->schema([
                    TextEntry::make('station.name')->label('Estación'),
                    TextEntry::make('station.country.name')->label('País'),
                    TextEntry::make('visitor_type')->label('Tipo de visitante'),
                    TextEntry::make('visit_reason')->label('Razón'),
                    TextEntry::make('visit_reason_custom')->label('Razón personalizada')->placeholder('—'),
                    TextEntry::make('visiting_person')->label('Visita a'),
                    TextEntry::make('check_in')->label('Entrada')->dateTime('d/m/Y H:i'),
                    TextEntry::make('check_out')->label('Salida')->dateTime('d/m/Y H:i')->placeholder('Activa'),
                    TextEntry::make('status')
                        ->label('Estado')
                        ->badge()
                        ->color(fn(string $state): string => match($state) {
                            'active'    => 'success',
                            'completed' => 'gray',
                            default     => 'gray',
                        }),
                    TextEntry::make('notes')->label('Notas')->placeholder('—')->columnSpanFull(),
                ]),

            Section::make('Imágenes')
                ->schema([
                    ImageEntry::make('images.file_path')
                        ->label('Fotos')
                        ->disk('local')
                        ->height(200),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('visitor.full_name')
                    ->label('Visitante')
                    ->searchable(['visitors.first_name', 'visitors.last_name'])
                    ->sortable(),

                TextColumn::make('station.code')
                    ->label('Estación')
                    ->badge()
                    ->sortable(),

                TextColumn::make('station.country.name')
                    ->label('País')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('visitor_type')
                    ->label('Tipo')
                    ->badge()
                    ->toggleable(),

                TextColumn::make('check_in')
                    ->label('Entrada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('check_out')
                    ->label('Salida')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('duration_in_minutes')
                    ->label('Duración (min)')
                    ->placeholder('—')
                    ->alignCenter()
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Estado')
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
                    ->label('Estado')
                    ->options([
                        'active'    => 'Activa',
                        'completed' => 'Completada',
                    ]),

                SelectFilter::make('station_id')
                    ->label('Estación')
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
                    ->label('Tipo de visitante')
                    ->options(fn() =>
                        Visit::distinct()->pluck('visitor_type', 'visitor_type')
                    ),

                Filter::make('date_range')
                    ->label('Rango de fechas')
                    ->form([
                        DatePicker::make('from')->label('Desde')->displayFormat('d/m/Y'),
                        DatePicker::make('until')->label('Hasta')->displayFormat('d/m/Y'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'],  fn($q) => $q->whereDate('check_in', '>=', $data['from']))
                            ->when($data['until'], fn($q) => $q->whereDate('check_in', '<=', $data['until']));
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
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
