<?php

namespace App\Filament\Resources\LinkTypes\Pages;

use App\Filament\Resources\LinkTypes\LinkTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLinkType extends EditRecord
{
    protected static string $resource = LinkTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
