@extends('layouts.app')

@section('title', 'Cataloging the Cryptic — Home')

@section('content')
<div class="space-y-16 py-10">

    {{-- 1. Featured Hero Section --}}
    @if ($featuredVideo)
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/60 backdrop-blur-sm p-6 sm:p-8 lg:p-10 shadow-2xl">
                <div class="flex items-center space-x-3 text-xs font-semibold tracking-wider uppercase mb-4">
                    @if ($featuredVideo->isExclusive())
                        <span class="px-2.5 py-1 rounded-full bg-red-500/10 text-red-400 border border-red-500/20">
                            Patron Exclusive
                        </span>
                    @else
                        <span class="px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            Public
                        </span>
                    @endif

                    @if ($featuredVideo->isYouTubeShort())
                        <span class="px-2.5 py-1 rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/20">
                            YouTube Short
                        </span>
                    @elseif ($featuredVideo->isLiveStream())
                        <span class="px-2.5 py-1 rounded-full bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                            Live Stream
                        </span>
                    @else
                        <span class="px-2.5 py-1 rounded-full bg-slate-800 text-slate-300 border border-slate-700">
                            YouTube
                        </span>
                    @endif
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                    <div class="lg:col-span-7 space-y-4">
                        {{-- Video Player or Patreon Thumbnail --}}
                        @if (! $featuredVideo->isExclusive() && $featuredVideo->youtube_video_id)
                            <div class="relative w-full aspect-video rounded-xl overflow-hidden shadow-2xl border border-slate-800 bg-slate-950 mb-4 group">
                                <div id="hero-yt-facade" class="relative w-full h-full cursor-pointer" onclick="loadHeroYouTubePlayer()">
                                    @if ($featuredVideo->thumbnail_url)
                                        <img
                                            src="{{ $featuredVideo->thumbnail_url }}"
                                            alt="{{ $featuredVideo->title }}"
                                            class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                                            loading="lazy"
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
                                <div id="hero-yt-iframe-wrapper" class="hidden absolute inset-0 w-full h-full"></div>
                            </div>
                        @elseif ($featuredVideo->isExclusive())
                            <div class="relative w-full aspect-video rounded-xl overflow-hidden shadow-2xl border border-red-500/20 bg-slate-950 mb-4 group">
                                @if ($featuredVideo->thumbnail_url)
                                    <img
                                        src="{{ $featuredVideo->thumbnail_url }}"
                                        alt="{{ $featuredVideo->title }}"
                                        class="w-full h-full object-cover"
                                    />
                                @endif
                                <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/60 to-transparent flex flex-col items-center justify-center p-6 text-center space-y-3">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-500/20 text-red-400 border border-red-500/30 uppercase tracking-wider">
                                        Patreon Exclusive
                                    </span>
                                    <p class="text-xs text-slate-300 max-w-md">
                                        This video is hosted exclusively on Patreon for supporters.
                                    </p>
                                    @if ($featuredVideo->getWatchUrl())
                                        <a href="{{ $featuredVideo->getWatchUrl() }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center space-x-2 px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white text-xs font-semibold tracking-wide transition-all shadow-lg shadow-red-950/60">
                                            <span>Watch on Patreon &rarr;</span>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <h1 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-white tracking-tight leading-tight">
                            <a href="{{ route('videos.show', $featuredVideo->slug) }}" class="hover:text-emerald-400 transition-colors">
                                {{ $featuredVideo->title }}
                            </a>
                        </h1>

                        <div class="flex flex-wrap items-center gap-3 text-xs text-slate-400 font-mono">
                            <span class="flex items-center space-x-1.5 bg-slate-800/80 px-2.5 py-1 rounded-md border border-slate-700/50">
                                <span>Duration:</span>
                                <strong class="text-white">{{ $featuredVideo->formatted_duration }}</strong>
                            </span>
                            <span class="flex items-center space-x-1.5 bg-slate-800/80 px-2.5 py-1 rounded-md border border-slate-700/50">
                                <span>Published:</span>
                                <strong class="text-white">@userDate($featuredVideo->published_at)</strong>
                            </span>
                            @if ($featuredVideo->view_count > 0)
                                <span class="flex items-center space-x-1.5 bg-slate-800/80 px-2.5 py-1 rounded-md border border-slate-700/50">
                                    <span>Views:</span>
                                    <strong class="text-white">{{ number_format($featuredVideo->view_count) }}</strong>
                                </span>
                            @endif
                        </div>

                        {{-- Hosts --}}
                        <div class="flex items-center space-x-2 pt-1">
                            <span class="text-xs font-semibold text-slate-400">Hosts:</span>
                            @foreach ($featuredVideo->hosts as $host)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-800 text-slate-200 border border-slate-700">
                                    {{ $host->name }}
                                </span>
                            @endforeach
                        </div>

                        @if ($featuredVideo->intro_music || $featuredVideo->outro_music)
                            <div class="pt-2 flex flex-wrap gap-2 text-xs text-slate-400">
                                @if ($featuredVideo->intro_music)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-slate-800/80 border border-slate-700/60 text-slate-300">
                                        <span class="text-indigo-400 font-medium mr-1.5">Intro:</span> {{ $featuredVideo->intro_music }}
                                    </span>
                                @endif
                                @if ($featuredVideo->outro_music)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-slate-800/80 border border-slate-700/60 text-slate-300">
                                        <span class="text-emerald-400 font-medium mr-1.5">Outro:</span> {{ $featuredVideo->outro_music }}
                                    </span>
                                @endif
                            </div>
                        @endif

                        <div class="pt-2 flex flex-wrap gap-2.5">
                            <a href="{{ route('videos.show', $featuredVideo->slug) }}" class="inline-flex items-center space-x-2 px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold tracking-wide transition-colors">
                                <span>View Video Details &amp; Timestamps &rarr;</span>
                            </a>
                            @if (($featuredVideo->isExclusive() || $featuredVideo->hasPatreonPost()) && $featuredVideo->getWatchUrl())
                                <a href="{{ $featuredVideo->getWatchUrl() }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center space-x-2 px-4 py-2 rounded-lg bg-red-600 hover:bg-red-500 text-white text-xs font-semibold tracking-wide transition-colors">
                                    <span>Open Patreon Post &rarr;</span>
                                </a>
                            @endif
                        </div>
                    </div>

                    {{-- Featured Linked Puzzles --}}
                    <div class="lg:col-span-5 bg-slate-950/60 rounded-xl p-5 border border-slate-800/80 space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                            <h2 class="text-xs font-bold text-slate-300 tracking-wider uppercase">
                                Contained Puzzles ({{ $featuredVideo->puzzles->count() }})
                            </h2>
                            <span class="text-[11px] text-slate-500 font-mono">Solve Links</span>
                        </div>

                        <div class="space-y-3">
                            @forelse ($featuredVideo->puzzles as $puzzle)
                                <div class="p-3.5 rounded-lg bg-slate-900/90 border border-slate-800 space-y-2">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <div class="flex items-center gap-2">
                                                @if ($featuredVideo->puzzles->count() > 1 && $puzzle->pivot?->order_in_video)
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-slate-800 text-slate-300 border border-slate-700 shrink-0" title="Order in video">
                                                        #{{ $puzzle->pivot->order_in_video }}
                                                    </span>
                                                @endif
                                                <h3 class="text-sm font-semibold text-white">
                                                    {{ $puzzle->title }}
                                                </h3>
                                            </div>
                                            <div class="text-xs text-slate-400 mt-0.5">
                                                by
                                                <span class="text-slate-300 font-medium">
                                                    {{ $puzzle->setters->pluck('name')->join(', ') ?: 'Unknown Setter' }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1.5 shrink-0">
                                            @if ($puzzle->isExclusive())
                                                <span class="px-2 py-0.5 rounded text-[11px] font-medium bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                                    Patron Exclusive
                                                </span>
                                            @endif
                                            <span class="px-2 py-0.5 rounded text-[11px] font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                                {{ $puzzle->category->name }}
                                            </span>
                                        </div>
                                    </div>

                                    @if ($puzzle->constraints->isNotEmpty())
                                        <div class="flex flex-wrap gap-1.5 pt-1">
                                            @foreach ($puzzle->constraints->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE) as $constraint)
                                                <a href="{{ route('constraints.show', $constraint->slug) }}" class="px-1.5 py-0.5 rounded text-[10px] bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-emerald-400 border border-slate-700/60 transition-colors">
                                                    {{ $constraint->name }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if ($puzzle->isExclusive())
                                        <div class="pt-2">
                                            <span class="inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-md bg-amber-500/10 border border-amber-500/20 text-amber-400 text-xs font-medium">
                                                <svg class="w-3.5 h-3.5 text-amber-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                </svg>
                                                <span>Patron Exclusive Puzzle</span>
                                            </span>
                                        </div>
                                    @elseif ($puzzle->links->isNotEmpty())
                                        <div class="flex flex-wrap gap-2 pt-2">
                                            @foreach ($puzzle->links as $link)
                                                <a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-md text-xs font-medium tracking-wide transition-colors {{ $link->link_type === 'sudokupad' ? 'bg-emerald-600 hover:bg-emerald-500 text-white' : 'bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700' }}">
                                                    <span>{{ $link->display_label }}</span>
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <p class="text-xs text-slate-500 italic">No puzzles attached to this video entry.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- 2. Dynamic Stats Bar Section --}}
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 lg:gap-6">
            <a href="{{ route('videos.index') }}" class="group rounded-xl border border-slate-800 bg-slate-900/40 hover:bg-slate-900/70 hover:border-emerald-500/50 p-6 flex flex-col items-center text-center transition-all duration-200">
                <span class="text-3xl sm:text-4xl font-extrabold text-white group-hover:text-emerald-400 font-mono tracking-tight transition-colors">
                    {{ number_format($stats['total_videos']) }}
                </span>
                <span class="mt-1 text-xs font-semibold uppercase tracking-wider text-slate-400 group-hover:text-slate-300 transition-colors flex items-center gap-1">
                    <span>Total Cataloged Videos</span>
                    <svg class="w-3.5 h-3.5 text-slate-500 group-hover:text-emerald-400 group-hover:translate-x-0.5 transition-all" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </span>
            </a>

            <div class="rounded-xl border border-slate-800 bg-slate-900/40 p-6 flex flex-col items-center text-center">
                <span class="text-3xl sm:text-4xl font-extrabold text-emerald-400 font-mono tracking-tight">
                    {{ number_format($stats['total_puzzles']) }}
                </span>
                <span class="mt-1 text-xs font-semibold uppercase tracking-wider text-slate-400">
                    Total Puzzles Indexed
                </span>
            </div>

            <a href="{{ route('setters.index') }}" class="group rounded-xl border border-slate-800 bg-slate-900/40 hover:bg-slate-900/70 hover:border-emerald-500/50 p-6 flex flex-col items-center text-center transition-all duration-200">
                <span class="text-3xl sm:text-4xl font-extrabold text-white group-hover:text-emerald-400 font-mono tracking-tight transition-colors">
                    {{ number_format($stats['total_setters']) }}
                </span>
                <span class="mt-1 text-xs font-semibold uppercase tracking-wider text-slate-400 group-hover:text-slate-300 transition-colors flex items-center gap-1">
                    <span>Distinct Setters</span>
                    <svg class="w-3.5 h-3.5 text-slate-500 group-hover:text-emerald-400 group-hover:translate-x-0.5 transition-all" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </span>
            </a>
        </div>
    </section>

    {{-- 3. Latest Videos Feed --}}
    <section id="latest-videos" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="flex items-center justify-between border-b border-slate-800 pb-4">
            <div>
                <h2 class="text-xl font-bold text-white tracking-tight">
                    Recent Videos
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">
                    Browse newly cataloged deep-dives, compilations, and exclusive masterclasses.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse ($recentVideos as $video)
                <article class="flex flex-col justify-between rounded-xl transition-all duration-200 overflow-hidden group {{ $video->is_finalized ? 'border border-emerald-500/40 ring-1 ring-emerald-500/20 shadow-[0_0_15px_rgba(16,185,129,0.15)] hover:border-emerald-400/60 hover:shadow-[0_0_20px_rgba(16,185,129,0.25)] bg-slate-900/70' : 'border border-slate-800 bg-slate-900/60 hover:border-slate-700 shadow-lg shadow-black/20' }}" data-finalized="{{ $video->is_finalized ? 'true' : 'false' }}">
                    {{-- Thumbnail if available --}}
                    @if ($video->thumbnail_url)
                        <a href="{{ route('videos.show', $video->slug) }}" class="block relative aspect-video w-full overflow-hidden bg-slate-950">
                            <img
                                src="{{ $video->thumbnail_url }}"
                                alt="{{ $video->title }}"
                                loading="lazy"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                            />
                            <div class="absolute bottom-2 right-2 px-2 py-0.5 rounded bg-black/80 backdrop-blur-sm text-[11px] font-mono text-white">
                                {{ $video->formatted_duration }}
                            </div>
                        </a>
                    @endif

                    <div class="p-5 space-y-4 flex-1 flex flex-col justify-between">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    @if ($video->isExclusive())
                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-red-500/10 text-red-400 border border-red-500/20">
                                            Patron Exclusive
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                            Public
                                        </span>
                                    @endif

                                    @if ($video->isYouTubeShort())
                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                            Short
                                        </span>
                                    @elseif ($video->isLiveStream())
                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                            Live
                                        </span>
                                    @endif
                                </div>

                                @if (! $video->thumbnail_url)
                                    <span class="text-[11px] text-slate-500 font-mono">
                                        {{ $video->formatted_duration }}
                                    </span>
                                @endif
                            </div>

                            <h3 class="text-base font-bold text-white leading-snug">
                                <a href="{{ route('videos.show', $video->slug) }}" class="hover:text-emerald-400 transition-colors">
                                    {{ $video->title }}
                                </a>
                            </h3>

                            <div class="flex flex-wrap items-center gap-1.5">
                                @foreach ($video->hosts as $host)
                                    <span class="text-[11px] px-2 py-0.5 rounded bg-slate-800 text-slate-300 border border-slate-700">
                                        {{ $host->name }}
                                    </span>
                                @endforeach
                            </div>

                            @if ($video->intro_music || $video->outro_music)
                                <div class="flex flex-col gap-1 text-[11px] text-slate-400 pt-1">
                                    @if ($video->intro_music)
                                        <span class="truncate"><span class="text-indigo-400 font-medium">Intro:</span> {{ $video->intro_music }}</span>
                                    @endif
                                    @if ($video->outro_music)
                                        <span class="truncate"><span class="text-emerald-400 font-medium">Outro:</span> {{ $video->outro_music }}</span>
                                    @endif
                                </div>
                            @endif

                            <div class="pt-1 flex flex-wrap gap-2 text-xs">
                                <a href="{{ route('videos.show', $video->slug) }}" class="text-emerald-400 hover:text-emerald-300 font-medium inline-flex items-center gap-1">
                                    <span>Details &amp; Timestamps &rarr;</span>
                                </a>
                                @if (($video->isExclusive() || $video->hasPatreonPost()) && $video->getWatchUrl())
                                    <a href="{{ $video->getWatchUrl() }}" target="_blank" rel="noopener noreferrer" class="text-red-400 hover:text-red-300 font-medium inline-flex items-center gap-1">
                                        <span>Open Patreon &rarr;</span>
                                    </a>
                                @endif
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400 font-mono">
                            <span>@userDate($video->published_at)</span>
                            <span>{{ $video->puzzles->count() }} {{ Str::plural('Puzzle', $video->puzzles->count()) }}</span>
                        </div>
                    </div>
                </article>
            @empty
                <div class="col-span-full py-12 text-center text-slate-500 text-sm">
                    No recent videos cataloged yet.
                </div>
            @endforelse
        </div>
    </section>

</div>

@if ($featuredVideo && ! $featuredVideo->isExclusive() && $featuredVideo->youtube_video_id)
<script>
    function loadHeroYouTubePlayer() {
        const facade = document.getElementById('hero-yt-facade');
        const wrapper = document.getElementById('hero-yt-iframe-wrapper');
        if (facade) {
            facade.classList.add('hidden');
        }
        if (wrapper) {
            wrapper.classList.remove('hidden');
            wrapper.innerHTML = '<iframe class="absolute inset-0 w-full h-full" src="https://www.youtube-nocookie.com/embed/{{ $featuredVideo->youtube_video_id }}?autoplay=1&rel=0" title="{{ addslashes($featuredVideo->title) }}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>';
        }
    }
</script>
@endif
@endsection
