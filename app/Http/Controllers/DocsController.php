<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Docs\DocPage;
use App\Docs\DocsCatalog;
use App\Docs\MarkdownRenderer;
use App\Docs\ReleaseNotes;
use App\Docs\RoadmapBoard;
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

    public function index(): Response
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

    public function show(string $slug): Response
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

    /**
     * A plain-text map of the docs for language models (llmstxt.org): one
     * summary line and one link to the markdown source per page.
     */
    public function llms(): Response
    {
        $base = rtrim((string) config('app.url'), '/');
        $line = fn (DocPage $page, string $title, string $slug): string => sprintf(
            '- [%s](%s/docs/%s.md): %s',
            $title,
            $base,
            $slug === '' ? 'index' : $slug,
            $this->cut($page->firstParagraph(), 160),
        );

        $front = $this->catalog->front();
        $lines = ['# Bathys', '', '> '.$front->firstParagraph()];
        $groups = $this->catalog->groups();

        $frontLine = $line($front, 'Start here', '');

        if (! isset($groups['Start here'])) {
            array_push($lines, '', '## Start here', $frontLine);
        }

        foreach ($groups as $group => $pages) {
            $lines[] = '';
            $lines[] = '## '.$group;
            if ($group === 'Start here') {
                $lines[] = $frontLine;
            }
            foreach ($pages as $page) {
                $lines[] = $line($page, $page->title, $page->slug);
            }
        }

        return $this->cacheable(response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=utf-8']));
    }

    /** Cut at the last space within the limit, dropping trailing punctuation. */
    private function cut(string $text, int $limit): string
    {
        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        $head = mb_substr($text, 0, $limit + 1);
        $space = mb_strrpos($head, ' ');
        $head = $space === false ? mb_substr($text, 0, $limit) : mb_substr($head, 0, $space);

        return rtrim($head, " ,;:-(\u{2013}");
    }

    private function find(string $slug): DocPage
    {
        return $this->catalog->find($slug) ?? abort(404);
    }

    /**
     * @param  view-string  $view
     * @param  array<string, mixed>  $extra
     */
    private function page(DocPage $page, string $view, array $extra = []): Response
    {
        return $this->cacheable(response()->view($view, $extra + [
            'catalog' => $this->catalog,
            'page' => $page,
            'html' => $this->renderer->render($page),
        ]));
    }

    private function markdownResponse(DocPage $page): Response
    {
        return $this->cacheable(response((string) file_get_contents($page->path), 200, [
            'Content-Type' => 'text/markdown; charset=utf-8',
        ]));
    }

    /** The one place the public cache header is set: the docs only change on deploy. */
    private function cacheable(Response $response): Response
    {
        return $response->header('Cache-Control', 'public, max-age=300');
    }
}
