<?php

declare(strict_types=1);

use App\Docs\DocPage;

/** @var list<string> */
$GLOBALS['docPageTempFiles'] = [];

afterEach(function () {
    foreach ($GLOBALS['docPageTempFiles'] as $file) {
        @unlink($file);
    }
    $GLOBALS['docPageTempFiles'] = [];
});

function docPageFor(string $markdown): DocPage
{
    $path = tempnam(sys_get_temp_dir(), 'docpage');
    $GLOBALS['docPageTempFiles'][] = $path;
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

it('replaces markdown links with their text', function () {
    expect(docPageFor("# T\n\nSee [the spec](spec.md) now.\n")->firstParagraph())->toBe('See the spec now.');
});

it('falls back to the first labeled line when there is no plain paragraph', function () {
    $md = "# Plan\n\n> For agents: read this first.\n\n**Goal:** Ship the shelf.\n\n- a\n- b\n";

    expect(docPageFor($md)->firstParagraph())->toBe('Goal: Ship the shelf.');
});
