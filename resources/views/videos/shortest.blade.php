@extends('layouts.app')

@section('title', !empty($currentHostName) ? "Top 24 Shortest Videos with {$currentHostName} — Cataloging the Cryptic" : 'Top 24 Shortest Videos — Cataloging the Cryptic')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
    {{-- Header & Breadcrumbs --}}
    <div class="space-y-4 border-b border-slate-800 pb-6">
        <nav class="flex items-center space-x-2 text-xs text-slate-400">
            <a href="{{ route('home') }}" class="hover:text-emerald-400 transition-colors">Home</a>
            <span>/</span>
            <a href="{{ route('videos.index') }}" class="hover:text-emerald-400 transition-colors">Videos</a>
            <span>/</span>
            @if (!empty($currentHostName))
                <a href="{{ route('videos.shortest') }}" class="hover:text-emerald-400 transition-colors">Shortest Solves</a>
                <span>/</span>
                <span class="text-slate-200">{{ $currentHostName }}</span>
            @else
                <span class="text-slate-200">Shortest Solves</span>
            @endif
        </nav>

        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-3xl font-extrabold text-white tracking-tight">
                    @if (!empty($currentHostName))
                        Top 24 Shortest with {{ $currentHostName }}
                    @else
                        Top 24 Shortest Videos
                    @endif
                </h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-teal-500/10 text-teal-400 border border-teal-500/30 font-mono">
                    Excludes YouTube Shorts
                </span>
                @if (!empty($currentHostName))
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-teal-500/10 text-teal-400 border border-teal-500/30 font-mono">
                        {{ $currentHostName }}
                    </span>
                @endif
            </div>
            <p class="text-sm text-slate-400 mt-1">
                @if (!empty($currentHostName))
                    The 24 shortest dedicated puzzle solves and masterclasses hosted by {{ $currentHostName }}.
                @else
                    The 24 shortest dedicated puzzle solves, masterclasses, and speed solves across Cracking the Cryptic history.
                @endif
            </p>
        </div>

        {{-- Host Filter Bar for Shortest Solves --}}
        <div class="flex items-center gap-2 pt-2 flex-wrap">
            <span class="text-xs font-semibold text-slate-400 font-mono uppercase tracking-wider mr-1">Host:</span>
            <a
                href="{{ route('videos.shortest') }}"
                class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all {{ empty($currentHostSlug) ? 'bg-teal-500/20 text-teal-300 border border-teal-500/40 shadow-sm' : 'bg-slate-800/80 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700/80' }}"
            >
                All Hosts
            </a>
            <a
                href="{{ route('videos.shortest', ['host' => 'simon']) }}"
                class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all {{ ($currentHostSlug ?? '') === 'simon' ? 'bg-teal-500/20 text-teal-300 border border-teal-500/40 shadow-sm' : 'bg-slate-800/80 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700/80' }}"
            >
                Simon Anthony
            </a>
            <a
                href="{{ route('videos.shortest', ['host' => 'mark']) }}"
                class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all {{ ($currentHostSlug ?? '') === 'mark' ? 'bg-teal-500/20 text-teal-300 border border-teal-500/40 shadow-sm' : 'bg-slate-800/80 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700/80' }}"
            >
                Mark Goodliffe
            </a>
        </div>
    </div>

    {{-- Videos Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse ($videos as $video)
            <article class="flex flex-col justify-between rounded-xl transition-all duration-200 overflow-hidden group {{ $video->is_finalized ? 'border border-emerald-500/40 ring-1 ring-emerald-500/20 shadow-[0_0_15px_rgba(16,185,129,0.15)] hover:border-emerald-400/60 hover:shadow-[0_0_20px_rgba(16,185,129,0.25)] bg-slate-900/70' : 'border border-slate-800 bg-slate-900/60 hover:border-slate-700 shadow-lg shadow-black/20' }}" data-finalized="{{ $video->is_finalized ? 'true' : 'false' }}">
                {{-- Video Thumbnail --}}
                <div class="relative aspect-video w-full overflow-hidden bg-slate-950">
                    @if ($video->thumbnail_url)
                        <a href="{{ route('videos.show', $video->slug) }}" class="block w-full h-full">
                            <img
                                src="{{ $video->thumbnail_url }}"
                                alt="{{ $video->title }}"
                                loading="lazy"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                            />
                        </a>
                    @else
                        {{-- Fallback Thumbnail --}}
                        <a href="{{ route('videos.show', $video->slug) }}" class="w-full h-full flex items-center justify-center bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 group-hover:bg-slate-900 transition-colors">
                            <div class="w-14 h-14 rounded-full bg-slate-800/80 border border-slate-700/80 flex items-center justify-center text-slate-400 group-hover:text-emerald-400 group-hover:border-emerald-500/50 transition-colors">
                                <svg class="w-6 h-6 ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z" />
                                </svg>
                            </div>
                        </a>
                    @endif

                    {{-- Rank Badge (Top-Left) --}}
                    <div class="absolute top-2.5 left-2.5 flex items-center gap-1.5 flex-wrap">
                        @if ($loop->iteration === 1)
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-gradient-to-r from-teal-400 to-emerald-300 text-slate-950 shadow-lg shadow-teal-500/30 ring-1 ring-teal-300 font-mono">
                                #1
                            </span>
                        @elseif ($loop->iteration === 2)
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-gradient-to-r from-slate-200 to-slate-300 text-slate-950 shadow-lg shadow-slate-400/20 ring-1 ring-white font-mono">
                                #2
                            </span>
                        @elseif ($loop->iteration === 3)
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-gradient-to-r from-teal-700 to-teal-600 text-teal-100 shadow-lg shadow-teal-900/40 ring-1 ring-teal-500/50 font-mono">
                                #3
                            </span>
                        @else
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-950/85 text-slate-300 border border-slate-700/80 backdrop-blur-sm font-mono">
                                #{{ $loop->iteration }}
                            </span>
                        @endif
                    </div>

                    @auth
                        {{-- Admin Edit Shortcut --}}
                        <div class="absolute top-2.5 right-2.5 z-10">
                            <a
                                href="{{ route('filament.admin.resources.videos.edit', ['record' => $video]) }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[11px] font-semibold bg-slate-900/90 hover:bg-emerald-600 text-slate-200 hover:text-white border border-slate-700/80 hover:border-emerald-500 shadow-md backdrop-blur-sm transition-all"
                                title="Edit in Admin Panel"
                            >
                                <svg class="w-3 h-3 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                <span>Edit</span>
                            </a>
                        </div>
                    @endauth

                    {{-- Current Views Overlay (Lower-Left) --}}
                    <div class="absolute bottom-2 left-2 px-2 py-0.5 rounded bg-black/80 backdrop-blur-sm text-[11px] font-mono text-white flex items-center gap-1" title="View Count">
                        <svg class="w-3.5 h-3.5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <span>{{ number_format($video->view_count) }}</span>
                    </div>

                    {{-- Video Length (Duration) Overlay (Lower-Right) --}}
                    <div class="absolute bottom-2 right-2 px-2 py-0.5 rounded bg-black/80 backdrop-blur-sm text-[11px] font-mono text-white" title="Duration">
                        {{ $video->formatted_duration }}
                    </div>
                </div>

                {{-- Video Metadata & Details --}}
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

                                @php
                                    $puzzleTypes = $video->puzzles->map(fn ($p) => $p->category?->name)->filter()->unique();
                                @endphp
                                @foreach ($puzzleTypes as $type)
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-indigo-950/80 text-indigo-300 border border-indigo-700/60">
                                        {{ $type }}
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        {{-- Video Title --}}
                        <h3 class="text-base font-bold text-white leading-snug">
                            <a href="{{ route('videos.show', $video->slug) }}" class="hover:text-emerald-400 transition-colors">
                                {{ $video->title }}
                            </a>
                        </h3>

                        {{-- Host(s) --}}
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

                    {{-- Footer Bar: Date (Lower-Left) and Number of Puzzles (Lower-Right) --}}
                    <div class="pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400 font-mono">
                        <span>@userDate($video->effective_published_at)</span>
                        <span>{{ $video->puzzles->count() }} {{ Str::plural('Puzzle', $video->puzzles->count()) }}</span>
                    </div>
                </div>
            </article>
        @empty
            <div class="col-span-full py-16 text-center rounded-xl border border-slate-800 bg-slate-900/30">
                <svg class="mx-auto h-12 w-12 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                <h3 class="mt-3 text-sm font-semibold text-slate-300">
                    {{ !empty($currentHostName) ? "No videos found for {$currentHostName}" : 'No videos cataloged' }}
                </h3>
                <p class="mt-1 text-xs text-slate-500">
                    {{ !empty($currentHostName) ? 'There are currently no videos tagged with this host.' : 'There are currently no videos stored in the database.' }}
                </p>
                @if (!empty($currentHostSlug))
                    <div class="mt-4">
                        <a href="{{ route('videos.shortest') }}" class="text-xs text-teal-400 hover:underline">
                            View all hosts
                        </a>
                    </div>
                @endif
            </div>
        @endforelse
    </div>
</div>
@endsection
