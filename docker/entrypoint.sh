#!/bin/sh
set -eu

if [ "${1:-}" = 'apache2-foreground' ]; then
    mkdir -p var/cache var/log

    project_lock_hash="$(sha256sum composer.lock | cut -d ' ' -f 1)"
    installed_lock_hash="$(cat vendor/.composer-lock-hash 2>/dev/null || true)"

    if [ ! -f vendor/autoload_runtime.php ] || [ "$project_lock_hash" != "$installed_lock_hash" ]; then
        echo 'Installing PHP dependencies...'
        composer install --no-interaction --prefer-dist --no-progress
        printf '%s' "$project_lock_hash" > vendor/.composer-lock-hash
    fi

    chown -R www-data:www-data var
fi

exec docker-php-entrypoint "$@"
