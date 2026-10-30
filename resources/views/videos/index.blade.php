@extends('layouts.app')

@section('title', 'All Videos - Cataloging the Cryptic')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
    {{-- Header & Breadcrumbs --}}
    <div class="space-y-3 border-b border-slate-800 pb-6">
        <nav class="flex items-center space-x-2 text-xs text-slate-400">
            <a href="{{ route('home') }}" class="hover:text-emerald-400 transition-colors">Home</a>
            <span>/</span>
            <span class="text-slate-200">Videos</span>
        </nav>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-extrabold text-white tracking-tight">
                    All Videos
                </h1>
                <p class="text-sm text-slate-400 mt-1">
                    Complete archive of Cracking the Cryptic videos in reverse chronological order.
                </p>
            </div>

            <div class="flex items-center gap-2.5 flex-wrap">
                <a href="{{ route('videos.top') }}" class="inline-flex items-center space-x-2 px-3.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition-colors text-xs font-semibold">
                    <svg class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                    </svg>
                    <span>Most Viewed</span>
                </a>
                <a href="{{ route('videos.longest') }}" class="inline-flex items-center space-x-2 px-3.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition-colors text-xs font-semibold">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Longest</span>
                </a>
                <a href="{{ route('videos.shortest') }}" class="inline-flex items-center space-x-2 px-3.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition-colors text-xs font-semibold">
                    <svg class="w-4 h-4 text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Shortest</span>
                </a>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-slate-800 text-slate-300 border border-slate-700">
                    {{ number_format($videos->total()) }} {{ Str::plural('Video', $videos->total()) }}
                </span>
            </div>
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
                        {{-- Fallback Sleek Thumbnail --}}
                        <a href="{{ route('videos.show', $video->slug) }}" class="w-full h-full flex items-center justify-center bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 group-hover:bg-slate-900 transition-colors">
                            <div class="w-14 h-14 rounded-full bg-slate-800/80 border border-slate-700/80 flex items-center justify-center text-slate-400 group-hover:text-emerald-400 group-hover:border-emerald-500/50 transition-colors">
                                <svg class="w-6 h-6 ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z" />
                                </svg>
                            </div>
                        </a>
                    @endif

                    {{-- Badges on Thumbnail --}}
                    <div class="absolute top-2.5 left-2.5 flex items-center gap-1.5 flex-wrap">
                        @if ($video->isExclusive())
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-red-500/90 text-white backdrop-blur-sm shadow-sm">
                                Patron Exclusive
                            </span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-600/90 text-white backdrop-blur-sm shadow-sm">
                                Public
                            </span>
                        @endif

                        @if ($video->isYouTubeShort())
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-500/90 text-white backdrop-blur-sm shadow-sm">
                                Short
                            </span>
                        @elseif ($video->isLiveStream())
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-indigo-600/90 text-white backdrop-blur-sm shadow-sm">
                                Live
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

                    {{-- Video Length (Duration) Overlay --}}
                    <div class="absolute bottom-2.5 right-2.5 px-2 py-0.5 rounded bg-black/85 backdrop-blur-sm text-[11px] font-mono font-medium text-white shadow-sm flex items-center gap-1">
                        <svg class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ $video->formatted_duration }}</span>
                    </div>
                </div>

                {{-- Video Metadata & Details --}}
                <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                    <div class="space-y-3">
                        {{-- Video Name --}}
                        <h2 class="text-base font-bold text-white leading-snug group-hover:text-emerald-400 transition-colors">
                            <a href="{{ route('videos.show', $video->slug) }}">
                                {{ $video->title }}
                            </a>
                        </h2>

                        {{-- Host(s) --}}
                        <div class="flex items-center gap-1.5 flex-wrap text-xs">
                            <span class="text-slate-400 font-medium">Host{{ $video->hosts->count() > 1 ? 's' : '' }}:</span>
                            @forelse ($video->hosts as $host)
                                <span class="px-2 py-0.5 rounded-full bg-slate-800 text-slate-200 border border-slate-700 font-medium">
                                    {{ $host->name }}
                                </span>
                            @empty
                                <span class="text-slate-500 italic">None listed</span>
                            @endforelse
                        </div>

                        {{-- Puzzle Type --}}
                        <div class="flex items-center gap-1.5 flex-wrap text-xs">
                            <span class="text-slate-400 font-medium">Puzzle Type:</span>
                            @php
                                $puzzleTypes = $video->puzzles->map(fn ($p) => $p->category?->name)->filter()->unique();
                            @endphp
                            @forelse ($puzzleTypes as $type)
                                <span class="px-2 py-0.5 rounded bg-indigo-950/80 text-indigo-300 border border-indigo-700/60 font-medium">
                                    {{ $type }}
                                </span>
                            @empty
                                <span class="text-slate-500 italic">None cataloged</span>
                            @endforelse
                        </div>
                    </div>

                    {{-- Stats & Footer Bar: Video Length, Views, and Date --}}
                    <div class="pt-4 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400 font-mono">
                        {{-- Views --}}
                        <span class="flex items-center gap-1.5" title="View Count">
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <span>{{ number_format($video->view_count) }} views</span>
                        </span>

                        {{-- Video Length & Published Date --}}
                        <div class="flex items-center gap-3">
                            <span class="text-slate-300" title="Duration">{{ $video->formatted_duration }}</span>
                            @if ($video->effective_published_at)
                                <span class="text-slate-500">&bull;</span>
                                <span class="text-slate-400">@userDate($video->effective_published_at)</span>
                            @endif
                        </div>
                    </div>
                </div>
            </article>
        @empty
            <div class="col-span-full py-16 text-center rounded-xl border border-slate-800 bg-slate-900/30">
                <svg class="mx-auto h-12 w-12 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                <h3 class="mt-3 text-sm font-semibold text-slate-300">No videos cataloged</h3>
                <p class="mt-1 text-xs text-slate-500">There are currently no videos stored in the database.</p>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if ($videos->hasPages())
        <div class="pt-6 border-t border-slate-800 flex justify-center">
            {{ $videos->links() }}
        </div>
    @endif
</div>
@endsection
