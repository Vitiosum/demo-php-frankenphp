#!/usr/bin/env bash
# Vérifie les routes de la démo : ./scripts/smoke.sh [URL]  (défaut http://127.0.0.1:8105)
# Fonctionne en local comme sur l'URL Clever Cloud de l'application.
set -euo pipefail

BASE="${1:-http://127.0.0.1:8105}"
fail=0

check() {
  local method="$1" path="$2" want="$3" grep_for="${4:-}" body code accept='text/html'
  [[ "$path" == /api/* ]] && accept='application/ld+json'
  body="$(mktemp)"
  code="$(curl -s -o "$body" -w '%{http_code}' -X "$method" -H "Accept: $accept" "$BASE$path")"
  if [[ "$code" != "$want" ]]; then
    echo "KO  $method $path → $code (attendu $want)"; fail=1
  elif [[ -n "$grep_for" ]] && ! grep -q -- "$grep_for" "$body"; then
    echo "KO  $method $path → $code mais « $grep_for » absent"; fail=1
  else
    echo "OK  $method $path → $code"
  fi
  rm -f "$body"
}

check GET  /                                  200 'Requête n°'
check GET  /                                  200 'cert-title'
check GET  /worker                            200 '"worker"'
check GET  /health                            200 '"ok"'
check GET  /api                               200 'swagger'
check GET  /api/monsters                      200 'Elephpant'
check GET  /api/monsters/1                    200 'FrankenPHP'
check POST /api/monsters                      405
check DELETE /api/monsters/1                  405
check GET  /benchmark/summary-100-vus-worker  200 'http_req_duration'
check GET  /benchmark/../.env                 404
check GET  /page-inexistante                  404 'Retour au worker'
check GET  /cc-brand.css                      200 '--cc-bg'

exit "$fail"
