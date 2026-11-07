# AI Tooling

**Status:** Adopted November 7, 2026. Drafted in a Claude Desktop conversation, then reviewed in Claude Code: plugin names and the `claude plugin` commands were checked against the installed marketplace, and model IDs, prices, and effort defaults against the Claude API reference, all on that date. Re-verify quarterly (next review: February 2027, tracked in [#13](https://github.com/polarispixels/bathys/issues/13)); this field moves fast.

This document says which model, plugin, skill, and MCP server Claude uses for each phase and concern of building Bathys, and why. It is written to be copied. Other projects should adopt its principles, model routing, and structure unchanged, then swap the rows that are specific to Laravel and Forge.

How it relates to the other docs: [`development.md`](development.md) says how a change moves from idea to release; this document says which tool does the work at each step. [`CLAUDE.md`](../CLAUDE.md) holds only the rules that must load in every session; this is reference material, read when needed.

---

## Principles

1. **One tool per job.** Two tools doing the same job (two code reviewers, two documentation lookups) cost tokens twice and give conflicting answers. Each concern below names one primary tool.
2. **Official first; read the source otherwise.** Install from Anthropic's `claude-plugins-official` marketplace by default. Anything from another marketplace gets its source read before it is installed, because a plugin can run hooks and MCP servers on your machine.
3. **Spend the expensive model on judgment.** The top-level model decides; cheaper subagents type and fetch. A long session has passes where judgment changes the outcome and passes where the answer is already determined.
4. **The stack lives in the repository.** Plugins are enabled at project scope in `.claude/settings.json` and subagents are defined in `.claude/agents/`, both committed. A fresh clone gets the same setup, and a reader can see it.
5. **Every tool earns its context.** Each enabled plugin adds tokens to every turn. `claude plugin details <name>` shows the always-on cost, and the **Installed** tab in `/plugin` groups plugins under **Not used recently**. The monthly plugin review ([development](development.md#skills-and-plugins)) disables what hasn't been used.
6. **Add a tool when its phase arrives.** Each row below names the roadmap step it starts at. Installing everything on day one pays the context cost for months before the tool is useful.
7. **A tool choice with ongoing cost or lock-in is an architectural decision.** It gets an ADR before it is adopted (a monitoring service, an AI vision provider).
8. **Production stays out of reach.** Routine deploys go through CI only ([deployment](deployment.md#the-pipeline)). The one exception is setup and operations through the narrowly scoped Forge API token ([deployment](deployment.md#forge-api-token)), which can't delete sites, add SSH keys, or run commands. No MCP server gets write access to the production server, which hosts other sites.

---

## Models and effort

Claude Code sets two dials independently: the **model** (`/model`) and the **effort** (`/effort`: low, medium, high, xhigh, max). A session's cost depends on both. Subagent definitions can set each one in their frontmatter.

| Work | Model | Effort | Why |
|---|---|---|---|
| Top-level session: brainstorming, design docs, ADRs, plans, contract review, branch review, ship judgment | Claude Fable 5.1 | high (its default); medium for routine back-and-forth | These passes decide the outcome. Fable 5.1 is built for demanding reasoning and long agentic work. |
| Hardest design or debugging passes | Claude Fable 5.1 | xhigh, for that pass only | Raise effort for the step that needs it, then drop back |
| Implementing one plan task, test-first | Claude Sonnet 5.5 subagent (`implementer`) | medium | A precise brief and its own test loop; about a fifth of Fable's price per token |
| An implementation task Sonnet has failed twice | Claude Opus 5.5 subagent | high | Escalate one step, not straight to Fable |
| Independent code review of a branch | Claude Opus 5.5 subagent (`reviewer`) | high | A different model from the one that orchestrated the work, at under half of Fable's price |
| Security review | Claude Opus 5.5 subagent (`security-reviewer`) | high | Same reasons as code review: a second model's eyes, at under half of Fable's price. (Both Fable 5.1 and Opus 5.5 run safety classifiers that can decline some cybersecurity content; if a legitimate review is declined, narrow it to the diff and say what the code is for.) |
| Searches, renames, doc-sync sweeps, changelog checks, log triage | Claude Haiku 5.5 subagent (`scout`) | low | Mechanical work; verbose output stays in the subagent's own context |

Prices on November 7, 2026, per million tokens (input / output): Fable 5.1 $10 / $50, Opus 5.5 $4 / $20, Sonnet 5.5 $2 / $10, Haiku 5.5 $0.10 / $0.50 for prompts up to 100K tokens. Effort defaults differ by model: `high` on Fable 5.1 and Sonnet 5.5, `medium` on Opus 5.5 and Haiku 5.5, so subagent definitions always set effort explicitly.

### Running Fable 5.1 as the top-level model

- **Keep the prompt cache warm.** Fable 5.1 cache reads cost 2.5% of the input price, so a long session on a stable prefix is far cheaper than its list price suggests. Batch changes to CLAUDE.md, plugins, and subagent definitions at the end of a session (they take effect in the next one anyway); Claude Code warns that a plugin change can invalidate the prompt cache.
- **Ultracode stays off by default.** It runs xhigh and gives Claude standing permission to launch multi-agent workflows on its own, and every subagent it starts inherits the session's effort. Turn it on for a single task by including the keyword `ultracode` in the prompt, never as a session default.
- **Counter its documented habits in CLAUDE.md.** Anthropic lists behavior changes in Fable 5.1 that show up in a coding session. Each has a one-line counter:

| Fable 5.1 tends to | Line for CLAUDE.md |
|---|---|
| Rewrite a whole file for a small change | "Make targeted edits; don't rewrite a whole file to change part of it." |
| Make one tool call per turn where several were independent | "Batch independent reads, searches, and test runs into one turn." |
| Write more tests than a change needs | "Write the tests the change needs and its important failure modes; no more." |
| Write denser prose, with longer sentences and fewer paragraph breaks | Already covered by hard rule 9 (plain writing); the `avoid-ai-writing` pass catches the rest |
| Narrate less between tool calls | No change needed; the task list shows progress |

### Subagent definitions

Four named subagents live in `.claude/agents/`, so plans and skills can dispatch work by name and the model and effort travel with the definition:

```markdown
---
name: implementer
description: Implements one task from a plan in docs/plans/, test-first, and commits it.
model: sonnet
effort: medium
---
```

| Name | Model | Effort | Tools |
|---|---|---|---|
| `implementer` | sonnet | medium | All (it writes code and runs tests) |
| `reviewer` | opus | high | Read, Grep, Glob, Bash |
| `security-reviewer` | opus | high | Read, Grep, Glob, Bash |
| `scout` | haiku | low | Read, Grep, Glob, Bash |

Two traps:

- **Model resolution takes the first match:** the model passed when a subagent is spawned, then the definition's `model`, then the `CLAUDE_CODE_SUBAGENT_MODEL` environment variable, then the parent's model.
- **A reviewer is only read-only if its tool list says so.** Leaving out `tools` gives a subagent every tool, and a parent session in `auto`, `acceptEdits`, or `bypassPermissions` mode passes that mode down. List the tools explicitly.

---

## The always-on layer

Enabled at project scope from the start, because every phase uses them.

| Tool | Kind | Why |
|---|---|---|
| **superpowers** (`superpowers@claude-plugins-official`, v7.0.0 installed November 7, 2026) | Plugin | The lifecycle in [development](development.md#lifecycle-of-a-change): brainstorming, writing-plans, subagent-driven-development, test-driven-development, requesting-code-review, systematic-debugging, verification-before-completion, finishing-a-development-branch |
| **Laravel Boost** | Composer dev dependency: MCP server plus seven skills in `.claude/skills/` | Version-correct Laravel documentation (`search-docs`), database schema and read-only queries, application logs, browser logs |
| **php-lsp** (`php-lsp@claude-plugins-official`) | Plugin; needs the `intelephense` language server on the PATH (see [Installing](#installing)) | Diagnostics and symbol navigation after every edit, so Claude finds a class by reference instead of by grep. Complements Larastan, which runs at check time. The language server runs outside the model, so it adds no context cost (`claude plugin details`, November 7, 2026). |
| **avoid-ai-writing** | User skill | A pass over prose people will read: docs, UI copy, the README |

---

## Concern by concern

"From" is the roadmap step where the tool is first needed. Add it then.

| Concern | From | Primary tool | Model | Notes |
|---|---|---|---|---|
| Product questions | now | `superpowers:brainstorming` | Fable, high | Domain answers come from Ryan and go into the spec (CLAUDE.md hard rule 6) |
| Architecture | now | `superpowers:brainstorming`, then an ADR by hand | Fable, high | Any choice with ongoing cost or lock-in gets an ADR before code |
| Design docs and plans | now | `superpowers:writing-plans` | Fable, high | Every plan has Global Constraints and Review Focus. Split a stage when one plan would make a branch too long to review well. |
| Domain core, test-first | 1a | `superpowers:subagent-driven-development` with `test-driven-development`; `implementer` | Sonnet, medium | `php artisan test --compact --testsuite=Core` boots no framework, so each loop takes seconds |
| Application layer and ports | 1b | Same as 1a | Sonnet, medium | The shared repository suite runs against both the in-memory and the Eloquent implementations |
| API contract | 1c | Fable drafts `contracts/openapi.yaml`; Ryan reviews | Fable, high | Add an OpenAPI linter (Redocly CLI or Spectral) as a composer script; choose at 1c |
| Persistence and schema | 1d | Boost `database-schema` and `search-docs` | Sonnet, medium; Fable reviews migrations | Tables follow the domain model, never the reverse ([ADR-0004](adr/0004-pragmatic-persistence.md)) |
| Database efficiency | 1d | `Model::preventLazyLoading()` outside production; Boost `database-query`; Postgres MCP Pro locally, once real queries exist | Opus, high, for query analysis | Postgres MCP Pro gives EXPLAIN plans and index advice but needs the `pg_stat_statements` and `hypopg` extensions, and its upstream development has slowed. Local database only, restricted mode. |
| Endpoints and authentication | 1e | superpowers lifecycle; Boost `fortify-development` skill | Sonnet, medium; `security-reviewer` on every auth change | Passkeys, single user, no registration |
| Security | 1e | `security-guidance` plugin, plus the built-in `/security-review` before merging anything that touches auth, secrets, or external input | Opus, high | `security-guidance` warns on risky patterns while Claude edits and reviews the diff when a session stops. That review costs tokens every session, so it starts when auth arrives. |
| Frontend design | 1f | `frontend-design` plugin | Fable sets direction; Sonnet builds | The session screen is mobile-first ([spec](spec.md#where-it-is-used)) |
| Frontend build | 1f | Boost `inertia-react-development`, `tailwindcss-development`, `wayfinder-development` skills; `typescript-lsp` plugin | Sonnet, medium | Inertia props go through the JSON:API resources ([ADR-0008](adr/0008-web-ui-shares-the-api-contract.md)) |
| Browser verification | 1f | `playwright` plugin; Boost `browser-logs` | Sonnet or Haiku | Check the session screen at phone width, in dim mode |
| External services | 1g | Ports plus recorded fixtures ([testing rules](development.md#testing)); Anthropic's Claude API skill when writing the vision adapter | Fable designs the port; Sonnet builds the adapter | Which model and provider read book spines is an ADR at 1g |
| MCP server | Phase 2 | `mcp-server-dev` plugin; the `laravel/mcp` package Boost already depends on | Fable designs; Sonnet builds | Test with MCP Inspector over both transports ([ADR-0009](adr/0009-mcp-server-inside-laravel.md)) |
| Code review | every branch | `superpowers:requesting-code-review`, dispatched to `reviewer` | Opus, high | Not also the official `code-review` plugin (principle 1) |
| Debugging | as needed | `superpowers:systematic-debugging` | Fable, high or xhigh | A flaky test is a nondeterminism bug ([testing](development.md#testing)) |
| Docs and copy | every change | `avoid-ai-writing`; `scout` for doc-sync sweeps | Fable writes; Haiku sweeps | Keep tables in the compact `\|---\|` style |
| CLAUDE.md hygiene | monthly | `claude-md-management` plugin, user scope | Fable | It can write session learnings into CLAUDE.md; check the size budget afterwards |
| Commits, releases, CI | every release | The CLAUDE.md release checklist, `gh`, GitHub Actions | Sonnet or Haiku | No plugin needed; tests enforce the version and changelog sync |
| Deployment | 1e | CI calls the Forge deploy hook; SSH only to inspect | n/a | No Forge MCP with write or destructive access. A read-only one is optional for status questions. |
| Monitoring and error reporting | 1e (candidate) | Laravel Nightwatch, with its MCP server for exceptions and performance issues | Fable or Opus to triage | Boost offers to configure the Nightwatch MCP server once `laravel/nightwatch` is installed. Adopting it is ADR-0011; confirm pricing first. |
| Logging | now | Laravel log channels; Boost log tools locally; production logs over SSH (or Nightwatch, if adopted) | Haiku for log triage | Confirm the site directory before any command on the server (CLAUDE.md known traps) |
| Reporting: status and spend | monthly | The roadmap status board; `claude plugin details`; the Anthropic usage dashboard | n/a | Review spend alongside the plugin review |

---

## Deliberately not used

Recording what was left out keeps it from being re-proposed every month.

| Tool | Why not |
|---|---|
| context7 | Boost's `search-docs` already gives version-correct docs for Laravel, Inertia, Pest, and Tailwind. Revisit if a non-Laravel library becomes central. |
| The official `code-review` plugin | Duplicates superpowers' review step |
| Large skill bundles (for example ECC, about 300 skills) | Their skill listings load into every session's context |
| Ultracode as a session default | Unbounded parallel work at xhigh, inherited by every subagent |
| Forge MCP servers with write access | The production server hosts polarispixels.com and other sites; deploys go through CI only |
| Memory plugins | The repository is the memory ([development](development.md#memory)) |
| Output-compression plugins | Terse output works against a teaching codebase |

---

## Installing

The always-on layer, at project scope:

```bash
npm install -g --prefix ~/.local intelephense   # no sudo; ~/.local/bin is already on the PATH
claude plugin install superpowers@claude-plugins-official --scope project
claude plugin install php-lsp@claude-plugins-official --scope project
```

On Ryan's machine npm's global prefix is `/usr`, so a plain `npm install -g` needs `sudo`; the `--prefix ~/.local` form avoids that without changing npm's configuration.

Committing `.claude/settings.json` turns these plugins on for anyone who clones the repository, but it doesn't download them. Each collaborator runs the install commands once.

Later, when their step arrives: `security-guidance` (1e), `typescript-lsp`, `frontend-design`, and `playwright` (1f), `mcp-server-dev` (Phase 2). `claude-md-management` goes at user scope, since it serves every project.

---

## Adopting this in another project

1. Copy the principles, the model table, and the subagent definitions unchanged.
2. Replace the stack-specific rows (Boost, php-lsp, Nightwatch, Forge) with that stack's equivalents, using principle 2 to choose them.
3. Tie the "From" column to that project's own roadmap.
4. Commit `.claude/settings.json` and `.claude/agents/`.
5. Add one line to that project's CLAUDE.md pointing here, and note which rows differ.

---

## Open items

- **Confirm that superpowers dispatches to the named subagents** ([#14](https://github.com/polarispixels/bathys/issues/14)). After the first 1a task, check the transcript for the subagent's model. If superpowers spawns its own general-purpose agents, add a line to the plan's Global Constraints naming `implementer`.
- **Quarterly review** of this document ([#13](https://github.com/polarispixels/bathys/issues/13)), next in February 2027.
- **OpenAPI linter:** choose at step 1c.
- **Monitoring service:** ADR at step 1e.
- **Vision provider and model for shelf-photo import:** ADR at step 1g.

---

## Sources (checked November 7, 2026)

- [Models overview](https://platform.claude.com/docs/en/models/overview) and [What's new in Claude Fable 5.1](https://platform.claude.com/docs/en/models/fable-5-1/whats-new-fable-5-1)
- [Install and manage plugins](https://code.claude.com/docs/en/discover-plugins) and [Workflows and ultracode](https://code.claude.com/docs/en/workflows)
- [Subagent configuration](https://stevekinney.com/courses/ai-development-setup/subagent-configuration/llms.txt)
- [Superpowers listing](https://www.claudepluginhub.com/plugins/obra-superpowers-2) and [PHP LSP](https://claude.com/plugins/php-lsp)
- [Nightwatch MCP server](https://nightwatch.laravel.com/docs/mcp-server)
- [Postgres MCP Pro](https://github.com/crystaldba/postgres-mcp)
