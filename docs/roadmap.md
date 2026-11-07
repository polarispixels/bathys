# Roadmap

The single place for what's next and what's deliberately deferred. Read this before proposing "new" work: it may already be planned or deliberately deferred.

**Maintenance:** update the status board in the same commit that ships an item (`done vX.Y.Z` plus one line on what was actually built). Reordering the sequence is a deliberate, discussed change.

Phase scope comes from [the spec's phasing](spec.md#suggested-phasing). Steps come from the Phase 1 plan, which becomes `docs/plans/` documents as each stage is designed. Each phase has a matching [GitHub milestone](https://github.com/polarispixels/bathys/milestones); bugs, ideas, and chores are issues in those milestones (see [development](development.md#issues-and-labels)).

---

## Phase 0: Foundations

| Item | Status |
|---|---|
| Product spec (v0.3), ADR-0001 to ADR-0009, README | done (docs only) |
| Development method, deployment and secrets, CLAUDE.md, roadmap, changelog | done (docs only) |

## Phase 1: Shelf and ladder

| Step | Item | Status |
|---|---|---|
| 0 | Scaffold Laravel 13 (React starter kit, Pest, Postgres, Boost, Pint, Larastan); `core/` autoload; architecture tests; version/changelog sync test; CI workflow without deploy | done v0.1.0: scaffold committed untouched, then PHP 8.4 pinned, Boost (Claude Code only), `core/` with dependency rules proven against a planted violation, release-consistency test, version meta tag, CI with Postgres. CI first runs once the GitHub repository exists. Local Postgres not yet installed (not needed until 1d). |
| 1a | Domain core: `DepthLevel`, value objects, `Format`/`Copy`, `Book`, `Pass` and depth as high-water mark, `Checklist`, `ReadingSession`, `Capture`/`Verdict`, `NextUpPicker`, facets | next |
| 1b | Application layer: ports, in-memory doubles, shared repository suite, use cases (`AddBook`, `RecordPass`, `EditPass`, `DeletePass`, `StartSession`, `CompleteSession`, `ResumeSession`, `ListNextUp` …) | planned |
| 1c | OpenAPI contract for Phase 1 resources (**review checkpoint with Ryan**) | planned |
| 1d | Persistence: migrations, `*Record` models, mappers, Eloquent repositories, composition root | planned |
| 1e | JSON:API endpoints, contract tests, passkey auth, **first Forge deploy** (step 17) | planned |
| 1f | Web UI: generated TS types, mobile-first session screen with focus aids, quick "I read this" form, shelf screen, next-up list | planned |
| 1g | Faster ways in: Open Library title search and ISBN lookup, barcode scan, **shelf-photo import** (step 23) | planned |
| | **1.0.0:** Phase 1 complete and in daily use | |

**Open follow-ups** for this phase are GitHub issues in the [Phase 1 milestone](https://github.com/polarispixels/bathys/milestone/1): backups (#6), the user account and passkey (#7), passkey recovery (#8), the queue worker and alerts (#9), the version in the footer (#10), and Forge token renewal (#11).

## Phase 2: MCP and research

MCP server inside the app ([ADR-0009](adr/0009-mcp-server-inside-laravel.md)), add by conversation, review summaries.

## Phase 3: Relationships and discovery

Questions, book-to-book links, syntopical suggestions, related-book discovery, purchase and borrow links.

## Later (not scheduled)

From the spec: Kindle highlight import; StoryBank and COMPASS integrations; mapping questions to work topics; a public recommended-reading page; a native phone app.

## Open questions

None. (The domain was settled November 7, 2026: `bathys.polarispixels.com`, [ADR-0010](adr/0010-host-on-a-polarispixels-subdomain.md).)
