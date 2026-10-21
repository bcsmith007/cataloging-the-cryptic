<?php

namespace App\Filament\Resources\Puzzles\Pages;

use App\Filament\Resources\Puzzles\PuzzleResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreatePuzzle extends CreateRecord
{
    protected static string $resource = PuzzleResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
