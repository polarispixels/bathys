# ADR-0011: Serve the Documentation Site from the Repository's Markdown

- **Status:** Accepted
- **Date:** November 7, 2026
- **Decided by:** Ryan Grissinger, delegating to Claude's recommendation
- **Related:** [ADR-0005](0005-spec-driven-design-with-an-api-contract.md), [ADR-0006](0006-react-with-typescript-through-inertia.md), [design](../designs/2026-11-07-docs-site-design.md)

## Context

Ryan wants `bathys.polarispixels.com/docs` to tell a person or an agent what Bathys is and where it stands, in a shape he controls, the way his other projects (polarispixels.com, Indigo Nebula) have a public `/docs`. Those sites hand-write their documentation pages in Blade, and each release checklist has a step to update tool counts and blurbs on them by hand. They drift.

Bathys already keeps its knowledge as markdown in the repository: the spec, the ADRs, the method, the roadmap, the designs, and the changelog. CLAUDE.md names each as the authority for its concern. A second copy for the web would be a second place to keep current.

The app's pages are React through Inertia ([ADR-0006](0006-react-with-typescript-through-inertia.md)). An Inertia response carries its content as JSON inside a `data-page` attribute, which a crawler or an agent reading the raw HTML can't follow without running the app.

## Decision

1. **The documentation site renders the repository's markdown.** `/docs` serves the files under `docs/`, plus `README.md`, `CHANGELOG.md`, and `core/README.md`, converted to HTML at request time. There is no second copy. A document appears on the site when its file is committed and deployed.
2. **The front page is self-updating.** Its orientation text is a committed file (`docs/index.md`), and its status block is built from what the repository already records: the version in `package.json`, the latest entry in `CHANGELOG.md`, and the status board in `docs/roadmap.md`.
3. **Documentation pages are server-rendered Blade, not Inertia.** They are content, not application state. Plain HTML is what a crawler, an agent, or a reader with scripts off needs, and it needs no JavaScript bundle. The Tailwind stylesheet is shared with the app, so the two will share a visual language once one is designed (step 1f).
4. **Every page is also available as markdown** at the same address with `.md` appended, and `/llms.txt` lists them, so an agent reads the source text instead of scraping HTML.

## Reasoning and alternatives considered

- **Hand-written docs pages (the polarispixels.com pattern).** Full control over layout, but each page is a copy that must be updated by hand, which is the drift the development method is built to prevent.
- **A static site generator (VitePress, MkDocs) built in CI and served from `public/docs`.** Good output, but a second toolchain, a second build, and a second set of templates to learn, for about fifteen documents. The version and roadmap status would also need injecting at build time.
- **Inertia pages for the docs.** Consistent with the rest of the app, but the content would be invisible to anything that doesn't run the React bundle, which defeats the "for an agent" purpose. Blade pages still use the shared Tailwind stylesheet.
- **GitHub as the docs site.** Already true, and it stays true. Ryan wants a front page shaped for a reader, not a repository listing, with the project's status on it.

## Consequences

- **Everything in `docs/` is public.** The rules already forbid secrets anywhere in the repository; the deployment doc names servers and paths but nothing private. The security reviewer reads the set before launch, and anything that shouldn't be public moves out of `docs/`.
- **The front page content is one more file to write well** (`docs/index.md`), and it is the one page a reader sees first.
- **Documents are rendered from a catalog built by scanning the repository,** never from the request path, so a URL can't reach a file outside the set.
- **Blade and React coexist** in `resources/views/`. The app keeps using Inertia; only the documentation site uses Blade. This is a small, deliberate exception to [ADR-0006](0006-react-with-typescript-through-inertia.md).
- **The API reference** (`/docs/api`, from `contracts/openapi.yaml`, after step 1c) and the MCP page (Phase 2) join the same site later.
