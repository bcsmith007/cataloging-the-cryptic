<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-950 text-slate-100 antialiased dark">
<head>
    @if (! request()->is('admin*') && ($googleTagId = config('services.google.tag_id', 'G-R88SRKQ41E')))
        <!-- Google tag (gtag.js) -->
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $googleTagId }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());

            gtag('config', '{{ $googleTagId }}');
        </script>
    @endif
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Cataloging the Cryptic - Comprehensive searchable index for Cracking the Cryptic videos, puzzles, setters, and timestamps.">
    <title>@yield('title', 'Cataloging the Cryptic')</title>

    <link rel="stylesheet" href="{{ asset('fonts/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('css/site.css') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full flex flex-col bg-slate-950 text-slate-200">
    @include('partials.header')

    @if (session('status'))
        <div class="border-b border-emerald-500/30 bg-emerald-950/80 px-4 py-3 text-center text-sm font-medium text-emerald-300">
            <div class="mx-auto max-w-7xl">
                <span>{{ session('status') }}</span>
            </div>
        </div>
    @endif
    @if (session('error'))
        <div class="border-b border-rose-500/30 bg-rose-950/80 px-4 py-3 text-center text-sm font-medium text-rose-300">
            <div class="mx-auto max-w-7xl">
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <main class="flex-1">
        @yield('content')
    </main>

    @include('partials.footer')
</body>
</html>
