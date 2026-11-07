# ADR-0001: Stay on Laravel and Upgrade to Laravel 13

- **Status:** Accepted
- **Date:** November 7, 2026
- **Decided by:** Ryan Grissinger
- **Related:** [ADR-0006](0006-react-with-typescript-through-inertia.md), [spec: Architecture and Stack](../spec.md#architecture-and-stack)

## Context

Ryan's existing projects (polaris-pixels, indigo-nebula) run on Laravel, Tailwind, Alpine, and Postgres, deployed on Laravel Forge. There is nothing wrong with that stack. The open question was whether to switch to something new or use this project to catch up on recent changes.

## Decision

Stay on Laravel and adopt Laravel 13 and its current ecosystem.

## Reasoning

Ryan has not fallen far behind, and the recent changes fit Bathys well.

- Laravel 13 launched on March 17, 2026, with no breaking changes, so moving from Laravel 12 is smooth.
- The **Laravel AI SDK** is now a first-party, production-stable package. It could power review summaries and shelf-photo recognition.
- Laravel 13 adds **first-party JSON:API support**, handling serialization, relationship inclusion, sparse fieldsets, links, and compliant headers automatically. That gives Bathys a standard API contract for free.
- **Passkeys** (Face ID, fingerprint, hardware keys) are built into the starter kits and Fortify, which simplifies single-user authentication.
- PHP attributes can now be used for more configuration (middleware, authorization, job retries), reducing boilerplate.
- Laravel 13 requires PHP 8.3 at minimum.

On the tooling side, **Laravel Boost** runs inside a project and provides a Laravel-aware MCP server, versioned guidelines, and version-specific documentation, so AI coding agents behave like an experienced Laravel developer. It is worth installing in polaris-pixels and indigo-nebula as well.

## Alternatives Considered

### TypeScript end to end

A full TypeScript stack, using something like Hono or Next.js on the backend and Drizzle on Postgres, was the main alternative.

**In favor:** one language is easier for a beginner, and it is closer to what most current tutorials show.

**Against:** it means learning a new backend ecosystem while also trying to model best practices, and it gives up the Laravel Forge deployment pattern Ryan already runs.

**Verdict:** worth choosing only if learning a new stack is itself a goal. For now, Laravel 13 plus a TypeScript frontend gets most of the teaching value with much less risk.

## Consequences

Deployment and maintenance stay consistent with what Ryan already runs on Forge. The learning effort goes into architecture and new Laravel features rather than a whole new ecosystem.

## References

- Laravel 13 announcement coverage, Laravel Daily: https://laraveldaily.com/post/laravel-13-laracon-eu-taylor-otwell
- Laravel 13 overview, DEV Community: https://dev.to/acquaintsoft/laravel-13-whats-new-and-what-changed-mig
- Laravel 13 features with code examples, DEV Community: https://dev.to/polash/laravel-13-drops-march-17-heres-every-new-feature-with-code-examples-3joa
- Laravel 13 release summary, Paulund: https://paulund.co.uk/resources/laravel-13-released
- Announcing Laravel Boost, Laravel blog: https://laravel.com/blog/announcing-laravel-boost
