<?php

namespace App\Http\Controllers;

use App\Models\Constraint;
use App\Models\Video;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Controller handling public constraint directory and constraint detail views.
 */
class ConstraintController extends Controller
{
    /**
     * Display a sortable table listing all puzzle constraints and rulesets.
     */
    public function index(Request $request): View
    {
        $sort = $request->query('sort', 'videos');
        $direction = strtolower($request->query('dir', $request->query('direction', 'desc')));
        if (! in_array($direction, ['asc', 'desc'])) {
            $direction = 'desc';
        }

        $constraints = Constraint::query()
            ->withCount('puzzles')
            ->with([
                'puzzles' => function ($query) {
                    $query->with(['videos' => function ($vq) {
                        $vq->orderBy('published_at', 'asc')->orderBy('id', 'asc');
                    }]);
                },
            ])
            ->get()
            ->map(function (Constraint $constraint) {
                $puzzleVideos = $constraint->puzzles->flatMap(function ($puzzle) {
                    return $puzzle->videos->map(function ($video) use ($puzzle) {
                        return [
                            'puzzle' => $puzzle,
                            'video' => $video,
                            'published_at' => $video->published_at,
                        ];
                    });
                })->filter(fn ($item) => ! is_null($item['published_at']))
                    ->sortBy('published_at')
                    ->values();

                $distinctVideos = $puzzleVideos->unique('video.id')->values();

                $first = $puzzleVideos->first();
                $recent = $distinctVideos->count() > 1 ? $puzzleVideos->last() : null;

                $constraint->videos_count = $distinctVideos->count();
                $constraint->first_puzzle = $first['puzzle'] ?? null;
                $constraint->first_video = $first['video'] ?? null;
                $constraint->first_date = $first['published_at'] ?? null;

                $constraint->recent_puzzle = $recent['puzzle'] ?? null;
                $constraint->recent_video = $recent['video'] ?? null;
                $constraint->recent_date = $recent['published_at'] ?? null;

                return $constraint;
            });

        $constraints = match ($sort) {
            'name' => $direction === 'asc'
                ? $constraints->sortBy(fn ($c) => Str::lower($c->name))->values()
                : $constraints->sortByDesc(fn ($c) => Str::lower($c->name))->values(),
            'puzzles' => $direction === 'asc'
                ? $constraints->sortBy('puzzles_count')->values()
                : $constraints->sortByDesc('puzzles_count')->values(),
            'first' => $direction === 'asc'
                ? $constraints->sortBy(fn ($c) => $c->first_date?->timestamp ?? PHP_INT_MAX)->values()
                : $constraints->sortByDesc(fn ($c) => $c->first_date?->timestamp ?? 0)->values(),
            'recent' => $direction === 'asc'
                ? $constraints->sortBy(fn ($c) => $c->recent_date?->timestamp ?? PHP_INT_MAX)->values()
                : $constraints->sortByDesc(fn ($c) => $c->recent_date?->timestamp ?? 0)->values(),
            default => $direction === 'asc'
                ? $constraints->sortBy('videos_count')->values()
                : $constraints->sortByDesc('videos_count')->values(),
        };

        return view('constraints.index', compact('constraints', 'sort', 'direction'));
    }

    /**
     * Display a constraint's detail page with definition, key stat tiles, and video listing.
     */
    public function show(Constraint $constraint): View
    {
        $allVideos = Video::query()
            ->whereHas('puzzles.constraints', function ($query) use ($constraint) {
                $query->where('constraints.id', $constraint->id);
            })
            ->whereNotNull('published_at')
            ->orderBy('published_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $totalVideos = $allVideos->count();
        $firstVideo = $allVideos->first();
        $latestVideo = $totalVideos > 1 ? $allVideos->last() : $firstVideo;
        $totalViews = (int) $allVideos->sum('view_count');
        $puzzlesCount = $constraint->puzzles()->count();

        $videos = Video::query()
            ->whereHas('puzzles.constraints', function ($query) use ($constraint) {
                $query->where('constraints.id', $constraint->id);
            })
            ->with([
                'hosts',
                'puzzles' => function ($query) {
                    $query->with(['setters', 'category', 'constraints']);
                },
            ])
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(24);

        return view('constraints.show', compact(
            'constraint',
            'videos',
            'totalVideos',
            'firstVideo',
            'latestVideo',
            'totalViews',
            'puzzlesCount'
        ));
    }
}
