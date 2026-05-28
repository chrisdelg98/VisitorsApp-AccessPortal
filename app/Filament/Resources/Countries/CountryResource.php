<?php

namespace App\Filament\Resources\Countries;

use App\Filament\Resources\Countries\Pages\ManageCountries;
use App\Models\Country;
use App\Support\CountryCatalog;
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
        $catalog = CountryCatalog::all();
        $nameOptions = [];
        foreach ($catalog as $entry) {
            $label = trim(($entry['flag_emoji'] ?? '') . ' ' . $entry['name']);
            $nameOptions[$entry['name']] = $label;
        }

        return $schema->components([
            Select::make('name')
                ->label('Country')
                ->options(function (?Country $record) use ($nameOptions): array {
                    // Si edita un país cuyo nombre no esté en el catálogo, lo preservamos.
                    if ($record && $record->name && ! isset($nameOptions[$record->name])) {
                        return [$record->name => $record->name] + $nameOptions;
                    }
                    return $nameOptions;
                })
                ->searchable()
                ->required()
                ->live()
                ->afterStateUpdated(function ($state, $set) {
                    $entry = $state ? CountryCatalog::findByName($state) : null;
                    if ($entry) {
                        $set('code',       $entry['code']);
                        $set('timezone',   $entry['timezone']);
                        $set('flag_emoji', $entry['flag_emoji']);
                    }
                })
                ->helperText('Picking a country auto-fills the code, timezone and flag.'),

            TextInput::make('code')
                ->label('Code')
                ->required()
                ->maxLength(5)
                ->unique(ignoreRecord: true)
                ->readOnly()
                ->dehydrated()
                ->helperText('Auto-filled from the selected country.'),

            TextInput::make('flag_emoji')
                ->label('Flag')
                ->maxLength(10)
                ->helperText('Auto-filled, editable if needed.'),

            Select::make('timezone')
                ->label('Timezone')
                ->options(fn(): array => array_combine(
                    \DateTimeZone::listIdentifiers(\DateTimeZone::ALL),
                    \DateTimeZone::listIdentifiers(\DateTimeZone::ALL),
                ))
                ->searchable()
                ->helperText('Auto-filled. Some countries have multiple zones (e.g. US) — change here if needed.'),

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
