<?php

declare(strict_types=1);

namespace App\Docs;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\MarkdownConverter;

/**
 * Turns a documentation page's markdown into safe HTML.
 *
 * Headings get GitHub-style ids (so `#the-book` links work), raw HTML is
 * stripped, and relative links are rewritten by LinkRewriter.
 */
final class MarkdownRenderer
{
    private readonly LinkRewriter $links;

    private readonly MarkdownConverter $converter;

    public function __construct(
        DocsCatalog $catalog,
        ?string $repositoryUrl,
        string $docsBaseUrl = '/docs',
    ) {
        $this->links = new LinkRewriter($catalog, $repositoryUrl, $docsBaseUrl);

        $environment = new Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'heading_permalink' => [
                'insert' => 'none',
                'apply_id_to_heading' => true,
                'id_prefix' => '',
                'fragment_prefix' => '',
            ],
        ]);
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new GithubFlavoredMarkdownExtension);
        $environment->addExtension(new HeadingPermalinkExtension);
        $environment->addEventListener(DocumentParsedEvent::class, $this->links);

        $this->converter = new MarkdownConverter($environment);
    }

    public function render(DocPage $page): string
    {
        $this->links->forPage($page);

        return $this->converter->convert((string) file_get_contents($page->path))->getContent();
    }

    /** Render a snippet (such as a table cell) as if it sat in docs/, without the wrapping paragraph. */
    public function renderInline(string $markdown): string
    {
        $this->links->forFolder('docs');

        $html = trim($this->converter->convert($markdown)->getContent());

        if (str_starts_with($html, '<p>') && str_ends_with($html, '</p>')) {
            $html = substr($html, 3, -4);
        }

        return $html;
    }
}
