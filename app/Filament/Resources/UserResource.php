<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use App\Models\Office;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Hash;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Actions\Action;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('office')
                    ->sortable()
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
