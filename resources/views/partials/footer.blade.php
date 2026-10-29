<footer class="ctc-public-footer border-t border-slate-800 bg-slate-950 py-12 mt-20">
    <div class="ctc-footer-inner max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-xs text-slate-500">
        <nav class="ctc-footer-links grid grid-cols-2 sm:grid-cols-4 gap-8 w-full max-w-4xl mx-auto text-left mb-8" aria-label="Footer navigation">
            <div class="ctc-footer-group space-y-2.5">
                <h3 class="ctc-footer-heading text-xs font-semibold uppercase tracking-wider text-slate-300">Videos</h3>
                <ul class="ctc-footer-list space-y-2">
                    <li><a href="{{ route('videos.index') }}" class="text-slate-400 hover:text-emerald-400 transition-colors">All Videos</a></li>
                    <li><a href="{{ route('videos.top') }}" class="text-slate-400 hover:text-emerald-400 transition-colors">Most Viewed</a></li>
                    <li><a href="{{ route('videos.longest') }}" class="text-slate-400 hover:text-emerald-400 transition-colors">Longest</a></li>
                    <li><a href="{{ route('videos.shortest') }}" class="text-slate-400 hover:text-emerald-400 transition-colors">Shortest</a></li>
                    <li><a href="{{ route('videos.random') }}" class="text-slate-400 hover:text-emerald-400 transition-colors">Random</a></li>
                </ul>
            </div>

            <div class="ctc-footer-group space-y-2.5">
                <h3 class="ctc-footer-heading text-xs font-semibold uppercase tracking-wider text-slate-300">Directory</h3>
                <ul class="ctc-footer-list space-y-2">
                    <li><a href="{{ route('setters.index') }}" class="text-slate-400 hover:text-emerald-400 transition-colors">Setters</a></li>
                    <li><a href="{{ route('constraints.index') }}" class="text-slate-400 hover:text-emerald-400 transition-colors">Constraints</a></li>
                    <li><a href="{{ route('apps.index') }}" class="text-slate-400 hover:text-emerald-400 transition-colors">Apps</a></li>
                    <li><a href="{{ route('stats') }}" class="text-slate-400 hover:text-emerald-400 transition-colors">Stats</a></li>
                </ul>
            </div>

            <div class="ctc-footer-group space-y-2.5">
                <h3 class="ctc-footer-heading text-xs font-semibold uppercase tracking-wider text-slate-300">Resources</h3>
                <ul class="ctc-footer-list space-y-2">
                    <li><a href="{{ url('/') }}" class="text-slate-400 hover:text-emerald-400 transition-colors">Home</a></li>
                    <li><a href="{{ route('search') }}" class="text-slate-400 hover:text-emerald-400 transition-colors">Search</a></li>
                    <li><a href="{{ route('markdown.guide') }}" class="text-slate-400 hover:text-emerald-400 transition-colors">Markdown Guide</a></li>
                    <li><a href="{{ route('privacy') }}" class="text-slate-400 hover:text-emerald-400 transition-colors">Privacy Policy</a></li>
                </ul>
            </div>

            <div class="ctc-footer-group space-y-2.5">
                <h3 class="ctc-footer-heading text-xs font-semibold uppercase tracking-wider text-slate-300">Account</h3>
                <ul class="ctc-footer-list space-y-2">
                    @auth
                        <li><a href="{{ url('/admin') }}" class="text-slate-400 hover:text-emerald-400 transition-colors">Admin Panel</a></li>
                        <li><a href="{{ filament()->getProfileUrl() ?? url('/admin/profile') }}" class="text-slate-400 hover:text-emerald-400 transition-colors">Profile</a></li>
                    @else
                        <li><a href="{{ route('filament.admin.auth.login') }}" class="text-slate-400 hover:text-emerald-400 transition-colors">Login</a></li>
                        <li><a href="{{ route('filament.admin.auth.register') }}" class="text-slate-400 hover:text-emerald-400 transition-colors">Register</a></li>
                    @endauth
                </ul>
            </div>
        </nav>

        <div class="ctc-footer-bottom border-t border-slate-800/80 pt-6 w-full text-center space-y-1.5">
            <p class="ctc-footer-copy">Cataloging the Cryptic &mdash; An unofficial community directory and archive for Cracking the Cryptic.</p>
            <p class="ctc-footer-sub text-slate-600">All sudoku puzzles and video content belong to their respective creators and setters.</p>
        </div>
    </div>
</footer>
