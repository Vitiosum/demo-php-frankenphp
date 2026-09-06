# Symfony + FrankenPHP — PHP worker mode on Clever Cloud

> A Symfony application running on FrankenPHP in worker mode, deployed as a Docker app on Clever Cloud. Demonstrates how to run a modern PHP app with persistent worker processes — dressed with the Clever Brand Kit, certification front and centre.

---

## Deploy on Clever Cloud

1. Fork this repository
2. In the Clever Cloud console, create a new **Docker** application — connect your forked repo
3. No add-on needed (uses SQLite)
4. The `.env` file is committed — no manual environment variables to set
5. The `.clever.json` file is committed — the app binding is pre-configured
6. Push → Clever Cloud builds the Docker image and deploys automatically

---

## Stack

| Layer      | Technology          |
|------------|---------------------|
| Language   | PHP 8.3             |
| Framework  | Symfony 7 + API Platform 3 |
| Server     | FrankenPHP (worker mode) |
| Database   | SQLite (local)      |
| Deploy     | Docker on Clever Cloud |
| Design     | Clever Brand Kit (Plus Jakarta Sans, navy #13172e, dégradé Clever) |

---

## Features

- FrankenPHP worker mode — PHP process stays alive between requests for maximum performance
- Symfony routing and templating (Twig), API Platform resource `Monster` with Swagger UI at `/api`
- Clever Brand Kit landing page: sticky brand bar, hero, **certification block**, four content tabs (Worker Mode / API Platform / Performance / Deploy), k6 benchmark cards, platform panel, footer
- Platform panel "Vu depuis Clever Cloud" reads the variables injected by the platform (see below)
- k6 reports served at `/benchmark/{name}` (e.g. `/benchmark/summary-100-vus-worker`) from the committed `benchmark/` folder
- Responsive layout — no horizontal scroll at 375 px, single dark theme

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

## Environment Variables

| Variable | Required | Description                                 |
|----------|----------|---------------------------------------------|
| —        | —        | All config is in the committed `.env` file  |

> The `.env` file must remain committed for Symfony to boot on Clever Cloud.

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
├── src/Controller/MainController.php  # Homepage (+ platform panel data), /benchmark/{name}
├── benchmark/                    # k6 script and HTML reports (FPM / no-worker / worker)
├── static-build.Dockerfile       # Docker build config
├── Caddyfile                     # FrankenPHP / Caddy config (worker ./public/index.php)
├── .env                          # Committed — required by Symfony
└── .clever.json                  # Clever Cloud app binding
```

---

## Local run

```bash
composer install
APP_ENV=dev php -S 127.0.0.1:8083 -t public
# http://127.0.0.1:8083/  ·  http://127.0.0.1:8083/api
php bin/console lint:twig templates
```

Without FrankenPHP the platform panel shows « Worker : inactif (php -S) » — expected outside Clever Cloud.

---

## Deployment Notes

- App type on Clever Cloud: **Docker** (not PHP runtime)
- `.env` must stay committed — Symfony requires it at boot time
- `.clever.json` must stay committed — it binds the app to the Clever Cloud instance
- HTTPS is terminated at the Clever Cloud proxy — no HTTPS config needed inside the app
