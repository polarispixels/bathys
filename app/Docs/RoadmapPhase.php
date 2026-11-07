<?php

declare(strict_types=1);

namespace App\Docs;

/**
 * A roadmap phase: its heading and the rows of its table.
 */
final readonly class RoadmapPhase
{
    /**
     * @param  list<RoadmapRow>  $rows
     */
    public function __construct(
        public string $title,
        public array $rows,
    ) {}
}
