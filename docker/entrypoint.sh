#!/bin/sh
# Punto de entrada común a los contenedores app / scheduler / queue.
#
# - Espera a la base de datos.
# - Sólo el rol "app" ejecuta migraciones y cachea configuración/rutas/vistas,
#   para que scheduler y queue no compitan por hacerlo a la vez.
# - Luego lanza el comando recibido (php-fpm, schedule, queue:work…).
set -e

ROLE="${CONTAINER_ROLE:-app}"

wait_for_db() {
    echo "DSLE: esperando a la base de datos ${DB_HOST:-db}:${DB_PORT:-3306}…"
    tries=0
    until php -r '
        $h=getenv("DB_HOST")?:"db"; $p=getenv("DB_PORT")?:3306;
        $c=@fsockopen($h,(int)$p,$e,$s,2); exit($c?0:1);
    ' 2>/dev/null; do
        tries=$((tries + 1))
        [ "$tries" -ge 30 ] && echo "DSLE: la base de datos no responde." && exit 1
        sleep 2
    done
}

wait_for_db

if [ "$ROLE" = "app" ]; then
    php artisan migrate --force --no-interaction
    php artisan storage:link || true
    php artisan optimize
else
    # scheduler / queue: sólo cachean, sin migrar.
    php artisan config:cache
    php artisan route:cache
    php artisan event:cache
fi

echo "DSLE: arrancando rol '${ROLE}' -> $*"
exec "$@"
