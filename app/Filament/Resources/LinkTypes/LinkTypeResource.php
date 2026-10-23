<?php

namespace App\Filament\Resources\LinkTypes;

use App\Filament\Resources\LinkTypes\Pages\CreateLinkType;
use App\Filament\Resources\LinkTypes\Pages\EditLinkType;
use App\Filament\Resources\LinkTypes\Pages\ListLinkTypes;
use App\Filament\Resources\LinkTypes\Schemas\LinkTypeForm;
use App\Filament\Resources\LinkTypes\Tables\LinkTypesTable;
use App\Models\LinkType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Resource management for puzzle link platforms and types.
 */
class LinkTypeResource extends Resource
{
    protected static ?string $model = LinkType::class;

    protected static ?string $recordRouteKeyName = 'slug';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static string|\UnitEnum|null $navigationGroup = 'Taxonomy';

    protected static ?int $navigationSort = 8;

    public static function form(Schema $schema): Schema
    {
        return LinkTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LinkTypesTable::configure($table);
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
            'index' => ListLinkTypes::route('/'),
            'create' => CreateLinkType::route('/create'),
            'edit' => EditLinkType::route('/{record}/edit'),
        ];
    }
}
