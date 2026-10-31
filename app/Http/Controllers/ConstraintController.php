<?php

namespace App\Http\Controllers;

use App\Models\Constraint;
use App\Models\Video;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $search = trim($request->query('q', $request->query('search', '')));

        $effectiveDateSql = Video::EFFECTIVE_PUBLISHED_AT_SQL;

        $videosCountSubquery = DB::table('puzzle_constraints')
            ->join('video_puzzles', 'video_puzzles.puzzle_id', '=', 'puzzle_constraints.puzzle_id')
            ->join('videos', 'videos.id', '=', 'video_puzzles.video_id')
            ->whereColumn('puzzle_constraints.constraint_id', 'constraints.id')
            ->whereRaw("{$effectiveDateSql} IS NOT NULL")
            ->selectRaw('COUNT(DISTINCT videos.id)');

        $firstPublishedSubquery = DB::table('puzzle_constraints')
            ->join('video_puzzles', 'video_puzzles.puzzle_id', '=', 'puzzle_constraints.puzzle_id')
            ->join('videos', 'videos.id', '=', 'video_puzzles.video_id')
            ->whereColumn('puzzle_constraints.constraint_id', 'constraints.id')
            ->whereRaw("{$effectiveDateSql} IS NOT NULL")
            ->selectRaw("MIN({$effectiveDateSql})");

        $recentPublishedSubquery = DB::table('puzzle_constraints')
            ->join('video_puzzles', 'video_puzzles.puzzle_id', '=', 'puzzle_constraints.puzzle_id')
            ->join('videos', 'videos.id', '=', 'video_puzzles.video_id')
            ->whereColumn('puzzle_constraints.constraint_id', 'constraints.id')
            ->whereRaw("{$effectiveDateSql} IS NOT NULL")
            ->selectRaw("CASE WHEN COUNT(DISTINCT videos.id) > 1 THEN MAX({$effectiveDateSql}) ELSE NULL END");

        $query = Constraint::query()
            ->withCount('puzzles')
            ->addSelect([
                'videos_count' => $videosCountSubquery,
                'first_published_at' => $firstPublishedSubquery,
                'recent_published_at' => $recentPublishedSubquery,
            ]);

        if ($search !== '') {
            $term = '%'.mb_strtolower($search).'%';
            $query->where(function ($q) use ($term): void {
                $q->whereRaw('LOWER(constraints.name) LIKE ?', [$term])
                    ->orWhereRaw("LOWER(COALESCE(constraints.description, '')) LIKE ?", [$term]);
            });
        }

        match ($sort) {
            'name' => $query
                ->orderByRaw('LOWER(constraints.name) '.($direction === 'asc' ? 'ASC' : 'DESC'))
                ->orderBy('constraints.id', 'asc'),
            'puzzles' => $query
                ->orderBy('puzzles_count', $direction)
                ->orderByRaw('LOWER(constraints.name) ASC')
                ->orderBy('constraints.id', 'asc'),
            'first' => $query
                ->orderByRaw('CASE WHEN first_published_at IS NULL THEN 1 ELSE 0 END ASC')
                ->orderBy('first_published_at', $direction)
                ->orderBy('constraints.id', 'asc'),
            'recent' => $query
                ->orderByRaw('CASE WHEN recent_published_at IS NULL THEN 1 ELSE 0 END ASC')
                ->orderBy('recent_published_at', $direction)
                ->orderBy('constraints.id', 'asc'),
            default => $query
                ->orderBy('videos_count', $direction)
                ->orderByRaw('LOWER(constraints.name) ASC')
                ->orderBy('constraints.id', 'asc'),
        };

        $constraints = $query->paginate(20)->withQueryString();
        if ($constraints->isEmpty() && $constraints->total() > 0) {
            $constraints = $query->paginate(20, ['*'], 'page', 1)->withQueryString();
        }

        $constraints->getCollection()->load([
            'puzzles' => function ($puzzleQuery): void {
                $puzzleQuery->with(['videos' => function ($videoQuery): void {
                    $videoQuery->with('patreonPost')->orderByEffectivePublishedAt('asc')->orderBy('id', 'asc');
                }]);
            },
        ]);

        $constraints->getCollection()->transform(function (Constraint $constraint): Constraint {
            $puzzleVideos = $constraint->puzzles->flatMap(function ($puzzle) {
                return $puzzle->videos->map(function ($video) use ($puzzle): array {
                    return [
                        'puzzle' => $puzzle,
                        'video' => $video,
                        'published_at' => $video->effective_published_at,
                    ];
                });
            })->filter(fn (array $item): bool => ! is_null($item['published_at']))
                ->sortBy('published_at')
                ->values();

            $distinctVideos = $puzzleVideos->unique('video.id')->values();

            $first = $puzzleVideos->first();
            $recent = $distinctVideos->count() > 1 ? $puzzleVideos->last() : null;

            $constraint->setAttribute('videos_count', $distinctVideos->count());
            $constraint->setAttribute('first_puzzle', $first['puzzle'] ?? null);
            $constraint->setAttribute('first_video', $first['video'] ?? null);
            $constraint->setAttribute('first_date', $first['published_at'] ?? null);

            $constraint->setAttribute('recent_puzzle', $recent['puzzle'] ?? null);
            $constraint->setAttribute('recent_video', $recent['video'] ?? null);
            $constraint->setAttribute('recent_date', $recent['published_at'] ?? null);

            return $constraint;
        });

        return view('constraints.index', compact('constraints', 'sort', 'direction', 'search'));
    }

    /**
     * Display a constraint's detail page with definition, key stat tiles, and video listing.
     */
    public function show(Constraint $constraint): View
    {
        $baseVideosQuery = Video::query()
            ->whereHas('puzzles.constraints', function ($query) use ($constraint): void {
                $query->where('constraints.id', $constraint->id);
            })
            ->whereRaw(Video::EFFECTIVE_PUBLISHED_AT_SQL.' IS NOT NULL');

        $aggregates = (clone $baseVideosQuery)
            ->toBase()
            ->selectRaw('COUNT(*) as total_videos, COALESCE(SUM(view_count), 0) as total_views')
            ->first();

        $totalVideos = (int) ($aggregates->total_videos ?? 0);
        $totalViews = (int) ($aggregates->total_views ?? 0);

        $firstVideo = $totalVideos > 0
            ? (clone $baseVideosQuery)->with('patreonPost')->orderByEffectivePublishedAt('asc')->orderBy('id', 'asc')->first()
            : null;

        $latestVideo = $totalVideos > 1
            ? (clone $baseVideosQuery)->with('patreonPost')->orderByEffectivePublishedAt('desc')->orderByDesc('id')->first()
            : $firstVideo;

        $puzzlesCount = $constraint->puzzles()->count();

        $videos = Video::query()
            ->whereHas('puzzles.constraints', function ($query) use ($constraint): void {
                $query->where('constraints.id', $constraint->id);
            })
            ->with([
                'hosts',
                'patreonPost',
                'puzzles' => function ($query): void {
                    $query->with(['setters', 'category', 'constraints']);
                },
            ])
            ->orderByEffectivePublishedAt('desc')
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
