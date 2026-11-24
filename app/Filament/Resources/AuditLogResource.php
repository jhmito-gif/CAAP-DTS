<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages\ListAuditLogs;
use App\Models\AuditLog;
use App\Models\Office;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Read-only admin viewer for the append-only audit trail: who did what, to
 * what, and when, across the whole system.
 */
class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string | \UnitEnum | null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 8;

    protected static ?string $navigationLabel = 'Audit Log';

    protected static ?string $modelLabel = 'audit log entry';

    protected static ?string $pluralModelLabel = 'audit log';

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
                TextColumn::make('action')
                    ->label('What')
                    ->badge()
                    ->formatStateUsing(fn (string $state, AuditLog $record): string => $record->label())
                    ->color(fn (string $state): string => match (true) {
                        str_ends_with($state, '.failed'), str_ends_with($state, '.deleted') => 'danger',
                        in_array($state, ['auth.blocked', 'user.deactivated', 'record.unlock_failed'], true) => 'danger',
                        in_array($state, ['record.token_reset', 'record.unlocked'], true) => 'warning',
                        $state === 'user.reactivated' => 'success',
                        str_ends_with($state, '.created'), $state === 'transaction.sent', $state === 'transaction.received' => 'success',
                        str_ends_with($state, '.updated'), $state === 'transaction.reference_assigned' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('user_name')
                    ->label('Who')
                    ->description(fn (AuditLog $record): ?string => $record->user_office)
                    ->searchable()
                    ->placeholder('System'),
                TextColumn::make('subject_label')
                    ->label('On')
                    ->description(fn (AuditLog $record): ?string => $record->subject_type)
                    ->limit(40)
                    ->tooltip(fn (AuditLog $record): ?string => $record->subject_label)
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('record_reference')
                    ->label('Record')
                    ->weight('bold')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('details')
                    ->label('Changes')
                    ->state(fn (AuditLog $record): ?string => implode("\n", [
                        ...$record->changeLines(),
                        ...collect($record->context ?? [])->map(fn ($value, $key) => "{$key}: " . (is_scalar($value) ? $value : json_encode($value)))->values(),
                    ]) ?: null)
                    ->formatStateUsing(fn (?string $state): string => nl2br(e((string) $state)))
                    ->html()
                    ->wrap()
                    ->lineClamp(6)
                    ->toggleable()
                    ->placeholder('—'),
                TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label('What')
                    ->options(AuditLog::ACTIONS)
                    ->searchable(),
                SelectFilter::make('subject_type')
                    ->label('Kind')
                    ->options(fn () => AuditLog::query()->whereNotNull('subject_type')->distinct()->orderBy('subject_type')->pluck('subject_type', 'subject_type')),
                SelectFilter::make('user_office')
                    ->label('Office')
                    ->options(fn () => Office::orderBy('name')->pluck('name', 'name'))
                    ->searchable(),
            ])
            ->emptyStateHeading('Nothing recorded yet')
            ->emptyStateDescription('Records, routing, files, sign-ins and admin changes appear here as they happen.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
        ];
    }
}
