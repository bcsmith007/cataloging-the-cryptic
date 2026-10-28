<?php

namespace App\Http\Controllers;

use App\Models\Setter;
use App\Models\Video;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $firstPublishedSubquery = DB::table('puzzle_setters')
            ->join('video_puzzles', 'video_puzzles.puzzle_id', '=', 'puzzle_setters.puzzle_id')
            ->join('videos', 'videos.id', '=', 'video_puzzles.video_id')
            ->whereColumn('puzzle_setters.setter_id', 'setters.id')
            ->whereNotNull('videos.published_at')
            ->selectRaw('MIN(videos.published_at)');

        $recentPublishedSubquery = DB::table('puzzle_setters')
            ->join('video_puzzles', 'video_puzzles.puzzle_id', '=', 'puzzle_setters.puzzle_id')
            ->join('videos', 'videos.id', '=', 'video_puzzles.video_id')
            ->whereColumn('puzzle_setters.setter_id', 'setters.id')
            ->whereNotNull('videos.published_at')
            ->selectRaw('CASE WHEN COUNT(DISTINCT videos.id) > 1 THEN MAX(videos.published_at) ELSE NULL END');

        $query = Setter::query()
            ->withCount('puzzles')
            ->addSelect([
                'first_published_at' => $firstPublishedSubquery,
                'recent_published_at' => $recentPublishedSubquery,
            ]);

        if ($search !== '') {
            $term = '%'.mb_strtolower($search).'%';
            $query->where(function ($q) use ($term): void {
                $q->whereRaw('LOWER(setters.name) LIKE ?', [$term])
                    ->orWhereRaw("LOWER(COALESCE(setters.bio, '')) LIKE ?", [$term]);
            });
        }

        match ($sort) {
            'name' => $query
                ->orderByRaw('LOWER(setters.name) '.($direction === 'asc' ? 'ASC' : 'DESC'))
                ->orderBy('setters.id', 'asc'),
            'first' => $query
                ->orderByRaw('CASE WHEN first_published_at IS NULL THEN 1 ELSE 0 END ASC')
                ->orderBy('first_published_at', $direction)
                ->orderBy('setters.id', 'asc'),
            'recent' => $query
                ->orderByRaw('CASE WHEN recent_published_at IS NULL THEN 1 ELSE 0 END ASC')
                ->orderBy('recent_published_at', $direction)
                ->orderBy('setters.id', 'asc'),
            default => $query
                ->orderBy('puzzles_count', $direction)
                ->orderByRaw('LOWER(setters.name) ASC')
                ->orderBy('setters.id', 'asc'),
        };

        $setters = $query->paginate(20)->withQueryString();

        $setters->getCollection()->load([
            'puzzles' => function ($puzzleQuery): void {
                $puzzleQuery->with(['videos' => function ($videoQuery): void {
                    $videoQuery->orderBy('published_at', 'asc')->orderBy('id', 'asc');
                }]);
            },
        ]);

        $setters->getCollection()->transform(function (Setter $setter): Setter {
            $puzzleVideos = $setter->puzzles->flatMap(function ($puzzle) {
                return $puzzle->videos->map(function ($video) use ($puzzle): array {
                    return [
                        'puzzle' => $puzzle,
                        'video' => $video,
                        'published_at' => $video->published_at,
                    ];
                });
            })->filter(fn (array $item): bool => ! is_null($item['published_at']))
                ->sortBy('published_at')
                ->values();

            $distinctVideos = $puzzleVideos->unique('video.id')->values();

            $first = $puzzleVideos->first();
            $recent = $distinctVideos->count() > 1 ? $puzzleVideos->last() : null;

            $setter->setAttribute('first_puzzle', $first['puzzle'] ?? null);
            $setter->setAttribute('first_video', $first['video'] ?? null);
            $setter->setAttribute('first_date', $first['published_at'] ?? null);

            $setter->setAttribute('recent_puzzle', $recent['puzzle'] ?? null);
            $setter->setAttribute('recent_video', $recent['video'] ?? null);
            $setter->setAttribute('recent_date', $recent['published_at'] ?? null);

            return $setter;
        });

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
