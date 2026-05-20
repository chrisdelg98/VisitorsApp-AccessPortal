<?php

namespace App\Filament\Resources\Visitors;

use App\Filament\Resources\Visitors\Pages\ManageVisitors;
use App\Models\Visitor;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class VisitorResource extends Resource
{
    protected static ?string $model = Visitor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Visitors';

    public static function getNavigationGroup(): string
    {
        return 'Records';
    }

    protected static ?string $pluralModelLabel = 'Visitors';

    protected static ?string $modelLabel = 'Visitor';

    protected static ?int $navigationSort = 20;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withCount('visits')
            ->withMax('visits', 'check_in');

        if (Gate::allows('is-super-admin')) {
            return $query;
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        return $query->whereHas('visits', fn(Builder $q) =>
            $q->whereHas('station', fn(Builder $q2) =>
                $q2->where('country_id', $user->country_id)
            )
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
                TextColumn::make('full_name')
                    ->label('Visitor')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable('first_name'),

                TextColumn::make('document_number')
                    ->label('Document')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('document_type')
                    ->label('Type')
                    ->badge()
                    ->placeholder('—'),

                TextColumn::make('company')
                    ->label('Company')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('visits_count')
                    ->label('Visits')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('visits_max_check_in')
                    ->label('Last visit')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->defaultSort('visits_max_check_in', 'desc')
            ->filters([])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageVisitors::route('/'),
        ];
    }
}
