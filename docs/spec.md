# Bathys: Product Spec

**Document Type:** Project Spec
**Status:** DRAFT (open questions resolved; ready for design and implementation planning)
**Version:** 0.3
**Last Updated:** November 7, 2026
**Owner:** Ryan Grissinger
**Related:** [Architecture Decision Records](adr/README.md)

---

## Name

**Bathys** (Greek *βαθύς*, "deep"). The name says what the tool measures: how deep you have gone into a book, not whether you finished it.

The project was first written up under the placeholder name "Reading Tracker" (v0.1).

---

## Intent

Ryan owns, wants, or has partly read a large and growing set of books. Long reading sessions rarely work for him: his mind wanders, he falls asleep, and finishing a book cover to cover is rare. Standard trackers measure "books finished," so they score most of his reading as failure.

Bathys measures **depth reached**, not completion. It borrows the four levels from Mortimer Adler's *How to Read a Book* (elementary, inspectional, analytical, syntopical) and turns them into a progressive ladder. Every book on the shelf can get a 5 to 15 minute inspectional pass. The tool then helps him decide which books have earned a deeper read, and how books relate to each other on the questions he cares about.

The tool is mostly private. It is its own project, separate from polarispixels.com. A public "recommended reading" page could come later, fed from this data, but it is not part of this spec.

Bathys has a second purpose: it is a **teaching codebase**. The repository is meant to be a clear, readable example of software architecture done well, which a new programmer can study. See [the ADR index](adr/README.md) for the reasoning behind the technical choices.

---

## Goals

1. Track every book Ryan owns, wants, or has touched, with ownership status and format.
2. Track reading depth per book on an Adler-based ladder, with partial analytical progress recorded by chapter or section.
3. Make short sessions the default unit of work, and make each one count.
4. Capture a few structured notes at the end of each pass (the digital version of underlining) so the tool has something to prioritize with.
5. Describe each book with independent **facets**: category, genre, reading level, depth, breadth, plus relationships to other books.
6. Suggest what to read next and at what depth.
7. Help discover related material and research reviews (mostly through Claude via MCP).
8. Provide simple purchase or borrow links (Amazon, Half Price Books, others).

## Non-Goals

- Social features, friends, public profiles, Goodreads-style feeds.
- Reading the books inside the app (no ebook reader).
- A public recommended-reading page (possible later phase).
- Integrations with StoryBank, COMPASS, or workshop and consulting topics in v1 (nice-to-have, later phase; see [Later Phases](#later-phases)).

## Anti-Goals (failure even if it technically works)

- Anything that makes not finishing a book feel like failure. No "books completed" counter as a headline metric, no streak guilt.
- Heavy data entry before reading can start. Adding a book must take seconds.
- A tool that becomes a procrastination project in itself (organizing instead of reading).

---

## Where It Is Used

Bathys is used in four places, and each one shapes a different part of the interface:

| Where | Device | What happens there |
|---|---|---|
| At the bookshelf | Phone | Adding books (shelf photo, barcode scan), starting a quick inspection |
| In bed | Phone | Sessions, often in low light, often short |
| At a desk | Desktop | Shelf management, facets, relationships, reviewing notes |
| On the couch | Tablet | Sessions and light shelf browsing |

Design consequences:

- **The session screen is mobile-first.** It is designed for a phone held in one hand, then scaled up.
- **Shelf management can lean on desktop.** Bulk editing, facet management, and relationship work may assume a larger screen, but must still work on a phone.
- **Capture happens on the device in hand.** The end-of-session form must be fast to complete on a phone.

---

## Adding Books

Adding a book must take seconds. Bathys supports four ways in:

1. **Shelf photo.** Photograph a shelf; AI vision reads the spines and proposes books. Ryan confirms or corrects the list before anything is added. This is the expected **first import**.
2. **ISBN barcode scan.** Use the phone camera to scan a barcode; metadata is looked up by ISBN.
3. **Title search.** Type part of a title or author; pick from metadata search results.
4. **Through Claude via MCP.** "Add *Thinking in Systems* to my shelf, I own the paperback." (See [Discovery, Reviews, and Purchasing](#discovery-reviews-and-purchasing).)

**Starting inventory:** there is no existing list to import (no Goodreads, Kindle library, or spreadsheet). The shelf starts empty and shelf photos fill it.

---

## The Depth Ladder

Each book has one **depth**: the deepest level of reading ever recorded for it.

| Level | Name | What it means | Typical time |
|---|---|---|---|
| 0 | Shelved | Known to the system, not yet touched | 0 |
| 1 | Inspected: skim | Adler's systematic skimming: title, subtitle, preface, table of contents, index, jacket copy, a few pivotal chapters, the last pages | 5 to 15 min |
| 2 | Inspected: superficial read | Read straight through without stopping to puzzle over hard parts | Hours, spread over sessions |
| 3 | Analytical (partial) | Careful read of selected chapters or sections | Per section |
| 4 | Analytical (complete) | Careful read of the whole book | Many sessions |
| 5 | Syntopical | Book has been used in a comparison with other books on a shared question | Per question |

Level 1 is the most important. A full shelf at level 1 is a real accomplishment and the main early win.

Analytical progress is tracked per chapter or section, so "read chapters 2, 5 and 7 carefully" is a first-class state, not "partially read."

### Any level, any time, after the fact

Real reading does not climb the ladder in order. Ryan might read one chapter carefully before ever skimming the book, flip through the index of a book he finished years ago, or mention on Tuesday what he read on Sunday night. Bathys records what happened. It does not enforce an order.

- **Any level can be recorded for any book at any time**, including levels above the book's current depth and readings that happened in the past.
- **Depth is a high-water mark.** A book's depth is the highest level among its recorded passes (see [Passes and Sessions](#passes-and-sessions)). Reading at a shallower level later never lowers it.
- **Lower levels are not assumed.** Recording level 3 does not invent a level 1 pass. The history shows exactly what was reported.
- **Mistakes are cheap to fix.** Editing or deleting a pass recalculates the book's depth.

**Format does not change the ladder.** Physical books, ebooks, and audiobooks all climb the same ladder in the same way. Format is a facet of a book, not a different kind of reading. (A level 1 skim of an audiobook will look different in practice, for example listening to the introduction and the final chapter, but it is still level 1.)

---

## Passes and Sessions

A **pass** is a record that some reading happened: one book, one level, when it happened, and optionally which sections were covered and a capture (below). Passes are the only thing that sets a book's depth.

There are two ways to record a pass:

1. **Report it after the fact.** Pick a book, a level, and when it happened (defaults to today); optionally add sections and a capture. This must take seconds.
2. **Run a live session.** A session is a short, timed block (default 10 minutes, adjustable) against one book at one level. Finishing a session records a pass. An abandoned session records nothing, and nothing is lost by abandoning one.

Each level gets a guided checklist for live sessions. The level 1 checklist walks through Adler's skimming steps one at a time so a wandering mind has a next step to return to.

Whichever way a pass is recorded, a short capture form asks for:

- What kind of book is this? (practical or theoretical, subject)
- What is it about, in one sentence?
- How is it structured? (major parts)
- What problem is the author trying to solve?
- Verdict: **go deeper**, **park it**, or **let it go**.
- Optional free-form notes and quotes (with page numbers).

Fields should be optional and fast. A pass with only a level and a verdict still counts.

### Focus Aids

The session screen helps a wandering mind come back. These are the defaults:

| Aid | Behavior |
|---|---|
| End-of-session chime | A gentle chime when the timer ends. No alarm, no countdown pressure. |
| One step at a time | The checklist shows only the current step, with the rest collapsed. |
| "Where was I" prompt | Reopening the app during or after an unfinished session shows the book, the level, and the step you were on, with one tap to resume. |
| Dim / low-light mode | A dark, low-brightness theme for reading in bed. |

Read-aloud was considered and is not a default.

---

## Facets

Facets are independent tags. A book can carry several values per facet where it makes sense.

| Facet | Notes |
|---|---|
| Category | Broad subject (business, technology, history, philosophy, etc.) |
| Genre | Form (practitioner guide, memoir, textbook, narrative nonfiction, fiction, etc.) |
| Reading level | Difficulty of the prose and ideas |
| Depth | How deep the book goes into its subject |
| Breadth | How wide a range of subjects it covers |
| Ownership | Own physical, own ebook, own audiobook, library, borrowed, don't own, wishlist |
| Format | Physical, ebook (Kindle, PDF, EPUB), audio |
| Source | How it reached the list (recommended by a person, cited in another book, podcast, etc.) |

Category and genre values should be editable lists, not hardcoded.

Format groups into three families (physical, ebook, audio). The families matter for display and filtering; none of them affects the depth ladder.

---

## Relationships Between Books

Two kinds of relationships:

1. **Book to book links**, with a type: responds to, builds on, prerequisite for, contradicts, cited by, recommended in.
2. **Questions** (syntopical topics). A question is something Ryan wants to understand, such as "How do small companies build durable operations?" Books attach to questions. Once two or more books on a question reach level 1, the tool can suggest a syntopical comparison.

Questions are the bridge between the shelf and Ryan's actual work (consulting, writing, the workshop).

---

## Prioritization

The tool suggests the next session. Initial heuristics (to refine in use):

- Books at level 0 that Ryan owns come first, because inspection is cheap and owned books need no purchase.
- Books marked **go deeper** move up for level 2 or 3 work.
- Questions with several inspected books surface as syntopical candidates.
- Manual pinning and a simple priority field override the heuristics.

---

## Discovery, Reviews, and Purchasing

These lean on Claude through an MCP server rather than custom in-app research pipelines.

- **Add by conversation:** "Add *Thinking in Systems* to my shelf, I own the paperback." Claude looks up metadata (ISBN, author, page count, cover) and creates the record.
- **Review research:** Claude summarizes critical reception and stores a short review summary on the book.
- **Discovery:** Claude suggests related books for a book or a question, added as wishlist items with a source noted.
- **Purchase and borrow links:** generated from ISBN or title for Amazon, Half Price Books, and other retailers (Bookshop.org and a library or Libby lookup are candidates). Exact URL formats need verification during the build.

Book metadata lookup should use a public source such as Open Library or Google Books by ISBN.

---

## Architecture and Stack

The stack is confirmed (it was Claude's unconfirmed proposal in v0.1). The reasoning for each choice is recorded as an Architecture Decision Record in [`docs/adr/`](adr/README.md).

**Stack**

- **Backend:** Laravel 13 on PHP 8.3+ ([ADR-0001](adr/0001-stay-on-laravel-13.md))
- **Database:** Postgres
- **Frontend:** Inertia with React and TypeScript, styled with Tailwind ([ADR-0006](adr/0006-react-with-typescript-through-inertia.md)). This replaces Alpine from the v0.1 proposal.
- **Hosting:** Laravel Forge, matching polaris-pixels and indigo-nebula
- **Authentication:** single user (Ryan), passkey sign-in
- **Developer tooling:** Laravel Boost, for AI-assisted development

**Architecture**

- **Hexagonal architecture (ports and adapters)** with a framework-independent domain core ([ADR-0003](adr/0003-hexagonal-architecture.md)). Abstraction is concentrated where change is likely ([ADR-0002](adr/0002-put-portability-where-change-is-likely.md)).
- **Eloquent behind repository interfaces**: domain rules in plain PHP classes, persistence in adapters ([ADR-0004](adr/0004-pragmatic-persistence.md)).
- **JSON:API endpoints described by an OpenAPI contract written before the code**, with tests that verify the application matches it ([ADR-0005](adr/0005-spec-driven-design-with-an-api-contract.md)).
- **Repository layout:** the domain core lives in `core/` beside Laravel's `app/`, and every file in `app/` is an adapter ([ADR-0007](adr/0007-core-beside-app-repository-layout.md)).
- **The web UI shares the API contract:** Inertia pages receive data serialized by the same JSON:API resources, and TypeScript types are generated from the OpenAPI document ([ADR-0008](adr/0008-web-ui-shares-the-api-contract.md)).
- **An MCP server** so Claude can add books, research reviews, and suggest related reading. It runs inside the Laravel app as a driving adapter that calls the same use cases as the web and API, and it keeps the dual-transport approach (stdio and Streamable HTTP) of `polaris-pixels/mcp-server/` and `indigo-nebula/mcp-server/` ([ADR-0009](adr/0009-mcp-server-inside-laravel.md)).

The app stays simple: the shelf, the ladder, sessions, notes, facets, and relationships. Claude does the research work through MCP.

---

## Rough Data Model (sketch for the builder)

This is a persistence sketch. Under the hexagonal architecture the domain model is designed first, in plain PHP, and the tables follow from it, so names and shapes here may change.

- **books**: title, subtitle, authors, ISBN, cover, page count, publication year, description, priority, verdict, review summary. (Depth is derived from passes; if it is stored here, it is only a cached copy.)
- **book_copies** or ownership fields: ownership status and format (a book can be owned in more than one format)
- **sections**: chapters or parts per book
- **passes**: book, level, occurred on (when the reading happened), recorded at (when it was entered), sections covered, capture fields, notes, verdict, and the session it came from, if any
- **reading_sessions**: book, level, started at, planned duration, current checklist step, ended at. (Named `reading_sessions`, not `sessions`, because Laravel uses a `sessions` table for HTTP sessions.)
- **facets** and **facet_values**, with a pivot to books
- **book_links**: from book, to book, link type
- **questions**, with a pivot to books
- **links**: retailer or library URLs per book

---

## Suggested Phasing

1. **Shelf and ladder.** Add books fast (title search with metadata lookup, ISBN barcode scan, and shelf-photo import last), set ownership and facets, report passes at any level after the fact, run timed level 1 sessions with the skimming checklist and focus aids, capture notes and verdicts, see a "next up" list.
2. **MCP and research.** MCP server, add by conversation, review summaries.
3. **Relationships and discovery.** Questions, book links, syntopical suggestions, related-book discovery, purchase and borrow links.

### Later Phases

Not scheduled; recorded so they are not forgotten.

- **Kindle highlight import** as notes on a book.
- **Integrations** with StoryBank and COMPASS (notes and quotes flowing out), and mapping questions to workshop or consulting topics.
- **Public recommended-reading page** fed from Bathys data.
- **Native phone app**, consuming the same API contract.

---

## Decisions Log

All questions below were answered on November 7, 2026. Questions 1 to 8 come from v0.1; 9 to 12 came up in review of v0.2.

| # | Question (v0.1) | Decision |
|---|---|---|
| 1 | Where and how is it used? | Phone at the bookshelf, phone in bed, desktop, and tablet. Session screen is mobile-first; shelf management can lean on desktop. See [Where It Is Used](#where-it-is-used). |
| 2 | How do books get in? | All four: shelf photo read by AI vision, ISBN barcode scan, title search, and adding via Claude through MCP. See [Adding Books](#adding-books). |
| 3 | Starting inventory? | Nothing to import. Shelf photos will be the first import. |
| 4 | Audiobooks and Kindle? | Physical, ebook, and audio all climb the same ladder; format is just a facet. Kindle highlight import is a later phase. |
| 5 | Focus aids? | Gentle end-of-session chime, one checklist step at a time, "where was I" prompt on reopen, and dim/low-light mode. See [Focus Aids](#focus-aids). |
| 6 | Connection to existing systems? | StoryBank, COMPASS, and work-topic integrations are nice-to-have, later phase, not v1. |
| 7 | Name and domain? | Name: **Bathys**. Domain: still open. |
| 8 | Stack confirmation? | Confirmed, with changes: Laravel 13, Postgres, Inertia with React and TypeScript, Tailwind, Forge, passkey auth, hexagonal architecture, JSON:API with an OpenAPI contract, MCP server, Laravel Boost. See [Architecture and Stack](#architecture-and-stack). |
| 9 | Must books climb the ladder in order? | No. Any level can be recorded at any time, after the fact; depth is the highest level recorded. See [Any level, any time, after the fact](#any-level-any-time-after-the-fact). |
| 10 | When does shelf-photo import ship? | End of Phase 1, after title search and barcode scan, since it is the expected first import. |
| 11 | Does the Inertia web UI use the API contract? | Yes, through shared JSON:API resources and generated TypeScript types. [ADR-0008](adr/0008-web-ui-shares-the-api-contract.md). |
| 12 | Where does the MCP server live? | Inside the Laravel app, as a driving adapter. [ADR-0009](adr/0009-mcp-server-inside-laravel.md). |

## Open Questions

1. **Domain name** for the deployed app.

---

## Next Step

Scaffold the Laravel app per [ADR-0007](adr/0007-core-beside-app-repository-layout.md), build the domain core test-first, then write the first version of the OpenAPI contract and review it before any endpoint code.

---

## Version History

**v0.3 (November 7, 2026)**
- Ladder rule changed after review: any level can be recorded at any time, after the fact. "Levels are cumulative" replaced by "depth is the highest level recorded." New subsection: Any level, any time, after the fact.
- Introduced the **pass** (a record that reading happened) as distinct from the **session** (a live, timed tool that records a pass). "Sessions" section renamed to "Passes and Sessions." Data model gains a `passes` table; depth is derived from passes.
- Shelf-photo import, title search, and metadata lookup moved into Phase 1.
- Recorded decisions on repository layout, the web UI and the API contract, and MCP placement (ADR-0007 to ADR-0009). Open question on shelf-photo timing resolved.

**v0.2 (November 7, 2026)**
- Renamed from "Reading Tracker" to **Bathys**.
- Recorded answers to all v0.1 open questions in a Decisions Log; removed the resolved questions. Domain name and the timing of shelf-photo import remain open.
- Added sections: Where It Is Used, Adding Books, Focus Aids, Architecture and Stack, Later Phases.
- Confirmed the stack (Laravel 13, Postgres, Inertia + React + TypeScript, Tailwind, Forge, passkeys, Laravel Boost) and the hexagonal architecture; linked to ADR-0001 through ADR-0006. Alpine dropped in favor of React.
- Noted the teaching-codebase purpose in Intent.
- Clarified that format does not affect the depth ladder, and grouped formats into physical, ebook, and audio.
- Renamed the `sessions` table to `reading_sessions` in the data model sketch to avoid a clash with Laravel's HTTP session table.

**v0.1 (November 7, 2026)**
- Preliminary spec captured from a brainstorming session in Claude Code. Intent, depth ladder, facets, and phasing agreed in conversation. Stack and data model are Claude's proposals pending Ryan's review.
