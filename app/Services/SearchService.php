<?php

namespace App\Services;

use App\Models\Bookmark;
use App\Models\Category;
use App\Models\Constraint;
use App\Models\Host;
use App\Models\Puzzle;
use App\Models\Setter;
use App\Models\Video;

/**
 * Service providing cross-entity catalog search across videos, timestamp bookmarks, puzzles, setters, categories, constraints, and hosts.
 */
class SearchService
{
    /**
     * Default number of items per page for search pagination.
     */
    public const PER_PAGE = 30;

    /**
     * Execute a search query across all catalog entities with pagination support.
     *
     * @return array<string, mixed>
     */
    public function search(?string $rawQuery, ?string $type = 'all', int $perPage = self::PER_PAGE): array
    {
        $query = trim((string) $rawQuery);
        $type = in_array($type, ['all', 'videos', 'bookmarks', 'puzzles', 'setters', 'categories', 'constraints', 'hosts'], true)
            ? $type
            : 'all';

        if ($query === '') {
            return [
                'query' => '',
                'type' => $type,
                'videos' => collect(),
                'bookmarks' => collect(),
                'puzzles' => collect(),
                'setters' => collect(),
                'categories' => collect(),
                'constraints' => collect(),
                'hosts' => collect(),
                'counts' => [
                    'all' => 0,
                    'videos' => 0,
                    'bookmarks' => 0,
                    'puzzles' => 0,
                    'setters' => 0,
                    'categories' => 0,
                    'constraints' => 0,
                    'hosts' => 0,
                ],
                'total_count' => 0,
                'per_page' => $perPage,
            ];
        }

        $term = '%'.mb_strtolower($query).'%';
        $currentPage = (int) request('page', 1);

        // 1. Base Query: Videos
        // Matches title, music credits, hosts, bookmarks, or attached puzzle titles / setters / categories / constraints
        $videosQuery = Video::query()
            ->with(['hosts', 'puzzles.category', 'puzzles.setters', 'patreonPost', 'bookmarks'])
            ->where(function ($q) use ($term) {
                $q->whereRaw('LOWER(title) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(intro_music) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(outro_music) LIKE ?', [$term])
                    ->orWhereHas('hosts', fn ($h) => $h->whereRaw('LOWER(name) LIKE ?', [$term]))
                    ->orWhereHas('bookmarks', fn ($b) => $b->whereRaw('LOWER(label) LIKE ?', [$term]))
                    ->orWhereHas('puzzles', function ($p) use ($term) {
                        $p->whereRaw('LOWER(title) LIKE ?', [$term])
                            ->orWhereHas('setters', fn ($s) => $s->whereRaw('LOWER(name) LIKE ?', [$term]))
                            ->orWhereHas('category', fn ($c) => $c->whereRaw('LOWER(name) LIKE ?', [$term]))
                            ->orWhereHas('constraints', fn ($cn) => $cn->whereRaw('LOWER(name) LIKE ?', [$term]));
                    });
            })
            ->orderByEffectivePublishedAt('desc')
            ->orderByDesc('id');

        // 2. Base Query: Bookmarks (Video Timestamp Annotations)
        // Matches label
        $bookmarksQuery = Bookmark::query()
            ->with(['video.hosts', 'video.patreonPost', 'puzzle'])
            ->whereHas('video')
            ->whereRaw('LOWER(bookmarks.label) LIKE ?', [$term])
            ->join('videos', 'bookmarks.video_id', '=', 'videos.id')
            ->select('bookmarks.*')
            ->orderByRaw(Video::EFFECTIVE_PUBLISHED_AT_SQL.' DESC')
            ->orderBy('bookmarks.timestamp_seconds');

        // 3. Base Query: Puzzles
        // Matches puzzle title, rules text, setters, category, constraints
        $puzzlesQuery = Puzzle::query()
            ->with(['setters', 'category', 'constraints', 'videos.hosts', 'links'])
            ->where(function ($q) use ($term) {
                $q->whereRaw('LOWER(title) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(rules_raw) LIKE ?', [$term])
                    ->orWhereHas('setters', fn ($s) => $s->whereRaw('LOWER(name) LIKE ?', [$term]))
                    ->orWhereHas('category', fn ($c) => $c->whereRaw('LOWER(name) LIKE ?', [$term]))
                    ->orWhereHas('constraints', fn ($cn) => $cn->whereRaw('LOWER(name) LIKE ?', [$term]));
            })
            ->orderBy('title');

        // 4. Base Query: Setters
        $settersQuery = Setter::query()
            ->with(['links', 'puzzles' => fn ($q) => $q->with('videos')->limit(4)])
            ->withCount('puzzles')
            ->where(function ($q) use ($term) {
                $q->whereRaw('LOWER(name) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(bio) LIKE ?', [$term]);
            })
            ->orderByDesc('puzzles_count')
            ->orderBy('name');

        // 5. Base Query: Categories (Puzzle Types)
        $categoriesQuery = Category::query()
            ->withCount('puzzles')
            ->where(function ($q) use ($term) {
                $q->whereRaw('LOWER(name) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(description) LIKE ?', [$term]);
            })
            ->orderByDesc('puzzles_count')
            ->orderBy('name');

        // 6. Base Query: Constraints (Variants)
        $constraintsQuery = Constraint::query()
            ->withCount('puzzles')
            ->where(function ($q) use ($term) {
                $q->whereRaw('LOWER(name) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(description) LIKE ?', [$term]);
            })
            ->orderByDesc('puzzles_count')
            ->orderBy('name');

        // 7. Base Query: Hosts
        $hostsQuery = Host::query()
            ->with(['videos' => fn ($q) => $q->with('patreonPost')->orderByEffectivePublishedAt('desc')->limit(4)])
            ->withCount('videos')
            ->whereRaw('LOWER(name) LIKE ?', [$term])
            ->orderByDesc('videos_count')
            ->orderBy('name');

        // Calculate accurate total counts for all categories
        $videosCount = (clone $videosQuery)->count();
        $bookmarksCount = (clone $bookmarksQuery)->count();
        $puzzlesCount = (clone $puzzlesQuery)->count();
        $settersCount = (clone $settersQuery)->count();
        $categoriesCount = (clone $categoriesQuery)->count();
        $constraintsCount = (clone $constraintsQuery)->count();
        $hostsCount = (clone $hostsQuery)->count();

        $counts = [
            'videos' => $videosCount,
            'bookmarks' => $bookmarksCount,
            'puzzles' => $puzzlesCount,
            'setters' => $settersCount,
            'categories' => $categoriesCount,
            'constraints' => $constraintsCount,
            'hosts' => $hostsCount,
        ];
        $totalCount = array_sum($counts);
        $counts['all'] = $totalCount;

        // Fetch paginated results based on active type
        if ($type === 'videos') {
            $videos = $videosQuery->paginate($perPage)->withQueryString();
            $bookmarks = collect();
            $puzzles = collect();
            $setters = collect();
            $categories = collect();
            $constraints = collect();
            $hosts = collect();
        } elseif ($type === 'bookmarks') {
            $videos = collect();
            $bookmarks = $bookmarksQuery->paginate($perPage)->withQueryString();
            $puzzles = collect();
            $setters = collect();
            $categories = collect();
            $constraints = collect();
            $hosts = collect();
        } elseif ($type === 'puzzles') {
            $videos = collect();
            $bookmarks = collect();
            $puzzles = $puzzlesQuery->paginate($perPage)->withQueryString();
            $setters = collect();
            $categories = collect();
            $constraints = collect();
            $hosts = collect();
        } elseif ($type === 'setters') {
            $videos = collect();
            $bookmarks = collect();
            $puzzles = collect();
            $setters = $settersQuery->paginate($perPage)->withQueryString();
            $categories = collect();
            $constraints = collect();
            $hosts = collect();
        } elseif ($type === 'categories') {
            $videos = collect();
            $bookmarks = collect();
            $puzzles = collect();
            $setters = collect();
            $categories = $categoriesQuery->paginate($perPage)->withQueryString();
            $constraints = collect();
            $hosts = collect();
        } elseif ($type === 'constraints') {
            $videos = collect();
            $bookmarks = collect();
            $puzzles = collect();
            $setters = collect();
            $categories = collect();
            $constraints = $constraintsQuery->paginate($perPage)->withQueryString();
            $hosts = collect();
        } elseif ($type === 'hosts') {
            $videos = collect();
            $bookmarks = collect();
            $puzzles = collect();
            $setters = collect();
            $categories = collect();
            $constraints = collect();
            $hosts = $hostsQuery->paginate($perPage)->withQueryString();
        } else {
            // type === 'all':
            // Videos are paginated (30 per page) so large result sets can be browsed across pages
            $videos = $videosQuery->paginate($perPage)->withQueryString();

            // Display secondary entity highlights on page 1
            if ($currentPage <= 1) {
                $bookmarks = $bookmarksQuery->limit(24)->get();
                $puzzles = $puzzlesQuery->limit(30)->get();
                $setters = $settersQuery->limit(12)->get();
                $categories = $categoriesQuery->limit(12)->get();
                $constraints = $constraintsQuery->limit(12)->get();
                $hosts = $hostsQuery->limit(10)->get();
            } else {
                $bookmarks = collect();
                $puzzles = collect();
                $setters = collect();
                $categories = collect();
                $constraints = collect();
                $hosts = collect();
            }
        }

        return [
            'query' => $query,
            'type' => $type,
            'videos' => $videos,
            'bookmarks' => $bookmarks,
            'puzzles' => $puzzles,
            'setters' => $setters,
            'categories' => $categories,
            'constraints' => $constraints,
            'hosts' => $hosts,
            'counts' => $counts,
            'total_count' => $totalCount,
            'per_page' => $perPage,
        ];
    }
}
