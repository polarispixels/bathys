# ADR-0010: Host on a polarispixels.com Subdomain, on the Main Forge Server

- **Status:** Accepted (implemented at Phase 1, step 17)
- **Date:** November 7, 2026
- **Decided by:** Ryan Grissinger, delegating to Claude's recommendation
- **Related:** [ADR-0001](0001-stay-on-laravel-13.md), [deployment](../deployment.md)

## Context

Ryan wants Bathys under polarispixels.com, set up like his other Forge-hosted projects, either as a subdomain or a subfolder.

The closest precedent is Bubbles and Bows, at `bubbles.polarispixels.com`: a Forge site with zero-downtime releases (`releases/` plus a `current` symlink), Let's Encrypt SSL, PHP 8.4, on a small server (`curved-tuscany`) it shares with `santa.polarispixels.com`. The main site, polarispixels.com, runs on a separate Forge server (`polished-monaco`).

Measured November 7, 2026 (read-only SSH):

| Server | Hosts | Memory free | PHP | Node | Postgres |
|---|---|---|---|---|---|
| `curved-tuscany` | Bubbles, Santa (client staging) | about 490 MB of 1 GB | 8.4 | 22 | 17 |
| `polished-monaco` | polarispixels.com | about 1.1 GB of 2 GB | 8.4 | 22 | active |

## Decision

Serve Bathys at **`bathys.polarispixels.com`**, as its own Forge site on **`polished-monaco`**, configured like Bubbles: zero-downtime deployments, Let's Encrypt, PHP 8.4, a Postgres database on the server, and a PHP 8.4 queue worker. Deploys are triggered by CI, not on push (see [deployment](../deployment.md#the-pipeline)).

## Reasoning

**Subdomain over subfolder:**

- **Passkeys are bound to a domain.** A passkey is registered for a relying-party ID, normally the site's domain. Under `polarispixels.com/bathys`, Bathys would share an origin with the main site, including its cookies, its storage, and the scope of its passkeys. A subdomain gives Bathys its own origin.
- **Laravel stays at the root path.** A subfolder needs a proxied nginx `location`, plus asset URLs, cookie paths, and route generation all aware of the `/bathys` prefix. Each is a small, recurring source of bugs.
- **It matches Bubbles,** so the setup is familiar and the Forge steps are known.

**Main server over the Bubbles server:**

- **Memory.** Forge runs `npm run build` on the server at every deploy. A React and TypeScript build can need a few hundred megabytes. With about 490 MB free, the Bubbles server has little margin; the main server has about 1.1 GB.
- **Role.** The Bubbles server holds client staging work. Bathys is a personal production app and shouldn't compete with client sites, or be affected by their experiments.

## Alternatives Considered

- *Subfolder of polarispixels.com.* Rejected for the origin and path reasons above.
- *The Bubbles server.* Workable today, but tight on memory, and it mixes personal production with client staging.
- *A new dedicated server.* Cleanest isolation, but an extra monthly cost and another machine to patch, for a single-user app. Revisit if Bathys grows users or the main server gets busy.
- *Building assets in CI and shipping them, instead of building on the server.* Removes the memory pressure entirely, but departs from how every other Forge site is deployed. Keep it in reserve if builds start failing.

## Consequences

- Bathys shares a machine with polarispixels.com. A runaway Bathys process (a stuck queue job, a heavy build) could slow the business site. Mitigations: one queue worker with a timeout, and a failure alert ([deployment](../deployment.md#background-work)).
- Node on both servers is 22, so CI uses Node 22 too.
- DNS: an `A` record for `bathys.polarispixels.com` pointing at `polished-monaco`.
- The open "domain name" question in the spec is answered for now. A dedicated domain can be added later without changing the architecture; passkeys registered under the subdomain would need re-registering.
