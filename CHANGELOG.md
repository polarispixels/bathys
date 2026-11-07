# Changelog

All notable changes to Bathys are documented here.

Format: [Keep a Changelog](https://keepachangelog.com/en/1.1.0/). Versioning: [SemVer](https://semver.org/), with the Bathys-specific rules in [`docs/development.md`](docs/development.md#versioning-and-releases). Document versions (such as the spec's) are tracked inside each document, not here.

## [Unreleased]

## [0.5.0] - 2026-11-07

The first useful page: `/` lists the books on the shelf, seeded with eight books, built as a thin slice through every layer the architecture describes. Ryan's call: a minimum viable product that later steps widen.

### Added
- **The shelf home page.** `/` shows the books on the shelf in title order, each with its authors, served by `ShelfController` through the `ListShelf` use case and `BookResource`, the same JSON:API shape the contract describes ([ADR-0008](docs/adr/0008-web-ui-shares-the-api-contract.md)). It replaces the coming-soon page and is public until sign-in exists (spec v0.6, Decisions Log 20).
- **The first domain classes** in `core/`: `Book` with its `Title`, `Author`, `BookId`, and `Status`, plain PHP with framework-free tests, written as the domain core design specifies (Unicode whitespace, NFC normalization, UTC `addedAt`, control and bidirectional characters rejected).
- **Ports and use cases:** `BookRepository`, `Clock`, and `IdGenerator`; `AddBook` and `ListShelf`; in-memory doubles in `tests/Doubles/` and a shared repository suite (`tests/Shared/`) that runs against both the in-memory double and Eloquent.
- **Persistence on Postgres:** the `books` table, `BookRecord`, `BookMapper`, `EloquentBookRepository`, the system clock and UUID v7 adapters, and the `CoreServiceProvider` composition root. Two architecture rules keep `BookRecord` inside the persistence adapter and keep controllers on use cases; both were planted with a violation and seen to fail. The framework test suites now run on Postgres (`bathys_test` locally, the CI service in CI).
- **Seeding that needs nobody:** an idempotent `ShelfSeeder` adds the eight books through `AddBook` for `db:seed`, and a data migration inserts the same rows on deploy, so production seeds itself. The starter kit's test user is no longer created by `db:seed`.
- **Contract types:** `resources/js/types/api.d.ts` is generated from `contracts/openapi.yaml` (`npm run contract:types`) and `composer ci:check` fails if the committed file is stale.
- The home page is rate limited (60 requests a minute per address).

### Fixed
- `llms.txt` summaries fall back to a document's first labeled line when it has no plain paragraph, so plan documents no longer get an empty description.

### Changed
- `README.md` describes what is live; `docs/development.md` records the Postgres test setup and the `Adapters` suite; CLAUDE.md gains the `contract:types` command and the seeding trap.

## [0.4.0] - 2026-11-07

The documentation site, and the repository made public. Read it at https://bathys.polarispixels.com/docs.

### Added
- **The documentation site at `/docs`** ([ADR-0011](docs/adr/0011-docs-site-rendered-from-the-repository.md)): every repository document (`docs/**`, the README, the changelog, `core/README.md`) rendered as a public page with a sidebar, each also served as raw markdown at `/docs/<slug>.md`, and `/llms.txt` as a plain-text map for language models. Relative links between documents are rewritten to site pages, or to GitHub for files outside the set. Styled with `@tailwindcss/typography`.
- **A self-updating "Where things stand" block** on the docs front page: the live version and release date, the latest changelog paragraph, and the roadmap's status tables, read from the repository on every request. The front page's orientation text is `docs/index.md`.
- Docs routes run without sessions or CSRF, are rate limited, and send a public five-minute cache header. Slugs are only ever keys into a catalog built by scanning the repository; symlinked files under `docs/` are never served.
- Design for the domain core, Phase 1 step 1a (`docs/designs/2026-11-07-domain-core-design.md`): the book, its value objects, status, depth, and categories as plain PHP, with a Claude Desktop review applied (NFC normalization and Unicode whitespace for every text value, `https`-only cover URLs, `addedAt` in UTC, `Depth::tryFrom()` at the edges). Spec v0.5 records the three decisions it raised (Decisions Log 17 to 19).
- `composer.json` declares `ext-intl`, `ext-mbstring`, and `league/commonmark`.

### Changed
- **The GitHub repository is public** (Ryan's decision, November 7, 2026), so the docs site can link to it. The history was scanned for secrets first; none were found.
- The deployment doc no longer claims the Forge API token cannot run commands (a token that can manage deploys can replace the deploy script), and no longer names the token file's path or expiry. The environment inventory gains `APP_URL` and `DOCS_REPOSITORY_URL`.
- The release checklist in CLAUDE.md and the pipeline diagram in the deployment doc name the real script, `npm run types:check` (there is no `typecheck` script).
- Roadmap: the docs site was built first, before the domain core, as a quick win; step 1a is designed and next.

## [0.3.0] - 2026-11-07

The re-scope to a book catalog, the production pipeline, and the tooling for the build. Live at https://bathys.polarispixels.com.

### Added
- The Bathys icon: a deep-blue tile with an open book over three depth bars. `scripts/generate-icons.py` draws it from one set of shapes into `public/icon.svg`, `favicon.svg`, `favicon.ico` (16, 32, 48 px), and `apple-touch-icon.png`, replacing the starter kit's icons.
- The coming-soon page shows the icon, and the current version at the bottom. The version reaches every Inertia page as the shared `appVersion` prop, tested against `package.json`.

### Changed
- **Re-scoped from a reading tool to a personal book catalog** (spec v0.4): the shelf, the wishlist, editable categories, and one rating per book, the Adler depth level (0 to 5) set directly. Version 1 adds books by hand, by title search, and by ISBN barcode scan. Passes, sessions, checklists, focus aids, captures, sections, the next-up picker, extra facets, relationships, syntopical work, review summaries, discovery, purchase links, and shelf-photo import moved to the spec's Later section.
- Roadmap Phase 1 re-planned for the catalog; Phase 3 folded into Later; GitHub milestones renamed (Phase 1: Catalog, Phase 2: MCP, Later), and the queue-worker issue (#9) moved to Later.
- README, CLAUDE.md, `core/README.md`, the `security-reviewer` agent, the deployment doc, the coming-soon page, and the GitHub repository description now describe the catalog.
- Doc drift fixed: README status (v0.2.0 is live, with CI deploys since) and PHP 8.4 in its stack; the roadmap's Phase 0 lists ADR-0010, the live production setup, and the CI deploy job, and its step 0 entry no longer says CI is waiting for a GitHub repository.
- `docs/ai-tooling.md` updated for the catalog scope (no session screen, dim mode, or vision adapter in version 1) and checked against the current subagent documentation (`/agents` no longer lists subagents; `claude plugin validate .claude/agents` checks them). `docs/development.md` aligns its CLAUDE.md budget with CLAUDE.md (12,000 characters for our part, 30,000 in total) and names the plugin-review tools.

### Added
- Production: https://bathys.polarispixels.com on Forge (polished-monaco), zero-downtime deployments, Let's Encrypt for the domain and `www`, a read-only GitHub deploy key for Forge.
- The Forge deploy script in the repository (`deploy/forge-deploy.sh`), including the step that shares `storage/` across releases.
- AI tooling (`docs/ai-tooling.md`, drafted in Claude Desktop, reviewed and corrected here): model and effort routing, four committed subagents in `.claude/agents/` (`implementer`, `reviewer`, `security-reviewer`, `scout`), superpowers and php-lsp enabled at project scope, a staged plan for adding tools by roadmap step, and what is deliberately not used.
- Issue tracking: milestones for Phases 1 to 3, Bug and Idea issue forms, a triage routine (`docs/development.md`), and the known Phase 1 follow-ups filed as issues #6 to #11.
- A CI `deploy` job: after the checks pass on `main`, it calls the Forge deploy hook and waits until the live version matches `package.json`. The secret is set, and the job was verified end to end.

### Changed
- Postgres 18 locally and in CI, matching production (polished-monaco runs 18, not 17).

## [0.2.0] - 2026-11-07

A public "coming soon" page, and the app made safe to put on the internet.

### Added
- Coming-soon page at `/`: the name, its meaning, and one sentence on what Bathys does. Replaces the starter kit's welcome page.
- Tests proving registration is unavailable (`GET` and `POST /register` return 404).
- Local Postgres 17 in Docker (`compose.yaml`, port 5433), with a separate `bathys_test` database.
- ADR-0010: host at `bathys.polarispixels.com` on the main Forge server, set up like Bubbles and Bows.
- Issue label convention (MoSCoW, effort, type), matching Ryan's other projects.

### Removed
- Public registration (Fortify's registration feature, the register page, and the login page's "Sign up" link). Bathys has one user, created on the server.

### Changed
- CI runs Node 22, the version on both Forge servers.
- The spec's last open question (domain name) is answered.

## [0.1.0] - 2026-11-07

Foundations: the documents, the scaffold, and the guards that keep the architecture honest. No reading features yet.

### Added
- Product spec (`docs/spec.md`, v0.3), renamed from "Reading Tracker" to Bathys, with a decisions log.
- Architecture Decision Records ADR-0001 to ADR-0009 and an index (`docs/adr/`).
- Development method (`docs/development.md`), deployment and secrets (`docs/deployment.md`), roadmap (`docs/roadmap.md`), and agent orientation (`CLAUDE.md`), drawing on lessons from block-buddies, indigo-nebula, and knight-notes.
- Laravel 13 React starter kit (Inertia, TypeScript, Fortify with passkeys, Wayfinder, Pest, Larastan, Pint).
- Laravel Boost for AI-assisted development, configured for Claude Code only.
- The domain core namespace `Bathys\` in `core/`, with architecture tests that fail the build if the core imports the framework or the app layer.
- A release-consistency test: the build fails if `package.json`'s version has no dated changelog entry.
- The app version in `<meta name="app-version">`, for verifying deploys.
- CI on GitHub Actions with a Postgres service.
- Repository guards: `.gitignore` (which versions agent configuration, unlike the starter kit's) and a committed `.claude/settings.json` that denies agent reads of `.env` files.

### Changed
- PHP pinned to 8.4 everywhere: `composer.json` requires `^8.4` with platform `8.4.1` (the floor Laravel 13's Symfony 8 components need), and CI runs 8.4. CI Node is 24, matching local.
