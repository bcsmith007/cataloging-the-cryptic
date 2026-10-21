@extends('layouts.app')

@section('title', 'Privacy Policy - Cataloging the Cryptic')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="space-y-8">
        <div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 uppercase tracking-wider mb-3">
                Transparency & Compliance
            </span>
            <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">Privacy Policy</h1>
            <p class="mt-2 text-sm text-slate-400">Effective Date: October 1, 2026 &bull; Last Updated: October 1, 2026</p>
        </div>

        <div class="prose prose-invert max-w-none text-slate-300 space-y-8 text-sm leading-relaxed">
            <section class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-4">
                <h2 class="text-lg font-bold text-white tracking-tight">1. Overview and Core Philosophy</h2>
                <p>
                    Cataloging the Cryptic (catalogingthecryptic.com) is an informational community catalog, archive, and indexing service for Cracking the Cryptic videos, sudoku puzzles, and setters.
                </p>
                <p>
                    We believe in privacy by design. We do not require public user registrations, we do not operate a commercial store, we do not run behavioral advertising networks, and we do not sell or monetize personal data. This document outlines our data processing practices in full compliance with the European General Data Protection Regulation (GDPR / UK GDPR), the California Consumer Privacy Act as amended by the California Privacy Rights Act (CCPA / CPRA), and the ePrivacy Directive.
                </p>
            </section>

            <section class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-4">
                <h2 class="text-lg font-bold text-white tracking-tight">2. Information We Process</h2>
                <ul class="list-disc list-inside space-y-2 text-slate-300">
                    <li>
                        <strong class="text-white">Web Server Access Logs:</strong> Like standard web infrastructure, our hosting environment temporarily logs inbound HTTP requests. These logs record your IP address, browser User-Agent, referring URL, and access timestamp. This information is processed strictly under GDPR Article 6(1)(f) (Legitimate Interests) to defend against DDoS attacks, detect automated malicious scraping, and maintain server reliability. Server logs are rotated regularly and retained for no longer than 30 days.
                    </li>
                    <li>
                        <strong class="text-white">Strictly Necessary Cookies:</strong> We only place functional security tokens (such as <code class="text-emerald-400 font-mono">XSRF-TOKEN</code> and <code class="text-emerald-400 font-mono">laravel_session</code>) essential for preventing Cross-Site Request Forgery and managing authenticated administrative panel sessions. Under the ePrivacy Directive Article 5(3), strictly necessary cookies do not require prior consent banners because they are required to operate the technical service.
                    </li>
                    <li>
                        <strong class="text-white">No Tracking or Advertising Cookies:</strong> We do not deploy third-party advertising cookies, cross-site trackers, or behavioral profiling tools.
                    </li>
                </ul>
            </section>

            <section class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-4">
                <h2 class="text-lg font-bold text-white tracking-tight">3. Self-Hosted Assets and Font Privacy</h2>
                <p>
                    In accordance with European court decisions regarding font provider data transfers, all web fonts utilized on this website (Plus Jakarta Sans and JetBrains Mono) are hosted locally on our own servers. Your web browser fetches font files directly from our domain without establishing connections to Google Fonts, Google CDN, or any external font delivery networks.
                </p>
            </section>

            <section class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-4">
                <h2 class="text-lg font-bold text-white tracking-tight">4. YouTube Video Embeds &amp; Two-Click Privacy Façade</h2>
                <p>
                    When video players are embedded on this website, we employ an interactive two-click privacy façade. Initial page loading only renders locally cached or static poster imagery.
                </p>
                <p>
                    No external network connection is established to YouTube or Google servers, and no third-party cookies or scripts are loaded until you affirmatively click the video play button or seek to a timestamp. Upon your click, video content is loaded via YouTube privacy-enhanced mode (<code class="text-emerald-400 font-mono">youtube-nocookie.com</code>), where playback is governed by Google's Privacy Policy.
                </p>
            </section>

            <section class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-4">
                <h2 class="text-lg font-bold text-white tracking-tight">5. California Privacy Disclosures (CCPA / CPRA)</h2>
                <div class="space-y-3">
                    <p>
                        <strong class="text-white">Notice of Non-Sale and Non-Sharing:</strong> We do not sell, rent, or share consumer personal information with third parties for cross-context behavioral advertising. We have not sold or shared any personal information in the preceding 12 months.
                    </p>
                    <p>
                        <strong class="text-white">Consumer Rights:</strong> California residents have the right to request information regarding categories of data processed, request deletion of any personal information held, and exercise their privacy rights free from discrimination. Because we do not sell personal data, we do not require a &quot;Do Not Sell My Personal Information&quot; opt-out link.
                    </p>
                </div>
            </section>

            <section class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-4">
                <h2 class="text-lg font-bold text-white tracking-tight">6. European Data Subject Rights (GDPR &amp; UK GDPR)</h2>
                <p>
                    Under the General Data Protection Regulation, individuals located in the European Union and the United Kingdom possess rights regarding their personal data:
                </p>
                <ul class="list-disc list-inside space-y-1.5 text-slate-300">
                    <li>The right of access to any personal data processed about you.</li>
                    <li>The right to rectification of inaccurate information.</li>
                    <li>The right to erasure (&quot;right to be forgotten&quot;).</li>
                    <li>The right to restrict or object to data processing based on legitimate interests.</li>
                    <li>The right to lodge a complaint with a supervisory authority in your jurisdiction.</li>
                </ul>
            </section>

            <section class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-4">
                <h2 class="text-lg font-bold text-white tracking-tight">7. External Links and Puzzle Platforms</h2>
                <p>
                    This catalog contains outbound hyperlinks to third-party puzzle platforms and solver tools (including SudokuPad, Penpa, Logic Masters Germany, and Patreon). Clicking an external link directs you to that third-party service, which operates under its own terms of service and privacy practices. We encourage you to review their respective privacy statements.
                </p>
            </section>

            <section class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-4">
                <h2 class="text-lg font-bold text-white tracking-tight">8. Contact Information</h2>
                <p>
                    If you have questions, privacy concerns, or data requests regarding this website, you may reach out to the project administrator at <code class="text-emerald-400 font-mono">privacy@catalogingthecryptic.com</code>.
                </p>
            </section>
        </div>
    </div>
</div>
@endsection
