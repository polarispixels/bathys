# Shelf Slice Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** `/` lists the books on the shelf, seeded with eight books, built through every layer: domain, ports and use cases, contract, Postgres persistence, seeder, Inertia page.

**Architecture:** Plain PHP domain and use cases in `core/` behind three ports; Laravel adapters in `app/` (Eloquent repository, system clock, UUID v7 ids, a composition-root provider); a JSON:API resource shared by the Inertia page and the future API; the contract in `contracts/openapi.yaml` with generated TypeScript types. See ADR-0003, 0004, 0005, 0007, 0008.

**Tech Stack:** PHP 8.4, Laravel 13, Pest 5 on PHPUnit 13 (file-based tests in `tests/Core/`, a class-based trait for the shared repository suite), Postgres 18 (Docker locally, service in CI), Inertia v3 + React 19 + TypeScript, `openapi-typescript` (new dev dependency, approved), `opis/json-schema` and `symfony/yaml` as direct dev dependencies (approval requested at the checkpoint; yaml is already installed through other dev packages).

**Spec:** `docs/designs/2026-11-07-shelf-slice-design.md`, which implements a subset of `docs/designs/2026-11-07-domain-core-design.md` verbatim. Read both.

## Global Constraints

- **The dependency rule.** Code in `core/` (namespace `Bathys\`) never imports `Illuminate\`, `Laravel\`, `Inertia\`, or `App\`. `Bathys\Domain` imports nothing from `Bathys\Application` or `Bathys\Ports`. The architecture tests enforce this; never weaken one.
- **Controllers call use cases, never repositories or Eloquent models.** Seeders go through use cases too.
- `declare(strict_types=1);` in every new PHP file (tests included); return types everywhere; PHPDoc array shapes (`list<Book>`, `list<Author>`). Larastan level 7 passes.
- **Core tests** (`tests/Core/`) boot nothing: no `base_path()`, `config()`, `app()`, or facades. They build plain objects and the doubles in `tests/Doubles/`.
- **Framework tests run on Postgres** (`bathys_test` locally via Docker, `bathys` in CI); use `RefreshDatabase`. No SQLite anywhere after Task 6.
- **Tests make zero external calls.**
- **Text values** follow the domain core design: trim and collapse Unicode whitespace (`\s+` with the `u` flag), NFC-normalize with `Normalizer::normalize`, reject empty; `addedAt` is converted to UTC in `Book`; UUIDs are validated by shape (8-4-4-4-12 hex) and lowercased.
- **Copy:** plain words, no em dashes anywhere (code comments, docs, UI, commit messages). Absolute dates.
- **Commits:** one per task, Conventional Commits with the layer as scope (`feat(core): ...`, `feat(persistence): ...`, `feat(contract): ...`, `feat(web): ...`, `test(...)`, `docs: ...`). Body ends with the attribution lines the dispatch brief gives you. Never pipe `composer test` or `composer ci:check`; gate the commit on the exit code.
- **CHANGELOG:** every task adds a line under `## [Unreleased]`. No version bump, no release, no tag in this branch.
- **Never run anything on the production server.**

## Review Focus

1. Running the seeder again must add nothing (Task 7). The seeder compares `Title` and `Author` values, never raw strings, so whitespace differences are absorbed by `Title` (covered in `TitleTest`) and a different capitalization is a different title, per the domain core design; the seeder's docblock states that.
2. `added_at` must survive a round trip through Postgres as the same instant regardless of the connection's time zone (Task 6: save a book added at `2026-11-07T15:04:05+00:00`, reload, compare `getTimestamp()` and that `getTimezone()->getName()` is `UTC`).
3. A book with an empty author list must render on `/` without a stray separator or "undefined" (Task 8 feature test: one seeded book with no authors shows its title and no comma).
4. The `books` prop must fail validation when the resource is wrong, or the contract test proves nothing (Task 8: a test feeds a document missing `addedAt` to the same validator and asserts failure).
5. Two books with the same title must both appear (Task 4 for `ListShelf`, Task 8 for the page).

---

## File structure

| File | Responsibility |
|---|---|
| `core/Domain/InvalidValue.php`, `InvalidId.php` | Base exception for rejected values; the id exception shared by all id classes |
| `core/Domain/Book/Title.php`, `Author.php`, `BookId.php`, `InvalidTitle.php`, `InvalidAuthor.php` | Value objects and their exceptions |
| `core/Domain/Book/Status.php` | Enum `OnShelf = 'shelf'`, `Wanted = 'wanted'` |
| `core/Domain/Book/Book.php` | Entity (slice subset) |
| `core/Domain/Text.php` | One internal helper: `Text::normalize(string): string` (trim, collapse Unicode whitespace, NFC), used by `Title`, `Author`, and later `CategoryName` |
| `core/Ports/BookRepository.php`, `Clock.php`, `IdGenerator.php` | Ports |
| `core/Application/AddBook.php`, `ListShelf.php` | Use cases |
| `tests/Doubles/InMemoryBookRepository.php`, `FixedClock.php`, `SequentialIdGenerator.php` | Test doubles (namespace `Tests\Doubles`) |
| `tests/Shared/BookRepositoryContract.php` | Trait: the shared repository suite (namespace `Tests\Shared`) |
| `tests/Core/Domain/Book/*Test.php`, `tests/Core/Application/*Test.php`, `tests/Core/Ports/InMemoryBookRepositoryTest.php` | Core tests |
| `contracts/openapi.yaml` | The contract (written; Task 5 wires it) |
| `resources/js/types/api.d.ts` | Generated from the contract; committed |
| `database/migrations/2026_11_07_000000_create_books_table.php` | The `books` table |
| `app/Models/BookRecord.php` | Eloquent model |
| `app/Adapters/Persistence/BookMapper.php`, `EloquentBookRepository.php` | Persistence adapter |
| `app/Adapters/Clock/SystemClock.php`, `app/Adapters/Ids/UuidV7IdGenerator.php` | Clock and id adapters |
| `app/Providers/CoreServiceProvider.php` | Composition root |
| `tests/Adapters/Persistence/EloquentBookRepositoryTest.php`, `BookMapperTest.php` | Adapter tests (Postgres) |
| `tests/Architecture/CoreDependencyTest.php` | Gains rules 3 and 4 |
| `database/seeders/ShelfSeeder.php`, `DevelopmentSeeder.php`, `DatabaseSeeder.php` | Seeding |
| `app/Http/Resources/BookResource.php` | JSON:API resource for a domain `Book` |
| `app/Http/Controllers/Web/ShelfController.php` | `/` |
| `resources/js/pages/shelf.tsx` | The page (replaces `coming-soon.tsx`) |
| `tests/Feature/ShelfPageTest.php`, `tests/Feature/ShelfSeederTest.php`, `tests/Feature/RegistrationClosedTest.php` | Feature tests |
| `tests/Contract/ShelfPropsContractTest.php` | Props validated against the contract |

---

### Task 1: Value objects and exceptions

**Files:**
- Create: `core/Domain/InvalidValue.php`, `core/Domain/InvalidId.php`, `core/Domain/Text.php`, `core/Domain/Book/Title.php`, `core/Domain/Book/InvalidTitle.php`, `core/Domain/Book/Author.php`, `core/Domain/Book/InvalidAuthor.php`, `core/Domain/Book/BookId.php`, `core/Domain/Book/Status.php`
- Test: `tests/Core/Domain/Book/TitleTest.php`, `AuthorTest.php`, `BookIdTest.php`, `StatusTest.php`
- Delete: `core/Domain/.gitkeep`

**Interfaces:**
- Produces: `abstract class InvalidValue extends \InvalidArgumentException`; `final class InvalidId extends InvalidValue`; `final class InvalidTitle extends InvalidValue`; `final class InvalidAuthor extends InvalidValue`.
- Produces: `final class Text { public static function normalize(string $text): string; }` (trim, collapse runs of Unicode whitespace to one space, NFC). Internal helper; not a value object.
- Produces: `final readonly class Title { public static function fromString(string $title): self; public function toString(): string; public function equals(Title $other): bool; }` (throws `InvalidTitle` when empty after normalization). `Author` has the same surface with `InvalidAuthor`. `BookId::fromString(string): self`, `toString()`, `equals()`, throws `InvalidId` unless the input matches `/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i`; stored lowercase.
- Produces: `enum Status: string { case OnShelf = 'shelf'; case Wanted = 'wanted'; }`.

- [ ] **Step 1: Write the failing tests**

```php
// TitleTest.php (tests/Core files start with declare(strict_types=1); and use statements; no Laravel)
it('keeps a title as typed after trimming and collapsing whitespace', function () {
    expect(Title::fromString("  How to  Read a Book ")->toString())->toBe('How to Read a Book');
});
it('treats a non-breaking space as whitespace', function () {
    expect(Title::fromString("How to\u{00A0}Read")->toString())->toBe('How to Read');
});
it('normalizes composed and decomposed accents to the same title', function () {
    expect(Title::fromString("\u{00C9}conomie")->equals(Title::fromString("E\u{0301}conomie")))->toBeTrue();
});
it('rejects a blank title', function (string $blank) {
    Title::fromString($blank);
})->with(['', '   ', "\u{00A0}\u{00A0}"])->throws(InvalidTitle::class);
it('compares titles exactly after normalization', function () {
    expect(Title::fromString('Good to Great')->equals(Title::fromString('good to great')))->toBeFalse();
});

// AuthorTest.php: same shape; plus
it('does not treat two spellings of a name as the same author', function () {
    expect(Author::fromString('Gino Wickman')->equals(Author::fromString('gino wickman')))->toBeFalse();
});

// BookIdTest.php
it('accepts a UUID and lowercases it', function () {
    expect(BookId::fromString('0192B4D8-7A3C-7E1F-9B2A-3C4D5E6F7A8B')->toString())->toBe('0192b4d8-7a3c-7e1f-9b2a-3c4d5e6f7a8b');
});
it('rejects anything that is not a UUID', function (string $bad) {
    BookId::fromString($bad);
})->with(['', 'not-a-uuid', '0192b4d8-7a3c-7e1f-9b2a-3c4d5e6f7a8', '0192b4d8-7a3c-7e1f-9b2a-3c4d5e6f7a8g'])->throws(InvalidId::class);

// StatusTest.php
it('uses the words the spec uses as backing values', function () {
    expect(Status::OnShelf->value)->toBe('shelf')->and(Status::Wanted->value)->toBe('wanted');
});
```

- [ ] **Step 2: Run them and see them fail**

Run: `php artisan test --compact --testsuite=Core`
Expected: FAIL, classes not found.

- [ ] **Step 3: Implement the classes**

Each class opens with a one-paragraph comment saying what the concept is and why it is a class rather than a string (teaching codebase). `Text::normalize` uses `preg_replace('/\s+/u', ' ', trim(...))` plus a Unicode-aware trim (`preg_replace('/^\s+|\s+$/u', '', ...)`) and `Normalizer::normalize($text, Normalizer::FORM_C)`.

- [ ] **Step 4: Run the Core suite and the Architecture suite; both pass**

Run: `php artisan test --compact --testsuite=Core` and `php artisan test --compact --testsuite=Architecture`
Expected: PASS (the architecture rules now cover real classes).

- [ ] **Step 5: Pint, `composer test`, commit** `feat(core): title, author, book id, and status value objects`

---

### Task 2: The Book entity (slice subset)

**Files:**
- Create: `core/Domain/Book/Book.php`
- Test: `tests/Core/Domain/Book/BookTest.php`

**Interfaces:**
- Produces: `final class Book { /** @param list<Author> $authors */ public function __construct(BookId $id, Title $title, array $authors, Status $status, \DateTimeImmutable $addedAt); public static function add(BookId $id, Title $title, \DateTimeImmutable $addedAt, Status $status = Status::OnShelf): self; public function creditAuthors(Author ...$authors): void; public function id(): BookId; public function title(): Title; /** @return list<Author> */ public function authors(): array; public function status(): Status; public function addedAt(): \DateTimeImmutable; public function isOnShelf(): bool; public function isWanted(): bool; }`
- The constructor and `creditAuthors` both drop duplicate authors (by `equals`), keeping first occurrence order. `addedAt` is stored as `$addedAt->setTimezone(new DateTimeZone('UTC'))`.

- [ ] **Step 1: Write the failing tests**

```php
it('starts on the shelf with no authors when added with only a title', function () {
    $book = Book::add(BookId::fromString('0192b4d8-7a3c-7e1f-9b2a-3c4d5e6f7a8b'), Title::fromString('Good to Great'), new DateTimeImmutable('2026-11-07T10:00:00+00:00'));
    expect($book->isOnShelf())->toBeTrue()->and($book->authors())->toBe([])->and($book->isWanted())->toBeFalse();
});
it('can be added as wanted', function () { /* Status::Wanted → isWanted() true */ });
it('keeps authors in cover order and drops a repeat', function () {
    $a = Author::fromString('Mortimer J. Adler'); $b = Author::fromString('Charles Van Doren');
    $book = Book::add(/* ... */); $book->creditAuthors($a, $b, $a);
    expect(array_map(fn (Author $x) => $x->toString(), $book->authors()))->toBe(['Mortimer J. Adler', 'Charles Van Doren']);
});
it('drops repeated authors given to the constructor too', function () { /* new Book(..., [$a, $a], ...) → one author */ });
it('stores the moment it was added in UTC', function () {
    $book = Book::add(/* id, title */, new DateTimeImmutable('2026-11-07T10:00:00-05:00'));
    expect($book->addedAt()->format(DATE_ATOM))->toBe('2026-11-07T15:00:00+00:00')->and($book->addedAt()->getTimezone()->getName())->toBe('UTC');
});
it('is identified by its id, not its title', function () { /* two books, same title, different ids: ids not equal */ });
```

- [ ] **Step 2: Run, see them fail** (`php artisan test --compact --testsuite=Core`)
- [ ] **Step 3: Implement `Book`** with the teaching comment: an entity has identity and changes over time; a value object does not.
- [ ] **Step 4: Run Core and Architecture suites; pass**
- [ ] **Step 5: Pint, `composer test`, commit** `feat(core): the Book entity with identity, title, authors, status, and addedAt`

---

### Task 3: Ports, doubles, and the shared repository suite

**Files:**
- Create: `core/Ports/BookRepository.php`, `core/Ports/Clock.php`, `core/Ports/IdGenerator.php`
- Create: `tests/Doubles/InMemoryBookRepository.php`, `tests/Doubles/FixedClock.php`, `tests/Doubles/SequentialIdGenerator.php`, `tests/Shared/BookRepositoryContract.php`
- Create: `tests/Core/Ports/InMemoryBookRepositoryTest.php` (a class: `final class InMemoryBookRepositoryTest extends PHPUnit\Framework\TestCase { use BookRepositoryContract; protected function makeRepository(): BookRepository { return new InMemoryBookRepository; } }`)
- Delete: `core/Ports/.gitkeep`

**Interfaces:**
- Produces: the three port interfaces as the design states (`save(Book): void`, `onShelf(): list<Book>`, `now(): DateTimeImmutable`, `nextBookId(): BookId`).
- Produces: `InMemoryBookRepository` (array keyed by id string; `onShelf` filters `isOnShelf()`); `FixedClock(DateTimeImmutable $now)`; `SequentialIdGenerator` returning `00000000-0000-7000-8000-000000000001`, `...002`, and so on.
- Produces: trait `Tests\Shared\BookRepositoryContract` with `abstract protected function makeRepository(): BookRepository;` and test methods: `test_a_saved_shelf_book_is_listed_with_every_field_intact`, `test_a_wanted_book_is_not_on_the_shelf`, `test_saving_the_same_id_again_updates_instead_of_duplicating`, `test_an_empty_repository_lists_nothing`, `test_two_books_with_the_same_title_are_both_listed`. Each builds books with real value objects and compares via `toString()`/`equals()`, and `addedAt()->getTimestamp()`.

- [ ] **Step 1: Write the trait and the in-memory test class first; run `php artisan test --compact --testsuite=Core`; FAIL (interfaces missing)**
- [ ] **Step 2: Implement ports and doubles; run again; PASS**
- [ ] **Step 3: Pint, `composer test`, commit** `feat(core): repository, clock, and id ports with in-memory doubles and a shared suite`

---

### Task 4: Use cases

**Files:**
- Create: `core/Application/AddBook.php`, `core/Application/ListShelf.php`
- Test: `tests/Core/Application/AddBookTest.php`, `ListShelfTest.php`
- Delete: `core/Application/.gitkeep`

**Interfaces:**
- Produces: `final class AddBook { public function __construct(private BookRepository $books, private IdGenerator $ids, private Clock $clock) {} public function execute(Title $title, Author ...$authors): Book; }` (new id from the generator, `addedAt` from the clock, status on the shelf, authors credited, saved, returned).
- Produces: `final class ListShelf { public function __construct(private BookRepository $books) {} /** @return list<Book> */ public function execute(): array; }` sorted by `mb_strtolower($title)` ascending, stable (`usort` is stable in PHP 8).

- [ ] **Step 1: Failing tests**

```php
// AddBookTest
it('adds a book on the shelf with an id from the generator and the clock time', function () {
    $repo = new InMemoryBookRepository; $clock = new FixedClock(new DateTimeImmutable('2026-11-07T15:04:05+00:00'));
    $book = (new AddBook($repo, new SequentialIdGenerator, $clock))->execute(Title::fromString('Good to Great'), Author::fromString('Jim Collins'));
    expect($book->id()->toString())->toBe('00000000-0000-7000-8000-000000000001')
        ->and($book->addedAt()->format(DATE_ATOM))->toBe('2026-11-07T15:04:05+00:00')
        ->and($repo->onShelf())->toHaveCount(1);
});
it('adds a book with no authors', function () { /* execute(Title) → authors() === [] */ });

// ListShelfTest
it('lists shelf books by title, ignoring case', function () {
    // save: 'the Checklist Manifesto', 'Secrets of Closing the Sale', 'Rich Dad Poor Dad', 'Good to Great'
    // expect titles in order: Good to Great, Rich Dad Poor Dad, Secrets of Closing the Sale, the Checklist Manifesto
});
it('leaves wanted books out', function () { /* one wanted, one shelf → one result */ });
it('lists two books that share a title', function () { /* two ids, same title → two results */ });
```

- [ ] **Step 2: Run, FAIL. Step 3: implement. Step 4: Core suite PASS.**
- [ ] **Step 5: Pint, `composer test`, commit** `feat(core): AddBook and ListShelf use cases`

---

### Task 5: Contract wiring and generated types

**Files:**
- Modify: `package.json` (`npm install -D openapi-typescript`; script `"contract:types": "openapi-typescript contracts/openapi.yaml -o resources/js/types/api.d.ts"`), `vite.config.ts` (add `resources/js/types/api.d.ts` to BOTH the `lint.ignorePatterns` and `fmt.ignorePatterns` lists: the generator writes double-quoted strings, the formatter wants single quotes, and reformatting the file would make the regenerate-and-diff check fail instead), `composer.json` (`ci:check` gains, after `npm run check`: `"npm run contract:types"` and `"git diff --exit-code -- resources/js/types/api.d.ts"`), `CLAUDE.md` Commands block (add `npm run contract:types   # regenerate resources/js/types/api.d.ts from contracts/openapi.yaml` after `npm run build`; it must have been run)
- Create: `resources/js/types/api.d.ts` (generated, committed)
- Create: `tests/Contract/ContractDocumentTest.php` (Pest file, boots nothing, no `uses()`: parses `contracts/openapi.yaml` with `Symfony\Component\Yaml\Yaml::parseFile` and asserts `openapi` starts with `3.1`, and that `components.schemas` has `BookCollection`, `BookResource`, `BookAttributes`, with `BookAttributes.required` equal to `['title', 'authors', 'status', 'addedAt']`)
- Modify: `phpunit.xml` (add `<testsuite name="Contract"><directory>tests/Contract</directory></testsuite>` and `<testsuite name="Adapters"><directory>tests/Adapters</directory></testsuite>`)

**Interfaces:**
- Produces: TypeScript `components['schemas']['BookCollection']` and friends, imported in Task 8 as `import type { components } from '@/types/api';`.

- [ ] **Step 1: Write `ContractDocumentTest`; run `php artisan test --compact --testsuite=Contract`; it fails only if the suite is missing from `phpunit.xml`; add the suite; run; PASS** (the contract file already exists).
- [ ] **Step 2: Install `openapi-typescript`, add the script, run it, inspect the generated file (it must export `components` with the three schemas), commit the file.**
- [ ] **Step 3: Wire `ci:check`; run `composer ci:check` on its own; exit 0.**
- [ ] **Step 4: Commit** `feat(contract): books resource schemas with generated TypeScript types checked in CI`

---

### Task 6: Persistence on Postgres and the composition root

**Files:**
- Modify: `phpunit.xml` (`DB_CONNECTION` `pgsql`, `DB_HOST` `127.0.0.1`, `DB_PORT` `5433`, `DB_DATABASE` `bathys_test`, `DB_USERNAME` `bathys`, `DB_PASSWORD` `bathys`; remove the sqlite lines; a comment says phpunit's `<env>` sets a variable only when it is not already set, so CI's job-level values win there).
- Modify: `.github/workflows/tests.yml`: the `DB_*` variables are currently set only on the "Setup Application" step, so `composer ci:check` would run with phpunit's local values (port 5433, `bathys_test`) and fail to connect. Move that `env:` block up to the `ci` job level (`jobs.ci.env`) so every step sees it, and update the comment above the `services:` block ("the PHP test suite uses its own database settings from phpunit.xml" is no longer true: it uses these).
- Create: `database/migrations/2026_11_07_000000_create_books_table.php`, `app/Models/BookRecord.php` (table `books`, `$incrementing = false`, `$keyType = 'string'`, `$guarded = []`, casts `authors` to `array`, `added_at` to `immutable_datetime`, and `protected $dateFormat = 'Y-m-d H:i:sP'` so the offset is written and Postgres never interprets a naive timestamp in the session's time zone), `app/Adapters/Persistence/BookMapper.php`, `app/Adapters/Persistence/EloquentBookRepository.php`, `app/Adapters/Clock/SystemClock.php`, `app/Adapters/Ids/UuidV7IdGenerator.php`, `app/Providers/CoreServiceProvider.php`
- Modify: `bootstrap/providers.php` (add `CoreServiceProvider::class`), `tests/Architecture/CoreDependencyTest.php` (rules 3 and 4)
- Test: `tests/Adapters/Persistence/EloquentBookRepositoryTest.php` (`final class ... extends Tests\TestCase { use RefreshDatabase; use BookRepositoryContract; protected function makeRepository(): BookRepository { return app(BookRepository::class); } }`), `tests/Adapters/Persistence/BookMapperTest.php`, `tests/Adapters/Clock/SystemClockTest.php` (returns UTC, within a second of now), `tests/Adapters/Ids/UuidV7IdGeneratorTest.php` (two ids differ, both parse as `BookId`, version nibble is `7`)

**Interfaces:**
- Produces: `BookMapper { public function toDomain(BookRecord $record): Book; public function fill(BookRecord $record, Book $book): BookRecord; }` (the design uses the same two names); `EloquentBookRepository implements BookRepository` (`save`: `BookRecord::query()->find($id) ?? new BookRecord(['id' => $id])`, fill, save; `onShelf`: `where('status', Status::OnShelf->value)->get()->map(toDomain)->all()`).
- Produces: `CoreServiceProvider::register()` binds `BookRepository::class => EloquentBookRepository::class`, `Clock::class => SystemClock::class`, `IdGenerator::class => UuidV7IdGenerator::class`.
- Architecture rules added:

```php
arch('only the persistence adapter, factories, and seeders use the BookRecord model')
    ->expect('App\Models\BookRecord')
    ->toOnlyBeUsedIn(['App\Adapters\Persistence', 'Database\Factories', 'Database\Seeders']);
arch('controllers call use cases, never repositories, adapters, or records')
    ->expect('App\Http\Controllers')
    ->not->toUse(['App\Models\BookRecord', 'Bathys\Ports', 'App\Adapters']);
```

- [ ] **Step 1: Switch `phpunit.xml` to Postgres; run `composer test`; everything still passes against `bathys_test` (Docker must be up: `docker compose up -d`).**
- [ ] **Step 2: Write the Eloquent repository test class and the mapper test (round trip: id, title, two authors, status, `added_at` as the same instant in UTC); plus the Review Focus 2 test in `EloquentBookRepositoryTest`: `DB::statement("SET TIME ZONE 'America/New_York'")`, save a book added at `2026-11-07T15:04:05+00:00`, reload through `onShelf()`, assert `getTimestamp()` equal and `getTimezone()->getName()` is `UTC`. Run `php artisan test --compact --testsuite=Adapters`; FAIL.**
- [ ] **Step 3: Migration, model, mapper, repository, adapters, provider; run Adapters suite; PASS.**
- [ ] **Step 4: Add the two architecture rules. Plant a violation for each (a temporary `use App\Models\BookRecord;` with a type-hint in `ShelfController`, which does not exist yet, so plant it in a scratch controller `app/Http/Controllers/Web/PlantedController.php`, and a `Bathys\Ports\BookRepository` type-hint there too); run the Architecture suite; see both rules FAIL; delete the scratch file; run again; PASS. Say in the commit body that the rules were seen to fail.**
- [ ] **Step 5: Pint, `composer test`, commit** `feat(persistence): books table, BookRecord, mapper, Eloquent repository, and the composition root`

---

### Task 7: The seeder

**Files:**
- Create: `database/seeders/ShelfSeeder.php`, `database/seeders/DevelopmentSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php` (calls `ShelfSeeder` only; the test-user block moves to `DevelopmentSeeder` with a comment: run with `php artisan db:seed --class=DevelopmentSeeder`, never on production)
- Modify: `CLAUDE.md` Known traps (add: "**`php artisan db:seed --force` runs on production.** `DatabaseSeeder` must stay safe for it: real data only, idempotent. Local-only data lives in `DevelopmentSeeder`, run by name.")
- Test: `tests/Feature/ShelfSeederTest.php`

**Interfaces:**
- Produces: `ShelfSeeder::BOOKS`, a constant `list<array{title: string, authors: list<string>}>` with the eight books from the design, in this order: Think and Grow Rich (Napoleon Hill); How to Read a Book (Mortimer J. Adler, Charles Van Doren); Building a StoryBrand (Donald Miller); What Color Is Your Parachute? (Richard N. Bolles); Rich Dad Poor Dad (Robert T. Kiyosaki); Good to Great (Jim Collins); The Checklist Manifesto (Atul Gawande); Secrets of Closing the Sale (Zig Ziglar).
- `run(ListShelf $listShelf, AddBook $addBook)`: resolved from the container (seeders support method injection). For each entry: skip when a listed book has `title()->equals($title)` and the same author list (same count, each `equals` in order); else `addBook->execute(...)`.

- [ ] **Step 1: Failing tests** (`uses(TestCase::class, RefreshDatabase::class)`): `it('seeds the eight books through the use case')` (seed, `app(ListShelf::class)->execute()` has 8, titles sorted as expected, "How to Read a Book" has two authors in order); `it('is idempotent')` (seed twice, still 8); `it('does not create the development user')` (`User::count()` is 0 after `db:seed`); `it('treats a book with different authors as a different book')` (FIRST add "Good to Great" by "Someone Else" through `AddBook`, THEN seed; the shelf has 9 books. A seeder that matched on title alone would stop at 8, so this order is what makes the test able to fail).
- [ ] **Step 2: Run, FAIL. Step 3: implement. Step 4: PASS; also run `php artisan db:seed` locally once and `php artisan db:seed` again; count stays 8 (`php artisan tinker --execute 'echo App\Models\BookRecord::count();'` is acceptable here as a one-off check).**
- [ ] **Step 5: Pint, `composer test`, commit** `feat(persistence): idempotent shelf seeder through AddBook; development user moved out of db:seed`

---

### Task 8: The home page through the contract

**Files:**
- Create: `app/Http/Resources/BookResource.php`, `app/Http/Controllers/Web/ShelfController.php`, `resources/js/pages/shelf.tsx`
- Delete: `resources/js/pages/coming-soon.tsx`, `tests/Feature/ComingSoonTest.php` (its registration tests move to `tests/Feature/RegistrationClosedTest.php`, unchanged in substance)
- Modify: `routes/web.php` (`Route::get('/', ShelfController::class)->name('home');`), `composer.json` (`composer require --dev opis/json-schema symfony/yaml` after approval), `resources/js/app.tsx` (the layout switch has `case name === 'coming-soon': return null;`, which keeps that page out of the dashboard sidebar layout; change it to `'shelf'`, or the home page renders inside `AppLayout` with Dashboard navigation and no test would notice)
- Test: `tests/Feature/ShelfPageTest.php`, `tests/Contract/ShelfPropsContractTest.php`

**Interfaces:**
- Produces: `BookResource extends JsonResource` whose `toArray()` returns `['type' => 'books', 'id' => ..., 'attributes' => ['title' => ..., 'authors' => [...strings], 'status' => $book->status()->value, 'addedAt' => $book->addedAt()->format(DATE_RFC3339)]]`; `$this->resource` is a domain `Book`.
- Produces: `ShelfController::__invoke(ListShelf $listShelf): Response` returning `Inertia::render('shelf', ['books' => ['data' => BookResource::collection($listShelf->execute())->resolve()]])`.
- The page props type: `{ books: components['schemas']['BookCollection'] }`. Markup: `<Head title="Shelf" />`, the icon and "Bathys" as an `h1`, the one-line description, then `<ol>` of `<li>` with the title in a `<p>` and authors joined by `', '` in a second `<p>` only when `authors.length > 0`; empty state `<p>No books on the shelf yet.</p>` when `books.data.length === 0`; the version footer from `appVersion` as today. Mobile-first Tailwind classes; keep it plain.

- [ ] **Step 1: Failing feature tests**

```php
it('lists the seeded shelf in title order', function () {
    $this->seed(); // DatabaseSeeder
    $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page->component('shelf')
        ->has('books.data', 8)
        // sorted: 0 Building a StoryBrand, 1 Good to Great, 2 How to Read a Book, 3 Rich Dad Poor Dad,
        // 4 Secrets of Closing the Sale, 5 The Checklist Manifesto, 6 Think and Grow Rich, 7 What Color Is Your Parachute?
        ->where('books.data.0.attributes.title', 'Building a StoryBrand')
        ->where('books.data.7.attributes.title', 'What Color Is Your Parachute?')
        ->where('books.data.2.attributes.authors', ['Mortimer J. Adler', 'Charles Van Doren']));
});
it('passes an empty collection when there are no books', function () { /* no seed; assertInertia: component 'shelf', has('books.data', 0). The empty-state copy is rendered by React, so it is checked by eye in the browser, not here. */ });
it('renders a book with no authors without a separator', function () { /* add one book via AddBook with no authors; where('books.data.0.attributes.authors', []) */ });
it('keeps the version meta tag', function () { /* assertSee('<meta name="app-version"', false) */ });
it('gives the page the package.json version to display', function () { /* moved from ComingSoonTest: where('appVersion', <package.json version>) */ });

// ShelfPropsContractTest (Feature-style, boots Laravel; in tests/Contract)
it('serves books props that match the contract', function () {
    $this->seed();
    $props = $this->get('/')->viewData('page')['props']['books'];   // or via assertInertia closure capturing props
    $schema = contractSchema('BookCollection');                     // helper in this file: parse YAML, pull components.schemas, build a JSON Schema with $defs
    expect((new Opis\JsonSchema\Validator)->validate(json_decode(json_encode($props)), $schema)->isValid())->toBeTrue();
});
it('rejects a document that breaks the contract', function () {
    $broken = ['data' => [['type' => 'books', 'id' => '0192b4d8-7a3c-7e1f-9b2a-3c4d5e6f7a8b', 'attributes' => ['title' => 'x', 'authors' => [], 'status' => 'shelf']]]]; // no addedAt
    expect(validator()->validate(json_decode(json_encode($broken)), $schema)->isValid())->toBeFalse();
});
```

The `contractSchema()` helper: load the YAML, take `components.schemas`, and produce a schema `{'$ref': '#/$defs/BookCollection', '$defs': <schemas with '#/components/schemas/' rewritten to '#/$defs/'>}` so `$ref`s resolve, returned as an object (`json_decode(json_encode($array))`), because `Opis\JsonSchema\Validator::validate()` takes objects, not arrays. Check in the installed opis source that `const` and `format: uuid` are supported with format validation on; if `uuid` is not a built-in format, add a pattern to the contract instead of a custom format.

Base classes: `tests/Pest.php` is empty, so `ShelfPropsContractTest.php` starts with `uses(Tests\TestCase::class, RefreshDatabase::class);` while `ContractDocumentTest.php` (Task 5) stays framework-free with no `uses()`.

- [ ] **Step 2: Run, FAIL. Step 3: implement resource, controller, route, page; delete coming-soon; `npm run build`; `npm run types:check`. Step 4: Feature + Contract suites PASS.**
- [ ] **Step 5: Pint, `composer ci:check` on its own, commit** `feat(web): the shelf home page, served through ListShelf and the JSON:API resource`

---

### Task 9: Documentation sync

**Files:**
- Modify: `docs/development.md` (testing table: `tests/Adapters/` and `tests/Contract/` exist and run on Postgres; replace the SQLite sentence with "All framework suites run on Postgres: `bathys_test` locally, created by the Docker init script, and the CI service database."), `docs/spec.md` (Decisions Log row 20: "Is the home screen public? Yes, until sign-in exists (step 1e)."; Version History v0.6 line; bump the header to 0.6), `README.md` (one line: the home page lists the shelf), `CHANGELOG.md` (tidy the `[Unreleased]` entries into one coherent block for the slice)
- Test: `composer ci:check` on its own.

- [ ] **Step 1: Make the edits. Step 2: `composer ci:check` exit 0. Step 3: commit** `docs: record the shelf slice in the spec, development method, and README`

---

## After the tasks

1. `reviewer` (Opus) reads the whole branch against this plan, both designs, and the five ADRs. `security-reviewer` reads the seeder, the new route, and the contract test (public page, new input surface: none yet, but the resource shape is public).
2. One fix wave, one scoped re-review.
3. Push `feat/shelf-slice`, open the PR. **Do not merge.** Ryan merges; CI deploys; then Ryan runs `php artisan db:seed --force` once on production from `/home/forge/bathys.polarispixels.com/current` (`--force` because production asks for confirmation otherwise).
