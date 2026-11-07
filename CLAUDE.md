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
| Bugs, ideas, and follow-up chores | [GitHub Issues](https://github.com/polarispixels/bathys/issues), one milestone per phase; Ryan files them, often from his phone |
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

Every command here has been run in this repo. Keep it that way: never document a command you haven't run.

```bash
docker compose up -d    # local Postgres 18 on :5433 (Docker Desktop: enable WSL integration for the distro)
composer dev            # server, queue, logs, and Vite together
composer test           # Pint check + Larastan (level 7) + the whole Pest suite
composer ci:check       # what CI runs: frontend lint/format, TypeScript, then composer test
composer lint           # fix PHP formatting (Pint)
php artisan test --compact --testsuite=Core   # just the domain core, no framework boot
npm run build           # production frontend build
```

Test suites (`phpunit.xml`): `Core`, `Architecture`, `Unit`, `Feature`. PHP is pinned to 8.4 everywhere ([deployment](docs/deployment.md#php-version-one-number-everywhere)).

## Laravel Boost

Boost (a dev dependency) provides an MCP server with version-correct Laravel docs (`search-docs`), skills in `.claude/skills/`, and the generated guidelines block at the end of this file. Configuration lives in `boost.json` (Claude Code only, no Laravel Cloud). Regenerate with `php artisan boost:update --no-discover`; never hand-edit inside the `<laravel-boost-guidelines>` block.

**Boost's guidelines are generic Laravel advice. Where they conflict with this file or an ADR, this file and the ADRs win:**

| Boost says | Bathys does |
|---|---|
| Don't create new base folders | `core/` and `contracts/` are approved ([ADR-0007](docs/adr/0007-core-beside-app-repository-layout.md), [ADR-0005](docs/adr/0005-spec-driven-design-with-an-api-contract.md)) |
| Only create docs when asked | Design docs, plans, and changelog entries are part of every change ([development](docs/development.md)) |
| Most tests should be feature tests | Most tests are framework-free `tests/Core/` tests |
| Default to Eloquent API Resources and API versioning | JSON:API resources shaped by `contracts/openapi.yaml`; Eloquent never crosses into `core/` |
| Use `make:class` for plain PHP classes | Core classes are written by hand in `core/` (generators target `app/`) |
| Use factories for test models | Core tests build plain domain objects |
| Deploy with Laravel Cloud | Forge, after CI passes ([deployment](docs/deployment.md)) |

Everything else in the block (PHP style, Pint, Inertia v3 notes, `search-docs` before ecosystem changes) applies.

## How work flows

The full method is in [`docs/development.md`](docs/development.md). In short: brainstorm, then a design doc, then a plan, then a test-first build (usually subagent-driven), then a review, then a release.

**Before planning, triage issues:** any issue without a milestone gets a `moscow:`, `effort:`, and `type:` label and a milestone ([triage](docs/development.md#triage)). Pending work that someone must *do* gets an issue, not only a line in a doc. You do all GitHub work (issues, labels, PRs, merges); don't hand Ryan `gh` commands.

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

- **The production server hosts other sites,** including polarispixels.com itself. SSH as `forge@` and confirm you are in `/home/forge/bathys.polarispixels.com/current` before any artisan command. The `polaris` SSH alias reaches this server; Bubbles is on a different one.
- **`sessions` is Laravel's table** (HTTP sessions). Ours is `reading_sessions`. The domain says *pass* and *session*; keep them distinct (spec: Passes and Sessions).
- **Inertia props are a second serialization path.** They must go through the JSON:API resources ([ADR-0008](docs/adr/0008-web-ui-shares-the-api-contract.md)), or the contract quietly stops covering the web UI.
- **Two `Book` classes would confuse everyone.** Eloquent models carry a `Record` suffix (`BookRecord`).
- **`boost:install` run non-interactively records no agents,** and `boost:update` then refuses to run. `boost.json` must list `"agents": ["claude_code"]`. The installer also writes a Windows-side `wsl.exe` command into `.mcp.json`; ours runs `php artisan boost:mcp` from the project root.
- **The Laravel installer can write `APP_URL` with a doubled port** (`http://localhost:8000:8000`), which breaks every artisan command with "Host is malformed". Check `.env` after a fresh `composer setup`.
- **A piped check can't stop a commit.** `composer ci:check | grep … | tail` exits 0 even when the check fails, and a chained `&& git commit` then commits broken code (this happened in v0.2.0; CI caught it). Gate commits on the check's own exit code: run it alone, or use `set -o pipefail`.
- **An architecture rule only counts if it has failed.** When adding an `arch()` rule over an empty namespace, plant a violation once to see it fail.

## Keeping this file healthy

This file loads into every session. It holds rules, pointers, and traps that must always be in mind, and nothing else.

- **No release history.** "As of v0.4…" belongs in `CHANGELOG.md`.
- **Nothing derivable.** No directory listings or dependency lists; a session can read those.
- **Feature detail goes in a reference doc** under `docs/` with a one-line pointer here.
- **Budget:** our part (everything above the Boost block) stays under about 12,000 characters; Boost's generated block adds about 11,000 more. Check the whole file with `wc -c CLAUDE.md` and keep it under 30,000. Claude Code truncates at around 150,000, and indigo-nebula hit that limit.

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.4. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-react-development` when working with Inertia client-side patterns.

# Inertia v3

- Use all Inertia features from v1, v2, and v3. Check the documentation before making changes to ensure the correct approach.
- New v3 features: standalone HTTP requests (`useHttp` hook), optimistic updates with automatic rollback, layout props (`useLayoutProps` hook), instant visits, simplified SSR via `@inertiajs/vite` plugin, custom exception handling for error pages.
- Carried over from v2: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.
- Axios has been removed. Use the built-in XHR client with interceptors, or install Axios separately if needed.
- `Inertia::lazy()` / `LazyProp` has been removed. Use `Inertia::optional()` instead.
- Prop types (`Inertia::optional()`, `Inertia::defer()`, `Inertia::merge()`) work inside nested arrays with dot-notation paths.
- SSR works automatically in Vite dev mode with `@inertiajs/vite` - no separate Node.js server needed during development.
- Event renames: `invalid` is now `httpException`, `exception` is now `networkError`.
- `router.cancel()` replaced by `router.cancelAll()`.
- The `future` configuration namespace has been removed - all v2 future options are now always enabled.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== wayfinder/core rules ===

# Laravel Wayfinder

Use Wayfinder to generate TypeScript functions for Laravel routes. Import from `@/actions/` (controllers) or `@/routes/` (named routes).

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Pest

- This project uses Pest. Create tests with `php artisan make:test --pest {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

=== inertia-react/core rules ===

# Inertia + React

- IMPORTANT: Activate `inertia-react-development` when working with Inertia React client-side patterns.

</laravel-boost-guidelines>
