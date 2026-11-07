# Deployment, Configuration, and Secrets

**Status:** Adopted November 7, 2026. **Live since November 7, 2026** at https://bathys.polarispixels.com (v0.2.0, the coming-soon page). Items still marked *confirm at step N* are settled when that Phase 1 step runs (see [`roadmap.md`](roadmap.md)).

---

## Environments

| Environment | Where | Purpose |
|---|---|---|
| **Local** | WSL on Ryan's machine; Postgres 18 in Docker (`compose.yaml`) | Development and the full test suite |
| **CI** | GitHub Actions on [`polarispixels/bathys`](https://github.com/polarispixels/bathys) (private) | Runs every check on every push and pull request; the only path to production |
| **Production** | `bathys.polarispixels.com`, a Forge site on the `polished-monaco` server alongside polarispixels.com ([ADR-0010](adr/0010-host-on-a-polarispixels-subdomain.md)) | The live app. Single user. |

### Local database (Docker)

PHP and Node run natively in WSL. Docker runs only Postgres, the same pattern as polaris-pixels.

```bash
docker compose up -d     # Postgres 18 on localhost:5433, databases bathys and bathys_test
```

Port 5433 avoids a clash with polaris-pixels' container on 5432. `.env.example` already points at it. Docker Desktop must have WSL integration turned on for the distro (Docker Desktop, Settings, Resources, WSL integration, then enable the distro by name). If `docker` is "not found in this WSL 2 distro", that switch is off. Verified November 7, 2026: Laravel in WSL reaches the container on port 5433 and migrates.

There is no staging environment. For a single-user app, CI plus a quick rollback ([below](#rollback)) costs less than maintaining a second server. Revisit this if Bathys gains users.

---

## The pipeline

```
push to main ──► GitHub Actions
                   ├─ composer lint        (Pint, Larastan)
                   ├─ composer test        (Pest: core, adapters on Postgres, feature, contract, architecture)
                   ├─ npm run typecheck
                   ├─ npm run build
                   └─ all green? ──► call the Forge deploy hook ──► Forge runs the deploy script
```

- **Forge's "deploy on push" stays off.** Forge deploys only when CI calls its deploy hook URL, after every check has passed. indigo-nebula deploys on push with tests run by hand; knight-notes gates deploys on CI. Bathys follows knight-notes.
- **The deploy job** (`deploy` in `.github/workflows/tests.yml`) runs only for pushes to `main`, only after the `ci` job passes. It calls the hook stored in the GitHub secret `FORGE_DEPLOY_HOOK`, then polls the live site until `<meta name="app-version">` matches `package.json`. If the secret is missing, it skips with a warning instead of failing.
- **Pull requests** run the same checks without the deploy step.
- **CI uses a real Postgres service container,** so adapter and contract tests run against the same database engine as production.

---

## Production server (Forge)

### PHP version: one number, everywhere

**Bathys runs PHP 8.4.** The version must match in all five places: `composer.json` (`require.php` is `^8.4`; `config.platform.php` is `8.4.1`, the lowest patch Laravel 13's Symfony 8 components accept), the Forge site, the queue worker, the scheduler cron, and CI (`.github/workflows/tests.yml`). In indigo-nebula, a queue worker pinned to a different PHP version than the app crashed on startup, and supervisor stopped retrying it. All queued mail stopped, and nothing reported it.

**Node is 22** on both Forge servers (checked November 7, 2026), so CI uses 22. Local development may run a newer Node; production is the version that counts. **Postgres is 18** on polished-monaco (Forge reports `postgres18`), so CI and the local container use 18. (The Bubbles server runs 17; that is where an early "17 everywhere" came from.)

### The Forge site

| Setting | Value |
|---|---|
| Server | `polished-monaco` (Forge server 988414, 68.183.103.138), alongside polarispixels.com |
| Site | `bathys.polarispixels.com` (Forge site 3487841), type Laravel, PHP 8.4, zero-downtime deployments |
| Domains | `bathys.polarispixels.com` primary; `www.bathys` redirects to it (DNS at Namecheap: `A` record plus a `www` CNAME) |
| Certificate | Let's Encrypt (ECDSA, HTTP-01), covers both names; Forge renews it |
| Database | Postgres database and user `bathys` on the server; credentials only in the site's environment in Forge |
| Repository | `git@github.com:polarispixels/bathys.git`, branch `main`, cloned with a **read-only deploy key** (below) |
| Push to deploy | Off: CI triggers deploys |

**Repository access.** Forge's GitHub connection uses Ryan's `rgriss` account, which is a collaborator on the other Forge-deployed repos. Bathys uses a read-only deploy key instead (GitHub repo settings, Deploy keys: "Forge: bathys.polarispixels.com (read-only)"). Forge can read this one repository and nothing else, and access doesn't depend on a person's account. Through the API, the key must exist on GitHub **before** the site is created, and the create request must set `generate_deploy_key: true` along with the key pair; otherwise Forge clones with the server's default key and GitHub answers "Repository not found". Forge's current API cannot add a repository to an existing site, only change one.

### Site layout

Forge's zero-downtime deployments keep each release in `releases/<id>/`, with `current` a symlink to the live one and `.env` shared across releases. Nginx serves `current/public`, so a deploy switches the symlink only after a release builds successfully. Run artisan in `current/`, and always confirm the site directory first: the server hosts other sites.

**`storage/` is shared by the deploy script,** not by Forge's "shared paths" setting. Sites created in Forge's UI get a `storage` shared path; sites created through the API get none, and the API cannot set it later. The deploy script links each release's `storage` to `/home/forge/bathys.polarispixels.com/storage`, so logs and uploads survive deploys.

### Deploy script

The script lives in the repository at [`deploy/forge-deploy.sh`](../deploy/forge-deploy.sh), and Forge holds a copy. After changing the file, update Forge's copy (Forge, the site, Deployments, Deploy script; or the API's `PUT .../deployments/script`). It uses Forge's `$FORGE_COMPOSER` and `$FORGE_PHP`, so it always runs the site's PHP version.

### Background work

Not set up yet; tracked in [#9](https://github.com/polarispixels/bathys/issues/9).

- **Queue worker:** not needed in Phase 1, which has no queued jobs. It returns with the first one (for example, shelf-photo import, now [Later](spec.md#later)). Same PHP version as the site.
- **Scheduler cron:** `artisan schedule:run` every minute. Same PHP version.
- **Failure alerts:** a failed queued job or a stuck queue must notify Ryan. indigo-nebula learned this after queued mail failed silently. Choose the channel (email or Slack) when the first queued job arrives.

### Forge API token

Setup and operations through Forge's API (not routine deploys, which use the deploy hook) use a token kept outside the repository on Ryan's machine, readable only by him. It is read from a file at call time, so its value never appears in a command or log. Its scopes are deliberately narrow:

- **Server:** `server:view`, `server:create-databases`
- **Site:** `site:create`, `site:meta`, `site:manage-deploys`, `site:manage-project`, `site:manage-environment`, `site:manage-ssl`

It can't delete sites or servers or add SSH keys, but it is not harmless: `site:manage-deploys` lets it replace the deploy script and trigger a deploy, which runs shell commands as `forge` on the server, and `site:manage-environment` lets it read the environment file. Treat it like server access. The token is kept outside the repository and expires; renewal is [#11](https://github.com/polarispixels/bathys/issues/11). The worker and scheduler ([#9](https://github.com/polarispixels/bathys/issues/9)) will need scopes it doesn't have.

When reading site details from the API, never print the `deployment_url` field: it contains the deploy hook's secret token.

### Database backups

Turn on scheduled Postgres backups before real data goes in, and **restore one once** to prove it works. A backup that has never been restored is only a hope. Tracked in [#6](https://github.com/polarispixels/bathys/issues/6).

---

## Verifying a deploy

The app version (`package.json`) is rendered as `<meta name="app-version" content="X.Y.Z">`. CI's deploy job checks it automatically; by hand:

```bash
curl -s https://bathys.polarispixels.com/ | grep app-version
```

A deploy is done when that prints the version just released. "Pushed" isn't done.

---

## Rollback

1. `git revert` the bad commit (or commits) on `main` and push. CI runs and redeploys the previous behavior.
2. **Migrations must allow this.** Prefer additive migrations (new columns, new tables). Drop or rename in a *later* release, after the code no longer uses the old shape. Then a code rollback never meets a schema it can't read.
3. A migration that cannot be safely reversed is a **major** version bump, and is called out in the changelog.

---

## Secrets and configuration

### Inventory

Every variable is listed, with a comment and without a real value, in `.env.example`. This table is the overview.

| Variable | Secret? | Lives in (local / CI / production) | Notes |
|---|---|---|---|
| `APP_KEY` | Yes | `.env` / generated per run / Forge environment | Encrypts sessions and cookies; rotating it signs Ryan out |
| `APP_URL` | No | `.env` / CI default / Forge | The public base URL; `/llms.txt` and absolute links are built from it |
| `DOCS_REPOSITORY_URL` | No | `.env` / unset / Forge | Defaults to the public GitHub repository; set empty to turn repository links off |
| `DB_*` | Password is | `.env` / CI service defaults / Forge | |
| AI provider key | Yes | `.env` / never (tests fake it) / Forge | Not used in version 1; needed only if shelf-photo import or review summaries return from [Later](spec.md#later) |
| Metadata source (Open Library) | No key | | If Google Books is added later, its key is a secret |
| MCP access tokens | Yes | Generated in the app, stored hashed | Phase 2 |
| `FORGE_DEPLOY_HOOK` | Yes | Never local / GitHub Actions secret / Forge | Anyone holding it can trigger a deploy |

### Rules

1. **`.env` is never committed.** `.env.example` is, and it lists every variable with a comment and an empty or safe placeholder value.
2. **Production values live only in Forge's environment editor.** Don't edit the server's `.env` over SSH, so there is one place to look and one place to change.
3. **CI secrets live in GitHub Actions secrets,** referenced by name in the workflow file.
4. **Tests run with every external key blank,** and stray HTTP requests fail the suite. A test can never reach a real service, even by accident.
5. **Secrets are referenced by name in commands.** Write `curl -X POST "$FORGE_DEPLOY_HOOK"`, never the URL itself. Claude Code saves approved commands word for word into `.claude/settings.local.json`. In polaris-pixels, a real bearer token ended up stored there that way.
6. **Claude cannot read `.env`.** The committed `.claude/settings.json` denies it. This is a seatbelt, not a vault: a shell command could still print the file, so rule 5 still applies.
7. **A secret that appears anywhere it shouldn't is rotated, not just deleted.** That includes a commit, a transcript, a log, a screenshot, or a permission file. Deleting a secret doesn't un-share it.

### Authentication

Bathys has one user and signs in with a passkey. There is no public registration route. The single user is created by an artisan command or seeder on first deploy (the same idea as indigo-nebula's seeded admin). Creating the user is [#7](https://github.com/polarispixels/bathys/issues/7); a recovery path for a lost passkey device, required before 1.0, is [#8](https://github.com/polarispixels/bathys/issues/8).

---

## First deploy checklist

The site went live early (November 7, 2026) for a coming-soon page. Done items are struck through.

1. ~~Create the GitHub repository (private), the Forge site on `polished-monaco` with zero-downtime deployments and the repository, the DNS records, and the Let's Encrypt certificate.~~ Done November 7, 2026.
2. ~~Leave Forge's push-to-deploy off; store the deploy hook as the `FORGE_DEPLOY_HOOK` GitHub secret.~~ Done November 7, 2026. CI's deploy job verified end to end (Forge deployment 82378358).
3. ~~PHP 8.4 on the site.~~ Done. The worker and scheduler get the same version when they are added ([#9](https://github.com/polarispixels/bathys/issues/9)).
4. ~~Production environment values.~~ Done (app URL, production mode, Postgres credentials, secure cookies).
5. ~~First deploy.~~ Done (deployment 82377813). Before 1.0: create the single user on the server and register a passkey ([#7](https://github.com/polarispixels/bathys/issues/7)), with a recovery path for a lost device ([#8](https://github.com/polarispixels/bathys/issues/8)).
6. Turn on database backups, and restore one, before real reading data goes in ([#6](https://github.com/polarispixels/bathys/issues/6)).
7. ~~`curl -s https://bathys.polarispixels.com/ | grep app-version`~~ prints `0.2.0`.
