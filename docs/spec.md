# Bathys: Product Spec

**Document Type:** Project Spec
**Status:** DRAFT (re-scoped to a catalog; ready for design and implementation planning)
**Version:** 0.6
**Last Updated:** November 7, 2026
**Owner:** Ryan Grissinger
**Related:** [Architecture Decision Records](adr/README.md)

---

## Name

**Bathys** (Greek *βαθύς*, "deep"). Each book carries one rating: how deep Ryan has read it.

The project was first written up under the placeholder name "Reading Tracker" (v0.1).

---

## Intent

In Ryan's words (November 7, 2026): "I just want a list of books that are on my bookshelf and a list of the books that I am interested in adding to my collection. We can categorize them."

Bathys is a **personal book catalog**:

- **The shelf:** the books Ryan owns.
- **The wishlist:** the books he wants.
- **Categories** he defines and edits himself.
- **One rating per book:** how deep he has read it, on Mortimer Adler's levels of reading, set directly on the book.

It is not a reading tool or a reading assistant. Earlier versions of this spec (v0.1 to v0.3) described one, with timed sessions, guided checklists, and recorded passes. Those ideas are kept under [Later](#later) so they aren't re-proposed as new.

The tool is private. It is its own project, separate from polarispixels.com.

Bathys has a second purpose: it is a **teaching codebase**. The repository is meant to be a clear, readable example of software architecture done well, which a new programmer can study. See [the ADR index](adr/README.md) for the reasoning behind the technical choices.

---

## Goals

1. List every book on Ryan's shelf, and every book he wants.
2. Move a book from the wishlist to the shelf when he gets it.
3. Group books by categories he can add, rename, and remove.
4. Record how deep he has read each book, as one level from 0 to 5.
5. Make adding a book take seconds: by hand, by title search, or by scanning its barcode.
6. Find a book quickly by title, author, category, status, or depth.

## Non-Goals

- Reading-tool features: timed sessions, checklists, recorded passes, prioritization (see [Later](#later)).
- Social features, friends, public profiles, Goodreads-style feeds.
- Reading the books inside the app (no ebook reader).
- A public recommended-reading page (possible later; see [Later](#later)).

## Anti-Goals (failure even if it technically works)

- Heavy data entry. **Adding a book must take seconds.** Title is the only required field.
- A tool that becomes a procrastination project in itself (organizing instead of reading).
- Anything that makes an unread book feel like a failure. Depth 0 is a normal state for a book on the shelf.

---

## Where It Is Used

| Where | Device | What happens there |
|---|---|---|
| At the bookshelf or in a bookstore | Phone | Adding books (barcode scan, title search), checking whether a book is already owned or wanted |
| At a desk | Desktop | Managing categories, editing several books, browsing |
| On the couch | Tablet or phone | Browsing the shelf and wishlist, updating depth |

**Adding a book and browsing are mobile-first.** Category management can lean on desktop but must still work on a phone.

---

## The Book

| Field | Required | Notes |
|---|---|---|
| Title | Yes | The only required field, so a hand-typed book takes seconds |
| Authors | No | One or more |
| ISBN | No | ISBN-10 or ISBN-13, validated and normalized; filled in by title search or barcode scan |
| Cover | No | An image URL, usually from the metadata lookup |
| Status | Yes | **On the shelf** or **Wanted**. Defaults to on the shelf. |
| Categories | No | Zero or more, from Ryan's editable list |
| Depth | Yes | Level 0 to 5 (below). Defaults to 0. |

**Categories** are an editable list, never hardcoded. Ryan can add, rename, and remove them. Removing a category takes it off its books; it never removes the books.

**Moving a book** between Wanted and On the shelf, in either direction, keeps everything else about it. A book that leaves the shelf (lent, given away, sold) is removed or moved back to Wanted; this is rare for Ryan, so a separate "no longer owned" status is deferred (see [Later](#later)).

**Authors** are names as printed on the cover, in cover order. "Everything by this author" is a filter on the name; authors are not records of their own (see [Later](#later)).

**Category names** match ignoring case and surrounding spaces: "Fiction", "fiction", and " Fiction " are one category, shown with the capitalization it was first typed with.

---

## Depth

Each book has one **depth**: how deep Ryan has read it, on the levels from Mortimer Adler's *How to Read a Book*. It is the book's only rating.

| Level | Name | What it means |
|---|---|---|
| 0 | Not yet read | Known to the catalog, not yet touched |
| 1 | Inspected: skim | Adler's systematic skim: title, preface, contents, index, a few key chapters, the last pages |
| 2 | Inspected: superficial read | Read straight through without stopping on hard parts |
| 3 | Analytical (partial) | Careful reading of part of the book |
| 4 | Analytical (complete) | Careful reading of the whole book |
| 5 | Syntopical | Compared with other books on a shared question |

Rules:

- **Set directly.** Ryan picks the level on the book. There is no history of reading events behind it.
- **Any level, any time.** Real reading doesn't climb in order: he might read one chapter carefully before ever skimming a book. Any level can be set at any time, higher or lower.
- **Not tracked by section.** Level 3 is "careful reading of part of the book"; which part isn't recorded.
- **Any status.** A wanted book can have a depth too, for example one read from a library.
- **Format doesn't change it.** Physical, ebook, and audio books all use the same levels.

---

## Adding Books

Adding a book must take seconds. Version 1 has three ways in:

1. **By hand.** Type a title (and optionally authors). Nothing else is required.
2. **Title search.** Type part of a title or author, pick a result, and the authors, ISBN, and cover fill in.
3. **ISBN barcode scan.** Scan the barcode with the phone camera; the book's details are looked up by ISBN.

Book metadata comes from a public source, Open Library first, behind a port so the source can change ([ADR-0002](adr/0002-put-portability-where-change-is-likely.md)).

Adding books **through Claude by conversation** comes in Phase 2, with the MCP server. **Shelf-photo import** is deferred ([Later](#later)).

**Starting inventory:** there is nothing to import. The shelf starts empty.

---

## Architecture and Stack

The reasoning for each choice is recorded as an Architecture Decision Record in [`docs/adr/`](adr/README.md).

**Stack**

- **Backend:** Laravel 13 on PHP 8.4 ([ADR-0001](adr/0001-stay-on-laravel-13.md))
- **Database:** Postgres
- **Frontend:** Inertia with React and TypeScript, styled with Tailwind ([ADR-0006](adr/0006-react-with-typescript-through-inertia.md))
- **Hosting:** Laravel Forge at `bathys.polarispixels.com` ([ADR-0010](adr/0010-host-on-a-polarispixels-subdomain.md))
- **Authentication:** single user (Ryan), passkey sign-in, no public registration
- **Developer tooling:** Laravel Boost, and the tools in [`docs/ai-tooling.md`](ai-tooling.md)

**Architecture**

- **Hexagonal architecture (ports and adapters)** with a framework-independent domain core ([ADR-0003](adr/0003-hexagonal-architecture.md)). Abstraction is concentrated where change is likely ([ADR-0002](adr/0002-put-portability-where-change-is-likely.md)).
- **Eloquent behind repository interfaces**: domain rules in plain PHP classes, persistence in adapters ([ADR-0004](adr/0004-pragmatic-persistence.md)).
- **JSON:API endpoints described by an OpenAPI contract written before the code**, with tests that verify the application matches it ([ADR-0005](adr/0005-spec-driven-design-with-an-api-contract.md)).
- **Repository layout:** the domain core lives in `core/` beside Laravel's `app/`, and every file in `app/` is an adapter ([ADR-0007](adr/0007-core-beside-app-repository-layout.md)).
- **The web UI shares the API contract** ([ADR-0008](adr/0008-web-ui-shares-the-api-contract.md)).
- **An MCP server** (Phase 2) so Claude can add and update books by conversation. It runs inside the Laravel app as a driving adapter ([ADR-0009](adr/0009-mcp-server-inside-laravel.md)).

---

## Rough Data Model (sketch for the builder)

A persistence sketch. The domain model is designed first, in plain PHP, and the tables follow from it, so names may change.

- **books**: title, authors, ISBN, cover URL, status (`shelf` or `wanted`), depth (0 to 5), timestamps
- **categories**: name (unique)
- **book_category**: the pivot between them

---

## Suggested Phasing

1. **Catalog.** Add books by hand, by title search, and by barcode scan; the shelf and the wishlist; categories; depth; finding books. Ends with 1.0.0 in daily use.
2. **MCP.** An MCP server inside the app, so Claude can add and update books by conversation.

Everything else is in [Later](#later).

---

## Later

Not scheduled. Recorded so they aren't re-proposed as new. Each would need Ryan's decision and a spec update before work starts.

**From the reading-tool design (v0.1 to v0.3):**

- **Passes:** records of individual reading events, with depth derived from them
- **Live reading sessions:** a timed block against one book at one level
- **Guided checklists** per level (for example, Adler's skimming steps one at a time)
- **Focus aids:** an end-of-session chime, one step at a time, a "where was I" prompt, dim mode
- **Captures and verdicts:** end-of-session notes, and "go deeper / park it / let it go"
- **Sections:** chapters tracked per book. Ryan never intended this (see the Decisions Log, row 16).
- **A next-up picker** that suggests what to read next
- **Facets beyond category:** genre, reading level, subject depth and breadth, ownership and format, source
- **Book relationships:** links such as "responds to", "builds on", "contradicts"
- **Questions and syntopical work:** books attached to questions Ryan wants to understand, with suggested comparisons

**From the domain core design (step 1a):**

- **A "no longer owned" status** for books that were on the shelf and left it (lent, given away, sold). Rare for Ryan; for now such a book is removed or moved back to Wanted.
- **Author records** with their own pages, so one author's books can be browsed and a misspelled name fixed in one place. For now authors are names on the book, and "everything by this author" is a filter on the name.

**Research and buying:**

- **Review summaries** written by Claude through MCP
- **Related-book discovery** that adds wishlist items with a noted source
- **Purchase and borrow links** (Amazon, Half Price Books, Bookshop.org, a library or Libby lookup)

**Ways in and integrations:**

- **Shelf-photo import** (AI vision reads the spines), which would need an AI provider ADR and a queue worker ([#9](https://github.com/polarispixels/bathys/issues/9))
- **Kindle highlight import**
- **StoryBank and COMPASS integrations**, and mapping questions to workshop or consulting topics
- **A public recommended-reading page** fed from Bathys data
- **A native phone app**, consuming the same API contract

---

## Decisions Log

Questions 1 to 8 come from v0.1, 9 to 12 from review of v0.2, 13 to 16 from the re-scope, 17 to 19 from the domain core design (step 1a), and 20 from the shelf slice. All were answered on November 7, 2026. Rows marked *superseded* record what was decided at the time; the row named in the decision replaces them.

| # | Question | Decision |
|---|---|---|
| 1 | Where and how is it used? | *Superseded by 13 for the session screen.* Phone at the bookshelf, desktop, and tablet; adding and browsing are mobile-first. See [Where It Is Used](#where-it-is-used). |
| 2 | How do books get in? | *Superseded by 15.* Originally all four: shelf photo, ISBN barcode scan, title search, and Claude through MCP. |
| 3 | Starting inventory? | Nothing to import. (Originally "shelf photos will be the first import"; shelf photos are now deferred, see 15.) |
| 4 | Audiobooks and Kindle? | Physical, ebook, and audio books use the same depth levels. Kindle highlight import is Later. |
| 5 | Focus aids? | *Superseded by 13.* Originally a chime, one checklist step at a time, a "where was I" prompt, and dim mode; all Later. |
| 6 | Connection to existing systems? | StoryBank, COMPASS, and work-topic integrations are Later. |
| 7 | Name and domain? | Name: **Bathys**. Domain: **`bathys.polarispixels.com`** ([ADR-0010](adr/0010-host-on-a-polarispixels-subdomain.md)). |
| 8 | Stack confirmation? | Laravel 13, Postgres, Inertia with React and TypeScript, Tailwind, Forge, passkey auth, hexagonal architecture, JSON:API with an OpenAPI contract, MCP server, Laravel Boost. See [Architecture and Stack](#architecture-and-stack). |
| 9 | Must books climb the ladder in order? | No. Any level, any time. Still true under 14, without passes: the level is set directly. |
| 10 | When does shelf-photo import ship? | *Superseded by 15.* Deferred to Later. |
| 11 | Does the Inertia web UI use the API contract? | Yes, through shared JSON:API resources and generated TypeScript types ([ADR-0008](adr/0008-web-ui-shares-the-api-contract.md)). |
| 12 | Where does the MCP server live? | Inside the Laravel app, as a driving adapter ([ADR-0009](adr/0009-mcp-server-inside-laravel.md)). |
| 13 | What is Bathys? | **A personal book catalog, not a reading tool:** the shelf, the wishlist, categories, and one rating per book. In Ryan's words: "I just want a list of books that are on my bookshelf and a list of the books that I am interested in adding to my collection. We can categorize them." Reading-tool features move to [Later](#later). |
| 14 | What does a book's one rating measure? | **How deep Ryan has read it:** the Adler depth level, 0 to 5, set directly on the book. See [Depth](#depth). |
| 15 | Which ways of adding a book are in version 1? | **By hand, title search, and ISBN barcode scan.** Shelf-photo import is Later; adding through Claude comes with the MCP server in Phase 2. |
| 16 | Can covering every section raise a book's level? | **No, and books are not tracked by section at all.** Ryan never intended to break books into sections, so the question is moot. |
| 17 | What happens to a book that leaves the shelf (lent, given away, sold)? | **Remove it, or move it back to Wanted.** It's rare for Ryan. A separate "no longer owned" status is in [Later](#later). |
| 18 | Are "Fiction", "fiction", and " Fiction " the same category? | **Yes.** Category names match ignoring case and surrounding spaces, and keep the capitalization first typed. |
| 19 | Are authors their own records? | **No, names on the book, in cover order.** Browsing everything by one author is a filter on the name. Author records are in [Later](#later). |
| 20 | Is the home screen public? | **Yes, until sign-in exists** (step 1e). The shelf at `/` is readable by anyone; adding and editing wait for sign-in. |

## Open Questions

None at the moment. New questions are added here as they come up.

---

## Next Step

Phase 1, step 1a: the domain core (Book, Status, Category, Depth, and their value objects), test-first. See [`roadmap.md`](roadmap.md).

---

## Version History

**v0.6 (November 7, 2026)**
- Decision 20: the shelf at `/` is public until sign-in exists (step 1e).

**v0.5 (November 7, 2026)**
- Decisions 17 to 19 from the domain core design ([`designs/2026-11-07-domain-core-design.md`](designs/2026-11-07-domain-core-design.md)): a book that leaves the shelf is removed or moved back to Wanted; category names match ignoring case and surrounding spaces; authors are names on the book. The Book section states all three. Later gains a "no longer owned" status and author records.

**v0.4 (November 7, 2026)**
- Re-scoped from a reading tool to a personal book catalog (Decisions Log 13 to 16): the shelf, the wishlist, editable categories, and one rating per book, the Adler depth level set directly.
- Removed passes, live sessions, checklists, focus aids, captures and verdicts, sections, the next-up picker, facets beyond category, relationships, questions, syntopical work, review summaries, discovery, purchase links, and shelf-photo import from version 1. All are listed under Later.
- Version 1 adds books by hand, by title search, and by ISBN barcode scan.
- New sections: The Book, Depth, Later. Rewritten: Intent, Goals, Non-Goals, Anti-Goals, Where It Is Used, Adding Books, Rough Data Model, Suggested Phasing. PHP 8.3+ corrected to 8.4.

**v0.3 (November 7, 2026)**
- Ladder rule changed after review: any level can be recorded at any time, after the fact.
- Introduced the **pass** as distinct from the **session**; depth derived from passes.
- Shelf-photo import, title search, and metadata lookup moved into Phase 1.
- Recorded decisions on repository layout, the web UI and the API contract, and MCP placement (ADR-0007 to ADR-0009).

**v0.2 (November 7, 2026)**
- Renamed from "Reading Tracker" to **Bathys**.
- Recorded answers to all v0.1 open questions in a Decisions Log.
- Added sections: Where It Is Used, Adding Books, Focus Aids, Architecture and Stack, Later Phases.
- Confirmed the stack and the hexagonal architecture; linked to ADR-0001 through ADR-0006.
- Noted the teaching-codebase purpose in Intent.

**v0.1 (November 7, 2026)**
- Preliminary spec captured from a brainstorming session in Claude Code. Intent, depth ladder, facets, and phasing agreed in conversation.
