<header class="ctc-public-header sticky top-0 z-40 w-full border-b border-slate-800 bg-slate-950/80 backdrop-blur-md">
    <div class="ctc-nav-inner max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <div class="flex items-center space-x-6">
            <a href="{{ url('/') }}" class="ctc-brand-group flex items-center space-x-3 group">
                <div class="ctc-logo-box w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 font-mono font-bold text-sm group-hover:border-emerald-400 transition-colors">
                    CTC
                </div>
                <span class="ctc-brand-text text-base font-bold tracking-tight text-white group-hover:text-emerald-400 transition-colors">
                    Cataloging the Cryptic
                </span>
            </a>
        </div>

        <nav class="flex items-center space-x-6 text-sm font-medium">
            <a href="{{ route('videos.index') }}" class="ctc-nav-link text-slate-400 hover:text-white transition-colors">Videos</a>
            <a href="{{ route('videos.top') }}" class="ctc-nav-link text-slate-400 hover:text-white transition-colors">Most Viewed</a>
            <a href="{{ route('videos.longest') }}" class="ctc-nav-link text-slate-400 hover:text-white transition-colors">Longest</a>
            <a href="{{ route('videos.shortest') }}" class="ctc-nav-link text-slate-400 hover:text-white transition-colors">Shortest</a>
            <a href="{{ route('setters.index') }}" class="ctc-nav-link text-slate-400 hover:text-white transition-colors">Setters</a>
            <a href="{{ route('stats') }}" class="ctc-nav-link text-slate-400 hover:text-white transition-colors {{ request()->routeIs('stats*') ? 'text-emerald-400 font-semibold' : '' }}">Stats</a>
            <a href="{{ route('videos.random') }}" class="ctc-nav-link text-slate-400 hover:text-white transition-colors flex items-center gap-1.5" title="Take me to a random video">
                <svg width="14" height="14" class="ctc-beaker-icon w-3.5 h-3.5 text-emerald-400" style="width: 14px; height: 14px; min-width: 14px; min-height: 14px; max-width: 14px; max-height: 14px; display: inline-block; vertical-align: middle;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                </svg>
                <span>Random</span>
            </a>
            <a href="{{ route('search') }}" class="ctc-nav-link text-slate-400 hover:text-white transition-colors flex items-center gap-1.5" title="Search the catalog">
                <svg width="14" height="14" class="w-3.5 h-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <span>Search</span>
            </a>
        </nav>
    </div>
</header>
