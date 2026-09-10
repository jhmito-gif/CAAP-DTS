<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccessResource\Pages\CreateAccess;
use App\Filament\Resources\AccessResource\Pages\EditAccess;
use App\Filament\Resources\AccessResource\Pages\ListAccesses;
use App\Models\Access;
use App\Models\Office;
use App\Models\Record;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

class AccessResource extends Resource
{
    protected static ?string $model = Access::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-key';

    protected static string | \UnitEnum | null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Record Access';

    protected static ?string $modelLabel = 'access grant';

    protected static ?string $pluralModelLabel = 'record access';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('record_id')
                    ->label('Record')
                    ->relationship('record', 'reference')
                    ->getOptionLabelFromRecordUsing(fn (Record $record) => trim("{$record->reference} — " . \Illuminate\Support\Str::limit($record->subject, 40)))
                    ->searchable(['reference', 'origin_reference', 'subject'])
                    ->preload()
                    ->required(),

                Select::make('office')
                    ->label('Grant access to office')
                    ->options(fn () => Office::orderBy('name')->pluck('name', 'name'))
                    ->searchable()
                    ->required()
                    ->unique(
                        table: 'accesses',
                        column: 'office',
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule, callable $get) => $rule->where('record_id', $get('record_id')),
                    )
                    ->validationMessages([
                        'unique' => 'This office already has access to the selected record.',
                    ])
                    ->helperText('This office will be able to view and print the record.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->columns([
                TextColumn::make('record.reference')
                    ->label('Record')
                    ->weight('bold')
                    ->searchable()
                    ->sortable()
                    ->placeholder('— deleted —'),
                TextColumn::make('record.subject')
                    ->label('Subject')
                    ->limit(40)
                    ->tooltip(fn (TextColumn $column): ?string => strlen($column->getState() ?? '') > 40 ? $column->getState() : null)
                    ->toggleable(),
                TextColumn::make('office')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Granted')
                    ->dateTime('M j, Y g:i A')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('office')
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
            ->emptyStateHeading('No access grants yet')
            ->emptyStateDescription('Grant an office access to a specific record so it can view and print it.')
            ->emptyStateIcon('heroicon-o-key');
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
            'index' => ListAccesses::route('/'),
            'create' => CreateAccess::route('/create'),
            'edit' => EditAccess::route('/{record}/edit'),
        ];
    }
}
