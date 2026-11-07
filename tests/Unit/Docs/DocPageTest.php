<?php

declare(strict_types=1);

use App\Docs\DocPage;

function docPageFor(string $markdown): DocPage
{
    $path = tempnam(sys_get_temp_dir(), 'docpage');
    file_put_contents($path, $markdown);

    return new DocPage('x', $path, 'X', 'Other');
}

it('skips metadata, tables, lists, code, and quotes to find the real paragraph', function () {
    $md = "# Title\n\n**Document Type:** x\n**Status:** y\n\n| a | b |\n|---|---|\n| 1 | 2 |\n\n- item\n- item\n\n```\ncode\n\ntext in code\n```\n\n> quote\n\nThe real paragraph\ncontinues here.\n\nA later paragraph.\n";

    expect(docPageFor($md)->firstParagraph())->toBe('The real paragraph continues here.');
});

it('is empty when the document has only a heading', function () {
    expect(docPageFor("# Only a heading\n")->firstParagraph())->toBe('');
});
