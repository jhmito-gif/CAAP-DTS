<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RemarkTemplateResource\Pages\CreateRemarkTemplate;
use App\Filament\Resources\RemarkTemplateResource\Pages\EditRemarkTemplate;
use App\Filament\Resources\RemarkTemplateResource\Pages\ListRemarkTemplates;
use App\Models\RemarkTemplate;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RemarkTemplateResource extends Resource
{
    protected static ?string $model = RemarkTemplate::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chat-bubble-left-ellipsis';

    protected static string | \UnitEnum | null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Remark Presets';

    protected static ?string $modelLabel = 'remark preset';

    protected static ?string $pluralModelLabel = 'remark presets';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('text')
                    ->label('Remark')
                    ->required()
                    ->maxLength(500)
                    ->rows(3)
                    ->helperText('This preset appears in the remarks dropdown across the app.')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('text')
            ->striped()
            ->columns([
                TextColumn::make('text')
                    ->label('Remark')
                    ->wrap()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Added')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ->emptyStateHeading('No remark presets yet')
            ->emptyStateDescription('Add presets so users can pick a common remark and still type their own.')
            ->emptyStateIcon('heroicon-o-chat-bubble-left-ellipsis');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRemarkTemplates::route('/'),
            'create' => CreateRemarkTemplate::route('/create'),
            'edit' => EditRemarkTemplate::route('/{record}/edit'),
        ];
    }
}
