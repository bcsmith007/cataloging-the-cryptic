<?php

namespace App\Filament\Resources\PatreonPosts\Pages;

use App\Filament\Resources\PatreonPosts\PatreonPostResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Edit page for PatreonPost resource.
 */
class EditPatreonPost extends EditRecord
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
            DeleteAction::make(),
        ];
    }
}
