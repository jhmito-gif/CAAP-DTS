<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EsignLogResource\Pages\ListEsignLogs;
use App\Models\EsignLog;
use App\Models\Office;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Read-only admin viewer for the append-only e-sign audit trail.
 */
class EsignLogResource extends Resource
{
    protected static ?string $model = EsignLog::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shield-check';

    protected static string | \UnitEnum | null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 9;

    protected static ?string $navigationLabel = 'E-Sign Log';

    protected static ?string $modelLabel = 'e-sign log entry';

    protected static ?string $pluralModelLabel = 'e-sign log';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->columns([
                TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime('M j, Y g:i:s A', 'Asia/Manila')
                    ->sortable(),
                TextColumn::make('event')
                    ->label('Event')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => EsignLog::EVENTS[$state] ?? $state)
                    ->color(fn (EsignLog $record): string => match ($record->outcome) {
                        EsignLog::SUCCESS => 'success',
                        EsignLog::FAILURE => 'danger',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('user_name')
                    ->label('User')
                    ->description(fn (EsignLog $record): ?string => $record->user_office)
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('record_reference')
                    ->label('Record')
                    ->weight('bold')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('document')
                    ->label('Document')
                    ->state(fn (EsignLog $record): ?string => $record->context['document'] ?? null)
                    ->limit(30)
                    ->toggleable()
                    ->placeholder('—'),
                TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable()
                    ->placeholder('—'),
                TextColumn::make('details')
                    ->label('Details')
                    ->state(fn (EsignLog $record): ?string => collect($record->context ?? [])
                        ->except('document')
                        ->map(fn ($value, $key) => $key . ': ' . match (true) {
                            is_bool($value) => $value ? 'yes' : 'no',
                            is_scalar($value) => (string) $value,
                            default => json_encode($value),
                        })
                        ->implode(' · ') ?: null)
                    ->wrap()
                    ->toggleable()
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('outcome')
                    ->options([
                        EsignLog::SUCCESS => 'Success',
                        EsignLog::FAILURE => 'Failure',
                        EsignLog::INFO => 'Info',
                    ]),
                SelectFilter::make('event')
                    ->options(EsignLog::EVENTS)
                    ->searchable(),
                SelectFilter::make('user_office')
                    ->label('Office')
                    ->options(fn () => Office::orderBy('name')->pluck('name', 'name'))
                    ->searchable(),
            ])
            ->emptyStateHeading('No e-sign activity yet')
            ->emptyStateDescription('Signing attempts, signature requests, document access and signature changes appear here.')
            ->emptyStateIcon('heroicon-o-shield-check');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEsignLogs::route('/'),
        ];
    }
}
