<?php

declare(strict_types=1);

namespace App\Docs;

use Symfony\Component\Finder\Finder;

/**
 * The list of pages the documentation site serves.
 *
 * It scans docs/**.md once, adds three fixed extras, and keeps a slug => page
 * map. Lookup is always a map lookup, so a request path never reaches the
 * filesystem and a file that is not listed cannot be served.
 */
final class DocsCatalog
{
    /** @var list<string> Sidebar groups, in display order. */
    private const array GROUPS = ['Start here', 'Product', 'Decisions', 'Method', 'Designs', 'Plans', 'Code', 'Other'];

    /** @var array<string, string> Files outside docs/, by path from the repository root. */
    private const array EXTRAS = [
        'README.md' => 'readme',
        'CHANGELOG.md' => 'changelog',
        'core/README.md' => 'core',
    ];

    private const string FRONT_PATH = 'docs/index.md';

    /** @var array<string, DocPage> Slug to page, every page except the front page. */
    private array $pages = [];

    /** @var array<string, string> Path from the repository root to slug, including the front page. */
    private array $slugsByPath = [];

    private DocPage $front;

    public function __construct(private string $repoRoot)
    {
        $this->repoRoot = rtrim($repoRoot, '/');

        $this->front = $this->makePage('', self::FRONT_PATH, 'Start here');
        $this->slugsByPath[self::FRONT_PATH] = '';

        foreach (self::EXTRAS as $relative => $slug) {
            if (is_file($this->repoRoot.'/'.$relative)) {
                $this->add($slug, $relative);
            }
        }

        if (is_dir($this->repoRoot.'/docs')) {
            $docsDir = realpath($this->repoRoot.'/docs');
            $files = Finder::create()->files()->in($this->repoRoot.'/docs')->name('*.md')->sortByName();

            foreach ($files as $file) {
                // Never serve a symlink, or anything that resolves outside docs/.
                $real = $file->getRealPath();
                if ($file->isLink() || $docsDir === false || $real === false || ! str_starts_with($real, $docsDir.DIRECTORY_SEPARATOR)) {
                    continue;
                }

                $relative = 'docs/'.str_replace('\\', '/', $file->getRelativePathname());

                if ($relative !== self::FRONT_PATH) {
                    $this->add(substr($relative, 5, -3), $relative);
                }
            }
        }
    }

    public function find(string $slug): ?DocPage
    {
        return $this->pages[$slug] ?? null;
    }

    /**
     * @return list<DocPage> Every page, in sidebar order.
     */
    public function all(): array
    {
        return array_merge(...array_values($this->groups()));
    }

    /**
     * @return array<string, list<DocPage>> Pages by group name, in group order. Empty groups are left out.
     */
    public function groups(): array
    {
        $buckets = array_fill_keys(self::GROUPS, []);

        foreach ($this->pages as $page) {
            $buckets[$page->group][] = $page;
        }

        $fixed = [
            'Start here' => ['readme', 'roadmap', 'changelog'],
            'Product' => ['spec'],
            'Method' => ['development', 'deployment', 'ai-tooling'],
            'Code' => ['core'],
        ];

        $groups = [];
        foreach ($buckets as $name => $pages) {
            if (isset($fixed[$name])) {
                $order = array_flip($fixed[$name]);
                usort($pages, fn (DocPage $a, DocPage $b): int => $order[$a->slug] <=> $order[$b->slug]);
            } elseif ($name === 'Decisions') {
                // The index first, then the numbered records by file name.
                usort($pages, fn (DocPage $a, DocPage $b): int => [$a->slug !== 'adr/README', $a->slug] <=> [$b->slug !== 'adr/README', $b->slug]);
            } elseif ($name === 'Designs' || $name === 'Plans') {
                // Newest first: the file names start with a date.
                usort($pages, fn (DocPage $a, DocPage $b): int => strcmp($b->slug, $a->slug));
            } else {
                usort($pages, fn (DocPage $a, DocPage $b): int => strcmp($a->slug, $b->slug));
            }

            if ($pages !== []) {
                $groups[$name] = $pages;
            }
        }

        return $groups;
    }

    public function front(): DocPage
    {
        return $this->front;
    }

    /**
     * Turn a path from the repository root ('docs/spec.md') into a slug.
     * The front page is the empty slug. Anything the catalog does not list is null.
     */
    public function relativePathToSlug(string $pathFromRepoRoot): ?string
    {
        return $this->slugsByPath[$pathFromRepoRoot] ?? null;
    }

    private function add(string $slug, string $relative): void
    {
        $this->pages[$slug] = $this->makePage($slug, $relative, $this->groupFor($slug));
        $this->slugsByPath[$relative] = $slug;
    }

    private function groupFor(string $slug): string
    {
        return match (true) {
            in_array($slug, ['readme', 'roadmap', 'changelog'], true) => 'Start here',
            $slug === 'spec' => 'Product',
            str_starts_with($slug, 'adr/') => 'Decisions',
            in_array($slug, ['development', 'deployment', 'ai-tooling'], true) => 'Method',
            str_starts_with($slug, 'designs/') => 'Designs',
            str_starts_with($slug, 'plans/') => 'Plans',
            $slug === 'core' => 'Code',
            default => 'Other',
        };
    }

    private function makePage(string $slug, string $relative, string $group): DocPage
    {
        $path = $this->repoRoot.'/'.$relative;

        return new DocPage($slug, $path, $this->titleOf($path), $group);
    }

    /** The first line that starts with "# ", or the file name without .md. */
    private function titleOf(string $path): string
    {
        $lines = is_file($path) ? file($path, FILE_IGNORE_NEW_LINES) : false;

        foreach ($lines ?: [] as $line) {
            if (str_starts_with($line, '# ')) {
                return trim(substr($line, 2));
            }
        }

        return basename($path, '.md');
    }
}
