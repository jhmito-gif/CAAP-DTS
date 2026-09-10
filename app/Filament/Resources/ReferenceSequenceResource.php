<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReferenceSequenceResource\Pages\CreateReferenceSequence;
use App\Filament\Resources\ReferenceSequenceResource\Pages\EditReferenceSequence;
use App\Filament\Resources\ReferenceSequenceResource\Pages\ListReferenceSequences;
use App\Models\Office;
use App\Models\ReferenceSequence;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

class ReferenceSequenceResource extends Resource
{
    protected static ?string $model = ReferenceSequence::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel = 'Reference Settings';

    protected static ?string $modelLabel = 'Reference Setting';

    protected static ?string $pluralModelLabel = 'Reference Settings';

    protected static string | \UnitEnum | null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('office')
                    ->options(fn () => Office::orderBy('name')->pluck('name', 'name'))
                    ->searchable()
                    ->required()
                    ->unique(
                        table: 'reference_sequences',
                        column: 'office',
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule, callable $get) => $rule->where('year', $get('year')),
                    )
                    ->validationMessages([
                        'unique' => 'This office already has a sequence for the selected year.',
                    ]),
                TextInput::make('year')
                    ->numeric()
                    ->minValue(2000)
                    ->maxValue(2100)
                    ->default(now()->year)
                    ->required(),
                TextInput::make('next_number')
                    ->label('Next Number')
                    ->numeric()
                    ->minValue(1)
                    ->required()
                    ->helperText('The next internal reference number to use for this office and year.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('office')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('year')
                    ->sortable(),
                TextColumn::make('next_number')
                    ->label('Next Number')
                    ->sortable(),
                TextColumn::make('next_reference')
                    ->label('Next Internal Reference')
                    ->searchable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => ListReferenceSequences::route('/'),
            'create' => CreateReferenceSequence::route('/create'),
            'edit' => EditReferenceSequence::route('/{record}/edit'),
        ];
    }
}
