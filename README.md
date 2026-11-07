# Bathys

*Bathys* (Greek *βαθύς*, "deep") is a private reading tool that measures **how deep you have gone into a book** instead of how many books you have finished.

Most reading trackers count finished books. For a reader whose attention wanders, who falls asleep twenty pages in, and who rarely reads cover to cover, that metric turns almost all of their reading into failure. Bathys measures something else.

## The Idea

Mortimer Adler's *How to Read a Book* describes levels of reading, each deeper than the last. Bathys turns them into a ladder:

| Level | Name | In practice |
|---|---|---|
| 0 | Shelved | On the shelf, not yet touched |
| 1 | Inspected: skim | A 5 to 15 minute systematic skim |
| 2 | Inspected: superficial read | Read straight through, without stopping on hard parts |
| 3 | Analytical (partial) | Careful reading of selected chapters |
| 4 | Analytical (complete) | Careful reading of the whole book |
| 5 | Syntopical | Compared with other books on a shared question |

Getting a whole shelf to level 1 is a real accomplishment. From there, Bathys helps you decide which books have earned a deeper read.

Reading does not happen in order, so Bathys doesn't enforce one. Any level can be recorded for any book at any time, even days later, and a book's depth is simply the deepest level recorded. Each record of reading is a **pass**: a book, a level, when it happened, and a quick capture (what the book is about, how it's built, and a verdict: *go deeper*, *park it*, or *let it go*).

You can report a pass after the fact in a few seconds, or run a **live session**: about ten minutes, one book, one level, with a gentle timer and a guided checklist that shows one step at a time so a wandering mind has somewhere to come back to.

## Who It's For

**As an app:** one reader (Ryan) on a phone at the bookshelf, a phone in bed, a tablet, or a desktop. It is single-user and private.

**As a codebase:** students and new programmers who want to see software architecture done carefully on a familiar problem. Books, authors, and categories are a classic teaching domain. Bathys uses that domain to show:

- a **domain core** in plain PHP that knows nothing about the framework or the database,
- **ports and adapters** (hexagonal architecture) around that core,
- an **API contract written before the code**, with tests that hold the code to it,
- **decision records** that explain each architectural choice, including the tradeoffs.

The architecture is deliberately more structured than a typical Laravel app. Where that structure costs something, the cost is written down.

## Status

**v0.1.0, foundations.** The documents, the Laravel scaffold, and the architecture guards are in place; reading features start with the domain core. See [`docs/roadmap.md`](docs/roadmap.md).

## Running it locally

Requires PHP 8.4, Composer, Node 24, and Postgres.

```bash
composer setup     # install dependencies, create .env, migrate, build the frontend
composer dev       # run the app at http://localhost:8000
composer test      # formatting, static analysis, and the full test suite
```

## Stack

Laravel 13 (PHP 8.3+), Postgres, Inertia with React and TypeScript, Tailwind, passkey authentication, an MCP server for Claude, deployed on Laravel Forge. See [`docs/adr/`](docs/adr/README.md) for why.

## How the Docs Are Organized

```
README.md            You are here
CLAUDE.md            Orientation and hard rules for AI coding sessions
CHANGELOG.md         What changed in each release
docs/
├── spec.md          What Bathys does and why: the product spec
├── roadmap.md       Phases, status board, deliberate deferrals
├── development.md   How we work: lifecycle, testing, commits, versioning, Claude
├── deployment.md    How it ships: CI, Forge, rollback, secrets
├── adr/             Why it is built the way it is (one record per decision)
├── designs/         How one feature will work, written before it is built
└── plans/           The step-by-step build of each design
```

`designs/` and `plans/` appear once the first feature is designed.

| If you want to know... | Read |
|---|---|
| What the app does and for whom | [`docs/spec.md`](docs/spec.md) |
| What has been decided, and what is still open | [Decisions Log](docs/spec.md#decisions-log) and [Open Questions](docs/spec.md#open-questions) in the spec |
| Why Laravel, why hexagonal, why an API contract | [`docs/adr/README.md`](docs/adr/README.md), then the individual ADRs |
| What's being built now, and what comes next | [`docs/roadmap.md`](docs/roadmap.md) |
| How a change goes from idea to production | [`docs/development.md`](docs/development.md), then [`docs/deployment.md`](docs/deployment.md) |
| How secrets are handled | [Secrets and configuration](docs/deployment.md#secrets-and-configuration) |

**Suggested reading order for learners:** the spec's *Intent* and *Depth Ladder* sections first (the problem), then the [ADR index](docs/adr/README.md) (the goals), then ADR-0002 and ADR-0003 (the core architectural idea), then the rest.

### Conventions

- **The spec says what, an ADR says why it's shaped like this, a design says how for one feature, a plan says in what order.** If you are asking "what should happen?", it belongs in the spec. If you are asking "why is the code shaped like this?", it belongs in an ADR.
- **Documents are versioned in place.** The spec carries a version number and a version history at the bottom. ADRs are never rewritten once accepted; a changed decision gets a new ADR that supersedes the old one.
