<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Docs\DocPage;
use App\Docs\DocsCatalog;
use App\Docs\MarkdownRenderer;
use App\Docs\ReleaseNotes;
use App\Docs\RoadmapBoard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

/**
 * The public documentation site. Every page comes from the catalog, so the
 * request path is only ever a lookup key and never a filesystem path.
 */
final class DocsController
{
    private DocsCatalog $catalog;

    private MarkdownRenderer $renderer;

    public function __construct()
    {
        $this->catalog = new DocsCatalog(base_path());

        $repositoryUrl = config('docs.repository_url');
        $this->renderer = new MarkdownRenderer($this->catalog, is_string($repositoryUrl) ? $repositoryUrl : null);
    }

    public function index(): View
    {
        $release = ReleaseNotes::latest(
            (string) file_get_contents(base_path('CHANGELOG.md')),
            (string) config('app.version'),
        );
        $board = RoadmapBoard::fromMarkdown((string) file_get_contents(base_path('docs/roadmap.md')));

        return $this->page($this->catalog->front(), 'docs.index', [
            'release' => $release,
            'board' => $board,
            'renderer' => $this->renderer,
        ]);
    }

    public function show(string $slug): View
    {
        return $this->page($this->find($slug), 'docs.show');
    }

    public function raw(string $slug): Response
    {
        return $this->markdownResponse($this->find($slug));
    }

    public function rawIndex(): Response
    {
        return $this->markdownResponse($this->catalog->front());
    }

    private function find(string $slug): DocPage
    {
        return $this->catalog->find($slug) ?? abort(404);
    }

    /**
     * @param  view-string  $view
     * @param  array<string, mixed>  $extra
     */
    private function page(DocPage $page, string $view, array $extra = []): View
    {
        return view($view, $extra + [
            'catalog' => $this->catalog,
            'page' => $page,
            'html' => $this->renderer->render($page),
        ]);
    }

    private function markdownResponse(DocPage $page): Response
    {
        return response((string) file_get_contents($page->path), 200, [
            'Content-Type' => 'text/markdown; charset=utf-8',
        ]);
    }
}
