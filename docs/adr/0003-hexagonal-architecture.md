# ADR-0003: Hexagonal Architecture (Ports and Adapters)

- **Status:** Accepted
- **Date:** November 7, 2026
- **Decided by:** Ryan Grissinger
- **Related:** [ADR-0002](0002-put-portability-where-change-is-likely.md), [ADR-0004](0004-pragmatic-persistence.md), [ADR-0005](0005-spec-driven-design-with-an-api-contract.md)

## Context

The application needs a structure that teaches separation of concerns, supports testing, and makes the likely swaps in [ADR-0002](0002-put-portability-where-change-is-likely.md) cheap.

## Decision

Use hexagonal architecture, also called ports and adapters.

## How It Works

- **The core** holds the real ideas of the application in plain PHP with no framework imports: Book, Author, DepthLevel, Session, and the rules, such as how a book climbs the depth ladder and how the next session gets picked.
- **Ports** are interfaces the core defines for what it needs from the outside world, such as a `BookRepository`, a `MetadataLookup`, and a `Clock`.
- **Adapters** are the outside pieces that plug into those ports: Laravel, Postgres, Open Library, the web UI, and the MCP server.

## Consequences

- The depth ladder and prioritization logic can be tested in milliseconds with no database.
- Swapping Open Library for Google Books means writing one new adapter class.
- The core can be read and understood on its own, which makes it a good teaching artifact.
- There is more structure than a typical Laravel app, so the folder layout and naming need to explain themselves.
