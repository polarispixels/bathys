<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Docs\DocPage;
use App\Docs\DocsCatalog;
use App\Docs\MarkdownRenderer;
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
        return $this->page($this->catalog->front(), 'docs.index');
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
     */
    private function page(DocPage $page, string $view): View
    {
        return view($view, [
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
