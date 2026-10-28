<?php

namespace App\Http\Controllers;

use App\Models\Constraint;
use App\Models\Puzzle;
use App\Models\Setter;
use App\Models\Video;
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
    public function index(): View
    {
        $featuredVideo = Video::with(['hosts', 'puzzles.setters', 'puzzles.category', 'puzzles.constraints', 'puzzles.links', 'patreonPost'])
            ->latest('published_at')
            ->first();

        $stats = [
            'total_videos' => Video::count(),
            'total_puzzles' => Puzzle::count(),
            'total_setters' => Setter::has('puzzles')->count(),
            'total_constraints' => Constraint::has('puzzles')->count(),
        ];

        $recentVideos = Video::with(['hosts', 'puzzles', 'patreonPost'])
            ->when($featuredVideo, fn ($query) => $query->where('id', '!=', $featuredVideo->id))
            ->latest('published_at')
            ->take(6)
            ->get();

        return view('home', compact('featuredVideo', 'stats', 'recentVideos'));
    }
}
