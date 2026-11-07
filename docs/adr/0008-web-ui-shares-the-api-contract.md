# ADR-0008: The Web UI Shares the API Contract

- **Status:** Accepted
- **Date:** November 7, 2026
- **Decided by:** Ryan Grissinger, delegating to Claude's recommendation
- **Related:** [ADR-0005](0005-spec-driven-design-with-an-api-contract.md), [ADR-0006](0006-react-with-typescript-through-inertia.md)

## Context

[ADR-0005](0005-spec-driven-design-with-an-api-contract.md) says every client consumes the same contract. But Inertia ([ADR-0006](0006-react-with-typescript-through-inertia.md)) does not make the browser call an API. A Laravel controller renders a React page and hands it data ("props") directly. Left alone, the web UI would get its own data shapes, separate from the JSON:API, and the contract would quietly stop applying to the most-used client.

## Decision

Keep Inertia for the web UI, and make its data follow the contract:

1. Inertia controllers call the same use cases as the API controllers.
2. They serialize results with the **same JSON:API resource classes** the API uses, so a book looks the same in Inertia props as in an API response.
3. TypeScript types for the frontend are **generated from `contracts/openapi.yaml`**, not written by hand.

## Alternatives Considered

- *Accept separate shapes.* Simplest. But the contract would no longer describe the web UI, and the frontend's types would drift from the backend's.
- *Drop Inertia and make the React app call the JSON:API directly.* Fully honest to ADR-0005, but it gives up Inertia's routing, session authentication, and server-driven pages, and it means building client-side data fetching and state that Inertia makes unnecessary.

## Consequences

- One description of each resource (the OpenAPI document) governs the API, the web UI's types, and the MCP tools.
- Changing a resource shape means changing the contract first. The generated types then make the frontend fail to compile until it is updated, which is the point.
- Inertia props are wrapped in JSON:API structure (`data`, `attributes`, `relationships`), which is wordier than plain props. Small frontend helpers can hide that.
- The contract is checked for the API by contract tests, and for the web UI by type generation. The web UI is not checked at runtime.
