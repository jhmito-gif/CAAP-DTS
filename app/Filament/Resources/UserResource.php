<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use App\Models\Office;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Hash;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';

    protected static string | \UnitEnum | null $navigationGroup = 'Directory';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'office'];
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Email' => $record->email,
            'Office' => $record->office,
        ];
    }

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
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Select::make('office')
                    ->label('Office')
                    ->options(function () {
                        return Office::all()->pluck('name', 'name'); 
                    })
                    ->searchable()
                    ->required(),
                
                Select::make('role')
                ->options(User::ROLES)
                ->required()
                ->default('USER') // Set default role
                ->disabled(fn () => !auth()->user()->isAdmin()), // Only admin can modify

                Toggle::make('manages_documents')
                ->label('Document manager')
                ->helperText('May create and rearrange folders in their office\'s document library. Admins always can.')
                ->disabled(fn () => !auth()->user()->isAdmin()),

		TextInput::make('password')
                ->label('Password')
                ->password()
                ->minLength(8)
                ->required(fn (string $context): bool => $context === 'create') // ✅ only required on create
                ->suffixAction(
                    Action::make('generate_password')
                        ->label('Generate')
                        ->icon('heroicon-m-key')
                        ->action(function ($set) {
                            $set('password', Str::random(8));
                        })
                )
                ->dehydrateStateUsing(fn ($state) => !empty($state) ? Hash::make($state) : null)
                ->dehydrated(fn ($state) => filled($state))
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->striped()
            ->columns([
                TextColumn::make('name')
                    ->weight('bold')
                    ->description(fn (User $record) => $record->email)
                    ->searchable(['name', 'email'])
                    ->sortable(),
                TextColumn::make('office')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('service')
                    ->placeholder('—')
                    ->toggleable()
                    ->searchable(),
                TextColumn::make('role')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => User::ROLES[$state] ?? $state)
                    ->color(fn (?string $state) => $state === User::ROLE_ADMIN ? 'success' : 'gray')
                    ->icon(fn (?string $state) => $state === User::ROLE_ADMIN ? 'heroicon-m-shield-check' : 'heroicon-m-user')
                    ->sortable(),
                IconColumn::make('manages_documents')
                    ->label('Documents')
                    ->tooltip('Looks after their office\'s document library')
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('status')
                    ->state(fn (User $record): string => $record->isDeactivated() ? 'Deactivated' : 'Active')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Deactivated' ? 'danger' : 'success')
                    ->description(fn (User $record): ?string => $record->deactivated_at
                        ? 'since ' . $record->deactivated_at->timezone('Asia/Manila')->format('M j, Y')
                        : null),
                TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('office')
                    ->options(fn () => Office::orderBy('name')->pluck('name', 'name'))
                    ->searchable()
                    ->multiple(),
                SelectFilter::make('role')
                    ->options(User::ROLES),
                TernaryFilter::make('deactivated_at')
                    ->label('Status')
                    ->nullable()
                    ->placeholder('Everyone')
                    ->trueLabel('Deactivated')
                    ->falseLabel('Active'),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    static::deactivateAction(),
                    static::reactivateAction(),
                ]),
            ])
            ->emptyStateHeading('No users found')
            ->emptyStateDescription('Add personnel so they can be assigned to offices and tagged on records.')
            ->emptyStateIcon('heroicon-o-users');
    }

    /**
     * Someone who has left is deactivated, never deleted: they can no longer
     * sign in, and their name stays on everything they handled. Not for your
     * own account -- that would lock you out.
     */
    public static function deactivateAction(): Action
    {
        return Action::make('deactivate')
            ->label('Deactivate')
            ->icon('heroicon-o-no-symbol')
            ->color('danger')
            ->visible(fn (User $record): bool => ! $record->isDeactivated() && $record->id !== auth()->id())
            ->requiresConfirmation()
            ->modalHeading(fn (User $record): string => "Deactivate {$record->name}?")
            ->modalDescription('They will be signed out and can no longer sign in, and they drop out of the people lists. Their name stays on every record they handled. You can reactivate them later.')
            ->modalSubmitActionLabel('Deactivate')
            ->action(function (User $record): void {
                $record->deactivate();

                Notification::make()->title("{$record->name} was deactivated")->success()->send();
            });
    }

    public static function reactivateAction(): Action
    {
        return Action::make('reactivate')
            ->label('Reactivate')
            ->icon('heroicon-o-arrow-path')
            ->color('success')
            ->visible(fn (User $record): bool => $record->isDeactivated())
            ->requiresConfirmation()
            ->modalHeading(fn (User $record): string => "Reactivate {$record->name}?")
            ->modalDescription('They will be able to sign in again with their existing password.')
            ->modalSubmitActionLabel('Reactivate')
            ->action(function (User $record): void {
                $record->reactivate();

                Notification::make()->title("{$record->name} can sign in again")->success()->send();
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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
