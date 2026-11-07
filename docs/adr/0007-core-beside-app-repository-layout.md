# ADR-0007: Repository Layout (the Core Beside `app/`)

- **Status:** Accepted
- **Date:** November 7, 2026
- **Decided by:** Ryan Grissinger, delegating to Claude's recommendation
- **Related:** [ADR-0003](0003-hexagonal-architecture.md), [ADR-0004](0004-pragmatic-persistence.md)

## Context

[ADR-0003](0003-hexagonal-architecture.md) chose hexagonal architecture and noted that it adds structure, "so the folder layout and naming need to explain themselves." The layout must make the core, the ports, and the adapters obvious to someone reading the code for the first time, without fighting Laravel's conventions, generators, starter kit, or Laravel Boost's guidance.

## Decision

Put the domain core in a top-level `core/` folder with its own namespace, beside Laravel's `app/`. Treat everything in `app/` as an adapter.

```
core/                           Bathys\     plain PHP; never imports Illuminate\ or App\
├── Domain/                     rules and concepts, grouped by idea (Shelf, Ladder, Pass, Session, Facets, Prioritization)
├── Application/                use cases, one per thing the user can do (AddBook, RecordPass, StartSession …)
└── Ports/                      interfaces the core needs from outside (BookRepository, Clock, MetadataLookup …)

app/                            App\        Laravel; every file is an adapter
├── Http/Controllers/Web/       driving adapter: Inertia pages
├── Http/Controllers/Api/       driving adapter: JSON:API
├── Http/Resources/             JSON:API serialization
├── Mcp/                        driving adapter: MCP tools
├── Models/                     Eloquent models, suffixed *Record (BookRecord)
├── Adapters/                   driven adapters implementing core/Ports (Persistence, Metadata, Vision, Clock)
└── Providers/CoreServiceProvider.php   binds each port to its adapter (the composition root)

tests/
├── Core/                       pure PHP; no framework boot
├── Adapters/                   each adapter against the real technology
├── Feature/                    HTTP and Inertia end to end
├── Contract/                   responses checked against the OpenAPI document
├── Architecture/               executable dependency rules
└── Doubles/                    in-memory adapters and fixed clocks for tests
```

`composer.json` maps `"Bathys\\": "core/"`.

**Dependency rules**, enforced by Pest architecture tests in `tests/Architecture/`:

1. `Bathys\` never uses `Illuminate\` or `App\`.
2. `Bathys\Domain` uses nothing from `Bathys\Application` or `Bathys\Ports`.
3. Only `App\Adapters\Persistence` (plus factories and seeders) uses `App\Models`.
4. Controllers and MCP tools call use cases; they do not call repositories or models directly.

## Reasoning

- **The boundary is visible in the file tree.** `core/` next to `app/` tells a newcomer the main idea before they open a file. A common alternative, `app/Domain`, places the core inside a folder (and namespace) that the framework owns, so the boundary has to be remembered rather than seen.
- **The boundary is enforced, not just described.** A dependency that crosses the line fails the test suite.
- **Laravel stays conventional.** Controllers, models, providers, and migrations stay where Laravel, its generators, and Boost expect them.
- **Ports live with the core.** The core states what it needs, and the outside supplies it. That reversal of dependency is the essence of hexagonal architecture.
- **Domain folders are named after ideas.** "How does depth get decided?" leads to `Domain/Ladder/`, not to `Services/`.

## Alternatives Considered

- *`app/Domain`, `app/Application`, `app/Infrastructure`.* Familiar from "DDD in Laravel" articles, but the core shares the `App\` namespace with framework code, and the boundary blurs.
- *Pure folder naming (`app/Adapters/Driving/Http/...`).* Spells out driving versus driven adapters, but breaks `artisan make:*` defaults and starter-kit paths. That fights the framework for a naming gain.
- *Modules per area (`modules/Shelf/{Domain,Application,Infrastructure}`).* Right for a system with several independent areas. Bathys has one, so modules would add folders without adding clarity.

## Consequences

- Driving adapters stay in Laravel's usual places, so the driving/driven distinction lives in documentation and naming, not in a folder. This is a deliberate trade of purity for framework fit.
- Eloquent models carry a `Record` suffix and an explicit `$table`. The small cost avoids two classes named `Book`, which reliably confuses learners and editors' auto-imports.
- Controllers give up route-model binding and quick `Book::find()` calls, because they must go through use cases. This is the visible price of [ADR-0004](0004-pragmatic-persistence.md).
- There is more code than in a typical Laravel app: mappers between rows and domain objects, in-memory test doubles, and port interfaces. Following [ADR-0002](0002-put-portability-where-change-is-likely.md), the database port stays thin (one repository per aggregate, no generic repository), and the thicker abstraction goes to metadata and AI.
