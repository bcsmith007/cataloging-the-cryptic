<?php

namespace App\Filament\Resources\PatreonPosts\Pages;

use App\Filament\Resources\PatreonPosts\PatreonPostResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * List records page for Patreon posts.
 */
class ListPatreonPosts extends ListRecords
{
    protected static string $resource = PatreonPostResource::class;

    /**
     * Get header actions.
     *
     * @return array<int, mixed>
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Add Patreon Post'),
        ];
    }
}
