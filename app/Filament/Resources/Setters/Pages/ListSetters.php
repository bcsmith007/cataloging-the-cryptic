<?php

namespace App\Filament\Resources\Setters\Pages;

use App\Filament\Resources\Setters\SetterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSetters extends ListRecords
{
    protected static string $resource = SetterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
