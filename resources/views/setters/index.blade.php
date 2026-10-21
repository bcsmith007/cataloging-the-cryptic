@extends('layouts.app')

@section('title', 'Setters Directory - Cataloging the Cryptic')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
    {{-- Header & Breadcrumbs --}}
    <div class="space-y-3 border-b border-slate-800 pb-6">
        <nav class="flex items-center space-x-2 text-xs text-slate-400">
            <a href="{{ route('home') }}" class="hover:text-emerald-400 transition-colors">Home</a>
            <span>/</span>
            <span class="text-slate-200">Setters</span>
        </nav>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-extrabold text-white tracking-tight">
                    Puzzle Setters
                </h1>
                <p class="text-sm text-slate-400 mt-1">
                    Index of all puzzle creators, authors, and compilers featured on Cracking the Cryptic.
                </p>
            </div>

            <div class="flex items-center gap-3 flex-wrap">
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-slate-900 border border-slate-800 text-slate-300">
                    <span class="text-emerald-400 font-mono font-bold mr-1.5">{{ number_format($setters->count()) }}</span>
                    <span>Distinct {{ Str::plural('Setter', $setters->count()) }}</span>
                </span>
            </div>
        </div>
    </div>

    {{-- Filter Search & Table Controls --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="relative max-w-sm w-full">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <input
                type="text"
                id="setter-table-search"
                placeholder="Filter setters by name or puzzle..."
                class="w-full pl-9 pr-4 py-2 bg-slate-900/80 border border-slate-800 rounded-lg text-sm text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors"
            />
        </div>

        <div class="text-xs text-slate-400 flex items-center gap-2">
            <span>Tip: Click column headers to toggle sorting order.</span>
        </div>
    </div>

    {{-- Sortable Setters Table --}}
    <div class="rounded-xl border border-slate-800 bg-slate-900/60 overflow-hidden shadow-xl shadow-black/20">
        <div class="overflow-x-auto">
            <table id="setters-table" class="min-w-full divide-y divide-slate-800 text-left text-sm">
                <thead class="bg-slate-950/80 text-xs font-semibold uppercase tracking-wider text-slate-400">
                    <tr>
                        {{-- 1. Name Column --}}
                        <th scope="col" class="py-3.5 pl-6 pr-4 cursor-pointer hover:text-white transition-colors select-none group" data-sort-key="name" data-default-dir="asc">
                            <a href="{{ route('setters.index', ['sort' => 'name', 'dir' => ($sort === 'name' && $direction === 'asc') ? 'desc' : 'asc']) }}" class="inline-flex items-center gap-1.5">
                                <span>Setter Name</span>
                                <span class="sort-indicator text-slate-500 group-hover:text-emerald-400 transition-colors">
                                    @if ($sort === 'name')
                                        {{ $direction === 'asc' ? '▲' : '▼' }}
                                    @else
                                        <span class="text-slate-600 opacity-60">⇅</span>
                                    @endif
                                </span>
                            </a>
                        </th>

                        {{-- 2. Number of Puzzles Column --}}
                        <th scope="col" class="py-3.5 px-4 text-center cursor-pointer hover:text-white transition-colors select-none group" data-sort-key="puzzles" data-default-dir="desc">
                            <a href="{{ route('setters.index', ['sort' => 'puzzles', 'dir' => ($sort === 'puzzles' && $direction === 'desc') ? 'asc' : 'desc']) }}" class="inline-flex items-center gap-1.5 justify-center">
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

                        {{-- 3. First Puzzle / Video on Channel --}}
                        <th scope="col" class="py-3.5 px-4 cursor-pointer hover:text-white transition-colors select-none group" data-sort-key="first" data-default-dir="asc">
                            <a href="{{ route('setters.index', ['sort' => 'first', 'dir' => ($sort === 'first' && $direction === 'asc') ? 'desc' : 'asc']) }}" class="inline-flex items-center gap-1.5">
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

                        {{-- 4. Most Recent Puzzle / Video on Channel --}}
                        <th scope="col" class="py-3.5 pl-4 pr-6 cursor-pointer hover:text-white transition-colors select-none group" data-sort-key="recent" data-default-dir="desc">
                            <a href="{{ route('setters.index', ['sort' => 'recent', 'dir' => ($sort === 'recent' && $direction === 'desc') ? 'asc' : 'desc']) }}" class="inline-flex items-center gap-1.5">
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
                <tbody class="divide-y divide-slate-800/80 bg-slate-900/40" id="setters-table-body">
                    @forelse ($setters as $setter)
                        <tr class="setter-row hover:bg-slate-800/40 transition-colors group"
                            data-name="{{ strtolower($setter->name) }}"
                            data-puzzles="{{ $setter->puzzles_count }}"
                            data-first="{{ $setter->first_date?->timestamp ?? 0 }}"
                            data-recent="{{ $setter->recent_date?->timestamp ?? 0 }}">

                            {{-- 1. Name Column --}}
                            <td class="py-4 pl-6 pr-4 align-top">
                                <a href="{{ route('setters.show', $setter->slug) }}" class="inline-flex items-center gap-3 group/link">
                                    <div class="w-9 h-9 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold text-sm shrink-0 group-hover/link:border-emerald-400 transition-colors">
                                        {{ Str::upper(Str::substr($setter->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-white group-hover/link:text-emerald-400 transition-colors">
                                            {{ $setter->name }}
                                        </div>
                                        @if ($setter->bio)
                                            <p class="text-xs text-slate-400 line-clamp-1 max-w-xs mt-0.5">
                                                {{ Str::limit($setter->bio, 80) }}
                                            </p>
                                        @endif
                                    </div>
                                </a>
                            </td>

                            {{-- 2. Number of Puzzles Column --}}
                            <td class="py-4 px-4 align-top text-center">
                                <span class="inline-flex items-center justify-center min-w-[2rem] px-2.5 py-1 rounded-md text-xs font-mono font-bold bg-slate-800/80 text-slate-200 border border-slate-700/80">
                                    {{ number_format($setter->puzzles_count) }}
                                </span>
                            </td>

                            {{-- 3. First Puzzle / Video on Channel --}}
                            <td class="py-4 px-4 align-top">
                                @if ($setter->first_video)
                                    <a href="{{ route('videos.show', $setter->first_video->slug) }}" class="group/vid block max-w-sm">
                                        <div class="font-medium text-slate-200 group-hover/vid:text-emerald-400 transition-colors line-clamp-1">
                                            {{ $setter->first_puzzle?->title ?? $setter->first_video->title }}
                                        </div>
                                        <div class="text-xs text-slate-400 flex items-center gap-1.5 mt-0.5 flex-wrap">
                                            <span class="text-slate-500 truncate max-w-[200px]">{{ $setter->first_video->title }}</span>
                                            <span class="text-slate-600">&bull;</span>
                                            <span class="font-mono text-slate-500 text-[11px] whitespace-nowrap">
                                                {{ $setter->first_date?->format('M j, Y') }}
                                            </span>
                                        </div>
                                    </a>
                                @else
                                    <span class="text-slate-500 italic text-xs">&mdash;</span>
                                @endif
                            </td>

                            {{-- 4. Most Recent Puzzle / Video on Channel --}}
                            <td class="py-4 pl-4 pr-6 align-top">
                                @if ($setter->recent_video)
                                    <a href="{{ route('videos.show', $setter->recent_video->slug) }}" class="group/vid block max-w-sm">
                                        <div class="font-medium text-slate-200 group-hover/vid:text-emerald-400 transition-colors line-clamp-1">
                                            {{ $setter->recent_puzzle?->title ?? $setter->recent_video->title }}
                                        </div>
                                        <div class="text-xs text-slate-400 flex items-center gap-1.5 mt-0.5 flex-wrap">
                                            <span class="text-slate-500 truncate max-w-[200px]">{{ $setter->recent_video->title }}</span>
                                            <span class="text-slate-600">&bull;</span>
                                            <span class="font-mono text-slate-500 text-[11px] whitespace-nowrap">
                                                {{ $setter->recent_date?->format('M j, Y') }}
                                            </span>
                                        </div>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-12 text-center text-slate-400">
                                No setters cataloged yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Interactive Client-side Search Filter & Table Sorting Enhancement --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('setter-table-search');
    const tableBody = document.getElementById('setters-table-body');
    const rows = Array.from(tableBody.querySelectorAll('tr.setter-row'));
    const headers = document.querySelectorAll('#setters-table thead th[data-sort-key]');

    // 1. Live Instant Filter
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            rows.forEach(function (row) {
                const text = row.innerText.toLowerCase();
                if (text.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // 2. Client-side instant column sort on header click
    let currentSortKey = '{{ $sort }}';
    let currentSortDir = '{{ $direction }}';

    headers.forEach(function (th) {
        th.addEventListener('click', function (e) {
            // If user clicked directly on the anchor or header, let JS sort in place
            e.preventDefault();
            const key = th.getAttribute('data-sort-key');
            let nextDir = 'asc';

            if (currentSortKey === key) {
                nextDir = currentSortDir === 'asc' ? 'desc' : 'asc';
            } else {
                nextDir = th.getAttribute('data-default-dir') || 'asc';
            }

            currentSortKey = key;
            currentSortDir = nextDir;

            // Sort rows in DOM
            rows.sort(function (a, b) {
                let valA, valB;
                if (key === 'puzzles' || key === 'first' || key === 'recent') {
                    valA = parseFloat(a.getAttribute('data-' + key)) || 0;
                    valB = parseFloat(b.getAttribute('data-' + key)) || 0;
                    return nextDir === 'asc' ? valA - valB : valB - valA;
                } else {
                    valA = (a.getAttribute('data-name') || '').toLowerCase();
                    valB = (b.getAttribute('data-name') || '').toLowerCase();
                    if (valA < valB) return nextDir === 'asc' ? -1 : 1;
                    if (valA > valB) return nextDir === 'asc' ? 1 : -1;
                    return 0;
                }
            });

            // Re-append sorted rows to table body
            rows.forEach(function (row) {
                tableBody.appendChild(row);
            });

            // Update sort indicators
            headers.forEach(function (otherTh) {
                const otherKey = otherTh.getAttribute('data-sort-key');
                const indicator = otherTh.querySelector('.sort-indicator');
                if (indicator) {
                    if (otherKey === key) {
                        indicator.textContent = nextDir === 'asc' ? '▲' : '▼';
                        indicator.className = 'sort-indicator text-emerald-400 font-bold';
                    } else {
                        indicator.innerHTML = '<span class="text-slate-600 opacity-60">⇅</span>';
                        indicator.className = 'sort-indicator text-slate-500 group-hover:text-emerald-400 transition-colors';
                    }
                }
            });

            // Update URL without full reload for bookmarking
            const url = new URL(window.location);
            url.searchParams.set('sort', key);
            url.searchParams.set('dir', nextDir);
            window.history.replaceState({}, '', url);
        });
    });
});
</script>
@endsection
