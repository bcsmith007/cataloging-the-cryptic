<?php

namespace App\Filament\Resources\Constraints;

use App\Filament\Resources\Constraints\Pages\CreateConstraint;
use App\Filament\Resources\Constraints\Pages\EditConstraint;
use App\Filament\Resources\Constraints\Pages\ListConstraints;
use App\Filament\Resources\Constraints\Schemas\ConstraintForm;
use App\Filament\Resources\Constraints\Tables\ConstraintsTable;
use App\Models\Constraint;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ConstraintResource extends Resource
{
    protected static ?string $model = Constraint::class;

    protected static ?string $recordRouteKeyName = 'slug';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ConstraintForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ConstraintsTable::configure($table);
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
            'index' => ListConstraints::route('/'),
            'create' => CreateConstraint::route('/create'),
            'edit' => EditConstraint::route('/{record}/edit'),
        ];
    }
}
