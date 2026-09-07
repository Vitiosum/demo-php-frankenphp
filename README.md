# Symfony + FrankenPHP — PHP worker mode on Clever Cloud

> A Symfony application running on FrankenPHP in worker mode, deployed on the **FrankenPHP runtime** of Clever Cloud (no Docker image to build). Demonstrates how to run a modern PHP app with persistent worker processes — dressed with the Clever Brand Kit, certification front and centre.

---

## Deploy on Clever Cloud

1. Fork this repository
2. Create a **FrankenPHP** application and link it to your fork:

   ```bash
   clever create -t frankenphp demo-php-frankenphp
   clever link <app_id>          # or reuse the committed .clever.json
   ```

3. Set the environment variables (console → *Environment variables*, or `clever env set`) — see the table below. `APP_SECRET` is **mandatory**: the committed `.env` only carries a placeholder.
4. No add-on needed by default (SQLite, see *Data* below)
5. `git push` (or `clever deploy`) → Clever Cloud runs `composer install --no-dev --no-scripts`, then the hooks, then starts FrankenPHP

### Environment variables

| Variable | Required | Value / description |
|----------|----------|---------------------|
| `APP_SECRET` | **yes** | Real secret, e.g. `php -r 'echo bin2hex(random_bytes(32));'` — overrides the placeholder committed in `.env` |
| `APP_ENV` | yes | `prod` |
| `APP_DEBUG` | yes | `0` |
| `CC_WEBROOT` | yes | `public` |
| `CC_FRANKENPHP_WORKER` | yes | `/public/index.php` — **enables worker mode**, the whole point of this demo (`symfony/runtime` + `runtime/frankenphp-symfony` support it natively) |
| `CC_PRE_RUN_HOOK` | yes | `php bin/console doctrine:migrations:migrate --no-interaction` — creates the schema before each start |
| `CC_POST_BUILD_HOOK` | yes | `php bin/console assets:install public --no-interaction` — installs the Swagger UI assets of API Platform (`public/bundles/` is git-ignored and Composer scripts are not run at build time) |
| `CC_HEALTH_CHECK_PATH` | recommended | `/health` — served by `MainController::health()`, returns `200 {"status":"ok"}` |
| `DATABASE_URL` | no | Defaults to SQLite in `.env`; set it to the PostgreSQL add-on URI if you link one |

Equivalent CLI:

```bash
clever env set APP_SECRET "$(php -r 'echo bin2hex(random_bytes(32));')"
clever env set APP_ENV prod
clever env set APP_DEBUG 0
clever env set CC_WEBROOT public
clever env set CC_FRANKENPHP_WORKER /public/index.php
clever env set CC_PRE_RUN_HOOK "php bin/console doctrine:migrations:migrate --no-interaction"
clever env set CC_POST_BUILD_HOOK "php bin/console assets:install public --no-interaction"
clever env set CC_HEALTH_CHECK_PATH /health
```

### Data

The default `DATABASE_URL` points to SQLite in `var/data.db`. On Clever Cloud this file lives on the instance's ephemeral filesystem: it is **recreated at every deployment or restart** (`CC_PRE_RUN_HOOK` replays the migration and its seed) and it is **not shared** between instances — keep the app at 1 instance, or treat the data as throw-away demo data.

For persistent data, link a PostgreSQL add-on (plan DEV is enough for a demo) and set `DATABASE_URL` to its `POSTGRESQL_ADDON_URI`; the Doctrine migration is dialect-agnostic.

### About `.env`, `Caddyfile` and `static-build.Dockerfile`

- `.env` is committed on purpose (Symfony Dotenv needs it to boot) and only contains non-secret defaults plus an `APP_SECRET` placeholder. Real environment variables set in the console always win over `.env`.
- `Caddyfile` and `benchmark.Caddyfile` are **not used by the Clever Cloud runtime** (which ships its own Caddy configuration driven by `CC_*` variables). They are kept for running FrankenPHP locally (`frankenphp run`) and for the k6 benchmarks.
- `static-build.Dockerfile` is **not used by the Clever Cloud runtime** either: it builds an optional standalone static binary of the app (see [frankenphp.dev/docs/static](https://frankenphp.dev/docs/static/)). `.dockerignore` keeps `.git`, `var/`, `vendor/` and local `.env.*` files out of that build context.

---

## Stack

| Layer      | Technology          |
|------------|---------------------|
| Language   | PHP 8.2+ (Clever Cloud FrankenPHP runtime) |
| Framework  | Symfony 7.4 + API Platform 3.4 |
| Server     | FrankenPHP (worker mode via `CC_FRANKENPHP_WORKER`) |
| Database   | SQLite (ephemeral) — PostgreSQL add-on optional |
| Deploy     | Clever Cloud FrankenPHP runtime |
| Design     | Clever Brand Kit (Plus Jakarta Sans, navy #13172e, dégradé Clever) |

---

## Features

- FrankenPHP worker mode — PHP process stays alive between requests for maximum performance
- Symfony routing and templating (Twig), API Platform resource `Monster` with Swagger UI at `/api`
- Clever Brand Kit landing page: sticky brand bar, hero, **certification block**, four content tabs (Worker Mode / API Platform / Performance / Deploy), k6 benchmark cards, platform panel, footer
- Platform panel "Vu depuis Clever Cloud" reads the variables injected by the platform (see below)
- k6 reports served at `/benchmark/{name}` (e.g. `/benchmark/summary-100-vus-worker`) from the committed `benchmark/` folder
- `/health` endpoint for the Clever Cloud health check
- Responsive layout — no horizontal scroll at 375 px, single dark theme

### API write access

`POST/PUT/PATCH/DELETE /api/monsters` are deliberately left open (no authentication) so the Swagger UI can be demoed end to end; the `name` field is validated (`NotBlank`, max 255 characters → `422` otherwise) and the SQLite database is reset at every deployment. To lock it down, either restrict the resource to read operations (`#[ApiResource(operations: [new Get(), new GetCollection()])]`) or add an `access_control` rule on `^/api` for write methods in `config/packages/security.yaml`.

---

## Certification Clever Cloud

The homepage puts the **Clever Cloud Academy** certification right under the hero: badge, the two official tracks (Cloud Computing Fundamentals, Advanced Deployment) and a call to action to [academy.clever.cloud](https://academy.clever.cloud/). The brand bar also carries a permanent « Se certifier ↗ » pill.

---

## Platform panel (variables read at runtime)

`MainController::platform()` reads the environment injected by Clever Cloud and passes it to Twig as `cc`:

| Variable | Shown as |
|----------|----------|
| `CC_APP_NAME` | Application |
| `APP_ID` | App ID — absent locally → pill « Local · hors Clever Cloud » |
| `INSTANCE_NUMBER` + `CC_PRETTY_INSTANCE_NAME` | Instance (`#0 · Pikachu`) |
| `INSTANCE_TYPE` | Type d'instance |
| `CC_COMMIT_ID` (7 chars) | Commit déployé |
| `CC_DEPLOYMENT_ID` (16 chars) | Déploiement |
| `PHP_VERSION`, `$_SERVER['FRANKENPHP_WORKER']` | PHP, Worker actif / inactif |

Reference: [Clever Cloud environment variables](https://www.clever.cloud/developers/doc/reference/reference-environment-variables/).

---

## Project Structure

```
demo-php-frankenphp/
├── templates/
│   ├── base.html.twig            # <head> Brand Kit, brand bar, footer
│   ├── homepage/index.html.twig  # Hero, certification, tabs, benchmarks, platform panel
│   └── partials/
│       ├── cc-logo.svg.twig      # Official Clever Cloud logo (inlined)
│       └── cc-badge.svg.twig     # Certification badge (inlined)
├── public/
│   ├── cc-brand.css              # Shared Clever Brand Kit — copied as-is, do not edit
│   ├── demo.css                  # Demo-specific styles (tabs, benchmark table)
│   └── index.php                 # Web root / FrankenPHP worker script
├── src/
│   ├── Controller/MainController.php  # Homepage (+ platform panel data), /health, /benchmark/{name}, /download-logo
│   └── Entity/Monster.php        # API Platform resource (validated name)
├── migrations/                   # Doctrine migration (schema + seed), replayed by CC_PRE_RUN_HOOK
├── benchmark/                    # k6 script and HTML reports (FPM / no-worker / worker)
├── Caddyfile                     # Local FrankenPHP config only (not used on Clever Cloud)
├── static-build.Dockerfile       # Optional static binary build (not used on Clever Cloud)
├── .dockerignore                 # Build context of static-build.Dockerfile
├── .env                          # Committed — Symfony defaults + APP_SECRET placeholder
└── .clever.json                  # Clever Cloud app binding
```

---

## Local run

```bash
composer install
APP_ENV=dev php -S 127.0.0.1:8083 -t public
# http://127.0.0.1:8083/  ·  http://127.0.0.1:8083/api  ·  http://127.0.0.1:8083/health
php bin/console lint:twig templates
composer audit
```

Without FrankenPHP the platform panel shows « Worker : inactif (php -S) » — expected outside Clever Cloud. To try worker mode locally: `frankenphp run` (uses the committed `Caddyfile`).

---

## Deployment Notes

- App type on Clever Cloud: **FrankenPHP** runtime (not Docker, not the PHP/Apache runtime)
- `.env` must stay committed — Symfony requires it at boot time; secrets are set in the console
- `.clever.json` must stay committed — it binds the app to the Clever Cloud instance
- HTTPS is terminated at the Clever Cloud proxy — no HTTPS config needed inside the app; `trusted_proxies`/`trusted_headers` in `config/packages/framework.yaml` make Symfony honour `X-Forwarded-Proto`/`For`
- Known dependency debt: `api-platform/core` 3.4 has two medium advisories only fixed in 4.x (migration to plan separately)
