#!/usr/bin/env bash
# Entrypoint for the screenshot stack (compose.screenshots.yaml): builds the schema on the fresh database, starts
# FrankenPHP and runs rfs:dev:screenshots. The argument is the mode (default "all").
set -euo pipefail

bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
bin/console cache:warmup

frankenphp run --config /etc/caddy/Caddyfile > /tmp/frankenphp.log 2>&1 &

url="https://${FRONTEND_HOSTNAME}/"
for _ in $(seq 1 30); do
    code=$(curl -sk -o /dev/null -w '%{http_code}' "$url" || true)
    if [ "$code" = 200 ] || [ "$code" = 302 ]; then
        break
    fi
    sleep 1
done
if [ "$code" != 200 ] && [ "$code" != 302 ]; then
    echo "The web server didn't come up at $url (last status: $code)" >&2
    cat /tmp/frankenphp.log >&2
    exit 1
fi

exec bin/console rfs:dev:screenshots "${1:-all}"
