# Changelog

All notable changes to Bathys are documented here.

Format: [Keep a Changelog](https://keepachangelog.com/en/1.1.0/). Versioning: [SemVer](https://semver.org/), with the Bathys-specific rules in [`docs/development.md`](docs/development.md#versioning-and-releases). Document versions (such as the spec's) are tracked inside each document, not here.

## [Unreleased]

### Changed
- **Re-scoped from a reading tool to a personal book catalog** (spec v0.4): the shelf, the wishlist, editable categories, and one rating per book, the Adler depth level (0 to 5) set directly. Version 1 adds books by hand, by title search, and by ISBN barcode scan. Passes, sessions, checklists, focus aids, captures, sections, the next-up picker, extra facets, relationships, syntopical work, review summaries, discovery, purchase links, and shelf-photo import moved to the spec's Later section.
- Roadmap Phase 1 re-planned for the catalog; Phase 3 folded into Later; GitHub milestones renamed (Phase 1: Catalog, Phase 2: MCP, Later), and the queue-worker issue (#9) moved to Later.
- README, CLAUDE.md, the deployment doc, the coming-soon page, and the GitHub repository description now describe the catalog.
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
