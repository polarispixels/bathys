# core/: the inside of the hexagon

Everything in this folder is plain PHP in the `Bathys\` namespace. It describes what Bathys *is* (books, their status on the shelf or the wishlist, categories, depth, and the rules between them) without knowing anything about Laravel, databases, HTTP, or the browser.

| Folder | Holds | May depend on |
|---|---|---|
| `Domain/` | The ideas and their rules: entities, value objects, domain services. Grouped by concept (for example `Book/`, `Category/`), not by kind. | Only itself |
| `Application/` | Use cases: one class per thing a user can do (`AddBook`, `SetDepth` …). They load and save through ports and apply domain rules. | `Domain/`, `Ports/` |
| `Ports/` | Interfaces the core needs from the outside world (`BookRepository`, `Clock` …). Laravel supplies the implementations, called adapters, in `app/Adapters/`. | `Domain/` |

**The one rule:** nothing in `core/` imports `Illuminate\`, `Laravel\`, `Inertia\`, or `App\`. This is checked by `tests/Architecture/CoreDependencyTest.php`, so a violation fails the build.

Why it's built this way: [ADR-0003](../docs/adr/0003-hexagonal-architecture.md) (hexagonal architecture) and [ADR-0007](../docs/adr/0007-core-beside-app-repository-layout.md) (this layout).

Tests for this folder live in `tests/Core/`. They run in milliseconds and never boot Laravel.
