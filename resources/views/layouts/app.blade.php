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
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    },
                    colors: {
                        brand: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            400: '#4ade80',
                            500: '#22c55e',
                            600: '#16a34a',
                            900: '#14532d',
                        },
                        patreon: {
                            500: '#ff424d',
                            600: '#e0323c',
                        }
                    }
                }
            }
        }
    </script>
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
