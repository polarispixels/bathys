# Shelf Slice Implementation Plan (MVP)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** `/` lists the books on the shelf, seeded with eight books on deploy, built through every layer: domain, ports and use cases, contract, Postgres persistence, seeder, Inertia page. Smallest version that is useful; later steps widen each layer.

**Architecture:** Plain PHP domain and use cases in `core/` behind three ports; Laravel adapters in `app/` (Eloquent repository, system clock, UUID v7 ids, a composition-root provider); a JSON:API resource shared by the Inertia page and the future API; the contract in `contracts/openapi.yaml` with generated TypeScript types. See ADR-0003, 0004, 0005, 0007, 0008.

**Tech Stack:** PHP 8.4, Laravel 13, Pest 5 on PHPUnit 13, Postgres 18 (Docker locally, service in CI), Inertia v3 + React 19 + TypeScript, `openapi-typescript` (new dev dependency, approved).

**Spec:** `docs/designs/2026-11-07-shelf-slice-design.md` (read the Decisions table and the MVP trim), which implements a subset of `docs/designs/2026-11-07-domain-core-design.md` verbatim. Read both.

## Global Constraints

- **The dependency rule.** Code in `core/` (namespace `Bathys\`) never imports `Illuminate\`, `Laravel\`, `Inertia\`, or `App\`. `Bathys\Domain` imports nothing from `Bathys\Application` or `Bathys\Ports`. The architecture tests enforce this; never weaken one.
- **Controllers call use cases, never repositories or Eloquent models.** Seeders go through use cases too.
- `declare(strict_types=1);` in every new PHP file (tests included); return types everywhere; PHPDoc array shapes (`list<Book>`, `list<Author>`). Larastan level 7 passes.
- **Core tests** (`tests/Core/`) boot nothing: no `base_path()`, `config()`, `app()`, or facades. `tests/Pest.php` is empty, so framework tests declare `uses(Tests\TestCase::class, RefreshDatabase::class);` themselves.
- **Framework tests run on Postgres** (`bathys_test` locally via Docker, `bathys` in CI) from Task 4 on; use `RefreshDatabase`.
- **Tests make zero external calls.**
- **Text values** follow the domain core design: trim and collapse Unicode whitespace (`\s+` with the `u` flag), NFC-normalize with `Normalizer::normalize`, reject empty; `addedAt` is converted to UTC in `Book`; UUIDs are validated by shape (8-4-4-4-12 hex) and lowercased.
- **Copy:** plain words, no em dashes anywhere (code comments, docs, UI, commit messages). Absolute dates. Each new class opens with a one-paragraph teaching comment (what it is, why it is a class).
- **Commits:** one per task, Conventional Commits with the layer as scope (`feat(core): ...`, `feat(persistence): ...`, `feat(contract): ...`, `feat(web): ...`). Body ends with the attribution lines the dispatch brief gives you. Never pipe `composer test` or `composer ci:check`; gate the commit on the exit code.
- **CHANGELOG:** every task adds a line under `## [Unreleased]`. No version bump in this branch (the controller releases after the review).
- **Never run anything on the production server.**

## Review Focus

1. Running the seeder again adds nothing (Task 5). The seeder compares `Title` and `Author` values, never raw strings.
2. `added_at` survives a Postgres round trip as the same instant with the session time zone set to `America/New_York` (Task 4).
3. A book with an empty author list renders on `/` with no stray separator (Task 6: a book added with no authors has `authors: []` in props, and the page only renders the authors line when the list is non-empty).
4. Two books with the same title both appear (Task 2 for `ListShelf`, Task 4 for the repository).
5. The home page keeps the version meta tag and the `appVersion` prop (Task 6).

---

## File structure

| File | Responsibility |
|---|---|
| `core/Domain/InvalidValue.php`, `InvalidId.php`, `Text.php` | Base exception; shared id exception; `Text::normalize()` helper |
| `core/Domain/Book/Title.php`, `Author.php`, `BookId.php`, `InvalidTitle.php`, `InvalidAuthor.php`, `Status.php`, `Book.php` | Value objects, enum, entity |
| `core/Ports/BookRepository.php`, `Clock.php`, `IdGenerator.php` | Ports |
| `core/Application/AddBook.php`, `ListShelf.php` | Use cases |
| `tests/Doubles/InMemoryBookRepository.php`, `FixedClock.php`, `SequentialIdGenerator.php` | Doubles (`Tests\Doubles`) |
| `tests/Shared/BookRepositoryContract.php` | Trait: the shared repository suite (`Tests\Shared`) |
| `tests/Core/...` | Core tests |
| `contracts/openapi.yaml` (exists), `resources/js/types/api.d.ts` (generated, committed) | Contract and types |
| `database/migrations/2026_11_07_000000_create_books_table.php`, `2026_11_07_000001_seed_the_first_shelf.php` | Table; the one-time seed that runs on deploy |
| `app/Models/BookRecord.php`, `app/Adapters/Persistence/BookMapper.php`, `EloquentBookRepository.php`, `app/Adapters/Clock/SystemClock.php`, `app/Adapters/Ids/UuidV7IdGenerator.php`, `app/Providers/CoreServiceProvider.php` | Adapters and the composition root |
| `database/seeders/ShelfSeeder.php`, `DatabaseSeeder.php` | Seeding through `AddBook` |
| `app/Http/Resources/BookResource.php`, `app/Http/Controllers/Web/ShelfController.php`, `resources/js/pages/shelf.tsx` | The page |
| `tests/Adapters/...`, `tests/Feature/ShelfPageTest.php`, `ShelfSeederTest.php`, `RegistrationClosedTest.php`, `tests/Architecture/CoreDependencyTest.php` | Framework tests and rules |

---

### Task 1: Value objects, the enum, and the Book entity

**Files:**
- Create: `core/Domain/InvalidValue.php`, `InvalidId.php`, `Text.php`, `core/Domain/Book/Title.php`, `InvalidTitle.php`, `Author.php`, `InvalidAuthor.php`, `BookId.php`, `Status.php`, `Book.php`
- Test: `tests/Core/Domain/Book/TitleTest.php`, `AuthorTest.php`, `BookIdTest.php`, `StatusTest.php`, `BookTest.php`
- Delete: `core/Domain/.gitkeep`

**Interfaces:**
- `abstract class InvalidValue extends \InvalidArgumentException`; `final class InvalidId`, `InvalidTitle`, `InvalidAuthor` extend it.
- `final class Text { public static function normalize(string $text): string; }`: Unicode-aware trim (`preg_replace('/^\s+|\s+$/u', '', ...)`), collapse `\s+` (with `u`) to one space, `Normalizer::normalize(..., Normalizer::FORM_C)`.
- `final readonly class Title { public static function fromString(string $title): self; public function toString(): string; public function equals(Title $other): bool; }` throws `InvalidTitle` when empty after normalization. `Author` is the same shape with `InvalidAuthor`. `BookId::fromString()`, `toString()`, `equals()`; throws `InvalidId` unless `/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i`; stored lowercase.
- `enum Status: string { case OnShelf = 'shelf'; case Wanted = 'wanted'; }`
- `final class Book { /** @param list<Author> $authors */ public function __construct(BookId $id, Title $title, array $authors, Status $status, \DateTimeImmutable $addedAt); public static function add(BookId $id, Title $title, \DateTimeImmutable $addedAt, Status $status = Status::OnShelf): self; public function creditAuthors(Author ...$authors): void; public function id(): BookId; public function title(): Title; /** @return list<Author> */ public function authors(): array; public function status(): Status; public function addedAt(): \DateTimeImmutable; public function isOnShelf(): bool; public function isWanted(): bool; }`. Constructor and `creditAuthors` drop duplicate authors (by `equals`), first occurrence wins; `addedAt` stored as `->setTimezone(new DateTimeZone('UTC'))`.

- [ ] **Step 1: Write the failing tests**

```php
// TitleTest.php
it('keeps a title as typed after trimming and collapsing whitespace', fn () => expect(Title::fromString("  How to  Read a Book ")->toString())->toBe('How to Read a Book'));
it('treats a non-breaking space as whitespace', fn () => expect(Title::fromString("How to\u{00A0}Read")->toString())->toBe('How to Read'));
it('normalizes composed and decomposed accents to the same title', fn () => expect(Title::fromString("\u{00C9}conomie")->equals(Title::fromString("E\u{0301}conomie")))->toBeTrue());
it('rejects a blank title', fn (string $blank) => Title::fromString($blank))->with(['', '   ', "\u{00A0}\u{00A0}"])->throws(InvalidTitle::class);
it('compares titles exactly after normalization', fn () => expect(Title::fromString('Good to Great')->equals(Title::fromString('good to great')))->toBeFalse());
// AuthorTest.php: the same four plus
it('does not treat two spellings of a name as the same author', fn () => expect(Author::fromString('Gino Wickman')->equals(Author::fromString('gino wickman')))->toBeFalse());
// BookIdTest.php
it('accepts a UUID and lowercases it', fn () => expect(BookId::fromString('0192B4D8-7A3C-7E1F-9B2A-3C4D5E6F7A8B')->toString())->toBe('0192b4d8-7a3c-7e1f-9b2a-3c4d5e6f7a8b'));
it('rejects anything that is not a UUID', fn (string $bad) => BookId::fromString($bad))->with(['', 'not-a-uuid', '0192b4d8-7a3c-7e1f-9b2a-3c4d5e6f7a8', '0192b4d8-7a3c-7e1f-9b2a-3c4d5e6f7a8g'])->throws(InvalidId::class);
// StatusTest.php
it('uses the words the spec uses as backing values', fn () => expect(Status::OnShelf->value)->toBe('shelf')->and(Status::Wanted->value)->toBe('wanted'));
// BookTest.php
it('starts on the shelf with no authors when added with only a title', ...);        // isOnShelf true, authors [], isWanted false
it('can be added as wanted', ...);                                                 // Status::Wanted
it('keeps authors in cover order and drops a repeat', ...);                        // creditAuthors(A, B, A) → [A, B] by toString
it('drops repeated authors given to the constructor too', ...);                    // new Book(..., [A, A], ...) → one
it('stores the moment it was added in UTC', ...);                                  // added at 2026-11-07T10:00:00-05:00 → '2026-11-07T15:00:00+00:00', timezone name 'UTC'
it('is identified by its id, not its title', ...);                                 // same title, two ids: ids not equal
```

- [ ] **Step 2: Run `php artisan test --compact --testsuite=Core`; FAIL (classes not found).**
- [ ] **Step 3: Implement.**
- [ ] **Step 4: Core and Architecture suites PASS.**
- [ ] **Step 5: Pint, `composer test`, commit** `feat(core): Book with its title, authors, id, and status value objects`

---

### Task 2: Ports, doubles, the shared repository suite, and the use cases

**Files:**
- Create: `core/Ports/BookRepository.php`, `Clock.php`, `IdGenerator.php`, `core/Application/AddBook.php`, `ListShelf.php`
- Create: `tests/Doubles/InMemoryBookRepository.php`, `FixedClock.php`, `SequentialIdGenerator.php`, `tests/Shared/BookRepositoryContract.php`
- Test: `tests/Core/Ports/InMemoryBookRepositoryTest.php` (class: `final class InMemoryBookRepositoryTest extends PHPUnit\Framework\TestCase { use BookRepositoryContract; protected function makeRepository(): BookRepository { return new InMemoryBookRepository; } }`), `tests/Core/Application/AddBookTest.php`, `ListShelfTest.php`
- Delete: `core/Ports/.gitkeep`, `core/Application/.gitkeep`

**Interfaces:**
- `interface BookRepository { public function save(Book $book): void; /** @return list<Book> */ public function onShelf(): array; }` (save inserts or updates by id; order of `onShelf` unspecified). `interface Clock { public function now(): \DateTimeImmutable; }`. `interface IdGenerator { public function nextBookId(): BookId; }`.
- Doubles: `InMemoryBookRepository` (array keyed by id string); `FixedClock(\DateTimeImmutable $now)`; `SequentialIdGenerator` yielding `00000000-0000-7000-8000-000000000001`, `...002`, and so on.
- Trait `Tests\Shared\BookRepositoryContract`: `abstract protected function makeRepository(): BookRepository;` and `test_a_saved_shelf_book_is_listed_with_every_field_intact` (id, title, two authors in order, status, `addedAt` timestamp and `UTC` zone), `test_a_wanted_book_is_not_on_the_shelf`, `test_saving_the_same_id_again_updates_instead_of_duplicating`, `test_an_empty_repository_lists_nothing`, `test_two_books_with_the_same_title_are_both_listed`.
- `final class AddBook { public function __construct(private BookRepository $books, private IdGenerator $ids, private Clock $clock) {} public function execute(Title $title, Author ...$authors): Book; }` (id from the generator, `addedAt` from the clock, status on the shelf, authors credited, saved, returned).
- `final class ListShelf { public function __construct(private BookRepository $books) {} /** @return list<Book> */ public function execute(): array; }` sorted by `mb_strtolower(title)` ascending, stable.

- [ ] **Step 1: Failing tests**

```php
// AddBookTest
it('adds a book on the shelf with an id from the generator and the clock time', function () {
    $repo = new InMemoryBookRepository; $clock = new FixedClock(new DateTimeImmutable('2026-11-07T15:04:05+00:00'));
    $book = (new AddBook($repo, new SequentialIdGenerator, $clock))->execute(Title::fromString('Good to Great'), Author::fromString('Jim Collins'));
    expect($book->id()->toString())->toBe('00000000-0000-7000-8000-000000000001')
        ->and($book->addedAt()->format(DATE_ATOM))->toBe('2026-11-07T15:04:05+00:00')
        ->and($book->isOnShelf())->toBeTrue()
        ->and($repo->onShelf())->toHaveCount(1);
});
it('adds a book with no authors', ...);   // authors() === []
// ListShelfTest
it('lists shelf books by title, ignoring case', ...);   // saved: 'the Checklist Manifesto', 'Secrets of Closing the Sale', 'Rich Dad Poor Dad', 'Good to Great' → Good to Great, Rich Dad Poor Dad, Secrets of Closing the Sale, the Checklist Manifesto
it('leaves wanted books out', ...);
it('lists two books that share a title', ...);
```

- [ ] **Step 2: Run Core suite; FAIL. Step 3: implement. Step 4: Core + Architecture PASS.**
- [ ] **Step 5: Pint, `composer test`, commit** `feat(core): ports, in-memory doubles, the shared repository suite, AddBook, and ListShelf`

---

### Task 3: Contract types generated and checked in CI

**Files:**
- Modify: `package.json` (`npm install -D openapi-typescript`; script `"contract:types": "openapi-typescript contracts/openapi.yaml -o resources/js/types/api.d.ts"`), `vite.config.ts` (add `resources/js/types/api.d.ts` to BOTH `lint.ignorePatterns` and `fmt.ignorePatterns`; the generator writes double quotes and the formatter wants single), `composer.json` (`ci:check` gains, after `npm run check`: `"npm run contract:types"` then `"git diff --exit-code -- resources/js/types/api.d.ts"`), `CLAUDE.md` Commands block (add `npm run contract:types   # regenerate resources/js/types/api.d.ts from contracts/openapi.yaml`; run it first)
- Create: `resources/js/types/api.d.ts` (generated, committed)

- [ ] **Step 1: Install, add the script, run it; the generated file exports `components` with `BookCollection`, `BookResource`, `BookAttributes`; `npm run check` and `npm run types:check` pass.**
- [ ] **Step 2: Wire `ci:check`; run `composer ci:check` on its own; exit 0.**
- [ ] **Step 3: Commit** `feat(contract): TypeScript types generated from contracts/openapi.yaml and checked in CI`

---

### Task 4: Persistence on Postgres, the composition root, and the architecture rules

**Files:**
- Modify: `phpunit.xml` (`DB_CONNECTION` `pgsql`, `DB_HOST` `127.0.0.1`, `DB_PORT` `5433`, `DB_DATABASE` `bathys_test`, `DB_USERNAME` `bathys`, `DB_PASSWORD` `bathys`; remove the sqlite lines; add `<testsuite name="Adapters"><directory>tests/Adapters</directory></testsuite>`; a comment: phpunit sets a variable only when it is not already set, so CI's job-level values win there)
- Modify: `.github/workflows/tests.yml`: move the `DB_*` `env:` block from the "Setup Application" step to the `ci` job level (`jobs.ci.env`), so `composer ci:check` sees it; fix the comment above `services:` (the suite now uses these values).
- Create: `database/migrations/2026_11_07_000000_create_books_table.php` (`uuid('id')->primary()`, `text('title')`, `jsonb('authors')`, `string('status', 16)->index()`, `timestampTz('added_at')`, `timestamps()`), `app/Models/BookRecord.php` (table `books`, `$incrementing = false`, `$keyType = 'string'`, `$guarded = []`, casts `authors` `array` and `added_at` `immutable_datetime`, `protected $dateFormat = 'Y-m-d H:i:sP'`), `app/Adapters/Persistence/BookMapper.php` (`toDomain(BookRecord): Book`, `fill(BookRecord, Book): BookRecord`), `EloquentBookRepository.php` (`save`: `BookRecord::query()->find($id) ?? new BookRecord(['id' => $id])`, fill, save; `onShelf`: `where('status', Status::OnShelf->value)->get()->map(toDomain)->all()`), `app/Adapters/Clock/SystemClock.php` (`new DateTimeImmutable('now', new DateTimeZone('UTC'))`), `app/Adapters/Ids/UuidV7IdGenerator.php` (`BookId::fromString((string) Str::uuid7())`), `app/Providers/CoreServiceProvider.php` (binds the three ports)
- Modify: `bootstrap/providers.php` (add `CoreServiceProvider::class`), `tests/Architecture/CoreDependencyTest.php`
- Test: `tests/Adapters/Persistence/EloquentBookRepositoryTest.php` (`final class ... extends Tests\TestCase { use RefreshDatabase; use BookRepositoryContract; protected function makeRepository(): BookRepository { return app(BookRepository::class); } public function test_the_instant_survives_a_session_time_zone(): void { DB::statement("SET TIME ZONE 'America/New_York'"); /* save a book added at 2026-11-07T15:04:05+00:00; reload via onShelf(); getTimestamp() equal; getTimezone()->getName() === 'UTC' */ } }`), `tests/Adapters/Ids/UuidV7IdGeneratorTest.php` (two ids differ, both parse, version nibble `7`), `tests/Adapters/Clock/SystemClockTest.php` (UTC, within 2 seconds of `time()`)
- Architecture rules to add:

```php
arch('only the persistence adapter, factories, and seeders use the BookRecord model')
    ->expect('App\Models\BookRecord')
    ->toOnlyBeUsedIn(['App\Adapters\Persistence', 'Database\Factories', 'Database\Seeders']);
arch('controllers call use cases, never repositories, adapters, or records')
    ->expect('App\Http\Controllers')
    ->not->toUse(['App\Models\BookRecord', 'Bathys\Ports', 'App\Adapters']);
```

- [ ] **Step 1: Switch `phpunit.xml` and the workflow to Postgres; `composer test` passes locally against `bathys_test` (Docker is up).**
- [ ] **Step 2: Write the three adapter test classes; `php artisan test --compact --testsuite=Adapters`; FAIL.**
- [ ] **Step 3: Migration, model, mapper, repository, clock, ids, provider; Adapters suite PASS.**
- [ ] **Step 4: Add the two architecture rules. Plant a violation: a scratch `app/Http/Controllers/Web/PlantedController.php` that type-hints both `App\Models\BookRecord` and `Bathys\Ports\BookRepository`; run the Architecture suite; see BOTH rules fail; delete the scratch file; run again; PASS. Say in the commit body that the rules were seen to fail.**
- [ ] **Step 5: Pint, `composer test`, commit** `feat(persistence): books table, BookRecord, mapper, Eloquent repository, composition root, and the model and controller rules`

---

### Task 5: The seeder, and the seed that runs on deploy

**Files:**
- Create: `database/seeders/ShelfSeeder.php`, `database/migrations/2026_11_07_000001_seed_the_first_shelf.php`
- Modify: `database/seeders/DatabaseSeeder.php` (calls `ShelfSeeder` only; the starter kit's test-user block is removed, with a comment that the real user arrives with #7), `CLAUDE.md` Known traps (add: "**`migrate --force` runs on every deploy and `DatabaseSeeder` may run on production.** Seeders hold real data only and are idempotent; local-only data never goes in `DatabaseSeeder`.")
- Test: `tests/Feature/ShelfSeederTest.php`

**Interfaces:**
- `ShelfSeeder::BOOKS`: constant `list<array{title: string, authors: list<string>}>`, in this order: Think and Grow Rich (Napoleon Hill); How to Read a Book (Mortimer J. Adler, Charles Van Doren); Building a StoryBrand (Donald Miller); What Color Is Your Parachute? (Richard N. Bolles); Rich Dad Poor Dad (Robert T. Kiyosaki); Good to Great (Jim Collins); The Checklist Manifesto (Atul Gawande); Secrets of Closing the Sale (Zig Ziglar).
- `ShelfSeeder::run(ListShelf $listShelf, AddBook $addBook): void` (method injection): for each entry, skip when a listed book has `title()->equals($title)` and the same author list (same count, each `equals` in order); else `$addBook->execute(...)`. Docblock states the exact-match rule.
- The migration's `up()`: `Artisan::call('db:seed', ['--class' => ShelfSeeder::class, '--force' => true]);` with a comment: the deploy script runs `migrate --force`, so this is how the first shelf reaches production without anyone running a command; the seeder is idempotent, so re-running is safe. `down()` does nothing (the books are data, not schema; say so).

- [ ] **Step 1: Failing tests** (`uses(Tests\TestCase::class, RefreshDatabase::class)`): `it('seeds the eight books through the use case')` (after `RefreshDatabase` migrations alone, the seed migration has already run: `app(ListShelf::class)->execute()` has 8 and "How to Read a Book" has two authors in order; title order starts with Building a StoryBrand and ends with What Color Is Your Parachute?); `it('is idempotent')` (`$this->seed()` after the migrations; still 8); `it('does not create the development user')` (`User::count()` is 0); `it('treats a book with different authors as a different book')` (add "Good to Great" by "Someone Else" through `AddBook`, then `$this->seed()`; 9 books, and both Good to Great rows present). Note for the implementer: `RefreshDatabase` runs migrations, which run the seed migration, so the eight books exist in every framework test that uses `RefreshDatabase`; Task 6's "empty" test must delete them first through `BookRecord::query()->delete()` (allowed in tests).
- [ ] **Step 2: FAIL. Step 3: implement. Step 4: Feature suite PASS; also run `php artisan migrate:fresh` locally then `php artisan db:seed`; the local count stays 8.**
- [ ] **Step 5: Pint, `composer test`, commit** `feat(persistence): idempotent shelf seeder through AddBook, run by a migration so production seeds itself on deploy`

---

### Task 6: The home page

**Files:**
- Create: `app/Http/Resources/BookResource.php`, `app/Http/Controllers/Web/ShelfController.php`, `resources/js/pages/shelf.tsx`
- Delete: `resources/js/pages/coming-soon.tsx`, `tests/Feature/ComingSoonTest.php` (its registration tests move to `tests/Feature/RegistrationClosedTest.php` unchanged; its `appVersion` prop test moves to `ShelfPageTest`)
- Modify: `routes/web.php` (`Route::get('/', ShelfController::class)->name('home');`), `resources/js/app.tsx` (the layout switch's `case name === 'coming-soon': return null;` becomes `'shelf'`, so the page stays out of the dashboard sidebar layout), `README.md` (one line: the home page lists the shelf), `docs/development.md` (testing table: `tests/Adapters/` exists and runs on Postgres; replace the SQLite sentence with "All framework suites run on Postgres: `bathys_test` locally, created by the Docker init script, and the CI service database."), `docs/spec.md` (Decisions Log row 20: "Is the home screen public? Yes, until sign-in exists (step 1e)."; header `Version: 0.6`; a v0.6 line in Version History)
- Test: `tests/Feature/ShelfPageTest.php`

**Interfaces:**
- `BookResource extends JsonResource`; `$this->resource` is a domain `Book`; `toArray()` returns `['type' => 'books', 'id' => $book->id()->toString(), 'attributes' => ['title' => ..., 'authors' => [...toString], 'status' => $book->status()->value, 'addedAt' => $book->addedAt()->format(DATE_RFC3339)]]`.
- `ShelfController::__invoke(ListShelf $listShelf): \Inertia\Response` returning `Inertia::render('shelf', ['books' => ['data' => BookResource::collection($listShelf->execute())->resolve()]])`.
- Page props type `{ books: components['schemas']['BookCollection'] }` via `import type { components } from '@/types/api';`. Markup: `<Head title="Shelf" />`; the icon (`/icon.svg`, as the coming-soon page did) and an `h1` "Bathys"; one line "A personal catalog of the books on the shelf and the books wanted, with how deep each one has been read."; an `<ol>` of `<li>` with the title and, only when `authors.length > 0`, a second line with the authors joined by `', '`; empty state `<p>No books on the shelf yet.</p>` when the list is empty; the version footer from `appVersion` as today. Mobile-first, plain Tailwind.

- [ ] **Step 1: Failing tests**

```php
it('lists the seeded shelf in title order', function () {
    // seeded by the migration (see Task 5); sorted: 0 Building a StoryBrand, 1 Good to Great, 2 How to Read a Book, 3 Rich Dad Poor Dad, 4 Secrets..., 5 The Checklist Manifesto, 6 Think and Grow Rich, 7 What Color Is Your Parachute?
    $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page->component('shelf')
        ->has('books.data', 8)
        ->where('books.data.0.attributes.title', 'Building a StoryBrand')
        ->where('books.data.7.attributes.title', 'What Color Is Your Parachute?')
        ->where('books.data.2.attributes.authors', ['Mortimer J. Adler', 'Charles Van Doren'])
        ->where('books.data.0.type', 'books')
        ->where('books.data.0.attributes.status', 'shelf'));
});
it('passes an empty collection when there are no books', ...);     // BookRecord::query()->delete() first; has('books.data', 0)
it('passes a book with no authors as an empty list', ...);          // delete all; AddBook with a title only; where('books.data.0.attributes.authors', [])
it('keeps the version meta tag', ...);                             // assertSee('<meta name="app-version"', false)
it('gives the page the package.json version to display', ...);     // moved from ComingSoonTest
```

- [ ] **Step 2: FAIL. Step 3: implement; delete coming-soon; `npm run build`; `npm run types:check`. Step 4: Feature suite PASS; boot `php artisan serve` and look at `/` once (eight books, authors under titles, nothing in a sidebar).**
- [ ] **Step 5: Pint, `composer ci:check` on its own, commit** `feat(web): the shelf home page, served through ListShelf and the JSON:API resource`

---

## After the tasks

1. `reviewer` (Opus) reads the whole branch against this plan, both designs, and ADR-0003 to 0008. `security-reviewer` reads the seeder, the seed migration, the public route, and the resource.
2. One fix wave, one scoped re-review.
3. The controller pushes, opens the PR, waits for CI, merges, releases v0.5.0 (version bump, changelog, roadmap `done v0.5.0`), confirms the live page shows the eight books. Ryan runs nothing.
