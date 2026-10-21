<?php

namespace App\Filament\Resources\PatreonPosts;

use App\Filament\Resources\PatreonPosts\Pages\CreatePatreonPost;
use App\Filament\Resources\PatreonPosts\Pages\EditPatreonPost;
use App\Filament\Resources\PatreonPosts\Pages\ListPatreonPosts;
use App\Filament\Resources\PatreonPosts\RelationManagers\VideosRelationManager;
use App\Filament\Resources\PatreonPosts\Schemas\PatreonPostForm;
use App\Filament\Resources\PatreonPosts\Tables\PatreonPostsTable;
use App\Models\PatreonPost;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Filament Resource for managing Patreon posts.
 *
 * Allows cataloging Patreon releases, linking public or exclusive videos,
 * and navigating associated puzzles.
 */
class PatreonPostResource extends Resource
{
    protected static ?string $model = PatreonPost::class;

    protected static ?string $recordRouteKeyName = 'slug';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLockClosed;

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return PatreonPostForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PatreonPostsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            VideosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPatreonPosts::route('/'),
            'create' => CreatePatreonPost::route('/create'),
            'edit' => EditPatreonPost::route('/{record}/edit'),
        ];
    }
}
