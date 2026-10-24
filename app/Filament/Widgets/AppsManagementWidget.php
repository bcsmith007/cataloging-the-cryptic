<?php

namespace App\Filament\Widgets;

use App\Models\CtcApp;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;

/**
 * Dashboard widget providing one-click access to add and manage official CTC apps.
 */
class AppsManagementWidget extends Widget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    /**
     * @var view-string
     */
    protected string $view = 'filament.widgets.apps-management-widget';

    public static function canView(): bool
    {
        return Filament::auth()->check();
    }

    /**
     * Retrieve statistics and app records for the widget.
     *
     * @return array{total: int, active: int, apps: Collection<int, CtcApp>}
     */
    public function getAppsData(): array
    {
        return [
            'total' => CtcApp::count(),
            'active' => CtcApp::where('is_active', true)->count(),
            'apps' => CtcApp::orderBy('sort_order')->orderBy('id')->get(),
        ];
    }
}
