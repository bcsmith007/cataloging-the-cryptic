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
                <span id="videos-total-badge" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-slate-800 text-slate-300 border border-slate-700">
                    {{ number_format($videos->total()) }} {{ Str::plural('Video', $videos->total()) }}
                </span>
            </div>
        </div>
    </div>

    {{-- Jump to Specific Date Controls --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <form id="video-jump-date-form" method="GET" action="{{ route('videos.index') }}" class="flex flex-wrap items-center gap-2.5">
            <label for="video-jump-date" class="text-xs font-semibold text-slate-300 uppercase tracking-wider font-mono">
                Jump to Date
            </label>
            <input
                type="date"
                id="video-jump-date"
                name="date"
                value="{{ $jumpDate ?? '' }}"
                max="{{ now()->format('Y-m-d') }}"
                class="px-3 py-1.5 bg-slate-900/80 border border-slate-800 rounded-lg text-xs text-white focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors font-mono"
            />
            <button
                type="submit"
                class="px-3.5 py-1.5 rounded-lg text-xs font-semibold bg-emerald-500/15 hover:bg-emerald-500/25 text-emerald-400 border border-emerald-500/30 transition-colors cursor-pointer"
            >
                Jump
            </button>
            <a
                id="video-jump-date-reset"
                href="{{ route('videos.index') }}"
                class="px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-800/80 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700/80 transition-colors @if(empty($jumpDate)) hidden @endif"
            >
                Reset to Latest
            </a>
        </form>

        <div id="videos-showing-summary" class="text-xs text-slate-400 font-mono">
            @if ($jumpDate)
                <span>Showing videos on or before {{ \Carbon\Carbon::parse($jumpDate)->format('M j, Y') }}</span>
            @elseif ($videos->total() > 0)
                <span>Showing 1–{{ number_format($videos->count()) }} of {{ number_format($videos->total()) }}</span>
            @endif
        </div>
    </div>

    {{-- Videos Grid --}}
    <div id="videos-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse ($videos as $video)
            <article class="video-card flex flex-col justify-between rounded-xl transition-all duration-200 overflow-hidden group {{ $video->is_finalized ? 'border border-emerald-500/40 ring-1 ring-emerald-500/20 shadow-[0_0_15px_rgba(16,185,129,0.15)] hover:border-emerald-400/60 hover:shadow-[0_0_20px_rgba(16,185,129,0.25)] bg-slate-900/70' : 'border border-slate-800 bg-slate-900/60 hover:border-slate-700 shadow-lg shadow-black/20' }}" data-finalized="{{ $video->is_finalized ? 'true' : 'false' }}">
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

                        {{-- Video Name --}}
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
                <h3 class="mt-3 text-sm font-semibold text-slate-300">No videos cataloged</h3>
                <p class="mt-1 text-xs text-slate-500">There are currently no videos stored in the database.</p>
            </div>
        @endforelse
    </div>

    {{-- Infinite Scroll Sentinel --}}
    <div
        id="videos-infinite-scroll"
        data-next-page-url="{{ $videos->nextPageUrl() ?? '' }}"
        data-total="{{ $videos->total() }}"
        data-jump-date="{{ $jumpDate ?? '' }}"
        class="pt-4"
    >
        @if ($videos->hasMorePages())
            <div id="videos-scroll-loading" class="flex items-center justify-center gap-2 py-4 text-xs text-slate-400">
                <svg class="w-4 h-4 animate-spin text-emerald-400 hidden" id="videos-scroll-spinner" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span id="videos-scroll-status">Scroll to load more videos...</span>
                <button type="button" id="videos-load-more-btn" class="ml-2 px-3 py-1 rounded-md bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition-colors">
                    Load More
                </button>
            </div>
        @elseif ($videos->total() > 0)
            <div class="text-center py-3 text-xs text-slate-500">
                All {{ number_format($videos->total()) }} {{ Str::plural('video', $videos->total()) }} loaded
            </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const videosGrid = document.getElementById('videos-grid');
    const infiniteContainer = document.getElementById('videos-infinite-scroll');
    const jumpForm = document.getElementById('video-jump-date-form');
    const jumpInput = document.getElementById('video-jump-date');
    const resetLink = document.getElementById('video-jump-date-reset');
    if (!videosGrid || !infiniteContainer) return;

    let scrollController = null;
    let jumpController = null;
    let isLoadingMore = false;
    let observer = null;

    function updateShowingSummary() {
        const summaryEl = document.getElementById('videos-showing-summary');
        if (!summaryEl) return;

        const jumpDate = infiniteContainer.dataset.jumpDate || '';
        if (jumpDate !== '') {
            return;
        }

        const total = parseInt(infiniteContainer.dataset.total || '0', 10);
        const loaded = videosGrid.querySelectorAll('article.video-card').length;
        if (total > 0 && loaded > 0) {
            summaryEl.innerHTML = '<span>Showing 1–' + loaded.toLocaleString() + ' of ' + total.toLocaleString() + '</span>';
        }
    }

    function setupObserver() {
        if (observer) {
            observer.disconnect();
            observer = null;
        }

        const loadMoreBtn = document.getElementById('videos-load-more-btn');
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', function () {
                loadMoreVideos();
            });
        }

        const nextUrl = infiniteContainer.dataset.nextPageUrl || '';
        if (!nextUrl || !('IntersectionObserver' in window)) {
            return;
        }

        observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    loadMoreVideos();
                }
            });
        }, {
            rootMargin: '300px 0px'
        });

        observer.observe(infiniteContainer);
    }

    function loadMoreVideos() {
        const nextUrl = infiniteContainer.dataset.nextPageUrl || '';
        if (!nextUrl || isLoadingMore) return;

        isLoadingMore = true;
        const spinner = document.getElementById('videos-scroll-spinner');
        const statusEl = document.getElementById('videos-scroll-status');
        const loadMoreBtn = document.getElementById('videos-load-more-btn');
        if (spinner) spinner.classList.remove('hidden');
        if (statusEl) statusEl.textContent = 'Loading more videos...';
        if (loadMoreBtn) loadMoreBtn.disabled = true;

        if (scrollController) {
            scrollController.abort();
        }
        scrollController = new AbortController();

        fetch(nextUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            },
            signal: scrollController.signal
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Failed to load more videos');
                }
                return response.text();
            })
            .then(function (html) {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                const newGrid = doc.getElementById('videos-grid');
                if (newGrid) {
                    newGrid.querySelectorAll('article.video-card').forEach(function (card) {
                        videosGrid.appendChild(card);
                    });
                }

                const nextSentinel = doc.getElementById('videos-infinite-scroll');
                if (nextSentinel) {
                    infiniteContainer.dataset.nextPageUrl = nextSentinel.dataset.nextPageUrl || '';
                    infiniteContainer.dataset.total = nextSentinel.dataset.total || infiniteContainer.dataset.total;
                    infiniteContainer.dataset.jumpDate = nextSentinel.dataset.jumpDate || '';
                    infiniteContainer.innerHTML = nextSentinel.innerHTML;
                } else {
                    infiniteContainer.dataset.nextPageUrl = '';
                    infiniteContainer.innerHTML = '';
                }

                updateShowingSummary();
                isLoadingMore = false;
                setupObserver();
            })
            .catch(function (err) {
                isLoadingMore = false;
                if (err && err.name === 'AbortError') {
                    return;
                }
                const spinnerEl = document.getElementById('videos-scroll-spinner');
                const statusText = document.getElementById('videos-scroll-status');
                const btn = document.getElementById('videos-load-more-btn');
                if (spinnerEl) spinnerEl.classList.add('hidden');
                if (statusText) statusText.textContent = 'Could not load more videos.';
                if (btn) btn.disabled = false;
            });
    }

    function applyJumpDate(dateValue) {
        if (!jumpForm) return;
        const url = new URL(jumpForm.action, window.location.origin);
        if (dateValue && dateValue.trim() !== '') {
            url.searchParams.set('date', dateValue.trim());
        }

        if (scrollController) {
            scrollController.abort();
            scrollController = null;
        }
        isLoadingMore = false;

        if (jumpController) {
            jumpController.abort();
        }
        jumpController = new AbortController();

        fetch(url.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            },
            signal: jumpController.signal
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Failed to jump to date');
                }
                return response.text();
            })
            .then(function (html) {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                const newGrid = doc.getElementById('videos-grid');
                if (newGrid) {
                    videosGrid.innerHTML = newGrid.innerHTML;
                }

                ['videos-total-badge', 'videos-showing-summary'].forEach(function (id) {
                    const currentEl = document.getElementById(id);
                    const nextEl = doc.getElementById(id);
                    if (currentEl && nextEl) {
                        currentEl.innerHTML = nextEl.innerHTML;
                    }
                });

                const nextSentinel = doc.getElementById('videos-infinite-scroll');
                if (nextSentinel) {
                    infiniteContainer.dataset.nextPageUrl = nextSentinel.dataset.nextPageUrl || '';
                    infiniteContainer.dataset.total = nextSentinel.dataset.total || '0';
                    infiniteContainer.dataset.jumpDate = nextSentinel.dataset.jumpDate || '';
                    infiniteContainer.innerHTML = nextSentinel.innerHTML;
                }

                if (resetLink) {
                    if (dateValue && dateValue.trim() !== '') {
                        resetLink.classList.remove('hidden');
                    } else {
                        resetLink.classList.add('hidden');
                    }
                }

                setupObserver();
                window.history.replaceState(null, '', url.toString());
            })
            .catch(function (err) {
                if (err && err.name === 'AbortError') {
                    return;
                }
            });
    }

    if (jumpForm && jumpInput) {
        jumpForm.addEventListener('submit', function (e) {
            e.preventDefault();
            applyJumpDate(jumpInput.value);
        });
    }

    if (resetLink && jumpInput) {
        resetLink.addEventListener('click', function (e) {
            e.preventDefault();
            jumpInput.value = '';
            applyJumpDate('');
        });
    }

    setupObserver();
});
</script>
@endsection
