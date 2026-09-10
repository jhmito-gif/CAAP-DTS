<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Filters\SelectFilter;
use App\Models\Office;
use App\Filament\Resources\RecordResource\Pages\ListRecords;
use App\Filament\Resources\RecordResource\Pages\CreateRecord;
use App\Filament\Resources\RecordResource\Pages\EditRecord;
use App\Filament\Resources\RecordResource\Pages;
use App\Filament\Resources\RecordResource\RelationManagers;
use App\Models\Record;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RecordResource extends Resource
{
    protected static ?string $model = Record::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected static string | \UnitEnum | null $navigationGroup = 'Records';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['reference', 'origin_reference', 'subject'];
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Subject' => \Illuminate\Support\Str::limit($record->subject, 50),
            'Owner' => $record->owner,
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('reference')
                    ->required()
                    ->maxLength(255),
                TextInput::make('origin_reference')
                    ->maxLength(255),
                TextInput::make('subject')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reference')
                    ->label('Reference')
                    ->weight('bold')
                    ->copyable()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('origin_reference')
                    ->label('Origin Ref')
                    ->placeholder('—')
                    ->toggleable()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('subject')
                    ->limit(48)
                    ->tooltip(fn (TextColumn $column): ?string => strlen($column->getState() ?? '') > 48 ? $column->getState() : null)
                    ->searchable(),
                IconColumn::make('is_urgent')
                    ->label('Urgent')
                    ->boolean()
                    ->trueIcon('heroicon-s-exclamation-triangle')
                    ->falseIcon('heroicon-o-minus-small')
                    ->trueColor('danger')
                    ->falseColor('gray')
                    ->sortable(),
                TextColumn::make('origin')
                    ->badge()
                    ->color('gray')
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('owner')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->description(fn ($record) => optional($record->created_at)->diffForHumans())
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_urgent')
                    ->label('Urgency')
                    ->placeholder('All records')
                    ->trueLabel('Urgent only')
                    ->falseLabel('Non-urgent only'),
                SelectFilter::make('owner')
                    ->label('Owner office')
                    ->options(fn () => Office::orderBy('name')->pluck('name', 'name'))
                    ->searchable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No records found')
            ->emptyStateDescription('Records created through the incoming and outgoing forms appear here.')
            ->emptyStateIcon('heroicon-o-document-text');
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
            'index' => ListRecords::route('/'),
            'create' => CreateRecord::route('/create'),
            'edit' => EditRecord::route('/{record}/edit'),
        ];
    }
}
