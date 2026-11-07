# Development Method

**Status:** Adopted November 7, 2026. Revise in place; note material changes in `CHANGELOG.md`.

How Bathys is built, day to day. This method draws on three earlier projects (block-buddies, indigo-nebula, knight-notes). Where a practice exists because of something that happened in one of them, the reason is given, so a reader can judge whether it still applies.

---

## Lessons carried forward

| Lesson | Where it came from | What Bathys does |
|---|---|---|
| An agent guide with a "where authority lives" table orients a fresh session faster than a long manual | knight-notes `AGENT_GUIDE.md` | `CLAUDE.md` opens with that table and stays short |
| CLAUDE.md grows until it is truncated | indigo-nebula: 162k characters, truncated by Claude Code; a cleanup moved about 75% of it into reference docs | Size budget, no release history, feature detail lives in `docs/` ([below](#claudemd-hygiene)) |
| A checklist step that isn't enforced drifts | indigo-nebula: CHANGELOG fell 65 versions behind | Version, changelog, and docs sync are checked by a test ([below](#versioning-and-releases)) |
| Documented commands rot | indigo-nebula: CLAUDE.md told agents to run `composer test`, which didn't exist | Commands are defined as composer/npm scripts, and CLAUDE.md lists only commands that have been run |
| Deploys that skip tests eventually ship a broken build | indigo-nebula deploys on push with tests run by hand; knight-notes gates deploy on CI | CI runs everything; only a green build deploys ([deployment](deployment.md#the-pipeline)) |
| The suite must make zero external calls | indigo-nebula `phpunit.xml` blanks every key | Stray HTTP fails the suite; services sit behind ports |
| Bugs repeat in recognizable classes | block-buddies' "recurring bug classes" section | A **Known traps** list in CLAUDE.md that grows with each new trap |
| Flaky passes often hide real nondeterminism | block-buddies: "run it 2-3 times" | Inject time and randomness through ports (`Clock`, `IdGenerator`); rerun a suspicious pass |
| A visible version string makes deploy verification trivial | block-buddies title screen; indigo-nebula `<meta name="app-version">` | Version in a meta tag and the footer; verified after every deploy |
| Brainstorm, design, plan, subagent build, review works | all three; superpowers is the most-used extension | The lifecycle below |
| Plans need explicit constraints and review targets | indigo-nebula plans: "Global Constraints", "Review Focus" | Both sections are required in every plan |
| Secrets end up in places nobody meant to put them | polaris-pixels: a bearer token saved inside a Claude permission rule | Secrets referenced by variable name only ([deployment](deployment.md#secrets-and-configuration)) |
| Memory files bloat with history the repo already has | indigo-nebula `MEMORY.md`: an 11,000-character release-history paragraph | Memory holds only what the repo cannot ([below](#memory)) |
| Conventions differ between repos for no reason | commit authors, message styles, `master`/`main` | One convention, written down here |

---

## Where things go

| Document | Holds | Changes when |
|---|---|---|
| `docs/spec.md` | What the product does and why (behavior) | Ryan decides product behavior |
| `docs/adr/NNNN-*.md` | Why the code is structured as it is | An architectural decision is made; never rewritten after acceptance, only superseded |
| `docs/designs/YYYY-MM-DD-<topic>-design.md` | How one feature will work: decisions, data, components, edge cases | Before a feature is built; frozen once its plan ships |
| `docs/plans/YYYY-MM-DD-<topic>.md` | The step-by-step build of one design | Written after the design; checkboxes tick as tasks land |
| `docs/roadmap.md` | Phases, status board, deliberate deferrals | Every release that moves an item |
| `contracts/openapi.yaml` | The API shape | Before any endpoint changes |
| `CHANGELOG.md` | What changed in each release | Every merge (into `[Unreleased]`) and every release |
| `CLAUDE.md` | Rules, pointers, and traps an agent must always have in mind | Rarely; see [hygiene](#claudemd-hygiene) |

Rule of thumb: **the spec says what, an ADR says why it's shaped like this, a design says how for one feature, a plan says in what order.**

These locations replace the superpowers defaults (`docs/superpowers/specs/`, `docs/superpowers/plans/`) used in the other projects. Bathys is a teaching repository, and folder names should describe the content, not the tool that produced it. CLAUDE.md tells the skills about the override.

---

## Lifecycle of a change

### Features

1. **Roadmap.** The work exists as an item on [`docs/roadmap.md`](roadmap.md). If it isn't there, add it first, which also catches "this is a deliberate deferral."
2. **Brainstorm** (`superpowers:brainstorming`). Ask questions until the intent is clear. Domain questions go to Ryan; technical questions get a recommendation.
3. **Design doc** in `docs/designs/`. It must include a dated **Decisions** section listing what was agreed, an **Out of scope** section, and edge cases. If the design changes product behavior, update `docs/spec.md` in the same change. If it makes an architectural choice, write an ADR.
4. **Contract.** If the API changes, update `contracts/openapi.yaml` and review it before code.
5. **Plan** (`superpowers:writing-plans`) in `docs/plans/`. It must include:
   - **Global Constraints:** rules every task obeys (dependency rule, no external calls, copy style).
   - **Review Focus:** the specific failure cases a reviewer must check.
   - Tasks small enough to land as one commit each, each naming its files and its tests.
6. **Branch.** One branch per plan: `feat/<topic>`. Use a git worktree when parallel work needs isolation.
7. **Build test-first** (`superpowers:subagent-driven-development` or `superpowers:executing-plans`). Each task: failing test, implementation, passing test, commit. The subagent progress ledger lives in `.superpowers/` (gitignored, so it never clutters history).
8. **Final review** of the whole branch (`superpowers:requesting-code-review`), with fixes landed before merge.
9. **Finish** (`superpowers:finishing-a-development-branch`). Merge to `main` with an `[Unreleased]` changelog entry.
10. **Release** when a meaningful set has landed (see the checklist below).

### Fixes and small changes

Skip the design doc and plan. Still: a failing regression test first, a `fix/<topic>` branch (or a direct commit for trivial docs), an `[Unreleased]` changelog line. If the bug belongs to a class that could recur, add it to **Known traps** in CLAUDE.md.

---

## Testing

The test folders mirror the architecture ([ADR-0007](adr/0007-core-beside-app-repository-layout.md)):

| Suite | What it proves | Speed | External dependencies |
|---|---|---|---|
| `tests/Core/` | Domain rules and use cases | Milliseconds | None; in-memory doubles |
| `tests/Adapters/` | Each adapter honors its port (shared suites run against in-memory *and* real implementations) | Seconds | Postgres; HTTP faked from recorded fixtures |
| `tests/Feature/` | HTTP and Inertia end to end | Seconds | Postgres |
| `tests/Contract/` | Responses match `contracts/openapi.yaml` | Seconds | Postgres |
| `tests/Architecture/` | Dependency rules hold | Milliseconds | None |

Rules:

- **Most tests live in `tests/Core/`.** If a domain rule can only be tested through HTTP, the rule is in the wrong place.
- **Time and randomness are injected** through ports (`Clock`, `IdGenerator`), never read directly in the core.
- **No real network.** Stray HTTP requests fail the suite. Recorded fixtures stand in for Open Library and AI responses; refresh them deliberately, never automatically.
- **A suspicious pass gets a rerun.** If a test passes and fails without code changes, treat it as a nondeterminism bug, not bad luck.
- **Every bug fix adds a regression test** that fails without the fix.

---

## Branches and commits

- **`main`** is always deployable. Nothing merges without green CI.
- **Branches:** `feat/<topic>`, `fix/<topic>`, `docs/<topic>`, `chore/<topic>`.
- **Commit messages use [Conventional Commits](https://www.conventionalcommits.org/):** `type(scope): summary`, with types `feat`, `fix`, `docs`, `test`, `refactor`, `chore`. Scopes follow the architecture: `core`, `api`, `web`, `mcp`, `persistence`, `contract`, `deploy`. Example: `feat(core): record a pass at any level`. The type shows the kind of change at a glance in `git log`, and the scope shows which layer of the hexagon it touched.
- **Author identity comes from git config** (`Ryan Grissinger <ryan@polarispixels.com>`). Don't override it per command.
- **Reference the plan task or issue** in the commit body when there is one.

---

## Versioning and releases

**Two kinds of version, kept separate:**

- **The application version** (`package.json`, shown in the page footer and in `<meta name="app-version">`) follows [SemVer](https://semver.org/).
- **Document versions** (the spec's `Version: 0.3`) track the document's own history and are independent of the app.

**SemVer for Bathys.** Before 1.0, minor versions may break things. **1.0.0 ships when Phase 1 is complete and in daily use.** From then on:

| Bump | When |
|---|---|
| **Major** | A breaking API contract change (a client must change), or a data migration that can't be rolled back |
| **Minor** | New user-visible capability, new endpoint, new MCP tool |
| **Patch** | Fixes, copy changes, internal refactors with no visible change |

**Changelog:** [Keep a Changelog](https://keepachangelog.com/) format, newest first. Every merge adds to `[Unreleased]`, and a release renames that section to `[X.Y.Z] - YYYY-MM-DD`.

**Enforced, not remembered.** A test asserts that the `package.json` version has a matching `CHANGELOG.md` heading. A drifted changelog fails CI rather than going unnoticed for 65 versions.

**Tags:** every release is tagged `vX.Y.Z`.

**The release checklist** lives in CLAUDE.md so it is always loaded.

---

## Working with Claude

### Skills and plugins

| Tool | Use it for |
|---|---|
| **superpowers** (plugin) | The lifecycle above: brainstorming, writing-plans, subagent-driven-development, test-driven-development, requesting-code-review, finishing-a-development-branch, systematic-debugging, verification-before-completion |
| **Laravel Boost** (Composer dev dependency, from Phase 1 step 0) | Version-correct Laravel documentation and a Laravel-aware MCP server for the agent |
| **avoid-ai-writing** (user skill) | A pass over prose that people will read: README, docs, UI copy |
| **code-review** / **security-review** (built in) | Before merging anything that touches authentication, secrets, or external input |

**Adding a project skill** (`.claude/skills/`) is justified only when a procedure is long, needed occasionally, and harmless if it fails to load. Anything that must happen every time (like the release checklist) stays in CLAUDE.md. That was a deliberate call in indigo-nebula's cleanup: a skill that doesn't load costs a botched release.

**Plugins are reviewed occasionally.** Disable what hasn't been used in a month. Unused plugins add skill listings to every session's context.

### CLAUDE.md hygiene

CLAUDE.md loads into every session, so every character costs every session. It holds:

- rules that must never be missed,
- pointers to where detail lives,
- traps that have bitten before.

It does not hold release history, directory listings, dependency lists, or feature walkthroughs. Budget: about 15,000 characters. When a section grows past a few paragraphs, move it into `docs/` and leave a one-line pointer.

### Memory

Claude's memory (outside the repo) holds only what the repository cannot: Ryan's preferences, how he likes to work, and pending items that live outside this repo. It never holds release history, code structure, or anything `git log` or the docs already record. If a memory and the repo disagree, the repo wins and the memory is corrected.

### Permissions

- **`.claude/settings.json` (committed)** holds rules everyone should have, such as denying reads of `.env` files.
- **`.claude/settings.local.json` (gitignored)** holds personal allow rules that accumulate as commands are approved.
- **Never approve a command that contains a secret.** The approval is saved word for word into `settings.local.json`, and the secret lives there in plaintext from then on. Reference secrets by environment variable name (`$FORGE_DEPLOY_HOOK`), never by value.

### Model assignment

See [Agents and models](../CLAUDE.md#agents-and-models) in CLAUDE.md.

---

## Writing style

These rules apply to docs, UI copy, and commit messages:

- Plain words, short sentences, active voice.
- No em dashes. Use a comma, colon, parentheses, or a period. En dashes in numeric ranges are fine.
- Explain the *why*. This repository teaches; a decision without a reason is half a lesson.
- Date things absolutely ("November 7, 2026"), never relatively ("last week").
