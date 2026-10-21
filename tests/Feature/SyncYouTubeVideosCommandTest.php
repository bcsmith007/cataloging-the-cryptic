<?php

namespace Tests\Feature;

use App\Models\Video;
use App\Models\YouTubeSyncLog;
use App\Services\YouTubeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Feature tests for the ctc:sync-youtube Artisan console command.
 */
class SyncYouTubeVideosCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test sync command creates new public videos without descriptions or puzzle links.
     */
    public function test_sync_youtube_creates_new_videos(): void
    {
        config(['services.youtube.key' => 'fake-api-key']);

        Http::fake([
            'https://www.googleapis.com/youtube/v3/playlistItems*' => Http::response([
                'items' => [
                    [
                        'contentDetails' => ['videoId' => 'video1234567'],
                        'snippet' => ['title' => 'Brilliant Sudoku In Three Minutes'],
                    ],
                ],
                'nextPageToken' => null,
                'pageInfo' => ['totalResults' => 1],
            ]),
            'https://www.googleapis.com/youtube/v3/videos*' => Http::response([
                'items' => [
                    [
                        'id' => 'video1234567',
                        'snippet' => [
                            'title' => 'Brilliant Sudoku In Three Minutes',
                            'publishedAt' => '2026-09-28T12:00:00Z',
                            'thumbnails' => [
                                'high' => ['url' => 'https://i.ytimg.com/vi/video1234567/hqdefault.jpg'],
                            ],
                        ],
                        'contentDetails' => [
                            'duration' => 'PT18M42S',
                        ],
                        'statistics' => [
                            'viewCount' => '8500',
                        ],
                    ],
                ],
            ]),
        ]);

        $this->artisan('ctc:sync-youtube --all')
            ->expectsOutputToContain('Starting YouTube sync')
            ->expectsOutputToContain('Sync completed. Processed: 1 | Created: 1 | Updated: 0')
            ->assertExitCode(0);

        $this->assertDatabaseHas('videos', [
            'youtube_video_id' => 'video1234567',
            'title' => 'Brilliant Sudoku In Three Minutes',
            'slug' => 'brilliant-sudoku-in-three-minutes',
            'duration_seconds' => 1122,
            'view_count' => 8500,
            'video_type' => 'youtube',
            'access_level' => 'public',
            'is_finalized' => false,
        ]);

        $video = Video::where('youtube_video_id', 'video1234567')->firstOrFail();
        $this->assertFalse($video->is_finalized);
        $this->assertCount(0, $video->puzzles);
        $this->assertCount(0, $video->bookmarks);
    }

    /**
     * Test sync command updates existing videos without overwriting curated titles or slugs.
     */
    public function test_sync_youtube_updates_existing_video_stats(): void
    {
        config(['services.youtube.key' => 'fake-api-key']);

        $existing = Video::create([
            'youtube_video_id' => 'existing1234',
            'title' => 'Custom Hand-Curated Title',
            'slug' => 'custom-hand-curated-title',
            'published_at' => '2026-09-01 00:00:00',
            'duration_seconds' => 1200,
            'view_count' => 500,
            'video_type' => 'youtube',
            'access_level' => 'public',
            'is_finalized' => true,
        ]);

        Http::fake([
            'https://www.googleapis.com/youtube/v3/playlistItems*' => Http::response([
                'items' => [
                    [
                        'contentDetails' => ['videoId' => 'existing1234'],
                        'snippet' => ['title' => 'YouTube Changed Title'],
                    ],
                ],
                'nextPageToken' => null,
                'pageInfo' => ['totalResults' => 1],
            ]),
            'https://www.googleapis.com/youtube/v3/videos*' => Http::response([
                'items' => [
                    [
                        'id' => 'existing1234',
                        'snippet' => [
                            'title' => 'YouTube Changed Title',
                            'publishedAt' => '2026-09-01T00:00:00Z',
                            'thumbnails' => [
                                'high' => ['url' => 'https://i.ytimg.com/vi/existing1234/hqdefault.jpg'],
                            ],
                        ],
                        'contentDetails' => [
                            'duration' => 'PT20M00S',
                        ],
                        'statistics' => [
                            'viewCount' => '9999',
                        ],
                    ],
                ],
            ]),
        ]);

        $this->artisan('ctc:sync-youtube --all')
            ->expectsOutputToContain('Sync completed. Processed: 1 | Created: 0 | Updated: 1')
            ->assertExitCode(0);

        $existing->refresh();
        $this->assertSame('Custom Hand-Curated Title', $existing->title);
        $this->assertSame('custom-hand-curated-title', $existing->slug);
        $this->assertSame(9999, $existing->view_count);
        $this->assertTrue($existing->is_finalized);
    }

    /**
     * Test dry-run mode previews changes without modifying the database.
     */
    public function test_sync_youtube_dry_run_mode(): void
    {
        config(['services.youtube.key' => 'fake-api-key']);

        Http::fake([
            'https://www.googleapis.com/youtube/v3/playlistItems*' => Http::response([
                'items' => [
                    [
                        'contentDetails' => ['videoId' => 'dryrun123456'],
                        'snippet' => ['title' => 'Dry Run Preview Video'],
                    ],
                ],
                'nextPageToken' => null,
                'pageInfo' => ['totalResults' => 1],
            ]),
            'https://www.googleapis.com/youtube/v3/videos*' => Http::response([
                'items' => [
                    [
                        'id' => 'dryrun123456',
                        'snippet' => [
                            'title' => 'Dry Run Preview Video',
                            'publishedAt' => '2026-09-30T10:00:00Z',
                            'thumbnails' => [
                                'high' => ['url' => 'https://i.ytimg.com/vi/dryrun123456/hqdefault.jpg'],
                            ],
                        ],
                        'contentDetails' => [
                            'duration' => 'PT15M00S',
                        ],
                        'statistics' => [
                            'viewCount' => '1500',
                        ],
                    ],
                ],
            ]),
        ]);

        $this->artisan('ctc:sync-youtube --dry-run')
            ->expectsOutputToContain('Running in DRY-RUN mode')
            ->expectsOutputToContain('[DRY-RUN] Would create new video: Dry Run Preview Video (dryrun123456)')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('videos', [
            'youtube_video_id' => 'dryrun123456',
        ]);
    }

    /**
     * Test command fails gracefully when API key is missing.
     */
    public function test_sync_youtube_missing_api_key(): void
    {
        config(['services.youtube.key' => null]);

        $this->artisan('ctc:sync-youtube')
            ->expectsOutputToContain('YouTube API key is not configured')
            ->assertExitCode(1);
    }

    /**
     * Tier 1: Videos released in the last 48 hours are due on every 30-minute run.
     */
    public function test_tier_1_videos_under_48_hours_are_due_on_every_run(): void
    {
        $service = app(YouTubeService::class);
        $now = Carbon::parse('2026-10-15 14:30:00', 'UTC');

        $videoRecent = new Video(['published_at' => '2026-10-15 10:00:00']);
        $video1DayOld = new Video(['published_at' => '2026-10-14 14:30:00']);
        $video47HoursOld = new Video(['published_at' => '2026-10-13 15:35:00']);
        $video49HoursOld = new Video(['published_at' => '2026-10-13 13:00:00']);

        $this->assertTrue($service->isVideoDueForViewUpdate($videoRecent, $now, false, false));
        $this->assertTrue($service->isVideoDueForViewUpdate($video1DayOld, $now, false, false));
        $this->assertTrue($service->isVideoDueForViewUpdate($video47HoursOld, $now, false, false));
        $this->assertFalse($service->isVideoDueForViewUpdate($video49HoursOld, $now, false, false));
    }

    /**
     * Tier 2: Videos released 2 to 7 days ago are due at midnight and noon only.
     */
    public function test_tier_2_videos_2_to_7_days_are_due_at_midnight_and_noon_only(): void
    {
        $service = app(YouTubeService::class);
        $video = new Video(['published_at' => '2026-10-12 00:00:00']); // 3 days old on Oct 15

        $midnight = Carbon::parse('2026-10-15 00:00:00', 'UTC');
        $noon = Carbon::parse('2026-10-15 12:00:00', 'UTC');
        $afternoon = Carbon::parse('2026-10-15 15:30:00', 'UTC');
        $morning = Carbon::parse('2026-10-15 06:00:00', 'UTC');

        $this->assertTrue($service->isVideoDueForViewUpdate($video, $midnight, false, false));
        $this->assertTrue($service->isVideoDueForViewUpdate($video, $noon, false, false));
        $this->assertFalse($service->isVideoDueForViewUpdate($video, $afternoon, false, false));
        $this->assertFalse($service->isVideoDueForViewUpdate($video, $morning, false, false));
    }

    /**
     * Tier 3: Videos released 7 to 14 days ago are due once per day at midnight.
     */
    public function test_tier_3_videos_7_to_14_days_are_due_at_midnight_only(): void
    {
        $service = app(YouTubeService::class);
        $video = new Video(['published_at' => '2026-10-05 00:00:00']); // 10 days old on Oct 15

        $midnight = Carbon::parse('2026-10-15 00:00:00', 'UTC');
        $noon = Carbon::parse('2026-10-15 12:00:00', 'UTC');

        $this->assertTrue($service->isVideoDueForViewUpdate($video, $midnight, false, false));
        $this->assertFalse($service->isVideoDueForViewUpdate($video, $noon, false, false));
    }

    /**
     * Tier 4: Videos released 14 to 31 days ago are due once a week at midnight on days 14, 21, and 28.
     */
    public function test_tier_4_videos_14_to_31_days_are_due_weekly_on_days_14_21_28_at_midnight(): void
    {
        $service = app(YouTubeService::class);
        $video = new Video(['published_at' => '2026-10-01 00:00:00']);

        $day14 = Carbon::parse('2026-10-15 00:00:00', 'UTC'); // 14 days
        $day15 = Carbon::parse('2026-10-16 00:00:00', 'UTC'); // 15 days
        $day21 = Carbon::parse('2026-10-22 00:00:00', 'UTC'); // 21 days
        $day22 = Carbon::parse('2026-10-23 00:00:00', 'UTC'); // 22 days
        $day28 = Carbon::parse('2026-10-29 00:00:00', 'UTC'); // 28 days
        $day29 = Carbon::parse('2026-10-30 00:00:00', 'UTC'); // 29 days

        $this->assertTrue($service->isVideoDueForViewUpdate($video, $day14, false, false));
        $this->assertFalse($service->isVideoDueForViewUpdate($video, $day15, false, false));
        $this->assertTrue($service->isVideoDueForViewUpdate($video, $day21, false, false));
        $this->assertFalse($service->isVideoDueForViewUpdate($video, $day22, false, false));
        $this->assertTrue($service->isVideoDueForViewUpdate($video, $day28, false, false));
        $this->assertFalse($service->isVideoDueForViewUpdate($video, $day29, false, false));
    }

    /**
     * Tier 5: Videos released more than 31 days ago are due once a month on the anniversary day.
     */
    public function test_tier_5_videos_over_31_days_are_due_monthly_at_midnight(): void
    {
        $service = app(YouTubeService::class);
        $video = new Video(['published_at' => '2022-06-01 12:00:00']); // Released June 1, 2022

        $july1 = Carbon::parse('2022-07-01 00:00:00', 'UTC');
        $july2 = Carbon::parse('2022-07-02 00:00:00', 'UTC');
        $august1 = Carbon::parse('2022-08-01 00:00:00', 'UTC');
        $september1 = Carbon::parse('2022-09-01 00:00:00', 'UTC');

        $this->assertTrue($service->isVideoDueForViewUpdate($video, $july1, false, false));
        $this->assertFalse($service->isVideoDueForViewUpdate($video, $july2, false, false));
        $this->assertTrue($service->isVideoDueForViewUpdate($video, $august1, false, false));
        $this->assertTrue($service->isVideoDueForViewUpdate($video, $september1, false, false));
    }

    /**
     * Tier 5 Short Months: On months with less than 31 days, on the last day of the month,
     * videos with release day >= current day are updated.
     */
    public function test_tier_5_short_months_update_videos_on_last_day_with_release_day_greater_equal(): void
    {
        $service = app(YouTubeService::class);

        $videoReleasedJan28 = new Video(['published_at' => '2025-01-28 00:00:00']);
        $videoReleasedJan29 = new Video(['published_at' => '2025-01-29 00:00:00']);
        $videoReleasedJan30 = new Video(['published_at' => '2025-01-30 00:00:00']);
        $videoReleasedJan31 = new Video(['published_at' => '2025-01-31 00:00:00']);
        $videoReleasedJan27 = new Video(['published_at' => '2025-01-27 00:00:00']);

        // 2026 is a non-leap year (February has 28 days). Last day is Feb 28.
        $feb28 = Carbon::parse('2026-02-28 00:00:00', 'UTC');

        $this->assertTrue($service->isVideoDueForViewUpdate($videoReleasedJan28, $feb28, false, false));
        $this->assertTrue($service->isVideoDueForViewUpdate($videoReleasedJan29, $feb28, false, false));
        $this->assertTrue($service->isVideoDueForViewUpdate($videoReleasedJan30, $feb28, false, false));
        $this->assertTrue($service->isVideoDueForViewUpdate($videoReleasedJan31, $feb28, false, false));
        $this->assertFalse($service->isVideoDueForViewUpdate($videoReleasedJan27, $feb28, false, false));

        // April has 30 days. Last day is April 30.
        $april30 = Carbon::parse('2026-04-30 00:00:00', 'UTC');
        $videoReleasedMarch30 = new Video(['published_at' => '2025-03-30 00:00:00']);
        $videoReleasedMarch31 = new Video(['published_at' => '2025-03-31 00:00:00']);
        $videoReleasedMarch29 = new Video(['published_at' => '2025-03-29 00:00:00']);

        $this->assertTrue($service->isVideoDueForViewUpdate($videoReleasedMarch30, $april30, false, false));
        $this->assertTrue($service->isVideoDueForViewUpdate($videoReleasedMarch31, $april30, false, false));
        $this->assertFalse($service->isVideoDueForViewUpdate($videoReleasedMarch29, $april30, false, false));
    }

    /**
     * Test command syncs due video stats via tiered schedule at noon.
     */
    public function test_command_syncs_due_video_stats_via_tiered_schedule_at_noon(): void
    {
        config(['services.youtube.key' => 'fake-api-key']);

        // Video 1: 1 day old (< 48 hours) -> Tier 1 (due)
        $video1 = Video::create([
            'youtube_video_id' => 'tier1video',
            'title' => 'Tier 1 Video',
            'slug' => 'tier-1-video',
            'published_at' => '2026-10-14 12:00:00',
            'duration_seconds' => 600,
            'view_count' => 100,
            'access_level' => 'public',
        ]);

        // Video 2: 4 days old -> Tier 2 (due at noon)
        $video2 = Video::create([
            'youtube_video_id' => 'tier2video',
            'title' => 'Tier 2 Video',
            'slug' => 'tier-2-video',
            'published_at' => '2026-10-11 12:00:00',
            'duration_seconds' => 600,
            'view_count' => 200,
            'access_level' => 'public',
        ]);

        // Video 3: 10 days old -> Tier 3 (due at midnight only, NOT noon)
        $video3 = Video::create([
            'youtube_video_id' => 'tier3video',
            'title' => 'Tier 3 Video',
            'slug' => 'tier-3-video',
            'published_at' => '2026-10-05 12:00:00',
            'duration_seconds' => 600,
            'view_count' => 300,
            'access_level' => 'public',
        ]);

        Http::fake([
            'https://www.googleapis.com/youtube/v3/playlistItems*' => Http::response([
                'items' => [],
                'nextPageToken' => null,
                'pageInfo' => ['totalResults' => 0],
            ]),
            'https://www.googleapis.com/youtube/v3/videos*' => Http::response([
                'items' => [
                    [
                        'id' => 'tier1video',
                        'snippet' => ['title' => 'Tier 1 Video'],
                        'contentDetails' => ['duration' => 'PT10M00S'],
                        'statistics' => ['viewCount' => '150'],
                    ],
                    [
                        'id' => 'tier2video',
                        'snippet' => ['title' => 'Tier 2 Video'],
                        'contentDetails' => ['duration' => 'PT10M00S'],
                        'statistics' => ['viewCount' => '250'],
                    ],
                ],
            ]),
        ]);

        $this->artisan('ctc:sync-youtube --at="2026-10-15 12:00:00"')
            ->expectsOutputToContain('Found 2 video(s) due for view count update.')
            ->expectsOutputToContain('Sync completed. Processed: 2 | Created: 0 | Updated: 2')
            ->assertExitCode(0);

        $video1->refresh();
        $video2->refresh();
        $video3->refresh();

        $this->assertSame(150, $video1->view_count);
        $this->assertSame(250, $video2->view_count);
        $this->assertSame(300, $video3->view_count); // Unchanged
    }

    /**
     * Test command syncs due video stats via tiered schedule at midnight.
     */
    public function test_command_syncs_due_video_stats_via_tiered_schedule_at_midnight(): void
    {
        config(['services.youtube.key' => 'fake-api-key']);

        // Video: Released on Jan 31, 2025. Evaluated on Feb 28, 2026 (short month last day).
        $video = Video::create([
            'youtube_video_id' => 'shortmonthvid',
            'title' => 'Short Month Video',
            'slug' => 'short-month-video',
            'published_at' => '2025-01-31 10:00:00',
            'duration_seconds' => 900,
            'view_count' => 1000,
            'access_level' => 'public',
        ]);

        Http::fake([
            'https://www.googleapis.com/youtube/v3/playlistItems*' => Http::response([
                'items' => [],
                'nextPageToken' => null,
                'pageInfo' => ['totalResults' => 0],
            ]),
            'https://www.googleapis.com/youtube/v3/videos*' => Http::response([
                'items' => [
                    [
                        'id' => 'shortmonthvid',
                        'snippet' => ['title' => 'Short Month Video'],
                        'contentDetails' => ['duration' => 'PT15M00S'],
                        'statistics' => ['viewCount' => '1234'],
                    ],
                ],
            ]),
        ]);

        $this->artisan('ctc:sync-youtube --at="2026-02-28 00:00:00"')
            ->expectsOutputToContain('Found 1 video(s) due for view count update.')
            ->expectsOutputToContain('Sync completed. Processed: 1 | Created: 0 | Updated: 1')
            ->assertExitCode(0);

        $video->refresh();
        $this->assertSame(1234, $video->view_count);
    }

    /**
     * Test that the sync command creates a YouTubeSyncLog record with structured details.
     */
    public function test_sync_command_creates_sync_log_with_detailed_activities(): void
    {
        config(['services.youtube.key' => 'fake-api-key']);

        Http::fake([
            'https://www.googleapis.com/youtube/v3/playlistItems*' => Http::response([
                'items' => [
                    [
                        'contentDetails' => ['videoId' => 'logtest123'],
                        'snippet' => ['title' => 'Logging Test Video'],
                    ],
                ],
                'nextPageToken' => null,
                'pageInfo' => ['totalResults' => 1],
            ]),
            'https://www.googleapis.com/youtube/v3/videos*' => Http::response([
                'items' => [
                    [
                        'id' => 'logtest123',
                        'snippet' => [
                            'title' => 'Logging Test Video',
                            'publishedAt' => '2026-10-15T12:00:00Z',
                            'thumbnails' => ['high' => ['url' => 'https://i.ytimg.com/vi/logtest123/hqdefault.jpg']],
                        ],
                        'contentDetails' => ['duration' => 'PT10M00S'],
                        'statistics' => ['viewCount' => '4321'],
                    ],
                ],
            ]),
        ]);

        $this->artisan('ctc:sync-youtube --all')
            ->assertExitCode(0);

        $this->assertDatabaseHas('youtube_sync_logs', [
            'status' => 'success',
            'new_videos_count' => 1,
            'total_processed' => 1,
        ]);

        $log = YouTubeSyncLog::latest('id')->firstOrFail();
        $this->assertNotNull($log->duration_ms);
        $this->assertNotEmpty($log->details);
        $this->assertSame('new_video', $log->details[0]['type']);
        $this->assertSame('logtest123', $log->details[0]['video_id']);
        $this->assertSame(4321, $log->details[0]['view_count']);
        $this->assertSame(0, $log->total_views_added);
    }

    /**
     * Test that cumulative view deltas across multiple updated videos are recorded in total_views_added.
     */
    public function test_sync_command_records_cumulative_views_added_in_sync_log(): void
    {
        config(['services.youtube.key' => 'fake-api-key']);

        // Create two existing videos in Tier 1 (< 48h)
        $video1 = Video::factory()->create([
            'youtube_video_id' => 'v_update_1',
            'title' => 'Video 1',
            'view_count' => 1000,
            'published_at' => Carbon::now('UTC')->subHours(5),
            'stats_synced_at' => Carbon::now('UTC')->subHours(1),
        ]);

        $video2 = Video::factory()->create([
            'youtube_video_id' => 'v_update_2',
            'title' => 'Video 2',
            'view_count' => 5000,
            'published_at' => Carbon::now('UTC')->subHours(12),
            'stats_synced_at' => Carbon::now('UTC')->subHours(1),
        ]);

        Http::fake([
            'https://www.googleapis.com/youtube/v3/playlistItems*' => Http::response([
                'items' => [],
                'nextPageToken' => null,
            ]),
            'https://www.googleapis.com/youtube/v3/videos*' => Http::response([
                'items' => [
                    [
                        'id' => 'v_update_1',
                        'snippet' => [
                            'title' => 'Video 1',
                            'publishedAt' => Carbon::now('UTC')->subHours(5)->toIso8601String(),
                        ],
                        'contentDetails' => ['duration' => 'PT10M00S'],
                        'statistics' => ['viewCount' => '1350'], // +350
                    ],
                    [
                        'id' => 'v_update_2',
                        'snippet' => [
                            'title' => 'Video 2',
                            'publishedAt' => Carbon::now('UTC')->subHours(12)->toIso8601String(),
                        ],
                        'contentDetails' => ['duration' => 'PT15M00S'],
                        'statistics' => ['viewCount' => '5650'], // +650
                    ],
                ],
            ]),
        ]);

        $this->artisan('ctc:sync-youtube')
            ->assertExitCode(0);

        $log = YouTubeSyncLog::latest('id')->firstOrFail();
        $this->assertSame(2, $log->updated_views_count);
        $this->assertSame(1000, $log->total_views_added);
        $this->assertSame('+1,000 Views', $log->formatted_views_added);
        $this->assertStringContainsString('+1,000 views', $log->message);

        $video1->refresh();
        $video2->refresh();
        $this->assertSame(1350, $video1->view_count);
        $this->assertSame(5650, $video2->view_count);
    }
}
