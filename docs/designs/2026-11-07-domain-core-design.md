# Design: the domain core (Phase 1, step 1a)

**Status:** Draft, November 7, 2026. Frozen once its plan ships.
**Roadmap item:** [Phase 1, step 1a](../roadmap.md#phase-1-catalog)
**Spec:** [The Book](../spec.md#the-book), [Depth](../spec.md#depth)
**Why the code is shaped this way:** [ADR-0003](../adr/0003-hexagonal-architecture.md) (hexagonal architecture), [ADR-0007](../adr/0007-core-beside-app-repository-layout.md) (`core/` beside `app/`)

---

## Intent

Write down what a book, a category, and a depth level *are* in plain PHP, with their rules, before anything about saving, serving, or showing them exists. Everything built later (use cases, persistence, the API, the web UI, the MCP server) leans on these classes, so their rules must be precise and their tests must be the clearest record of them.

This is the first code in `core/`. It also sets the pattern a learner will copy for every later domain concept, so clarity of naming and explanation matters as much as correctness.

## Goals

1. Every rule in the spec's [The Book](../spec.md#the-book) and [Depth](../spec.md#depth) sections is expressed in code and proven by a test in `tests/Core/`.
2. A value that would violate a rule cannot be constructed. A title can't be blank; an ISBN can't have a wrong check digit; a depth can't be 7.
3. The classes run with no framework: `php artisan test --compact --testsuite=Core` passes in milliseconds and the architecture tests stay green.

## Non-goals

- Saving, loading, listing, searching, or filtering books and categories. Those are ports and use cases (step 1b) and persistence (step 1d).
- Generating IDs or reading the clock. The core receives these values; step 1b adds the `IdGenerator` and `Clock` ports that supply them.
- Fetching book metadata (step 1g).
- Any HTTP, JSON, or UI shape (steps 1c, 1e, 1f).

## Anti-goals

- A domain model that knows about tables, columns, or JSON. Nothing here may need Laravel.
- Rules that live in two places. If a use case or an endpoint re-validates an ISBN, this design has failed.
- Clever abstractions. One concept, one class, a few obvious methods.

---

## Decisions (November 7, 2026)

Product decisions are Ryan's; technical ones are recommendations recorded here (and in the spec's Decisions Log, rows 17 to 19).

| # | Decision | Whose |
|---|---|---|
| 1 | **Two statuses stay:** on the shelf and wanted. A book may move in either direction. A book that leaves the shelf (lent, given away, sold) is removed, or moved back to wanted. A third status such as "no longer owned" is rare enough for Ryan that it goes in the spec's Later list. | Ryan |
| 2 | **Category names match ignoring case and surrounding spaces.** "Fiction", "fiction", and " Fiction " are one category. The name keeps the capitalization it was first typed with. | Ryan |
| 3 | **Authors are names on the book, in cover order,** not records of their own. "Everything by Gino Wickman" is a filter on the author name (step 1b). Author records with their own pages go in Later. | Ryan |
| 4 | **ISBNs are kept in their 13-digit form.** ISBN-10 and ISBN-13 are both accepted, with or without hyphens and spaces, and checked against their check digit. An ISBN-10 is converted to ISBN-13 on the way in, so two entries of the same book compare equal, and the stored form is what a barcode carries. | Claude |
| 5 | **IDs are UUIDs, assigned outside the domain.** `BookId` and `CategoryId` wrap a UUID string and validate its shape. Which version is generated (UUID v7, time-ordered, is the recommendation) is a step 1b decision in the `IdGenerator` port. | Claude |
| 6 | **A book carries the moment it was added.** `addedAt` is part of the book because "what did I add recently" is a catalog question. The time is passed in (step 1b takes it from the `Clock` port). Modification timestamps are persistence's business (step 1d). | Claude |
| 7 | **A book holds category IDs, not category objects.** Renaming a category then touches one object, and a book never carries a stale name. | Claude |
| 8a | **Review (Claude Desktop, November 7, 2026):** NFC normalization and Unicode whitespace apply to every text value; `CoverUrl` is `https` only; `addedAt` is kept in UTC; `ext-intl` and `ext-mbstring` are declared. One suggestion declined: renaming `setDepth` to `recordDepth`, since the spec says depth is set, not recorded. | Claude |
| 8 | **Value objects are `final readonly` classes; `Status` and `Depth` are native enums.** Entities (`Book`, `Category`) are `final` classes with named methods for each change, never public setters for everything. | Claude |

---

## The model

All classes live under `core/Domain/`, namespace `Bathys\Domain`, grouped by concept.

```
core/Domain/
  InvalidValue.php            base exception for every rejected value
  Book/
    Book.php                  entity
    BookId.php                value object
    Title.php                 value object
    Author.php                value object
    Isbn.php                  value object
    CoverUrl.php              value object
    Status.php                enum: OnShelf, Wanted
    Depth.php                 enum: levels 0 to 5
  Category/
    Category.php              entity
    CategoryId.php            value object
    CategoryName.php          value object
```

### Value objects

A value object is defined by its value: two titles with the same text are the same title. Each one validates in its constructor (or a named constructor such as `fromString`), normalizes, exposes the value, and offers `equals()`. Each throws a subclass of `InvalidValue` (which extends `InvalidArgumentException`) when the input breaks a rule. The subclasses are `InvalidId`, `InvalidTitle`, `InvalidAuthor`, `InvalidIsbn`, `InvalidCoverUrl`, and `InvalidCategoryName`, each a tiny class beside the value it protects (`InvalidId` serves both ID classes, so it sits in `core/Domain/` next to `InvalidValue`). Messages say what was wrong in plain words, because they will reach the API and the UI later.

| Class | Rule | Normalization |
|---|---|---|
| `BookId`, `CategoryId` | A UUID: 32 hex digits in the 8-4-4-4-12 layout. | Lowercase. |
| `Title` | Not empty after trimming. No length limit in the domain. | Trimmed; runs of whitespace collapsed to one space; Unicode-normalized to NFC. |
| `Author` | A name as printed on the cover. Not empty after trimming. | Trimmed; runs of whitespace collapsed; Unicode-normalized to NFC. Equality is exact (case-sensitive) after normalization. |
| `Isbn` | After removing hyphens and spaces: 10 characters (9 digits plus a digit or `X`/`x` check) with a valid mod-11 check digit, or 13 digits starting `978` or `979` with a valid mod-10 check digit. | Kept as 13 digits. An ISBN-10 becomes `978` + its first 9 digits + a recomputed check digit. |
| `CoverUrl` | An absolute `https` URL with a host. | Trimmed; an `http` URL is upgraded to `https`, which is what a browser does with a mixed-content image on an `https` page anyway. Any other scheme is rejected. |
| `CategoryName` | Not empty after trimming. | Trimmed; runs of whitespace collapsed; Unicode-normalized to NFC so a composed and a decomposed "É" are the same letter. Display keeps the typed case. `equals()` compares after case folding (`mb_convert_case` with `MB_CASE_FOLD`). |

**Whitespace means Unicode whitespace** (the `\s` class with the `u` modifier, which covers the non-breaking space and the other `\p{Z}` separators), not only the ASCII characters `trim()` strips. **NFC normalization** uses the `Normalizer` class from `ext-intl`; the project declares `ext-intl` and `ext-mbstring` in `composer.json`, and the core depends on both (they ship with the PHP builds on CI and Forge).

An absent ISBN or cover is `null` on the book, never an empty value object. The value objects reject an empty string so that "absent" has exactly one representation.

### Enums

**`Status`** is a string-backed enum: `OnShelf = 'shelf'`, `Wanted = 'wanted'`. The backing values are the words the spec's data sketch uses, so they can flow to the database and the API unchanged.

**`Depth`** is an int-backed enum with one case per level, in the spec's words:

| Case | Value | `label()` |
|---|---|---|
| `NotYetRead` | 0 | Not yet read |
| `Skimmed` | 1 | Inspected: skim |
| `ReadSuperficially` | 2 | Inspected: superficial read |
| `ReadPartAnalytically` | 3 | Analytical (partial) |
| `ReadAnalytically` | 4 | Analytical (complete) |
| `ReadSyntopically` | 5 | Syntopical |

`Depth` also has `description()` returning the spec's one-line meaning for each level, so the UI and the MCP server read it from one place. `Depth::from(7)` throws PHP's own `ValueError`; no wrapper is added, because that is the idiomatic enum behavior and a learner should see it. Adapters that take a level from user input (the API, the UI, the MCP server) call `Depth::tryFrom()` and turn `null` into a validation error, so a `ValueError` never reaches a user.

### `Book` (entity)

A book is identified by its `BookId`, not by its fields: two books can share a title. It is mutable, and every change goes through a method named for what Ryan does.

State: `id`, `title`, `authors` (a list of `Author`, in order), `isbn` (nullable), `coverUrl` (nullable), `status`, `categoryIds` (a set of `CategoryId`), `depth`, `addedAt` (`DateTimeImmutable`, converted to UTC on the way in, so the stored instant doesn't depend on the caller's time zone).

Construction: one constructor taking every field (it applies the same rules as the change methods, dropping duplicate authors and category IDs, so a book rebuilt from storage can't break a rule `Book::add` enforces), plus a named constructor `Book::add(BookId $id, Title $title, DateTimeImmutable $addedAt, ...)` for the common "type a title and go" path, which defaults status to `OnShelf`, depth to `NotYetRead`, and everything else to empty or null. Title is the only required descriptive field, exactly as the spec promises.

Changes:

| Method | Rule |
|---|---|
| `retitle(Title)` | Replaces the title. |
| `creditAuthors(Author ...$authors)` | Replaces the author list. Order kept; an author listed twice is kept once. |
| `assignIsbn(?Isbn)` | Sets or clears the ISBN. |
| `changeCover(?CoverUrl)` | Sets or clears the cover. |
| `moveToShelf()` / `moveToWishlist()` | Changes status. Nothing else about the book changes. Calling the one that matches the current status does nothing. |
| `categorize(CategoryId)` | Adds a category. Adding one already present does nothing. |
| `uncategorize(CategoryId)` | Removes a category. Removing one that isn't there does nothing. |
| `setDepth(Depth)` | Any level, any time, higher or lower. No ordering rule. Named `set` on purpose: the spec's word is "set directly", and a name like `recordDepth` would suggest the reading history the spec rules out. |

Readers: a getter per field (`id()`, `title()`, `authors()`, `isbn()`, `coverUrl()`, `status()`, `categoryIds()`, `depth()`, `addedAt()`), plus `isOnShelf()`, `isWanted()`, `isIn(CategoryId)`, and `hasIsbn()`. `authors()` and `categoryIds()` return plain lists; `categoryIds()` is ordered by insertion, with no duplicates.

### `Category` (entity)

State: `id` (`CategoryId`), `name` (`CategoryName`). One change: `rename(CategoryName)`. Reader: `id()`, `name()`, and `isNamed(CategoryName)` (case-insensitive, via `CategoryName::equals`).

**Uniqueness of names across categories is not enforced here.** It needs the whole list, so it belongs to the use case that adds or renames a category (step 1b), which compares with `CategoryName::equals`. This design records the rule; 1b enforces it.

**Removing a category takes it off its books** (spec). That is a use case too (1b): load the category's books, call `uncategorize` on each, delete the category. The domain provides the pieces.

---

## Edge cases the tests must cover

ISBN:
- `0-306-40615-2` (ISBN-10) becomes `9780306406157`; the two compare equal.
- `0-8044-2957-X` and `080442957x` (ISBN-10 with an `X` check digit) are accepted.
- `978 0 306 40615 7` with spaces is accepted.
- `4006381333931` (a valid 13-digit retail barcode that is not a `978` or `979` ISBN) is rejected with `InvalidIsbn`, so scanning the wrong barcode on a box is caught.
- A `979` ISBN-13 is accepted and stays as given.
- Wrong check digit, 12 digits, 11 digits, letters other than a trailing `X`, and an empty string are rejected with `InvalidIsbn`.
- `X` anywhere but the last position of an ISBN-10 is rejected.

Title and names:
- `"  How to  Read a Book "` becomes `"How to Read a Book"`.
- `"How to\u{00A0}Read"` (a non-breaking space) becomes `"How to Read"` with an ordinary space; a title of only non-breaking spaces is rejected.
- Whitespace-only is rejected.
- A composed and a decomposed `"É"` in a title or an author name compare equal after NFC normalization.
- `CategoryName("fiction")->equals(CategoryName("Fiction"))` is true; the first one still displays as `fiction`. A non-ASCII name (`"Économie"` versus `"économie"`) also matches.
- `Author("Gino Wickman")` does not equal `Author("gino wickman")` (author matching is exact; the UI fills names from metadata so they line up).

Book:
- `Book::add` with only an ID, a title, and a time yields on the shelf, depth 0, no authors, no ISBN, no cover, no categories.
- Moving wanted to shelf keeps title, authors, ISBN, cover, categories, and depth.
- Moving in either direction works; moving to the current status leaves the book unchanged.
- Depth can go from 4 to 1 and from 0 to 5 directly.
- A wanted book can hold a depth above 0.
- Crediting `[A, B, A]` yields `[A, B]`.
- Categorizing twice yields one entry; uncategorizing an absent category is a no-op.

IDs:
- `addedAt` given in `America/New_York` is stored as the same instant in UTC.
- Uppercase UUIDs are accepted and lowercased; a 35-character string, a non-hex string, and an empty string are rejected.

Cover URL:
- `https://covers.openlibrary.org/b/id/1-L.jpg` is accepted; `http://covers.openlibrary.org/b/id/1-L.jpg` becomes the `https` form; `ftp://…`, `covers.openlibrary.org/x.jpg` (no scheme), and `javascript:alert(1)` are rejected.

---

## Teaching notes

- Each class opens with a short comment saying what the concept is and why it is a class rather than a string (one paragraph, no more). `core/README.md` gets a line pointing at `Book` as the worked example of an entity and `Isbn` as the worked example of a value object.
- Tests are named as sentences in the user's words (`it('keeps everything else when a wanted book moves to the shelf')`), so `php artisan test --testsuite=Core` reads as a description of the rules.
- Test files mirror the class layout: `tests/Core/Domain/Book/IsbnTest.php` tests `core/Domain/Book/Isbn.php`.

## Out of scope

- Repositories, ports, use cases, in-memory doubles (1b).
- Searching and filtering by title, author, category, status, or depth (1b).
- Category name uniqueness and cascading removal (1b, see above).
- Hyphenated display of ISBNs (needs the ISBN range tables; not needed for a personal catalog).
- A "no longer owned" status and author records (Later, by Ryan's decision).

## Left to the builder

- The exact text of exception messages.
- Whether `Book::add` takes authors, ISBN, and cover as optional parameters or the use case calls the change methods after construction. Either is fine; keep the common path to one call.
- Private helper layout inside `Isbn` (check-digit functions), as long as the public surface is `Isbn::fromString()`, `toString()`, `equals()`.

## Changes to other documents

Made in the same change as this design (done November 7, 2026):

- `docs/spec.md`: Decisions Log rows 17 to 19 (leaving the shelf, category name matching, authors as names); Later gains "a no longer owned status" and "author records"; The Book notes that moving works in either direction.
- `CHANGELOG.md`: an `[Unreleased]` line for this design.
