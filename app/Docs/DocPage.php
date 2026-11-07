<?php

declare(strict_types=1);

namespace App\Docs;

/**
 * One page of the documentation site: where it lives in the URL, where it
 * lives on disk, what to call it, and which sidebar group it belongs to.
 */
final readonly class DocPage
{
    public function __construct(
        public string $slug,
        public string $path,
        public string $title,
        public string $group,
    ) {}
}
