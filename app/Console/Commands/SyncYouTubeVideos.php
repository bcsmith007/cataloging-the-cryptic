<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Video;
use App\Models\YouTubeSyncLog;
use App\Services\YouTubeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Artisan command to synchronize public videos from the YouTube uploads playlist into the database.
 *
 * Grabs video records and core metadata (title, runtime, publication date, view count, thumbnail,
 * video type) without touching descriptions or attempting puzzle/bookmark linking.
 */
class SyncYouTubeVideos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ctc:sync-youtube
                            {--all : Sync all historical public videos from the channel uploads playlist}
                            {--limit= : Maximum number of videos to process from playlist}
                            {--playlist= : Optional custom YouTube playlist ID}
                            {--dry-run : Preview actions without writing to the database}
                            {--force : Force sync regardless of cadence or consecutive existing videos}
                            {--all-stats : Update view counts for all videos in database regardless of schedule}
                            {--at= : Reference timestamp (e.g. "2026-10-15 00:00:00", "midnight", "noon") for schedule evaluation}
                            {--skip-new : Skip checking playlist for new videos}
                            {--skip-stats : Skip tiered view count updates}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync public YouTube videos and metadata from the channel uploads playlist into the database';

    /**
     * Execute the console command.
     */
    public function handle(YouTubeService $youTubeService): int
    {
        $startTime = microtime(true);
        $startedAt = now();
        $activityDetails = [];

        $apiKey = config('services.youtube.key');
        if (empty($apiKey)) {
            $msg = 'YouTube API key is not configured in services.youtube.key or YOUTUBE_API_KEY environment variable.';
            $this->error($msg);
            Log::error("YouTube Sync: {$msg}");

            try {
                YouTubeSyncLog::create([
                    'status' => 'failed',
                    'started_at' => $startedAt,
                    'finished_at' => now(),
                    'duration_ms' => (int) round((microtime(true) - $startTime) * 1000),
                    'new_videos_count' => 0,
                    'updated_views_count' => 0,
                    'total_processed' => 0,
                    'message' => $msg,
                    'details' => [['type' => 'error', 'message' => $msg]],
                ]);
            } catch (\Throwable) {
                // Ignore DB logging failure during test setups without migrated tables
            }

            return self::FAILURE;
        }

        $playlistId = (string) ($this->option('playlist')
            ?: config('services.youtube.uploads_playlist_id', 'UUC-UOdK8-mIjxBQm_ot1T-Q'));

        $syncAll = (bool) $this->option('all');
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $allStats = (bool) $this->option('all-stats');
        $skipNew = (bool) $this->option('skip-new');
        $skipStats = (bool) $this->option('skip-stats');
        $atOption = $this->option('at');
        $now = $youTubeService->resolveUtcCarbon($atOption);

        $limitOption = $this->option('limit');
        $limit = $limitOption !== null ? (int) $limitOption : ($syncAll ? null : 50);

        $mode = $dryRun ? 'dry-run' : ($syncAll ? 'full-sync' : 'scheduled');
        Log::info("YouTube Sync [{$mode}]: Starting synchronization for playlist {$playlistId} (reference time: {$now->toDateTimeString()} UTC)");

        $this->info("Starting YouTube sync for playlist: {$playlistId}");
        if ($dryRun) {
            $this->warn('Running in DRY-RUN mode. No database records will be created or modified.');
        }

        $totalProcessed = 0;
        $totalCreated = 0;
        $totalUpdated = 0;
        $totalViewsAdded = 0;
        $consecutiveExisting = 0;
        $stopEarlyThreshold = 10;
        $systemUser = ! $dryRun ? User::getSystemUser() : null;

        // -------------------------------------------------------------
        // Step 1: Check for new videos and add them to the catalog
        // -------------------------------------------------------------
        if (! $skipNew) {
            $pageToken = null;

            do {
                $fetchLimit = 50;
                if ($limit !== null) {
                    $remaining = $limit - $totalProcessed;
                    if ($remaining <= 0) {
                        break;
                    }
                    $fetchLimit = min(50, $remaining);
                }

                $playlistData = $youTubeService->getPlaylistVideos($playlistId, $pageToken, $fetchLimit);

                if ($playlistData === null) {
                    $msg = 'Failed to retrieve items from YouTube playlist API.';
                    $this->error($msg);
                    Log::error("YouTube Sync: {$msg}");

                    try {
                        YouTubeSyncLog::create([
                            'status' => 'failed',
                            'started_at' => $startedAt,
                            'finished_at' => now(),
                            'duration_ms' => (int) round((microtime(true) - $startTime) * 1000),
                            'new_videos_count' => $totalCreated,
                            'updated_views_count' => $totalUpdated,
                            'total_views_added' => $totalViewsAdded,
                            'total_processed' => $totalProcessed,
                            'message' => $msg,
                            'details' => array_merge($activityDetails, [['type' => 'error', 'message' => $msg]]),
                        ]);
                    } catch (\Throwable) {
                        // ignore
                    }

                    return self::FAILURE;
                }

                $items = $playlistData['items'];
                if (empty($items)) {
                    break;
                }

                $videoIds = [];
                foreach ($items as $item) {
                    $vid = (string) ($item['contentDetails']['videoId']
                        ?? $item['snippet']['resourceId']['videoId']
                        ?? '');

                    $title = (string) ($item['snippet']['title'] ?? '');
                    if ($vid !== '' && $title !== 'Private video' && $title !== 'Deleted video') {
                        $videoIds[] = $vid;
                    }
                }

                if (empty($videoIds)) {
                    $pageToken = $playlistData['nextPageToken'];

                    continue;
                }

                $details = $youTubeService->getBatchVideoDetails($videoIds);
                $existingVideos = Video::whereIn('youtube_video_id', array_keys($details))
                    ->get()
                    ->keyBy('youtube_video_id');

                foreach ($details as $videoId => $videoData) {
                    if ($limit !== null && $totalProcessed >= $limit) {
                        break 2;
                    }

                    $totalProcessed++;
                    $title = $videoData['title'];

                    /** @var Video|null $existing */
                    $existing = $existingVideos->get($videoId);

                    if ($existing) {
                        $consecutiveExisting++;

                        if ($syncAll || $force) {
                            $totalUpdated++;
                            $oldViews = (int) $existing->view_count;
                            $newViews = (int) $videoData['view_count'];
                            $diff = $newViews - $oldViews;
                            $totalViewsAdded += $diff;
                            $diffFormatted = ($diff >= 0 ? "+{$diff}" : (string) $diff);

                            $activityDetails[] = [
                                'type' => 'view_update',
                                'video_id' => $videoId,
                                'title' => $title,
                                'old_views' => $oldViews,
                                'new_views' => $newViews,
                                'diff' => $diff,
                                'source' => 'playlist',
                            ];

                            Log::info("YouTube Sync: Updated view count for '{$title}' ({$videoId}) [{$oldViews} -> {$newViews} ({$diffFormatted})]");

                            if ($dryRun) {
                                $this->line("[DRY-RUN] Would update existing video: {$title} ({$videoId})");
                            } else {
                                $existing->view_count = $videoData['view_count'];
                                $existing->stats_synced_at = now();

                                if ($existing->duration_seconds === 0 && $videoData['duration_seconds'] > 0) {
                                    $existing->duration_seconds = $videoData['duration_seconds'];
                                }

                                if (empty($existing->youtube_thumbnail_url) && ! empty($videoData['youtube_thumbnail_url'])) {
                                    $existing->youtube_thumbnail_url = $videoData['youtube_thumbnail_url'];
                                }

                                $existing->save();
                            }
                        }

                        if (! $syncAll && ! $force && $consecutiveExisting >= $stopEarlyThreshold) {
                            $this->info("Reached {$stopEarlyThreshold} consecutive existing videos. Stopping recent uploads check.");
                            break 2;
                        }
                    } else {
                        $consecutiveExisting = 0;
                        $totalCreated++;

                        $activityDetails[] = [
                            'type' => 'new_video',
                            'video_id' => $videoId,
                            'title' => $title,
                            'published_at' => $videoData['published_at'],
                            'duration_seconds' => $videoData['duration_seconds'],
                            'view_count' => $videoData['view_count'],
                            'video_type' => $videoData['video_type'],
                        ];

                        Log::info("YouTube Sync: Ingested new video '{$title}' ({$videoId}) [Type: {$videoData['video_type']}, Views: {$videoData['view_count']}]");

                        if ($dryRun) {
                            $this->line("[DRY-RUN] Would create new video: {$title} ({$videoId})");
                        } else {
                            $newVideo = new Video;
                            $newVideo->youtube_video_id = $videoId;
                            $newVideo->title = mb_substr($title, 0, 255);
                            $newVideo->slug = Video::generateSlug($title);
                            $newVideo->published_at = $videoData['published_at'];
                            $newVideo->duration_seconds = $videoData['duration_seconds'];
                            $newVideo->view_count = $videoData['view_count'];
                            $newVideo->youtube_thumbnail_url = $videoData['youtube_thumbnail_url'];
                            $newVideo->video_type = $videoData['video_type'];
                            $newVideo->access_level = 'public';
                            $newVideo->is_finalized = false;
                            $newVideo->stats_synced_at = now();
                            if ($systemUser) {
                                $newVideo->created_by_id = $systemUser->id;
                                $newVideo->updated_by_id = $systemUser->id;
                            }
                            $newVideo->save();
                        }
                    }
                }

                $pageToken = $playlistData['nextPageToken'];
            } while ($pageToken !== null);
        }

        // -------------------------------------------------------------
        // Step 2: Tiered view count updates based on release schedule
        // -------------------------------------------------------------
        if (! $skipStats && ! $syncAll) {
            $dueVideos = $youTubeService->getVideosDueForViewUpdate($now, $force || $allStats);
            $dueCount = $dueVideos->count();

            if ($dueCount > 0) {
                $this->info("Found {$dueCount} video(s) due for view count update.");

                $dueVideos->chunk(50)->each(function ($chunk) use ($youTubeService, $dryRun, &$totalUpdated, &$totalProcessed, &$totalViewsAdded, &$activityDetails) {
                    $chunkVideoIds = $chunk->pluck('youtube_video_id')->filter()->values()->all();
                    if (empty($chunkVideoIds)) {
                        return;
                    }

                    $batchDetails = $youTubeService->getBatchVideoDetails($chunkVideoIds);

                    foreach ($chunk as $video) {
                        $vid = $video->youtube_video_id;
                        if (! isset($batchDetails[$vid])) {
                            continue;
                        }

                        $data = $batchDetails[$vid];
                        $totalProcessed++;
                        $totalUpdated++;

                        $oldViews = (int) $video->view_count;
                        $newViews = (int) $data['view_count'];
                        $diff = $newViews - $oldViews;
                        $totalViewsAdded += $diff;
                        $diffFormatted = ($diff >= 0 ? "+{$diff}" : (string) $diff);

                        $activityDetails[] = [
                            'type' => 'view_update',
                            'video_id' => $vid,
                            'title' => $video->title,
                            'old_views' => $oldViews,
                            'new_views' => $newViews,
                            'diff' => $diff,
                            'source' => 'tiered_cadence',
                        ];

                        Log::info("YouTube Sync: Updated view count for '{$video->title}' ({$vid}) [{$oldViews} -> {$newViews} ({$diffFormatted})]");

                        if ($dryRun) {
                            $this->line("[DRY-RUN] Would update view count for: {$video->title} ({$vid}) to {$data['view_count']}");
                        } else {
                            $video->view_count = $data['view_count'];
                            $video->stats_synced_at = now();

                            if ($video->duration_seconds === 0 && $data['duration_seconds'] > 0) {
                                $video->duration_seconds = $data['duration_seconds'];
                            }

                            if (empty($video->youtube_thumbnail_url) && ! empty($data['youtube_thumbnail_url'])) {
                                $video->youtube_thumbnail_url = $data['youtube_thumbnail_url'];
                            }

                            $video->save();
                        }
                    }
                });
            }
        }

        $durationMs = (int) round((microtime(true) - $startTime) * 1000);
        $durationSec = round($durationMs / 1000, 2);
        $viewsDeltaFormatted = ($totalViewsAdded >= 0 ? '+'.number_format($totalViewsAdded) : number_format($totalViewsAdded));
        $summaryMessage = "Processed: {$totalProcessed} | Created: {$totalCreated} | Updated: {$totalUpdated} ({$viewsDeltaFormatted} views) in {$durationSec}s";

        $this->info("Sync completed. Processed: {$totalProcessed} | Created: {$totalCreated} | Updated: {$totalUpdated} ({$viewsDeltaFormatted} views)");
        Log::info("YouTube Sync [{$mode}]: Completed. {$summaryMessage}");

        try {
            YouTubeSyncLog::create([
                'status' => $dryRun ? 'dry_run' : 'success',
                'started_at' => $startedAt,
                'finished_at' => now(),
                'duration_ms' => $durationMs,
                'new_videos_count' => $totalCreated,
                'updated_views_count' => $totalUpdated,
                'total_views_added' => $totalViewsAdded,
                'total_processed' => $totalProcessed,
                'message' => $summaryMessage,
                'details' => $activityDetails,
            ]);

            YouTubeSyncLog::pruneOldLogs(100);
        } catch (\Throwable) {
            // Ignore during environments where table might not exist
        }

        if (! $dryRun) {
            Cache::put('youtube_sync_last_run_at', now()->toIso8601String());
        }

        return self::SUCCESS;
    }
}
