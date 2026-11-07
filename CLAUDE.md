# CLAUDE.md

Orientation for AI coding sessions in this repository. Humans should start with [`README.md`](README.md).

Bathys is a private, single-user reading tool that measures depth reached in a book (Adler's reading levels) instead of books finished. It is also a **teaching codebase**: clarity of structure and explanation matters as much as working code.

## Where authority lives

| Concern | Authoritative source |
|---|---|
| What the product does, and why | [`docs/spec.md`](docs/spec.md) |
| Why the code is shaped the way it is | [`docs/adr/`](docs/adr/README.md) |
| How we work: lifecycle, testing, versioning, Claude usage | [`docs/development.md`](docs/development.md) |
| How it ships: CI, Forge, secrets | [`docs/deployment.md`](docs/deployment.md) |
| What's next, and what's deliberately deferred | [`docs/roadmap.md`](docs/roadmap.md) |
| The API shape | `contracts/openapi.yaml` (once written; the contract wins over the code) |
| Current behavior | the code and its tests; tests are the most precise record |
| What changed, when | [`CHANGELOG.md`](CHANGELOG.md) and `git log` |

**When docs and code disagree,** don't overwrite either blindly. Read the code and its tests, check `git log` for the change that introduced the difference, then decide: either the doc is stale (fix the doc) or the code drifted from a deliberate contract (fix the code, test-first).

## Hard rules

1. **The dependency rule.** Code in `core/` (namespace `Bathys\`) is plain PHP. It never imports `Illuminate\` or `App\`. Controllers and MCP tools call use cases, never repositories or Eloquent models. Architecture tests enforce this; never weaken a rule to make a change pass. See [ADR-0007](docs/adr/0007-core-beside-app-repository-layout.md).
2. **Contract first.** Change `contracts/openapi.yaml` before changing an endpoint's shape. See [ADR-0005](docs/adr/0005-spec-driven-design-with-an-api-contract.md).
3. **Test first.** Every behavior change starts with a failing test. Every bug fix gets a regression test.
4. **Tests make zero external calls.** Stray HTTP fails the suite. External services are faked or reached through a port.
5. **No secrets anywhere in the repo, in commands, or in permission rules.** Refer to secrets by environment variable name. See [Secrets](docs/deployment.md#secrets-and-configuration).
6. **Product behavior belongs to Ryan.** Domain rules (what counts as a pass, how depth works, what the ladder means) are decided by Ryan and recorded in the spec. Technical choices may be recommended and recorded as ADRs.
7. **Stay in the current phase.** Features listed as later phases in the spec or roadmap are not built as side effects of other work.
8. **Verify before claiming done.** Run the checks and show the output. A deploy is done when the live version string matches the release.
9. **Plain writing.** No em dashes in user-facing copy or in docs; use a comma, colon, parentheses, or a period. Explain the *why* in code comments only where a learner would otherwise get stuck.

## Commands

Not yet available: the Laravel app is scaffolded in Phase 1, step 0. These are the intended commands. Update this section to match `composer.json` and `package.json` when they exist, and never document a command that hasn't been run.

```bash
composer dev        # server, queue, logs, Vite together
composer test       # full PHP suite (Pest), including architecture and contract tests
composer lint       # Pint + Larastan
npm run typecheck   # TypeScript
npm run build       # production frontend build
```

## How work flows

The full method is in [`docs/development.md`](docs/development.md). In short: brainstorm, then a design doc, then a plan, then a test-first build (usually subagent-driven), then a review, then a release.

**Document locations override the superpowers skill defaults:**
- Design docs: `docs/designs/YYYY-MM-DD-<topic>-design.md` (not `docs/superpowers/specs/`)
- Implementation plans: `docs/plans/YYYY-MM-DD-<topic>.md` (not `docs/superpowers/plans/`)
- Working files from subagent runs and brainstorms: `.superpowers/` (gitignored)

## Release checklist

Kept here, not in a skill, because it must run on every release. Details: [Versioning and releases](docs/development.md#versioning-and-releases).

1. Bump the version in `package.json` (SemVer; pre-1.0 rules in the development doc).
2. Add a `## [X.Y.Z] - YYYY-MM-DD` entry to `CHANGELOG.md`, moving items out of `[Unreleased]`.
3. Update `docs/roadmap.md` status, and any doc whose truth changed (spec, ADR status, this file).
4. `composer lint && composer test && npm run typecheck && npm run build` must all pass.
5. Commit `chore(release): vX.Y.Z`, tag `vX.Y.Z`, push with tags. CI deploys; confirm the live version.

## Agents and models

- **Top-level session (the most capable model):** brainstorming, design, plans, architecture, review of subagent output, the trickiest debugging, ship judgment.
- **Sonnet subagents:** well-scoped implementation tasks from a precise brief, each with its own test loop.
- **Haiku subagents:** mechanical work: searches, renames, doc-sync sweeps.
- Work solo when delegating would cost more than it saves. If a session starts on a lesser model with architectural work ahead, say so early.

## Known traps

- **`sessions` is Laravel's table** (HTTP sessions). Ours is `reading_sessions`. The domain says *pass* and *session*; keep them distinct (spec: Passes and Sessions).
- **Inertia props are a second serialization path.** They must go through the JSON:API resources ([ADR-0008](docs/adr/0008-web-ui-shares-the-api-contract.md)), or the contract quietly stops covering the web UI.
- **Two `Book` classes would confuse everyone.** Eloquent models carry a `Record` suffix (`BookRecord`).
- **Laravel Boost may manage its own guidelines block in this file.** Don't hand-edit inside it; regenerate it with Boost's own command, and keep project rules outside it.

## Keeping this file healthy

This file loads into every session. It holds rules, pointers, and traps that must always be in mind, and nothing else.

- **No release history.** "As of v0.4…" belongs in `CHANGELOG.md`.
- **Nothing derivable.** No directory listings or dependency lists; a session can read those.
- **Feature detail goes in a reference doc** under `docs/` with a one-line pointer here.
- **Budget:** keep it under about 15,000 characters (`wc -c CLAUDE.md`). Claude Code truncates at around 150,000, and indigo-nebula hit that limit.
