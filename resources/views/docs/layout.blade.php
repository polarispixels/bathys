<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="app-version" content="{{ config('app.version') }}">

        <title>{{ $page->title }} | Bathys docs</title>

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.documentElement.classList.add('dark');
                }
            })();
        </script>

        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @vite(['resources/css/app.css'])
    </head>
    <body class="min-h-screen bg-white font-sans text-neutral-900 antialiased dark:bg-neutral-950 dark:text-neutral-100">
        <header class="border-b border-neutral-200 dark:border-neutral-800">
            <div class="mx-auto flex max-w-6xl items-center gap-3 px-4 py-3">
                <a href="/docs" class="flex items-center gap-3 no-underline">
                    <img src="/icon.svg" alt="" class="h-6 w-6">
                    <span class="font-semibold">Bathys</span>
                </a>
                <a href="/" class="ml-auto text-sm underline">Back to the app</a>
            </div>
        </header>

        <div class="mx-auto max-w-6xl px-4 py-6 md:flex md:gap-8">
            {{-- The nav is rendered twice because a closed <details> hides its children
                 at every breakpoint, so one copy cannot serve both phone and desktop. --}}
            <details class="mb-6 md:hidden">
                <summary class="cursor-pointer font-medium">Contents</summary>
                @include('docs.nav')
            </details>
            <aside class="hidden md:block md:w-64 md:shrink-0">
                @include('docs.nav')
            </aside>

            <main class="prose min-w-0 flex-1 dark:prose-invert max-w-none">
                @yield('content')
            </main>
        </div>

        <footer class="border-t border-neutral-200 dark:border-neutral-800">
            <div class="mx-auto flex max-w-6xl gap-4 px-4 py-4 text-sm text-neutral-500 dark:text-neutral-400">
                <span>Bathys v{{ config('app.version') }}</span>
                @if (config('docs.repository_url'))
                    <a href="{{ config('docs.repository_url') }}" class="underline">Source on GitHub</a>
                @endif
            </div>
        </footer>
    </body>
</html>
