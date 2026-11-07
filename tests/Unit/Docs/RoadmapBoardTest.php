<?php

declare(strict_types=1);

use App\Docs\RoadmapBoard;

it('reads phases, steps, and items from the real roadmap', function () {
    // Unit tests don't boot Laravel (tests/Pest.php is empty), so no base_path().
    $phases = RoadmapBoard::fromMarkdown((string) file_get_contents(dirname(__DIR__, 3).'/docs/roadmap.md'));
    $phase1 = array_find($phases, fn ($p) => $p->title === 'Phase 1: Catalog');
    $row1a = array_find($phase1->rows, fn ($r) => $r->step === '1a');
    expect($row1a->step)->toBe('1a')
        ->and($row1a->item)->toStartWith('Domain core')
        ->and($row1a->badge)->toBeIn([null, 'done', 'next', 'planned']);
});

it('badges statuses by their first word and leaves the rest plain', function () {
    $md = "## Phase X\n\n| Step | Item | Status |\n|---|---|---|\n| 1 | A | done v0.1.0: notes |\n| 2 | B | next |\n| 3 | C | planned |\n| 4 | D | waiting on Ryan |\n";
    $rows = RoadmapBoard::fromMarkdown($md)[0]->rows;
    expect(array_map(fn ($r) => $r->badge, $rows))->toBe(['done', 'next', 'planned', null]);
});

it('handles two-column tables and rows without a step', function () {
    $md = "## Phase 0\n\n| Item | Status |\n|---|---|\n| Spec | done (docs only) |\n\n## Phase 1\n\n| Step | Item | Status |\n|---|---|---|\n| | **1.0.0:** done | |\n";
    $phases = RoadmapBoard::fromMarkdown($md);
    expect($phases[0]->rows[0]->step)->toBe('')->and($phases[0]->rows[0]->item)->toBe('Spec');
    expect($phases[1]->rows[0]->step)->toBe('')->and($phases[1]->rows[0]->status)->toBe('');
});

it('leaves out phases with no table', function () {
    expect(RoadmapBoard::fromMarkdown("## Phase 2: MCP\n\nProse only.\n"))->toBe([]);
});
