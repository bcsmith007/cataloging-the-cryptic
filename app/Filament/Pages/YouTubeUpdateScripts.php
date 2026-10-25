<?php

namespace App\Filament\Pages;

use App\Models\Video;
use App\Models\YouTubeSyncLog;
use App\Services\TimezoneService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * Administrative page for reviewing YouTube data synchronization and update scripts.
 *
 * Displays detailed information on command signatures, execution frequencies,
 * scheduling rules, API quota consumption, last execution time, and recently added videos.
 */
class YouTubeUpdateScripts extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static ?string $navigationLabel = 'YouTube Scripts';

    protected static ?string $title = 'YouTube Update & Sync Scripts';

    protected static ?string $slug = 'youtube-update-scripts';

    protected static string|UnitEnum|null $navigationGroup = 'Tools';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.youtube-update-scripts';

    /**
     * Get the header actions available on this page.
     *
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('previewDryRun')
                ->label('Preview Sync (Dry Run)')
                ->icon(Heroicon::OutlinedPlay)
                ->color('gray')
                ->modalHeading('Execute YouTube Sync Dry Run')
                ->modalDescription('This will inspect the channel uploads playlist and preview upcoming video ingestions and view count updates without saving changes to the database.')
                ->action(function () {
                    try {
                        Artisan::call('ctc:sync-youtube', ['--dry-run' => true, '--limit' => 5]);
                        $output = Artisan::output();

                        Notification::make()
                            ->title('Dry Run Completed')
                            ->body(Str::limit(trim($output), 400))
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Dry Run Execution Error')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('runSyncNow')
                ->label('Run Sync Now')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Execute YouTube Sync Now')
                ->modalDescription('This will scan the uploads playlist for new videos, add them to the catalog, and update view counts for all videos currently due according to the release schedule.')
                ->action(function () {
                    try {
                        Artisan::call('ctc:sync-youtube');
                        $output = Artisan::output();

                        Notification::make()
                            ->title('YouTube Sync Completed')
                            ->body(Str::limit(trim($output), 400))
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Sync Execution Error')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    /**
     * Return the timestamp of when the YouTube update script was last run.
     */
    public function getLastSyncRunAt(): ?Carbon
    {
        $latestLog = YouTubeSyncLog::orderByDesc('started_at')->first();
        if ($latestLog && $latestLog->started_at) {
            return $latestLog->started_at;
        }

        $cached = Cache::get('youtube_sync_last_run_at');
        if ($cached) {
            try {
                return Carbon::parse($cached);
            } catch (\Throwable) {
                // fall through
            }
        }

        $maxSynced = Video::max('stats_synced_at');
        if ($maxSynced) {
            return Carbon::parse($maxSynced);
        }

        return null;
    }

    /**
     * Return the current time zone to use for displaying timestamps.
     */
    public function getUserTimezone(): string
    {
        return TimezoneService::getUserTimezone();
    }

    /**
     * Format a given timestamp in the user's current timezone.
     */
    public function formatInUserTimezone(?Carbon $dateTime, string $format = 'M j, Y H:i', bool $includeTzName = true): string
    {
        if (! $dateTime) {
            return 'N/A';
        }

        return $includeTzName
            ? TimezoneService::formatDateTime($dateTime, "{$format} T")
            : TimezoneService::format($dateTime, $format);
    }

    /**
     * Return the last 10 videos added to the catalog.
     *
     * @return Collection<int, Video>
     */
    public function getLastAddedVideos(): Collection
    {
        return Video::orderByDesc('created_at')
            ->orderByDesc('id')
            ->take(10)
            ->get();
    }

    /**
     * Return the most recent execution logs of the YouTube synchronization command.
     *
     * @return Collection<int, YouTubeSyncLog>
     */
    public function getRecentSyncLogs(int $limit = 10): Collection
    {
        return YouTubeSyncLog::orderByDesc('started_at')
            ->orderByDesc('id')
            ->take($limit)
            ->get();
    }

    /**
     * Return the structured registry of all YouTube updating and ingestion scripts.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getScripts(): array
    {
        return [
            [
                'id' => 'sync-youtube',
                'title' => 'YouTube Channel Uploads Ingestion & Tiered View Count Sync',
                'command' => 'php artisan ctc:sync-youtube',
                'full_signature' => 'ctc:sync-youtube {--all : Full historical sync} {--limit= : Max videos to inspect} {--dry-run : Preview without writing} {--force : Force update} {--all-stats : Update all view counts} {--at= : Simulate reference time} {--skip-new : Skip new check} {--skip-stats : Skip view updates}',
                'status' => 'active',
                'status_label' => 'Active & Scheduled (Every 30m)',
                'status_color' => 'success',
                'frequency' => 'Every 30 Minutes',
                'schedule_expression' => '*/30 * * * *',
                'schedule_details' => 'Configured in routes/console.php via Schedule::command(\'ctc:sync-youtube\')->everyThirtyMinutes()->withoutOverlapping(). Runs automatically in background every 30 minutes.',
                'quota_cost' => '1 unit per 50 playlist items + 1 unit per 50 video details. Daily consumption is ~90-110 units out of 10,000 allowance (< 1.1% of quota).',
                'summary' => 'Automatically runs every 30 minutes. Always checks the official Cracking the Cryptic channel uploads playlist (UUC-UOdK8-mIjxBQm_ot1T-Q) for newly published videos and adds them to the catalog, then refreshes view counts across the catalog following a 5-tier release age schedule.',
                'key_behaviors' => [
                    'Always checks the channel uploads playlist every 30 minutes and adds any new videos to the catalog.',
                    'Tier 1 (< 48 hours old): View count is updated on every 30-minute run.',
                    'Tier 2 (2 to 7 days old): View count is updated twice a day (at midnight and noon UTC).',
                    'Tier 3 (7 to 14 days old): View count is updated once per day (at midnight UTC).',
                    'Tier 4 (14 to 31 days old): View count is updated once a week (at midnight UTC on days 14, 21, and 28 after release).',
                    'Tier 5 (> 31 days old): View count is updated once a month (at midnight UTC on release day-of-month anniversary). For months with fewer than 31 days, on the last day of the month, videos with release days >= current day are updated.',
                    'Ingests core video metadata: title, slug, published_at, duration_seconds, view_count, thumbnail_url, and video_type.',
                    'Automatically detects video formats: standard video, short, or live stream replay.',
                    'Sets is_finalized = false on all new videos so they automatically enter the review queue at /admin/unfinalized-videos.',
                    'Intentionally does NOT auto-populate descriptions or puzzles; puzzle and bookmark associations are curated by editors in Filament.',
                    'Preserves editor-curated titles and slugs; only updates view counts and missing metadata.',
                ],
                'options' => [
                    ['flag' => '--all', 'description' => 'Scan full channel upload history and update all records.'],
                    ['flag' => '--limit=N', 'description' => 'Limit the maximum number of videos fetched from playlist.'],
                    ['flag' => '--dry-run', 'description' => 'Simulate ingestion and view count updates without writing to the database.'],
                    ['flag' => '--force', 'description' => 'Force update regardless of cadence intervals or consecutive existing videos.'],
                    ['flag' => '--all-stats', 'description' => 'Update view counts for all videos in database regardless of release schedule.'],
                    ['flag' => '--at=TIME', 'description' => 'Evaluate cadence against reference time (e.g. "2026-10-15 00:00:00", "midnight", "noon").'],
                    ['flag' => '--skip-new', 'description' => 'Skip checking playlist for new videos; only run tiered view count updates.'],
                    ['flag' => '--skip-stats', 'description' => 'Skip tiered view count updates; only check for new videos.'],
                ],
                'example_cli' => 'php artisan ctc:sync-youtube --dry-run',
            ],
            [
                'id' => 'update-views',
                'title' => 'Tiered View Count Synchronization',
                'command' => 'php artisan ctc:update-views',
                'full_signature' => 'ctc:sync-youtube {--all-stats} {--at=}',
                'status' => 'active',
                'status_label' => 'Active (Integrated in ctc:sync-youtube)',
                'status_color' => 'success',
                'frequency' => 'Tiered: Every 30m / 2x Daily / Daily / Weekly / Monthly',
                'schedule_expression' => '0,30 * * * * (Evaluated in UTC)',
                'schedule_details' => 'Fully implemented directly within ctc:sync-youtube. Evaluated every 30 minutes in UTC: Tier 1 (< 48h) on every run; Tier 2 (2-7d) at 00:00 and 12:00; Tier 3 (7-14d) at 00:00; Tier 4 (14-31d) on days 14, 21, 28; Tier 5 (> 31d) monthly on release day anniversary.',
                'quota_cost' => 'Batched in 50-ID chunks via videos.list?part=statistics. Total daily view count quota is only ~90-110 units.',
                'summary' => 'Refreshes view counts across the catalog using release-age tiers, ensuring fast updates for fresh content while conserving quota for archive videos.',
                'key_behaviors' => [
                    'Batches up to 50 video IDs per API request to maximize quota efficiency.',
                    'Only updates view_count (and duration/thumbnail if missing); leaves editor curations untouched.',
                    'Tier 1 (< 48h): Every 30 minutes.',
                    'Tier 2 (2 - 7d): Twice daily at midnight and noon UTC.',
                    'Tier 3 (7 - 14d): Once daily at midnight UTC.',
                    'Tier 4 (14 - 31d): Once weekly at midnight UTC on days 14, 21, 28.',
                    'Tier 5 (> 31d): Once monthly at midnight UTC on anniversary, with short-month boundary handling.',
                ],
                'options' => [
                    ['flag' => '--all-stats', 'description' => 'Force view count updates on all videos regardless of tier.'],
                    ['flag' => '--at=TIME', 'description' => 'Simulate run time (midnight, noon, or custom datetime).'],
                ],
                'example_cli' => 'php artisan ctc:sync-youtube --at=midnight',
            ],
        ];
    }

    /**
     * Return YouTube Data API quota overview parameters.
     *
     * @return array<string, string>
     */
    public function getQuotaOverview(): array
    {
        return [
            'daily_limit' => '10,000 units',
            'current_usage_estimate' => '~90 - 110 units / day (< 1.2%)',
            'reset_schedule' => 'Midnight Pacific Time (00:00 PST / PDT)',
            'endpoint_costs' => 'playlistItems.list = 1 unit / 50 items | videos.list = 1 unit / 50 videos',
        ];
    }
}
