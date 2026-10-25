@extends('layouts.app')

@section('title', $setter->name . ' - Puzzle Setter - Cataloging the Cryptic')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-10">
    {{-- Breadcrumbs --}}
    <nav class="flex items-center space-x-2 text-xs text-slate-400">
        <a href="{{ route('home') }}" class="hover:text-emerald-400 transition-colors">Home</a>
        <span>/</span>
        <a href="{{ route('setters.index') }}" class="hover:text-emerald-400 transition-colors">Setters</a>
        <span>/</span>
        <span class="text-slate-200">{{ $setter->name }}</span>
    </nav>

    {{-- Setter Profile Header Card --}}
    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 sm:p-8 backdrop-blur-sm shadow-xl shadow-black/20">
        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-6">
            <div class="flex items-start gap-5">
                {{-- Monogram Box --}}
                <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-gradient-to-br from-emerald-500/20 via-emerald-600/10 to-transparent border border-emerald-500/30 text-emerald-400 flex items-center justify-center font-extrabold text-2xl sm:text-3xl font-mono shrink-0 shadow-lg shadow-emerald-950/30">
                    {{ Str::upper(Str::substr($setter->name, 0, 1)) }}
                </div>

                <div class="space-y-2">
                    <div class="flex items-center gap-3 flex-wrap">
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                            {{ $setter->name }}
                        </h1>

                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            Puzzle Setter
                        </span>

                        @auth
                            <a
                                href="{{ route('filament.admin.resources.setters.edit', ['record' => $setter]) }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-semibold bg-slate-800 hover:bg-emerald-600 text-slate-200 hover:text-white border border-slate-700 hover:border-emerald-500 shadow-sm transition-all"
                                title="Edit Setter in Admin Panel"
                            >
                                <svg class="w-3.5 h-3.5 text-emerald-400 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                <span>Edit in Admin</span>
                            </a>
                        @endauth
                    </div>

                    {{-- Bio Section --}}
                    @if ($setter->bio)
                        <div class="text-sm text-slate-300 leading-relaxed max-w-3xl pt-1">
                            {!! nl2br(e($setter->bio)) !!}
                        </div>
                    @else
                        <p class="text-sm text-slate-500 italic max-w-2xl pt-1">
                            No biography provided yet for this setter.
                        </p>
                    @endif

                    {{-- External Setter Links --}}
                    @if ($setter->links->isNotEmpty() || filled($setter->patreon_url))
                        <div class="flex items-center gap-2 flex-wrap pt-3">
                            @foreach ($setter->links as $link)
                                <a
                                    href="{{ $link->url }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-medium transition-colors {{ $link->link_type === 'patreon' ? 'bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500/20' : 'bg-slate-800/80 text-slate-200 border border-slate-700 hover:bg-slate-700' }}"
                                >
                                    <span>{{ $link->display_label }}</span>
                                    <svg class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </a>
                            @endforeach

                            @if ($setter->links->isEmpty() && filled($setter->patreon_url))
                                <a
                                    href="{{ $setter->patreon_url }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-medium bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500/20 transition-colors"
                                >
                                    <span>Patreon</span>
                                    <svg class="w-3 h-3 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- Setter Quick Stats Counters --}}
            <div class="flex items-center gap-3 shrink-0 self-start md:self-auto">
                <div class="px-4 py-3 rounded-xl bg-slate-950/60 border border-slate-800 text-center min-w-[5.5rem]">
                    <div class="text-xl font-bold font-mono text-emerald-400">
                        {{ number_format($videos->total()) }}
                    </div>
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 mt-0.5">
                        {{ Str::plural('Video', $videos->total()) }}
                    </div>
                </div>

                <div class="px-4 py-3 rounded-xl bg-slate-950/60 border border-slate-800 text-center min-w-[5.5rem]">
                    <div class="text-xl font-bold font-mono text-white">
                        {{ number_format($setter->puzzles()->count()) }}
                    </div>
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 mt-0.5">
                        {{ Str::plural('Puzzle', $setter->puzzles()->count()) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Videos Grid Section --}}
    <div class="space-y-6">
        <div class="flex items-center justify-between border-b border-slate-800 pb-4">
            <div>
                <h2 class="text-xl font-bold text-white tracking-tight flex items-center gap-2">
                    <span>Videos Featuring {{ $setter->name }}</span>
                    <span class="text-xs font-mono font-medium text-slate-500">
                        ({{ number_format($videos->total()) }})
                    </span>
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">
                    Cracking the Cryptic channel videos solving puzzles created or co-created by this setter.
                </p>
            </div>
        </div>

        @if ($videos->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($videos as $video)
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
                                <a href="{{ route('videos.show', $video->slug) }}" class="w-full h-full flex items-center justify-center bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 group-hover:bg-slate-900 transition-colors">
                                    <div class="w-14 h-14 rounded-full bg-slate-800/80 border border-slate-700/80 flex items-center justify-center text-slate-400 group-hover:text-emerald-400 group-hover:border-emerald-500/50 transition-colors">
                                        <svg class="w-6 h-6 ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M8 5v14l11-7z" />
                                        </svg>
                                    </div>
                                </a>
                            @endif

                            {{-- Badges (Top-Left) --}}
                            <div class="absolute top-2.5 left-2.5 flex items-center gap-1.5 flex-wrap">
                                @if ($video->isExclusive())
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-red-500/90 text-white backdrop-blur-sm shadow-sm">
                                        Patron Exclusive
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

                                {{-- Featured Puzzle by this Setter --}}
                                <div class="space-y-1.5 pt-1">
                                    @foreach ($video->puzzles as $puzzle)
                                        @php
                                            $isSetterPuzzle = $puzzle->setters->contains('id', $setter->id);
                                        @endphp
                                        <div class="flex items-center gap-1.5 text-xs">
                                            <span class="px-2 py-0.5 rounded-md text-[11px] font-medium {{ $isSetterPuzzle ? 'bg-emerald-500/15 text-emerald-300 border border-emerald-500/30' : 'bg-slate-800/80 text-slate-400 border border-slate-700/60' }}">
                                                {{ $puzzle->title }}
                                            </span>
                                            @if ($puzzle->category)
                                                <span class="text-[11px] text-slate-500">
                                                    ({{ $puzzle->category->name }})
                                                </span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Footer: Views & Date --}}
                            <div class="pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400">
                                <span>{{ number_format($video->view_count) }} views</span>
                                <span class="font-mono text-slate-500">
                                    {{ $video->published_at ? \App\Services\TimezoneService::format($video->published_at) : 'Unknown date' }}
                                </span>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Pagination Links --}}
            @if ($videos->hasPages())
                <div class="pt-6 border-t border-slate-800 flex justify-center">
                    {{ $videos->links() }}
                </div>
            @endif
        @else
            {{-- Empty State --}}
            <div class="rounded-xl border border-slate-800 bg-slate-900/40 p-12 text-center space-y-3">
                <div class="w-12 h-12 rounded-full bg-slate-800 text-slate-500 flex items-center justify-center mx-auto">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-white">No videos cataloged yet</h3>
                <p class="text-sm text-slate-400 max-w-sm mx-auto">
                    There are currently no cataloged channel videos associated with puzzles by {{ $setter->name }}.
                </p>
                <div class="pt-2">
                    <a href="{{ route('setters.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold transition-colors">
                        <span>&larr; Back to all setters</span>
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
