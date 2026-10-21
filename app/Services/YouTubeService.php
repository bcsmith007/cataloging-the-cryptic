<?php

namespace App\Services;

use App\Models\Video;
use Carbon\CarbonInterface;
use DateInterval;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Service for querying video metadata from YouTube.
 *
 * Supports Google YouTube Data API v3 when configured with an API key,
 * and falls back to YouTube oEmbed for unauthenticated title/thumbnail fetching.
 */
class YouTubeService
{
    /**
     * Extract an 11-character YouTube video ID from a raw ID or standard/short URL.
     */
    public static function extractVideoId(string $input): string
    {
        $trimmed = trim($input);

        if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?|shorts|live)/|.*[?&]v=)|youtu\.be/)([^"&?/\s]{11})%i', $trimmed, $matches)) {
            return $matches[1];
        }

        return $trimmed;
    }

    /**
     * Detect likely video type based on the input URL structure, API snippet data, and duration.
     *
     * @param  array<string, mixed>  $snippet
     */
    public static function detectVideoType(string $input, array $snippet = [], int $durationSeconds = 0): ?string
    {
        if (preg_match('%youtube(?:-nocookie)?\.com/live/%i', $input)) {
            return 'live_stream';
        }

        if (preg_match('%youtube(?:-nocookie)?\.com/shorts/%i', $input)) {
            return 'youtube_short';
        }

        $broadcast = $snippet['liveBroadcastContent'] ?? null;
        if (in_array($broadcast, ['live', 'completed', 'upcoming'], true)) {
            return 'live_stream';
        }

        $title = (string) ($snippet['title'] ?? '');
        if (stripos($title, '#shorts') !== false || ($durationSeconds > 0 && $durationSeconds <= 60)) {
            return 'youtube_short';
        }

        return null;
    }

    /**
     * Parse an ISO 8601 duration string (e.g., PT1H23M45S) into total seconds.
     */
    public static function parseDuration(string $duration): int
    {
        try {
            $interval = new DateInterval($duration);

            return ($interval->d * 86400) + ($interval->h * 3600) + ($interval->i * 60) + $interval->s;
        } catch (Exception) {
            return 0;
        }
    }

    /**
     * Fetch video metadata for a given video ID or YouTube URL.
     *
     * @param  string  $input  Video ID or URL.
     * @return array<string, mixed>|null
     */
    public function getVideoDetails(string $input): ?array
    {
        $videoId = self::extractVideoId($input);

        if (empty($videoId)) {
            return null;
        }

        $apiKey = config('services.youtube.key');

        $result = null;
        if ($apiKey) {
            $result = $this->fetchFromDataApi($videoId, $apiKey, $input) ?? $this->fetchFromOembed($videoId, $input);
        } else {
            $result = $this->fetchFromOembed($videoId, $input);
        }

        if ($result && empty($result['detected_video_type'])) {
            $result['detected_video_type'] = self::detectVideoType($input);
        }

        return $result;
    }

    /**
     * Fetch comprehensive metadata from YouTube Data API v3.
     *
     * @return array<string, mixed>|null
     */
    protected function fetchFromDataApi(string $videoId, string $apiKey, string $originalInput = ''): ?array
    {
        try {
            $response = Http::get('https://www.googleapis.com/youtube/v3/videos', [
                'part' => 'snippet,contentDetails,statistics',
                'id' => $videoId,
                'key' => $apiKey,
            ]);

            if (! $response->successful()) {
                return null;
            }

            $items = $response->json('items', []);

            if (empty($items)) {
                return null;
            }

            $item = $items[0];
            $snippet = $item['snippet'] ?? [];
            $contentDetails = $item['contentDetails'] ?? [];
            $statistics = $item['statistics'] ?? [];

            $title = (string) ($snippet['title'] ?? '');
            $publishedAt = isset($snippet['publishedAt'])
                ? Carbon::parse($snippet['publishedAt'])->toDateTimeString()
                : null;

            $durationIso = (string) ($contentDetails['duration'] ?? '');
            $durationSeconds = self::parseDuration($durationIso);
            $viewCount = (int) ($statistics['viewCount'] ?? 0);

            $rawThumbnail = $snippet['thumbnails']['maxres']['url']
                ?? $snippet['thumbnails']['high']['url']
                ?? $snippet['thumbnails']['medium']['url']
                ?? "https://i.ytimg.com/vi/{$videoId}/hqdefault.jpg";
            $thumbnail = Video::convertToWebpThumbnailUrl($rawThumbnail);

            $detectedType = self::detectVideoType($originalInput, $snippet);

            return [
                'video_id' => $videoId,
                'title' => $title,
                'slug' => Str::slug($title),
                'published_at' => $publishedAt,
                'duration_seconds' => $durationSeconds,
                'view_count' => $viewCount,
                'youtube_thumbnail_url' => $thumbnail,
                'detected_video_type' => $detectedType,
                'source' => 'api',
            ];
        } catch (Exception) {
            return null;
        }
    }

    /**
     * Fallback: Fetch basic video information via public YouTube oEmbed without an API key.
     *
     * @return array<string, mixed>|null
     */
    protected function fetchFromOembed(string $videoId, string $originalInput = ''): ?array
    {
        try {
            $url = "https://www.youtube.com/watch?v={$videoId}";
            $response = Http::get('https://www.youtube.com/oembed', [
                'url' => $url,
                'format' => 'json',
            ]);

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json();
            $title = (string) ($data['title'] ?? '');

            $rawThumbnail = (string) ($data['thumbnail_url'] ?? "https://i.ytimg.com/vi/{$videoId}/hqdefault.jpg");
            $thumbnail = Video::convertToWebpThumbnailUrl($rawThumbnail);

            return [
                'video_id' => $videoId,
                'title' => $title,
                'slug' => Str::slug($title),
                'published_at' => now()->toDateTimeString(),
                'duration_seconds' => 0,
                'view_count' => 0,
                'youtube_thumbnail_url' => $thumbnail,
                'detected_video_type' => self::detectVideoType($originalInput),
                'source' => 'oembed',
            ];
        } catch (Exception) {
            return null;
        }
    }

    /**
     * Fetch a page of video items from a YouTube playlist.
     *
     * @return array{items: array<int, array<string, mixed>>, nextPageToken: string|null, totalResults: int}|null
     */
    public function getPlaylistVideos(string $playlistId, ?string $pageToken = null, int $maxResults = 50): ?array
    {
        $apiKey = config('services.youtube.key');

        if (empty($apiKey)) {
            return null;
        }

        try {
            $params = [
                'part' => 'snippet,contentDetails',
                'playlistId' => $playlistId,
                'maxResults' => min(max($maxResults, 1), 50),
                'key' => $apiKey,
            ];

            if ($pageToken) {
                $params['pageToken'] = $pageToken;
            }

            $response = Http::get('https://www.googleapis.com/youtube/v3/playlistItems', $params);

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json();

            return [
                'items' => (array) ($data['items'] ?? []),
                'nextPageToken' => $data['nextPageToken'] ?? null,
                'totalResults' => (int) ($data['pageInfo']['totalResults'] ?? 0),
            ];
        } catch (Exception) {
            return null;
        }
    }

    /**
     * Fetch video details for a batch of YouTube video IDs (up to 50).
     *
     * @param  array<int, string>  $videoIds
     * @return array<string, array<string, mixed>> Keyed by video ID.
     */
    public function getBatchVideoDetails(array $videoIds): array
    {
        $apiKey = config('services.youtube.key');

        if (empty($apiKey) || empty($videoIds)) {
            return [];
        }

        $idsToQuery = array_values(array_slice(array_unique($videoIds), 0, 50));

        try {
            $response = Http::get('https://www.googleapis.com/youtube/v3/videos', [
                'part' => 'snippet,contentDetails,statistics',
                'id' => implode(',', $idsToQuery),
                'key' => $apiKey,
            ]);

            if (! $response->successful()) {
                return [];
            }

            $results = [];

            foreach ($response->json('items', []) as $item) {
                $videoId = (string) ($item['id'] ?? '');
                if (empty($videoId)) {
                    continue;
                }

                $snippet = $item['snippet'] ?? [];
                $contentDetails = $item['contentDetails'] ?? [];
                $statistics = $item['statistics'] ?? [];

                $rawTitle = (string) ($snippet['title'] ?? '');
                $title = html_entity_decode($rawTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8');

                $publishedAt = isset($snippet['publishedAt'])
                    ? Carbon::parse($snippet['publishedAt'])->toDateTimeString()
                    : null;

                $durationIso = (string) ($contentDetails['duration'] ?? '');
                $durationSeconds = self::parseDuration($durationIso);
                $viewCount = (int) ($statistics['viewCount'] ?? 0);

                $rawThumbnail = $snippet['thumbnails']['maxres']['url']
                    ?? $snippet['thumbnails']['standard']['url']
                    ?? $snippet['thumbnails']['high']['url']
                    ?? $snippet['thumbnails']['medium']['url']
                    ?? "https://i.ytimg.com/vi/{$videoId}/hqdefault.jpg";
                $thumbnail = Video::convertToWebpThumbnailUrl($rawThumbnail);

                $detectedType = self::detectVideoType('', $snippet, $durationSeconds) ?? 'youtube';

                $results[$videoId] = [
                    'video_id' => $videoId,
                    'title' => $title,
                    'published_at' => $publishedAt,
                    'duration_seconds' => $durationSeconds,
                    'view_count' => $viewCount,
                    'youtube_thumbnail_url' => $thumbnail,
                    'video_type' => $detectedType,
                ];
            }

            return $results;
        } catch (Exception) {
            return [];
        }
    }

    /**
     * Resolve a Carbon UTC instance from various input formats.
     */
    public function resolveUtcCarbon(CarbonInterface|string|null $now = null): Carbon
    {
        if ($now instanceof Carbon) {
            return $now->copy()->utc();
        }

        if ($now instanceof CarbonInterface) {
            return Carbon::instance($now)->utc();
        }

        if (is_string($now) && trim($now) !== '') {
            $lower = strtolower(trim($now));
            if ($lower === 'midnight') {
                return Carbon::now('UTC')->startOfDay();
            }
            if ($lower === 'noon') {
                return Carbon::now('UTC')->startOfDay()->addHours(12);
            }

            return Carbon::parse($now, 'UTC')->utc();
        }

        return Carbon::now('UTC');
    }

    /**
     * Determine if a video is due for a view count update based on release age and reference time.
     *
     * Schedule Cadence:
     * - Last 48 hours: updated each time (every 30-minute run).
     * - 2 - 7 days: updated twice a day (at midnight and noon UTC).
     * - 7 - 14 days: updated once per day (at midnight UTC).
     * - 14 - 31 days: updated once a week (at midnight on days 14, 21, and 28 after release).
     * - More than 31 days: updated once a month (at midnight on monthly anniversary; on the last day of shorter months, days >= current day).
     */
    public function isVideoDueForViewUpdate(
        Video $video,
        CarbonInterface|string|null $now = null,
        bool $force = false,
        bool $respectAlreadySynced = true
    ): bool {
        if ($force) {
            return true;
        }

        if (! $video->published_at) {
            return false;
        }

        $nowUtc = $this->resolveUtcCarbon($now);
        $publishedAt = Carbon::parse($video->published_at)->utc();

        // Allow slight clock skew in case YouTube published timestamp is slightly ahead of server
        if ($publishedAt->isAfter($nowUtc->copy()->addHour())) {
            return false;
        }

        $diffInHours = $publishedAt->diffInHours($nowUtc, false);

        // Tier 1: Last 48 hours - updated each time (every 30-minute run)
        if ($diffInHours >= -1 && $diffInHours <= 48) {
            return true;
        }

        $isMidnight = ($nowUtc->hour === 0 && $nowUtc->minute < 30);
        $isNoon = ($nowUtc->hour === 12 && $nowUtc->minute < 30);

        // Difference in calendar days in UTC
        $daysSincePublished = (int) $publishedAt->copy()->startOfDay()->diffInDays($nowUtc->copy()->startOfDay());

        // Tier 2: 2 - 7 days - updated at midnight and noon (twice a day)
        if ($diffInHours >= 48 && $daysSincePublished < 7) {
            if (! ($isMidnight || $isNoon)) {
                return false;
            }

            if ($respectAlreadySynced && $video->stats_synced_at) {
                $syncedAt = Carbon::parse($video->stats_synced_at)->utc();
                if ($syncedAt->diffInHours($nowUtc) < 6) {
                    return false;
                }
            }

            return true;
        }

        // All subsequent tiers (Tiers 3, 4, 5) run at midnight only
        if (! $isMidnight) {
            return false;
        }

        // Avoid duplicate runs within the same day
        if ($respectAlreadySynced && $video->stats_synced_at) {
            $syncedAt = Carbon::parse($video->stats_synced_at)->utc();
            if ($syncedAt->isSameDay($nowUtc)) {
                return false;
            }
        }

        // Tier 3: 7 - 14 days - updated once per day (at midnight)
        if ($daysSincePublished >= 7 && $daysSincePublished < 14) {
            return true;
        }

        // Tier 4: 14 - 31 days - updated once a week (at midnight on days 14, 21, and 28 after release)
        if (in_array($daysSincePublished, [14, 21, 28], true)) {
            return true;
        }

        // Tier 5: Released more than 31 days ago (or past 28-day weekly cycle) - updated once a month
        if ($daysSincePublished >= 29) {
            $releaseDay = (int) $publishedAt->day;
            $currentDay = (int) $nowUtc->day;
            $daysInCurrentMonth = (int) $nowUtc->daysInMonth;
            $isLastDayOfMonth = ($currentDay === $daysInCurrentMonth);

            if ($isLastDayOfMonth && $daysInCurrentMonth < 31) {
                return $releaseDay >= $currentDay;
            }

            return $releaseDay === $currentDay;
        }

        return false;
    }

    /**
     * Retrieve all videos from the catalog that are due for view count update at the reference time.
     *
     * @return Collection<int, Video>
     */
    public function getVideosDueForViewUpdate(
        CarbonInterface|string|null $now = null,
        bool $force = false,
        bool $respectAlreadySynced = true
    ): Collection {
        if ($force) {
            return Video::whereNotNull('youtube_video_id')->get();
        }

        $nowUtc = $this->resolveUtcCarbon($now);
        $isMidnight = ($nowUtc->hour === 0 && $nowUtc->minute < 30);
        $isNoon = ($nowUtc->hour === 12 && $nowUtc->minute < 30);

        $query = Video::whereNotNull('youtube_video_id')
            ->whereNotNull('published_at');

        if (! $isMidnight && ! $isNoon) {
            // Only Tier 1 (< 48 hours) can be due on off-hours runs
            $query->where('published_at', '>=', $nowUtc->copy()->subHours(50));
        } elseif ($isNoon && ! $isMidnight) {
            // Tier 1 and Tier 2 (<= 7 days) can be due at noon
            $query->where('published_at', '>=', $nowUtc->copy()->subDays(8));
        }

        $candidates = $query->get();

        return $candidates->filter(function (Video $video) use ($nowUtc, $respectAlreadySynced) {
            return $this->isVideoDueForViewUpdate($video, $nowUtc, false, $respectAlreadySynced);
        })->values();
    }
}
