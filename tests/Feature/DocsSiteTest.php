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
