# ADR-0004: Pragmatic Persistence (Active Record Behind Repositories)

- **Status:** Accepted
- **Date:** November 7, 2026
- **Decided by:** Ryan Grissinger
- **Related:** [ADR-0002](0002-put-portability-where-change-is-likely.md), [ADR-0003](0003-hexagonal-architecture.md)

## Context

Eloquent, Laravel's ORM, uses the **Active Record** pattern, where the model object and the database row are the same thing. That pulls against a pure domain core. The alternative is the **Data Mapper** pattern (Doctrine is the main PHP example), which keeps domain objects and database persistence separate at the cost of more code.

## Decision

Be pragmatic. Keep the domain rules in pure classes, and put Eloquent behind repository interfaces.

## Alternatives Considered

- *Pure Data Mapper with Doctrine.* Cleanest separation, but heavier, less familiar, and against the grain of the Laravel ecosystem.
- *Eloquent everywhere.* Simplest, but domain rules leak into database models and become hard to test in isolation.

## Consequences

This tradeoff is taught openly rather than hidden. The repository includes this decision record so readers can see that architecture is a series of judgment calls, not a set of rules applied blindly.
