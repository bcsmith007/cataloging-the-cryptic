<?php

namespace App\Filament\Resources\Setters\Pages;

use App\Filament\Resources\Setters\SetterResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSetter extends EditRecord
{
    protected static string $resource = SetterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
