@extends('layouts.app')

@section('title', 'Catalog Analytics & Trends — Cataloging the Cryptic')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-10">

    {{-- Breadcrumbs & Header --}}
    <div class="space-y-4 border-b border-slate-800 pb-6">
        <nav class="flex items-center space-x-2 text-xs text-slate-400">
            <a href="{{ route('home') }}" class="hover:text-emerald-400 transition-colors">Home</a>
            <span>/</span>
            <a href="{{ route('videos.index') }}" class="hover:text-emerald-400 transition-colors">Videos</a>
            <span>/</span>
            <span class="text-slate-200">Catalog Analytics & Trends</span>
        </nav>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-3xl font-extrabold text-white tracking-tight">
                        Catalog Analytics & Trends
                    </h1>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 font-mono">
                        {{ number_format($kpis['total_videos']) }} Videos
                    </span>
                </div>
                <p class="text-sm text-slate-400 mt-1 max-w-3xl">
                    Comprehensive visual analytics across 9+ years of Cracking the Cryptic history, tracking over {{ number_format($kpis['total_views']) }} views, format breakdowns, publishing patterns, and duration sweet spots.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('videos.top') }}" class="px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-800/80 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700/80 transition-all">
                    Most Viewed &rarr;
                </a>
                <a href="{{ route('videos.longest') }}" class="px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-800/80 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700/80 transition-all">
                    Longest &rarr;
                </a>
                <a href="{{ route('videos.shortest') }}" class="px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-800/80 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700/80 transition-all">
                    Shortest &rarr;
                </a>
            </div>
        </div>
    </div>

    {{-- Top KPI Cards & Key Highlights --}}
    <div class="space-y-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            {{-- Total Views --}}
            <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-5 shadow-lg relative overflow-hidden group hover:border-emerald-500/40 transition-colors">
                <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-emerald-500/5 rounded-full blur-xl group-hover:bg-emerald-500/10 transition-colors"></div>
                <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider font-mono">Total Views</div>
                <div class="text-3xl font-extrabold text-emerald-400 mt-2 tracking-tight font-mono">
                    {{ number_format($kpis['total_views']) }}
                </div>
                <div class="text-xs text-slate-400 mt-1">
                    Median {{ number_format($kpis['median_views']) }} views per video
                </div>
            </div>

            {{-- Total Videos --}}
            <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-5 shadow-lg relative overflow-hidden group hover:border-sky-500/40 transition-colors">
                <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-sky-500/5 rounded-full blur-xl group-hover:bg-sky-500/10 transition-colors"></div>
                <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider font-mono">Catalog Videos</div>
                <div class="text-3xl font-extrabold text-sky-400 mt-2 tracking-tight font-mono">
                    {{ number_format($kpis['total_videos']) }}
                </div>
                <div class="text-xs text-slate-400 mt-1">
                    {{ number_format($formatStats['youtube']['count']) }} standard &bull; {{ number_format($formatStats['youtube_short']['count']) }} shorts
                </div>
            </div>

            {{-- Total Footage Duration --}}
            <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-5 shadow-lg relative overflow-hidden group hover:border-purple-500/40 transition-colors">
                <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-purple-500/5 rounded-full blur-xl group-hover:bg-purple-500/10 transition-colors"></div>
                <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider font-mono">Solve Footage</div>
                <div class="text-3xl font-extrabold text-purple-400 mt-2 tracking-tight font-mono">
                    {{ number_format($kpis['total_hours']) }} hrs
                </div>
                <div class="text-xs text-slate-400 mt-1">
                    Equivalent to {{ number_format($kpis['total_days_footage'], 1) }} days of continuous solves
                </div>
            </div>

            {{-- Median Video Length --}}
            <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-5 shadow-lg relative overflow-hidden group hover:border-amber-500/40 transition-colors">
                <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-amber-500/5 rounded-full blur-xl group-hover:bg-amber-500/10 transition-colors"></div>
                <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider font-mono">Median Video Length</div>
                <div class="text-3xl font-extrabold text-amber-400 mt-2 tracking-tight font-mono">
                    {{ $kpis['median_duration_formatted'] }}
                </div>
                <div class="text-xs text-slate-400 mt-1">
                    Standard solves median {{ $formatStats['youtube']['median_duration_formatted'] }}
                </div>
            </div>
        </div>

        {{-- Row 2: Milestone & Record Highlights --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            {{-- Most Viewed Standard --}}
            @if (!empty($milestones['most_viewed_standard']))
                @php $v = $milestones['most_viewed_standard']; @endphp
                <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-5 shadow-lg flex flex-col justify-between hover:border-amber-500/40 transition-colors">
                    <div>
                        <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-amber-400 font-mono">
                            <span>Most Viewed Video</span>
                            <span>#1</span>
                        </div>
                        <a href="{{ route('videos.show', ['slug' => data_get($v, 'slug')]) }}" class="mt-2 block font-bold text-slate-100 hover:text-emerald-400 transition-colors text-sm line-clamp-2">
                            {{ data_get($v, 'title') }}
                        </a>
                        <div class="text-xs text-slate-400 mt-1">
                            Published {{ data_get($v, 'published_at') ? \App\Services\TimezoneService::format(data_get($v, 'published_at')) : 'N/A' }}
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between">
                        <span class="text-lg font-extrabold text-amber-400 font-mono">{{ number_format((int) data_get($v, 'view_count', 0)) }} views</span>
                        <a href="{{ route('videos.show', ['slug' => data_get($v, 'slug')]) }}" class="text-xs text-emerald-400 hover:underline">Watch &rarr;</a>
                    </div>
                </div>
            @endif

            {{-- Most Viewed Short --}}
            @if (!empty($milestones['most_viewed_short']))
                @php $v = $milestones['most_viewed_short']; @endphp
                <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-5 shadow-lg flex flex-col justify-between hover:border-sky-500/40 transition-colors">
                    <div>
                        <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-sky-400 font-mono">
                            <span>Most Viewed Short</span>
                            <span>#1 Short</span>
                        </div>
                        <a href="{{ route('videos.show', ['slug' => data_get($v, 'slug')]) }}" class="mt-2 block font-bold text-slate-100 hover:text-emerald-400 transition-colors text-sm line-clamp-2">
                            {{ data_get($v, 'title') }}
                        </a>
                        <div class="text-xs text-slate-400 mt-1">
                            Published {{ data_get($v, 'published_at') ? \App\Services\TimezoneService::format(data_get($v, 'published_at')) : 'N/A' }}
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between">
                        <span class="text-lg font-extrabold text-sky-400 font-mono">{{ number_format((int) data_get($v, 'view_count', 0)) }} views</span>
                        <a href="{{ route('videos.show', ['slug' => data_get($v, 'slug')]) }}" class="text-xs text-emerald-400 hover:underline">Watch &rarr;</a>
                    </div>
                </div>
            @endif

            {{-- Longest Video --}}
            @if (!empty($milestones['longest_solve']))
                @php $v = $milestones['longest_solve']; @endphp
                <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-5 shadow-lg flex flex-col justify-between hover:border-purple-500/40 transition-colors">
                    <div>
                        <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-purple-400 font-mono">
                            <span>Longest Solve</span>
                            <span>Marathon</span>
                        </div>
                        <a href="{{ route('videos.show', ['slug' => data_get($v, 'slug')]) }}" class="mt-2 block font-bold text-slate-100 hover:text-emerald-400 transition-colors text-sm line-clamp-2">
                            {{ data_get($v, 'title') }}
                        </a>
                        <div class="text-xs text-slate-400 mt-1">
                            Duration: {{ data_get($v, 'duration_formatted', '0s') }} ({{ data_get($v, 'duration_hours', 0) }} hours)
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between">
                        <span class="text-sm font-semibold text-slate-300 font-mono">{{ number_format((int) data_get($v, 'view_count', 0)) }} views</span>
                        <a href="{{ route('videos.show', ['slug' => data_get($v, 'slug')]) }}" class="text-xs text-emerald-400 hover:underline">Watch &rarr;</a>
                    </div>
                </div>
            @endif

            {{-- Peak Records --}}
            <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-5 shadow-lg flex flex-col justify-between hover:border-emerald-500/40 transition-colors">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wider text-emerald-400 font-mono">
                        Channel Peak Months
                    </div>
                    <div class="mt-3 space-y-3 text-xs">
                        <div>
                            <span class="text-slate-400 block">Record Viewership Month:</span>
                            <span class="font-bold text-slate-200 block text-sm mt-0.5">{{ $milestones['peak_views_month'] ?? 'May 2020' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block">Record Upload Month:</span>
                            <span class="font-bold text-slate-200 block text-sm mt-0.5">{{ $milestones['peak_upload_month'] ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-800/80 text-right">
                    <a href="{{ route('videos.index') }}" class="text-xs text-emerald-400 hover:underline">Explore Catalog &rarr;</a>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Section 1: Views & Upload Timeline (Interactive Chart) --}}
    <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-slate-800/80 pb-4">
            <div>
                <h2 class="text-xl font-bold text-white tracking-tight flex items-center gap-2">
                    Release Volume & Viewership Trends Over Time
                </h2>
                <p class="text-xs text-slate-400 mt-1">
                    Explore aggregate view counts, release volume, and median viewership across channel history.
                </p>
            </div>

            {{-- Controls / Toggles --}}
            <div class="flex flex-wrap items-center gap-2">
                {{-- Granularity: Year vs Month --}}
                <div class="inline-flex rounded-lg bg-slate-950 p-1 border border-slate-800 text-xs font-medium">
                    <button id="btn-period-year" type="button" class="px-3 py-1.5 rounded-md transition-colors bg-emerald-500/20 text-emerald-400 font-semibold" onclick="setTimePeriod('year')">
                        By Year (All-Time)
                    </button>
                    <button id="btn-period-month" type="button" class="px-3 py-1.5 rounded-md transition-colors text-slate-400 hover:text-white" onclick="setTimePeriod('month')">
                        By Month (Last 36 Months)
                    </button>
                </div>

                {{-- Metric Switcher --}}
                <div class="inline-flex rounded-lg bg-slate-950 p-1 border border-slate-800 text-xs font-medium">
                    <button id="btn-metric-views" type="button" class="px-3 py-1.5 rounded-md transition-colors bg-sky-500/20 text-sky-400 font-semibold" onclick="setMetric('views')">
                        Total Views
                    </button>
                    <button id="btn-metric-count" type="button" class="px-3 py-1.5 rounded-md transition-colors text-slate-400 hover:text-white" onclick="setMetric('count')">
                        Uploads Count
                    </button>
                    <button id="btn-metric-avg" type="button" class="px-3 py-1.5 rounded-md transition-colors text-slate-400 hover:text-white" onclick="setMetric('avg')">
                        Median Views / Video
                    </button>
                </div>
            </div>
        </div>

        {{-- Canvas Container --}}
        <div class="relative w-full" style="height: 380px;">
            <canvas id="timelineChart"></canvas>
        </div>

        {{-- Explanatory Notes --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2 text-xs text-slate-400 border-t border-slate-800/80">
            <div>
                <strong class="text-slate-200">Historical Peak:</strong> 2020 was the breakthrough year with over 58.4 million views during the Miracle Sudoku boom.
            </div>
            <div>
                <strong class="text-slate-200">Sustained Output:</strong> Since 2022, Cracking the Cryptic has consistently published over 1,100 videos annually (~3 videos/day).
            </div>
            <div>
                <strong class="text-slate-200">Median Duration Growth:</strong> Video solve lengths expanded from ~17 mins in 2017 to a steady 30–35 minutes in modern uploads.
            </div>
        </div>
    </div>

    {{-- Main Section 2: Format & Duration Analysis (2-column grid) --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {{-- Card 1: Format Breakdown (Standard vs Shorts vs Live) --}}
        <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-6 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
                    <h3 class="text-lg font-bold text-white tracking-tight">
                        Video Format Breakdown
                    </h3>
                    <span class="text-xs font-mono text-slate-400">Standard &bull; Shorts &bull; Live</span>
                </div>
                <p class="text-xs text-slate-400 mt-2">
                    Proportion of catalog uploads and total views contributed by each video format.
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-center mt-4">
                    <div class="relative w-full" style="height: 220px;">
                        <canvas id="formatDonutChart"></canvas>
                    </div>

                    <div class="space-y-3">
                        @foreach ($formatStats as $f)
                            <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-semibold text-slate-200">{{ $f['label'] }}</span>
                                    <span class="text-xs font-mono text-emerald-400">{{ $f['percentage'] }}% of catalog</span>
                                </div>
                                <div class="grid grid-cols-2 gap-2 mt-2 pt-2 border-t border-slate-800/60 text-xs">
                                    <div>
                                        <span class="text-slate-400">Total Count:</span>
                                        <span class="font-mono text-slate-200 block font-semibold">{{ number_format($f['count']) }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400">Median Duration:</span>
                                        <span class="font-mono text-slate-200 block font-semibold">{{ $f['median_duration_formatted'] }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400">Total Views:</span>
                                        <span class="font-mono text-slate-200 block font-semibold">{{ number_format($f['total_views']) }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400">Median Views:</span>
                                        <span class="font-mono text-slate-200 block font-semibold">{{ number_format($f['median_views']) }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="p-3 rounded-xl bg-slate-950/40 border border-slate-800 text-xs text-slate-400">
                <strong class="text-slate-300">Observation:</strong> {{ $observations['format'] ?? ("Standard solves generate " . ($formatStats['youtube']['views_percentage'] ?? 0) . "% of total channel views, while Shorts provide rapid bite-sized puzzles.") }}
            </div>
        </div>

        {{-- Card 2: Duration Sweet Spot Analysis --}}
        <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-6 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
                    <h3 class="text-lg font-bold text-white tracking-tight">
                        Duration Sweet Spot Analysis
                    </h3>
                    <span class="text-xs font-mono text-amber-400">Median Views by Length</span>
                </div>
                <p class="text-xs text-slate-400 mt-2">
                    How solve length influences typical viewership engagement across the catalog.
                </p>

                <div class="relative w-full mt-4" style="height: 220px;">
                    <canvas id="durationBracketChart"></canvas>
                </div>

                {{-- Bracket Grid --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mt-4 text-xs">
                    @foreach ($durationBrackets as $bracket)
                        <div class="p-2.5 rounded-lg bg-slate-950/60 border border-slate-800">
                            <div class="font-semibold text-slate-300 truncate" title="{{ $bracket['bracket'] }}">{{ $bracket['bracket'] }}</div>
                            <div class="text-slate-400 mt-1">{{ number_format($bracket['count']) }} videos ({{ $bracket['percentage'] }}%)</div>
                            <div class="text-amber-400 font-mono font-semibold mt-0.5">~{{ number_format($bracket['median_views']) }} median views</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="p-3 rounded-xl bg-slate-950/40 border border-slate-800 text-xs text-slate-400">
                <strong class="text-slate-300">Engagement Takeaway:</strong> {{ $observations['duration'] ?? 'Longer solves achieve peak median viewership across standard solves in the catalog.' }}
            </div>
        </div>
    </div>

    {{-- Main Section: Uploads by Puzzle Category --}}
    <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-800/80 pb-4">
            <div>
                <h3 class="text-lg font-bold text-white tracking-tight flex items-center gap-2">
                    Total Uploads by Puzzle Category
                </h3>
                <p class="text-xs text-slate-400 mt-1">
                    Multi-puzzle videos count each attached puzzle individually as of that video's date. Videos pending puzzle indexing are grouped as Unclassified.
                </p>
            </div>

            {{-- Controls / Toggles --}}
            <div class="flex flex-wrap items-center gap-2">
                <div class="inline-flex rounded-lg bg-slate-950 p-1 border border-slate-800 text-xs font-medium">
                    <button id="btn-cat-all" type="button" class="px-3 py-1.5 rounded-md transition-colors bg-emerald-500/20 text-emerald-400 font-semibold" onclick="setCategoryFilter('all')">
                        All Categories (inc. Unclassified)
                    </button>
                    <button id="btn-cat-classified" type="button" class="px-3 py-1.5 rounded-md transition-colors text-slate-400 hover:text-white" onclick="setCategoryFilter('classified')">
                        Classified Only
                    </button>
                    <button id="btn-cat-timeline" type="button" class="px-3 py-1.5 rounded-md transition-colors text-slate-400 hover:text-white" onclick="setCategoryFilter('timeline')">
                        Timeline by Year
                    </button>
                    <button id="btn-cat-monthly" type="button" class="px-3 py-1.5 rounded-md transition-colors text-slate-400 hover:text-white" onclick="setCategoryFilter('monthly')">
                        Monthly
                    </button>
                </div>

                {{-- Monthly 12-Month Window Navigation (active when Monthly is selected) --}}
                <div id="cat-monthly-nav" class="hidden items-center gap-1 bg-slate-950 p-1 rounded-lg border border-slate-800 text-xs font-mono">
                    <button id="btn-cat-prev-month" type="button" class="p-1.5 rounded-md hover:bg-slate-800 text-slate-300 disabled:opacity-30 disabled:cursor-not-allowed transition-colors" title="Previous 12 months (shift back 1 month)" onclick="shiftMonthlyWindow(-1)">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                    </button>
                    <span id="cat-monthly-range-label" class="px-2 text-slate-300 font-semibold select-none min-w-[130px] text-center text-[11px]">
                        --
                    </span>
                    <button id="btn-cat-next-month" type="button" class="p-1.5 rounded-md hover:bg-slate-800 text-slate-300 disabled:opacity-30 disabled:cursor-not-allowed transition-colors" title="Next 12 months (shift forward 1 month)" onclick="shiftMonthlyWindow(1)">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Bar Chart Canvas --}}
        <div class="relative w-full" style="height: 300px;">
            <canvas id="categoryChart"></canvas>
        </div>

        {{-- Category Breakdown Cards / Grid --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3 pt-2 border-t border-slate-800/80 text-xs">
            @foreach ($categoryStats as $cat)
                <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800 flex flex-col justify-between">
                    <div>
                        <div class="font-bold text-slate-200 truncate" title="{{ $cat['name'] }}">{{ $cat['name'] }}</div>
                        <div class="text-slate-400 mt-1 font-mono">{{ number_format($cat['count']) }} uploads</div>
                        @if (!$cat['is_unclassified'])
                            <div class="text-slate-400 font-mono text-[11px]">{{ $cat['distinct_videos'] }} videos</div>
                        @endif
                    </div>
                    <div class="mt-2 pt-2 border-t border-slate-800/60">
                        <span class="text-emerald-400 font-mono font-semibold">{{ $cat['percentage'] }}%</span>
                        @if ($cat['median_views'] > 0)
                            <div class="text-[11px] text-slate-400 font-mono">~{{ number_format($cat['median_views'] / 1000, 1) }}k median</div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Main Section: Variant Sudoku Constraints Breakdown (Pie Chart Tile) --}}
    <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-800/80 pb-4">
            <div>
                <h3 class="text-lg font-bold text-white tracking-tight flex items-center gap-2">
                    Variant Sudoku Constraints Breakdown
                </h3>
                <p class="text-xs text-slate-400 mt-1">
                    Frequency of rulesets and puzzle constraints (e.g., Kropki, German Whispers, Arrow, Thermo) featured across Variant Sudoku solves.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 font-mono">
                    {{ count($constraintStats ?? []) }} Unique Constraints
                </span>
                <span class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-sky-500/10 text-sky-400 border border-sky-500/30 font-mono">
                    {{ $totalConstraintOccurrences ?? 0 }} Tagged Rulesets
                </span>
            </div>
        </div>

        @if (!empty($constraintStats))
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                {{-- Left: Pie Chart --}}
                <div class="lg:col-span-5 flex flex-col items-center justify-center">
                    <div class="relative w-full max-w-[340px]" style="height: 320px;">
                        <canvas id="constraintPieChart"></canvas>
                    </div>
                    <div class="text-[11px] text-slate-400 mt-3 font-mono text-center">
                        Multi-constraint puzzles count each applied rule individually
                    </div>
                </div>

                {{-- Right: Constraint List / Grid with Color Badges and Percentages --}}
                <div class="lg:col-span-7">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 max-h-[360px] overflow-y-auto pr-1">
                        @foreach ($constraintStats as $index => $c)
                            <div class="p-2.5 rounded-xl bg-slate-950/60 border border-slate-800/80 hover:border-slate-700 transition-colors flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2.5 min-w-0 pr-2">
                                    <span class="w-3 h-3 rounded-full shrink-0 constraint-dot" data-index="{{ $index }}"></span>
                                    <a href="{{ route('constraints.show', $c['slug']) }}" class="font-semibold text-slate-200 hover:text-emerald-400 transition-colors truncate" title="{{ $c['name'] }}">{{ $c['name'] }}</a>
                                </div>
                                <div class="flex items-center gap-3 shrink-0 font-mono">
                                    <span class="text-slate-400 text-[11px]">{{ $c['count'] }} {{ $c['count'] === 1 ? 'puzzle' : 'puzzles' }}</span>
                                    <span class="text-emerald-400 font-semibold w-12 text-right">{{ $c['percentage'] }}%</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="p-3 rounded-xl bg-slate-950/40 border border-slate-800 text-xs text-slate-400">
                <strong class="text-slate-300">Observation:</strong> {{ $observations['constraints'] ?? 'Line-based and clue marker constraints represent the most prolific variant mechanics solved by Simon and Mark.' }}
            </div>
        @else
            <div class="p-8 text-center text-slate-400 text-xs">
                No Variant Sudoku constraints indexed yet.
            </div>
        @endif
    </div>

    {{-- Main Section: Music Selections Breakdown (Intro & Outro Pie Charts) --}}
    <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-800/80 pb-4">
            <div>
                <h3 class="text-lg font-bold text-white tracking-tight flex items-center gap-2">
                    Intro & Outro Music Selections
                </h3>
                <p class="text-xs text-slate-400 mt-1">
                    Distribution of intro and outro themes across videos with indexed audio tracks. Untagged videos are excluded.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 font-mono">
                    {{ $musicStats['total_intro_tagged'] ?? 0 }} Tagged Intros
                </span>
                <span class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-sky-500/10 text-sky-400 border border-sky-500/30 font-mono">
                    {{ $musicStats['total_outro_tagged'] ?? 0 }} Tagged Outros
                </span>
            </div>
        </div>

        @if (!empty($musicStats['intro']) || !empty($musicStats['outro']))
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                {{-- Left: Intro Music Pie Chart --}}
                <div class="lg:col-span-4 flex flex-col items-center justify-center">
                    <div class="text-xs font-semibold text-slate-300 mb-2 uppercase tracking-wider font-mono flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        Intro Music
                    </div>
                    @if (!empty($musicStats['intro']))
                        <div class="relative w-full max-w-[280px]" style="height: 260px;">
                            <canvas id="introMusicChart"></canvas>
                        </div>
                        <div class="text-[11px] text-slate-400 mt-2 font-mono text-center">
                            {{ number_format($musicStats['total_intro_tagged']) }} tagged videos ({{ count($musicStats['intro']) }} {{ count($musicStats['intro']) === 1 ? 'track' : 'tracks' }})
                        </div>
                    @else
                        <div class="p-8 text-center text-slate-400 text-xs">
                            No intro music tagged yet.
                        </div>
                    @endif
                </div>

                {{-- Middle: Common Shared Legend --}}
                <div class="lg:col-span-4 border-y lg:border-y-0 lg:border-x border-slate-800/80 py-4 lg:py-0 lg:px-6">
                    <div class="text-xs font-semibold text-slate-300 mb-3 text-center uppercase tracking-wider font-mono">
                        Music Selections Legend
                    </div>
                    @if (!empty($musicStats['legend']))
                        <div class="space-y-2.5 max-h-[320px] overflow-y-auto pr-1">
                            @foreach ($musicStats['legend'] as $item)
                                <div class="p-2.5 rounded-xl bg-slate-950/60 border border-slate-800/80 hover:border-slate-700 transition-colors">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="w-3 h-3 rounded-full shrink-0" style="background-color: {{ $item['color'] }};"></span>
                                        <span class="font-semibold text-slate-200 text-xs truncate" title="{{ $item['track'] }}">
                                            {{ $item['track'] }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between text-[11px] font-mono text-slate-400 mt-2 pt-1.5 border-t border-slate-800/60">
                                        <div>
                                            <span class="text-slate-400">Intro:</span>
                                            <span class="text-slate-200 font-semibold">{{ $item['intro_count'] }}</span>
                                            @if ($item['intro_count'] > 0)
                                                <span class="text-emerald-400 font-semibold">({{ $item['intro_percentage'] }}%)</span>
                                            @endif
                                        </div>
                                        <div>
                                            <span class="text-slate-400">Outro:</span>
                                            <span class="text-slate-200 font-semibold">{{ $item['outro_count'] }}</span>
                                            @if ($item['outro_count'] > 0)
                                                <span class="text-sky-400 font-semibold">({{ $item['outro_percentage'] }}%)</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-6 text-center text-slate-400 text-xs">
                            No music data available.
                        </div>
                    @endif
                </div>

                {{-- Right: Outro Music Pie Chart --}}
                <div class="lg:col-span-4 flex flex-col items-center justify-center">
                    <div class="text-xs font-semibold text-slate-300 mb-2 uppercase tracking-wider font-mono flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-sky-400"></span>
                        Outro Music
                    </div>
                    @if (!empty($musicStats['outro']))
                        <div class="relative w-full max-w-[280px]" style="height: 260px;">
                            <canvas id="outroMusicChart"></canvas>
                        </div>
                        <div class="text-[11px] text-slate-400 mt-2 font-mono text-center">
                            {{ number_format($musicStats['total_outro_tagged']) }} tagged videos ({{ count($musicStats['outro']) }} {{ count($musicStats['outro']) === 1 ? 'track' : 'tracks' }})
                        </div>
                    @else
                        <div class="p-8 text-center text-slate-400 text-xs">
                            No outro music tagged yet.
                        </div>
                    @endif
                </div>
            </div>
        @else
            <div class="p-8 text-center text-slate-400 text-xs">
                No intro or outro music indexed in the catalog yet.
            </div>
        @endif
    </div>

    {{-- Main Section 3: Publishing Cadence & Day of Week --}}
    <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-800/80 pb-4">
            <div>
                <h3 class="text-lg font-bold text-white tracking-tight">
                    Publishing Cadence & Day-of-Week Performance
                </h3>
                <p class="text-xs text-slate-400 mt-1">
                    Upload frequency across the week compared with median views per video release.
                </p>
            </div>
            <div class="text-xs text-slate-400 font-mono">
                Evaluated in UTC
            </div>
        </div>

        <div class="relative w-full" style="height: 260px;">
            <canvas id="dayOfWeekChart"></canvas>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-7 gap-2 pt-2 border-t border-slate-800/80 text-xs">
            @foreach ($dayOfWeek as $d)
                <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800 text-center">
                    <div class="font-bold text-slate-200">{{ $d['day'] }}</div>
                    <div class="text-slate-400 mt-1">{{ number_format($d['count']) }} uploads</div>
                    <div class="text-emerald-400 font-mono font-semibold mt-1">{{ number_format($d['median_views']) }} median</div>
                </div>
            @endforeach
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Inject Chart data from server
    const yearlyData = @json($yearly);
    const monthlyData = @json($monthly);
    const formatData = @json(array_values($formatStats));
    const durationData = @json($durationBrackets);
    const dayOfWeekData = @json($dayOfWeek);
    const categoryData = @json($categoryStats ?? []);
    const categoryYearlyData = @json($categoryYearly ?? []);
    const categoryMonthlyData = @json($categoryMonthly ?? []);
    const constraintData = @json($constraintStats ?? []);
    const totalConstraintCount = {{ (int) ($totalConstraintOccurrences ?? 0) }};

    let currentPeriod = 'year';
    let currentMetric = 'views';
    let timelineChart = null;

    // Chart.js default dark theme colors
    Chart.defaults.color = '#94a3b8';
    Chart.defaults.font.family = '"Plus Jakarta Sans", sans-serif';

    // -------------------------------------------------------------
    // Chart 1: Timeline Chart (Yearly vs Monthly)
    // -------------------------------------------------------------
    function renderTimelineChart() {
        const dataSet = (currentPeriod === 'year') ? yearlyData : monthlyData;
        const labels = dataSet.map(d => d.year || d.label);

        let dataValues = [];
        let labelName = '';
        let colorBorder = '#10b981';
        let colorBg = 'rgba(16, 185, 129, 0.15)';
        let isCurrency = false;

        if (currentMetric === 'views') {
            dataValues = dataSet.map(d => d.views);
            labelName = 'Total Views';
            colorBorder = '#0ea5e9';
            colorBg = 'rgba(14, 165, 233, 0.2)';
        } else if (currentMetric === 'count') {
            dataValues = dataSet.map(d => d.count);
            labelName = 'Videos Uploaded';
            colorBorder = '#10b981';
            colorBg = 'rgba(16, 185, 129, 0.25)';
        } else if (currentMetric === 'avg') {
            dataValues = dataSet.map(d => d.median_views ?? d.avg_views);
            labelName = 'Median Views / Video';
            colorBorder = '#f59e0b';
            colorBg = 'rgba(245, 158, 11, 0.2)';
        }

        const ctx = document.getElementById('timelineChart').getContext('2d');

        if (timelineChart) {
            timelineChart.destroy();
        }

        timelineChart = new Chart(ctx, {
            type: currentPeriod === 'year' ? 'bar' : 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: labelName,
                    data: dataValues,
                    borderColor: colorBorder,
                    backgroundColor: colorBg,
                    borderWidth: 2,
                    fill: currentPeriod === 'month',
                    tension: 0.35,
                    borderRadius: currentPeriod === 'year' ? 6 : 0,
                    pointBackgroundColor: colorBorder,
                    pointRadius: currentPeriod === 'year' ? 0 : 3,
                    pointHoverRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        backgroundColor: '#020617',
                        borderColor: '#1e293b',
                        borderWidth: 1,
                        titleColor: '#f8fafc',
                        bodyColor: '#cbd5e1',
                        padding: 10,
                        callbacks: {
                            label: function (context) {
                                const val = Number(context.raw) || 0;
                                return ' ' + labelName + ': ' + val.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: 'rgba(51, 65, 85, 0.25)',
                        },
                        ticks: {
                            color: '#94a3b8',
                            font: { size: 11 },
                            maxRotation: currentPeriod === 'month' ? 45 : 0,
                        }
                    },
                    y: {
                        grid: {
                            color: 'rgba(51, 65, 85, 0.25)',
                        },
                        ticks: {
                            color: '#94a3b8',
                            font: { size: 11 },
                            callback: function (value) {
                                if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M';
                                if (value >= 1000) return (value / 1000).toFixed(0) + 'k';
                                return value;
                            }
                        }
                    }
                }
            }
        });
    }

    // Toggle Handlers
    window.setTimePeriod = function (period) {
        currentPeriod = period;
        document.getElementById('btn-period-year').className = (period === 'year')
            ? 'px-3 py-1.5 rounded-md transition-colors bg-emerald-500/20 text-emerald-400 font-semibold'
            : 'px-3 py-1.5 rounded-md transition-colors text-slate-400 hover:text-white';
        document.getElementById('btn-period-month').className = (period === 'month')
            ? 'px-3 py-1.5 rounded-md transition-colors bg-emerald-500/20 text-emerald-400 font-semibold'
            : 'px-3 py-1.5 rounded-md transition-colors text-slate-400 hover:text-white';
        renderTimelineChart();
    };

    window.setMetric = function (metric) {
        currentMetric = metric;
        ['views', 'count', 'avg'].forEach(m => {
            const btn = document.getElementById('btn-metric-' + m);
            if (m === metric) {
                btn.className = 'px-3 py-1.5 rounded-md transition-colors bg-sky-500/20 text-sky-400 font-semibold';
            } else {
                btn.className = 'px-3 py-1.5 rounded-md transition-colors text-slate-400 hover:text-white';
            }
        });
        renderTimelineChart();
    };

    renderTimelineChart();

    // -------------------------------------------------------------
    // Chart 2: Format Donut Chart
    // -------------------------------------------------------------
    const formatCtx = document.getElementById('formatDonutChart').getContext('2d');
    new Chart(formatCtx, {
        type: 'doughnut',
        data: {
            labels: formatData.map(f => f.label),
            datasets: [{
                data: formatData.map(f => f.count),
                backgroundColor: [
                    '#10b981', // emerald
                    '#f59e0b', // amber
                    '#0ea5e9', // sky
                ],
                borderColor: '#020617',
                borderWidth: 3,
                hoverOffset: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        padding: 12,
                        color: '#cbd5e1',
                        font: { size: 11 }
                    }
                },
                tooltip: {
                    backgroundColor: '#020617',
                    borderColor: '#1e293b',
                    borderWidth: 1,
                    callbacks: {
                        label: function (ctx) {
                            const val = ctx.raw || 0;
                            const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                            const pct = ((val / total) * 100).toFixed(1);
                            return ` ${ctx.label}: ${val.toLocaleString()} (${pct}%)`;
                        }
                    }
                }
            }
        }
    });

    // -------------------------------------------------------------
    // Chart 3: Duration Bracket Chart (The Sweet Spot)
    // -------------------------------------------------------------
    const durationCtx = document.getElementById('durationBracketChart').getContext('2d');
    new Chart(durationCtx, {
        type: 'bar',
        data: {
            labels: durationData.map(d => d.bracket),
            datasets: [{
                label: 'Median Views',
                data: durationData.map(d => d.median_views ?? d.avg_views),
                backgroundColor: [
                    'rgba(245, 158, 11, 0.65)', // Shorts (Amber)
                    'rgba(14, 165, 233, 0.5)',   // < 15 mins (Sky)
                    'rgba(16, 185, 129, 0.75)',  // Highest: 15-30m (Emerald)
                    'rgba(14, 165, 233, 0.5)',   // 30-45m
                    'rgba(245, 158, 11, 0.6)',   // 45-60m
                    'rgba(168, 85, 247, 0.65)',  // > 60m (Purple)
                ],
                borderColor: [
                    '#f59e0b',
                    '#0ea5e9',
                    '#10b981',
                    '#0ea5e9',
                    '#f59e0b',
                    '#a855f7',
                ],
                borderWidth: 1.5,
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#020617',
                    borderColor: '#1e293b',
                    borderWidth: 1,
                    callbacks: {
                        label: function (ctx) {
                            return ' Median Views: ' + (ctx.raw || 0).toLocaleString();
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 10 } }
                },
                y: {
                    grid: { color: 'rgba(51, 65, 85, 0.25)' },
                    ticks: {
                        callback: val => (val / 1000) + 'k'
                    }
                }
            }
        }
    });

    // -------------------------------------------------------------
    // Chart 4: Day of Week Chart
    // -------------------------------------------------------------
    const dowCtx = document.getElementById('dayOfWeekChart').getContext('2d');
    new Chart(dowCtx, {
        type: 'bar',
        data: {
            labels: dayOfWeekData.map(d => d.day),
            datasets: [
                {
                    label: 'Videos Uploaded',
                    data: dayOfWeekData.map(d => d.count),
                    backgroundColor: 'rgba(14, 165, 233, 0.6)',
                    borderColor: '#0ea5e9',
                    borderWidth: 1,
                    borderRadius: 4,
                    yAxisID: 'y',
                },
                {
                    label: 'Median Views per Video',
                    data: dayOfWeekData.map(d => d.median_views ?? d.avg_views),
                    type: 'line',
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2,
                    tension: 0.3,
                    pointBackgroundColor: '#10b981',
                    yAxisID: 'y1',
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'top',
                    align: 'end',
                    labels: {
                        boxWidth: 12,
                        font: { size: 11 }
                    }
                },
                tooltip: {
                    backgroundColor: '#020617',
                    borderColor: '#1e293b',
                    borderWidth: 1,
                    callbacks: {
                        label: function (ctx) {
                            return ' ' + ctx.dataset.label + ': ' + (ctx.raw || 0).toLocaleString();
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 11 } }
                },
                y: {
                    type: 'linear',
                    position: 'left',
                    grid: { color: 'rgba(51, 65, 85, 0.25)' },
                    title: { display: true, text: 'Total Uploads', color: '#94a3b8', font: { size: 11 } },
                    ticks: { font: { size: 10 } }
                },
                y1: {
                    type: 'linear',
                    position: 'right',
                    grid: { drawOnChartArea: false },
                    title: { display: true, text: 'Median Views', color: '#10b981', font: { size: 11 } },
                    ticks: {
                        font: { size: 10 },
                        callback: val => (val / 1000) + 'k'
                    }
                }
            }
        }
    });

    // -------------------------------------------------------------
    // Chart: Puzzle Categories Bar Chart
    // -------------------------------------------------------------
    const categoryColors = {
        'Variant Sudoku': '#10b981',
        'Wordle': '#22c55e',
        'GAS': '#f59e0b',
        'Classic Sudoku': '#0ea5e9',
        'Cryptic Crossword': '#8b5cf6',
        'Crossword': '#6366f1',
        'Unclassified': '#64748b',
    };

    let currentCategoryFilter = 'all';
    let categoryChart = null;

    // Monthly navigation state: sliding window of 12 months
    const MONTHLY_WINDOW_SIZE = 12;
    let monthlyWindowEnd = categoryMonthlyData.length;

    function updateMonthlyNavControls() {
        const prevBtn = document.getElementById('btn-cat-prev-month');
        const nextBtn = document.getElementById('btn-cat-next-month');
        const rangeLabel = document.getElementById('cat-monthly-range-label');

        if (!rangeLabel) return;

        const totalMonths = categoryMonthlyData.length;
        if (totalMonths === 0) {
            rangeLabel.textContent = 'No data';
            if (prevBtn) prevBtn.disabled = true;
            if (nextBtn) nextBtn.disabled = true;
            return;
        }

        const startIdx = Math.max(0, monthlyWindowEnd - MONTHLY_WINDOW_SIZE);
        const endIdx = monthlyWindowEnd;
        const currentSlice = categoryMonthlyData.slice(startIdx, endIdx);

        if (currentSlice.length === 0) {
            rangeLabel.textContent = 'No data';
            if (prevBtn) prevBtn.disabled = true;
            if (nextBtn) nextBtn.disabled = true;
            return;
        }

        const firstLabel = currentSlice[0].label;
        const lastLabel = currentSlice[currentSlice.length - 1].label;
        rangeLabel.textContent = (firstLabel === lastLabel) ? firstLabel : `${firstLabel} – ${lastLabel}`;

        if (prevBtn) {
            prevBtn.disabled = (startIdx <= 0);
        }
        if (nextBtn) {
            nextBtn.disabled = (monthlyWindowEnd >= totalMonths);
        }
    }

    window.shiftMonthlyWindow = function (step) {
        const totalMonths = categoryMonthlyData.length;
        if (totalMonths === 0) return;

        const minEnd = Math.min(MONTHLY_WINDOW_SIZE, totalMonths);
        const newEnd = monthlyWindowEnd + step;

        if (newEnd < minEnd || newEnd > totalMonths) {
            return;
        }

        monthlyWindowEnd = newEnd;
        updateMonthlyNavControls();
        renderCategoryChart();
    };

    function renderCategoryChart() {
        const ctx = document.getElementById('categoryChart').getContext('2d');

        if (categoryChart) {
            categoryChart.destroy();
        }

        if (currentCategoryFilter === 'monthly') {
            const totalMonths = categoryMonthlyData.length;
            const startIdx = Math.max(0, monthlyWindowEnd - MONTHLY_WINDOW_SIZE);
            const endIdx = monthlyWindowEnd;
            const currentSlice = categoryMonthlyData.slice(startIdx, endIdx);

            const monthLabels = currentSlice.map(d => d.label);
            const categoryNames = categoryData.map(c => c.name);

            const datasets = categoryNames.map(catName => {
                const color = categoryColors[catName] || '#38bdf8';
                return {
                    label: catName,
                    data: currentSlice.map(d => (d.categories && d.categories[catName]) ? d.categories[catName] : 0),
                    backgroundColor: color,
                    borderColor: color,
                    borderWidth: 1,
                    borderRadius: 2,
                    stack: 'categoriesStack',
                };
            });

            categoryChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: monthLabels,
                    datasets: datasets,
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            align: 'end',
                            labels: {
                                boxWidth: 12,
                                color: '#cbd5e1',
                                font: { size: 11 }
                            }
                        },
                        tooltip: {
                            backgroundColor: '#020617',
                            borderColor: '#1e293b',
                            borderWidth: 1,
                            filter: function (tooltipItem) {
                                return (Number(tooltipItem.raw) || 0) > 0;
                            },
                            callbacks: {
                                label: function (ctx) {
                                    const val = ctx.raw || 0;
                                    if (val <= 0) return null;
                                    return ' ' + ctx.dataset.label + ': ' + val.toLocaleString() + ' uploads';
                                },
                                footer: function (items) {
                                    const total = items.reduce((sum, item) => sum + (item.raw || 0), 0);
                                    return total > 0 ? ' Total: ' + total.toLocaleString() + ' uploads' : '';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: {
                                font: { size: 11 },
                                maxRotation: 25,
                            }
                        },
                        y: {
                            stacked: true,
                            grid: { color: 'rgba(51, 65, 85, 0.25)' },
                            title: { display: true, text: 'Uploads as of Video Date', color: '#94a3b8', font: { size: 11 } },
                            ticks: { font: { size: 10 } }
                        }
                    }
                }
            });

            updateMonthlyNavControls();
            return;
        }

        if (currentCategoryFilter === 'timeline') {
            // Stacked Bar Chart by Year
            const years = categoryYearlyData.map(d => d.year);
            const categoryNames = categoryData.map(c => c.name);

            const datasets = categoryNames.map(catName => {
                const color = categoryColors[catName] || '#38bdf8';
                return {
                    label: catName,
                    data: categoryYearlyData.map(d => (d.categories && d.categories[catName]) ? d.categories[catName] : 0),
                    backgroundColor: color,
                    borderColor: color,
                    borderWidth: 1,
                    borderRadius: 2,
                    stack: 'categoriesStack',
                };
            });

            categoryChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: years,
                    datasets: datasets,
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            align: 'end',
                            labels: {
                                boxWidth: 12,
                                color: '#cbd5e1',
                                font: { size: 11 }
                            }
                        },
                        tooltip: {
                            backgroundColor: '#020617',
                            borderColor: '#1e293b',
                            borderWidth: 1,
                            filter: function (tooltipItem) {
                                return (Number(tooltipItem.raw) || 0) > 0;
                            },
                            callbacks: {
                                label: function (ctx) {
                                    const val = ctx.raw || 0;
                                    if (val <= 0) return null;
                                    return ' ' + ctx.dataset.label + ': ' + val.toLocaleString() + ' uploads';
                                },
                                footer: function (items) {
                                    const total = items.reduce((sum, item) => sum + (item.raw || 0), 0);
                                    return total > 0 ? ' Total: ' + total.toLocaleString() + ' uploads' : '';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 11 } }
                        },
                        y: {
                            stacked: true,
                            grid: { color: 'rgba(51, 65, 85, 0.25)' },
                            title: { display: true, text: 'Uploads as of Video Date', color: '#94a3b8', font: { size: 11 } },
                            ticks: { font: { size: 10 } }
                        }
                    }
                }
            });
        } else {
            // Standard Bar Chart (All or Classified Only)
            const filtered = (currentCategoryFilter === 'classified')
                ? categoryData.filter(c => !c.is_unclassified)
                : categoryData;

            const labels = filtered.map(c => c.name);
            const counts = filtered.map(c => c.count);
            const bgColors = filtered.map(c => categoryColors[c.name] || '#38bdf8');

            categoryChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Uploads',
                        data: counts,
                        backgroundColor: bgColors,
                        borderColor: bgColors,
                        borderWidth: 1,
                        borderRadius: 6,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#020617',
                            borderColor: '#1e293b',
                            borderWidth: 1,
                            filter: function (tooltipItem) {
                                return (Number(tooltipItem.raw) || 0) > 0;
                            },
                            callbacks: {
                                label: function (ctx) {
                                    const item = filtered[ctx.dataIndex];
                                    const val = ctx.raw || 0;
                                    if (val <= 0) return null;
                                    const videosText = item.is_unclassified
                                        ? ''
                                        : ' (' + item.distinct_videos.toLocaleString() + ' distinct videos)';
                                    return ' ' + val.toLocaleString() + ' uploads' + videosText;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: {
                                font: { size: 11 },
                                maxRotation: 25,
                            }
                        },
                        y: {
                            grid: { color: 'rgba(51, 65, 85, 0.25)' },
                            title: { display: true, text: 'Total Uploads', color: '#94a3b8', font: { size: 11 } },
                            ticks: {
                                font: { size: 10 },
                                callback: val => (val >= 1000 ? (val / 1000).toFixed(0) + 'k' : val)
                            }
                        }
                    }
                }
            });
        }
    }

    window.setCategoryFilter = function (filter) {
        currentCategoryFilter = filter;
        ['all', 'classified', 'timeline', 'monthly'].forEach(f => {
            const btn = document.getElementById('btn-cat-' + f);
            if (btn) {
                if (f === filter) {
                    btn.className = 'px-3 py-1.5 rounded-md transition-colors bg-emerald-500/20 text-emerald-400 font-semibold';
                } else {
                    btn.className = 'px-3 py-1.5 rounded-md transition-colors text-slate-400 hover:text-white';
                }
            }
        });

        const monthlyNav = document.getElementById('cat-monthly-nav');
        if (monthlyNav) {
            if (filter === 'monthly') {
                monthlyNav.classList.remove('hidden');
                monthlyNav.classList.add('flex');
            } else {
                monthlyNav.classList.add('hidden');
                monthlyNav.classList.remove('flex');
            }
        }

        renderCategoryChart();
    };

    document.addEventListener('keydown', function (e) {
        if (currentCategoryFilter === 'monthly') {
            if (e.key === 'ArrowLeft') {
                window.shiftMonthlyWindow(-1);
            } else if (e.key === 'ArrowRight') {
                window.shiftMonthlyWindow(1);
            }
        }
    });

    renderCategoryChart();

    // -------------------------------------------------------------
    // Chart: Variant Sudoku Constraints Pie Chart
    // -------------------------------------------------------------
    const constraintColors = [
        '#10b981', // emerald
        '#0ea5e9', // sky
        '#f59e0b', // amber
        '#8b5cf6', // purple
        '#ec4899', // pink
        '#06b6d4', // cyan
        '#f97316', // orange
        '#14b8a6', // teal
        '#a855f7', // violet
        '#eab308', // yellow
        '#6366f1', // indigo
        '#84cc16', // lime
        '#d946ef', // fuchsia
        '#3b82f6', // blue
        '#ef4444', // red
        '#64748b', // slate
    ];

    if (constraintData && constraintData.length > 0) {
        // Color the dot badges in the list
        document.querySelectorAll('.constraint-dot').forEach(dot => {
            const idx = parseInt(dot.getAttribute('data-index'), 10) || 0;
            dot.style.backgroundColor = constraintColors[idx % constraintColors.length];
        });

        const constraintCanvas = document.getElementById('constraintPieChart');
        if (constraintCanvas) {
            const constraintCtx = constraintCanvas.getContext('2d');
            const bgSliceColors = constraintData.map((c, i) => constraintColors[i % constraintColors.length]);

            new Chart(constraintCtx, {
                type: 'pie',
                data: {
                    labels: constraintData.map(c => c.name),
                    datasets: [{
                        data: constraintData.map(c => c.count),
                        backgroundColor: bgSliceColors,
                        borderColor: '#020617',
                        borderWidth: 2,
                        hoverOffset: 8,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false,
                        },
                        tooltip: {
                            backgroundColor: '#020617',
                            borderColor: '#1e293b',
                            borderWidth: 1,
                            titleColor: '#f8fafc',
                            bodyColor: '#cbd5e1',
                            padding: 10,
                            callbacks: {
                                label: function (ctx) {
                                    const val = ctx.raw || 0;
                                    const pct = totalConstraintCount > 0 ? ((val / totalConstraintCount) * 100).toFixed(1) : 0;
                                    return ` ${ctx.label}: ${val} ${val === 1 ? 'puzzle' : 'puzzles'} (${pct}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    // -----------------------------------------------------------------
    // Music Selections Pie Charts (Intro & Outro with Shared Palette)
    // -----------------------------------------------------------------
    const musicStats = @json($musicStats ?? ['intro' => [], 'outro' => []]);

    const introCanvas = document.getElementById('introMusicChart');
    if (introCanvas && musicStats.intro && musicStats.intro.length > 0) {
        const introCtx = introCanvas.getContext('2d');
        new Chart(introCtx, {
            type: 'pie',
            data: {
                labels: musicStats.intro.map(item => item.track),
                datasets: [{
                    data: musicStats.intro.map(item => item.count),
                    backgroundColor: musicStats.intro.map(item => item.color),
                    borderColor: '#020617',
                    borderWidth: 2,
                    hoverOffset: 8,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        backgroundColor: '#020617',
                        borderColor: '#1e293b',
                        borderWidth: 1,
                        titleColor: '#f8fafc',
                        bodyColor: '#cbd5e1',
                        padding: 10,
                        callbacks: {
                            label: function (ctx) {
                                const val = ctx.raw || 0;
                                const item = musicStats.intro[ctx.dataIndex];
                                const pct = item ? item.percentage : 0;
                                return ` ${ctx.label}: ${val} ${val === 1 ? 'video' : 'videos'} (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    const outroCanvas = document.getElementById('outroMusicChart');
    if (outroCanvas && musicStats.outro && musicStats.outro.length > 0) {
        const outroCtx = outroCanvas.getContext('2d');
        new Chart(outroCtx, {
            type: 'pie',
            data: {
                labels: musicStats.outro.map(item => item.track),
                datasets: [{
                    data: musicStats.outro.map(item => item.count),
                    backgroundColor: musicStats.outro.map(item => item.color),
                    borderColor: '#020617',
                    borderWidth: 2,
                    hoverOffset: 8,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        backgroundColor: '#020617',
                        borderColor: '#1e293b',
                        borderWidth: 1,
                        titleColor: '#f8fafc',
                        bodyColor: '#cbd5e1',
                        padding: 10,
                        callbacks: {
                            label: function (ctx) {
                                const val = ctx.raw || 0;
                                const item = musicStats.outro[ctx.dataIndex];
                                const pct = item ? item.percentage : 0;
                                return ` ${ctx.label}: ${val} ${val === 1 ? 'video' : 'videos'} (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
@endsection
