<?php

namespace App\Filament\Resources\Stations;

use App\Filament\Resources\Stations\Pages\ManageStations;
use App\Filament\Traits\ScopedByCountry;
use App\Models\Country;
use App\Models\Station;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class StationResource extends Resource
{
    use ScopedByCountry;

    protected static ?string $model = Station::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDevicePhoneMobile;

    protected static ?string $navigationLabel = 'Estaciones';

    public static function getNavigationGroup(): string
    {
        return 'Gestión';
    }

    protected static ?string $modelLabel = 'Estación';

    protected static ?string $pluralModelLabel = 'Estaciones';

    protected static ?int $navigationSort = 10;

    public static function getEloquentQuery(): Builder
    {
        return static::applyCountryScope(parent::getEloquentQuery(), 'direct');
    }

    public static function canCreate(): bool
    {
        return Gate::allows('can-write');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->maxLength(100),

            TextInput::make('code')
                ->label('Código')
                ->required()
                ->maxLength(20)
                ->unique(ignoreRecord: true)
                ->helperText('Ej: EFL-001'),

            TextInput::make('location')
                ->label('Ubicación')
                ->maxLength(100),

            Select::make('country_id')
                ->label('País')
                ->options(Country::where('is_active', true)->pluck('name', 'id'))
                ->searchable()
                ->required(),

            TextInput::make('latitude')
                ->label('Latitud')
                ->numeric()
                ->minValue(-90)
                ->maxValue(90)
                ->extraInputAttributes(['step' => 'any'])
                ->placeholder('13.811938385010887'),

            TextInput::make('longitude')
                ->label('Longitud')
                ->numeric()
                ->minValue(-180)
                ->maxValue(180)
                ->extraInputAttributes(['step' => 'any'])
                ->placeholder('-89.42609853826056'),

            Toggle::make('is_active')
                ->label('Activa')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->badge(),

                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('location')
                    ->label('Ubicación')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('country.name')
                    ->label('País')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean(),

                TextColumn::make('device_model')
                    ->label('Tablet')
                    ->placeholder('Sin registrar')
                    ->description(fn(Station $record): ?string =>
                        $record->registered_at?->format('d/m/Y H:i')
                    ),
            ])
            ->defaultSort('code')
            ->filters([])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->color('gray')
                        ->visible(fn() => Gate::allows('can-write')),

                    Action::make('resetDevice')
                        ->label('Reset tablet')
                        ->icon(Heroicon::OutlinedArrowPath)
                        ->color('gray')
                        ->requiresConfirmation()
                        ->modalHeading('¿Desregistrar tablet?')
                        ->modalDescription('La tablet actual quedará desvinculada. Deberá registrarse nuevamente.')
                        ->action(fn(Station $record) => $record->unregisterDevice('admin_reset'))
                        ->visible(fn(Station $record) => $record->is_registered && Gate::allows('can-write')),

                    Action::make('deviceLogs')
                        ->label('Historial')
                        ->icon(Heroicon::OutlinedClock)
                        ->color('gray')
                        ->infolist(fn(Schema $schema, Station $record): Schema =>
                            $schema->components([
                                RepeatableEntry::make('deviceLogs')
                                    ->label('Dispositivos registrados')
                                    ->schema([
                                        TextEntry::make('device_model')->label('Modelo'),
                                        TextEntry::make('registered_at')->label('Registrado el')->dateTime('d/m/Y H:i'),
                                        TextEntry::make('unregistered_by')->label('Desregistrado por')->badge(),
                                    ])
                                    ->record($record),
                            ])
                        )
                        ->slideOver(),

                    DeleteAction::make()
                        ->color('danger')
                        ->visible(fn() => Gate::allows('is-super-admin')),
                ])->color('gray')->tooltip('Actions'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->visible(fn() => Gate::allows('is-super-admin')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageStations::route('/'),
        ];
    }
}
