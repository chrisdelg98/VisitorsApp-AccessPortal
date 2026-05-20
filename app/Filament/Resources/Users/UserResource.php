<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\Country;
use App\Models\User;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Users';

    public static function getNavigationGroup(): string
    {
        return 'System';
    }

    protected static ?string $modelLabel = 'User';

    protected static ?string $pluralModelLabel = 'Users';

    protected static ?int $navigationSort = 10;

    public static function canViewAny(): bool
    {
        return Gate::allows('is-super-admin');
    }

    public static function canDelete(Model $record): bool
    {
        // super_admin accounts are protected from deletion through the panel
        return $record->role !== 'super_admin';
    }

    public static function canEdit(Model $record): bool
    {
        return true;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Name')
                ->required()
                ->maxLength(255),

            TextInput::make('email')
                ->label('Email')
                ->email()
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),

            TextInput::make('password')
                ->label('Password')
                ->password()
                ->revealable()
                ->required(fn(string $operation): bool => $operation === 'create')
                ->dehydrateStateUsing(fn(?string $state) => filled($state) ? bcrypt($state) : null)
                ->dehydrated(fn(?string $state) => filled($state))
                ->maxLength(255),

            Select::make('role')
                ->label('Role')
                ->options([
                    'super_admin'     => 'Super Admin',
                    'country_manager' => 'Country Manager',
                    'viewer'          => 'Viewer',
                ])
                ->required()
                ->default('country_manager')
                ->live()
                // Prevent changing the role of a super_admin through the panel
                ->disabled(fn(?Model $record): bool => $record?->role === 'super_admin')
                ->dehydrated(fn(?Model $record): bool => $record?->role !== 'super_admin')
                ->helperText(fn(?Model $record): ?string =>
                    $record?->role === 'super_admin'
                        ? 'The role of a super admin cannot be changed through the panel.'
                        : null
                ),

            Select::make('country_id')
                ->label('Country')
                ->options(Country::where('is_active', true)->pluck('name', 'id'))
                ->searchable()
                ->required(fn(Get $get): bool => $get('role') !== 'super_admin')
                ->hidden(fn(Get $get): bool => $get('role') === 'super_admin')
                ->helperText('Required for country_manager and viewer'),

            Toggle::make('is_active')
                ->label('Active')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),

                TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->color(fn(string $state): string => match($state) {
                        'super_admin'     => 'danger',
                        'country_manager' => 'warning',
                        'viewer'          => 'info',
                        default           => 'gray',
                    }),

                TextColumn::make('country.name')
                    ->label('Country')
                    ->placeholder('—')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => ManageUsers::route('/'),
        ];
    }
}
