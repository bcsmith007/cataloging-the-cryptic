<footer class="ctc-public-footer border-t border-slate-800 bg-slate-950 py-10 mt-20">
    <div class="ctc-footer-inner max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-xs text-slate-500 space-y-3">
        <div class="ctc-footer-links flex items-center justify-center space-x-6">
            <a href="{{ url('/') }}" class="text-slate-400 hover:text-white transition-colors">Home</a>
            <a href="{{ route('videos.index') }}" class="text-slate-400 hover:text-white transition-colors">All Videos</a>
            <a href="{{ route('videos.top') }}" class="text-slate-400 hover:text-white transition-colors">Most Viewed</a>
            <a href="{{ route('videos.longest') }}" class="text-slate-400 hover:text-white transition-colors">Longest</a>
            <a href="{{ route('videos.shortest') }}" class="text-slate-400 hover:text-white transition-colors">Shortest</a>
            <a href="{{ route('setters.index') }}" class="text-slate-400 hover:text-white transition-colors">Setters</a>
            <a href="{{ route('constraints.index') }}" class="text-slate-400 hover:text-white transition-colors">Constraints</a>
            <a href="{{ route('apps.index') }}" class="text-slate-400 hover:text-white transition-colors">Apps</a>
            <a href="{{ route('stats') }}" class="text-slate-400 hover:text-white transition-colors">Stats</a>
            <a href="{{ route('videos.random') }}" class="text-slate-400 hover:text-white transition-colors">Random</a>
            <a href="{{ route('search') }}" class="text-slate-400 hover:text-white transition-colors">Search</a>
            <a href="{{ route('privacy') }}" class="text-slate-400 hover:text-white transition-colors">Privacy Policy</a>
            <a href="{{ route('markdown.guide') }}" class="text-slate-400 hover:text-white transition-colors">Markdown Guide</a>
            @auth
                <a href="{{ url('/admin') }}" class="text-slate-400 hover:text-white transition-colors">Admin Panel</a>
                <a href="{{ filament()->getProfileUrl() ?? url('/admin/profile') }}" class="text-slate-400 hover:text-white transition-colors">Profile</a>
            @else
                <a href="{{ route('filament.admin.auth.login') }}" class="text-slate-400 hover:text-white transition-colors">Login</a>
                <a href="{{ route('filament.admin.auth.register') }}" class="text-slate-400 hover:text-white transition-colors">Register</a>
            @endauth
        </div>
        <p class="ctc-footer-copy">Cataloging the Cryptic &mdash; An unofficial community directory and archive for Cracking the Cryptic.</p>
        <p class="ctc-footer-sub text-slate-600">All sudoku puzzles and video content belong to their respective creators and setters.</p>
    </div>
</footer>
