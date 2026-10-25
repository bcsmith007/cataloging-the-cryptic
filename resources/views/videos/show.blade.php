@extends('layouts.app')

@section('title', $video->title . ' — Cataloging the Cryptic')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-10">

    {{-- Breadcrumb Navigation & Admin Edit Button --}}
    <div class="flex items-center justify-between gap-4 flex-wrap">
        <nav class="flex items-center space-x-2 text-xs text-slate-400 font-mono">
            <a href="{{ route('home') }}" class="hover:text-emerald-400 transition-colors">Home</a>
            <span>/</span>
            <a href="{{ route('videos.index') }}" class="hover:text-emerald-400 transition-colors">Videos</a>
            <span>/</span>
            <span class="text-slate-200 truncate max-w-md">{{ $video->title }}</span>
        </nav>
        @auth
            <a href="{{ route('filament.admin.resources.videos.edit', ['record' => $video]) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-800 hover:bg-emerald-600 text-slate-200 hover:text-white border border-slate-700 hover:border-emerald-500 transition-colors shadow-sm">
                <svg class="w-3.5 h-3.5 text-emerald-400 hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                <span>Edit in Admin</span>
            </a>
        @endauth
    </div>

    {{-- Main Video Header & Player Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <div class="lg:col-span-8 space-y-6">
            {{-- Video Player or Patreon Exclusive Thumbnail Card --}}
            @if (! $video->isExclusive() && $video->youtube_video_id)
                <div id="yt-player-container" class="relative w-full aspect-video rounded-2xl overflow-hidden shadow-2xl border border-slate-800 bg-slate-950 group">
                    {{-- Two-Click Privacy Façade --}}
                    <div id="yt-facade" class="relative w-full h-full cursor-pointer" onclick="loadYouTubePlayer()">
                        @if ($video->thumbnail_url)
                            <img
                                src="{{ $video->thumbnail_url }}"
                                alt="{{ $video->title }}"
                                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                            />
                        @else
                            <div class="w-full h-full bg-slate-900 flex items-center justify-center">
                                <span class="text-slate-600 font-mono text-xs">Video Thumbnail</span>
                            </div>
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/60 via-transparent to-transparent flex items-center justify-center">
                            <button
                                type="button"
                                class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-red-600/90 group-hover:bg-red-500 text-white flex items-center justify-center shadow-xl shadow-red-950/80 transition-all transform group-hover:scale-110"
                                aria-label="Play Video"
                            >
                                <svg class="w-8 h-8 sm:w-9 sm:h-9 translate-x-0.5 fill-current" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    {{-- Iframe Container Loaded On-Demand --}}
                    <div id="yt-iframe-wrapper" class="hidden absolute inset-0 w-full h-full"></div>
                </div>
            @elseif ($video->isExclusive())
                <div class="relative w-full aspect-video rounded-2xl overflow-hidden shadow-2xl border border-red-500/20 bg-slate-950 group">
                    @if ($video->thumbnail_url)
                        <img
                            src="{{ $video->thumbnail_url }}"
                            alt="{{ $video->title }}"
                            class="w-full h-full object-cover"
                        />
                    @endif
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/75 to-slate-950/30 flex flex-col items-center justify-center p-6 text-center space-y-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-500/20 text-red-400 border border-red-500/30 uppercase tracking-wider">
                            Cracking the Cryptic Patreon Exclusive
                        </span>
                        @if ($video->patreonPost)
                            <h2 class="text-xl sm:text-2xl font-bold text-white tracking-tight max-w-xl">
                                {{ $video->patreonPost->title }}
                            </h2>
                        @else
                            <h2 class="text-xl sm:text-2xl font-bold text-white tracking-tight max-w-xl">
                                {{ $video->title }}
                            </h2>
                        @endif
                        <p class="text-xs sm:text-sm text-slate-300 max-w-lg leading-relaxed">
                            This video is exclusive to Patreon supporters. Follow the link below to access the post on Patreon and watch the video.
                        </p>
                        @if ($video->getWatchUrl())
                            <a href="{{ $video->getWatchUrl() }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center space-x-2 px-6 py-3 rounded-xl bg-red-600 hover:bg-red-500 text-white text-xs sm:text-sm font-semibold tracking-wide transition-all shadow-lg shadow-red-950/60">
                                <span>Watch on Patreon &rarr;</span>
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Patreon Companion Callout Banner for Public Videos attached to a Patreon Post --}}
            @if (! $video->isExclusive() && $video->hasPatreonPost())
                <div class="rounded-2xl border border-indigo-500/20 bg-slate-900/60 p-5 sm:p-6 text-center space-y-2">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 uppercase tracking-wider">
                        Patreon Companion Video
                    </span>
                    @if ($video->patreonPost)
                        <h2 class="text-lg sm:text-xl font-bold text-white tracking-tight">
                            {{ $video->patreonPost->title }}
                        </h2>
                    @endif
                    <p class="text-xs sm:text-sm text-slate-300 max-w-xl mx-auto leading-relaxed">
                        This video is attached to an official Patreon publication.
                    </p>
                    @if ($video->patreonPost?->patreon_url)
                        <div class="pt-1">
                            <a href="{{ $video->patreonPost->patreon_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center space-x-2 px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold tracking-wide transition-all">
                                <span>Open Patreon Post &rarr;</span>
                            </a>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Title & Meta Bar --}}
            <div class="space-y-4">
                <div class="flex flex-wrap items-center gap-2">
                    @if ($video->isExclusive())
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-500/10 text-red-400 border border-red-500/20">
                            Patron Exclusive
                        </span>
                    @else
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            Public
                        </span>
                    @endif

                    @if ($video->isYouTubeShort())
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                            YouTube Short
                        </span>
                    @elseif ($video->isLiveStream())
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                            Live Stream
                        </span>
                    @else
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-800 text-slate-300 border border-slate-700">
                            YouTube
                        </span>
                    @endif

                    <div class="flex items-center space-x-2">
                        @foreach ($video->hosts as $host)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-800 text-slate-200 border border-slate-700">
                                {{ $host->name }}
                            </span>
                        @endforeach
                    </div>
                </div>

                <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight leading-snug">
                    {{ $video->title }}
                </h1>

                <div class="flex flex-wrap items-center gap-3 text-xs text-slate-400 font-mono pt-1">
                    <span class="flex items-center space-x-1.5 bg-slate-900 px-3 py-1.5 rounded-md border border-slate-800">
                        <span>Duration:</span>
                        <strong class="text-white">{{ $video->formatted_duration }}</strong>
                    </span>
                    <span class="flex items-center space-x-1.5 bg-slate-900 px-3 py-1.5 rounded-md border border-slate-800">
                        <span>Published:</span>
                        <strong class="text-white">@userDate($video->published_at)</strong>
                    </span>
                    @if ($video->view_count > 0)
                        <span class="flex items-center space-x-1.5 bg-slate-900 px-3 py-1.5 rounded-md border border-slate-800">
                            <span>Views:</span>
                            <strong class="text-white">{{ number_format($video->view_count) }}</strong>
                        </span>
                    @endif
                </div>

                @if ($video->intro_music || $video->outro_music)
                    <div class="pt-2 flex flex-wrap gap-2 text-xs text-slate-400">
                        @if ($video->intro_music)
                            <span class="inline-flex items-center px-3 py-1 rounded-md bg-slate-900 border border-slate-800 text-slate-300">
                                <span class="text-indigo-400 font-medium mr-1.5">Intro Music:</span> {{ $video->intro_music }}
                            </span>
                        @endif
                        @if ($video->outro_music)
                            <span class="inline-flex items-center px-3 py-1 rounded-md bg-slate-900 border border-slate-800 text-slate-300">
                                <span class="text-emerald-400 font-medium mr-1.5">Outro Music:</span> {{ $video->outro_music }}
                            </span>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Interactive Bookmarks & Timestamps Section --}}
            @if ($video->bookmarks->isNotEmpty())
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <div class="space-y-0.5">
                            <h2 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                                <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                                </svg>
                                <span>Video Bookmarks & Timestamps</span>
                                <span class="ml-1 px-2 py-0.5 rounded-full text-xs font-mono font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                    {{ $video->bookmarks->count() }}
                                </span>
                            </h2>
                            <p class="text-xs text-slate-400">
                                @if (! $video->isExclusive() && $video->youtube_video_id)
                                    Click any bookmark to jump directly to that moment without reloading the page.
                                @else
                                    Key moments and timestamps for this video.
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="space-y-2.5" id="bookmarks-list">
                        @foreach ($video->bookmarks as $bookmark)
                            @if (! $video->isExclusive() && $video->youtube_video_id)
                                <button
                                    type="button"
                                    onclick="seekTo({{ $bookmark->timestamp_seconds }}, this)"
                                    data-timestamp="{{ $bookmark->timestamp_seconds }}"
                                    class="bookmark-item w-full text-left p-3.5 rounded-xl border border-slate-800/80 bg-slate-950/40 hover:bg-slate-800/70 hover:border-emerald-500/40 transition-all duration-150 group cursor-pointer focus:outline-none focus:ring-2 focus:ring-emerald-500/40 flex items-start justify-between gap-4">
                                    <div class="space-y-1 min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="inline-flex items-center space-x-1.5 font-mono text-xs font-semibold px-2.5 py-1 rounded-md bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 group-hover:bg-emerald-600 group-hover:text-white group-hover:border-emerald-500 transition-colors">
                                                <svg class="w-3 h-3 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                                                </svg>
                                                <span>{{ $bookmark->formatted_timestamp }}</span>
                                            </span>

                                            <span class="text-sm font-semibold text-slate-100 group-hover:text-emerald-300 transition-colors truncate">
                                                {{ $bookmark->label }}
                                            </span>
                                        </div>
                                    </div>

                                    @if ($bookmark->puzzle)
                                        <span class="text-xs text-slate-400 group-hover:text-slate-200 bg-slate-900 px-2.5 py-1 rounded-md border border-slate-800 font-medium shrink-0 max-w-[200px] truncate">
                                            Puzzle: {{ $bookmark->puzzle->title }}
                                        </span>
                                    @endif
                                </button>
                            @else
                                <div
                                    class="w-full text-left p-3.5 rounded-xl border border-slate-800/80 bg-slate-950/40 flex items-start justify-between gap-4">
                                    <div class="space-y-1 min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-mono text-xs font-semibold px-2.5 py-1 rounded-md bg-slate-800 text-slate-300 border border-slate-700">
                                                {{ $bookmark->formatted_timestamp }}
                                            </span>

                                            <span class="text-sm font-semibold text-slate-100 truncate">
                                                {{ $bookmark->label }}
                                            </span>
                                        </div>
                                    </div>

                                    @if ($bookmark->puzzle)
                                        <span class="text-xs text-slate-400 bg-slate-900 px-2.5 py-1 rounded-md border border-slate-800 font-medium shrink-0 max-w-[200px] truncate">
                                            Puzzle: {{ $bookmark->puzzle->title }}
                                        </span>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Contained Puzzles Column --}}
        <div class="lg:col-span-4 space-y-6">
            <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h2 class="text-sm font-bold text-white uppercase tracking-wider">
                        Contained Puzzles ({{ $video->puzzles->count() }})
                    </h2>
                    <span class="text-xs text-slate-400 font-mono">Links & Rules</span>
                </div>

                <div class="space-y-4">
                    @forelse ($video->puzzles as $puzzle)
                        <div class="rounded-xl border border-slate-800 bg-slate-950/80 p-4 space-y-3">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <div class="flex items-center gap-2">
                                        @if ($video->puzzles->count() > 1 && $puzzle->pivot?->order_in_video)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-mono font-bold bg-slate-800 text-slate-300 border border-slate-700 shrink-0" title="Order in video">
                                                #{{ $puzzle->pivot->order_in_video }}
                                            </span>
                                        @endif
                                        <h3 class="text-base font-bold text-white">
                                            {{ $puzzle->title }}
                                        </h3>
                                    </div>
                                    <div class="text-xs text-slate-400 mt-0.5 flex flex-wrap items-center gap-2">
                                        <span>
                                            by
                                            <span class="text-slate-300 font-medium">
                                                {{ $puzzle->setters->pluck('name')->join(', ') ?: 'Unknown Setter' }}
                                            </span>
                                        </span>
                                        @if (! $video->isExclusive() && $video->youtube_video_id && $puzzle->pivot && $puzzle->pivot->start_seconds !== null)
                                            <button
                                                type="button"
                                                onclick="seekTo({{ $puzzle->pivot->start_seconds }})"
                                                class="inline-flex items-center space-x-1 font-mono text-[11px] font-semibold px-2 py-0.5 rounded bg-emerald-500/10 hover:bg-emerald-600 hover:text-white text-emerald-400 border border-emerald-500/20 hover:border-emerald-500 transition-colors"
                                                title="Jump to puzzle start in video">
                                                <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                                                </svg>
                                                <span>{{ \App\Models\Video::formatSecondsToHms($puzzle->pivot->start_seconds) }}</span>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5 shrink-0">
                                    @if ($puzzle->isExclusive())
                                        <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                            Patron Exclusive
                                        </span>
                                    @endif
                                    <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 shrink-0">
                                        {{ $puzzle->category->name }}
                                    </span>
                                </div>
                            </div>

                            @if ($puzzle->constraints->isNotEmpty())
                                <div class="flex flex-wrap gap-1.5 pt-1">
                                    @foreach ($puzzle->constraints->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE) as $constraint)
                                        <a href="{{ route('constraints.show', $constraint->slug) }}" class="px-2 py-0.5 rounded text-[10px] bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-emerald-400 border border-slate-700/60 transition-colors">
                                            {{ $constraint->name }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Puzzle External Solve / Platform Links --}}
                            @if ($puzzle->isExclusive())
                                <div class="pt-2">
                                    <div class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-400 text-xs font-semibold">
                                        <svg class="w-3.5 h-3.5 text-amber-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                        <span>Patron Exclusive Puzzle (Link available on Patreon)</span>
                                    </div>
                                </div>
                            @elseif ($puzzle->links->isNotEmpty())
                                <div class="flex flex-wrap gap-2 pt-2">
                                    @foreach ($puzzle->links as $link)
                                        <a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold tracking-wide transition-colors {{ $link->link_type === 'sudokupad' ? 'bg-emerald-600 hover:bg-emerald-500 text-white' : 'bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700' }}">
                                            <span>{{ $link->display_label }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Rules Drawer --}}
                            @if ($puzzle->rules_raw)
                                <details class="text-xs text-slate-400 pt-2 border-t border-slate-800/80 group">
                                    <summary class="cursor-pointer font-medium text-slate-300 hover:text-white transition-colors flex items-center justify-between py-1">
                                        <span>View Puzzle Rules</span>
                                        <span class="text-slate-500 group-open:rotate-180 transition-transform">&darr;</span>
                                    </summary>
                                    <div class="mt-2 p-3 rounded-lg bg-slate-900 border border-slate-800/60 leading-relaxed whitespace-pre-line text-slate-300">{{ trim($puzzle->rules_raw) }}</div>
                                </details>
                            @endif
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 italic">No puzzles attached to this video entry.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

@if (! $video->isExclusive() && $video->youtube_video_id)
<script>
    let ytPlayer = null;
    let isPlayerReady = false;
    let isPlayerLoaded = false;
    let pendingSeekSeconds = null;
    const embedBaseUrl = "https://www.youtube-nocookie.com/embed/{{ $video->youtube_video_id }}?enablejsapi=1&rel=0";
    const hostOrigin = @json(request()->getSchemeAndHttpHost());

    /**
     * Load the YouTube iframe player dynamically after affirmative user interaction.
     *
     * @param {number} startTime
     */
    function loadYouTubePlayer(startTime = 0) {
        if (isPlayerLoaded) {
            return;
        }
        isPlayerLoaded = true;
        pendingSeekSeconds = startTime;

        const facade = document.getElementById('yt-facade');
        const wrapper = document.getElementById('yt-iframe-wrapper');
        if (facade) {
            facade.classList.add('hidden');
        }
        if (wrapper) {
            wrapper.classList.remove('hidden');
        }

        const iframe = document.createElement('iframe');
        iframe.id = 'yt-player';
        iframe.className = 'absolute inset-0 w-full h-full';
        iframe.src = `${embedBaseUrl}&autoplay=1&start=${startTime}&origin=${encodeURIComponent(hostOrigin)}`;
        iframe.title = @json($video->title);
        iframe.frameBorder = '0';
        iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
        iframe.allowFullscreen = true;

        if (wrapper) {
            wrapper.innerHTML = '';
            wrapper.appendChild(iframe);
        }

        // Dynamically load YouTube API script only after user interaction
        if (!window.YT) {
            const tag = document.createElement('script');
            tag.src = 'https://www.youtube.com/iframe_api';
            const firstScriptTag = document.getElementsByTagName('script')[0];
            if (firstScriptTag && firstScriptTag.parentNode) {
                firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
            } else {
                document.head.appendChild(tag);
            }
        } else if (window.YT && window.YT.Player) {
            window.onYouTubeIframeAPIReady();
        }
    }

    /**
     * YouTube Iframe API ready callback.
     */
    window.onYouTubeIframeAPIReady = function() {
        const iframe = document.getElementById('yt-player');
        if (!iframe || !window.YT || !window.YT.Player) {
            return;
        }

        try {
            ytPlayer = new YT.Player('yt-player', {
                events: {
                    'onReady': function() {
                        isPlayerReady = true;
                        if (pendingSeekSeconds !== null && pendingSeekSeconds > 0) {
                            try {
                                ytPlayer.seekTo(pendingSeekSeconds, true);
                                ytPlayer.playVideo();
                            } catch (e) {}
                            pendingSeekSeconds = null;
                        }
                    }
                }
            });
        } catch (e) {
            console.warn('YouTube Iframe Player initialization error:', e);
        }
    };

    /**
     * Seek the embedded YouTube player to the specified timestamp without refreshing the page.
     *
     * @param {number} seconds
     * @param {HTMLElement|null} element
     */
    function seekTo(seconds, element = null) {
        seconds = Math.max(0, parseInt(seconds, 10) || 0);

        // If player has not been loaded by user yet, load it now starting at requested timestamp
        if (!isPlayerLoaded) {
            loadYouTubePlayer(seconds);
            if (window.history && window.history.replaceState) {
                window.history.replaceState(null, '', '#t=' + seconds);
            }
            highlightActiveBookmark(seconds, element);
            return;
        }

        const iframe = document.getElementById('yt-player');

        // 1. Primary: Use YouTube Iframe API player instance
        if (ytPlayer && typeof ytPlayer.seekTo === 'function') {
            try {
                ytPlayer.seekTo(seconds, true);
                if (typeof ytPlayer.playVideo === 'function') {
                    ytPlayer.playVideo();
                }
            } catch (err) {
                console.warn('YT.Player seekTo call failed, using fallback:', err);
            }
        }

        // 2. Secondary: Send postMessage commands to the iframe
        if (iframe && iframe.contentWindow) {
            try {
                iframe.contentWindow.postMessage(JSON.stringify({
                    event: 'command',
                    func: 'seekTo',
                    args: [seconds, true]
                }), '*');

                iframe.contentWindow.postMessage(JSON.stringify({
                    event: 'command',
                    func: 'playVideo',
                    args: []
                }), '*');
            } catch (err) {
                console.warn('postMessage command failed:', err);
            }
        }

        // 3. Fallback: If player not yet initialized or ready, update iframe src
        if (!isPlayerReady && (!ytPlayer || typeof ytPlayer.getPlayerState !== 'function')) {
            if (iframe) {
                try {
                    const currentSrc = iframe.getAttribute('src');
                    if (currentSrc) {
                        const url = new URL(currentSrc, window.location.href);
                        url.searchParams.set('start', seconds);
                        url.searchParams.set('autoplay', '1');
                        url.searchParams.set('enablejsapi', '1');
                        iframe.src = url.toString();
                    }
                } catch (err) {
                    // Ignore URL parsing errors
                }
            }
        }

        // 4. Update browser URL hash without reloading the page
        if (window.history && window.history.replaceState) {
            window.history.replaceState(null, '', '#t=' + seconds);
        }

        // 5. Smoothly scroll iframe into view if out of viewport
        const playerContainer = document.getElementById('yt-player-container') || iframe;
        if (playerContainer) {
            const rect = playerContainer.getBoundingClientRect();
            if (rect.top < 0 || rect.bottom > window.innerHeight) {
                playerContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        // 6. Highlight active bookmark
        highlightActiveBookmark(seconds, element);
    }

    /**
     * Apply active styling to the currently active bookmark.
     *
     * @param {number} seconds
     * @param {HTMLElement|null} element
     */
    function highlightActiveBookmark(seconds, element = null) {
        const bookmarks = document.querySelectorAll('.bookmark-item');
        bookmarks.forEach(item => {
            item.classList.remove('border-emerald-500/80', 'bg-emerald-500/10', 'ring-1', 'ring-emerald-500/30');
            item.classList.add('border-slate-800/80', 'bg-slate-950/40');
        });

        const target = element || document.querySelector(`.bookmark-item[data-timestamp="${seconds}"]`);
        if (target) {
            target.classList.remove('border-slate-800/80', 'bg-slate-950/40');
            target.classList.add('border-emerald-500/80', 'bg-emerald-500/10', 'ring-1', 'ring-emerald-500/30');
        }
    }

    /**
     * Check if a timestamp is specified in the URL hash and highlight it.
     */
    function checkInitialHashTime() {
        const hash = window.location.hash;
        const match = hash.match(/#t=(\d+)/) || window.location.search.match(/[?&]t=(\d+)/);
        if (match && match[1]) {
            const seconds = parseInt(match[1], 10);
            highlightActiveBookmark(seconds);
            const facade = document.getElementById('yt-facade');
            if (facade) {
                facade.setAttribute('onclick', `loadYouTubePlayer(${seconds})`);
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        checkInitialHashTime();
    });
</script>
@endif
@endsection
