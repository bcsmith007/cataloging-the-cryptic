<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Services\CatalogStatsService;
use Illuminate\Contracts\View\View;

/**
 * Controller handling the public landing page.
 *
 * Provides featured video hero, live catalog statistics, and latest video feed.
 */
class HomeController extends Controller
{
    /**
     * Display the catalog landing page.
     */
    public function index(CatalogStatsService $statsService): View
    {
        $featuredVideo = Video::with(['hosts', 'puzzles.setters', 'puzzles.category', 'puzzles.constraints', 'puzzles.links', 'patreonPost'])
            ->orderByEffectivePublishedAt('desc')
            ->orderByDesc('id')
            ->first();

        $stats = $statsService->getHomeStats();

        $recentVideos = Video::with(['hosts', 'puzzles', 'patreonPost'])
            ->when($featuredVideo, fn ($query) => $query->where('id', '!=', $featuredVideo->id))
            ->orderByEffectivePublishedAt('desc')
            ->orderByDesc('id')
            ->take(6)
            ->get();

        return view('home', compact('featuredVideo', 'stats', 'recentVideos'));
    }
}
