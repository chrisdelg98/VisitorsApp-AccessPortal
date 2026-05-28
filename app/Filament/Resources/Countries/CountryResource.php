<?php

namespace App\Filament\Resources\Countries;

use App\Filament\Resources\Countries\Pages\ManageCountries;
use App\Models\Country;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;

class CountryResource extends Resource
{
    protected static ?string $model = Country::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?string $navigationLabel = 'Countries';

    public static function getNavigationGroup(): string
    {
        return 'System';
    }

    protected static ?string $modelLabel = 'Country';

    protected static ?string $pluralModelLabel = 'Countries';

    protected static ?int $navigationSort = 20;

    public static function canViewAny(): bool
    {
        return Gate::allows('is-super-admin');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Name')
                ->required()
                ->maxLength(100),

            TextInput::make('code')
                ->label('Code')
                ->required()
                ->maxLength(5)
                ->unique(ignoreRecord: true)
                ->helperText('SV, GT, HN, US, etc.'),

            TextInput::make('flag_emoji')
                ->label('Flag emoji')
                ->maxLength(10),

            Select::make('timezone')
                ->label('Timezone')
                ->options(fn(): array => array_combine(
                    \DateTimeZone::listIdentifiers(\DateTimeZone::ALL),
                    \DateTimeZone::listIdentifiers(\DateTimeZone::ALL),
                ))
                ->searchable()
                ->helperText('IANA name (e.g. America/El_Salvador). Used to display times of stations in this country.'),

            Toggle::make('is_active')
                ->label('Active')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('flag_emoji')
                    ->label('')
                    ->width('40px'),

                TextColumn::make('name')
                    ->label('Country')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('code')
                    ->label('Code')
                    ->badge()
                    ->sortable(),

                TextColumn::make('timezone')
                    ->label('Timezone')
                    ->placeholder('—')
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->defaultSort('name')
            ->filters([])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()->color('gray'),
                    DeleteAction::make()->color('danger'),
                ])->color('gray')->tooltip('Actions'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCountries::route('/'),
        ];
    }
}
