# Bathys

*Bathys* (Greek *βαθύς*, "deep") is a private catalog of the books on one person's shelf and the books they want, grouped into their own categories, with one rating per book: **how deep they have read it**.

## The Idea

Three things, in one place:

- **The shelf:** every book owned.
- **The wishlist:** every book wanted, ready to move to the shelf when it arrives.
- **Categories:** an editable list, so the catalog is organized the way its owner thinks.

Each book's one rating is its **depth**, on the levels of reading from Mortimer Adler's *How to Read a Book*:

| Level | Name | In practice |
|---|---|---|
| 0 | Not yet read | On the shelf or the wishlist, not yet touched |
| 1 | Inspected: skim | A short, systematic skim |
| 2 | Inspected: superficial read | Read straight through, without stopping on hard parts |
| 3 | Analytical (partial) | Careful reading of part of the book |
| 4 | Analytical (complete) | Careful reading of the whole book |
| 5 | Syntopical | Compared with other books on a shared question |

Depth is set directly, at any level, at any time. Reading doesn't happen in order, so Bathys doesn't enforce one.

Adding a book takes seconds: type the title, search for it, or scan its barcode.

## Who It's For

**As an app:** one person (Ryan) on a phone at the bookshelf or in a bookstore, a tablet, or a desktop. It is single-user and private.

**As a codebase:** students and new programmers who want to see software architecture done carefully on a familiar problem. Books, authors, and categories are a classic teaching domain. Bathys uses that domain to show:

- a **domain core** in plain PHP that knows nothing about the framework or the database,
- **ports and adapters** (hexagonal architecture) around that core,
- an **API contract written before the code**, with tests that hold the code to it,
- **decision records** that explain each architectural choice, including the tradeoffs.

The architecture is deliberately more structured than a typical Laravel app. Where that structure costs something, the cost is written down.

## Status

**v0.1.0, foundations.** The documents, the Laravel scaffold, and the architecture guards are in place; reading features start with the domain core. See [`docs/roadmap.md`](docs/roadmap.md).

## Running it locally

Requires PHP 8.4, Composer, Node 22 or newer, and Docker (for Postgres).

```bash
docker compose up -d   # Postgres 18 on localhost:5433
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
├── ai-tooling.md    Which model, plugin, and subagent does each kind of work
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
| How AI tools and models are used to build it | [`docs/ai-tooling.md`](docs/ai-tooling.md) |

**Suggested reading order for learners:** the spec's *Intent*, *The Book*, and *Depth* sections first (the problem), then the [ADR index](docs/adr/README.md) (the goals), then ADR-0002 and ADR-0003 (the core architectural idea), then the rest.

### Conventions

- **The spec says what, an ADR says why it's shaped like this, a design says how for one feature, a plan says in what order.** If you are asking "what should happen?", it belongs in the spec. If you are asking "why is the code shaped like this?", it belongs in an ADR.
- **Documents are versioned in place.** The spec carries a version number and a version history at the bottom. ADRs are never rewritten once accepted; a changed decision gets a new ADR that supersedes the old one.
