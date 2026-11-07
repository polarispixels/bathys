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

    /**
     * The first paragraph after the "# " heading, on one line, markdown left as is.
     *
     * Blocks that start with a heading, table, list, blockquote, rule, or code
     * fence are skipped. So is a block that opens with a bold label such as
     * "**Status:** Accepted": specs, ADRs, and designs begin with those metadata
     * lines, and they are not a summary. Markdown links become their plain text.
     * Empty when the document has no such paragraph.
     */
    public function firstParagraph(): string
    {
        $lines = is_file($this->path) ? file($this->path, FILE_IGNORE_NEW_LINES) : false;
        $seenTitle = false;
        $inFence = false;
        $block = [];

        foreach (array_merge($lines ?: [], ['']) as $line) {
            if (! $seenTitle) {
                $seenTitle = str_starts_with($line, '# ');

                continue;
            }

            if (str_starts_with(ltrim($line), '```')) {
                $inFence = ! $inFence;
                $block = [];

                continue;
            }

            if ($inFence) {
                continue;
            }

            if (trim($line) !== '') {
                $block[] = trim($line);

                continue;
            }

            if ($block !== [] && preg_match('/^(#|\||>|[-*+]\s|\*\*[^*]+:\*\*|\d+[.)]\s|---|\*\*\*)/', $block[0]) !== 1) {
                return (string) preg_replace('/\[([^\]]*)\]\([^)]*\)/', '$1', implode(' ', $block));
            }
            $block = [];
        }

        return '';
    }
}
