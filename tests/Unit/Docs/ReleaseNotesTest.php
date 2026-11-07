<?php

declare(strict_types=1);

use App\Docs\ReleaseNotes;

it('finds the version, date, and summary paragraph of a release', function () {
    $changelog = "# Changelog\n\n## [Unreleased]\n\n## [0.3.0] - 2026-11-07\n\nThe re-scope to a catalog.\nLive now.\n\n### Added\n- Things\n\n## [0.2.0] - 2026-11-07\n\nOlder.\n";
    $release = ReleaseNotes::latest($changelog, '0.3.0');
    expect($release->date)->toBe('2026-11-07')->and($release->summary)->toBe("The re-scope to a catalog.\nLive now.");
});

it('returns an empty summary when a release has only lists', function () {
    $release = ReleaseNotes::latest("## [0.4.0] - 2026-11-08\n\n### Added\n- A thing\n", '0.4.0');
    expect($release->summary)->toBe('');
});

it('returns null when the version has no entry', function () {
    expect(ReleaseNotes::latest("## [0.3.0] - 2026-11-07\n", '9.9.9'))->toBeNull();
});
