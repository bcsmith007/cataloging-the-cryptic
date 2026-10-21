<?php

namespace App\Filament\Resources\PatreonPosts\Pages;

use App\Filament\Resources\PatreonPosts\PatreonPostResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Create page for PatreonPost resource.
 */
class CreatePatreonPost extends CreateRecord
{
    protected static string $resource = PatreonPostResource::class;
}
