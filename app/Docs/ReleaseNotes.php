<?php

declare(strict_types=1);

namespace App\Docs;

/**
 * Reads one release entry out of the changelog text.
 */
final class ReleaseNotes
{
    public static function latest(string $changelog, string $version): ?Release
    {
        $heading = '/^## \['.preg_quote($version, '/').'\] - (\d{4}-\d{2}-\d{2})\s*$/';
        $lines = preg_split('/\R/', $changelog) ?: [];

        foreach ($lines as $index => $line) {
            if (preg_match($heading, $line, $match) !== 1) {
                continue;
            }

            // The summary is everything up to the next heading of any level.
            $summary = [];
            foreach (array_slice($lines, $index + 1) as $next) {
                if (str_starts_with($next, '#')) {
                    break;
                }
                $summary[] = $next;
            }

            return new Release($version, $match[1], trim(implode("\n", $summary)));
        }

        return null;
    }
}
