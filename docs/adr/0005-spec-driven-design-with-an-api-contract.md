# ADR-0005: Spec-Driven Design with an API Contract

- **Status:** Accepted
- **Date:** November 7, 2026
- **Decided by:** Ryan Grissinger
- **Related:** [ADR-0001](0001-stay-on-laravel-13.md) (first-party JSON:API support), [ADR-0003](0003-hexagonal-architecture.md)

## Context

Bathys will have several consumers: the web frontend, the MCP server Claude uses, and possibly a phone app later.

## Decision

Make the API contract the source of truth. Write an OpenAPI document describing the JSON:API endpoints before writing the code, and add tests that verify the application matches it.

## Consequences

- Every client consumes the same contract, so a new client does not require changes to the core.
- The contract doubles as documentation.
- This works especially well with AI coding tools. A clear spec plus tests is what keeps generated code honest.
