<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentCategoryResource\Pages\CreateDocumentCategory;
use App\Filament\Resources\DocumentCategoryResource\Pages\EditDocumentCategory;
use App\Filament\Resources\DocumentCategoryResource\Pages\ListDocumentCategories;
use App\Models\DocumentCategory;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocumentCategoryResource extends Resource
{
    protected static ?string $model = DocumentCategory::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-folder';

    protected static string | \UnitEnum | null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Document Categories';

    protected static ?string $modelLabel = 'document category';

    protected static ?string $pluralModelLabel = 'document categories';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Category')
                    ->required()
                    ->maxLength(100)
                    ->unique(ignoreRecord: true)
                    ->helperText('Offices pick this when uploading to or organising the document library.')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->striped()
            ->columns([
                TextColumn::make('name')
                    ->label('Category')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('documents_count')
                    ->label('Library files')
                    ->counts('documents')
                    ->sortable(),
                TextColumn::make('attachments_count')
                    ->label('Routed files')
                    ->counts('attachments')
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
                    DeleteAction::make()
                        ->modalDescription('Files in this category are kept and become uncategorised.'),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No document categories yet')
            ->emptyStateDescription('Add categories so offices can organise the document library.')
            ->emptyStateIcon('heroicon-o-folder');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocumentCategories::route('/'),
            'create' => CreateDocumentCategory::route('/create'),
            'edit' => EditDocumentCategory::route('/{record}/edit'),
        ];
    }
}
