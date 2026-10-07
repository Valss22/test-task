#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

mkdir -p storage/smarty/compile storage/smarty/cache public/assets/images
chmod -R a+rwx storage public/assets/images

if [[ ! -f vendor/autoload.php ]]; then
    composer install --no-interaction --prefer-dist --no-dev
fi

exec apache2-foreground
