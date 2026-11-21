#!/usr/bin/env bash
# Installs WordPress, the WordPress PHPUnit test library and WooCommerce for the test suite.
# Usage: bin/install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version] [wc-version]
# Example: bin/install-wp-tests.sh wp_test root root 127.0.0.1 latest latest

set -e

if [ $# -lt 3 ]; then
	echo "usage: $0 <db-name> <db-user> <db-pass> [db-host] [wp-version] [wc-version]"
	exit 1
fi

DB_NAME=$1
DB_USER=$2
DB_PASS=$3
DB_HOST=${4-localhost}
WP_VERSION=${5-latest}
WC_VERSION=${6-latest}

TMPDIR=${TMPDIR-/tmp}
TMPDIR=$(echo "$TMPDIR" | sed -e "s/\/$//")
WP_TESTS_DIR=${WP_TESTS_DIR-$TMPDIR/wordpress-tests-lib}
WP_CORE_DIR=${WP_CORE_DIR-$TMPDIR/wordpress}

download() {
	if command -v curl >/dev/null 2>&1; then
		curl -sL "$1" >"$2"
	else
		wget -nv -O "$2" "$1"
	fi
}

if [ "$WP_VERSION" = "latest" ]; then
	WP_TAG="trunk"
	WP_ZIP="https://wordpress.org/latest.zip"
else
	WP_TAG="tags/$WP_VERSION"
	WP_ZIP="https://wordpress.org/wordpress-$WP_VERSION.zip"
fi

install_wp() {
	if [ -d "$WP_CORE_DIR" ]; then return; fi
	mkdir -p "$WP_CORE_DIR"
	download "$WP_ZIP" "$TMPDIR/wordpress.zip"
	unzip -q "$TMPDIR/wordpress.zip" -d "$TMPDIR/wp-extract"
	mv "$TMPDIR/wp-extract/wordpress/"* "$WP_CORE_DIR"
	download https://raw.github.com/markoheijnen/wp-mysqli/master/db.php "$WP_CORE_DIR/wp-content/db.php"
}

install_test_suite() {
	if [ -d "$WP_TESTS_DIR" ]; then return; fi
	mkdir -p "$WP_TESTS_DIR"
	svn co --quiet --ignore-externals "https://develop.svn.wordpress.org/${WP_TAG}/tests/phpunit/includes/" "$WP_TESTS_DIR/includes"
	svn co --quiet --ignore-externals "https://develop.svn.wordpress.org/${WP_TAG}/tests/phpunit/data/" "$WP_TESTS_DIR/data"

	download "https://develop.svn.wordpress.org/${WP_TAG}/wp-tests-config-sample.php" "$WP_TESTS_DIR/wp-tests-config.php"
	WP_CORE_DIR_ESC=$(echo "$WP_CORE_DIR" | sed "s:/\+$::")
	sed -i.bak "s:dirname( __FILE__ ) . '/src/':'$WP_CORE_DIR_ESC/':" "$WP_TESTS_DIR/wp-tests-config.php"
	sed -i.bak "s/youremptytestdbnamehere/$DB_NAME/; s/yourusernamehere/$DB_USER/; s/yourpasswordhere/$DB_PASS/; s|localhost|${DB_HOST}|" "$WP_TESTS_DIR/wp-tests-config.php"
	rm -f "$WP_TESTS_DIR"/wp-tests-config.php.bak
}

install_db() {
	mysqladmin create "$DB_NAME" --user="$DB_USER" --password="$DB_PASS" --host="${DB_HOST%%:*}" --protocol=tcp 2>/dev/null || true
}

install_woocommerce() {
	local dir="$WP_CORE_DIR/wp-content/plugins/woocommerce"
	if [ -d "$dir" ]; then return; fi
	local zip="https://downloads.wordpress.org/plugin/woocommerce.zip"
	if [ "$WC_VERSION" != "latest" ]; then zip="https://downloads.wordpress.org/plugin/woocommerce.$WC_VERSION.zip"; fi
	download "$zip" "$TMPDIR/woocommerce.zip"
	unzip -q "$TMPDIR/woocommerce.zip" -d "$WP_CORE_DIR/wp-content/plugins"
}

install_wp
install_test_suite
install_db
install_woocommerce

echo "WP_TESTS_DIR=$WP_TESTS_DIR"
echo "WP_CORE_DIR=$WP_CORE_DIR"
