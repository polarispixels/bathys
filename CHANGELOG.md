# Changelog

All notable changes to Bathys are documented here.

Format: [Keep a Changelog](https://keepachangelog.com/en/1.1.0/). Versioning: [SemVer](https://semver.org/), with the Bathys-specific rules in [`docs/development.md`](docs/development.md#versioning-and-releases). Document versions (such as the spec's) are tracked inside each document, not here.

## [Unreleased]

### Added
- Production: https://bathys.polarispixels.com on Forge (polished-monaco), zero-downtime deployments, Let's Encrypt for the domain and `www`, a read-only GitHub deploy key for Forge.
- The Forge deploy script in the repository (`deploy/forge-deploy.sh`), including the step that shares `storage/` across releases.
- A CI `deploy` job: after the checks pass on `main`, it calls the Forge deploy hook and waits until the live version matches `package.json`. It skips with a warning until the `FORGE_DEPLOY_HOOK` secret exists.

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
