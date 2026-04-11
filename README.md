# Symfony + FrankenPHP — PHP worker mode on Clever Cloud

> A Symfony application running on FrankenPHP in worker mode, deployed as a Docker app on Clever Cloud. Demonstrates how to run a modern PHP app with persistent worker processes.

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
| Framework  | Symfony 7           |
| Server     | FrankenPHP (worker mode) |
| Database   | SQLite (local)      |
| Deploy     | Docker on Clever Cloud |
| Design     | Stellar.ai (Inter, white background, fadeInUp animations) |

---

## Features

- FrankenPHP worker mode — PHP process stays alive between requests for maximum performance
- Symfony routing and templating (Twig)
- Stellar.ai landing page design: hero section, feature tabs with auto-cycle, logo strip, video background
- Responsive layout — mobile-first

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
│   ├── base.html.twig          # CSS foundation + Inter font
│   └── homepage/index.html.twig # Full page: nav, hero, tabs, footer
├── src/                        # Symfony controllers
├── public/                     # Web root
├── Dockerfile                  # Docker build config
├── Caddyfile                   # FrankenPHP / Caddy config
├── .env                        # Committed — required by Symfony
└── .clever.json                # Clever Cloud app binding
```

---

## Deployment Notes

- App type on Clever Cloud: **Docker** (not PHP runtime)
- `.env` must stay committed — Symfony requires it at boot time
- `.clever.json` must stay committed — it binds the app to the Clever Cloud instance
- HTTPS is terminated at the Clever Cloud proxy — no HTTPS config needed inside the app
