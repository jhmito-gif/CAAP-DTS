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
use Illuminate\Database\Eloquent\Builder;
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
                    // Not a column: it is built from office, year and number
                    // (ReferenceSequence::getNextReferenceAttribute). Searching
                    // it directly put a column that does not exist into the
                    // SQL, so "SPD-2026-0012" is taken apart and matched
                    // against the parts it is made of.
                    ->searchable(query: fn (Builder $query, string $search): Builder => self::searchReference($query, $search)),
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

    /**
     * Match a search against the parts a reference is made of. "SPD" finds the
     * office, "2026" the year, "SPD-2026" both, and "SPD-2026-0012" the one
     * sequence whose next number is 12.
     */
    public static function searchReference(Builder $query, string $search): Builder
    {
        $parts = array_values(array_filter(explode('-', trim($search)), fn (string $part) => $part !== ''));

        if (count($parts) <= 1) {
            $term = $parts[0] ?? '';

            return $query->where(fn (Builder $match) => $match
                ->where('office', 'like', "%{$term}%")
                ->orWhere('year', 'like', "%{$term}%")
                ->when(ctype_digit($term), fn (Builder $number) => $number->orWhere('next_number', (int) $term)));
        }

        [$office, $year, $number] = array_pad($parts, 3, null);

        return $query->where(function (Builder $match) use ($office, $year, $number) {
            $match->where('office', 'like', "%{$office}%")
                ->where('year', 'like', "%{$year}%");

            if ($number !== null && ctype_digit($number)) {
                $match->where('next_number', (int) $number);
            }
        });
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
