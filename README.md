# Symfony + FrankenPHP en mode worker sur Clever Cloud

Une application Symfony 7.4 servie par le **runtime FrankenPHP** de Clever Cloud (sans Dockerfile), en **mode worker** : le kernel Symfony est chargé une fois puis reste en mémoire entre les requêtes. Le premier écran le montre en direct : « Requête n° 214. » est le nombre de requêtes déjà servies par le worker qui répond, et une rafale de requêtes vers `/worker` mesure le temps serveur de chacune.

---

## Déployer sur Clever Cloud

```bash
clever create -t frankenphp demo-php-frankenphp
clever link <app_id>      # .clever.json est committé vide
clever scale --flavor nano

clever env set APP_SECRET "$(php -r 'echo bin2hex(random_bytes(32));')"
clever env set APP_ENV prod
clever env set APP_DEBUG 0
clever env set CC_WEBROOT public
clever env set CC_FRANKENPHP_WORKER /public/index.php
clever env set CC_POST_BUILD_HOOK "php bin/console assets:install public --no-interaction && php bin/console cache:warmup"
clever env set CC_PRE_RUN_HOOK "php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration"
clever env set CC_HEALTH_CHECK_PATH /health
clever env set CC_CACHE_DEPENDENCIES true

clever deploy
```

Aucun add-on : les données sont dans SQLite (`var/data.db`), recréé à chaque démarrage par `CC_PRE_RUN_HOOK` (voir *Données*).

### Variables Clever Cloud (preuve dans la doc)

| Variable | Valeur | Pourquoi | Doc |
|---|---|---|---|
| `CC_FRANKENPHP_WORKER` | `/public/index.php` | Active le mode worker | « Define the path to this script, relative to the root of your project, with the CC_FRANKENPHP_WORKER environment variable » ([FrankenPHP](https://www.clever.cloud/developers/doc/deploy/applications/frankenphp/)) ; « The worker script must be located within the webroot directory » |
| `CC_WEBROOT` | `public` | Racine web Symfony | « If you need to serve files from a specific directory, set the CC_WEBROOT environment variable » |
| `CC_POST_BUILD_HOOK` | `assets:install` + `cache:warmup` | Assets de la Swagger UI et cache Symfony construits au build, donc mis en cache | « Ran after the project is built, and before the cache archive is generated » ([référence](https://www.clever.cloud/developers/doc/develop/common-configuration/environment-variables/reference/)) |
| `CC_PRE_RUN_HOOK` | `doctrine:migrations:migrate` | Recrée la base SQLite à chaque démarrage | « This hook runs every time » ([hooks](https://www.clever.cloud/developers/doc/develop/common-configuration/build-hooks/)) |
| `CC_HEALTH_CHECK_PATH` | `/health` | Bascule seulement si l'app répond 200 | « If these paths don't return a 2xx response code, the deployment fails » |
| `CC_CACHE_DEPENDENCIES` | `true` | Réutilise les dépendances d'un build à l'autre | « Enable caching of your build dependencies to speed up following builds. » (défaut `false`) |

À **ne pas** poser :
- `CC_PHP_VERSION` : sans effet sur ce runtime, « FrankenPHP uses this version whatever the value of CC_PHP_VERSION » (FrankenPHP 1.13.1 / PHP 8.5.12 au 2026-10-21).
- `CC_PHP_COMPOSER_FLAGS` : le défaut `-n --no-dev --no-progress --no-scripts` convient ; `--optimize-autoloader` vient de `config.optimize-autoloader: true` dans `composer.json`.
- `CC_DISABLE_BUILD_CACHE_UPLOAD` : « Don't use it with nano and pico plans ».

`vendor/` n'est pas versionné (`.gitignore`) : Clever lance `composer install` à chaque build.

### Variables de démo (onglet Variables de la console)

| Variable | Défaut | Effet visible |
|---|---|---|
| `APP_ENV` | `prod` (valeur de `.env`) | Pastille d'environnement du panneau processus, dégradé Clever si `prod` ou `production`. C'est aussi l'environnement Symfony : `production` ou une valeur inconnue démarrent en `prod` sans debug (`src/Kernel.php`) |
| `ANNOUNCEMENT` | vide | Bandeau en haut de toutes les pages (160 caractères max, échappé) |
| `LATENCY_BUDGET_MS` | `10` (1 à 10 000) | Ligne pointillée de la rafale, barres hachurées rouges au-delà, compteur « Sous N ms » |
| `BURST_SIZE` | `40` (5 à 200) | Nombre de requêtes de la rafale (bouton « Envoyer N requêtes ») |

Valeur invalide ou hors bornes : défaut. Affichées aussi : `FRANKENPHP_LOOP_MAX` (recyclage du worker, défaut 500, `0` = jamais ; **une valeur non entière fait planter le runtime Symfony au démarrage**) et les variables injectées `CC_APP_NAME`, `INSTANCE_NUMBER`, `CC_PRETTY_INSTANCE_NAME`, `INSTANCE_TYPE`, `CC_COMMIT_ID`, `CC_DEPLOYMENT_ID`, `APP_ID` (« injecté au déploiement » en local).

### Scénario vidéo

1. Ouvrir l'app : « Requête n° N. », pastille `worker 3f9ac1`, la rafale part seule : médiane ~1-2 ms, **0 boot de Symfony**, 2 à 4 workers vus.
2. Recharger : le numéro monte, l'uptime aussi. Cliquer « Envoyer 40 requêtes » : le compteur grimpe en direct.
3. Console → Variables : `ANNOUNCEMENT=Maintenance ce soir à 22 h`, `LATENCY_BUDGET_MS=1`, `clever restart`, recharger : bandeau, budget plus strict (barres rouges). Le compteur repart de zéro : nouveaux processus.
4. Option : `FRANKENPHP_LOOP_MAX=10` puis rafale de 40 → un worker se recycle en pleine rafale (nouvelle couleur, « Boots de Symfony » > 0).
5. Contraste : retirer `CC_FRANKENPHP_WORKER` + restart → « sans worker », 40 scripts différents, 40 boots.
6. Descendre au bloc certification, puis au benchmark k6.

---

## Données

SQLite (`DATABASE_URL` de `.env`), aucun add-on PostgreSQL : la table `monster` (7 lignes) est recréée à chaque démarrage par les migrations, et l'API est **en lecture seule** (`GET` uniquement, écritures → 405). Pas de script de rechargement : `clever restart` remet les données à zéro. Chaque instance a sa propre base, identique (lecture seule), donc la mise à l'échelle horizontale ne pose pas de problème.

---

## Pages et routes

| Route | Contenu |
|---|---|
| `/` | Premier écran du worker (titre, rafale, panneau processus + variables), certification Academy, benchmark k6, API, déploiement |
| `/worker` | JSON de l'état du worker (`mode`, `worker`, `request`, `uptime_s`, `memory_mb`, `server_ms`, `recycle_in`…), `Cache-Control: no-store` |
| `/health` | `{"status":"ok"}` sans toucher la base |
| `/api`, `/api/monsters`, `/api/monsters/{id}` | API Platform 4.4, Swagger UI, lecture seule |
| `/benchmark/summary-(10\|100)-vus-(fpm\|no-worker\|worker)` | Rapports k6 committés |

Toutes les réponses portent `Server-Timing: app;dur=…` (temps PHP depuis l'arrivée de la requête).

---

## Local

```bash
composer install
php bin/console doctrine:migrations:migrate -n
php bin/console assets:install public
APP_ENV=production ANNOUNCEMENT="Test" LATENCY_BUDGET_MS=10 BURST_SIZE=40 php -S 127.0.0.1:8105 -t public
./scripts/smoke.sh                     # 13 vérifications de routes
php bin/console lint:twig templates && php bin/console lint:container && composer audit
```

Sous `php -S`, **le mode worker n'est pas reproduit** : chaque requête est un nouveau script (« Requête n° 1. », « sans worker », 40 boots dans la rafale). C'est aussi le contraste utile pour la vidéo. Pour le mode worker en local : `frankenphp run` (Caddyfile committé, non utilisé par Clever Cloud). Sous `php -S`, `$_SERVER` ne contient pas l'environnement : Symfony garde `APP_ENV` de `.env`, la pastille lit la vraie variable.

---

## Stack

PHP 8.5 (runtime FrankenPHP), Symfony 7.4 LTS, API Platform 4.4, Doctrine ORM 3.7 / DBAL 4.5, SQLite, Twig 3.30, Clever Brand Kit (`public/cc-brand.css`, ne pas modifier ; styles propres dans `public/demo.css`).

`Caddyfile`, `benchmark.Caddyfile` et `static-build.Dockerfile` ne servent pas sur Clever Cloud (local, k6, binaire statique optionnel).
