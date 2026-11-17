@extends('layouts.app')

@section('title', 'Puzzle Constraints & Rulesets - Cataloging the Cryptic')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
    {{-- Header & Breadcrumbs --}}
    <div class="space-y-3 border-b border-slate-800 pb-6">
        <nav class="flex items-center space-x-2 text-xs text-slate-400">
            <a href="{{ route('home') }}" class="hover:text-emerald-400 transition-colors">Home</a>
            <span>/</span>
            <span class="text-slate-200">Constraints</span>
        </nav>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-extrabold text-white tracking-tight">
                    Puzzle Constraints & Rulesets
                </h1>
                <p class="text-sm text-slate-400 mt-1">
                    Index of variant sudoku rules, clue mechanics, and special constraints featured across the catalog.
                </p>
            </div>

            <div class="flex items-center gap-3 flex-wrap">
                <span id="constraints-total-badge" class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-slate-900 border border-slate-800 text-slate-300">
                    <span class="text-emerald-400 font-mono font-bold mr-1.5">{{ number_format($constraints->total()) }}</span>
                    <span>Distinct {{ Str::plural('Constraint', $constraints->total()) }}</span>
                </span>
            </div>
        </div>
    </div>

    {{-- Filter Search & Table Controls --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <form id="constraint-search-form" method="GET" action="{{ route('constraints.index') }}" class="relative max-w-sm w-full">
            @if ($sort !== 'videos')
                <input type="hidden" name="sort" value="{{ $sort }}">
            @endif
            @if ($direction !== 'desc')
                <input type="hidden" name="dir" value="{{ $direction }}">
            @endif
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <input
                type="text"
                name="q"
                value="{{ $search }}"
                id="constraint-table-search"
                placeholder="Search constraints..."
                autocomplete="off"
                class="w-full pl-9 @if($search !== '') pr-16 @else pr-4 @endif py-2 bg-slate-900/80 border border-slate-800 rounded-lg text-sm text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors"
            />
            <a id="constraint-search-clear"
               href="{{ route('constraints.index', array_filter(['sort' => $sort !== 'videos' ? $sort : null, 'dir' => $direction !== 'desc' ? $direction : null])) }}"
               class="absolute inset-y-0 right-0 pr-3 flex items-center text-xs text-slate-400 hover:text-emerald-400 @if($search === '') hidden @endif"
               title="Clear search">
                ✕ Clear
            </a>
        </form>

        <div id="constraints-showing-summary" class="text-xs text-slate-400 flex items-center gap-2">
            @if ($constraints->total() > 0)
                <span>Showing {{ number_format($constraints->firstItem()) }}–{{ number_format($constraints->lastItem()) }} of {{ number_format($constraints->total()) }}</span>
                <span class="text-slate-600">&bull;</span>
            @endif
            <span>Click column headers to sort</span>
        </div>
    </div>

    {{-- Sortable Constraints Table --}}
    <div class="rounded-xl border border-slate-800 bg-slate-900/60 overflow-hidden shadow-xl shadow-black/20">
        <div class="overflow-x-auto">
            <table id="constraints-table" class="min-w-full divide-y divide-slate-800 text-left text-sm">
                <thead id="constraints-table-head" class="bg-slate-950/80 text-xs font-semibold uppercase tracking-wider text-slate-400">
                    <tr>
                        {{-- 1. Name & Definition Column --}}
                        <th scope="col" class="py-3.5 pl-6 pr-4 cursor-pointer hover:text-white transition-colors select-none group" data-sort-key="name">
                            <a href="{{ route('constraints.index', array_filter(['sort' => 'name', 'dir' => ($sort === 'name' && $direction === 'asc') ? 'desc' : 'asc', 'q' => $search ?: null])) }}" class="inline-flex items-center gap-1.5">
                                <span>Constraint & Definition</span>
                                <span class="sort-indicator text-slate-500 group-hover:text-emerald-400 transition-colors">
                                    @if ($sort === 'name')
                                        {{ $direction === 'asc' ? '▲' : '▼' }}
                                    @else
                                        <span class="text-slate-600 opacity-60">⇅</span>
                                    @endif
                                </span>
                            </a>
                        </th>

                        {{-- 2. Number of Videos Column --}}
                        <th scope="col" class="py-3.5 px-4 text-center cursor-pointer hover:text-white transition-colors select-none group" data-sort-key="videos">
                            <a href="{{ route('constraints.index', array_filter(['sort' => 'videos', 'dir' => ($sort === 'videos' && $direction === 'desc') ? 'asc' : 'desc', 'q' => $search ?: null])) }}" class="inline-flex items-center gap-1.5 justify-center">
                                <span>Videos</span>
                                <span class="sort-indicator text-slate-500 group-hover:text-emerald-400 transition-colors">
                                    @if ($sort === 'videos')
                                        {{ $direction === 'asc' ? '▲' : '▼' }}
                                    @else
                                        <span class="text-slate-600 opacity-60">⇅</span>
                                    @endif
                                </span>
                            </a>
                        </th>

                        {{-- 3. Number of Puzzles Column --}}
                        <th scope="col" class="py-3.5 px-4 text-center cursor-pointer hover:text-white transition-colors select-none group" data-sort-key="puzzles">
                            <a href="{{ route('constraints.index', array_filter(['sort' => 'puzzles', 'dir' => ($sort === 'puzzles' && $direction === 'desc') ? 'asc' : 'desc', 'q' => $search ?: null])) }}" class="inline-flex items-center gap-1.5 justify-center">
                                <span>Puzzles</span>
                                <span class="sort-indicator text-slate-500 group-hover:text-emerald-400 transition-colors">
                                    @if ($sort === 'puzzles')
                                        {{ $direction === 'asc' ? '▲' : '▼' }}
                                    @else
                                        <span class="text-slate-600 opacity-60">⇅</span>
                                    @endif
                                </span>
                            </a>
                        </th>

                        {{-- 4. First Video on Channel --}}
                        <th scope="col" class="py-3.5 px-4 cursor-pointer hover:text-white transition-colors select-none group" data-sort-key="first">
                            <a href="{{ route('constraints.index', array_filter(['sort' => 'first', 'dir' => ($sort === 'first' && $direction === 'asc') ? 'desc' : 'asc', 'q' => $search ?: null])) }}" class="inline-flex items-center gap-1.5">
                                <span>First on Channel</span>
                                <span class="sort-indicator text-slate-500 group-hover:text-emerald-400 transition-colors">
                                    @if ($sort === 'first')
                                        {{ $direction === 'asc' ? '▲' : '▼' }}
                                    @else
                                        <span class="text-slate-600 opacity-60">⇅</span>
                                    @endif
                                </span>
                            </a>
                        </th>

                        {{-- 5. Most Recent Video on Channel --}}
                        <th scope="col" class="py-3.5 pl-4 pr-6 cursor-pointer hover:text-white transition-colors select-none group" data-sort-key="recent">
                            <a href="{{ route('constraints.index', array_filter(['sort' => 'recent', 'dir' => ($sort === 'recent' && $direction === 'desc') ? 'asc' : 'desc', 'q' => $search ?: null])) }}" class="inline-flex items-center gap-1.5">
                                <span>Most Recent on Channel</span>
                                <span class="sort-indicator text-slate-500 group-hover:text-emerald-400 transition-colors">
                                    @if ($sort === 'recent')
                                        {{ $direction === 'asc' ? '▲' : '▼' }}
                                    @else
                                        <span class="text-slate-600 opacity-60">⇅</span>
                                    @endif
                                </span>
                            </a>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80 bg-slate-900/40" id="constraints-table-body">
                    @forelse ($constraints as $constraint)
                        <tr class="constraint-row hover:bg-slate-800/40 transition-colors group"
                            data-name="{{ strtolower($constraint->name) }}"
                            data-description="{{ strtolower(strip_tags(Str::markdown($constraint->description ?? '', ['html_input' => 'strip', 'allow_unsafe_links' => false]))) }}"
                            data-videos="{{ $constraint->videos_count }}"
                            data-puzzles="{{ $constraint->puzzles_count }}"
                            data-first="{{ $constraint->first_date?->timestamp ?? 0 }}"
                            data-recent="{{ $constraint->recent_date?->timestamp ?? 0 }}">

                            {{-- 1. Name & Definition Column --}}
                            <td class="py-4 pl-6 pr-4 align-top">
                                <div class="max-w-md">
                                    <a href="{{ route('constraints.show', $constraint->slug) }}" class="font-bold text-white hover:text-emerald-400 transition-colors">
                                        {{ $constraint->name }}
                                    </a>
                                    @if ($constraint->description)
                                        <div class="ctc-markdown-content ctc-markdown-preview mt-1 leading-relaxed">
                                            {!! Str::markdown($constraint->description, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                                        </div>
                                    @else
                                        <p class="text-xs text-slate-500 italic mt-1">
                                            No definition added yet.
                                        </p>
                                    @endif
                                </div>
                            </td>

                            {{-- 2. Videos Count Column --}}
                            <td class="py-4 px-4 text-center align-top whitespace-nowrap">
                                <a href="{{ route('constraints.show', $constraint->slug) }}" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold font-mono bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500/20 transition-colors">
                                    {{ number_format($constraint->videos_count) }}
                                </a>
                            </td>

                            {{-- 3. Puzzles Count Column --}}
                            <td class="py-4 px-4 text-center align-top whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold font-mono bg-slate-800 text-slate-300 border border-slate-700/60">
                                    {{ number_format($constraint->puzzles_count) }}
                                </span>
                            </td>

                            {{-- 4. First Video Column --}}
                            <td class="py-4 px-4 align-top text-xs">
                                @if ($constraint->first_video && $constraint->first_date)
                                    <div>
                                        <a href="{{ route('videos.show', $constraint->first_video->slug) }}" class="font-medium text-slate-200 hover:text-emerald-400 transition-colors line-clamp-1 max-w-xs" title="{{ $constraint->first_video->title }}">
                                            {{ $constraint->first_video->title }}
                                        </a>
                                        <div class="text-slate-400 text-[11px] font-mono mt-0.5">
                                            {{ \App\Services\TimezoneService::format($constraint->first_date) }}
                                        </div>
                                    </div>
                                @else
                                    <span class="text-slate-500 italic">&mdash;</span>
                                @endif
                            </td>

                            {{-- 5. Most Recent Video Column --}}
                            <td class="py-4 pl-4 pr-6 align-top text-xs">
                                @if ($constraint->recent_video && $constraint->recent_date)
                                    <div>
                                        <a href="{{ route('videos.show', $constraint->recent_video->slug) }}" class="font-medium text-slate-200 hover:text-emerald-400 transition-colors line-clamp-1 max-w-xs" title="{{ $constraint->recent_video->title }}">
                                            {{ $constraint->recent_video->title }}
                                        </a>
                                        <div class="text-slate-400 text-[11px] font-mono mt-0.5">
                                            {{ \App\Services\TimezoneService::format($constraint->recent_date) }}
                                        </div>
                                    </div>
                                @elseif ($constraint->first_video && $constraint->first_date)
                                    <div>
                                        <a href="{{ route('videos.show', $constraint->first_video->slug) }}" class="font-medium text-slate-200 hover:text-emerald-400 transition-colors line-clamp-1 max-w-xs" title="{{ $constraint->first_video->title }}">
                                            {{ $constraint->first_video->title }}
                                        </a>
                                        <div class="text-slate-400 text-[11px] font-mono mt-0.5">
                                            {{ \App\Services\TimezoneService::format($constraint->first_date) }}
                                        </div>
                                    </div>
                                @else
                                    <span class="text-slate-500 italic">&mdash;</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-slate-400 text-sm">
                                @if ($search !== '')
                                    No constraints found matching "{{ $search }}".
                                @else
                                    No constraints cataloged yet.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Infinite Scroll Sentinel --}}
    <div
        id="constraints-infinite-scroll"
        data-next-page-url="{{ $constraints->nextPageUrl() ?? '' }}"
        data-total="{{ $constraints->total() }}"
        data-first-item="{{ $constraints->firstItem() ?? 0 }}"
        class="pt-4"
    >
        @if ($constraints->hasMorePages())
            <div id="constraints-scroll-loading" class="flex items-center justify-center gap-2 py-4 text-xs text-slate-400">
                <svg class="w-4 h-4 animate-spin text-emerald-400 hidden" id="constraints-scroll-spinner" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span id="constraints-scroll-status">Scroll to load more constraints...</span>
                <button type="button" id="constraints-load-more-btn" class="ml-2 px-3 py-1 rounded-md bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition-colors">
                    Load More
                </button>
            </div>
        @elseif ($constraints->total() > 0)
            <div class="text-center py-3 text-xs text-slate-500">
                All {{ number_format($constraints->total()) }} {{ Str::plural('constraint', $constraints->total()) }} loaded
            </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchForm = document.getElementById('constraint-search-form');
    const searchInput = document.getElementById('constraint-table-search');
    const clearButton = document.getElementById('constraint-search-clear');
    const tableBody = document.getElementById('constraints-table-body');
    const infiniteContainer = document.getElementById('constraints-infinite-scroll');
    if (!searchForm || !searchInput || !tableBody || !infiniteContainer) return;

    let debounceTimer = null;
    let activeController = null;
    let scrollController = null;
    let isLoadingMore = false;
    let observer = null;

    function updateShowingSummary() {
        const summaryEl = document.getElementById('constraints-showing-summary');
        if (!summaryEl) return;

        const total = parseInt(infiniteContainer.dataset.total || '0', 10);
        const firstItem = parseInt(infiniteContainer.dataset.firstItem || '1', 10) || 1;
        const loadedRows = tableBody.querySelectorAll('tr.constraint-row').length;

        if (total > 0 && loadedRows > 0) {
            const lastItem = Math.min(total, firstItem + loadedRows - 1);
            summaryEl.innerHTML =
                '<span>Showing ' + firstItem.toLocaleString() + '–' + lastItem.toLocaleString() + ' of ' + total.toLocaleString() + '</span>' +
                '<span class="text-slate-600">&bull;</span>' +
                '<span>Click column headers to sort</span>';
        } else {
            summaryEl.innerHTML = '<span>Click column headers to sort</span>';
        }
    }

    function setupObserver() {
        if (observer) {
            observer.disconnect();
            observer = null;
        }

        const loadMoreBtn = document.getElementById('constraints-load-more-btn');
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', function () {
                loadMoreConstraints();
            });
        }

        const nextUrl = infiniteContainer.dataset.nextPageUrl || '';
        if (!nextUrl || !('IntersectionObserver' in window)) {
            return;
        }

        observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    loadMoreConstraints();
                }
            });
        }, {
            rootMargin: '250px 0px'
        });

        observer.observe(infiniteContainer);
    }

    function loadMoreConstraints() {
        const nextUrl = infiniteContainer.dataset.nextPageUrl || '';
        if (!nextUrl || isLoadingMore) return;

        isLoadingMore = true;
        const spinner = document.getElementById('constraints-scroll-spinner');
        const statusEl = document.getElementById('constraints-scroll-status');
        const loadMoreBtn = document.getElementById('constraints-load-more-btn');
        if (spinner) spinner.classList.remove('hidden');
        if (statusEl) statusEl.textContent = 'Loading more constraints...';
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
                    throw new Error('Failed to load more constraints');
                }
                return response.text();
            })
            .then(function (html) {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                const newBody = doc.getElementById('constraints-table-body');
                if (newBody) {
                    const newRows = newBody.querySelectorAll('tr.constraint-row');
                    newRows.forEach(function (row) {
                        tableBody.appendChild(row);
                        if (window.renderMath) {
                            row.querySelectorAll('.ctc-markdown-content').forEach(function (el) {
                                window.renderMath(el);
                            });
                        }
                    });
                }

                const nextSentinel = doc.getElementById('constraints-infinite-scroll');
                if (nextSentinel) {
                    infiniteContainer.dataset.nextPageUrl = nextSentinel.dataset.nextPageUrl || '';
                    infiniteContainer.dataset.total = nextSentinel.dataset.total || infiniteContainer.dataset.total;
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
                const spinnerEl = document.getElementById('constraints-scroll-spinner');
                const statusText = document.getElementById('constraints-scroll-status');
                const btn = document.getElementById('constraints-load-more-btn');
                if (spinnerEl) spinnerEl.classList.add('hidden');
                if (statusText) statusText.textContent = 'Could not load more constraints.';
                if (btn) btn.disabled = false;
            });
    }

    function updateClearButtonVisibility(value) {
        if (!clearButton) return;
        if (value.trim() !== '') {
            clearButton.classList.remove('hidden');
            searchInput.classList.remove('pr-4');
            searchInput.classList.add('pr-16');
        } else {
            clearButton.classList.add('hidden');
            searchInput.classList.remove('pr-16');
            searchInput.classList.add('pr-4');
        }
    }

    function performLiveSearch(rawQuery) {
        const query = rawQuery.trim();
        const url = new URL(searchForm.action, window.location.origin);

        const sortInput = searchForm.querySelector('input[name="sort"]');
        const dirInput = searchForm.querySelector('input[name="dir"]');
        if (sortInput && sortInput.value) {
            url.searchParams.set('sort', sortInput.value);
        }
        if (dirInput && dirInput.value) {
            url.searchParams.set('dir', dirInput.value);
        }
        if (query !== '') {
            url.searchParams.set('q', query);
        }

        if (scrollController) {
            scrollController.abort();
            scrollController = null;
        }
        isLoadingMore = false;

        if (activeController) {
            activeController.abort();
        }
        activeController = new AbortController();

        fetch(url.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            },
            signal: activeController.signal
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Search request failed');
                }
                return response.text();
            })
            .then(function (html) {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                const newBody = doc.getElementById('constraints-table-body');
                if (newBody) {
                    tableBody.innerHTML = newBody.innerHTML;
                    if (window.renderMath) {
                        tableBody.querySelectorAll('.ctc-markdown-content').forEach(function (el) {
                            window.renderMath(el);
                        });
                    }
                }

                ['constraints-table-head', 'constraints-total-badge', 'constraints-showing-summary'].forEach(function (id) {
                    const currentEl = document.getElementById(id);
                    const nextEl = doc.getElementById(id);
                    if (currentEl && nextEl) {
                        currentEl.innerHTML = nextEl.innerHTML;
                    }
                });

                const nextSentinel = doc.getElementById('constraints-infinite-scroll');
                if (nextSentinel) {
                    infiniteContainer.dataset.nextPageUrl = nextSentinel.dataset.nextPageUrl || '';
                    infiniteContainer.dataset.total = nextSentinel.dataset.total || '0';
                    infiniteContainer.dataset.firstItem = nextSentinel.dataset.firstItem || '0';
                    infiniteContainer.innerHTML = nextSentinel.innerHTML;
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

    searchInput.addEventListener('input', function (e) {
        const value = e.target.value;
        updateClearButtonVisibility(value);

        if (debounceTimer) {
            clearTimeout(debounceTimer);
        }
        debounceTimer = setTimeout(function () {
            performLiveSearch(value);
        }, 200);
    });

    if (clearButton) {
        clearButton.addEventListener('click', function (e) {
            e.preventDefault();
            searchInput.value = '';
            updateClearButtonVisibility('');
            if (debounceTimer) {
                clearTimeout(debounceTimer);
            }
            performLiveSearch('');
            searchInput.focus();
        });
    }

    setupObserver();
});
</script>
@endsection
