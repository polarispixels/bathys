# ADR-0006: React with TypeScript Through Inertia

- **Status:** Accepted
- **Date:** November 7, 2026
- **Decided by:** Ryan Grissinger
- **Related:** [ADR-0001](0001-stay-on-laravel-13.md), [ADR-0005](0005-spec-driven-design-with-an-api-contract.md)

## Context

The frontend choice affects both the user experience and what the codebase teaches.

## Decision

Use React with TypeScript through Inertia, one of Laravel's official starter kit options.

## Reasoning

- React and TypeScript are among the most transferable frontend skills in the industry.
- Having PHP on one side and TypeScript on the other makes the boundary between layers visible in a way a single-language app does not.

## Alternatives Considered

A TypeScript-end-to-end stack was the main alternative to the overall direction; it is recorded in [ADR-0001](0001-stay-on-laravel-13.md#typescript-end-to-end).

## Consequences

Two languages in one repository. That is a small cost in complexity and a real benefit in showing where the seams are.
