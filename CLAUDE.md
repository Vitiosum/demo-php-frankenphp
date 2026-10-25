# CLAUDE.md — demo-php-frankenphp

## Contexte

Démo avant-vente Clever Cloud : Symfony 7.4 sur le **runtime FrankenPHP** (type `frankenphp`, pas Docker), en **mode worker**. Le sujet de la page est le worker lui-même : nombre de requêtes servies par le même processus PHP, rafale mesurée vers `/worker`, comparaison k6 committée. Aucune application en service : la procédure du README vaut pour une app à créer.

## Déploiement Clever Cloud

- Type : `clever create -t frankenphp`, flavor nano (build sur instance dédiée automatique pour nano/pico), 1 à 2 instances possibles (données en lecture seule).
- Add-on : **aucun**. SQLite `var/data.db`, recréé par `CC_PRE_RUN_HOOK` à chaque démarrage ; pas de PostgreSQL, donc pas de `reset-demo.sh` (un `clever restart` suffit).
- Variables obligatoires : `APP_SECRET`, `APP_ENV=prod`, `APP_DEBUG=0`, `CC_WEBROOT=public`, `CC_FRANKENPHP_WORKER=/public/index.php`, `CC_POST_BUILD_HOOK="php bin/console assets:install public --no-interaction && php bin/console cache:warmup"`, `CC_PRE_RUN_HOOK="php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration"`.
- Recommandées : `CC_HEALTH_CHECK_PATH=/health`, `CC_CACHE_DEPENDENCIES=true`. Citations de la doc dans le README.
- À ne pas poser : `CC_PHP_VERSION` (ignorée par FrankenPHP, PHP 8.5.12 imposé), `CC_DISABLE_BUILD_CACHE_UPLOAD` (interdit en nano).
- Composer au build : `-n --no-dev --no-progress --no-scripts` (donc ni `cache:clear` ni `assets:install` automatiques : ils sont dans le hook de build). `vendor/` n'est pas versionné.

## Mode worker : ce que fait le code

- `symfony/runtime` 7.4 détecte `$_SERVER['FRANKENPHP_WORKER']` et utilise `FrankenPhpWorkerRunner` (le paquet `runtime/frankenphp-symfony` a été retiré). Le worker se recycle après `FRANKENPHP_LOOP_MAX` requêtes (défaut 500, `0` = jamais ; valeur non entière = plantage au boot, c'est le runtime Symfony qui valide).
- `src/Demo/WorkerStats.php` : propriétés statiques (id du worker, requêtes, démarrage) incrémentées par `WorkerRequestSubscriber` sur chaque requête principale. En worker elles persistent par thread ; en mode classique elles repartent de zéro. « Boots de Symfony » dans la rafale = réponses avec `request === 1`.
- Temps serveur = `microtime() - REQUEST_TIME_FLOAT` (inclut le boot hors worker), exposé dans `/worker` et l'en-tête `Server-Timing`.
- Aucune promesse non implémentée : pas d'Early Hints 103, pas de Mercure.

## Variables de démo

| Variable | Défaut | Effet |
|---|---|---|
| `APP_ENV` | `prod` (`.env`) | Pastille du panneau processus (dégradé si `prod`/`production`). `production` ou inconnue → kernel `prod` sans debug (`src/Kernel.php`) ; `dev` sans paquets de dev → `prod` |
| `ANNOUNCEMENT` | vide | Bandeau sur toutes les pages, y compris erreurs |
| `LATENCY_BUDGET_MS` | `10` (1–10 000) | Ligne de budget de la rafale, barres rouges au-delà, « Sous N ms » |
| `BURST_SIZE` | `40` (5–200) | Taille de la rafale |

Lues par `src/Demo/DemoSettings.php` (environnement du processus d'abord, puis `$_SERVER`/`$_ENV`), exposées à Twig en global `demo`. Valeur invalide → défaut.

Scénario : changer `ANNOUNCEMENT` ou `LATENCY_BUDGET_MS`, `clever restart`, recharger. Option : `FRANKENPHP_LOOP_MAX=10` pour voir un worker se recycler pendant la rafale ; retirer `CC_FRANKENPHP_WORKER` pour le contraste « sans worker ».

## Structure de page (accueil)

topbar → bandeau `ANNOUNCEMENT` → premier écran (« Requête n° N. », pastille du worker, boutons « Envoyer N requêtes » / « Se certifier ↓ », rafale + 4 statistiques ; à droite panneau processus : mode, PID, uptime, requêtes / recyclage, mémoire, variables, variables injectées) → `partials/cert.html.twig` (Academy, badge animé, une seule inclusion par page) → benchmark k6 (barres) → API lecture seule → déploiement → footer.

## Fichiers clés

```
src/Kernel.php                         normalisation de APP_ENV
src/Demo/WorkerStats.php               état du worker (statiques)
src/Demo/DemoSettings.php              variables de démo + plateforme
src/EventSubscriber/WorkerRequestSubscriber.php  compteur + Server-Timing
src/EventSubscriber/SecurityHeadersSubscriber.php
src/Controller/MainController.php      /, /worker, /health, /benchmark/{name}
src/Entity/Monster.php                 ApiResource Get + GetCollection
migrations/                            schéma SQLite + 7 monstres
templates/homepage/index.html.twig     accueil + JS de la rafale
templates/partials/cert.html.twig      bloc certification
templates/bundles/TwigBundle/Exception/error.html.twig
public/cc-brand.css                    kit partagé : NE PAS MODIFIER
public/demo.css                        styles propres (préfixe fp-)
scripts/smoke.sh                       13 vérifications de routes : ./scripts/smoke.sh [URL]
```

## Local

`php -S 127.0.0.1:8105 -t public` (pas de frankenphp installé) : mode classique, « Requête n° 1. ». Après modification d'un template en `prod` : `php bin/console cache:clear`.

## Vigilance

- `APP_SECRET` obligatoire en console (placeholder dans `.env`).
- `.env` reste committé (Symfony Dotenv en a besoin), secrets en console.
- Benchmark k6 : mesures locales (`https://localhost`) committées, elles comparent les modes, pas Clever Cloud.
- `X-Frame-Options: DENY` : la page ne s'affiche pas en iframe.

## Workflow

```bash
git add <fichiers>
git commit -m "description"
git push
```
