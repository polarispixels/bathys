<?php

declare(strict_types=1);

namespace App\Docs;

/**
 * One row of a roadmap table.
 */
final readonly class RoadmapRow
{
    /**
     * @param  string  $step  Empty when the table has no Step column or the cell is empty
     * @param  string  $item  Markdown
     * @param  string  $status  As written
     * @param  'done'|'next'|'planned'|null  $badge  Set when the status starts with that word
     */
    public function __construct(
        public string $step,
        public string $item,
        public string $status,
        public ?string $badge,
    ) {}
}
