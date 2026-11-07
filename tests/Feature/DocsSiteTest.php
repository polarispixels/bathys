<?php

declare(strict_types=1);

use App\Docs\DocsCatalog;
use Tests\TestCase;

uses(TestCase::class);

it('serves the front page at /docs', function () {
    $this->get('/docs')->assertOk()->assertSee('Bathys');
});

it('renders a document from the repository', function () {
    $this->get('/docs/spec')->assertOk()
        ->assertSee('<h2 id="the-book">The Book</h2>', escape: false)
        ->assertSee('| Bathys docs', escape: false);
});

it('renders the three documents outside docs/', function () {
    $this->get('/docs/readme')->assertOk();
    $this->get('/docs/changelog')->assertOk()->assertSee('Keep a Changelog');
    $this->get('/docs/core')->assertOk();
});

it('serves the markdown source with .md appended', function () {
    $this->get('/docs/spec.md')->assertOk()
        ->assertHeader('Content-Type', 'text/markdown; charset=utf-8')
        ->assertContent((string) file_get_contents(base_path('docs/spec.md')));
    $this->get('/docs/index.md')->assertOk()->assertHeader('Content-Type', 'text/markdown; charset=utf-8');
});

it('is 404 for anything the catalog does not list', function (string $path) {
    $this->get($path)->assertNotFound();
})->with(['/docs/nope', '/docs/nope.md', '/docs/SPEC', '/docs/../.env', '/docs/..%2F.env', '/docs/adr/../../.env', '/docs/.env']);

it('lists every document in the sidebar', function () {
    $response = $this->get('/docs/spec');
    foreach ((new DocsCatalog(base_path()))->all() as $page) {
        $response->assertSee('href="/docs/'.$page->slug.'"', escape: false);
    }
});

it('shows the live version, its release date, and the roadmap on the front page', function () {
    $version = config('app.version');
    $this->get('/docs')->assertOk()
        ->assertSee("Version {$version}")
        ->assertSee('Where things stand')
        ->assertSee('Phase 1: Catalog')
        ->assertSee('Domain core');
});

it('publishes llms.txt with a markdown link for every page', function () {
    config(['app.url' => 'https://example.test/']);
    $response = $this->get('/llms.txt')->assertOk()->assertHeader('Content-Type', 'text/plain; charset=utf-8');
    $base = 'https://example.test';
    $body = (string) $response->getContent();

    expect($body)->toStartWith("# Bathys\n\n> ")
        ->toContain("- [Start here]({$base}/docs/index.md): ");
    foreach ((new DocsCatalog(base_path()))->all() as $page) {
        expect($body)->toContain("({$base}/docs/{$page->slug}.md)");
    }
    expect($body)->not->toContain('http://localhost');
    foreach (explode("\n", $body) as $line) {
        if (str_starts_with($line, '- [')) {
            expect($line)->toMatch('/\): \S/');
        }
    }
});

it('renders front page status cells without raw markdown and with badges', function () {
    $this->get('/docs')->assertOk()
        ->assertDontSee('](designs/', escape: false)
        ->assertSee('badge-done', escape: false);
});

it('links the header back to the docs front page', function () {
    $this->get('/docs/spec')->assertOk()->assertSee('href="/docs"', escape: false);
});

it('sends no cookies from docs routes and marks them cacheable', function () {
    $this->get('/docs')->assertOk()->assertHeaderMissing('Set-Cookie');
    $this->get('/llms.txt')->assertOk()->assertHeaderMissing('Set-Cookie');

    $cache = (string) $this->get('/docs/spec')->assertOk()->headers->get('Cache-Control');
    expect($cache)->toContain('max-age=300')->toContain('public');
});
