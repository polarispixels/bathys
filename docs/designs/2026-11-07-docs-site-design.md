# Design: the documentation site at /docs

**Status:** Frozen, November 7, 2026 (the plan shipped as v0.4.0).
**Roadmap item:** [Phase 1, docs site](../roadmap.md#phase-1-catalog)
**Why:** [ADR-0011](../adr/0011-docs-site-rendered-from-the-repository.md)

---

## Intent

A public page at `bathys.polarispixels.com/docs` where a person or an agent can learn what Bathys is and where it stands, now and six months from now, without reading a GitHub repository. In Ryan's words: "what I like about an explicit docs page is the fact that we can control the content in a way I would say is better than a vanilla GitHub readme file." The status on it updates itself, "always self updating for the most part."

## Goals

1. `/docs` opens to a front page that says what Bathys is, why, and where it stands, and the "where it stands" part is never stale.
2. Every document in the repository's `docs/` folder, plus the README, the changelog, and the core README, is readable on the site, with working links between them.
3. An agent can read each page as plain markdown, and find them all from `/llms.txt`.
4. A new document appears on the site the moment it is committed and deployed. No registry to update.

## Non-goals

- A general CMS, search, comments, or editing on the site.
- Rendering anything outside the documentation set (source code, the OpenAPI contract as HTML; `/docs/api` comes with step 1c).
- The app's visual design. The site uses plain, readable defaults; step 1f's design pass restyles it with the app.
- Dark-mode polish beyond following the system preference.

## Anti-goals

- Any content that must be updated by hand on release. If the release checklist gains a step for this site, the design has failed.
- A URL that can reach a file the catalog doesn't list.

---

## Decisions (November 7, 2026)

| # | Decision | Whose |
|---|---|---|
| 1 | **Public, no sign-in.** The reader may be an agent, or someone Ryan sends the link to. | Ryan (implied by the purpose; recorded as his) |
| 2 | **The front page is two parts:** orientation text Ryan owns in `docs/index.md`, then a status block built from `package.json`, `CHANGELOG.md`, and `docs/roadmap.md`. | Ryan |
| 3 | **Rendered from the repository's markdown, server-side in Blade,** per [ADR-0011](../adr/0011-docs-site-rendered-from-the-repository.md). | Claude |
| 4 | **Markdown is converted with `league/commonmark`** (already installed as a Laravel dependency) with the GitHub-flavored extension (tables, task lists, autolinks) and heading anchors whose ids match GitHub's slugs, so existing `#section` links keep working. HTML inside markdown is stripped and unsafe links dropped, even though the content is ours. | Claude |
| 5 | **Prose is styled with `@tailwindcss/typography`** (`prose` classes), added next to `tailwindcss` in `package.json` and loaded with `@plugin` in `app.css`. It is the standard way to style rendered markdown with Tailwind v4 and will take the app's theme in 1f. | Claude |
| 6 | **URLs mirror the folder layout:** `/docs/spec`, `/docs/adr/0003-hexagonal-architecture`, `/docs/designs/2026-11-07-domain-core-design`. The three files outside `docs/` get `/docs/readme`, `/docs/changelog`, and `/docs/core`. `docs/index.md` is the front page itself at `/docs`. | Claude |
| 7 | **Built first, before the domain core,** as a quick win; every later step then shows up on the site as it lands. | Ryan |

---

## How it works

### The catalog

`App\Docs\DocsCatalog` scans `docs/**/*.md` once per request (about fifteen files; milliseconds) and adds the three extras. For each file it records the slug (the path under `docs/` without `.md`, or the fixed slug for an extra), the absolute file path, the title (the first `# ` heading, or the file name if none), and its group for the sidebar:

| Group | Files | Order |
|---|---|---|
| Start here | the front page, `readme`, `roadmap`, `changelog` | fixed |
| Product | `spec` | fixed |
| Decisions | `adr/README` (as the index), then `adr/NNNN-*` | by number |
| Method | `development`, `deployment`, `ai-tooling` | fixed |
| Designs | `designs/*` | newest first (the date prefix) |
| Plans | `plans/*` | newest first |
| Code | `core` | fixed |

A file in `docs/` that fits no group (a new folder, say) goes in a trailing "Other" group, so it is never hidden. Lookup is always slug to entry in this map; the request path never touches the filesystem. A slug that isn't in the catalog is a 404.

### Rendering

`App\Docs\MarkdownRenderer` wraps a CommonMark environment:

- `CommonMarkCoreExtension`, `GithubFlavoredMarkdownExtension`, `HeadingPermalinkExtension` (ids only, no visible anchor symbol), with `html_input => 'strip'` and `allow_unsafe_links => false`.
- **Link rewriting.** A relative link in a document (`adr/README.md`, `../spec.md#the-book`, `designs/2026-11-07-domain-core-design.md`) is resolved against the document's own folder, and if the target is in the catalog it becomes that page's URL, fragment kept. A relative link to a file that isn't in the catalog (`contracts/openapi.yaml`, `scripts/generate-icons.py`, `.claude/agents/`) becomes a link to that path on GitHub (`https://github.com/polarispixels/bathys/blob/main/<path>`) **when `config('docs.repository_url')` is set**. When it is empty, the link is dropped and its text kept, because the repository is private as of November 7, 2026 and a public reader would get a 404. Whether to make the repository public, or to leave the config empty, is Ryan's call (open question below). Absolute links are untouched either way.
- **Heading ids** use the same slug rule as GitHub: lowercase, spaces to hyphens, punctuation removed, so `## The Book` is `#the-book` and `## Depth` is `#depth`. This needs the extension's `id_prefix` and `fragment_prefix` set to empty strings, `insert` set to `none`, and `apply_id_to_heading` on, or the id lands on a visible `¶` anchor with a `content-` prefix. One known gap, accepted: the library drops underscores where GitHub keeps them, and no heading has one today.
- **`league/commonmark` is declared in `composer.json`** (`^2.10`). It is installed already as a Laravel dependency, but code that uses a library directly names it.

### Routes and views

| Route | Serves |
|---|---|
| `GET /docs` | The front page: `docs/index.md` rendered, then the status block. |
| `GET /docs/{slug}` | A rendered page inside the layout. `{slug}` is constrained to `[A-Za-z0-9][A-Za-z0-9\-/]*` (capitals allowed, so `adr/README` keeps its file name) and must exist in the catalog. |
| `GET /docs/{slug}.md` and `GET /docs/index.md` | The file's markdown, `Content-Type: text/markdown; charset=utf-8`. |
| `GET /llms.txt` | Per [llmstxt.org](https://llmstxt.org): an H1, a one-paragraph summary as a blockquote (`> ...`), then each group as a section listing `[title](https://bathys.polarispixels.com/docs/<slug>.md): one line`. The one line is the first paragraph of the document, truncated. |

Views are Blade under `resources/views/docs/`: a `layout` (header with the Bathys icon and name, the sidebar, the content column, a footer with the version and a link to the repository), `show`, and `index`. Mobile first: the sidebar collapses to a "Contents" disclosure above the content on narrow screens. The layout reads the system color scheme the same way the app shell does.

### The front page status block

Three pieces, each from a file the repository already keeps current, each with a parser small enough to read in one sitting and a test that pins its format:

| Piece | Source | Shown as |
|---|---|---|
| Version | `package.json` `version` (already `config('app.version')`) and the matching `## [X.Y.Z] - YYYY-MM-DD` line in `CHANGELOG.md` | "Version 0.4.0, released November 8, 2026" and a link to the changelog page |
| Latest release | The paragraph under that changelog heading, before the first `###` | One paragraph |
| Where we are | Every `| step | item | status |` row of the phase tables in `docs/roadmap.md` | A compact table per phase: step, item (first sentence), and a badge from the status's first word: `done`, `next`, `planned`, anything else shown as written |

If a parser finds nothing (a format change), the page still renders and the piece shows a one-line "see the roadmap" fallback. The test for each parser runs against the real files, so a format change that breaks the front page fails the build, which is the point.

### Security

- Public, read-only, no forms, no session use.
- The slug is looked up in a scan-built catalog; path traversal has nothing to reach. A test requests `/docs/../.env`, `/docs/..%2F.env`, and `/docs/adr/../../.env` and expects 404.
- Markdown HTML is stripped; unsafe link schemes are dropped.
- The security reviewer reads `docs/` as a stranger would before launch, with the deployment doc in mind. **Merging is the launch** (CI deploys `main`), so that read happens on the branch, and the merge waits for Ryan's go. If the reviewer flags content, what leaves `docs/` is Ryan's decision; there is no feature flag, because a hidden site would be one more thing to remember to turn on.

---

## Edge cases the tests must cover

- A document with no `# ` heading gets its file name as the title.
- A new `docs/anything.md` appears in the sidebar under Other without code changes (the test writes a temporary file into a fake docs root, or the catalog accepts a root path for testing).
- `../spec.md#the-book` from an ADR resolves to `/docs/spec#the-book`; `adr/README.md` from the spec resolves to `/docs/adr/README`; `contracts/openapi.yaml` from CLAUDE.md-style text resolves to GitHub.
- `/docs/spec.md` returns the exact file bytes with the markdown content type; `/docs/nope.md` is 404.
- `/docs/index.md` returns the front page's source.
- `/llms.txt` lists every catalog page and nothing else.
- The front page shows the current version, a changelog paragraph, and at least the 1a row of the roadmap with its status.
- The roadmap parser shows the Phase 0 table's two-column rows (`Item | Status`) with an empty step column, and renders the Phase 1 "1.0.0" row without a step.

## Open question (Ryan), resolved November 7, 2026

**Resolved:** Ryan decided the repository goes public, so `docs.repository_url` defaults to the GitHub repository and the site links to it. The original question, kept for the record:

- **The GitHub repository is private.** The docs link to it in three ways: non-catalog files (the contract, scripts, CLAUDE.md), the footer, and the issue and milestone links already written into the roadmap and README. A stranger gets a 404 on each. Either the repository becomes public (it is a teaching codebase, so this is the natural end state) and the site links to it, or it stays private and `docs.repository_url` stays empty, the footer has no repository link, and the hand-written issue links stay as they are. The build works either way; only the config value changes.

## Out of scope

- `/docs/api` from the OpenAPI contract (step 1c) and `/docs/mcp` (Phase 2).
- Search, a table of contents per page, or "edit on GitHub" links. Easy later; not needed to orient a reader.
- Caching the catalog or rendered HTML. Measure first; a scan and render of fifteen files costs milliseconds.
- Closing #10 (the app version in the footer): this site's footer shows the version, but the app's own footer is still #10.

## Left to the builder

- The exact Tailwind classes and the layout's spacing; keep it plain and readable.
- Where the GitHub base URL lives (`config/app.php` or a constant on the catalog); keep it in one place.
- How the front page's orientation text reads (`docs/index.md`): Claude drafts it from the spec's Intent and the README; Ryan edits it as he likes afterward, since it is his page.

## Changes to other documents

- `docs/roadmap.md`: a "Docs site" row under Phase 1 (added with this design).
- `README.md`: a line pointing at the live docs.
- `CLAUDE.md`: the "Where authority lives" table gains nothing (the site is a view of the same files), but Known traps gains: "A file in `docs/` is public the moment it merges."
- `CHANGELOG.md`: `[Unreleased]` entries as tasks land.
