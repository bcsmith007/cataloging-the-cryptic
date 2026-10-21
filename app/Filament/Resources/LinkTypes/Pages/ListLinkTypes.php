<?php

namespace App\Filament\Resources\LinkTypes\Pages;

use App\Filament\Resources\LinkTypes\LinkTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLinkTypes extends ListRecords
{
    protected static string $resource = LinkTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
