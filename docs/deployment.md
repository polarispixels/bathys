# Deployment, Configuration, and Secrets

**Status:** Adopted November 7, 2026, before the first deploy. Items marked *confirm at step N* are settled when that Phase 1 step runs (see [`roadmap.md`](roadmap.md)). Update this document then, and remove the marker.

---

## Environments

| Environment | Where | Purpose |
|---|---|---|
| **Local** | WSL on Ryan's machine; Postgres 17 in Docker (`compose.yaml`) | Development and the full test suite |
| **CI** | GitHub Actions on [`polarispixels/bathys`](https://github.com/polarispixels/bathys) (private) | Runs every check on every push and pull request; the only path to production |
| **Production** | `bathys.polarispixels.com`, a Forge site on the `polished-monaco` server alongside polarispixels.com ([ADR-0010](adr/0010-host-on-a-polarispixels-subdomain.md)) | The live app. Single user. |

### Local database (Docker)

PHP and Node run natively in WSL. Docker runs only Postgres, the same pattern as polaris-pixels.

```bash
docker compose up -d     # Postgres 17 on localhost:5433, databases bathys and bathys_test
```

Port 5433 avoids a clash with polaris-pixels' container on 5432. `.env.example` already points at it. Docker Desktop must have WSL integration turned on for the Ubuntu distro (Docker Desktop, Settings, Resources, WSL integration).

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
- **Pull requests** run the same checks without the deploy step.
- **CI uses a real Postgres service container,** so adapter and contract tests run against the same database engine as production.

---

## Production server (Forge)

### PHP version: one number, everywhere

**Bathys runs PHP 8.4.** The version must match in all five places: `composer.json` (`require.php` is `^8.4`; `config.platform.php` is `8.4.1`, the lowest patch Laravel 13's Symfony 8 components accept), the Forge site, the queue worker, the scheduler cron, and CI (`.github/workflows/tests.yml`). In indigo-nebula, a queue worker pinned to a different PHP version than the app crashed on startup, and supervisor stopped retrying it. All queued mail stopped, and nothing reported it.

**Node is 22** on both Forge servers (checked November 7, 2026), so CI uses 22. Local development may run a newer Node; production is the version that counts. **Postgres is 17** on the servers, in CI, and in the local container.

### Site layout (as on Bubbles)

Forge's zero-downtime deployments keep each release in `releases/<id>/`, with `current` a symlink to the live one and `.env` plus `storage/` shared across releases. Nginx serves `current/public`, so a deploy switches the symlink only after a release builds successfully. Run artisan in `current/`, and always confirm the site directory first: the server hosts other sites.

### Deploy script (sketch, *confirm at step 17*)

```bash
cd $FORGE_SITE_PATH
git pull origin main
$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader
npm ci && npm run build
$FORGE_PHP artisan migrate --force
$FORGE_PHP artisan optimize        # config, route, view, and event caches
$FORGE_PHP artisan queue:restart
( flock -w 10 9 || exit 1; echo 'Restarting FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock
```

Using Forge's `$FORGE_PHP` variable, instead of hard-coding `php8.x`, keeps the deploy script on the site's PHP version.

### Background work

- **Queue worker:** needed from shelf-photo import onward (AI vision runs as a queued job). Same PHP version as the site.
- **Scheduler cron:** `artisan schedule:run` every minute. Same PHP version.
- **Failure alerts:** a failed queued job or a stuck queue must notify Ryan. indigo-nebula learned this after queued mail failed silently. *Confirm the channel (email or Slack) at step 23.*

### Database backups

Turn on scheduled Postgres backups before real data goes in, and **restore one once** to prove it works. A backup that has never been restored is only a hope. *Confirm at step 17.*

---

## Verifying a deploy

The app version (`package.json`) is rendered as `<meta name="app-version" content="X.Y.Z">` and in the page footer. After every deploy:

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
| `DB_*` | Password is | `.env` / CI service defaults / Forge | |
| AI provider key (shelf photos, review summaries) | Yes | `.env` / never (tests fake it) / Forge | Name set by the Laravel AI SDK; *confirm at step 23* |
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

Bathys has one user and signs in with a passkey. There is no public registration route. The single user is created by an artisan command or seeder on first deploy (the same idea as indigo-nebula's seeded admin). Passkey recovery (a lost device) needs a documented path before 1.0. *Confirm at step 17.*

---

## First deploy checklist (*step 17*)

1. ~~Create the GitHub repository (private).~~ Done November 7, 2026. Create the Forge site `bathys.polarispixels.com` on `polished-monaco` with zero-downtime deployments; connect it to the repository; add the DNS `A` record; issue the Let's Encrypt certificate.
2. Turn **off** Forge's deploy-on-push. Store the Forge deploy hook URL as the `FORGE_DEPLOY_HOOK` GitHub secret.
3. Set the PHP version on the site, the worker, and the scheduler to the one in `composer.json`.
4. Fill in production environment values in Forge's editor from `.env.example`.
5. Run the first deploy from CI. Create the user. Register a passkey.
6. Turn on database backups. Restore one.
7. `curl -s https://bathys.polarispixels.com/ | grep app-version`.
