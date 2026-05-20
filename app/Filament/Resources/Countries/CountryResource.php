<?php

namespace App\Filament\Resources\Countries;

use App\Filament\Resources\Countries\Pages\ManageCountries;
use App\Models\Country;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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

    protected static ?string $navigationLabel = 'Países';

    public static function getNavigationGroup(): string
    {
        return 'Sistema';
    }

    protected static ?string $modelLabel = 'País';

    protected static ?string $pluralModelLabel = 'Países';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return Gate::allows('is-super-admin');
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
                ->maxLength(5)
                ->unique(ignoreRecord: true)
                ->helperText('SV, GT, HN, US, etc.'),

            TextInput::make('flag_emoji')
                ->label('Emoji de bandera')
                ->maxLength(10),

            Toggle::make('is_active')
                ->label('Activo')
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
                    ->label('País')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('code')
                    ->label('Código')
                    ->badge()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->defaultSort('name')
            ->filters([])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
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
