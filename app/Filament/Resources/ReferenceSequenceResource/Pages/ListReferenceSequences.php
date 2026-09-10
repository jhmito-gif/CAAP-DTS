<?php

namespace App\Filament\Resources\ReferenceSequenceResource\Pages;

use App\Filament\Resources\ReferenceSequenceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReferenceSequences extends ListRecords
{
    protected static string $resource = ReferenceSequenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
