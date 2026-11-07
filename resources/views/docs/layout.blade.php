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
                <img src="/icon.svg" alt="" class="h-6 w-6">
                <span class="font-semibold">Bathys</span>
                <a href="/" class="ml-auto text-sm underline">Back to the app</a>
            </div>
        </header>

        <div class="mx-auto max-w-6xl px-4 py-6 md:flex md:gap-8">
            <details class="mb-6 md:mb-0 md:block md:w-64 md:shrink-0" open>
                <summary class="cursor-pointer font-medium md:hidden">Contents</summary>
                <nav aria-label="Documentation" class="mt-3 space-y-4 text-sm md:mt-0">
                    @foreach ($catalog->groups() as $group => $pages)
                        <div>
                            <h2 class="mb-1 font-semibold text-neutral-500 dark:text-neutral-400">{{ $group }}</h2>
                            <ul class="space-y-1">
                                @foreach ($pages as $item)
                                    <li>
                                        <a href="/docs/{{ $item->slug }}"
                                           @if ($item->slug === $page->slug) aria-current="page" @endif
                                           class="block underline-offset-2 hover:underline aria-[current=page]:font-semibold">{{ $item->title }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </nav>
            </details>

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
