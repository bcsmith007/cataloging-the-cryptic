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

        {{-- Desktop Navigation (hidden on mobile, visible on desktop >= 1024px) --}}
        <nav class="ctc-desktop-nav flex items-center space-x-6 text-sm font-medium">
            <a href="{{ route('videos.index') }}" class="ctc-nav-link text-slate-400 hover:text-white transition-colors {{ request()->routeIs('videos.index') ? 'text-emerald-400 font-semibold' : '' }}">Videos</a>
            <a href="{{ route('videos.top') }}" class="ctc-nav-link text-slate-400 hover:text-white transition-colors {{ request()->routeIs('videos.top') ? 'text-emerald-400 font-semibold' : '' }}">Most Viewed</a>
            <a href="{{ route('videos.longest') }}" class="ctc-nav-link text-slate-400 hover:text-white transition-colors {{ request()->routeIs('videos.longest') ? 'text-emerald-400 font-semibold' : '' }}">Longest</a>
            <a href="{{ route('videos.shortest') }}" class="ctc-nav-link text-slate-400 hover:text-white transition-colors {{ request()->routeIs('videos.shortest') ? 'text-emerald-400 font-semibold' : '' }}">Shortest</a>
            <a href="{{ route('setters.index') }}" class="ctc-nav-link text-slate-400 hover:text-white transition-colors {{ request()->routeIs('setters*') ? 'text-emerald-400 font-semibold' : '' }}">Setters</a>
            <a href="{{ route('apps.index') }}" class="ctc-nav-link text-slate-400 hover:text-white transition-colors {{ request()->routeIs('apps*') ? 'text-emerald-400 font-semibold' : '' }}">Apps</a>
            <a href="{{ route('stats') }}" class="ctc-nav-link text-slate-400 hover:text-white transition-colors {{ request()->routeIs('stats*') ? 'text-emerald-400 font-semibold' : '' }}">Stats</a>
            <a href="{{ route('videos.random') }}" class="ctc-nav-link text-slate-400 hover:text-white transition-colors flex items-center gap-1.5" title="Take me to a random video">
                <svg width="14" height="14" class="ctc-shuffle-icon ctc-beaker-icon w-3.5 h-3.5 text-emerald-400" style="width: 14px; height: 14px; min-width: 14px; min-height: 14px; max-width: 14px; max-height: 14px; display: inline-block; vertical-align: middle;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 4h4v4M18 20h4v-4M2 18h3a5 5 0 004-2l6-8a5 5 0 014-2h3M2 6h3a5 5 0 014 2l6 8a5 5 0 004 2h3" />
                </svg>
                <span>Random</span>
            </a>
            <a href="{{ route('search') }}" class="ctc-nav-link text-slate-400 hover:text-white transition-colors flex items-center gap-1.5 {{ request()->routeIs('search*') ? 'text-emerald-400 font-semibold' : '' }}" title="Search the catalog">
                <svg width="14" height="14" class="w-3.5 h-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <span>Search</span>
            </a>
        </nav>

        {{-- Mobile Hamburger Toggle Button (visible on mobile / narrower screens < 1024px) --}}
        <div class="flex items-center lg:hidden">
            <button
                type="button"
                id="ctc-mobile-menu-btn"
                class="ctc-mobile-toggle-btn"
                aria-controls="ctc-mobile-menu"
                aria-expanded="false"
                aria-label="Toggle navigation menu"
            >
                {{-- Hamburger Icon (visible when menu closed) --}}
                <svg id="ctc-icon-hamburger" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                {{-- Close (X) Icon (visible when menu opened) --}}
                <svg id="ctc-icon-close" class="w-6 h-6" style="display: none;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    {{-- Mobile Menu Dropdown Panel --}}
    <div id="ctc-mobile-menu" class="ctc-mobile-menu px-4 pt-3 pb-4">
        <nav>
            <a href="{{ route('videos.index') }}" class="ctc-mobile-nav-link {{ request()->routeIs('videos.index') ? 'active' : '' }}">
                <span>Videos</span>
            </a>
            <a href="{{ route('videos.top') }}" class="ctc-mobile-nav-link {{ request()->routeIs('videos.top') ? 'active' : '' }}">
                <span>Most Viewed</span>
            </a>
            <a href="{{ route('videos.longest') }}" class="ctc-mobile-nav-link {{ request()->routeIs('videos.longest') ? 'active' : '' }}">
                <span>Longest</span>
            </a>
            <a href="{{ route('videos.shortest') }}" class="ctc-mobile-nav-link {{ request()->routeIs('videos.shortest') ? 'active' : '' }}">
                <span>Shortest</span>
            </a>
            <a href="{{ route('setters.index') }}" class="ctc-mobile-nav-link {{ request()->routeIs('setters*') ? 'active' : '' }}">
                <span>Setters</span>
            </a>
            <a href="{{ route('apps.index') }}" class="ctc-mobile-nav-link {{ request()->routeIs('apps*') ? 'active' : '' }}">
                <span>Apps</span>
            </a>
            <a href="{{ route('stats') }}" class="ctc-mobile-nav-link {{ request()->routeIs('stats*') ? 'active' : '' }}">
                <span>Stats</span>
            </a>
            <a href="{{ route('videos.random') }}" class="ctc-mobile-nav-link">
                <svg width="14" height="14" class="ctc-shuffle-icon ctc-beaker-icon w-3.5 h-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 4h4v4M18 20h4v-4M2 18h3a5 5 0 004-2l6-8a5 5 0 014-2h3M2 6h3a5 5 0 014 2l6 8a5 5 0 004 2h3" />
                </svg>
                <span>Random Video</span>
            </a>
            <a href="{{ route('search') }}" class="ctc-mobile-nav-link {{ request()->routeIs('search*') ? 'active' : '' }}">
                <svg width="14" height="14" class="w-3.5 h-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <span>Search</span>
            </a>
        </nav>
    </div>

    {{-- Accessible Vanilla Mobile Menu Toggle Script --}}
    <script>
        (function () {
            function initMobileMenu() {
                var btn = document.getElementById('ctc-mobile-menu-btn');
                var menu = document.getElementById('ctc-mobile-menu');
                var iconHamburger = document.getElementById('ctc-icon-hamburger');
                var iconClose = document.getElementById('ctc-icon-close');

                if (!btn || !menu) return;
                if (btn.dataset.ctcInit) return;
                btn.dataset.ctcInit = 'true';

                function setMenuOpen(open) {
                    if (open) {
                        menu.classList.add('is-open');
                        btn.setAttribute('aria-expanded', 'true');
                        if (iconHamburger) iconHamburger.style.display = 'none';
                        if (iconClose) iconClose.style.display = 'block';
                    } else {
                        menu.classList.remove('is-open');
                        btn.setAttribute('aria-expanded', 'false');
                        if (iconHamburger) iconHamburger.style.display = 'block';
                        if (iconClose) iconClose.style.display = 'none';
                    }
                }

                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    var isOpen = menu.classList.contains('is-open');
                    setMenuOpen(!isOpen);
                });

                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape' && menu.classList.contains('is-open')) {
                        setMenuOpen(false);
                    }
                });

                document.addEventListener('click', function (e) {
                    if (menu.classList.contains('is-open') && !menu.contains(e.target) && !btn.contains(e.target)) {
                        setMenuOpen(false);
                    }
                });

                window.addEventListener('resize', function () {
                    if (window.innerWidth >= 1024 && menu.classList.contains('is-open')) {
                        setMenuOpen(false);
                    }
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initMobileMenu);
            } else {
                initMobileMenu();
            }
        })();
    </script>
</header>
