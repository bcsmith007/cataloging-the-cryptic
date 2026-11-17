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
    public const DASHBOARD_CACHE_KEY = 'catalog_analytics_summary_v12';

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
            'total_videos' => Video::publiclyVisible()->count(),
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

        if (
            ! is_array($stats)
            || ! isset($stats['categoryMonthly'], $stats['constraintStats'], $stats['observations'], $stats['musicStats'], $stats['totalUniqueConstraints'], $stats['formatStats']['patreon_exclusive'], $stats['hostCumulativeMonthly'], $stats['puzzlesPerVideoStats']['total_videos'], $stats['hostComparison']['simon'], $stats['hostComparison']['mark'])
            || (isset($stats['hostCumulativeMonthly'][0]) && ! isset($stats['hostCumulativeMonthly'][0]['other']))
            || collect($stats['categoryStats'] ?? [])->contains('name', 'Unclassified')
        ) {
            $stats = $this->computeStats();
            Cache::put(self::DASHBOARD_CACHE_KEY, $stats, self::CACHE_TTL_SECONDS);
        }

        $stats['categoryMonthly'] = $stats['categoryMonthly'] ?? [];
        $stats['hostCumulativeMonthly'] = $stats['hostCumulativeMonthly'] ?? [];
        $stats['hostComparison'] = $stats['hostComparison'] ?? [
            'simon' => [],
            'mark' => [],
            'co_hosted_videos' => 0,
        ];
        $stats['puzzlesPerVideoStats'] = $stats['puzzlesPerVideoStats'] ?? [
            'slices' => [],
            'total_videos' => 0,
            'total_tagged_videos' => 0,
            'tagged_percentage' => 0.0,
            'untagged_videos' => 0,
            'multi_puzzle_videos' => 0,
            'multi_puzzle_percentage' => 0.0,
            'max_puzzles_in_video' => 0,
        ];
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
            ->publiclyVisible()
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

        $months = [];
        if ($earliestVideo && $latestVideo && $earliestVideo->published_at && $latestVideo->published_at) {
            $cursor = $earliestVideo->published_at->copy()->startOfMonth();
            $endMonth = $latestVideo->published_at->copy()->startOfMonth();
            while ($cursor->lte($endMonth)) {
                $months[] = $cursor->format('Y-m');
                $cursor->addMonth();
            }
        }

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

        // Monthly Trend (All-Time, for 24-Month Sliding Window)
        $monthlyGrouped = $publishedVideos->groupBy(fn (Video $v): string => $v->published_at->format('Y-m'))->sortKeys();

        $monthly = [];
        foreach ($months as $monthKey) {
            $key = (string) $monthKey;
            try {
                $label = Carbon::createFromFormat('!Y-m', $key)->format('M Y');
            } catch (\Throwable) {
                $label = $key;
            }

            /** @var Collection<int, Video> $group */
            $group = $monthlyGrouped->get($key, collect());
            $views = $group->pluck('view_count')->map(fn ($v): int => (int) $v)->all();
            $durs = $group->where('duration_seconds', '>', 0)->pluck('duration_seconds')->map(fn ($d): int => (int) $d)->all();
            $medViews = (int) round($this->calculateMedian($views));
            $medDurSec = (int) round($this->calculateMedian($durs));
            $medDurMin = round($medDurSec / 60, 1);

            $monthly[] = [
                'key' => $key,
                'label' => $label,
                'count' => $group->count(),
                'views' => (int) $group->sum('view_count'),
                'median_views' => $medViews,
                'median_duration_minutes' => $medDurMin,
                'avg_views' => $medViews,
                'avg_duration_minutes' => $medDurMin,
            ];
        }

        // -------------------------------------------------------------
        // Cumulative Host Activity (Simon, Mark & Other Hosts) Over Time
        // -------------------------------------------------------------
        $hostVideoRows = DB::table('video_hosts')
            ->join('videos', 'video_hosts.video_id', '=', 'videos.id')
            ->join('hosts', 'video_hosts.host_id', '=', 'hosts.id')
            ->where('videos.access_level', '!=', 'private')
            ->whereNotNull('videos.published_at')
            ->selectRaw("videos.id as video_id, videos.duration_seconds, {$monthExpr} as mo, hosts.slug, hosts.name")
            ->get();

        $simonMonthlyVideos = [];
        $markMonthlyVideos = [];
        $otherMonthlyVideos = [];

        foreach ($hostVideoRows as $row) {
            $slug = (string) $row->slug;
            $name = strtolower((string) $row->name);
            $mo = (string) $row->mo;
            $vidId = (int) $row->video_id;
            $duration = max(0, (int) $row->duration_seconds);

            $isSimon = in_array($slug, ['simon', 'simon-anthony'], true) || str_contains($name, 'simon');
            $isMark = in_array($slug, ['mark', 'mark-goodliffe'], true) || str_contains($name, 'mark');

            if ($isSimon) {
                $simonMonthlyVideos[$mo][$vidId] = $duration;
            }
            if ($isMark) {
                $markMonthlyVideos[$mo][$vidId] = $duration;
            }
            if (! $isSimon && ! $isMark) {
                $otherMonthlyVideos[$mo][$vidId] = $duration;
            }
        }

        $simonCumulativeCount = 0;
        $markCumulativeCount = 0;
        $otherCumulativeCount = 0;

        $simonCumulativeDuration = 0;
        $markCumulativeDuration = 0;
        $otherCumulativeDuration = 0;

        $hostCumulativeMonthly = [];

        foreach ($months as $mo) {
            $label = $mo;
            try {
                $label = Carbon::createFromFormat('!Y-m', $mo)->format('M Y');
            } catch (\Throwable) {
                $label = $mo;
            }

            $simonVids = $simonMonthlyVideos[$mo] ?? [];
            $markVids = $markMonthlyVideos[$mo] ?? [];
            $otherVids = $otherMonthlyVideos[$mo] ?? [];

            $simonMonthCount = count($simonVids);
            $markMonthCount = count($markVids);
            $otherMonthCount = count($otherVids);

            $simonMonthDuration = array_sum($simonVids);
            $markMonthDuration = array_sum($markVids);
            $otherMonthDuration = array_sum($otherVids);

            $simonCumulativeCount += $simonMonthCount;
            $markCumulativeCount += $markMonthCount;
            $otherCumulativeCount += $otherMonthCount;

            $simonCumulativeDuration += $simonMonthDuration;
            $markCumulativeDuration += $markMonthDuration;
            $otherCumulativeDuration += $otherMonthDuration;

            $hostCumulativeMonthly[] = [
                'month' => (string) $mo,
                'label' => $label,
                'simon' => $simonCumulativeCount,
                'mark' => $markCumulativeCount,
                'other' => $otherCumulativeCount,
                'simon_monthly' => $simonMonthCount,
                'mark_monthly' => $markMonthCount,
                'other_monthly' => $otherMonthCount,
                'simon_duration_seconds' => $simonCumulativeDuration,
                'mark_duration_seconds' => $markCumulativeDuration,
                'other_duration_seconds' => $otherCumulativeDuration,
                'simon_duration_hours' => round($simonCumulativeDuration / 3600, 1),
                'mark_duration_hours' => round($markCumulativeDuration / 3600, 1),
                'other_duration_hours' => round($otherCumulativeDuration / 3600, 1),
                'simon_duration_monthly_hours' => round($simonMonthDuration / 3600, 1),
                'mark_duration_monthly_hours' => round($markMonthDuration / 3600, 1),
                'other_duration_monthly_hours' => round($otherMonthDuration / 3600, 1),
            ];
        }

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
        $mostViewedStandard = Video::query()
            ->publiclyVisible()
            ->where('video_type', 'youtube')
            ->orderByDesc('view_count')
            ->first(['id', 'title', 'slug', 'youtube_video_id', 'view_count', 'published_at', 'duration_seconds', 'youtube_thumbnail_url']);

        $mostViewedShort = Video::query()
            ->publiclyVisible()
            ->where('video_type', 'youtube_short')
            ->orderByDesc('view_count')
            ->first(['id', 'title', 'slug', 'youtube_video_id', 'view_count', 'published_at', 'duration_seconds', 'youtube_thumbnail_url']);

        $longestSolve = Video::query()
            ->publiclyVisible()
            ->where('video_type', 'youtube')
            ->orderByDesc('duration_seconds')
            ->first(['id', 'title', 'slug', 'youtube_video_id', 'view_count', 'published_at', 'duration_seconds', 'youtube_thumbnail_url']);

        $shortestSolve = Video::query()
            ->publiclyVisible()
            ->where('video_type', 'youtube')
            ->where('duration_seconds', '>=', 60)
            ->orderBy('duration_seconds')
            ->first(['id', 'title', 'slug', 'youtube_video_id', 'view_count', 'published_at', 'duration_seconds', 'youtube_thumbnail_url']);

        // Month with peak uploads
        $peakUploadRow = Video::query()
            ->publiclyVisible()
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
            ->publiclyVisible()
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
            ->where('videos.access_level', '!=', 'private')
            ->select(['categories.id as category_id', 'categories.name as category_name', 'videos.id as video_id', 'videos.view_count'])
            ->get()
            ->groupBy('category_id');

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

        foreach ($categoryList as &$item) {
            $item['percentage'] = $totalClassifiedCount > 0
                ? round(($item['count'] / $totalClassifiedCount) * 100, 1)
                : 0;
        }
        unset($item);

        // Yearly Category Progression
        $classifiedByYearRaw = DB::table('video_puzzles')
            ->join('videos', 'video_puzzles.video_id', '=', 'videos.id')
            ->join('puzzles', 'video_puzzles.puzzle_id', '=', 'puzzles.id')
            ->join('categories', 'puzzles.category_id', '=', 'categories.id')
            ->where('videos.access_level', '!=', 'private')
            ->selectRaw("{$yearExpr} as yr, categories.name as category_name, count(*) as count")
            ->whereNotNull('videos.published_at')
            ->groupBy('yr', 'categories.name')
            ->get();

        $years = Video::query()
            ->publiclyVisible()
            ->selectRaw("{$yearExpr} as yr")
            ->whereNotNull('published_at')
            ->groupBy('yr')
            ->orderBy('yr')
            ->pluck('yr')
            ->all();

        $yearlyCategories = [];
        foreach ($years as $yr) {
            $catCounts = [];
            foreach ($categoryList as $catItem) {
                $matching = $classifiedByYearRaw->first(fn ($r): bool => $r->yr === $yr && $r->category_name === $catItem['name']);
                $catCounts[$catItem['name']] = $matching ? (int) $matching->count : 0;
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
            ->where('videos.access_level', '!=', 'private')
            ->selectRaw("{$monthExpr} as mo, categories.name as category_name, count(*) as count")
            ->whereNotNull('videos.published_at')
            ->groupBy('mo', 'categories.name')
            ->get();

        $classifiedMonthMap = [];
        foreach ($classifiedByMonthRaw as $row) {
            $classifiedMonthMap[$row->mo][$row->category_name] = (int) $row->count;
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

            foreach ($categoryList as $catItem) {
                $catCounts[$catItem['name']] = $classifiedMonthMap[$mo][$catItem['name']] ?? 0;
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
        $puzzlesPerVideoStats = $this->computePuzzlesPerVideoStats();
        $hostComparison = $this->computeHostComparisonStats();

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
            'hostCumulativeMonthly' => $hostCumulativeMonthly,
            'hostComparison' => $hostComparison,
            'formatStats' => $formatStats,
            'durationBrackets' => $durationBrackets,
            'dayOfWeek' => $dowData,
            'categoryStats' => $categoryList,
            'categoryYearly' => $yearlyCategories,
            'categoryMonthly' => $monthlyCategories,
            'constraintStats' => $constraintStats,
            'totalConstraintOccurrences' => $totalConstraintOccurrences,
            'totalUniqueConstraints' => $totalUniqueConstraints,
            'totalVariantPuzzles' => $totalVariantPuzzles,
            'musicStats' => $musicStats,
            'puzzlesPerVideoStats' => $puzzlesPerVideoStats,
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
            ->where('access_level', '!=', 'private')
            ->whereNotNull('intro_music')
            ->where('intro_music', '!=', '')
            ->selectRaw('intro_music, count(*) as count')
            ->groupBy('intro_music')
            ->orderByDesc('count')
            ->pluck('count', 'intro_music')
            ->toArray();

        $outroMusicCounts = DB::table('videos')
            ->where('access_level', '!=', 'private')
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

    /**
     * Compute distribution of how many puzzles are tagged into each publicly visible video.
     *
     * Includes videos with 0 tagged puzzles so tagging progress can be tracked.
     *
     * @return array{
     *     slices: array<int, array{puzzle_count: int, label: string, video_count: int, percentage: float}>,
     *     total_videos: int,
     *     total_tagged_videos: int,
     *     tagged_percentage: float,
     *     untagged_videos: int,
     *     multi_puzzle_videos: int,
     *     multi_puzzle_percentage: float,
     *     max_puzzles_in_video: int
     * }
     */
    public function computePuzzlesPerVideoStats(): array
    {
        $perVideoCounts = DB::table('videos')
            ->leftJoin('video_puzzles', 'videos.id', '=', 'video_puzzles.video_id')
            ->where('videos.access_level', '!=', 'private')
            ->selectRaw('videos.id as video_id, count(distinct video_puzzles.puzzle_id) as puzzle_count')
            ->groupBy('videos.id')
            ->get();

        $totalVideos = $perVideoCounts->count();
        $totalTaggedVideos = $perVideoCounts->filter(fn ($row): bool => (int) $row->puzzle_count >= 1)->count();
        $untaggedVideos = $totalVideos - $totalTaggedVideos;
        $multiPuzzleVideos = $perVideoCounts->filter(fn ($row): bool => (int) $row->puzzle_count > 1)->count();
        $maxPuzzlesInVideo = (int) ($perVideoCounts->max('puzzle_count') ?? 0);

        $grouped = $perVideoCounts
            ->groupBy(fn ($row): int => (int) $row->puzzle_count)
            ->sortKeys();

        $slices = [];
        foreach ($grouped as $puzzleCount => $rows) {
            $countInt = (int) $puzzleCount;
            $videoCount = $rows->count();

            $slices[] = [
                'puzzle_count' => $countInt,
                'label' => $countInt === 1 ? '1 Puzzle' : "{$countInt} Puzzles",
                'video_count' => $videoCount,
                'percentage' => $totalVideos > 0
                    ? round(($videoCount / $totalVideos) * 100, 1)
                    : 0.0,
            ];
        }

        return [
            'slices' => $slices,
            'total_videos' => $totalVideos,
            'total_tagged_videos' => $totalTaggedVideos,
            'tagged_percentage' => $totalVideos > 0
                ? round(($totalTaggedVideos / $totalVideos) * 100, 1)
                : 0.0,
            'untagged_videos' => $untaggedVideos,
            'multi_puzzle_videos' => $multiPuzzleVideos,
            'multi_puzzle_percentage' => $totalVideos > 0
                ? round(($multiPuzzleVideos / $totalVideos) * 100, 1)
                : 0.0,
            'max_puzzles_in_video' => $maxPuzzlesInVideo,
        ];
    }

    /**
     * Compute a head-to-head comparison of catalog videos involving Simon Anthony vs Mark Goodliffe.
     *
     * @return array{
     *     simon: array<string, mixed>,
     *     mark: array<string, mixed>,
     *     co_hosted_videos: int
     * }
     */
    public function computeHostComparisonStats(): array
    {
        $hosts = DB::table('hosts')->get(['id', 'name', 'slug']);

        $simonHostIds = [];
        $markHostIds = [];

        foreach ($hosts as $host) {
            $slug = (string) $host->slug;
            $name = strtolower((string) $host->name);

            if (in_array($slug, ['simon', 'simon-anthony'], true) || str_contains($name, 'simon')) {
                $simonHostIds[] = (int) $host->id;
            }
            if (in_array($slug, ['mark', 'mark-goodliffe'], true) || str_contains($name, 'mark')) {
                $markHostIds[] = (int) $host->id;
            }
        }

        $simonProfile = $this->buildHostComparisonProfile($simonHostIds, 'Simon Anthony', 'simon');
        $markProfile = $this->buildHostComparisonProfile($markHostIds, 'Mark Goodliffe', 'mark');

        $coHostedVideos = 0;
        if (! empty($simonHostIds) && ! empty($markHostIds)) {
            $coHostedVideos = (int) DB::table('videos')
                ->where('videos.access_level', '!=', 'private')
                ->whereExists(function ($query) use ($simonHostIds): void {
                    $query->select(DB::raw(1))
                        ->from('video_hosts')
                        ->whereColumn('video_hosts.video_id', 'videos.id')
                        ->whereIn('video_hosts.host_id', $simonHostIds);
                })
                ->whereExists(function ($query) use ($markHostIds): void {
                    $query->select(DB::raw(1))
                        ->from('video_hosts')
                        ->whereColumn('video_hosts.video_id', 'videos.id')
                        ->whereIn('video_hosts.host_id', $markHostIds);
                })
                ->count();
        }

        $simonProfile['co_hosted_video_count'] = $coHostedVideos;
        $simonProfile['solo_video_count'] = max(0, (int) $simonProfile['video_count'] - $coHostedVideos);

        $markProfile['co_hosted_video_count'] = $coHostedVideos;
        $markProfile['solo_video_count'] = max(0, (int) $markProfile['video_count'] - $coHostedVideos);

        return [
            'simon' => $simonProfile,
            'mark' => $markProfile,
            'co_hosted_videos' => $coHostedVideos,
        ];
    }

    /**
     * Build detailed comparison statistics for a single host based on their host IDs.
     *
     * @param  array<int, int>  $hostIds
     * @return array<string, mixed>
     */
    protected function buildHostComparisonProfile(array $hostIds, string $displayName, string $filterKey): array
    {
        if (empty($hostIds)) {
            return [
                'name' => $displayName,
                'filter_key' => $filterKey,
                'video_count' => 0,
                'solo_video_count' => 0,
                'co_hosted_video_count' => 0,
                'puzzle_count' => 0,
                'tagged_video_count' => 0,
                'total_duration_seconds' => 0,
                'total_duration_formatted' => '0s',
                'total_hours' => 0.0,
                'median_duration_seconds' => 0,
                'median_duration_formatted' => '0s',
                'avg_duration_seconds' => 0,
                'avg_duration_formatted' => '0s',
                'total_views' => 0,
                'median_views' => 0,
                'top_category' => null,
                'top_constraint' => null,
                'top_setter' => null,
                'top_music' => null,
                'top_intro_music' => null,
                'top_outro_music' => null,
                'longest_video' => null,
                'most_viewed_video' => null,
            ];
        }

        $videos = Video::query()
            ->publiclyVisible()
            ->whereExists(function ($query) use ($hostIds): void {
                $query->select(DB::raw(1))
                    ->from('video_hosts')
                    ->whereColumn('video_hosts.video_id', 'videos.id')
                    ->whereIn('video_hosts.host_id', $hostIds);
            })
            ->get([
                'id',
                'title',
                'slug',
                'youtube_video_id',
                'video_type',
                'published_at',
                'duration_seconds',
                'view_count',
                'intro_music',
                'outro_music',
                'youtube_thumbnail_url',
            ]);

        $videoCount = $videos->count();
        $totalDurationSeconds = (int) $videos->sum('duration_seconds');
        $positiveDurations = $videos->where('duration_seconds', '>', 0)
            ->pluck('duration_seconds')
            ->map(fn ($d): int => (int) $d)
            ->all();

        $medianDurationSeconds = (int) round($this->calculateMedian($positiveDurations));
        $avgDurationSeconds = count($positiveDurations) > 0
            ? (int) round(array_sum($positiveDurations) / count($positiveDurations))
            : 0;

        $totalViews = (int) $videos->sum('view_count');
        $viewsList = $videos->pluck('view_count')->map(fn ($v): int => (int) $v)->all();
        $medianViews = (int) round($this->calculateMedian($viewsList));

        $longestSoloVideo = Video::query()
            ->publiclyVisible()
            ->whereExists(function ($query) use ($hostIds): void {
                $query->select(DB::raw(1))
                    ->from('video_hosts')
                    ->whereColumn('video_hosts.video_id', 'videos.id')
                    ->whereIn('video_hosts.host_id', $hostIds);
            })
            ->whereNotExists(function ($query) use ($hostIds): void {
                $query->select(DB::raw(1))
                    ->from('video_hosts')
                    ->whereColumn('video_hosts.video_id', 'videos.id')
                    ->whereNotIn('video_hosts.host_id', $hostIds);
            })
            ->orderByDesc('duration_seconds')
            ->first([
                'id',
                'title',
                'slug',
                'youtube_video_id',
                'video_type',
                'published_at',
                'duration_seconds',
                'view_count',
                'youtube_thumbnail_url',
            ]);

        $longestVideo = $this->serializeVideoRecord($longestSoloVideo);
        $mostViewedVideo = $this->serializeVideoRecord($videos->sortByDesc('view_count')->first());

        // Intro, outro, and combined music counts
        $introCounts = [];
        $outroCounts = [];
        $combinedMusicCounts = [];

        foreach ($videos as $video) {
            $intro = trim((string) ($video->intro_music ?? ''));
            if ($intro !== '') {
                $introCounts[$intro] = ($introCounts[$intro] ?? 0) + 1;
                $combinedMusicCounts[$intro] = ($combinedMusicCounts[$intro] ?? 0) + 1;
            }

            $outro = trim((string) ($video->outro_music ?? ''));
            if ($outro !== '') {
                $outroCounts[$outro] = ($outroCounts[$outro] ?? 0) + 1;
                $combinedMusicCounts[$outro] = ($combinedMusicCounts[$outro] ?? 0) + 1;
            }
        }

        $topIntroMusic = $this->extractTopMusicTrack($introCounts);
        $topOutroMusic = $this->extractTopMusicTrack($outroCounts);
        $topMusic = $this->extractTopMusicTrack($combinedMusicCounts);

        // Puzzle count and tagged videos count
        $puzzleSummary = DB::table('video_puzzles')
            ->join('videos', 'video_puzzles.video_id', '=', 'videos.id')
            ->where('videos.access_level', '!=', 'private')
            ->whereExists(function ($query) use ($hostIds): void {
                $query->select(DB::raw(1))
                    ->from('video_hosts')
                    ->whereColumn('video_hosts.video_id', 'videos.id')
                    ->whereIn('video_hosts.host_id', $hostIds);
            })
            ->selectRaw('count(distinct video_puzzles.puzzle_id) as puzzle_count, count(distinct video_puzzles.video_id) as tagged_video_count')
            ->first();

        $puzzleCount = (int) ($puzzleSummary->puzzle_count ?? 0);
        $taggedVideoCount = (int) ($puzzleSummary->tagged_video_count ?? 0);

        // Most common puzzle type (category)
        $topCategoryRow = DB::table('video_puzzles')
            ->join('videos', 'video_puzzles.video_id', '=', 'videos.id')
            ->join('puzzles', 'video_puzzles.puzzle_id', '=', 'puzzles.id')
            ->join('categories', 'puzzles.category_id', '=', 'categories.id')
            ->where('videos.access_level', '!=', 'private')
            ->whereExists(function ($query) use ($hostIds): void {
                $query->select(DB::raw(1))
                    ->from('video_hosts')
                    ->whereColumn('video_hosts.video_id', 'videos.id')
                    ->whereIn('video_hosts.host_id', $hostIds);
            })
            ->selectRaw('categories.name, categories.slug, count(distinct puzzles.id) as puzzle_count')
            ->groupBy('categories.id', 'categories.name', 'categories.slug')
            ->orderByDesc('puzzle_count')
            ->orderBy('categories.name')
            ->first();

        $topCategory = $topCategoryRow ? [
            'name' => (string) $topCategoryRow->name,
            'slug' => (string) $topCategoryRow->slug,
            'count' => (int) $topCategoryRow->puzzle_count,
            'percentage' => $puzzleCount > 0
                ? round(((int) $topCategoryRow->puzzle_count / $puzzleCount) * 100, 1)
                : 0.0,
        ] : null;

        // Most common constraint
        $topConstraintRow = DB::table('video_puzzles')
            ->join('videos', 'video_puzzles.video_id', '=', 'videos.id')
            ->join('puzzles', 'video_puzzles.puzzle_id', '=', 'puzzles.id')
            ->join('puzzle_constraints', 'puzzles.id', '=', 'puzzle_constraints.puzzle_id')
            ->join('constraints', 'puzzle_constraints.constraint_id', '=', 'constraints.id')
            ->where('videos.access_level', '!=', 'private')
            ->whereExists(function ($query) use ($hostIds): void {
                $query->select(DB::raw(1))
                    ->from('video_hosts')
                    ->whereColumn('video_hosts.video_id', 'videos.id')
                    ->whereIn('video_hosts.host_id', $hostIds);
            })
            ->selectRaw('constraints.name, constraints.slug, count(distinct puzzles.id) as puzzle_count')
            ->groupBy('constraints.id', 'constraints.name', 'constraints.slug')
            ->orderByDesc('puzzle_count')
            ->orderBy('constraints.name')
            ->first();

        $topConstraint = $topConstraintRow ? [
            'name' => (string) $topConstraintRow->name,
            'slug' => (string) $topConstraintRow->slug,
            'url' => route('constraints.show', (string) $topConstraintRow->slug),
            'count' => (int) $topConstraintRow->puzzle_count,
            'percentage' => $puzzleCount > 0
                ? round(((int) $topConstraintRow->puzzle_count / $puzzleCount) * 100, 1)
                : 0.0,
        ] : null;

        // Most featured setter
        $topSetterRow = DB::table('video_puzzles')
            ->join('videos', 'video_puzzles.video_id', '=', 'videos.id')
            ->join('puzzles', 'video_puzzles.puzzle_id', '=', 'puzzles.id')
            ->join('puzzle_setters', 'puzzles.id', '=', 'puzzle_setters.puzzle_id')
            ->join('setters', 'puzzle_setters.setter_id', '=', 'setters.id')
            ->where('videos.access_level', '!=', 'private')
            ->whereExists(function ($query) use ($hostIds): void {
                $query->select(DB::raw(1))
                    ->from('video_hosts')
                    ->whereColumn('video_hosts.video_id', 'videos.id')
                    ->whereIn('video_hosts.host_id', $hostIds);
            })
            ->selectRaw('setters.name, setters.slug, count(distinct puzzles.id) as puzzle_count')
            ->groupBy('setters.id', 'setters.name', 'setters.slug')
            ->orderByDesc('puzzle_count')
            ->orderBy('setters.name')
            ->first();

        $topSetter = $topSetterRow ? [
            'name' => (string) $topSetterRow->name,
            'slug' => (string) $topSetterRow->slug,
            'url' => route('setters.show', (string) $topSetterRow->slug),
            'count' => (int) $topSetterRow->puzzle_count,
        ] : null;

        return [
            'name' => $displayName,
            'filter_key' => $filterKey,
            'video_count' => $videoCount,
            'solo_video_count' => $videoCount,
            'co_hosted_video_count' => 0,
            'puzzle_count' => $puzzleCount,
            'tagged_video_count' => $taggedVideoCount,
            'total_duration_seconds' => $totalDurationSeconds,
            'total_duration_formatted' => $this->formatDurationSeconds($totalDurationSeconds),
            'total_hours' => round($totalDurationSeconds / 3600, 1),
            'median_duration_seconds' => $medianDurationSeconds,
            'median_duration_formatted' => $this->formatDurationSeconds($medianDurationSeconds),
            'avg_duration_seconds' => $avgDurationSeconds,
            'avg_duration_formatted' => $this->formatDurationSeconds($avgDurationSeconds),
            'total_views' => $totalViews,
            'median_views' => $medianViews,
            'top_category' => $topCategory,
            'top_constraint' => $topConstraint,
            'top_setter' => $topSetter,
            'top_music' => $topMusic,
            'top_intro_music' => $topIntroMusic,
            'top_outro_music' => $topOutroMusic,
            'longest_video' => $longestVideo,
            'most_viewed_video' => $mostViewedVideo,
        ];
    }

    /**
     * Extract the most frequent music track from a track-to-count map.
     *
     * @param  array<string, int>  $counts
     * @return array{track: string, count: int}|null
     */
    protected function extractTopMusicTrack(array $counts): ?array
    {
        if (empty($counts)) {
            return null;
        }

        uksort($counts, function (string $a, string $b) use ($counts): int {
            if ($counts[$a] === $counts[$b]) {
                return strcmp($a, $b);
            }

            return $counts[$b] <=> $counts[$a];
        });

        $topTrack = array_key_first($counts);
        if ($topTrack === null) {
            return null;
        }

        return [
            'track' => (string) $topTrack,
            'count' => (int) $counts[$topTrack],
        ];
    }
}
