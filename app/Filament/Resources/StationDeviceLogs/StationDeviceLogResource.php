<?php

namespace App\Filament\Resources\StationDeviceLogs;

use App\Filament\Resources\StationDeviceLogs\Pages\ListStationDeviceLogs;
use App\Models\StationDeviceLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class StationDeviceLogResource extends Resource
{
    protected static ?string $model = StationDeviceLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDevicePhoneMobile;

    protected static ?string $navigationLabel = 'Dispositivos registrados';

    public static function getNavigationGroup(): string
    {
        return 'Registros';
    }

    protected static ?string $modelLabel = 'Registro de dispositivo';

    protected static ?string $pluralModelLabel = 'Dispositivos registrados';

    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['station.country']);

        if (Gate::allows('is-super-admin')) {
            return $query;
        }

        /** @var \App\Models\User $user */
        $user = \Illuminate\Support\Facades\Auth::user();

        return $query->whereHas('station', fn(Builder $q) =>
            $q->where('country_id', $user->country_id)
        );
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('station.code')
                    ->label('Estación')
                    ->badge()
                    ->sortable()
                    ->searchable(),

                TextColumn::make('station.name')
                    ->label('Nombre estación')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('station.country.name')
                    ->label('País')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('device_model')
                    ->label('Modelo')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('device_android_id')
                    ->label('Android ID')
                    ->searchable()
                    ->placeholder('—')
                    ->fontFamily('mono')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('device_imei')
                    ->label('IMEI')
                    ->searchable()
                    ->placeholder('—')
                    ->fontFamily('mono')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('registered_ip')
                    ->label('IP registrada')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('registered_at')
                    ->label('Registrada el')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('unregistered_by')
                    ->label('Desregistrada por')
                    ->badge()
                    ->color(fn(string $state): string => match($state) {
                        'admin_reset'   => 'warning',
                        'device_logout' => 'info',
                        default         => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Fecha log')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('unregistered_by')
                    ->label('Tipo de desregistro')
                    ->options([
                        'admin_reset'   => 'Reset por admin',
                        'device_logout' => 'Logout del dispositivo',
                    ]),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStationDeviceLogs::route('/'),
        ];
    }
}
