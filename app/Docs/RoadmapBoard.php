<?php

declare(strict_types=1);

namespace App\Docs;

/**
 * Turns the tables in docs/roadmap.md into a status board.
 */
final class RoadmapBoard
{
    /**
     * @return list<RoadmapPhase>
     */
    public static function fromMarkdown(string $roadmap): array
    {
        $phases = [];
        $title = null;
        /** @var list<RoadmapRow> $rows */
        $rows = [];
        $inTable = false;

        foreach (preg_split('/\R/', $roadmap) ?: [] as $line) {
            if (str_starts_with($line, '## ')) {
                if ($title !== null && $rows !== []) {
                    $phases[] = new RoadmapPhase($title, $rows);
                }
                $rows = [];
                $title = trim(substr($line, 3));
                $inTable = false;

                continue;
            }

            if ($title === null || ! str_starts_with(ltrim($line), '|')) {
                $inTable = false;

                continue;
            }

            // The first line of each table is its header; the next is the |---| separator.
            if (! $inTable) {
                $inTable = true;

                continue;
            }
            if (preg_match('/^\s*\|[\s|:-]+\|\s*$/', $line) === 1) {
                continue;
            }

            $row = self::row($line);
            if ($row !== null) {
                $rows[] = $row;
            }
        }
        if ($title !== null && $rows !== []) {
            $phases[] = new RoadmapPhase($title, $rows);
        }

        return $phases;
    }

    private static function row(string $line): ?RoadmapRow
    {
        $inner = trim(trim($line), '|');
        // Split on pipes that are not escaped as \|.
        $cells = array_map('trim', preg_split('/(?<!\\\\)\|/', $inner) ?: []);

        [$step, $item, $status] = match (count($cells)) {
            2 => ['', $cells[0], $cells[1]],
            3 => [$cells[0], $cells[1], $cells[2]],
            default => [null, null, null],
        };
        if ($item === null) {
            return null;
        }

        $first = strtolower(strtok($status, " \t:,;.(") ?: '');
        $badge = match ($first) {
            'done' => 'done',
            'next' => 'next',
            'planned' => 'planned',
            default => null,
        };

        return new RoadmapRow($step, $item, $status, $badge);
    }
}
