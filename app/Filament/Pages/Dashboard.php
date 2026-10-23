<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

/**
 * Custom admin dashboard page with left sidebar navigation link hidden.
 */
class Dashboard extends BaseDashboard
{
    protected static bool $shouldRegisterNavigation = false;
}
