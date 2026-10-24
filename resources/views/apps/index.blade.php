@extends('layouts.app')

@section('title', 'Official Apps - Cataloging the Cryptic')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
    {{-- Header & Breadcrumbs --}}
    <div class="space-y-3 border-b border-slate-800 pb-6">
        <nav class="flex items-center space-x-2 text-xs text-slate-400">
            <a href="{{ route('home') }}" class="hover:text-emerald-400 transition-colors">Home</a>
            <span>/</span>
            <span class="text-slate-200">Apps</span>
        </nav>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-extrabold text-white tracking-tight">
                    Cracking the Cryptic Apps
                </h1>
                <p class="text-sm text-slate-400 mt-1 max-w-3xl">
                    Official puzzle games and dedicated solving software created by Mark Goodliffe, Simon Anthony, and partner game developers. Available across mobile, desktop, and web.
                </p>
            </div>

            <div class="flex items-center gap-3 flex-wrap">
                @auth
                    <a
                        href="{{ route('filament.admin.resources.ctc-apps.create') }}"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/20 hover:border-emerald-400 transition-colors"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Add New App</span>
                    </a>
                    <a
                        href="{{ route('filament.admin.resources.ctc-apps.index') }}"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-900 border border-slate-700 text-slate-300 hover:bg-slate-800 hover:text-white transition-colors"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        <span>Manage in Admin</span>
                    </a>
                @endauth
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-slate-900 border border-slate-800 text-slate-300">
                    <span class="text-emerald-400 font-mono font-bold mr-1.5">{{ number_format($apps->count()) }}</span>
                    <span>Official {{ Str::plural('App', $apps->count()) }}</span>
                </span>
            </div>
        </div>
    </div>

    {{-- Filter Search & Summary --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="relative max-w-sm w-full">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <input
                type="text"
                id="apps-filter-input"
                placeholder="Filter apps by title, platform, or keyword..."
                class="w-full pl-9 pr-4 py-2 bg-slate-900/80 border border-slate-800 rounded-lg text-sm text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors"
            />
        </div>

        <div class="text-xs text-slate-400 flex items-center gap-2">
            <span>Store links automatically route to your platform of choice.</span>
        </div>
    </div>

    {{-- App Tiles Grid --}}
    <div id="apps-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse ($apps as $app)
            @php
                $storeLinks = $app->store_links;
                $searchKeywords = strtolower($app->name.' '.$app->tagline.' '.$app->developer.' '.$app->badge.' '.implode(' ', array_column($storeLinks, 'platform')).' '.implode(' ', array_column($storeLinks, 'label')));
            @endphp
            <div
                class="app-card group rounded-2xl border border-slate-800 bg-slate-900/60 p-6 flex flex-col justify-between hover:border-slate-700/80 hover:bg-slate-900/90 transition-all duration-200 shadow-xl shadow-black/20"
                data-keywords="{{ $searchKeywords }}"
            >
                <div class="space-y-4">
                    {{-- App Header (Thumbnail & Title) --}}
                    <div class="flex items-start gap-4">
                        <div class="w-16 h-16 rounded-xl overflow-hidden bg-slate-800 border border-slate-700/60 flex-shrink-0 flex items-center justify-center relative shadow-md">
                            @if (filled($app->thumbnail_url))
                                <img
                                    src="{{ $app->thumbnail_url }}"
                                    alt="{{ $app->name }} icon"
                                    class="w-full h-full object-cover"
                                    loading="lazy"
                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                />
                                <div class="hidden w-full h-full items-center justify-center bg-gradient-to-br from-emerald-950 to-slate-900 text-emerald-400 font-mono font-bold text-lg">
                                    {{ Str::limit($app->name, 2, '') }}
                                </div>
                            @else
                                <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-emerald-950/60 to-slate-900">
                                    <svg class="w-8 h-8 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <rect x="3" y="3" width="18" height="18" rx="2" stroke-width="2"/>
                                        <path d="M9 3v18M15 3v18M3 9h18M3 15h18" stroke-width="1" stroke-opacity="0.4"/>
                                    </svg>
                                </div>
                            @endif
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap mb-1">
                                <h2 class="text-lg font-bold text-white tracking-tight leading-snug group-hover:text-emerald-300 transition-colors">
                                    {{ $app->name }}
                                </h2>
                                @if (filled($app->badge))
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-500/10 border border-amber-500/30 text-amber-400">
                                        {{ $app->badge }}
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-400 truncate">
                                {{ $app->developer }}
                            </p>
                        </div>
                    </div>

                    {{-- Tagline & Description --}}
                    @if (filled($app->tagline))
                        <p class="text-xs font-semibold text-emerald-400/90 leading-relaxed">
                            {{ $app->tagline }}
                        </p>
                    @endif

                    @if (filled($app->description))
                        <p class="text-xs text-slate-300 leading-relaxed">
                            {{ $app->description }}
                        </p>
                    @endif
                </div>

                {{-- Store Links --}}
                <div class="mt-6 pt-5 border-t border-slate-800/80 space-y-2.5">
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                        Available On
                    </div>

                    @if (count($storeLinks) > 0)
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach ($storeLinks as $link)
                                <a
                                    href="{{ $link['url'] }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center justify-between px-3 py-2 rounded-lg bg-slate-950/70 border border-slate-800 hover:border-emerald-500/40 hover:bg-slate-800/80 text-slate-200 hover:text-white transition-all text-xs group/btn"
                                    title="{{ $app->name }} on {{ $link['label'] }}"
                                >
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        @if ($link['platform'] === 'apple')
                                            <svg class="w-4 h-4 flex-shrink-0 text-slate-300 group-hover/btn:text-white" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M15.97 6.37c.63-.77 1.06-1.84.94-2.92-1 .04-2.18.66-2.88 1.48-.61.71-1.14 1.83-1 2.9 1.11.09 2.3-.67 2.94-1.46z"/>
                                            </svg>
                                        @elseif ($link['platform'] === 'google')
                                            <svg class="w-4 h-4 flex-shrink-0 text-slate-300 group-hover/btn:text-white" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M3.609 1.814L13.792 12 3.61 22.186a2.38 2.38 0 01-.61-1.636V3.45c0-.624.23-1.2.61-1.636zm11.24 11.24l2.584 2.583-12.04 6.842 9.456-9.425zm0-2.108L5.393 1.52l12.04 6.843-2.584 2.583zm1.48 1.054l3.878 2.203a1.59 1.59 0 000-2.76l-3.878-2.204 1.414 1.38 1.414 1.381-2.828.001z"/>
                                            </svg>
                                        @elseif ($link['platform'] === 'steam')
                                            <svg class="w-4 h-4 flex-shrink-0 text-slate-300 group-hover/btn:text-white" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M11.979 0C5.64 0 .463 4.908.034 11.137l6.634 2.738c.55-.38 1.218-.609 1.94-.609.112 0 .221.008.33.021l3.056-4.428v-.063c0-2.977 2.422-5.398 5.4-5.398 2.977 0 5.398 2.421 5.398 5.398 0 2.978-2.421 5.4-5.398 5.4h-.125l-4.37 3.094c0 .08.008.16.008.243 0 2.054-1.666 3.72-3.72 3.72-1.69 0-3.117-1.129-3.567-2.677L.67 16.924C2.073 21.037 6.67 24 11.979 24 18.618 24 24 18.619 24 11.98 24 5.342 18.618 0 11.979 0z"/>
                                            </svg>
                                        @elseif ($link['platform'] === 'web')
                                            <svg class="w-4 h-4 flex-shrink-0 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"/>
                                                <path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                                            </svg>
                                        @else
                                            <svg class="w-4 h-4 flex-shrink-0 text-slate-300 group-hover/btn:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                        @endif
                                        <div class="truncate text-left">
                                            <div class="font-medium text-slate-200 group-hover/btn:text-white truncate">{{ $link['label'] }}</div>
                                            <div class="text-[10px] text-slate-400 truncate">{{ $link['sublabel'] }}</div>
                                        </div>
                                    </div>
                                    <svg class="w-3 h-3 text-slate-400 group-hover/btn:text-emerald-400 transition-colors flex-shrink-0 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="px-3 py-2 rounded-lg bg-slate-950/40 border border-slate-800 text-xs text-slate-400 italic">
                            Links coming soon.
                        </div>
                    @endif

                    @auth
                        <div class="pt-2 flex items-center justify-end">
                            <a
                                href="{{ route('filament.admin.resources.ctc-apps.edit', ['record' => $app]) }}"
                                class="inline-flex items-center gap-1 text-[11px] font-medium text-amber-400 hover:text-amber-300 transition-colors"
                                title="Edit {{ $app->name }} in Admin Panel"
                            >
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                <span>Edit in Admin</span>
                            </a>
                        </div>
                    @endauth
                </div>
            </div>
        @empty
            <div class="col-span-full py-16 text-center text-slate-400">
                <p class="text-base font-medium text-slate-300">No applications found.</p>
                <p class="text-xs text-slate-400 mt-1">Check back later for new releases.</p>
            </div>
        @endforelse
    </div>

    {{-- Filter Empty State --}}
    <div id="apps-empty-state" class="hidden py-16 text-center text-slate-400">
        <p class="text-base font-medium text-slate-300">No matching apps found</p>
        <p class="text-xs text-slate-400 mt-1">Try refining your search terms or filter keywords.</p>
    </div>

    {{-- Affiliate & Support Notice --}}
    <div class="rounded-xl border border-slate-800 bg-slate-900/40 p-4 text-xs text-slate-400 space-y-1">
        <p class="font-semibold text-slate-300">Supporting This Website</p>
        <p class="leading-relaxed">
            Store links may include affiliate tags where available. When you purchase an app via these links, you help support this website to defray the costs of hosting, development, and ongoing maintenance at no additional cost to you.
        </p>
    </div>
</div>

{{-- Interactive Client-side Filter Script --}}
<script>
    (function () {
        var input = document.getElementById('apps-filter-input');
        var cards = document.querySelectorAll('.app-card');
        var emptyState = document.getElementById('apps-empty-state');

        if (!input || !cards.length) return;

        input.addEventListener('input', function () {
            var query = input.value.trim().toLowerCase();
            var visibleCount = 0;

            cards.forEach(function (card) {
                var keywords = card.getAttribute('data-keywords') || '';
                if (!query || keywords.indexOf(query) !== -1) {
                    card.style.display = '';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            if (emptyState) {
                emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
            }
        });
    })();
</script>
@endsection
