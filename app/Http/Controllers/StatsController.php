<?php

namespace App\Http\Controllers;

use App\Services\CatalogStatsService;
use Illuminate\View\View;

/**
 * Controller for presenting aggregated catalog analytics, viewing trends, and duration distributions.
 */
class StatsController extends Controller
{
    /**
     * Display the catalog statistics and analytics dashboard.
     */
    public function index(CatalogStatsService $statsService): View
    {
        $stats = $statsService->getDashboardStats();

        return view('stats.index', $stats);
    }
}
