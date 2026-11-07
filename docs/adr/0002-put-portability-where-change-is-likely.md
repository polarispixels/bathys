# ADR-0002: Put Portability Where Change Is Likely

- **Status:** Accepted
- **Date:** November 7, 2026
- **Decided by:** Ryan Grissinger
- **Related:** [ADR-0003](0003-hexagonal-architecture.md), [ADR-0004](0004-pragmatic-persistence.md)

## Context

A common example of abstraction is designing a system so the database can be swapped, for instance MySQL for Postgres. In practice, teams almost never change the production database of a running system. Designing hard for that swap is a classic case of over-abstraction.

## Decision

Concentrate abstraction at the boundaries most likely to change, and keep the abstraction thin where change is unlikely.

## Reasoning

For Bathys the likely changes are concrete:

| Boundary | Likely change |
|---|---|
| Book metadata | Open Library today, Google Books or another source tomorrow |
| AI provider | Models and providers change often |
| User interface | Web now, possibly a native phone app later |
| How Claude talks to it | REST and MCP, with protocols still evolving |
| Database | Least likely to change, so it gets the thinnest abstraction |

## Consequences

This is the most useful lesson for a new programmer: portability is not free, so spend it where it pays off. Every abstraction adds code and indirection. The skill is choosing where.
