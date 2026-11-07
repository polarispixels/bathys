<?php

declare(strict_types=1);

use App\Docs\DocsCatalog;
use App\Docs\MarkdownRenderer;

beforeEach(function () {
    $this->catalog = new DocsCatalog(dirname(__DIR__, 2).'/Fixtures/docs-site');
    $this->renderer = new MarkdownRenderer($this->catalog, 'https://github.com/polarispixels/bathys');
});

it('gives headings GitHub-style ids with no visible anchor', function () {
    $html = $this->renderer->render($this->catalog->find('spec'));
    expect($html)->toContain('<h2 id="the-book">The Book</h2>')
        ->not->toContain('heading-permalink');
});

it('rewrites a relative link to another document into its page URL, keeping the fragment', function () {
    $html = $this->renderer->render($this->catalog->find('adr/0001-first'));
    expect($html)->toContain('href="/docs/spec#the-book"');
});

it('rewrites a relative link into a sibling folder', function () {
    $html = $this->renderer->render($this->catalog->find('spec'));
    expect($html)->toContain('href="/docs/adr/README"');
});

it('sends a relative link to a file outside the catalog to GitHub when a repository URL is set', function () {
    $html = $this->renderer->render($this->catalog->find('spec'));
    expect($html)->toContain('href="https://github.com/polarispixels/bathys/blob/main/contracts/openapi.yaml"');
});

it('drops the link but keeps the text for a file outside the catalog when there is no repository URL', function () {
    $renderer = new MarkdownRenderer($this->catalog, null);
    $html = $renderer->render($this->catalog->find('spec'));
    expect($html)->toContain('contract')->not->toContain('contracts/openapi.yaml');
});

it('leaves absolute links alone and strips raw HTML', function () {
    $html = $this->renderer->render($this->catalog->find('spec'));
    expect($html)->toContain('href="https://example.com/adler"')->not->toContain('<script>');
});

it('renders tables from GitHub-flavored markdown', function () {
    expect($this->renderer->render($this->catalog->find('roadmap')))->toContain('<table>');
});

it('renders inline markdown without a paragraph wrapper', function () {
    expect($this->renderer->renderInline('Domain core: `Book` and [spec](spec.md)'))
        ->toBe('Domain core: <code>Book</code> and <a href="/docs/spec">spec</a>');
});

it('sends a link to a deleted ADR to GitHub, or keeps only its text when there is no repository URL', function () {
    $html = $this->renderer->render($this->catalog->find('spec'));
    expect($html)->toContain('href="https://github.com/polarispixels/bathys/blob/main/docs/adr/0099-deleted.md"')
        ->not->toContain('/docs/adr/0099-deleted"');

    $plain = (new MarkdownRenderer($this->catalog, null))->render($this->catalog->find('spec'));
    expect($plain)->toContain('gone')->not->toContain('0099');
});
