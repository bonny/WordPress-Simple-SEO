#!/bin/bash
# Smoke test Simple SEO in the Classic Editor over HTTP, against the local
# docker stack (../_docker-compose-to-run-on-system-boot).
#
# Needs Simple SEO and the Classic Editor plugin active on the site. Creates a
# page, loads its edit screen as an admin, saves the Simple SEO meta box with
# every field filled in and noindex ticked, then prints the stored meta, the front-end
# <title>, meta description and robots tags, the wp_list_pages() output, and
# any new debug.log lines. Deletes the page after.
#
# Usage: scripts/smoke-test.sh <classic|stable|php74> [title_value] [menu_value]
#   classic = wordpress_playground_classiceditor (current WP, Classic Editor always on)
#   stable  = wordpress_mariadb (PHP 8.3), php74 = wordpress_php74 (PHP 7.4)
set -u
TMP=$(mktemp -d)
trap 'rm -rf "$TMP"' EXIT
SITE=$1
TITLE_VALUE=${2-'Custom <b>SEO</b> title & "quotes"'}
MENU_VALUE=${3-'Short menu label'}
# The docker stack sits next to the repo on one machine and in ~/Projects on another.
for DC in "$(dirname "$0")/../../_docker-compose-to-run-on-system-boot" "$HOME/Projects/_docker-compose-to-run-on-system-boot"; do
	[ -d "$DC" ] && break
done
DC=$(cd "$DC" && pwd)

if [ "$SITE" = classic ]; then
	BASE=http://wp-playground-classiceditor.test:8314
	SVC=wordpress_playground_classiceditor
	wp() { (cd $DC && docker compose run --rm -T wpcli_classiceditor "$@" 2>/dev/null); }
elif [ "$SITE" = stable ]; then
	BASE=http://wordpress-stable-docker-mariadb.test:8282
	SVC=wordpress_mariadb
	wp() { (cd $DC && docker compose run --rm -T wpcli_mariadb "$@" 2>/dev/null); }
else
	BASE=http://wordpress-php74.test:8299
	SVC=wordpress_php74
	# No paired wpcli service for this site, so run a one-off wordpress:cli container.
	wp() {
		(cd $DC && docker run --rm -i --user 33:33 --network docker-compose-to-run-on-system-boot_default \
			--volumes-from "$(docker compose ps -q wordpress_php74)" \
			-e WORDPRESS_DB_HOST=mariadb -e WORDPRESS_DB_USER=wp -e WORDPRESS_DB_NAME=wordpress \
			-e WORDPRESS_DB_PASSWORD="$(grep '^DB_PASSWORD=' .env | cut -d= -f2-)" \
			-e WORDPRESS_TABLE_PREFIX=wp_php74_ -e HTTP_HOST=wordpress-php74.test \
			-e WP_HOME=$BASE -e WP_SITEURL=$BASE \
			wordpress:cli wp "$@" 2>/dev/null)
	}
fi

LOGSTART=$(cd $DC && docker compose exec -T $SVC sh -c 'wc -l < wp-content/debug.log')

ID=$(wp post create --post_type=page --post_title="Smoke original title" --post_status=publish --porcelain | tr -d '\r')
echo "page id: $ID"

JAR=$TMP/cookies.txt
wp eval '
$u = get_users( array( "role" => "administrator", "number" => 1 ) )[0];
$exp = time() + 3600;
echo AUTH_COOKIE . "=" . wp_generate_auth_cookie( $u->ID, $exp, "auth" ) . "; " . LOGGED_IN_COOKIE . "=" . wp_generate_auth_cookie( $u->ID, $exp, "logged_in" );
' > $JAR
COOKIE=$(cat $JAR)

EDIT=$(curl -s -b "$COOKIE" "$BASE/wp-admin/post.php?post=$ID&action=edit")
echo "edit screen has meta box: $(echo "$EDIT" | grep -c 'id="simple-seo"')"
echo "edit screen is classic (#titlediv): $(echo "$EDIT" | grep -c 'id="titlediv"')"
echo "php messages in edit html: $(echo "$EDIT" | grep -c -E '<b>(Warning|Notice|Deprecated|Fatal error)</b>')"

field() { echo "$EDIT" | grep -o -E "name=['\"]$1['\"][^>]*value=['\"][^'\"]*" | head -1 | sed -E 's/.*value=.//'; }
WPNONCE=$(echo "$EDIT" | grep -o -E 'id="_wpnonce" name="_wpnonce" value="[^"]*' | sed -E 's/.*value="//')
SEONONCE=$(field simple_seo_nonce)
USERID=$(field user_ID)
echo "nonces: wp=$WPNONCE seo=$SEONONCE user=$USERID"

CODE=$(curl -s -o /dev/null -w '%{http_code}' -b "$COOKIE" "$BASE/wp-admin/post.php" \
	--data-urlencode "_wpnonce=$WPNONCE" \
	--data-urlencode "_wp_http_referer=/wp-admin/post.php?post=$ID&action=edit" \
	--data-urlencode "user_ID=$USERID" \
	--data-urlencode "action=editpost" \
	--data-urlencode "originalaction=editpost" \
	--data-urlencode "post_author=$USERID" \
	--data-urlencode "post_type=page" \
	--data-urlencode "original_post_status=publish" \
	--data-urlencode "post_ID=$ID" \
	--data-urlencode "post_title=Smoke original title" \
	--data-urlencode "content=Hello" \
	--data-urlencode "post_status=publish" \
	--data-urlencode "hidden_post_status=publish" \
	--data-urlencode "visibility=public" \
	--data-urlencode "save=Update" \
	--data-urlencode "simple_seo_nonce=$SEONONCE" \
	--data-urlencode "simple_seo[title]=$TITLE_VALUE" \
	--data-urlencode "simple_seo[description]=Smoke <i>description</i> & more" \
	--data-urlencode "simple_seo[noindex_on]=1" \
	--data-urlencode "simple_seo[menu_label]=$MENU_VALUE")
echo "save http: $CODE"

echo "--- stored meta"
wp post meta list $ID --keys=_simple_seo_title,_simple_seo_description,_simple_seo_noindex,_simple_seo_use_custom_page_title,_simple_seo_custom_page_title_value,_simple_seo_use_custom_menu_label,_simple_seo_custom_menu_label_value --format=csv

echo "--- front end <title>, description, robots"
curl -sL "$BASE/?page_id=$ID" | grep -o -E "<title>[^<]*</title>|<meta name=.(description|robots).[^>]*>"

echo "--- wp_list_pages"
wp eval "wp_list_pages( array( 'include' => $ID, 'title_li' => '' ) );"
echo

echo "--- new debug.log lines"
(cd $DC && docker compose exec -T $SVC sh -c "tail -n +$((LOGSTART + 1)) wp-content/debug.log") | cut -c1-300 | sort | uniq -c | sort -rn | head -20

wp post delete $ID --force >/dev/null
