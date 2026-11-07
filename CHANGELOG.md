# Changelog

All notable changes to Bathys are documented here.

Format: [Keep a Changelog](https://keepachangelog.com/en/1.1.0/). Versioning: [SemVer](https://semver.org/), with the Bathys-specific rules in [`docs/development.md`](docs/development.md#versioning-and-releases). Document versions (such as the spec's) are tracked inside each document, not here.

## [Unreleased]

### Added
- Product spec (`docs/spec.md`, v0.3), renamed from "Reading Tracker" to Bathys, with a decisions log.
- Architecture Decision Records ADR-0001 to ADR-0009 and an index (`docs/adr/`).
- Development method (`docs/development.md`), deployment and secrets (`docs/deployment.md`), roadmap (`docs/roadmap.md`), and agent orientation (`CLAUDE.md`), drawing on lessons from block-buddies, indigo-nebula, and knight-notes.
- Repository guards: `.gitignore`, and a committed `.claude/settings.json` that denies agent reads of `.env` files.
