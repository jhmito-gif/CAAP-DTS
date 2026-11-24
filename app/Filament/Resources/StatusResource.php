<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use App\Models\Transaction;
use App\Filament\Resources\StatusResource\Pages\ListStatuses;
use App\Filament\Resources\StatusResource\Pages\CreateStatus;
use App\Filament\Resources\StatusResource\Pages\EditStatus;
use App\Filament\Resources\StatusResource\Pages;
use App\Filament\Resources\StatusResource\RelationManagers;
use App\Models\Status;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;

class StatusResource extends Resource
{
    protected static ?string $model = Status::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-tag';

    protected static string | \UnitEnum | null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    /** Nothing here is deleted from the admin panel; changes are made by editing. */
    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    /**
     * Transactions per status name, counted once for the whole table.
     *
     * @return array<string, int>
     */
    protected static function usageCounts(): array
    {
        static $counts = null;

        return $counts ??= Transaction::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->toArray();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->striped()
            ->columns([
                TextColumn::make('name')
                    ->label('Status')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('usage')
                    ->label('In use by')
                    ->getStateUsing(fn (Status $record) => static::usageCounts()[$record->name] ?? 0)
                    ->formatStateUsing(fn ($state) => $state . ' ' . str('transaction')->plural($state))
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'info' : 'gray')
                    ->alignCenter(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                ]),
            ])
            ->emptyStateHeading('No statuses yet')
            ->emptyStateDescription('Statuses are the routing actions offices pick when forwarding a record.')
            ->emptyStateIcon('heroicon-o-tag');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStatuses::route('/'),
            'create' => CreateStatus::route('/create'),
            'edit' => EditStatus::route('/{record}/edit'),
        ];
    }
}
