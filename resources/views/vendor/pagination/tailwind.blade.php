@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex flex-col sm:flex-row items-center justify-between gap-4 w-full py-4">
        {{-- Mobile Previous / Next Buttons --}}
        <div class="flex items-center justify-between w-full sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-900/50 border border-slate-800/60 text-slate-600 cursor-not-allowed">
                    Previous
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-900 hover:bg-slate-800 border border-slate-700 text-slate-200 hover:text-white transition-colors">
                    Previous
                </a>
            @endif

            <span class="text-xs font-mono text-slate-400">
                Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}
            </span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-900 hover:bg-slate-800 border border-slate-700 text-slate-200 hover:text-white transition-colors">
                    Next
                </a>
            @else
                <span class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-900/50 border border-slate-800/60 text-slate-600 cursor-not-allowed">
                    Next
                </span>
            @endif
        </div>

        {{-- Desktop Pagination Summary & Numbered Controls --}}
        <div class="hidden sm:flex items-center justify-between w-full">
            <div>
                <p class="text-xs font-mono text-slate-400">
                    Showing
                    @if ($paginator->firstItem())
                        <span class="font-bold text-slate-200">{{ number_format($paginator->firstItem()) }}</span>
                        to
                        <span class="font-bold text-slate-200">{{ number_format($paginator->lastItem()) }}</span>
                    @else
                        <span class="font-bold text-slate-200">{{ number_format($paginator->count()) }}</span>
                    @endif
                    of
                    <span class="font-bold text-emerald-400">{{ number_format($paginator->total()) }}</span>
                    results
                </p>
            </div>

            <div>
                <span class="inline-flex items-center gap-1">
                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="Previous" class="px-2.5 py-1.5 rounded-lg text-xs bg-slate-900/50 border border-slate-800/60 text-slate-600 cursor-not-allowed">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous" class="px-2.5 py-1.5 rounded-lg text-xs bg-slate-900 hover:bg-slate-800 border border-slate-700 text-slate-300 hover:text-white transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </a>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($elements as $element)
                        {{-- "Three Dots" Separator --}}
                        @if (is_string($element))
                            <span aria-disabled="true" class="px-2.5 py-1.5 rounded-lg text-xs font-mono text-slate-500">
                                {{ $element }}
                            </span>
                        @endif

                        {{-- Array Of Links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page" class="px-3 py-1.5 rounded-lg text-xs font-mono font-bold bg-emerald-600 text-white border border-emerald-500 shadow-sm">
                                        {{ $page }}
                                    </span>
                                @else
                                    <a href="{{ $url }}" class="px-3 py-1.5 rounded-lg text-xs font-mono bg-slate-900 hover:bg-slate-800 border border-slate-700 text-slate-300 hover:text-white transition-colors">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next" class="px-2.5 py-1.5 rounded-lg text-xs bg-slate-900 hover:bg-slate-800 border border-slate-700 text-slate-300 hover:text-white transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    @else
                        <span aria-disabled="true" aria-label="Next" class="px-2.5 py-1.5 rounded-lg text-xs bg-slate-900/50 border border-slate-800/60 text-slate-600 cursor-not-allowed">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif
