# Architecture Decision Records

This folder records *why* Bathys is built the way it is, not just *what* it is built with.

## What Is an ADR?

In software engineering, a document that explains the reasoning behind a system's design is usually called a **design rationale**. The individual choices inside it are written as **Architecture Decision Records (ADRs)**: short entries that capture the context, the decision, the alternatives considered, and the consequences.

The goal is that anyone reading this later (a collaborator, a student, a new programmer, or Ryan himself in five years) can understand the reasoning behind the technical choices and judge whether that reasoning still holds.

### How to read one

Every ADR follows the same shape:

| Section | Question it answers |
|---|---|
| **Status** | Is this decision in force? (`Proposed`, `Accepted`, `Superseded by ADR-XXXX`, `Deprecated`) |
| **Context** | What situation forced a decision? |
| **Decision** | What did we choose? |
| **Reasoning / Alternatives Considered** | Why this, and what else was on the table? |
| **Consequences** | What gets easier, what gets harder, what we now have to live with? |

### How to add one

1. Copy the most recent ADR and give it the next number: `NNNN-short-title-in-kebab-case.md`.
2. Start it as `Proposed`. Change it to `Accepted` once agreed.
3. **Never rewrite an accepted ADR to reflect a new decision.** Write a new ADR, and mark the old one `Superseded by ADR-XXXX`. The history of a changed mind is part of what this folder teaches.
4. Add it to the index below.

## Summary

Stay on Laravel, upgrade to Laravel 13, and get portability and teaching value from architecture rather than from a new framework.

Bathys is built as a framework-independent domain core with clean boundaries around it, a written API contract as the spec, and a TypeScript/React frontend. This catches Ryan up on recent changes to the Laravel ecosystem, produces a codebase worth studying as a case study in software architecture, and makes every layer swappable in the places where swapping actually matters.

## Goals Behind the Technical Choices

These goals came out of the planning conversation and drive every decision below.

1. **A modern application.** Use current tools and current practices, not just familiar ones.
2. **A teaching codebase.** Books, authors, and categories are one of the most common examples in computer science teaching. Bathys should be a model example of software architecture done well: design patterns, object structure, separation of concerns, and testability. It should be readable by students and new programmers.
3. **Portability.** It should be practical to replace one layer of the system with another, with the abstraction layers in the right places and an honest account of what each swap would cost.
4. **Spec-driven design.** The specification comes first, and the code is verified against it.
5. **Catching up.** Use this project to learn what has changed in the Laravel ecosystem recently, or to learn something new.

A guiding observation: the lines between technology stacks are getting blurrier as AI coding tools improve. Translating or swapping a layer is becoming cheaper and less risky. That makes clear boundaries and clear specs more valuable, because they are what make a swap safe.

## Index

| ADR | Title | Status |
|---|---|---|
| [0001](0001-stay-on-laravel-13.md) | Stay on Laravel and Upgrade to Laravel 13 | Accepted |
| [0002](0002-put-portability-where-change-is-likely.md) | Put Portability Where Change Is Likely | Accepted |
| [0003](0003-hexagonal-architecture.md) | Hexagonal Architecture (Ports and Adapters) | Accepted |
| [0004](0004-pragmatic-persistence.md) | Pragmatic Persistence (Active Record Behind Repositories) | Accepted |
| [0005](0005-spec-driven-design-with-an-api-contract.md) | Spec-Driven Design with an API Contract | Accepted |
| [0006](0006-react-with-typescript-through-inertia.md) | React with TypeScript Through Inertia | Accepted |
| [0007](0007-core-beside-app-repository-layout.md) | Repository Layout (the Core Beside `app/`) | Accepted |
| [0008](0008-web-ui-shares-the-api-contract.md) | The Web UI Shares the API Contract | Accepted |
| [0009](0009-mcp-server-inside-laravel.md) | The MCP Server Lives Inside the Laravel App | Accepted (Phase 2) |
| [0010](0010-host-on-a-polarispixels-subdomain.md) | Host on a polarispixels.com Subdomain, on the Main Forge Server | Accepted (step 17) |
| [0011](0011-docs-site-rendered-from-the-repository.md) | Serve the Documentation Site from the Repository's Markdown | Accepted |

## Origin

ADR-0001 through ADR-0006 were first written together as a single document, *Bathys: Architecture and Technology Rationale* (v0.1, November 7, 2026), and split into individual records when the repository was created. They were accepted when Ryan confirmed the stack the same day (see the [spec's Decisions Log](../spec.md#decisions-log)). ADR-0007 through ADR-0009 were written during review of spec v0.2.

Some ADRs say "Decided by: Ryan Grissinger, delegating to Claude's recommendation." That means the owner accepted a recommendation without working through the alternatives himself. Recording it tells a future reader which decisions had less scrutiny, and which are cheaper to revisit.
