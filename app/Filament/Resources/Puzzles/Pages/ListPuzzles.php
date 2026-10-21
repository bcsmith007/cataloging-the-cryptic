<?php

namespace App\Filament\Resources\Puzzles\Pages;

use App\Filament\Resources\Puzzles\PuzzleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPuzzles extends ListRecords
{
    protected static string $resource = PuzzleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
