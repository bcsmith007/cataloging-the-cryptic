<?php

namespace App\Http\Controllers;

use App\Models\Setter;
use App\Models\Video;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

/**
 * Controller handling public setter directory and setter profile views.
 */
class SetterController extends Controller
{
    /**
     * Display a sortable table listing all puzzle setters.
     */
    public function index(Request $request): View
    {
        $sort = $request->query('sort', 'puzzles');
        $direction = strtolower($request->query('dir', $request->query('direction', 'desc')));
        if (! in_array($direction, ['asc', 'desc'])) {
            $direction = 'desc';
        }
        $search = trim($request->query('q', $request->query('search', '')));

        $setters = Setter::query()
            ->withCount('puzzles')
            ->with([
                'puzzles' => function ($query) {
                    $query->with(['videos' => function ($vq) {
                        $vq->orderBy('published_at', 'asc')->orderBy('id', 'asc');
                    }]);
                },
            ])
            ->get()
            ->map(function (Setter $setter) {
                $puzzleVideos = $setter->puzzles->flatMap(function ($puzzle) {
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

                $setter->first_puzzle = $first['puzzle'] ?? null;
                $setter->first_video = $first['video'] ?? null;
                $setter->first_date = $first['published_at'] ?? null;

                $setter->recent_puzzle = $recent['puzzle'] ?? null;
                $setter->recent_video = $recent['video'] ?? null;
                $setter->recent_date = $recent['published_at'] ?? null;

                return $setter;
            });

        if ($search !== '') {
            $setters = $setters->filter(function (Setter $s) use ($search) {
                return str_contains(Str::lower($s->name), Str::lower($search))
                    || str_contains(Str::lower($s->bio ?? ''), Str::lower($search));
            })->values();
        }

        $setters = match ($sort) {
            'name' => $direction === 'asc'
                ? $setters->sortBy(fn ($s) => Str::lower($s->name))->values()
                : $setters->sortByDesc(fn ($s) => Str::lower($s->name))->values(),
            'first' => $direction === 'asc'
                ? $setters->sortBy(fn ($s) => $s->first_date?->timestamp ?? PHP_INT_MAX)->values()
                : $setters->sortByDesc(fn ($s) => $s->first_date?->timestamp ?? 0)->values(),
            'recent' => $direction === 'asc'
                ? $setters->sortBy(fn ($s) => $s->recent_date?->timestamp ?? PHP_INT_MAX)->values()
                : $setters->sortByDesc(fn ($s) => $s->recent_date?->timestamp ?? 0)->values(),
            default => $direction === 'asc'
                ? $setters->sortBy('puzzles_count')->values()
                : $setters->sortByDesc('puzzles_count')->values(),
        };

        $perPage = 20;
        $currentPage = LengthAwarePaginator::resolveCurrentPage('page');
        $totalSetters = $setters->count();
        $currentPageItems = $setters->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $paginatedSetters = new LengthAwarePaginator(
            $currentPageItems,
            $totalSetters,
            $perPage,
            $currentPage,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'query' => $request->query(),
            ]
        );

        $setters = $paginatedSetters;

        return view('setters.index', compact('setters', 'sort', 'direction', 'search'));
    }

    /**
     * Display a setter's detail page with bio and featured videos.
     */
    public function show(Setter $setter): View
    {
        $setter->loadMissing(['links']);

        $videos = Video::query()
            ->whereHas('puzzles.setters', function ($query) use ($setter) {
                $query->where('setters.id', $setter->id);
            })
            ->with([
                'hosts',
                'puzzles' => function ($query) {
                    $query->with(['setters', 'category']);
                },
            ])
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(24);

        return view('setters.show', compact('setter', 'videos'));
    }
}
