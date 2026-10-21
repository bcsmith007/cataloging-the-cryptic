<?php

namespace App\Filament\Resources\Constraints\Pages;

use App\Filament\Resources\Constraints\ConstraintResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListConstraints extends ListRecords
{
    protected static string $resource = ConstraintResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
