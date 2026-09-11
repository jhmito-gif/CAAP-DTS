<?php

namespace App\Filament\Resources\RemarkTemplateResource\Pages;

use App\Filament\Resources\RemarkTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRemarkTemplates extends ListRecords
{
    protected static string $resource = RemarkTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
