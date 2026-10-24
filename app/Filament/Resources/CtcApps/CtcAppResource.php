<?php

namespace App\Filament\Resources\CtcApps;

use App\Filament\Resources\CtcApps\Pages\CreateCtcApp;
use App\Filament\Resources\CtcApps\Pages\EditCtcApp;
use App\Filament\Resources\CtcApps\Pages\ListCtcApps;
use App\Filament\Resources\CtcApps\Schemas\CtcAppForm;
use App\Filament\Resources\CtcApps\Tables\CtcAppsTable;
use App\Models\CtcApp;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CtcAppResource extends Resource
{
    protected static ?string $model = CtcApp::class;

    protected static ?string $recordRouteKeyName = 'slug';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static string|\UnitEnum|null $navigationGroup = 'Taxonomy';

    protected static ?string $navigationLabel = 'Apps';

    protected static ?int $navigationSort = 9;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return CtcAppForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CtcAppsTable::configure($table);
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
            'index' => ListCtcApps::route('/'),
            'create' => CreateCtcApp::route('/create'),
            'edit' => EditCtcApp::route('/{record}/edit'),
        ];
    }
}
