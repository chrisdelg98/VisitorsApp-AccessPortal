<?php

namespace App\Filament\Resources\Stations;

use App\Filament\Resources\Stations\Pages\ManageStations;
use App\Filament\Traits\ScopedByCountry;
use App\Models\Country;
use App\Models\Station;
use App\Support\TzFormatter;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
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

    protected static ?string $navigationLabel = 'Stations';

    public static function getNavigationGroup(): string
    {
        return 'Management';
    }

    protected static ?string $modelLabel = 'Station';

    protected static ?string $pluralModelLabel = 'Stations';

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
                ->label('Name')
                ->required()
                ->maxLength(100),

            TextInput::make('code')
                ->label('Code')
                ->required()
                ->maxLength(20)
                ->unique(ignoreRecord: true)
                ->helperText('e.g. EFL-001'),

            TextInput::make('location')
                ->label('Location')
                ->maxLength(100),

            Select::make('country_id')
                ->label('Country')
                ->options(Country::where('is_active', true)->pluck('name', 'id'))
                ->searchable()
                ->required(),

            TextInput::make('latitude')
                ->label('Latitude')
                ->numeric()
                ->minValue(-90)
                ->maxValue(90)
                ->extraInputAttributes(['step' => 'any'])
                ->placeholder('13.811938385010887'),

            TextInput::make('longitude')
                ->label('Longitude')
                ->numeric()
                ->minValue(-180)
                ->maxValue(180)
                ->extraInputAttributes(['step' => 'any'])
                ->placeholder('-89.42609853826056'),

            Toggle::make('is_active')
                ->label('Active')
                ->default(true),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columnSpanFull()
                ->schema([
                    Grid::make(4)
                        ->schema([
                            TextEntry::make('code')
                                ->label('Code')
                                ->badge()
                                ->weight(FontWeight::Bold),

                            TextEntry::make('name')
                                ->label('Name'),

                            TextEntry::make('country.name')
                                ->label('Country')
                                ->icon(Heroicon::OutlinedGlobeAlt),

                            IconEntry::make('is_active')
                                ->label('Active')
                                ->boolean(),
                        ]),

                    Grid::make(3)
                        ->schema([
                            TextEntry::make('location')
                                ->label('Location')
                                ->placeholder('—'),

                            TextEntry::make('latitude')
                                ->label('Latitude')
                                ->placeholder('—'),

                            TextEntry::make('longitude')
                                ->label('Longitude')
                                ->placeholder('—'),
                        ]),
                ]),

            Section::make('Tablet')
                ->columnSpanFull()
                ->icon(Heroicon::OutlinedDevicePhoneMobile)
                ->schema([
                    Grid::make(3)
                        ->schema([
                            TextEntry::make('device_model')
                                ->label('Model')
                                ->placeholder('Not registered'),

                            TextEntry::make('registered_at')
                                ->label('Registered at')
                                ->html()
                                ->formatStateUsing(fn(Station $record) =>
                                    TzFormatter::forCountry($record->registered_at, $record->country)
                                )
                                ->placeholder('—'),

                            TextEntry::make('registered_ip')
                                ->label('IP')
                                ->placeholder('—'),
                        ]),

                    Grid::make(2)
                        ->schema([
                            TextEntry::make('device_imei')
                                ->label('IMEI')
                                ->placeholder('—'),

                            TextEntry::make('device_android_id')
                                ->label('Android ID')
                                ->placeholder('—'),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->badge(),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('location')
                    ->label('Location')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('country.name')
                    ->label('Country')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('device_model')
                    ->label('Tablet')
                    ->placeholder('Not registered')
                    ->description(fn(Station $record): ?string =>
                        TzFormatter::plain($record->registered_at, $record->country)
                    ),
            ])
            ->defaultSort('code')
            ->filters([])
            ->recordAction('view')
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->color('gray')
                        ->slideOver()
                        ->modalHeading('Station details'),

                    EditAction::make()
                        ->color('gray')
                        ->visible(fn() => Gate::allows('can-write')),

                    Action::make('resetDevice')
                        ->label('Reset tablet')
                        ->icon(Heroicon::OutlinedArrowPath)
                        ->color('gray')
                        ->requiresConfirmation()
                        ->modalHeading('Unregister tablet?')
                        ->modalDescription('The current tablet will be unlinked. It will need to be registered again.')
                        ->action(fn(Station $record) => $record->unregisterDevice('admin_reset'))
                        ->visible(fn(Station $record) => $record->is_registered && Gate::allows('can-write')),

                    Action::make('deviceLogs')
                        ->label('History')
                        ->icon(Heroicon::OutlinedClock)
                        ->color('gray')
                        ->slideOver()
                        ->modalHeading('Tablet history')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close')
                        ->infolist(fn(Schema $schema, Station $record): Schema =>
                            $schema->components([
                                Section::make('Currently registered')
                                    ->icon(Heroicon::OutlinedCheckCircle)
                                    ->visible(fn() => $record->is_registered)
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                TextEntry::make('current_model')
                                                    ->label('Model')
                                                    ->state(fn() => $record->device_model)
                                                    ->placeholder('—'),
                                                TextEntry::make('current_registered_at')
                                                    ->label('Registered at')
                                                    ->html()
                                                    ->state(fn() =>
                                                        TzFormatter::forCountry($record->registered_at, $record->country)
                                                    )
                                                    ->placeholder('—'),
                                                TextEntry::make('current_status')
                                                    ->label('Status')
                                                    ->state('Active session')
                                                    ->badge()
                                                    ->color('success'),
                                            ]),
                                    ]),

                                Section::make('Previous pairings')
                                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                                    ->description(fn() =>
                                        $record->deviceLogs->isEmpty()
                                            ? 'No previous pairings recorded.'
                                            : 'Each row is a tablet that was paired with this station and later unregistered.'
                                    )
                                    ->schema([
                                        RepeatableEntry::make('logs')
                                            ->hiddenLabel()
                                            ->state(fn() => $record->deviceLogs)
                                            ->columns([
                                                'default' => 1,
                                                'md'      => 2,
                                                'lg'      => 4,
                                            ])
                                            ->schema([
                                                TextEntry::make('device_model')
                                                    ->label('Model')
                                                    ->placeholder('—'),
                                                TextEntry::make('registered_at')
                                                    ->label('Registered')
                                                    ->html()
                                                    ->formatStateUsing(fn($state) =>
                                                        $state ? TzFormatter::forCountry(\Carbon\Carbon::parse($state), $record->country) : null
                                                    )
                                                    ->placeholder('—'),
                                                TextEntry::make('unregistered_at')
                                                    ->label('Unregistered')
                                                    ->html()
                                                    ->formatStateUsing(fn($state) =>
                                                        $state ? TzFormatter::forCountry(\Carbon\Carbon::parse($state), $record->country) : null
                                                    )
                                                    ->placeholder('—'),
                                                TextEntry::make('unregistered_by')
                                                    ->label('Reason')
                                                    ->badge()
                                                    ->placeholder('—'),
                                            ])
                                            ->visible(fn() => $record->deviceLogs->isNotEmpty()),
                                    ]),
                            ])
                        ),

                    DeleteAction::make()
                        ->color('danger')
                        ->visible(fn() => Gate::allows('is-super-admin'))
                        ->before(function (Station $record, DeleteAction $action) {
                            $visitsCount = $record->visits()->count();
                            if ($visitsCount > 0) {
                                Notification::make()
                                    ->title('Cannot delete this station')
                                    ->body("Station {$record->code} has {$visitsCount} visit(s) in its history. To preserve the audit trail, deactivate it instead (Edit → toggle Active off).")
                                    ->danger()
                                    ->persistent()
                                    ->send();

                                $action->cancel();
                            }
                        }),
                ])->color('gray')->tooltip('Actions'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->visible(fn() => Gate::allows('is-super-admin'))
                    ->before(function (\Illuminate\Database\Eloquent\Collection $records, DeleteBulkAction $action) {
                        $blocked = $records->filter(fn(Station $s) => $s->visits()->exists());
                        if ($blocked->isNotEmpty()) {
                            $codes = $blocked->pluck('code')->take(5)->implode(', ');
                            $extra = $blocked->count() > 5 ? ' …' : '';
                            Notification::make()
                                ->title('Some stations cannot be deleted')
                                ->body("These have visit history and must stay for auditing: {$codes}{$extra}. Deactivate them instead.")
                                ->danger()
                                ->persistent()
                                ->send();

                            $action->cancel();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageStations::route('/'),
        ];
    }
}
