<?php

namespace App\Filament\Resources\Puzzles;

use App\Filament\Resources\Puzzles\Pages\CreatePuzzle;
use App\Filament\Resources\Puzzles\Pages\EditPuzzle;
use App\Filament\Resources\Puzzles\Pages\ListPuzzles;
use App\Filament\Resources\Puzzles\Schemas\PuzzleForm;
use App\Filament\Resources\Puzzles\Tables\PuzzlesTable;
use App\Models\Puzzle;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PuzzleResource extends Resource
{
    protected static ?string $model = Puzzle::class;

    protected static ?string $recordRouteKeyName = 'slug';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return PuzzleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PuzzlesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPuzzles::route('/'),
            'create' => CreatePuzzle::route('/create'),
            'edit' => EditPuzzle::route('/{record}/edit'),
        ];
    }
}
