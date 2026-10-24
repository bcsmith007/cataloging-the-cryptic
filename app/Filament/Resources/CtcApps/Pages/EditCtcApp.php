<?php

namespace App\Filament\Resources\CtcApps\Pages;

use App\Filament\Resources\CtcApps\CtcAppResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCtcApp extends EditRecord
{
    protected static string $resource = CtcAppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
