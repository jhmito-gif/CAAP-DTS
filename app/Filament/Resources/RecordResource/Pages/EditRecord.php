<?php

namespace App\Filament\Resources\RecordResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\RecordResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord as BaseEditRecord;

class EditRecord extends BaseEditRecord
{
    protected static string $resource = RecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
