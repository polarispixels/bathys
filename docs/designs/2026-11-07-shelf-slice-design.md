# Design: the shelf home screen, a thin slice through every layer

**Status:** Draft, November 7, 2026. Frozen once its plan ships.
**Roadmap item:** [Phase 1, the slice](../roadmap.md#phase-1-catalog)
**Spec:** [The Book](../spec.md#the-book)
**Builds on:** [the domain core design](2026-11-07-domain-core-design.md) (this slice implements part of it, exactly as written), [ADR-0003](../adr/0003-hexagonal-architecture.md), [ADR-0004](../adr/0004-pragmatic-persistence.md), [ADR-0005](../adr/0005-spec-driven-design-with-an-api-contract.md), [ADR-0007](../adr/0007-core-beside-app-repository-layout.md), [ADR-0008](../adr/0008-web-ui-shares-the-api-contract.md)

---

## Intent

Replace the coming-soon page at `/` with a public home screen that lists the books on Ryan's shelf, seeded with eight real books. Build one capability, "list my shelf", but build it through every layer the ADRs describe: domain, ports and use cases, the OpenAPI contract, Postgres persistence behind a repository, a seeder that goes through the use case, and an Inertia page whose props follow the contract. It is a walking skeleton: it proves the architecture end to end once, so the later steps (1a to 1f) widen layers that already exist instead of inventing them.

## Goals

1. `/` shows the eight seeded books in title order, title with authors underneath, on a phone.
2. Every layer exists and is tested the way the ADRs promise: core tests without the framework, a shared repository suite run against both the in-memory double and Eloquent on Postgres, a contract that the Inertia props are checked against, architecture rules that have been seen to fail.
3. The seeder can be run again safely, including once on production after the merge.

## Non-goals

- Adding books through the UI, editing, removing, moving, categories, depth, ISBN, covers, search. Those are steps 1a to 1g.
- An API endpoint. The contract gets the `books` resource schema; the first endpoint arrives in step 1e.
- Sign-in. The home screen is public until step 1e.
- Visual design beyond plain and readable (step 1f).

## Anti-goals

- A slice that takes a shortcut through a layer (a controller reading Eloquent, a seeder writing rows). The point is the path, not the page.
- Domain code that differs from the domain core design. This slice implements a subset of it verbatim; anything it changes must change the design too.

---

## Decisions (November 7, 2026)

| # | Decision | Whose |
|---|---|---|
| 1 | **Build the slice now, ahead of steps 1a to 1f,** as a deliberate roadmap reorder. | Ryan |
| 2 | **The home screen is public until sign-in exists** (step 1e). | Ryan |
| 3 | **The eight books are simply the first on the shelf.** No "favorite" concept. Titles as printed on the cover without subtitles, authors in cover order, no ISBNs or covers yet. | Ryan |
| 4 | **The domain subset** is `InvalidValue`, `InvalidId`, `InvalidTitle`, `InvalidAuthor`, `BookId`, `Title`, `Author`, `Status`, and `Book` with its full constructor, `Book::add`, `creditAuthors`, and the readers, written as the domain core design specifies (NFC normalization, Unicode whitespace, UTC `addedAt`). `Isbn`, `CoverUrl`, `Category`, `Depth`, and the other change methods stay in step 1a. | Ryan (scope), design (shape) |
| 5 | **Ports:** `BookRepository` (`save`, `onShelf`), `Clock` (`now`), `IdGenerator` (`nextBookId`). `onShelf` returns books in any order; sorting is the use case's job, so both repositories stay trivial and the rule lives in one place. | Claude |
| 6 | **Use cases:** `AddBook` takes value objects (`Title`, `Author ...`) and returns the saved `Book`; `ListShelf` returns shelf books sorted by title, ignoring case. Boundaries (seeder now, API and MCP later) build the value objects and turn `InvalidValue` into their own error shape. | Claude |
| 7 | **The shared repository suite is a PHP trait** of test methods with an abstract `makeRepository()`. The in-memory test class extends PHPUnit's `TestCase`; the Eloquent one extends Laravel's `TestCase` with `RefreshDatabase`. A trait is the one shape that works under both base classes, and it reads as a plain contract. | Claude |
| 8 | **The framework test suites move to Postgres now** (`bathys_test` locally, created by the Docker init script; `bathys` in CI, whose `DB_*` variables move to the job level so the check step sees them). The development doc said this would happen at the persistence step; this is it. Core and Architecture suites still boot nothing. | Claude |
| 9 | **Contract first:** `contracts/openapi.yaml` (OpenAPI 3.1) starts with the JSON:API `books` resource schemas and an empty `paths`. TypeScript types are generated from it with `openapi-typescript` (approved dev dependency) into a committed file, and CI fails if the committed file is stale. | Ryan (dependency), Claude (shape) |
| 10 | **Contract test library:** `opis/json-schema` (dev dependency, needs approval at the checkpoint) validates the Inertia `books` prop against the contract's `BookCollection` schema; `symfony/yaml` (already installed through other dev packages) is declared directly since the tests read the contract with it. Writing a schema validator by hand would be a project of its own. | Claude |
| 11 | **Persistence:** table `books` with `id` (uuid), `title` (text), `authors` (jsonb list of strings), `status` (string), `added_at` (timestamptz), timestamps. Columns for ISBN, cover, depth, and categories arrive with their steps, each in its own migration. `BookRecord` is the Eloquent model and writes timestamps with their offset (`Y-m-d H:i:sP`), so an instant survives any session time zone; `BookMapper` converts both ways (`toDomain`, `fill`); `EloquentBookRepository` implements the port. `CoreServiceProvider` is the composition root. | Claude |
| 12 | **IDs are UUID v7** from `Str::uuid7()` in the `UuidV7IdGenerator` adapter; time-ordered, so new books cluster at the end of the index. The domain only checks the UUID shape. | Claude |
| 13 | **The seeder goes through `AddBook`** and is idempotent: it lists the shelf first and skips a book whose title and authors already match. `DatabaseSeeder` runs only `ShelfSeeder`. The starter kit's test user (`test@example.com`) moves to a `DevelopmentSeeder` that is never run by default, because Ryan will run `db:seed` on production. | Claude (the test-user move is a trap found while designing) |
| 14 | **The web page** is `resources/js/pages/shelf.tsx` at `/` (route `home`), rendered by `App\Http\Controllers\Web\ShelfController`, which calls `ListShelf` and passes `books` through `App\Http\Resources\BookResource` as a JSON:API collection document. It is a standalone page like the coming-soon page was (not inside the dashboard sidebar layout; `resources/js/app.tsx` keeps it out). The coming-soon page is deleted. | Claude |
| 15 | **Architecture rules 3 and 4 from ADR-0007 become tests now,** scoped to the slice's classes: `App\Models\BookRecord` may be used only by `App\Adapters\Persistence`, `Database\Factories`, and `Database\Seeders`; `App\Http\Controllers` may not use `BookRecord`, `Bathys\Ports`, or `App\Adapters`. The starter kit's `User` model is used by Fortify actions and settings controllers, so the ADR's rule 3 is read as applying to domain records (the `*Record` models), not to `User`. Each rule is planted with a violation once, per CLAUDE.md. | Claude (interpretation of ADR-0007) |

---

## The slice, layer by layer

### Domain (`core/Domain/`)

Exactly the subset of the domain core design named in decision 4, in the files that design lays out (`core/Domain/InvalidValue.php`, `InvalidId.php`, `core/Domain/Book/{Book,BookId,Title,Author,Status,InvalidTitle,InvalidAuthor}.php`). `Book` holds `id`, `title`, `authors`, `status`, `addedAt` only; the constructor drops duplicate authors; `Book::add(BookId, Title, DateTimeImmutable $addedAt, Status $status = Status::OnShelf)`; `creditAuthors(Author ...$authors)` replaces the list, order kept, duplicates dropped; readers `id()`, `title()`, `authors()`, `status()`, `addedAt()`, `isOnShelf()`, `isWanted()`. Tests in `tests/Core/Domain/Book/`.

### Ports and use cases (`core/Ports/`, `core/Application/`)

```php
interface BookRepository { public function save(Book $book): void; /** @return list<Book> */ public function onShelf(): array; }
interface Clock { public function now(): DateTimeImmutable; }
interface IdGenerator { public function nextBookId(): BookId; }

final class AddBook { public function __construct(BookRepository, IdGenerator, Clock) {} public function execute(Title $title, Author ...$authors): Book; }
final class ListShelf { public function __construct(BookRepository) {} /** @return list<Book> */ public function execute(): array; }
```

`save` inserts or updates by id. `ListShelf` sorts with a case-insensitive comparison of the normalized title (`mb_strtolower`), stable for equal titles. Doubles in `tests/Doubles/`: `InMemoryBookRepository`, `FixedClock`, `SequentialIdGenerator`. The shared suite `Tests\Shared\BookRepositoryContract` (trait) proves: a saved book comes back from `onShelf` with every field equal; a wanted book does not; saving the same id twice updates, not duplicates; an empty repository lists nothing.

### Contract (`contracts/openapi.yaml`)

OpenAPI 3.1. `components.schemas`: `BookResource` (JSON:API resource object: `type` const `books`, `id` uuid, `attributes`), `BookAttributes` (`title`, `authors` as a list of strings, `status` enum `shelf|wanted`, `addedAt` date-time, all required), and `BookCollection` (`{ data: BookResource[] }`, the JSON:API top-level document the home page receives). `paths` is empty with a note that step 1e adds `GET /api/books`. `npm run contract:types` writes `resources/js/types/api.d.ts`; `composer ci:check` regenerates and fails on a diff.

### Persistence (`app/Models/BookRecord.php`, `app/Adapters/Persistence/`, `database/migrations/`)

As decision 11. `BookMapper::fill(BookRecord, Book): BookRecord` and `toDomain(BookRecord): Book`; `EloquentBookRepository` uses `BookRecord::query()->where('status', 'shelf')` and `updateOrCreate` by id (or find-then-fill). `CoreServiceProvider` binds the three ports and is listed in `bootstrap/providers.php`. The Eloquent repository test uses the shared trait with `RefreshDatabase` on Postgres.

### Seeding (`database/seeders/ShelfSeeder.php`)

The eight books as a constant list of `[title, [authors]]`. For each: if `ListShelf` already has a book with that title (value-object equality) and the same author list, skip; else `AddBook`. `DatabaseSeeder` calls `ShelfSeeder`. `DevelopmentSeeder` holds the test user. A feature test seeds twice and counts eight.

### Web (`app/Http/Controllers/Web/ShelfController.php`, `app/Http/Resources/BookResource.php`, `resources/js/pages/shelf.tsx`)

`ShelfController::__invoke(ListShelf $listShelf)` returns `Inertia::render('shelf', ['books' => BookCollectionDocument])`, where the document is `['data' => BookResource::collection($books)->resolve()]`. `BookResource` wraps a domain `Book`, not a record, and emits `type`, `id`, `attributes` with `addedAt` in RFC 3339 UTC. The page: the icon and the name "Bathys", one line ("A personal catalog of the books on the shelf and the books wanted, with how deep each one has been read."), then an ordered list of books, each title with its authors joined by ", " underneath; an empty state "No books on the shelf yet."; the version footer and the version meta tag stay. Props are typed with the generated `components['schemas']['BookCollection']`.

---

## Edge cases the tests must cover

- Two books with the same title are both listed (identity is the id, not the title).
- Sorting ignores case: "the Checklist Manifesto" sorts with "The ...", and "Rich Dad Poor Dad" before "Secrets of Closing the Sale".
- A wanted book saved to the repository never appears on `/`.
- `creditAuthors(A, B, A)` yields `[A, B]`; the constructor does the same.
- The mapper round-trips every field, including a multi-author list and the exact `addedAt` instant in UTC.
- Seeding twice leaves exactly eight books. A "Good to Great" by another author added before seeding is kept and the seeded one is added beside it (nine books), so matching is on title and authors, not title alone.
- The home page with no books shows the empty state and still carries the version meta tag.
- The `books` prop validates against `BookCollection`; a deliberately broken resource (missing `addedAt`) fails the same validation (the test proves the validator can fail).
- The architecture rules fail when planted: a controller that type-hints `BookRepository`, and a class outside persistence that imports `BookRecord`.

## Out of scope

- Everything in the Non-goals. The roadmap says which parts of 1a to 1f this slice delivers early, so those steps shrink rather than repeat.

## Left to the builder

- Exact exception messages; the TypeScript component's class names; whether `EloquentBookRepository::save` uses `updateOrCreate` or find-then-save.
- How `BookRecord` casts `authors` (`array` cast on a jsonb column is the expected choice).

## Changes to other documents

- `docs/roadmap.md`: a "slice" row before 1a listing what it delivers early (added with this design).
- `docs/spec.md`: Decisions Log row 20 (the home screen is public until sign-in); v0.6.
- `docs/development.md`: the testing table and the SQLite sentence (framework suites run on Postgres from this slice on); the `Adapters`, `Contract`, and `Doubles` folders exist.
- `CLAUDE.md`: commands gain `npm run contract:types`; Known traps gains the seeder trap (the starter kit's test user).
- `CHANGELOG.md`: `[Unreleased]` entries as tasks land. No release in this branch.
