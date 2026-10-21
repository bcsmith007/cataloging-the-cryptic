<?php

namespace App\Filament\Resources\Setters;

use App\Filament\Resources\Setters\Pages\CreateSetter;
use App\Filament\Resources\Setters\Pages\EditSetter;
use App\Filament\Resources\Setters\Pages\ListSetters;
use App\Filament\Resources\Setters\Schemas\SetterForm;
use App\Filament\Resources\Setters\Tables\SettersTable;
use App\Models\Setter;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SetterResource extends Resource
{
    protected static ?string $model = Setter::class;

    protected static ?string $recordRouteKeyName = 'slug';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return SetterForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SettersTable::configure($table);
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
            'index' => ListSetters::route('/'),
            'create' => CreateSetter::route('/create'),
            'edit' => EditSetter::route('/{record}/edit'),
        ];
    }
}
