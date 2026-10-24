<?php

namespace App\Filament\Resources\CtcApps\Pages;

use App\Filament\Resources\CtcApps\CtcAppResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCtcApps extends ListRecords
{
    protected static string $resource = CtcAppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
