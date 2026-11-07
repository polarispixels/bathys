# Roadmap

The single place for what's next and what's deliberately deferred. Read this before proposing "new" work: it may already be planned or deliberately deferred.

**Maintenance:** update the status board in the same commit that ships an item (`done vX.Y.Z` plus one line on what was actually built). Reordering the sequence is a deliberate, discussed change.

Phase scope comes from [the spec's phasing](spec.md#suggested-phasing). Steps come from the Phase 1 plan, which becomes `docs/plans/` documents as each stage is designed. Each phase has a matching [GitHub milestone](https://github.com/polarispixels/bathys/milestones); bugs, ideas, and chores are issues in those milestones (see [development](development.md#issues-and-labels)).

---

## Phase 0: Foundations

| Item | Status |
|---|---|
| Product spec (now v0.5), ADR-0001 to ADR-0010, README | done (docs only) |
| Development method, deployment and secrets, CLAUDE.md, roadmap, changelog | done (docs only) |
| Production at https://bathys.polarispixels.com (Forge, zero-downtime, Let's Encrypt) and the CI deploy job | done: live since v0.2.0; the CI deploy job and the Forge setup shipped in v0.3.0 |
| Issue tracking (milestones, issue forms, triage) and AI tooling (`docs/ai-tooling.md`, four subagents, project plugins) | done v0.3.0 |

## Phase 1: Catalog

| Step | Item | Status |
|---|---|---|
| 0 | Scaffold Laravel 13 (React starter kit, Pest, Postgres, Boost, Pint, Larastan); `core/` autoload; architecture tests; version/changelog sync test; CI workflow without deploy | done v0.1.0: scaffold committed untouched, then PHP 8.4 pinned, Boost (Claude Code only), `core/` with dependency rules proven against a planted violation, release-consistency test, version meta tag, CI with Postgres (Postgres 18 since, matching production). CI has run on every push since the GitHub repository was created November 7, 2026. Local Postgres runs in Docker (`compose.yaml`). |
| docs | Documentation site at `/docs`, rendered from the repository's markdown with a self-updating front page ([design](designs/2026-11-07-docs-site-design.md), [ADR-0011](adr/0011-docs-site-rendered-from-the-repository.md)) | next (built first, as a quick win) |
| 1a | Domain core: `Book`, `BookId`, `Title`, `Author`, `Isbn` (validated ISBN-10 and ISBN-13), `CoverUrl`, `Status` (shelf or wanted), `Category`, `Depth` (0 to 5) | designed ([design](designs/2026-11-07-domain-core-design.md)); next after the docs site |
| 1b | Application layer: ports, in-memory doubles, shared repository suite; use cases to add, edit, and remove a book, move it between the wishlist and the shelf, categorize it, set its depth, manage categories, and list and filter the shelf and the wishlist | planned |
| 1c | OpenAPI contract for Phase 1 resources (**review checkpoint with Ryan**) | planned |
| 1d | Persistence: migrations, `*Record` models, mappers, Eloquent repositories, composition root | planned |
| 1e | JSON:API endpoints, contract tests, passkey sign-in for the one user | planned |
| 1f | Web UI: generated TypeScript types; mobile-first add-a-book and browse screens for the shelf and the wishlist; category management | planned |
| 1g | Faster ways in: Open Library title search and ISBN lookup (a `MetadataLookup` port), barcode scan in the browser | planned |
| | **1.0.0:** Phase 1 complete and in daily use | |

**Open follow-ups** for this phase are GitHub issues in the [Phase 1 milestone](https://github.com/polarispixels/bathys/milestone/1): backups (#6), the user account and passkey (#7), passkey recovery (#8), the version in the footer (#10), Forge token renewal (#11), the quarterly AI tooling review (#13), and confirming subagent dispatch (#14).

## Phase 2: MCP

An MCP server inside the app ([ADR-0009](adr/0009-mcp-server-inside-laravel.md)), so Claude can add and update books by conversation.

## Later (not scheduled)

Everything deferred by the November 7, 2026 re-scope, and older "later" ideas, is listed in [the spec's Later section](spec.md#later): the reading-tool features (passes, sessions, checklists, focus aids, captures, the next-up picker), facets beyond category, relationships and syntopical work, review summaries, discovery, purchase links, shelf-photo import, Kindle highlights, integrations, a public page, and a native app. Their GitHub issues live in the [Later milestone](https://github.com/polarispixels/bathys/milestone/3), for example the queue worker (#9).

## Open questions

None. (The domain was settled November 7, 2026: `bathys.polarispixels.com`, [ADR-0010](adr/0010-host-on-a-polarispixels-subdomain.md).)
