@extends('layouts.app')

@section('title', !empty($query) ? "Search: {$query} - Cataloging the Cryptic" : 'Search Catalog - Cataloging the Cryptic')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-10">

    {{-- Search Hero & Input Section --}}
    <div class="space-y-6">
        <div class="text-center max-w-3xl mx-auto space-y-3">
            <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white font-sans">
                Search the <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-teal-300">Catalog</span>
            </h1>
            <p class="text-sm sm:text-base text-slate-400">
                Find videos, puzzles, setters, puzzle types, constraints, and hosts across Cracking the Cryptic.
            </p>
        </div>

        {{-- Main Prominent Search Input --}}
        <div class="max-w-2xl mx-auto">
            <form action="{{ route('search') }}" method="GET" class="relative flex items-center">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-500">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input
                    type="search"
                    name="q"
                    value="{{ $query }}"
                    placeholder="Search by video title, bookmark moment, setter, puzzle, variant, host..."
                    autofocus
                    class="w-full pl-12 pr-28 py-3.5 rounded-xl bg-slate-900 border border-slate-700/80 text-white placeholder-slate-500 text-sm sm:text-base shadow-xl focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-all font-sans"
                />
                @if (!empty($type) && $type !== 'all')
                    <input type="hidden" name="type" value="{{ $type }}" />
                @endif
                <div class="absolute inset-y-0 right-1.5 flex items-center gap-1">
                    @if (!empty($query))
                        <a href="{{ route('search', ['type' => $type]) }}" class="p-2 text-slate-400 hover:text-slate-200 transition-colors" title="Clear query">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </a>
                    @endif
                    <button
                        type="submit"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs sm:text-sm font-semibold transition-all shadow-md flex items-center gap-1.5"
                    >
                        <span>Search</span>
                    </button>
                </div>
            </form>

            {{-- Quick Example Search Chips --}}
            <div class="mt-3 flex flex-wrap items-center justify-center gap-2 text-xs text-slate-400">
                <span class="text-slate-500">Suggestions:</span>
                @foreach (['Simon', 'Mark', 'Phistomefel', 'Rules explanation', 'Variant Sudoku', 'Miracle', 'Break-in', 'Killer', 'Thermo', 'Classic'] as $suggestion)
                    <a
                        href="{{ route('search', ['q' => $suggestion]) }}"
                        class="px-2.5 py-1 rounded-full bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-emerald-400 border border-slate-800 hover:border-slate-700 transition-all font-mono text-[11px]"
                    >
                        {{ $suggestion }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    @if (!empty($query))
        {{-- Results Summary & Filter Tabs Bar --}}
        <div class="border-b border-slate-800 pb-4 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <h2 class="text-lg font-bold text-slate-200 font-sans">
                    Results for &ldquo;<span class="text-emerald-400">{{ $query }}</span>&rdquo;
                    <span class="text-xs font-mono font-normal text-slate-400 ml-2">({{ number_format($results['total_count']) }} total found)</span>
                </h2>
                @if ($results['total_count'] > 0)
                    <span class="text-xs text-slate-500 font-mono">
                        Showing results across {{ count(array_filter($results['counts'], fn ($c, $k) => $k !== 'all' && $c > 0, ARRAY_FILTER_USE_BOTH)) }} categories
                    </span>
                @endif
            </div>

            {{-- Filter Tabs --}}
            <div class="flex flex-wrap items-center gap-2 pt-1">
                @php
                    $tabs = [
                        'all' => ['label' => 'All Results', 'count' => $results['counts']['all']],
                        'videos' => ['label' => 'Videos', 'count' => $results['counts']['videos']],
                        'bookmarks' => ['label' => 'Bookmarks', 'count' => $results['counts']['bookmarks']],
                        'puzzles' => ['label' => 'Puzzles', 'count' => $results['counts']['puzzles']],
                        'setters' => ['label' => 'Setters', 'count' => $results['counts']['setters']],
                        'categories' => ['label' => 'Puzzle Types', 'count' => $results['counts']['categories']],
                        'constraints' => ['label' => 'Variants', 'count' => $results['counts']['constraints']],
                        'hosts' => ['label' => 'Hosts', 'count' => $results['counts']['hosts']],
                    ];
                @endphp

                @foreach ($tabs as $tabKey => $tab)
                    @php $isActive = ($type === $tabKey); @endphp
                    <a
                        href="{{ route('search', ['q' => $query, 'type' => $tabKey]) }}"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all {{ $isActive ? 'bg-emerald-600 text-white shadow-md' : 'bg-slate-900 text-slate-400 hover:text-slate-200 border border-slate-800 hover:border-slate-700' }}"
                    >
                        <span>{{ $tab['label'] }}</span>
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono {{ $isActive ? 'bg-emerald-800 text-emerald-100' : 'bg-slate-800 text-slate-400' }}">
                            {{ number_format($tab['count']) }}
                        </span>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Zero Results State --}}
        @if ($results['total_count'] === 0)
            <div class="rounded-2xl border border-slate-800 bg-slate-900/40 p-12 text-center space-y-4 max-w-xl mx-auto">
                <div class="w-12 h-12 rounded-full bg-slate-800 text-slate-400 flex items-center justify-center mx-auto">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="space-y-1">
                    <h3 class="text-base font-bold text-white">No results found</h3>
                    <p class="text-xs text-slate-400">
                        We could not find anything matching &ldquo;{{ $query }}&rdquo;. Try searching for a different puzzle name, setter, host, or keyword.
                    </p>
                </div>
                <div class="pt-2">
                    <a href="{{ route('videos.index') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-400 hover:text-emerald-300">
                        <span>Browse all videos instead</span>
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            </div>
        @endif

        {{-- 1. Hosts Section --}}
        @if (($type === 'all' || $type === 'hosts') && $results['hosts']->isNotEmpty())
            <section class="space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800/80 pb-2">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        <span>Hosts</span>
                        <span class="text-xs font-mono text-slate-500">({{ number_format($results['counts']['hosts']) }})</span>
                    </h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($results['hosts'] as $host)
                        <div class="p-4 rounded-xl bg-slate-900/70 border border-slate-800 hover:border-slate-700 transition-all flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 font-bold text-sm">
                                    {{ substr($host->name, 0, 1) }}
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-white">{{ $host->name }}</h4>
                                    <p class="text-xs text-slate-400 font-mono">{{ $host->videos_count }} {{ Str::plural('video', $host->videos_count) }}</p>
                                </div>
                            </div>
                            <a
                                href="{{ route('search', ['q' => $host->name, 'type' => 'videos']) }}"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-800 hover:bg-emerald-600 text-slate-200 hover:text-white transition-colors"
                            >
                                View Videos
                            </a>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination for Hosts --}}
                @if ($results['hosts'] instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $results['hosts']->hasPages())
                    <div class="pt-6 border-t border-slate-800 flex justify-center">
                        {{ $results['hosts']->links() }}
                    </div>
                @endif
            </section>
        @endif

        {{-- 2. Setters Section --}}
        @if (($type === 'all' || $type === 'setters') && $results['setters']->isNotEmpty())
            <section class="space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800/80 pb-2">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-teal-400"></span>
                        <span>Setters (Puzzle Authors)</span>
                        <span class="text-xs font-mono text-slate-500">({{ number_format($results['counts']['setters']) }})</span>
                    </h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($results['setters'] as $setter)
                        <div class="p-4 rounded-xl bg-slate-900/70 border border-slate-800 hover:border-slate-700 transition-all flex flex-col justify-between gap-3">
                            <div class="space-y-1">
                                <div class="flex items-center justify-between gap-2">
                                    <h4 class="text-sm font-bold text-white">{{ $setter->name }}</h4>
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-mono font-medium bg-slate-800 text-teal-300 border border-slate-700">
                                        {{ $setter->puzzles_count }} {{ Str::plural('puzzle', $setter->puzzles_count) }}
                                    </span>
                                </div>
                                @if (!empty($setter->bio))
                                    <p class="text-xs text-slate-400 line-clamp-2">{{ $setter->bio }}</p>
                                @endif
                            </div>

                            <div class="pt-2 border-t border-slate-800/60 flex items-center justify-between text-xs">
                                <a
                                    href="{{ route('search', ['q' => $setter->name, 'type' => 'puzzles']) }}"
                                    class="text-teal-400 hover:text-teal-300 font-semibold flex items-center gap-1"
                                >
                                    <span>Puzzles by {{ $setter->name }}</span>
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                                @if ($setter->relationLoaded('links') && $setter->links->isNotEmpty())
                                    @foreach ($setter->links as $link)
                                        <a
                                            href="{{ $link->url }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="text-slate-400 hover:text-emerald-400 transition-colors"
                                            title="{{ $link->display_label }}"
                                        >
                                            {{ $link->display_label }}
                                        </a>
                                    @endforeach
                                @elseif (!empty($setter->patreon_url))
                                    <a
                                        href="{{ $setter->patreon_url }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-slate-400 hover:text-emerald-400 transition-colors"
                                        title="Patreon"
                                    >
                                        Patreon
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination for Setters --}}
                @if ($results['setters'] instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $results['setters']->hasPages())
                    <div class="pt-6 border-t border-slate-800 flex justify-center">
                        {{ $results['setters']->links() }}
                    </div>
                @endif
            </section>
        @endif

        {{-- 3. Categories (Puzzle Types) Section --}}
        @if (($type === 'all' || $type === 'categories') && $results['categories']->isNotEmpty())
            <section class="space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800/80 pb-2">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-indigo-400"></span>
                        <span>Puzzle Types (Categories)</span>
                        <span class="text-xs font-mono text-slate-500">({{ number_format($results['counts']['categories']) }})</span>
                    </h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($results['categories'] as $category)
                        <div class="p-4 rounded-xl bg-slate-900/70 border border-slate-800 hover:border-slate-700 transition-all flex flex-col justify-between gap-3">
                            <div class="space-y-1">
                                <div class="flex items-center justify-between gap-2">
                                    <h4 class="text-sm font-bold text-white">{{ $category->name }}</h4>
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-mono font-medium bg-indigo-950/80 text-indigo-300 border border-indigo-800/60">
                                        {{ $category->puzzles_count }} {{ Str::plural('puzzle', $category->puzzles_count) }}
                                    </span>
                                </div>
                                @if (!empty($category->description))
                                    <div class="ctc-markdown-content ctc-markdown-preview">
                                        {!! Str::markdown($category->description) !!}
                                    </div>
                                @endif
                            </div>

                            <div class="pt-2 border-t border-slate-800/60">
                                <a
                                    href="{{ route('search', ['q' => $category->name, 'type' => 'puzzles']) }}"
                                    class="text-xs text-indigo-400 hover:text-indigo-300 font-semibold flex items-center gap-1"
                                >
                                    <span>Browse {{ $category->name }} puzzles</span>
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination for Categories --}}
                @if ($results['categories'] instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $results['categories']->hasPages())
                    <div class="pt-6 border-t border-slate-800 flex justify-center">
                        {{ $results['categories']->links() }}
                    </div>
                @endif
            </section>
        @endif

        {{-- 4. Constraints (Variants) Section --}}
        @if (($type === 'all' || $type === 'constraints') && $results['constraints']->isNotEmpty())
            <section class="space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800/80 pb-2">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                        <span>Variants &amp; Constraints</span>
                        <span class="text-xs font-mono text-slate-500">({{ number_format($results['counts']['constraints']) }})</span>
                    </h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($results['constraints'] as $constraint)
                        <div class="p-4 rounded-xl bg-slate-900/70 border border-slate-800 hover:border-slate-700 transition-all flex flex-col justify-between gap-3">
                            <div class="space-y-1">
                                <div class="flex items-center justify-between gap-2">
                                    <h4 class="text-sm font-bold text-white">{{ $constraint->name }}</h4>
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-mono font-medium bg-amber-950/80 text-amber-300 border border-amber-800/60">
                                        {{ $constraint->puzzles_count }} {{ Str::plural('puzzle', $constraint->puzzles_count) }}
                                    </span>
                                </div>
                                @if (!empty($constraint->description))
                                    <div class="ctc-markdown-content ctc-markdown-preview">
                                        {!! Str::markdown($constraint->description) !!}
                                    </div>
                                @endif
                            </div>

                            <div class="pt-2 border-t border-slate-800/60">
                                <a
                                    href="{{ route('search', ['q' => $constraint->name, 'type' => 'puzzles']) }}"
                                    class="text-xs text-amber-400 hover:text-amber-300 font-semibold flex items-center gap-1"
                                >
                                    <span>Browse {{ $constraint->name }} puzzles</span>
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination for Constraints --}}
                @if ($results['constraints'] instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $results['constraints']->hasPages())
                    <div class="pt-6 border-t border-slate-800 flex justify-center">
                        {{ $results['constraints']->links() }}
                    </div>
                @endif
            </section>
        @endif

        {{-- Video Bookmarks & Timestamps Section --}}
        @if (($type === 'all' || $type === 'bookmarks') && $results['bookmarks']->isNotEmpty())
            <section class="space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800/80 pb-2">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                        <span>Video Bookmarks &amp; Timestamps</span>
                        <span class="text-xs font-mono text-slate-500">({{ number_format($results['counts']['bookmarks']) }})</span>
                    </h3>
                    @if ($type === 'all' && $results['counts']['bookmarks'] > 24)
                        <a href="{{ route('search', ['q' => $query, 'type' => 'bookmarks']) }}" class="text-xs text-slate-400 hover:text-amber-400 transition-colors">
                            View all {{ number_format($results['counts']['bookmarks']) }} bookmarks &rarr;
                        </a>
                    @endif
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($results['bookmarks'] as $bookmark)
                        <a
                            href="{{ route('videos.show', $bookmark->video->slug) }}#t={{ $bookmark->timestamp_seconds }}"
                            class="group p-4 rounded-xl bg-slate-900/70 border border-slate-800 hover:border-amber-500/60 hover:bg-slate-800/60 transition-all flex flex-col justify-between gap-3 shadow-md"
                        >
                            <div class="space-y-2">
                                <div class="flex items-start justify-between gap-2">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-mono font-bold bg-amber-500/15 text-amber-300 border border-amber-500/30 group-hover:bg-amber-500 group-hover:text-slate-950 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M6.3 2.841A1.5 1.5 0 004 4.11v11.78a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z" />
                                        </svg>
                                        {{ $bookmark->formatted_timestamp }}
                                    </span>
                                    @if ($bookmark->puzzle)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-indigo-950/80 text-indigo-300 border border-indigo-700/60 truncate max-w-[130px]" title="Puzzle: {{ $bookmark->puzzle->title }}">
                                            {{ $bookmark->puzzle->title }}
                                        </span>
                                    @endif
                                </div>

                                <h4 class="text-sm font-bold text-white group-hover:text-amber-300 transition-colors line-clamp-2">
                                    {{ $bookmark->label }}
                                </h4>

                                <p class="text-xs text-slate-400 line-clamp-1 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-slate-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    <span class="truncate">{{ $bookmark->video->title }}</span>
                                </p>
                            </div>

                            <div class="pt-2.5 border-t border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500 font-mono">
                                <span>@userDate($bookmark->video->published_at)</span>
                                <span class="text-amber-400/80 group-hover:text-amber-300 font-sans font-semibold flex items-center gap-1">
                                    Jump to moment &rarr;
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>

                {{-- Pagination for Bookmarks --}}
                @if ($results['bookmarks'] instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $results['bookmarks']->hasPages())
                    <div class="pt-6 border-t border-slate-800 flex justify-center">
                        {{ $results['bookmarks']->links() }}
                    </div>
                @endif
            </section>
        @endif

        {{-- 5. Videos Section --}}
        @if (($type === 'all' || $type === 'videos') && $results['videos']->isNotEmpty())
            <section class="space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800/80 pb-2">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        <span>Videos</span>
                        <span class="text-xs font-mono text-slate-500">({{ number_format($results['counts']['videos']) }})</span>
                    </h3>
                    <a href="{{ route('videos.index') }}" class="text-xs text-slate-400 hover:text-emerald-400 transition-colors">
                        View Full Catalog &rarr;
                    </a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($results['videos'] as $video)
                        <article class="flex flex-col rounded-xl overflow-hidden transition-all duration-200 group {{ $video->is_finalized ? 'border border-emerald-500/40 ring-1 ring-emerald-500/20 shadow-[0_0_15px_rgba(16,185,129,0.15)] hover:border-emerald-400/60 hover:shadow-[0_0_20px_rgba(16,185,129,0.25)] bg-slate-900/70' : 'border border-slate-800/80 bg-slate-900/60 hover:border-slate-700 shadow-lg shadow-black/20' }}" data-finalized="{{ $video->is_finalized ? 'true' : 'false' }}">
                            {{-- Thumbnail Container --}}
                            <div class="relative aspect-video w-full overflow-hidden bg-slate-950">
                                @if ($video->thumbnail_url)
                                    <img
                                        src="{{ $video->thumbnail_url }}"
                                        alt="{{ $video->title }}"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                        loading="lazy"
                                    />
                                @else
                                    <div class="w-full h-full flex flex-col items-center justify-center bg-slate-950 text-slate-600 space-y-2">
                                        <svg class="w-10 h-10 opacity-40 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span class="text-xs font-mono font-medium tracking-wider uppercase text-slate-600">Video Preview</span>
                                    </div>
                                @endif

                                {{-- Badges on Thumbnail --}}
                                <div class="absolute top-2.5 left-2.5 flex flex-wrap gap-1.5 z-10">
                                    @if ($video->isExclusive())
                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-patreon-500 text-white backdrop-blur-sm shadow-sm">
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

                                {{-- Video Duration Overlay --}}
                                <div class="absolute bottom-2.5 right-2.5 px-2 py-0.5 rounded bg-black/85 backdrop-blur-sm text-[11px] font-mono font-medium text-white shadow-sm flex items-center gap-1">
                                    <svg class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>{{ $video->formatted_duration }}</span>
                                </div>
                            </div>

                            {{-- Card Body --}}
                            <div class="p-4 flex-1 flex flex-col justify-between space-y-3">
                                <div class="space-y-2">
                                    <h4 class="font-bold text-sm leading-snug line-clamp-2 text-white group-hover:text-emerald-400 transition-colors">
                                        <a href="{{ route('videos.show', $video->slug) }}">
                                            {{ $video->title }}
                                        </a>
                                    </h4>

                                    {{-- Hosts & Attached Puzzles --}}
                                    <div class="flex flex-wrap items-center gap-1.5 text-xs">
                                        @foreach ($video->hosts as $host)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-slate-800 text-slate-300 font-medium">
                                                {{ $host->name }}
                                            </span>
                                        @endforeach

                                        @foreach ($video->puzzles as $puz)
                                            @if ($puz->category)
                                                <span class="px-2 py-0.5 rounded bg-indigo-950/80 text-indigo-300 border border-indigo-700/60 font-medium">
                                                    {{ $puz->category->name }}
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>

                                    {{-- Matching Bookmarks on Video Card --}}
                                    @if ($video->bookmarks->isNotEmpty())
                                        @php
                                            $matchingBookmarks = $video->bookmarks->filter(fn ($b) => str_contains(mb_strtolower($b->label), mb_strtolower($query)));
                                        @endphp
                                        @if ($matchingBookmarks->isNotEmpty())
                                            <div class="pt-2 border-t border-slate-800/60 space-y-1.5">
                                                <span class="text-[10px] font-semibold text-amber-400 uppercase tracking-wider flex items-center gap-1 font-mono">
                                                    <svg class="w-3 h-3 text-amber-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                                    </svg>
                                                    Matching Bookmarks:
                                                </span>
                                                <div class="flex flex-wrap gap-1.5">
                                                    @foreach ($matchingBookmarks->take(3) as $mb)
                                                        <a
                                                            href="{{ route('videos.show', $video->slug) }}#t={{ $mb->timestamp_seconds }}"
                                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-mono bg-amber-500/10 text-amber-300 border border-amber-500/30 hover:border-amber-400 hover:text-amber-200 transition-colors"
                                                            title="Jump to {{ $mb->formatted_timestamp }} - {{ $mb->label }}"
                                                        >
                                                            <span class="font-bold">{{ $mb->formatted_timestamp }}</span>
                                                            <span class="text-slate-400 font-sans truncate max-w-[130px]">{{ $mb->label }}</span>
                                                        </a>
                                                    @endforeach
                                                    @if ($matchingBookmarks->count() > 3)
                                                        <span class="text-[10px] text-slate-500 self-center">+{{ $matchingBookmarks->count() - 3 }} more</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    @endif
                                </div>

                                {{-- Meta Footer: Views & Date --}}
                                <div class="pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400 font-mono">
                                    <span class="flex items-center gap-1.5" title="View Count">
                                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <span>{{ number_format($video->view_count) }} views</span>
                                    </span>
                                    <span class="text-slate-400">@userDate($video->published_at, 'M d, Y')</span>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                {{-- Pagination for Videos --}}
                @if ($results['videos'] instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $results['videos']->hasPages())
                    <div class="pt-6 border-t border-slate-800 flex justify-center">
                        {{ $results['videos']->links() }}
                    </div>
                @endif
            </section>
        @endif

        {{-- 6. Puzzles Section --}}
        @if (($type === 'all' || $type === 'puzzles') && $results['puzzles']->isNotEmpty())
            <section class="space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800/80 pb-2">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        <span>Puzzles</span>
                        <span class="text-xs font-mono text-slate-500">({{ number_format($results['counts']['puzzles']) }})</span>
                    </h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($results['puzzles'] as $puzzle)
                        <article class="p-5 rounded-xl bg-slate-900/60 border border-slate-800/80 hover:border-slate-700 transition-all flex flex-col justify-between space-y-4">
                            <div class="space-y-3">
                                {{-- Category & Access Badge --}}
                                <div class="flex items-center justify-between gap-2">
                                    @if ($puzzle->category)
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-950 text-indigo-300 border border-indigo-800">
                                            {{ $puzzle->category->name }}
                                        </span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-800 text-slate-300">
                                            Puzzle
                                        </span>
                                    @endif

                                    @if ($puzzle->access_level === 'exclusive')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-patreon-500/20 text-patreon-500 border border-patreon-500/40">
                                            Patron Exclusive
                                        </span>
                                    @endif
                                </div>

                                {{-- Title --}}
                                <h4 class="font-bold text-base text-white">
                                    {{ $puzzle->title }}
                                </h4>

                                {{-- Setter(s) & Constraints --}}
                                <div class="space-y-1.5 text-xs text-slate-400">
                                    @if ($puzzle->setters->isNotEmpty())
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-slate-500">Setter:</span>
                                            <span class="text-slate-200 font-medium">
                                                {{ $puzzle->setters->pluck('name')->join(', ') }}
                                            </span>
                                        </div>
                                    @endif

                                    @if ($puzzle->constraints->isNotEmpty())
                                        <div class="flex flex-wrap gap-1 pt-1">
                                            @foreach ($puzzle->constraints->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE) as $constraint)
                                                <a href="{{ route('constraints.show', $constraint->slug) }}" class="px-2 py-0.5 rounded bg-slate-800/80 hover:bg-slate-700 text-amber-300 hover:text-emerald-400 border border-slate-700/60 text-[11px] font-mono transition-colors">
                                                    {{ $constraint->name }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if (!empty($puzzle->rules_raw))
                                        <p class="text-xs text-slate-400 line-clamp-3 pt-1 border-t border-slate-800/60">
                                            {{ Str::limit(strip_tags($puzzle->rules_raw), 140) }}
                                        </p>
                                    @endif
                                </div>
                            </div>

                            {{-- Puzzle Footer with Actions --}}
                            <div class="pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs">
                                @if ($puzzle->videos->isNotEmpty())
                                    @php $parentVideo = $puzzle->videos->first(); @endphp
                                    <a
                                        href="{{ route('videos.show', $parentVideo->slug) }}"
                                        class="text-emerald-400 hover:text-emerald-300 font-semibold flex items-center gap-1"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>Watch Solve</span>
                                    </a>
                                @else
                                    <span class="text-slate-500 italic">No video attached</span>
                                @endif

                                @if ($puzzle->access_level !== 'exclusive' && $puzzle->links->isNotEmpty())
                                    @php $primaryLink = $puzzle->links->first(); @endphp
                                    <a
                                        href="{{ $primaryLink->url }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="px-2.5 py-1 rounded bg-slate-800 hover:bg-emerald-600 text-slate-200 hover:text-white font-medium transition-colors"
                                    >
                                        Play Puzzle &rarr;
                                    </a>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>

                {{-- Pagination for Puzzles --}}
                @if ($results['puzzles'] instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $results['puzzles']->hasPages())
                    <div class="pt-6 border-t border-slate-800 flex justify-center">
                        {{ $results['puzzles']->links() }}
                    </div>
                @endif

                @if ($type === 'all' && $results['counts']['puzzles'] > 30)
                    <div class="pt-2 text-center">
                        <a href="{{ route('search', ['q' => $query, 'type' => 'puzzles']) }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-emerald-400 border border-slate-800 hover:border-slate-700 transition-colors">
                            <span>View all {{ number_format($results['counts']['puzzles']) }} matching puzzles &rarr;</span>
                        </a>
                    </div>
                @endif
            </section>
        @endif

    @else
        {{-- Default Search Landing State (No Query Entered Yet) --}}
        <div class="rounded-2xl border border-slate-800 bg-slate-900/30 p-8 sm:p-12 space-y-8 max-w-4xl mx-auto">
            <div class="text-center space-y-2">
                <h3 class="text-lg font-bold text-white">What can you search for?</h3>
                <p class="text-xs sm:text-sm text-slate-400 max-w-xl mx-auto">
                    Type any keyword to search across the entire Cracking the Cryptic archive.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 space-y-1.5">
                    <div class="text-emerald-400 font-bold text-xs uppercase tracking-wider font-mono">Videos</div>
                    <p class="text-xs text-slate-400">Search by title, intro/outro music track, or video topic.</p>
                </div>
                <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 space-y-1.5">
                    <div class="text-teal-400 font-bold text-xs uppercase tracking-wider font-mono">Setters</div>
                    <p class="text-xs text-slate-400">Search for puzzle authors like Phistomefel, Clover, BremSter, or Prasanna Seshadri.</p>
                </div>
                <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 space-y-1.5">
                    <div class="text-indigo-400 font-bold text-xs uppercase tracking-wider font-mono">Puzzle Types</div>
                    <p class="text-xs text-slate-400">Find Variant Sudoku, Classic Sudoku, Crosswords, Wordle, and GAS speed-solves.</p>
                </div>
                <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 space-y-1.5">
                    <div class="text-amber-400 font-bold text-xs uppercase tracking-wider font-mono">Variants &amp; Rules</div>
                    <p class="text-xs text-slate-400">Find puzzles using Killer cages, Thermo lines, German Whispers, Arrows, or Fog of War.</p>
                </div>
                <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 space-y-1.5">
                    <div class="text-emerald-400 font-bold text-xs uppercase tracking-wider font-mono">Hosts</div>
                    <p class="text-xs text-slate-400">Filter videos featuring Simon Anthony, Mark Goodliffe, or guest solvers.</p>
                </div>
                <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 space-y-1.5">
                    <div class="text-purple-400 font-bold text-xs uppercase tracking-wider font-mono">Puzzles</div>
                    <p class="text-xs text-slate-400">Locate specific puzzles by title, rule keywords, or difficulty score.</p>
                </div>
                <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 space-y-1.5">
                    <div class="text-amber-400 font-bold text-xs uppercase tracking-wider font-mono">Bookmarks &amp; Timestamps</div>
                    <p class="text-xs text-slate-400">Search for recurring video moments, rules explanations, break-ins, and key solve milestones.</p>
                </div>
            </div>
        </div>
    @endif

</div>
@endsection
