<?php

declare(strict_types=1);

namespace App\Docs;

use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;

/**
 * Rewrites the relative links in a markdown document so they work on the site.
 *
 * Authors write links that work on GitHub and in an editor (`../spec.md#x`).
 * Here each one becomes the page URL when the target is in the catalog, a
 * GitHub URL when it is not and a repository URL is set, or plain text
 * otherwise.
 */
final class LinkRewriter
{
    /** The folder of the page being rendered, relative to the repository root. '' is the root. */
    private string $folder = 'docs';

    public function __construct(
        private readonly DocsCatalog $catalog,
        private readonly ?string $repositoryUrl,
        private readonly string $docsBaseUrl,
    ) {}

    public function forPage(DocPage $page): void
    {
        $this->forFolder(match (true) {
            $page->slug === '' => 'docs',
            in_array($page->slug, ['readme', 'changelog'], true) => '',
            $page->slug === 'core' => 'core',
            default => 'docs/'.dirname($page->slug),
        });
    }

    public function forFolder(string $folderFromRepoRoot): void
    {
        $this->folder = trim($folderFromRepoRoot, '/');
    }

    public function __invoke(DocumentParsedEvent $event): void
    {
        $links = [];
        foreach ($event->getDocument()->iterator() as $node) {
            if ($node instanceof Link) {
                $links[] = $node;
            }
        }

        foreach ($links as $link) {
            $url = $link->getUrl();

            if ($url === '' || $url[0] === '#' || $url[0] === '/' || preg_match('/^[a-z][a-z0-9+.-]*:/i', $url) === 1) {
                continue;
            }

            $target = $this->rewrite($url);

            if ($target !== null) {
                $link->setUrl($target);

                continue;
            }

            // No page and no repository URL: keep the words, lose the link.
            foreach ($link->children() as $child) {
                $link->insertBefore($child);
            }
            $link->detach();
        }
    }

    private function rewrite(string $url): ?string
    {
        $suffixAt = strcspn($url, '?#');
        $path = $this->normalize(substr($url, 0, $suffixAt));
        $suffix = substr($url, $suffixAt);

        $slug = $this->catalog->relativePathToSlug($path);

        if ($slug !== null) {
            return rtrim($this->docsBaseUrl.'/'.$slug, '/').$suffix;
        }

        if ($this->repositoryUrl !== null && $this->repositoryUrl !== '') {
            return rtrim($this->repositoryUrl, '/').'/blob/main/'.$path.$suffix;
        }

        return null;
    }

    /** Join the path onto the page's folder and resolve `.` and `..` segments. */
    private function normalize(string $path): string
    {
        $segments = [];

        foreach (explode('/', $this->folder.'/'.$path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                array_pop($segments);

                continue;
            }

            $segments[] = $segment;
        }

        return implode('/', $segments);
    }
}
