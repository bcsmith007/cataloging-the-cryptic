<x-filament-panels::page>
    <style>
        .ctc-scripts-wrapper {
            display: flex;
            flex-direction: column;
            gap: 2rem;
            font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
            color: #e2e8f0;
        }
        .ctc-metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1rem;
        }
        .ctc-metric-box {
            background-color: #0f172a;
            border: 1px solid #1e293b;
            border-radius: 0.75rem;
            padding: 1.25rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.25);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .ctc-metric-label {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #94a3b8;
            margin-bottom: 0.5rem;
        }
        .ctc-metric-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #f8fafc;
            display: flex;
            align-items: baseline;
            gap: 0.5rem;
            font-family: 'JetBrains Mono', monospace;
        }
        .ctc-metric-subtext {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 0.375rem;
        }

        .ctc-script-card {
            background-color: #0f172a;
            border: 1px solid #1e293b;
            border-radius: 0.875rem;
            padding: 1.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .ctc-card-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .ctc-card-title-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .ctc-card-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -0.01em;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.75rem;
        }
        .ctc-command-badge {
            display: inline-flex;
            align-items: center;
            background-color: #020617;
            border: 1px solid #334155;
            border-radius: 0.375rem;
            padding: 0.25rem 0.625rem;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.8125rem;
            color: #fbbf24;
            font-weight: 600;
            width: fit-content;
        }

        .ctc-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.625rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            line-height: 1rem;
            white-space: nowrap;
        }
        .ctc-badge-success {
            background-color: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.35);
        }
        .ctc-badge-warning {
            background-color: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.35);
        }
        .ctc-badge-info {
            background-color: rgba(14, 165, 233, 0.15);
            color: #38bdf8;
            border: 1px solid rgba(14, 165, 233, 0.35);
        }
        .ctc-badge-secondary {
            background-color: rgba(148, 163, 184, 0.15);
            color: #94a3b8;
            border: 1px solid rgba(148, 163, 184, 0.35);
        }
        .ctc-freq-pill {
            background-color: #1e293b;
            color: #cbd5e1;
            border: 1px solid #334155;
            border-radius: 0.375rem;
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .ctc-hr {
            border: 0;
            border-top: 1px solid #1e293b;
            margin: 0.25rem 0;
            width: 100%;
        }

        .ctc-section-block {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .ctc-section-heading {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #94a3b8;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .ctc-summary-text {
            font-size: 0.9375rem;
            line-height: 1.5rem;
            color: #cbd5e1;
        }

        .ctc-bullet-list {
            margin: 0.25rem 0 0 0;
            padding-left: 1.5rem;
            list-style-type: disc;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            color: #cbd5e1;
            font-size: 0.875rem;
            line-height: 1.375rem;
        }
        .ctc-bullet-list li::marker {
            color: #10b981;
        }

        .ctc-two-col-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1rem;
        }
        .ctc-info-box {
            background-color: #020617;
            border: 1px solid #1e293b;
            border-radius: 0.5rem;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.375rem;
        }
        .ctc-info-box-title {
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #94a3b8;
        }
        .ctc-info-box-body {
            font-size: 0.8125rem;
            line-height: 1.25rem;
            color: #e2e8f0;
        }
        .ctc-cron-tag {
            font-family: 'JetBrains Mono', monospace;
            background-color: #0f172a;
            color: #34d399;
            padding: 0.125rem 0.5rem;
            border-radius: 0.25rem;
            border: 1px solid #1e293b;
            font-size: 0.75rem;
            width: fit-content;
            margin-top: 0.25rem;
        }

        .ctc-table-wrapper {
            overflow-x: auto;
            border: 1px solid #1e293b;
            border-radius: 0.5rem;
            background-color: #020617;
        }
        .ctc-options-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8125rem;
            text-align: left;
        }
        .ctc-options-table th {
            padding: 0.625rem 1rem;
            background-color: #090d16;
            border-bottom: 1px solid #1e293b;
            color: #94a3b8;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.6875rem;
            letter-spacing: 0.05em;
        }
        .ctc-options-table td {
            padding: 0.625rem 1rem;
            border-bottom: 1px solid #1e293b;
            color: #cbd5e1;
        }
        .ctc-options-table tr:last-child td {
            border-bottom: none;
        }
        .ctc-flag-code {
            font-family: 'JetBrains Mono', monospace;
            color: #fbbf24;
            font-weight: 600;
            white-space: nowrap;
        }

        .ctc-example-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            background-color: #020617;
            border: 1px solid #1e293b;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            font-size: 0.8125rem;
        }
        .ctc-example-label {
            color: #94a3b8;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.6875rem;
            letter-spacing: 0.05em;
        }
        .ctc-example-code {
            font-family: 'JetBrains Mono', monospace;
            color: #34d399;
            font-weight: 500;
            word-break: break-all;
            user-select: all;
        }

        .ctc-quota-ref-card {
            background-color: #0f172a;
            border: 1px solid #1e293b;
            border-radius: 0.875rem;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .ctc-quota-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.75rem;
        }
        .ctc-quota-subcard {
            background-color: #020617;
            border: 1px solid #1e293b;
            border-radius: 0.5rem;
            padding: 0.875rem;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        /* Recent Videos Table Styling */
        .ctc-recent-card {
            background-color: #0f172a;
            border: 1px solid #1e293b;
            border-radius: 0.875rem;
            padding: 1.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }
        .ctc-recent-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8125rem;
            text-align: left;
        }
        .ctc-recent-table th {
            padding: 0.75rem 1rem;
            background-color: #090d16;
            border-bottom: 1px solid #1e293b;
            color: #94a3b8;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.6875rem;
            letter-spacing: 0.05em;
            white-space: nowrap;
        }
        .ctc-recent-table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #1e293b;
            color: #cbd5e1;
            vertical-align: middle;
        }
        .ctc-recent-table tr:hover td {
            background-color: rgba(30, 41, 59, 0.4);
        }
        .ctc-recent-table tr:last-child td {
            border-bottom: none;
        }
        .ctc-video-thumb {
            width: 72px;
            height: 40px;
            object-fit: cover;
            border-radius: 0.375rem;
            border: 1px solid #334155;
            background-color: #020617;
            flex-shrink: 0;
        }
        .ctc-video-title-link {
            color: #f8fafc;
            font-weight: 600;
            text-decoration: none;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.25rem;
        }
        .ctc-video-title-link:hover {
            color: #38bdf8;
            text-decoration: underline;
        }
        .ctc-action-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.25rem 0.625rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-decoration: none;
            background-color: #1e293b;
            color: #e2e8f0;
            border: 1px solid #334155;
            transition: all 0.15s ease-in-out;
            white-space: nowrap;
        }
        .ctc-action-btn:hover {
            background-color: #334155;
            color: #ffffff;
            border-color: #475569;
        }
    </style>

    <div class="ctc-scripts-wrapper">
        {{-- High-Level Overview Grid --}}
        <div class="ctc-metrics-grid">
            <div class="ctc-metric-box">
                <div class="ctc-metric-label">Scheduled Frequency</div>
                <div class="ctc-metric-value">
                    <span>Every 30m</span>
                    <span class="ctc-badge ctc-badge-success">Active</span>
                </div>
                <div class="ctc-metric-subtext">ctc:sync-youtube (Every 30 Minutes)</div>
            </div>

            <div class="ctc-metric-box">
                <div class="ctc-metric-label">Last Script Execution</div>
                @php
                    $lastRun = $this->getLastSyncRunAt();
                @endphp
                @if ($lastRun)
                    <div class="ctc-metric-value" style="font-size: 1.25rem; color: #38bdf8;">
                        {{ $lastRun->setTimezone('UTC')->format('M j, H:i') }} UTC
                    </div>
                    <div class="ctc-metric-subtext">
                        Ran {{ $lastRun->diffForHumans() }}
                    </div>
                @else
                    <div class="ctc-metric-value" style="font-size: 1.25rem; color: #94a3b8;">
                        Not Run Yet
                    </div>
                    <div class="ctc-metric-subtext">Pending first scheduled run</div>
                @endif
            </div>

            <div class="ctc-metric-box">
                <div class="ctc-metric-label">Total Catalog Videos</div>
                <div class="ctc-metric-value" style="color: #f8fafc;">
                    {{ number_format(\App\Models\Video::count()) }}
                </div>
                <div class="ctc-metric-subtext">
                    {{ number_format(\App\Models\Video::where('is_finalized', true)->count()) }} finalized,
                    {{ number_format(\App\Models\Video::where('is_finalized', false)->count()) }} in review
                </div>
            </div>

            <div class="ctc-metric-box">
                <div class="ctc-metric-label">Daily YouTube API Quota</div>
                <div class="ctc-metric-value" style="color: #34d399;">
                    &lt; 1.2%
                </div>
                <div class="ctc-metric-subtext">~90 - 110 of 10,000 units / day used</div>
            </div>
        </div>

        {{-- Recently Added Videos (Last 10) --}}
        <div class="ctc-recent-card">
            <div class="ctc-card-header">
                <div class="ctc-card-title-group">
                    <div class="ctc-card-title">
                        <span>Last 10 Videos Added to Catalog</span>
                        <span class="ctc-badge ctc-badge-info">Latest Ingestions</span>
                    </div>
                    <div style="font-size: 0.8125rem; color: #94a3b8;">
                        The 10 most recent videos discovered and imported from the Cracking the Cryptic YouTube channel uploads playlist.
                    </div>
                </div>
                <div>
                    <a href="{{ route('filament.admin.resources.videos.index') }}" class="ctc-action-btn">
                        View All Videos in Catalog &rarr;
                    </a>
                </div>
            </div>

            <hr class="ctc-hr" />

            @php
                $recentVideos = $this->getLastAddedVideos();
            @endphp

            @if ($recentVideos->isEmpty())
                <div style="padding: 2rem; text-align: center; color: #94a3b8; font-size: 0.875rem;">
                    No videos found in the database. Run the sync command to ingest initial videos.
                </div>
            @else
                <div class="ctc-table-wrapper">
                    <table class="ctc-recent-table">
                        <thead>
                            <tr>
                                <th style="width: 36%;">Video</th>
                                <th style="width: 12%;">Format</th>
                                <th style="width: 16%;">When Added</th>
                                <th style="width: 14%;">Published (YT)</th>
                                <th style="width: 12%;">Stats</th>
                                <th style="width: 10%; text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentVideos as $video)
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                            @if ($video->youtube_thumbnail_url)
                                                <img src="{{ $video->youtube_thumbnail_url }}" alt="{{ $video->title }}" class="ctc-video-thumb" loading="lazy" />
                                            @else
                                                <div class="ctc-video-thumb" style="display: flex; align-items: center; justify-content: center; font-size: 0.625rem; color: #64748b;">
                                                    No Image
                                                </div>
                                            @endif
                                            <div style="display: flex; flex-direction: column; gap: 0.25rem; min-width: 0;">
                                                <a href="{{ route('filament.admin.resources.videos.edit', ['record' => $video]) }}" class="ctc-video-title-link" title="{{ $video->title }}">
                                                    {{ $video->title }}
                                                </a>
                                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                    <span style="font-family: 'JetBrains Mono', monospace; font-size: 0.6875rem; color: #94a3b8;">
                                                        ID: {{ $video->youtube_video_id ?? 'N/A' }}
                                                    </span>
                                                    @if ($video->youtube_video_id)
                                                        <a href="https://www.youtube.com/watch?v={{ $video->youtube_video_id }}" target="_blank" rel="noopener noreferrer" style="color: #ef4444; font-size: 0.6875rem; text-decoration: none;" title="Watch on YouTube">
                                                            &#x25B6; YouTube
                                                        </a>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if ($video->video_type === 'youtube_short')
                                            <span class="ctc-badge ctc-badge-warning">Short</span>
                                        @elseif ($video->video_type === 'live_stream')
                                            <span class="ctc-badge ctc-badge-info">Live Stream</span>
                                        @else
                                            <span class="ctc-badge ctc-badge-secondary">Standard</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div style="display: flex; flex-direction: column; gap: 0.125rem;">
                                            <span style="font-weight: 600; color: #f8fafc; font-size: 0.75rem;">
                                                {{ $video->created_at ? $video->created_at->setTimezone('UTC')->format('M j, Y H:i') . ' UTC' : 'N/A' }}
                                            </span>
                                            <span style="font-size: 0.6875rem; color: #64748b;">
                                                {{ $video->created_at?->diffForHumans() ?? '' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display: flex; flex-direction: column; gap: 0.125rem;">
                                            <span style="color: #cbd5e1; font-size: 0.75rem;">
                                                {{ $video->published_at ? $video->published_at->setTimezone('UTC')->format('M j, Y') : 'N/A' }}
                                            </span>
                                            <span style="font-size: 0.6875rem; color: #64748b;">
                                                {{ $video->published_at?->diffForHumans() ?? '' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display: flex; flex-direction: column; gap: 0.125rem;">
                                            <span style="font-family: 'JetBrains Mono', monospace; font-size: 0.75rem; color: #34d399; font-weight: 600;">
                                                {{ number_format($video->view_count) }} views
                                            </span>
                                            <span style="font-family: 'JetBrains Mono', monospace; font-size: 0.6875rem; color: #94a3b8;">
                                                {{ $video->formatted_duration ?: '0:00' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td style="text-align: right;">
                                        <div style="display: inline-flex; align-items: center; gap: 0.375rem; justify-content: flex-end;">
                                            @if ($video->is_finalized)
                                                <span class="ctc-badge ctc-badge-success" title="Finalized and visible in public catalog">Finalized</span>
                                            @else
                                                <span class="ctc-badge ctc-badge-warning" title="In review queue">Review</span>
                                            @endif
                                            <a href="{{ route('filament.admin.resources.videos.edit', ['record' => $video]) }}" class="ctc-action-btn" title="Edit video metadata and puzzle associations">
                                                Edit
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Recent Execution Logs --}}
        <div class="ctc-recent-card">
            <div class="ctc-card-header">
                <div class="ctc-card-title-group">
                    <div class="ctc-card-title">
                        <span>Recent Script Execution Logs</span>
                        <span class="ctc-badge ctc-badge-success">Audit Trail</span>
                    </div>
                    <div style="font-size: 0.8125rem; color: #94a3b8;">
                        Detailed execution records from background 30-minute sync runs and manual invocations.
                    </div>
                </div>
            </div>

            <hr class="ctc-hr" />

            @php
                $recentLogs = $this->getRecentSyncLogs(10);
            @endphp

            @if ($recentLogs->isEmpty())
                <div style="padding: 2rem; text-align: center; color: #94a3b8; font-size: 0.875rem;">
                    No execution logs recorded yet. Logs will appear here automatically on the next scheduled run or when you click "Run Sync Now".
                </div>
            @else
                <div class="ctc-table-wrapper">
                    <table class="ctc-recent-table">
                        <thead>
                            <tr>
                                <th style="width: 12%;">Status</th>
                                <th style="width: 17%;">Started At</th>
                                <th style="width: 9%;">Duration</th>
                                <th style="width: 28%;">Impact Summary</th>
                                <th style="width: 34%;">Activity Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentLogs as $log)
                                <tr>
                                    <td>
                                        @if ($log->status === 'success')
                                            <span class="ctc-badge ctc-badge-success">Success</span>
                                        @elseif ($log->status === 'dry_run')
                                            <span class="ctc-badge ctc-badge-secondary">Dry Run</span>
                                        @elseif ($log->status === 'failed')
                                            <span class="ctc-badge ctc-badge-danger" style="background-color: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.35);">Failed</span>
                                        @else
                                            <span class="ctc-badge ctc-badge-warning">{{ ucfirst($log->status) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div style="display: flex; flex-direction: column; gap: 0.125rem;">
                                            <span style="font-weight: 600; color: #f8fafc; font-size: 0.75rem;">
                                                {{ $log->started_at ? $log->started_at->setTimezone('UTC')->format('M j, Y H:i:s') . ' UTC' : 'N/A' }}
                                            </span>
                                            <span style="font-size: 0.6875rem; color: #64748b;">
                                                {{ $log->started_at?->diffForHumans() ?? '' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <span style="font-family: 'JetBrains Mono', monospace; font-size: 0.75rem; color: #e2e8f0;">
                                            {{ $log->duration_ms !== null ? round($log->duration_ms / 1000, 2) . 's' : 'N/A' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; flex-direction: column; gap: 0.375rem;">
                                            <div style="display: flex; flex-wrap: wrap; gap: 0.375rem; align-items: center;">
                                                @php
                                                    $viewsAdded = $log->total_views_added;
                                                @endphp
                                                @if ($viewsAdded > 0)
                                                    <span class="ctc-badge ctc-badge-success" style="font-size: 0.6875rem;" title="Cumulative view count delta across all updated videos">
                                                        +{{ number_format($viewsAdded) }} Views Added
                                                    </span>
                                                @elseif ($viewsAdded < 0)
                                                    <span class="ctc-badge ctc-badge-danger" style="background-color: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.35); font-size: 0.6875rem;" title="Cumulative view count delta across all updated videos">
                                                        {{ number_format($viewsAdded) }} Views Added
                                                    </span>
                                                @else
                                                    <span class="ctc-badge ctc-badge-secondary" style="font-size: 0.6875rem;" title="Cumulative views added">
                                                        +0 Views Added
                                                    </span>
                                                @endif

                                                <span class="ctc-badge ctc-badge-warning" style="font-size: 0.6875rem;">
                                                    {{ $log->updated_views_count }} {{ \Illuminate\Support\Str::plural('Video', $log->updated_views_count) }} Updated
                                                </span>

                                                @if ($log->new_videos_count > 0)
                                                    <span class="ctc-badge ctc-badge-info" style="font-size: 0.6875rem;">
                                                        +{{ $log->new_videos_count }} New
                                                    </span>
                                                @endif
                                            </div>
                                            <div style="font-size: 0.6875rem; color: #64748b;">
                                                {{ $log->total_processed }} Total Processed
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                            <div style="font-size: 0.75rem; color: #cbd5e1; font-family: 'JetBrains Mono', monospace;">
                                                {{ $log->message }}
                                            </div>
                                            @if (!empty($log->details))
                                                @php
                                                    $detailsViews = $log->total_views_added;
                                                @endphp
                                                <details style="margin-top: 0.25rem; font-size: 0.6875rem; color: #94a3b8;">
                                                    <summary style="cursor: pointer; color: #38bdf8; font-weight: 600;">
                                                        View {{ count($log->details) }} Activity Details ({{ ($detailsViews >= 0 ? '+' : '') . number_format($detailsViews) }} views) &darr;
                                                    </summary>
                                                    <div style="margin-top: 0.375rem; max-height: 140px; overflow-y: auto; background-color: #020617; border: 1px solid #1e293b; border-radius: 0.375rem; padding: 0.5rem; display: flex; flex-direction: column; gap: 0.25rem;">
                                                        @foreach ($log->details as $item)
                                                            @if (($item['type'] ?? '') === 'new_video')
                                                                <div style="color: #34d399;">
                                                                    <strong>[New Video]</strong> {{ $item['title'] ?? '' }} (<code>{{ $item['video_id'] ?? '' }}</code>) &bull; {{ number_format($item['view_count'] ?? 0) }} views
                                                                </div>
                                                            @elseif (($item['type'] ?? '') === 'view_update')
                                                                <div style="color: #fbbf24;">
                                                                    <strong>[Views]</strong> {{ $item['title'] ?? '' }} &bull; {{ number_format($item['old_views'] ?? 0) }} &rarr; {{ number_format($item['new_views'] ?? 0) }} ({{ ($item['diff'] ?? 0) >= 0 ? '+' : '' }}{{ number_format($item['diff'] ?? 0) }})
                                                                </div>
                                                            @elseif (($item['type'] ?? '') === 'error')
                                                                <div style="color: #f87171;">
                                                                    <strong>[Error]</strong> {{ $item['message'] ?? '' }}
                                                                </div>
                                                            @else
                                                                <div style="color: #94a3b8;">
                                                                    {{ json_encode($item) }}
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                </details>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Script Detail Cards --}}
        <div style="display: flex; flex-direction: column; gap: 1.75rem;">
            @foreach ($this->getScripts() as $script)
                <div class="ctc-script-card">
                    {{-- 1. Header Row --}}
                    <div class="ctc-card-header">
                        <div class="ctc-card-title-group">
                            <div class="ctc-card-title">
                                <span>{{ $script['title'] }}</span>
                                @if ($script['status'] === 'active')
                                    <span class="ctc-badge ctc-badge-success">{{ $script['status_label'] }}</span>
                                @elseif ($script['status'] === 'warning')
                                    <span class="ctc-badge ctc-badge-warning">{{ $script['status_label'] }}</span>
                                @else
                                    <span class="ctc-badge ctc-badge-info">{{ $script['status_label'] }}</span>
                                @endif
                            </div>
                            <div>
                                <code class="ctc-command-badge">{{ $script['command'] }}</code>
                            </div>
                        </div>

                        <div>
                            <span class="ctc-freq-pill">Frequency: {{ $script['frequency'] }}</span>
                        </div>
                    </div>

                    <hr class="ctc-hr" />

                    {{-- 2. Purpose & Summary --}}
                    <div class="ctc-section-block">
                        <div class="ctc-section-heading">Purpose &amp; Summary</div>
                        <div class="ctc-summary-text">{{ $script['summary'] }}</div>
                    </div>

                    <hr class="ctc-hr" />

                    {{-- 3. Key Operational Behaviors (Bulleted List) --}}
                    <div class="ctc-section-block">
                        <div class="ctc-section-heading">Key Operational Behaviors</div>
                        <ul class="ctc-bullet-list">
                            @foreach ($script['key_behaviors'] as $behavior)
                                <li>{{ $behavior }}</li>
                            @endforeach
                        </ul>
                    </div>

                    <hr class="ctc-hr" />

                    {{-- 4. Scheduling & Quota Details --}}
                    <div class="ctc-two-col-grid">
                        <div class="ctc-info-box">
                            <div class="ctc-info-box-title">Execution &amp; Schedule Details</div>
                            <div class="ctc-info-box-body">{{ $script['schedule_details'] }}</div>
                            <div style="margin-top: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                                <span style="font-size: 0.75rem; color: #64748b;">Cron:</span>
                                <code class="ctc-cron-tag">{{ $script['schedule_expression'] }}</code>
                            </div>
                        </div>

                        <div class="ctc-info-box">
                            <div class="ctc-info-box-title">YouTube API Quota Impact</div>
                            <div class="ctc-info-box-body">{{ $script['quota_cost'] }}</div>
                        </div>
                    </div>

                    {{-- 5. CLI Options & Arguments --}}
                    @if (!empty($script['options']))
                        <hr class="ctc-hr" />

                        <div class="ctc-section-block">
                            <div class="ctc-section-heading">CLI Options &amp; Arguments</div>
                            <div class="ctc-table-wrapper">
                                <table class="ctc-options-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 25%;">Flag / Argument</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($script['options'] as $option)
                                            <tr>
                                                <td class="ctc-flag-code">{{ $option['flag'] }}</td>
                                                <td>{{ $option['description'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    <hr class="ctc-hr" />

                    {{-- 6. Example Command --}}
                    <div class="ctc-example-bar">
                        <span class="ctc-example-label">Example CLI Run:</span>
                        <code class="ctc-example-code">{{ $script['example_cli'] }}</code>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- YouTube Data API Quota Reference Card --}}
        <div class="ctc-quota-ref-card">
            <div style="font-size: 1rem; font-weight: 700; color: #ffffff;">
                YouTube Data API v3 Quota Reference
            </div>

            <hr class="ctc-hr" />

            <div class="ctc-quota-grid">
                @foreach ($this->getQuotaOverview() as $label => $value)
                    <div class="ctc-quota-subcard">
                        <div style="font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.05em;">
                            {{ str_replace('_', ' ', $label) }}
                        </div>
                        <div style="font-size: 0.8125rem; font-weight: 600; color: #f8fafc; font-family: 'JetBrains Mono', monospace;">
                            {{ $value }}
                        </div>
                    </div>
                @endforeach
            </div>

            <p style="font-size: 0.8125rem; line-height: 1.375rem; color: #94a3b8; margin: 0;">
                All YouTube operations in Cataloging the Cryptic batch IDs into multi-record requests (50 records per API call). This ensures rapid ingestion while consuming less than 1.2% of the daily 10,000 unit developer quota allowance.
            </p>
        </div>
    </div>
</x-filament-panels::page>
