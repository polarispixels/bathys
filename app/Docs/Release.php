<?php

declare(strict_types=1);

namespace App\Docs;

/**
 * One released version, as written in CHANGELOG.md.
 */
final readonly class Release
{
    /**
     * @param  string  $date  YYYY-MM-DD
     * @param  string  $summary  Markdown; empty when the entry only has lists
     */
    public function __construct(
        public string $version,
        public string $date,
        public string $summary,
    ) {}
}
