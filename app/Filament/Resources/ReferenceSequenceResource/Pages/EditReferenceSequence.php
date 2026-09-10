<?php

namespace App\Filament\Resources\ReferenceSequenceResource\Pages;

use App\Filament\Resources\ReferenceSequenceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReferenceSequence extends EditRecord
{
    protected static string $resource = ReferenceSequenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
