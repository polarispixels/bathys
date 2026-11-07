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
