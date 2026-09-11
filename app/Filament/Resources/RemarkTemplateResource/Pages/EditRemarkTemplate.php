<?php

namespace App\Filament\Resources\RemarkTemplateResource\Pages;

use App\Filament\Resources\RemarkTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRemarkTemplate extends EditRecord
{
    protected static string $resource = RemarkTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
