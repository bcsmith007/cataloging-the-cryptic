@extends('layouts.app')

@section('title', $constraint->name . ' - Puzzle Constraint - Cataloging the Cryptic')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-10">
    {{-- Breadcrumbs --}}
    <nav class="flex items-center space-x-2 text-xs text-slate-400">
        <a href="{{ route('home') }}" class="hover:text-emerald-400 transition-colors">Home</a>
        <span>/</span>
        <a href="{{ route('constraints.index') }}" class="hover:text-emerald-400 transition-colors">Constraints</a>
        <span>/</span>
        <span class="text-slate-200">{{ $constraint->name }}</span>
    </nav>

    {{-- Constraint Profile & Definition Card --}}
    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 sm:p-8 backdrop-blur-sm shadow-xl shadow-black/20">
        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-6">
            <div class="space-y-3">
                <div class="flex items-center gap-3 flex-wrap">
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                            {{ $constraint->name }}
                        </h1>

                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            Puzzle Constraint
                        </span>

                        @auth
                            <a
                                href="{{ route('filament.admin.resources.constraints.edit', ['record' => $constraint]) }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-semibold bg-slate-800 hover:bg-emerald-600 text-slate-200 hover:text-white border border-slate-700 hover:border-emerald-500 shadow-sm transition-all"
                                title="Edit Constraint in Admin Panel"
                            >
                                <svg class="w-3.5 h-3.5 text-emerald-400 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                <span>Edit in Admin</span>
                            </a>
                        @endauth
                    </div>

                    {{-- Definition / Rules Description --}}
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 font-mono mb-1">
                            Rule Definition
                        </div>
                        @if ($constraint->description)
                            <div class="text-sm text-slate-300 leading-relaxed max-w-3xl">
                                {!! nl2br(e($constraint->description)) !!}
                            </div>
                        @else
                            <p class="text-sm text-slate-500 italic max-w-2xl">
                                No formal definition provided yet for this constraint.
                            </p>
                        @endif
                    </div>
                </div>

            {{-- Counter Badges --}}
            <div class="flex items-center gap-3 shrink-0 self-start md:self-auto">
                <div class="px-4 py-3 rounded-xl bg-slate-950/60 border border-slate-800 text-center min-w-[5.5rem]">
                    <div class="text-xl font-bold font-mono text-emerald-400">
                        {{ number_format($totalVideos) }}
                    </div>
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 mt-0.5">
                        {{ Str::plural('Video', $totalVideos) }}
                    </div>
                </div>

                <div class="px-4 py-3 rounded-xl bg-slate-950/60 border border-slate-800 text-center min-w-[5.5rem]">
                    <div class="text-xl font-bold font-mono text-white">
                        {{ number_format($puzzlesCount) }}
                    </div>
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 mt-0.5">
                        {{ Str::plural('Puzzle', $puzzlesCount) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Stat Tiles: Videos, First Video, Most Recent Video, Total Views --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        {{-- Stat Tile 1: Number of Videos --}}
        <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-5 shadow-lg flex flex-col justify-between hover:border-emerald-500/40 transition-colors">
            <div>
                <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-emerald-400 font-mono">
                    <span>Videos with Constraint</span>
                    <span>Total</span>
                </div>
                <div class="text-3xl font-extrabold text-white mt-2 tracking-tight font-mono">
                    {{ number_format($totalVideos) }}
                </div>
                <div class="text-xs text-slate-400 mt-1">
                    Featured across {{ number_format($puzzlesCount) }} distinct puzzle solves
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-800/80 text-xs text-slate-400">
                Cataloged appearances
            </div>
        </div>

        {{-- Stat Tile 2: First Video with Constraint --}}
        <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-5 shadow-lg flex flex-col justify-between hover:border-amber-500/40 transition-colors">
            <div>
                <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-amber-400 font-mono">
                    <span>First on Channel</span>
                    <span>Debut</span>
                </div>
                @if ($firstVideo)
                    <a href="{{ route('videos.show', $firstVideo->slug) }}" class="mt-2 block font-bold text-slate-100 hover:text-emerald-400 transition-colors text-sm line-clamp-2" title="{{ $firstVideo->title }}">
                        {{ $firstVideo->title }}
                    </a>
                    <div class="text-xs text-slate-400 mt-1">
                        Published {{ $firstVideo->published_at ? \App\Services\TimezoneService::format($firstVideo->published_at) : 'N/A' }}
                    </div>
                @else
                    <div class="text-sm text-slate-500 italic mt-2">
                        No videos cataloged yet.
                    </div>
                @endif
            </div>
            <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between">
                @if ($firstVideo)
                    <span class="text-sm font-semibold text-slate-300 font-mono">{{ number_format((int) $firstVideo->view_count) }} views</span>
                    <a href="{{ route('videos.show', $firstVideo->slug) }}" class="text-xs text-emerald-400 hover:underline">Watch &rarr;</a>
                @else
                    <span class="text-xs text-slate-500">&mdash;</span>
                @endif
            </div>
        </div>

        {{-- Stat Tile 3: Most Recent Video with Constraint --}}
        <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-5 shadow-lg flex flex-col justify-between hover:border-sky-500/40 transition-colors">
            <div>
                <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-sky-400 font-mono">
                    <span>Most Recent Video</span>
                    <span>Latest</span>
                </div>
                @if ($latestVideo)
                    <a href="{{ route('videos.show', $latestVideo->slug) }}" class="mt-2 block font-bold text-slate-100 hover:text-emerald-400 transition-colors text-sm line-clamp-2" title="{{ $latestVideo->title }}">
                        {{ $latestVideo->title }}
                    </a>
                    <div class="text-xs text-slate-400 mt-1">
                        Published {{ $latestVideo->published_at ? \App\Services\TimezoneService::format($latestVideo->published_at) : 'N/A' }}
                    </div>
                @else
                    <div class="text-sm text-slate-500 italic mt-2">
                        No videos cataloged yet.
                    </div>
                @endif
            </div>
            <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between">
                @if ($latestVideo)
                    <span class="text-sm font-semibold text-slate-300 font-mono">{{ number_format((int) $latestVideo->view_count) }} views</span>
                    <a href="{{ route('videos.show', $latestVideo->slug) }}" class="text-xs text-emerald-400 hover:underline">Watch &rarr;</a>
                @else
                    <span class="text-xs text-slate-500">&mdash;</span>
                @endif
            </div>
        </div>

        {{-- Stat Tile 4: Total Viewership --}}
        <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-5 shadow-lg flex flex-col justify-between hover:border-purple-500/40 transition-colors">
            <div>
                <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-purple-400 font-mono">
                    <span>Total Views</span>
                    <span>Reach</span>
                </div>
                <div class="text-3xl font-extrabold text-purple-400 mt-2 tracking-tight font-mono">
                    {{ number_format($totalViews) }}
                </div>
                <div class="text-xs text-slate-400 mt-1">
                    Cumulative views across all featured videos
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-800/80 text-xs text-slate-400">
                Audience engagement
            </div>
        </div>
    </div>

    {{-- Videos Section Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-800 pb-4">
        <div>
            <h2 class="text-xl font-bold text-white tracking-tight flex items-center gap-2">
                Videos Featuring {{ $constraint->name }}
            </h2>
            <p class="text-xs text-slate-400 mt-1">
                Browse all Cracking the Cryptic video solves featuring the {{ $constraint->name }} rule.
            </p>
        </div>

        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-slate-800 text-slate-300 border border-slate-700">
            {{ number_format($videos->total()) }} {{ Str::plural('Video', $videos->total()) }}
        </span>
    </div>

    {{-- Full Video Listing (identical to /videos/ layout) --}}
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
                        {{-- Video Title --}}
                        <h3 class="text-base font-bold text-white leading-snug group-hover:text-emerald-400 transition-colors">
                            <a href="{{ route('videos.show', $video->slug) }}">
                                {{ $video->title }}
                            </a>
                        </h3>

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

                        {{-- Puzzle Type(s) & Setters --}}
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

                        {{-- Other Constraints on Video --}}
                        @php
                            $otherConstraints = $video->puzzles->flatMap->constraints->unique('id')->reject(fn ($c) => $c->id === $constraint->id)->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();
                        @endphp
                        @if ($otherConstraints->isNotEmpty())
                            <div class="flex items-center gap-1.5 flex-wrap text-xs pt-1">
                                <span class="text-slate-400 font-medium">Also features:</span>
                                @foreach ($otherConstraints->take(3) as $oc)
                                    <a href="{{ route('constraints.show', $oc->slug) }}" class="px-2 py-0.5 rounded text-[11px] bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-emerald-400 border border-slate-700/60 transition-colors">
                                        {{ $oc->name }}
                                    </a>
                                @endforeach
                                @if ($otherConstraints->count() > 3)
                                    <span class="text-[11px] text-slate-500 font-mono">+{{ $otherConstraints->count() - 3 }} more</span>
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- Stats & Footer Bar: Views, Duration, Published Date --}}
                    <div class="pt-4 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400 font-mono">
                        {{-- Views --}}
                        <span class="flex items-center gap-1.5" title="View Count">
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <span>{{ number_format($video->view_count) }} views</span>
                        </span>

                        {{-- Duration & Published Date --}}
                        <div class="flex items-center gap-3">
                            <span class="text-slate-300" title="Duration">{{ $video->formatted_duration }}</span>
                            @if ($video->published_at)
                                <span class="text-slate-500">&bull;</span>
                                <span title="Published Date">{{ \App\Services\TimezoneService::format($video->published_at) }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </article>
        @empty
            <div class="col-span-full py-16 text-center rounded-xl border border-dashed border-slate-800 bg-slate-900/30 p-12">
                <p class="text-base text-slate-400">No videos cataloged for this constraint yet.</p>
                <div class="mt-4">
                    <a href="{{ route('constraints.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold transition-colors">
                        <span>&larr; Back to all constraints</span>
                    </a>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if ($videos->hasPages())
        <div class="pt-6 border-t border-slate-800">
            {{ $videos->links() }}
        </div>
    @endif
</div>
@endsection
