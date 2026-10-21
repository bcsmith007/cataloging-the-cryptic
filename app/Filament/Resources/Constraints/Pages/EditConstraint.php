<?php

namespace App\Filament\Resources\Constraints\Pages;

use App\Filament\Resources\Constraints\ConstraintResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditConstraint extends EditRecord
{
    protected static string $resource = ConstraintResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
