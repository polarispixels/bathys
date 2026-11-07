# ADR-0009: The MCP Server Lives Inside the Laravel App

- **Status:** Accepted (implementation in Phase 2)
- **Date:** November 7, 2026
- **Decided by:** Ryan Grissinger, delegating to Claude's recommendation
- **Related:** [ADR-0002](0002-put-portability-where-change-is-likely.md), [ADR-0003](0003-hexagonal-architecture.md), [ADR-0007](0007-core-beside-app-repository-layout.md)

## Context

Claude reaches Bathys through an MCP server. Ryan's existing projects (polaris-pixels, indigo-nebula) run their MCP servers as separate programs in an `mcp-server/` folder that call the application's REST API, with two transports: stdio for local use and Streamable HTTP for remote use.

For Bathys there are two options:

- **Outside the app:** a separate MCP program that is a client of the JSON:API.
- **Inside the app:** MCP tools that are another driving adapter, calling the core's use cases directly, as the web and API controllers do.

## Decision

Build the MCP server inside the Laravel app, in `app/Mcp/`, as a driving adapter. Keep both transports (stdio and Streamable HTTP). The exact package and transport setup are confirmed when Phase 2 begins.

## Reasoning

- **It is what hexagonal architecture predicts.** MCP is one more way for the outside world to call the core, like a web page or an API request. Putting it beside the other driving adapters shows that symmetry, which is a central lesson of [ADR-0003](0003-hexagonal-architecture.md).
- **One process, one deploy.** No second program to run, version, or authenticate on Forge.
- **No extra hop.** Tools call use cases directly instead of serializing to HTTP and back.
- **Swapping stays cheap.** Per [ADR-0002](0002-put-portability-where-change-is-likely.md), the MCP protocol is likely to change. Because MCP tools only translate between the protocol and use cases, replacing or splitting out the MCP adapter later means rewriting thin translation code, not business logic.

## Alternatives Considered

- *Separate MCP server calling the JSON:API* (the polaris-pixels pattern). Proves that the API contract is enough for an outside client, and it could run anywhere. But it is a second deployable, and it duplicates what the core already offers.

## Consequences

- The MCP tools must follow the same rule as controllers: call use cases, never repositories or models. The architecture tests in [ADR-0007](0007-core-beside-app-repository-layout.md) enforce this.
- Bathys departs from the `mcp-server/` pattern of Ryan's other projects. The difference is intentional and recorded here.
- The MCP tools' input and output shapes should reuse the contract's resource shapes ([ADR-0008](0008-web-ui-shares-the-api-contract.md)), so Claude sees the same book the web UI does.
