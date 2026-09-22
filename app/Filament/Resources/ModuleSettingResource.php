<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ModuleSettingResource\Pages\ListModuleSettings;
use App\Models\ModuleSetting;
use App\Support\Modules;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Switch optional modules on and off.
 *
 * Every row says what the module is and what happens elsewhere when it is off,
 * so nobody turns one off blind. Switching off hides; it never deletes.
 */
class ModuleSettingResource extends Resource
{
    protected static ?string $model = ModuleSetting::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string | \UnitEnum | null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Modules';

    protected static ?string $modelLabel = 'module';

    protected static ?string $pluralModelLabel = 'modules';

    // The list of modules is fixed by the code; only the switch changes.
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
            // Only modules the code knows about; a stray row would switch nothing.
            ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('module', array_keys(Modules::CATALOGUE)))
            ->defaultSort('id')
            ->paginated(false)
            ->description('Switching a module off hides it for everyone: its pages, menu entries and buttons. '
                . 'Nothing is deleted, and switching it back on brings everything back as it was. '
                . 'Always on: ' . implode(', ', Modules::CORE) . '.')
            ->columns([
                TextColumn::make('module')
                    ->label('Module')
                    ->formatStateUsing(fn (string $state) => Modules::CATALOGUE[$state]['label'] ?? $state)
                    ->description(fn (ModuleSetting $record) => Modules::CATALOGUE[$record->module]['description'] ?? null)
                    ->weight('bold')
                    ->wrap(),
                TextColumn::make('when_off')
                    ->label('While it is off')
                    ->state(fn (ModuleSetting $record) => Modules::CATALOGUE[$record->module]['when_off'] ?? '')
                    ->color('gray')
                    ->wrap(),
                ToggleColumn::make('enabled')
                    ->label('On')
                    ->afterStateUpdated(function (ModuleSetting $record) {
                        // Who switched it, for anyone wondering why it is off.
                        $record->forceFill(['updated_by' => auth()->user()?->name])->saveQuietly();
                        Modules::forget();
                    }),
                TextColumn::make('updated_by')
                    ->label('Changed by')
                    ->description(fn (ModuleSetting $record) => $record->updated_by ? $record->updated_at?->diffForHumans() : null)
                    ->placeholder('—'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListModuleSettings::route('/'),
        ];
    }
}
