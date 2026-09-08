<?php

namespace App\Filament\Resources\AccessResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\AccessResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAccess extends EditRecord
{
    protected static string $resource = AccessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
