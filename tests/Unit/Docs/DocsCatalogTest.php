<?php

use App\Docs\DocsCatalog;

beforeEach(fn () => $this->catalog = new DocsCatalog(dirname(__DIR__, 2).'/Fixtures/docs-site'));

it('maps a slug to a file under docs', function () {
    expect($this->catalog->find('spec')?->path)->toEndWith('/docs/spec.md');
    expect($this->catalog->find('adr/0001-first')?->title)->toBe('ADR-0001: First');
});

it('exposes the three files outside docs under fixed slugs', function () {
    expect($this->catalog->find('readme')?->title)->toBe('Fixture README');
    expect($this->catalog->find('changelog'))->not->toBeNull();
    expect($this->catalog->find('core')?->path)->toEndWith('/core/README.md');
});

it('returns null for unknown slugs and never touches the filesystem with them', function () {
    expect($this->catalog->find('../.env'))->toBeNull();
    expect($this->catalog->find('SPEC'))->toBeNull();
    expect($this->catalog->find(''))->toBeNull();
});

it('falls back to the file name when a document has no heading', function () {
    expect($this->catalog->find('no-heading')?->title)->toBe('no-heading');
});

it('groups pages in sidebar order', function () {
    $groups = $this->catalog->groups();
    expect(array_keys($groups))->toBe(['Start here', 'Product', 'Decisions', 'Method', 'Designs', 'Plans', 'Code', 'Other']);
    expect(array_map(fn ($p) => $p->slug, $groups['Start here']))->toBe(['readme', 'roadmap', 'changelog']);
    expect(array_map(fn ($p) => $p->slug, $groups['Decisions']))->toBe(['adr/README', 'adr/0001-first', 'adr/0002-second']);
    expect(array_map(fn ($p) => $p->slug, $groups['Designs']))->toBe(['designs/2026-01-02-later-design', 'designs/2026-01-01-earlier-design']);
    expect(array_map(fn ($p) => $p->slug, $groups['Other']))->toBe(['extras/loose', 'no-heading']);
});

it('resolves repository paths to slugs', function () {
    expect($this->catalog->relativePathToSlug('docs/spec.md'))->toBe('spec');
    expect($this->catalog->relativePathToSlug('README.md'))->toBe('readme');
    expect($this->catalog->relativePathToSlug('docs/index.md'))->toBe('');
    expect($this->catalog->relativePathToSlug('contracts/openapi.yaml'))->toBeNull();
});
