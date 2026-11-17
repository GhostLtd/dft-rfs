#!/usr/bin/env bash
# Entrypoint for the screenshot stack (compose.screenshots.yaml) and the DAST stack (compose.dast.yaml): builds the
# schema on the fresh database, starts FrankenPHP, then runs rfs:dev:screenshots. The argument is the screenshot mode
# (default "all"), or "serve" to keep the web server up instead.
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

# "serve": just keep the web server up, for the DAST stack (compose.dast.yaml, bin/dast)
if [ "${1:-}" = serve ]; then
    touch /tmp/rfs-ready
    wait
    exit
fi

exec bin/console rfs:dev:screenshots "${1:-all}"
