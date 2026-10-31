<?php

namespace App\Services;

use App\Models\Constraint;
use App\Models\Puzzle;
use App\Models\Setter;
use App\Models\Video;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Service for computing and caching catalog-wide statistics, trends, and analytical observations.
 */
class CatalogStatsService
{
    /**
     * Cache key for the full analytics dashboard summary.
     */
    public const DASHBOARD_CACHE_KEY = 'catalog_analytics_summary_v6';

    /**
     * Cache key for the homepage counter bar statistics.
     */
    public const HOME_CACHE_KEY = 'catalog_home_stats_v1';

    /**
     * Cache TTL in seconds (30 minutes).
     */
    public const CACHE_TTL_SECONDS = 1800;

    /**
     * Retrieve the cached homepage statistics bar counters.
     *
     * @return array{total_videos: int, total_puzzles: int, total_setters: int, total_constraints: int}
     */
    public function getHomeStats(): array
    {
        /** @var array{total_videos: int, total_puzzles: int, total_setters: int, total_constraints: int} $stats */
        $stats = Cache::remember(self::HOME_CACHE_KEY, self::CACHE_TTL_SECONDS, function (): array {
            return $this->computeHomeStats();
        });

        return $stats;
    }

    /**
     * Compute the homepage statistics bar counters directly from the database.
     *
     * @return array{total_videos: int, total_puzzles: int, total_setters: int, total_constraints: int}
     */
    public function computeHomeStats(): array
    {
        return [
            'total_videos' => Video::count(),
            'total_puzzles' => Puzzle::count(),
            'total_setters' => Setter::has('puzzles')->count(),
            'total_constraints' => Constraint::has('puzzles')->count(),
        ];
    }

    /**
     * Retrieve the cached catalog analytics dashboard statistics, recomputing if missing or stale.
     *
     * @return array<string, mixed>
     */
    public function getDashboardStats(): array
    {
        $stats = Cache::remember(self::DASHBOARD_CACHE_KEY, self::CACHE_TTL_SECONDS, function (): array {
            return $this->computeStats();
        });

        if (! is_array($stats) || ! isset($stats['categoryMonthly'], $stats['constraintStats'], $stats['observations'], $stats['musicStats'], $stats['totalUniqueConstraints'], $stats['formatStats']['patreon_exclusive'])) {
            $stats = $this->computeStats();
            Cache::put(self::DASHBOARD_CACHE_KEY, $stats, self::CACHE_TTL_SECONDS);
        }

        $stats['categoryMonthly'] = $stats['categoryMonthly'] ?? [];
        $stats['constraintStats'] = $stats['constraintStats'] ?? [];
        $stats['totalConstraintOccurrences'] = $stats['totalConstraintOccurrences'] ?? 0;
        $stats['totalUniqueConstraints'] = $stats['totalUniqueConstraints'] ?? count($stats['constraintStats'] ?? []);
        $stats['totalVariantPuzzles'] = $stats['totalVariantPuzzles'] ?? 0;
        $stats['observations'] = $stats['observations'] ?? [];
        $stats['musicStats'] = $stats['musicStats'] ?? [
            'intro' => [],
            'outro' => [],
            'legend' => [],
            'total_intro_tagged' => 0,
            'total_outro_tagged' => 0,
            'distinct_tracks' => 0,
        ];

        return $stats;
    }

    /**
     * Flush all cached catalog statistics (both dashboard and homepage counters).
     */
    public static function flushCache(): void
    {
        Cache::forget(self::DASHBOARD_CACHE_KEY);
        Cache::forget(self::HOME_CACHE_KEY);
    }

    /**
     * Compute aggregated statistics from the video catalog.
     *
     * @return array<string, mixed>
     */
    public function computeStats(): array
    {
        $isSqlite = DB::getDriverName() === 'sqlite';
        $yearExpr = $isSqlite ? "strftime('%Y', published_at)" : "TO_CHAR(published_at, 'YYYY')";
        $monthExpr = $isSqlite ? "strftime('%Y-%m', published_at)" : "TO_CHAR(published_at, 'YYYY-MM')";

        // Load catalog video attributes for unified in-memory grouping and median calculations
        $allVideos = Video::query()
            ->select(['id', 'video_type', 'access_level', 'patreon_post_id', 'duration_seconds', 'view_count', 'published_at'])
            ->get();

        $totalVideos = $allVideos->count();
        $totalViews = (int) $allVideos->sum('view_count');
        $totalSeconds = (int) $allVideos->sum('duration_seconds');

        $allViewsList = $allVideos->pluck('view_count')->map(fn ($v): int => (int) $v)->all();
        $positiveDurationsList = $allVideos->where('duration_seconds', '>', 0)->pluck('duration_seconds')->map(fn ($d): int => (int) $d)->all();

        $medianViews = (int) round($this->calculateMedian($allViewsList));
        $medianSeconds = (int) round($this->calculateMedian($positiveDurationsList));

        $totalHours = round($totalSeconds / 3600, 1);
        $totalDaysFootage = round($totalSeconds / 86400, 1);

        $publishedVideos = $allVideos->filter(fn (Video $v): bool => $v->published_at !== null)->sortBy('published_at');
        $earliestVideo = $publishedVideos->first();
        $latestVideo = $publishedVideos->last();

        // Yearly Trend (All-Time)
        $yearlyGrouped = $publishedVideos->groupBy(fn (Video $v): string => $v->published_at->format('Y'))->sortKeys();
        $yearly = $yearlyGrouped->map(function (Collection $group, $yr): array {
            $views = $group->pluck('view_count')->map(fn ($v): int => (int) $v)->all();
            $durs = $group->where('duration_seconds', '>', 0)->pluck('duration_seconds')->map(fn ($d): int => (int) $d)->all();
            $medViews = (int) round($this->calculateMedian($views));
            $medDurSec = (int) round($this->calculateMedian($durs));
            $medDurMin = round($medDurSec / 60, 1);

            return [
                'year' => (string) $yr,
                'count' => $group->count(),
                'views' => (int) $group->sum('view_count'),
                'median_views' => $medViews,
                'median_duration_minutes' => $medDurMin,
                'avg_views' => $medViews,
                'avg_duration_minutes' => $medDurMin,
            ];
        })->values()->all();

        // Monthly Trend (Last 36 Months)
        $cutoff = now()->subMonths(36);
        $monthlyVideos = $publishedVideos->filter(fn (Video $v): bool => $v->published_at >= $cutoff);
        $monthlyGrouped = $monthlyVideos->groupBy(fn (Video $v): string => $v->published_at->format('Y-m'))->sortKeys();

        $monthly = $monthlyGrouped->map(function (Collection $group, $monthKey): array {
            $key = (string) $monthKey;
            try {
                $label = Carbon::createFromFormat('!Y-m', $key)->format('M Y');
            } catch (\Throwable) {
                $label = $key;
            }

            $views = $group->pluck('view_count')->map(fn ($v): int => (int) $v)->all();
            $durs = $group->where('duration_seconds', '>', 0)->pluck('duration_seconds')->map(fn ($d): int => (int) $d)->all();
            $medViews = (int) round($this->calculateMedian($views));
            $medDurSec = (int) round($this->calculateMedian($durs));
            $medDurMin = round($medDurSec / 60, 1);

            return [
                'key' => $key,
                'label' => $label,
                'count' => $group->count(),
                'views' => (int) $group->sum('view_count'),
                'median_views' => $medViews,
                'median_duration_minutes' => $medDurMin,
                'avg_views' => $medViews,
                'avg_duration_minutes' => $medDurMin,
            ];
        })->values()->all();

        // Video Format Breakdown
        $formatLabels = [
            'youtube' => 'Standard Solves',
            'youtube_short' => 'YouTube Shorts',
            'live_stream' => 'Live Streams',
            'patreon_exclusive' => 'Patreon Exclusives',
        ];

        $byFormat = $allVideos->groupBy(fn (Video $v): string => ($v->isExclusive() || $v->video_type === 'patreon')
            ? 'patreon_exclusive'
            : $v->video_type
        );
        $formatStats = [];
        foreach (['youtube', 'youtube_short', 'live_stream', 'patreon_exclusive'] as $type) {
            $group = $byFormat->get($type, collect());
            $count = $group->count();
            $views = (int) $group->sum('view_count');

            $viewsList = $group->pluck('view_count')->map(fn ($v): int => (int) $v)->all();
            $durList = $group->where('duration_seconds', '>', 0)->pluck('duration_seconds')->map(fn ($d): int => (int) $d)->all();
            $medView = (int) round($this->calculateMedian($viewsList));
            $medDur = (int) round($this->calculateMedian($durList));

            $formatStats[$type] = [
                'type' => $type,
                'label' => $formatLabels[$type] ?? ucfirst($type),
                'count' => $count,
                'percentage' => $totalVideos > 0 ? round(($count / $totalVideos) * 100, 1) : 0,
                'total_views' => $views,
                'views_percentage' => $totalViews > 0 ? round(($views / $totalViews) * 100, 1) : 0,
                'median_views' => $medView,
                'median_duration_seconds' => $medDur,
                'median_duration_formatted' => $this->formatDurationSeconds($medDur),
                'avg_views' => $medView,
                'avg_duration_seconds' => $medDur,
                'avg_duration_formatted' => $this->formatDurationSeconds($medDur),
            ];
        }

        // Duration Brackets (Sweet Spot Analysis)
        $bracketDefs = [
            1 => ['bracket' => 'Shorts', 'filter' => fn (Video $v): bool => $v->video_type === 'youtube_short'],
            2 => ['bracket' => '< 15 mins', 'filter' => fn (Video $v): bool => $v->video_type !== 'youtube_short' && (int) $v->duration_seconds <= 900],
            3 => ['bracket' => '15 - 30 mins', 'filter' => fn (Video $v): bool => $v->video_type !== 'youtube_short' && (int) $v->duration_seconds > 900 && (int) $v->duration_seconds <= 1800],
            4 => ['bracket' => '30 - 45 mins', 'filter' => fn (Video $v): bool => $v->video_type !== 'youtube_short' && (int) $v->duration_seconds > 1800 && (int) $v->duration_seconds <= 2700],
            5 => ['bracket' => '45 - 60 mins', 'filter' => fn (Video $v): bool => $v->video_type !== 'youtube_short' && (int) $v->duration_seconds > 2700 && (int) $v->duration_seconds <= 3600],
            6 => ['bracket' => '> 60 mins', 'filter' => fn (Video $v): bool => $v->video_type !== 'youtube_short' && (int) $v->duration_seconds > 3600],
        ];

        $validDurationVideos = $allVideos->where('duration_seconds', '>', 0);
        $durationBrackets = [];
        foreach ($bracketDefs as $def) {
            $group = $validDurationVideos->filter($def['filter']);
            $cnt = $group->count();
            $viewsList = $group->pluck('view_count')->map(fn ($v): int => (int) $v)->all();
            $durList = $group->pluck('duration_seconds')->map(fn ($d): int => (int) $d)->all();
            $medViews = (int) round($this->calculateMedian($viewsList));
            $medDur = (int) round($this->calculateMedian($durList));

            $durationBrackets[] = [
                'bracket' => $def['bracket'],
                'count' => $cnt,
                'percentage' => $totalVideos > 0 ? round(($cnt / $totalVideos) * 100, 1) : 0,
                'total_views' => (int) $group->sum('view_count'),
                'median_views' => $medViews,
                'median_duration_formatted' => $this->formatDurationSeconds($medDur),
                'avg_views' => $medViews,
                'avg_duration_formatted' => $this->formatDurationSeconds($medDur),
            ];
        }

        // Day of Week Distribution
        $dayNames = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];

        $byDow = $publishedVideos->groupBy(fn (Video $v): int => (int) $v->published_at->dayOfWeek);
        $dowData = [];
        for ($i = 0; $i <= 6; $i++) {
            $group = $byDow->get($i, collect());
            $viewsList = $group->pluck('view_count')->map(fn ($v): int => (int) $v)->all();
            $medViews = (int) round($this->calculateMedian($viewsList));

            $dowData[] = [
                'dow' => $i,
                'day' => $dayNames[$i] ?? 'Day '.$i,
                'count' => $group->count(),
                'total_views' => (int) $group->sum('view_count'),
                'median_views' => $medViews,
                'avg_views' => $medViews,
            ];
        }

        // Milestones & Hall of Fame Records
        $mostViewedStandard = Video::where('video_type', 'youtube')
            ->orderByDesc('view_count')
            ->first(['id', 'title', 'slug', 'youtube_video_id', 'view_count', 'published_at', 'duration_seconds', 'youtube_thumbnail_url']);

        $mostViewedShort = Video::where('video_type', 'youtube_short')
            ->orderByDesc('view_count')
            ->first(['id', 'title', 'slug', 'youtube_video_id', 'view_count', 'published_at', 'duration_seconds', 'youtube_thumbnail_url']);

        $longestSolve = Video::where('video_type', 'youtube')
            ->orderByDesc('duration_seconds')
            ->first(['id', 'title', 'slug', 'youtube_video_id', 'view_count', 'published_at', 'duration_seconds', 'youtube_thumbnail_url']);

        $shortestSolve = Video::where('video_type', 'youtube')
            ->where('duration_seconds', '>=', 60)
            ->orderBy('duration_seconds')
            ->first(['id', 'title', 'slug', 'youtube_video_id', 'view_count', 'published_at', 'duration_seconds', 'youtube_thumbnail_url']);

        // Month with peak uploads
        $peakUploadRow = Video::query()
            ->selectRaw("{$monthExpr} as m_key, count(*) as count")
            ->whereNotNull('published_at')
            ->groupBy('m_key')
            ->orderByDesc('count')
            ->first();

        $peakUploadMonth = null;
        if ($peakUploadRow && $peakUploadRow->m_key) {
            try {
                $monthLabel = Carbon::createFromFormat('!Y-m', $peakUploadRow->m_key)->format('F Y');
            } catch (\Throwable) {
                $monthLabel = $peakUploadRow->m_key;
            }
            $peakUploadMonth = $monthLabel.' ('.number_format($peakUploadRow->count).' videos)';
        }

        // Month with peak views
        $peakViewsRow = Video::query()
            ->selectRaw("{$monthExpr} as m_key, sum(view_count) as total_views")
            ->whereNotNull('published_at')
            ->groupBy('m_key')
            ->orderByDesc('total_views')
            ->first();

        $peakViewsMonth = null;
        if ($peakViewsRow && $peakViewsRow->m_key) {
            try {
                $monthLabel = Carbon::createFromFormat('!Y-m', $peakViewsRow->m_key)->format('F Y');
            } catch (\Throwable) {
                $monthLabel = $peakViewsRow->m_key;
            }
            $peakViewsMonth = $monthLabel.' ('.number_format($peakViewsRow->total_views).' views)';
        }

        // -------------------------------------------------------------
        // Puzzle Category Breakdown
        // -------------------------------------------------------------
        $allCategories = DB::table('categories')->orderBy('id')->get(['id', 'name', 'slug']);

        $classifiedRows = DB::table('video_puzzles')
            ->join('videos', 'video_puzzles.video_id', '=', 'videos.id')
            ->join('puzzles', 'video_puzzles.puzzle_id', '=', 'puzzles.id')
            ->join('categories', 'puzzles.category_id', '=', 'categories.id')
            ->select(['categories.id as category_id', 'categories.name as category_name', 'videos.id as video_id', 'videos.view_count'])
            ->get()
            ->groupBy('category_id');

        $unclassifiedRows = DB::table('videos')
            ->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('video_puzzles')
                    ->whereColumn('video_puzzles.video_id', 'videos.id');
            })
            ->select(['id', 'view_count'])
            ->get();

        $unclassifiedCount = $unclassifiedRows->count();
        $unclassifiedViews = (int) $unclassifiedRows->sum('view_count');
        $unclassifiedViewsList = $unclassifiedRows->pluck('view_count')->map(fn ($v): int => (int) $v)->all();
        $unclassifiedMedianViews = (int) round($this->calculateMedian($unclassifiedViewsList));

        $categoryList = [];
        $totalClassifiedCount = 0;

        foreach ($allCategories as $cat) {
            $group = $classifiedRows->get($cat->id, collect());
            $count = $group->count();
            $totalClassifiedCount += $count;
            $distinctVideos = $group->pluck('video_id')->unique()->count();
            $views = (int) $group->sum('view_count');
            $viewsList = $group->pluck('view_count')->map(fn ($v): int => (int) $v)->all();
            $medViews = (int) round($this->calculateMedian($viewsList));

            $categoryList[] = [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'count' => $count,
                'distinct_videos' => $distinctVideos,
                'total_views' => $views,
                'median_views' => $medViews,
                'avg_views' => $medViews,
                'is_unclassified' => false,
            ];
        }

        usort($categoryList, fn (array $a, array $b): int => $b['count'] <=> $a['count']);

        $totalAllPuzzlesAndUnclassified = $totalClassifiedCount + $unclassifiedCount;

        $categoryWithUnclassified = $categoryList;
        $categoryWithUnclassified[] = [
            'id' => null,
            'name' => 'Unclassified',
            'slug' => 'unclassified',
            'count' => $unclassifiedCount,
            'distinct_videos' => $unclassifiedCount,
            'total_views' => $unclassifiedViews,
            'median_views' => $unclassifiedMedianViews,
            'avg_views' => $unclassifiedMedianViews,
            'is_unclassified' => true,
        ];

        foreach ($categoryWithUnclassified as &$item) {
            $item['percentage'] = $totalAllPuzzlesAndUnclassified > 0
                ? round(($item['count'] / $totalAllPuzzlesAndUnclassified) * 100, 1)
                : 0;
        }
        unset($item);

        // Yearly Category Progression
        $classifiedByYearRaw = DB::table('video_puzzles')
            ->join('videos', 'video_puzzles.video_id', '=', 'videos.id')
            ->join('puzzles', 'video_puzzles.puzzle_id', '=', 'puzzles.id')
            ->join('categories', 'puzzles.category_id', '=', 'categories.id')
            ->selectRaw("{$yearExpr} as yr, categories.name as category_name, count(*) as count")
            ->whereNotNull('videos.published_at')
            ->groupBy('yr', 'categories.name')
            ->get();

        $unclassifiedByYearRaw = DB::table('videos')
            ->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('video_puzzles')
                    ->whereColumn('video_puzzles.video_id', 'videos.id');
            })
            ->selectRaw("{$yearExpr} as yr, count(*) as count")
            ->whereNotNull('videos.published_at')
            ->groupBy('yr')
            ->get()
            ->keyBy('yr');

        $years = Video::query()
            ->selectRaw("{$yearExpr} as yr")
            ->whereNotNull('published_at')
            ->groupBy('yr')
            ->orderBy('yr')
            ->pluck('yr')
            ->all();

        $yearlyCategories = [];
        foreach ($years as $yr) {
            $catCounts = [];
            foreach ($categoryWithUnclassified as $catItem) {
                if ($catItem['is_unclassified']) {
                    $catCounts[$catItem['name']] = isset($unclassifiedByYearRaw[$yr]) ? (int) $unclassifiedByYearRaw[$yr]->count : 0;
                } else {
                    $matching = $classifiedByYearRaw->first(fn ($r): bool => $r->yr === $yr && $r->category_name === $catItem['name']);
                    $catCounts[$catItem['name']] = $matching ? (int) $matching->count : 0;
                }
            }

            $yearlyCategories[] = [
                'year' => (string) $yr,
                'categories' => $catCounts,
            ];
        }

        // Monthly Category Progression
        $classifiedByMonthRaw = DB::table('video_puzzles')
            ->join('videos', 'video_puzzles.video_id', '=', 'videos.id')
            ->join('puzzles', 'video_puzzles.puzzle_id', '=', 'puzzles.id')
            ->join('categories', 'puzzles.category_id', '=', 'categories.id')
            ->selectRaw("{$monthExpr} as mo, categories.name as category_name, count(*) as count")
            ->whereNotNull('videos.published_at')
            ->groupBy('mo', 'categories.name')
            ->get();

        $classifiedMonthMap = [];
        foreach ($classifiedByMonthRaw as $row) {
            $classifiedMonthMap[$row->mo][$row->category_name] = (int) $row->count;
        }

        $unclassifiedByMonthRaw = DB::table('videos')
            ->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('video_puzzles')
                    ->whereColumn('video_puzzles.video_id', 'videos.id');
            })
            ->selectRaw("{$monthExpr} as mo, count(*) as count")
            ->whereNotNull('videos.published_at')
            ->groupBy('mo')
            ->get()
            ->keyBy('mo');

        $months = [];
        if ($earliestVideo && $latestVideo && $earliestVideo->published_at && $latestVideo->published_at) {
            $cursor = $earliestVideo->published_at->copy()->startOfMonth();
            $endMonth = $latestVideo->published_at->copy()->startOfMonth();
            while ($cursor->lte($endMonth)) {
                $months[] = $cursor->format('Y-m');
                $cursor->addMonth();
            }
        }

        $monthlyCategories = [];
        foreach ($months as $mo) {
            $catCounts = [];
            $label = $mo;
            try {
                $label = Carbon::createFromFormat('!Y-m', $mo)->format('M Y');
            } catch (\Throwable) {
                $label = $mo;
            }

            foreach ($categoryWithUnclassified as $catItem) {
                if ($catItem['is_unclassified']) {
                    $catCounts[$catItem['name']] = isset($unclassifiedByMonthRaw[$mo]) ? (int) $unclassifiedByMonthRaw[$mo]->count : 0;
                } else {
                    $catCounts[$catItem['name']] = $classifiedMonthMap[$mo][$catItem['name']] ?? 0;
                }
            }

            $monthlyCategories[] = [
                'month' => (string) $mo,
                'label' => $label,
                'categories' => $catCounts,
            ];
        }

        // -------------------------------------------------------------
        // Variant Sudoku Constraints Breakdown
        // -------------------------------------------------------------
        $constraintStatsRaw = DB::table('puzzle_constraints')
            ->join('constraints', 'puzzle_constraints.constraint_id', '=', 'constraints.id')
            ->join('puzzles', 'puzzle_constraints.puzzle_id', '=', 'puzzles.id')
            ->join('categories', 'puzzles.category_id', '=', 'categories.id')
            ->where(function ($query): void {
                $query->where('categories.slug', 'variant-sudoku')
                    ->orWhere('categories.name', 'Variant Sudoku');
            })
            ->selectRaw('constraints.id, constraints.name, constraints.slug, count(distinct puzzles.id) as puzzle_count')
            ->groupBy('constraints.id', 'constraints.name', 'constraints.slug')
            ->orderByDesc('puzzle_count')
            ->orderBy('constraints.name')
            ->get();

        $totalConstraintOccurrences = (int) $constraintStatsRaw->sum('puzzle_count');
        $totalUniqueConstraints = $constraintStatsRaw->count();

        $totalVariantPuzzles = DB::table('puzzles')
            ->join('categories', 'puzzles.category_id', '=', 'categories.id')
            ->where(function ($query): void {
                $query->where('categories.slug', 'variant-sudoku')
                    ->orWhere('categories.name', 'Variant Sudoku');
            })
            ->count();

        $constraintStats = $this->formatConstraintStats($constraintStatsRaw, $totalConstraintOccurrences);

        $musicStats = $this->computeMusicStats();

        return [
            'kpis' => [
                'total_videos' => $totalVideos,
                'total_views' => $totalViews,
                'total_hours' => $totalHours,
                'total_days_footage' => $totalDaysFootage,
                'median_views' => $medianViews,
                'median_seconds' => $medianSeconds,
                'median_duration_formatted' => $this->formatDurationSeconds($medianSeconds),
                'avg_views' => $medianViews,
                'avg_seconds' => $medianSeconds,
                'avg_duration_formatted' => $this->formatDurationSeconds($medianSeconds),
                'earliest_date' => $earliestVideo?->published_at?->format('M j, Y'),
                'latest_date' => $latestVideo?->published_at?->format('M j, Y'),
            ],
            'yearly' => $yearly,
            'monthly' => $monthly,
            'formatStats' => $formatStats,
            'durationBrackets' => $durationBrackets,
            'dayOfWeek' => $dowData,
            'categoryStats' => $categoryWithUnclassified,
            'categoryYearly' => $yearlyCategories,
            'categoryMonthly' => $monthlyCategories,
            'constraintStats' => $constraintStats,
            'totalConstraintOccurrences' => $totalConstraintOccurrences,
            'totalUniqueConstraints' => $totalUniqueConstraints,
            'totalVariantPuzzles' => $totalVariantPuzzles,
            'musicStats' => $musicStats,
            'milestones' => [
                'most_viewed_standard' => $this->serializeVideoRecord($mostViewedStandard),
                'most_viewed_short' => $this->serializeVideoRecord($mostViewedShort),
                'longest_solve' => $this->serializeVideoRecord($longestSolve),
                'shortest_solve' => $this->serializeVideoRecord($shortestSolve),
                'peak_upload_month' => $peakUploadMonth,
                'peak_views_month' => $peakViewsMonth,
            ],
            'observations' => $this->computeObservations(
                $formatStats,
                $durationBrackets,
                $constraintStats,
                $dowData
            ),
        ];
    }

    /**
     * Format and group the tail constraints (~last 25%) into an "Other" category for clean pie chart display.
     *
     * @param  Collection<int, object>  $constraintStatsRaw
     * @return array<int, array<string, mixed>>
     */
    public function formatConstraintStats(Collection $constraintStatsRaw, int $totalConstraintOccurrences): array
    {
        $items = $constraintStatsRaw->map(function ($row) use ($totalConstraintOccurrences): array {
            $cnt = (int) $row->puzzle_count;

            return [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'slug' => (string) $row->slug,
                'count' => $cnt,
                'percentage' => $totalConstraintOccurrences > 0
                    ? round(($cnt / $totalConstraintOccurrences) * 100, 1)
                    : 0,
                'is_other' => false,
                'url' => route('constraints.show', (string) $row->slug),
            ];
        })->values();

        $totalItems = $items->count();

        if ($totalItems < 4 || $totalConstraintOccurrences <= 0) {
            return $items->all();
        }

        $bestK = null;
        $bestScore = PHP_FLOAT_MAX;

        for ($k = 2; $k <= $totalItems - 2; $k++) {
            $tailCount = $items->slice($k)->sum('count');
            $tailPct = ($tailCount / $totalConstraintOccurrences) * 100;

            if ($tailPct < 10.0 || $tailPct > 40.0) {
                continue;
            }

            if (($items[$k]['percentage'] ?? 0) > 15.0) {
                continue;
            }

            $isTie = ($items[$k - 1]['count'] === $items[$k]['count']);
            $score = abs($tailPct - 25.0) + ($isTie ? 3.0 : 0.0);

            if ($score < $bestScore) {
                $bestScore = $score;
                $bestK = $k;
            }
        }

        if ($bestK === null) {
            return $items->all();
        }

        $prominent = $items->slice(0, $bestK)->values();
        $tail = $items->slice($bestK)->values();

        $otherCount = (int) $tail->sum('count');
        $otherPercentage = $totalConstraintOccurrences > 0
            ? round(($otherCount / $totalConstraintOccurrences) * 100, 1)
            : 0;

        $otherEntry = [
            'id' => null,
            'name' => 'Other',
            'slug' => null,
            'count' => $otherCount,
            'percentage' => $otherPercentage,
            'is_other' => true,
            'url' => route('constraints.index'),
            'distinct_constraints_count' => $tail->count(),
        ];

        return $prominent->push($otherEntry)->all();
    }

    /**
     * Compute automated, data-driven analytical observations and takeaways across the catalog.
     *
     * @param  array<string, mixed>  $formatStats
     * @param  array<int, mixed>  $durationBrackets
     * @param  array<int, mixed>  $constraintStats
     * @param  array<int, mixed>  $dayOfWeek
     * @return array<string, string>
     */
    public function computeObservations(
        array $formatStats,
        array $durationBrackets,
        array $constraintStats,
        array $dayOfWeek = []
    ): array {
        $stdViewsPct = $formatStats['youtube']['views_percentage'] ?? 0;
        $shortsMedian = $formatStats['youtube_short']['median_duration_formatted'] ?? 'under 1 minute';
        $shortsViewsPct = $formatStats['youtube_short']['views_percentage'] ?? 0;

        $formatObs = "Standard solves generate {$stdViewsPct}% of total channel views, while Shorts provide rapid bite-sized puzzles (median {$shortsMedian}) capturing {$shortsViewsPct}% of all views.";

        $standardBrackets = array_values(array_filter($durationBrackets, fn (array $b): bool => ($b['bracket'] ?? '') !== 'Shorts' && ($b['count'] ?? 0) > 0));
        usort($standardBrackets, fn (array $a, array $b): int => ($b['median_views'] ?? 0) <=> ($a['median_views'] ?? 0));

        if (! empty($standardBrackets)) {
            $topBracket = $standardBrackets[0];
            $topLabel = $topBracket['bracket'];
            $topViews = number_format((int) ($topBracket['median_views'] ?? 0));

            if (count($standardBrackets) > 1) {
                $secondBracket = $standardBrackets[1];
                $secondLabel = $secondBracket['bracket'];
                $secondViews = number_format((int) ($secondBracket['median_views'] ?? 0));
                $durationObs = "Videos in the {$topLabel} bracket achieve peak median viewership ({$topViews} views), followed closely by the {$secondLabel} bracket ({$secondViews} views).";
            } else {
                $durationObs = "Videos in the {$topLabel} bracket achieve peak median viewership across standard solves ({$topViews} views).";
            }
        } else {
            $durationObs = 'Viewer engagement metrics will populate as video watch counts are indexed.';
        }

        if (! empty($constraintStats)) {
            $topNames = array_slice(array_filter(array_column($constraintStats, 'name'), fn (string $n): bool => $n !== 'Other'), 0, 4);
            if (count($topNames) >= 2) {
                $last = array_pop($topNames);
                $namesList = implode(', ', $topNames).', and '.$last;
            } else {
                $namesList = $topNames[0] ?? 'Variant constraints';
            }
            $constraintsObs = "{$namesList} represent the most prolific variant mechanics tagged across Variant Sudoku solves on the channel.";
        } else {
            $constraintsObs = 'Variant constraint frequency updates dynamically as variant puzzles are tagged.';
        }

        return [
            'format' => $formatObs,
            'duration' => $durationObs,
            'constraints' => $constraintsObs,
        ];
    }

    /**
     * Convert an Eloquent Video model into a serializable primitive array for safe caching.
     *
     * @return array<string, mixed>|null
     */
    public function serializeVideoRecord(?Video $video): ?array
    {
        if (! $video) {
            return null;
        }

        return [
            'id' => $video->id,
            'title' => $video->title,
            'slug' => $video->slug,
            'youtube_video_id' => $video->youtube_video_id,
            'view_count' => (int) $video->view_count,
            'published_at' => $video->published_at ? $video->published_at->format('M j, Y') : 'N/A',
            'duration_seconds' => (int) $video->duration_seconds,
            'duration_formatted' => gmdate('H:i:s', (int) $video->duration_seconds),
            'duration_hours' => round(((int) $video->duration_seconds) / 3600, 1),
            'youtube_thumbnail_url' => $video->youtube_thumbnail_url,
        ];
    }

    /**
     * Format a duration in seconds into a friendly human string (e.g. 35m 42s or 1h 23m).
     */
    public function formatDurationSeconds(int $seconds): string
    {
        if ($seconds <= 0) {
            return '0s';
        }

        $hours = intdiv($seconds, 3600);
        $remainder = $seconds % 3600;
        $minutes = intdiv($remainder, 60);
        $secs = $remainder % 60;

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }

        if ($minutes > 0) {
            return $secs > 0 ? "{$minutes}m {$secs}s" : "{$minutes}m";
        }

        return "{$secs}s";
    }

    /**
     * Calculate the median of an array of numeric values.
     *
     * @param  array<int|float>  $values
     */
    public function calculateMedian(array $values): float
    {
        $count = count($values);
        if ($count === 0) {
            return 0.0;
        }

        sort($values, SORT_NUMERIC);
        $middle = intdiv($count, 2);

        if ($count % 2 === 1) {
            return (float) $values[$middle];
        }

        return ((float) $values[$middle - 1] + (float) $values[$middle]) / 2.0;
    }

    /**
     * Compute intro and outro music breakdown across tagged videos.
     * Videos without tagged music are excluded.
     *
     * @return array<string, mixed>
     */
    public function computeMusicStats(): array
    {
        $introMusicCounts = DB::table('videos')
            ->whereNotNull('intro_music')
            ->where('intro_music', '!=', '')
            ->selectRaw('intro_music, count(*) as count')
            ->groupBy('intro_music')
            ->orderByDesc('count')
            ->pluck('count', 'intro_music')
            ->toArray();

        $outroMusicCounts = DB::table('videos')
            ->whereNotNull('outro_music')
            ->where('outro_music', '!=', '')
            ->selectRaw('outro_music, count(*) as count')
            ->groupBy('outro_music')
            ->orderByDesc('count')
            ->pluck('count', 'outro_music')
            ->toArray();

        $normalizedIntro = [];
        foreach ($introMusicCounts as $track => $count) {
            $t = trim((string) $track);
            if ($t !== '') {
                $normalizedIntro[$t] = ($normalizedIntro[$t] ?? 0) + (int) $count;
            }
        }

        $normalizedOutro = [];
        foreach ($outroMusicCounts as $track => $count) {
            $t = trim((string) $track);
            if ($t !== '') {
                $normalizedOutro[$t] = ($normalizedOutro[$t] ?? 0) + (int) $count;
            }
        }

        $allUniqueTracks = array_values(array_unique(array_merge(array_keys($normalizedIntro), array_keys($normalizedOutro))));

        usort($allUniqueTracks, function (string $a, string $b) use ($normalizedIntro, $normalizedOutro): int {
            $totalA = ($normalizedIntro[$a] ?? 0) + ($normalizedOutro[$a] ?? 0);
            $totalB = ($normalizedIntro[$b] ?? 0) + ($normalizedOutro[$b] ?? 0);

            if ($totalA === $totalB) {
                return strcmp($a, $b);
            }

            return $totalB <=> $totalA;
        });

        $palette = [
            '#10b981', // emerald-500
            '#38bdf8', // sky-400
            '#f59e0b', // amber-500
            '#a855f7', // purple-500
            '#ec4899', // pink-500
            '#f97316', // orange-500
            '#06b6d4', // cyan-500
            '#84cc16', // lime-500
            '#6366f1', // indigo-500
            '#14b8a6', // teal-500
            '#f43f5e', // rose-500
            '#eab308', // yellow-500
            '#d946ef', // fuchsia-500
            '#3b82f6', // blue-500
            '#22c55e', // green-500
            '#8b5cf6', // violet-500
        ];

        $trackColorMap = [];
        foreach ($allUniqueTracks as $idx => $track) {
            $trackColorMap[$track] = $palette[$idx % count($palette)];
        }

        $totalIntro = array_sum($normalizedIntro);
        $totalOutro = array_sum($normalizedOutro);

        arsort($normalizedIntro);
        $introItems = [];
        foreach ($normalizedIntro as $track => $count) {
            $introItems[] = [
                'track' => $track,
                'count' => (int) $count,
                'percentage' => $totalIntro > 0 ? round(($count / $totalIntro) * 100, 1) : 0,
                'color' => $trackColorMap[$track] ?? '#94a3b8',
            ];
        }

        arsort($normalizedOutro);
        $outroItems = [];
        foreach ($normalizedOutro as $track => $count) {
            $outroItems[] = [
                'track' => $track,
                'count' => (int) $count,
                'percentage' => $totalOutro > 0 ? round(($count / $totalOutro) * 100, 1) : 0,
                'color' => $trackColorMap[$track] ?? '#94a3b8',
            ];
        }

        $legendItems = [];
        foreach ($allUniqueTracks as $track) {
            $inCnt = $normalizedIntro[$track] ?? 0;
            $outCnt = $normalizedOutro[$track] ?? 0;
            $legendItems[] = [
                'track' => $track,
                'color' => $trackColorMap[$track] ?? '#94a3b8',
                'intro_count' => $inCnt,
                'intro_percentage' => $totalIntro > 0 ? round(($inCnt / $totalIntro) * 100, 1) : 0,
                'outro_count' => $outCnt,
                'outro_percentage' => $totalOutro > 0 ? round(($outCnt / $totalOutro) * 100, 1) : 0,
                'total_count' => $inCnt + $outCnt,
            ];
        }

        return [
            'intro' => $introItems,
            'outro' => $outroItems,
            'legend' => $legendItems,
            'total_intro_tagged' => $totalIntro,
            'total_outro_tagged' => $totalOutro,
            'distinct_tracks' => count($allUniqueTracks),
        ];
    }
}
