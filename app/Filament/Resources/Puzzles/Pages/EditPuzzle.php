<?php

namespace App\Filament\Resources\Puzzles\Pages;

use App\Filament\Resources\Puzzles\PuzzleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditPuzzle extends EditRecord
{
    protected static string $resource = PuzzleResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
