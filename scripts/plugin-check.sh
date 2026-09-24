#!/bin/bash
# Run WordPress.org's Plugin Check (https://wordpress.org/plugins/plugin-check/)
# against what actually ships: the repo minus .distignore, the same files the
# deploy action puts in SVN. Uses the wordpress_php74 site in the docker stack
# (../_docker-compose-to-run-on-system-boot), which has Plugin Check installed.
#
# Usage: scripts/plugin-check.sh [extra wp plugin check args]
set -eu
REPO=$(cd "$(dirname "$0")/.." && pwd)
DC=$(cd "$REPO/../_docker-compose-to-run-on-system-boot" && pwd)
BUILD=$(mktemp -d)
trap 'rm -rf "$BUILD"' EXIT

# Build the zip contents.
mkdir -p "$BUILD/simple-seo-dist"
rsync -a --exclude-from=<(grep -v -E '^(#|$)' "$REPO/.distignore") "$REPO/" "$BUILD/simple-seo-dist/"
echo "Checking: $(cd "$BUILD/simple-seo-dist" && find . -type f | sed 's|^\./||' | sort | tr '\n' ' ')"

cd "$DC"
docker run --rm -i --user 33:33 --network docker-compose-to-run-on-system-boot_default \
	--volumes-from "$(docker compose ps -q wordpress_php74)" \
	-v "$BUILD/simple-seo-dist:/var/www/html/wp-content/plugins/simple-seo-dist:ro" \
	-e WORDPRESS_DB_HOST=mariadb -e WORDPRESS_DB_USER=wp -e WORDPRESS_DB_NAME=wordpress \
	-e WORDPRESS_DB_PASSWORD="$(grep '^DB_PASSWORD=' .env | cut -d= -f2-)" \
	-e WORDPRESS_TABLE_PREFIX=wp_php74_ -e HTTP_HOST=wordpress-php74.test \
	-e WP_HOME=http://wordpress-php74.test:8299 -e WP_SITEURL=http://wordpress-php74.test:8299 \
	wordpress:cli wp plugin check simple-seo-dist --slug=simple-seo --format=table "$@" 2>/dev/null \
	|| true
