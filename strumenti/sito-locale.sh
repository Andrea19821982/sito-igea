#!/usr/bin/env bash
# WordPress locale usa e getta per provare il tema (SQLite, nessun database da installare).
# Solo per lo sviluppo: non c'entra con staging o produzione.
#
# Uso:  bash strumenti/sito-locale.sh crea  CARTELLA   scarica WordPress, installa e semina pagine di prova
#       bash strumenti/sito-locale.sh avvia CARTELLA   server su http://127.0.0.1:8080
#       bash strumenti/sito-locale.sh ferma CARTELLA
#
# CARTELLA deve stare fuori dal repository. Le email non partono: finiscono in CARTELLA/posta/.
# Poi: node strumenti/prova-sito.js http://127.0.0.1:8080/ --invia
set -euo pipefail
REPO="$(cd "$(dirname "$0")/.." && pwd)"
WP_VERSIONE="7.1.2"
SQLITE_VERSIONE="v3.0.2"
azione="${1:-}"; dir="${2:-}"
[ -n "$azione" ] && [ -n "$dir" ] || { sed -n '4,10p' "$0"; exit 2; }
mkdir -p "$dir"; dir="$(cd "$dir" && pwd)"
case "$dir/" in "$REPO"/*) echo "La cartella deve stare fuori dal repository."; exit 2;; esac

crea() {
	[ -e "$dir/wp" ] && { echo "Esiste già $dir/wp"; exit 2; }
	git clone -q --depth 1 --branch "$WP_VERSIONE" https://github.com/WordPress/WordPress.git "$dir/wp"
	git clone -q --depth 1 --branch "$SQLITE_VERSIONE" https://github.com/WordPress/sqlite-database-integration.git "$dir/sqlite"
	local p="$dir/wp/wp-content/plugins/sqlite-database-integration"
	cp -rL "$dir/sqlite/packages/plugin-sqlite-database-integration" "$p"
	sed -e "s#'{SQLITE_IMPLEMENTATION_FOLDER_PATH}'#__DIR__.'/plugins/sqlite-database-integration'#g" \
		-e "s#{SQLITE_PLUGIN}#sqlite-database-integration/load.php#g" "$p/db.copy" > "$dir/wp/wp-content/db.php"
	mkdir -p "$dir/wp/wp-content/database" "$dir/posta"
	ln -sfn "$REPO/tema/igea" "$dir/wp/wp-content/themes/igea"
	rm -rf "$dir/wp/wp-content/mu-plugins"; ln -sfn "$REPO/mu-plugins" "$dir/wp/wp-content/mu-plugins"

	# Configurazione locale: chiavi generate qui, nessuna credenziale reale.
	{
		echo "<?php"
		echo "define( 'DB_NAME', 'wordpress' ); define( 'DB_USER', '' ); define( 'DB_PASSWORD', '' ); define( 'DB_HOST', '' );"
		echo "define( 'DB_CHARSET', 'utf8mb4' ); define( 'DB_COLLATE', '' );"
		echo "define( 'DB_DIR', __DIR__ . '/wp-content/database/' ); define( 'DB_FILE', '.ht.sqlite' );"
		for k in AUTH_KEY SECURE_AUTH_KEY LOGGED_IN_KEY NONCE_KEY AUTH_SALT SECURE_AUTH_SALT LOGGED_IN_SALT NONCE_SALT; do
			echo "define( '$k', '$(php -r 'echo bin2hex(random_bytes(32));')' );"
		done
		echo "\$table_prefix = 'wp_';"
		echo "define( 'WP_DEBUG', true ); define( 'WP_DEBUG_LOG', true ); define( 'WP_DEBUG_DISPLAY', false );"
		echo "define( 'WP_ENVIRONMENT_TYPE', 'local' ); define( 'DISALLOW_FILE_EDIT', true );"
		echo "define( 'WP_HTTP_BLOCK_EXTERNAL', true ); define( 'AUTOMATIC_UPDATER_DISABLED', true );"
		echo "define( 'IGEA_MAIL_TO', 'info@example.test' ); define( 'IGEA_MAIL_STAGING', 'prova@example.test' );"
		echo "if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ . '/' ); }"
		echo "require_once ABSPATH . 'wp-settings.php';"
	} > "$dir/wp/wp-config.php"

	# Router per il server di PHP (permalink "belli").
	cat > "$dir/router.php" <<'PHP'
<?php
$root = $_SERVER['DOCUMENT_ROOT'];
$path = (string) parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
if ( '/' !== $path && is_file( $root . $path ) ) { return false; }
if ( is_dir( $root . $path ) && is_file( rtrim( $root . $path, '/' ) . '/index.php' ) ) { $_SERVER['SCRIPT_NAME'] = rtrim( $path, '/' ) . '/index.php'; require rtrim( $root . $path, '/' ) . '/index.php'; return; }
$_SERVER['SCRIPT_NAME'] = '/index.php';
require $root . '/index.php';
PHP

	# Le email vanno in un file invece di partire.
	cat > "$dir/wp/wp-content/plugins/posta-locale.php" <<PHP
<?php
/** Plugin Name: Posta locale (solo prove) */
add_filter( 'pre_wp_mail', static function ( \$nullo, array \$a ) {
	if ( null !== \$nullo ) { return \$nullo; }
	file_put_contents( '$dir/posta/' . microtime( true ) . '.json', wp_json_encode( \$a, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
	return true;
}, 99, 2 );
PHP

	# Installazione e pagine di prova.
	cat > "$dir/installa.php" <<'PHP'
<?php
define( 'WP_INSTALLING', true );
$_SERVER['HTTP_HOST'] = '127.0.0.1:8080'; $_SERVER['REQUEST_URI'] = '/';
require __DIR__ . '/wp/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/upgrade.php';
add_filter( 'pre_wp_mail', '__return_true' ); // niente email di benvenuto
$pw = bin2hex( random_bytes( 12 ) );
wp_install( 'IGEA Group', 'admin', 'admin@example.test', false, '', $pw );
file_put_contents( __DIR__ . '/accesso-locale.txt', "admin / $pw\n" );
update_option( 'siteurl', 'http://127.0.0.1:8080' ); update_option( 'home', 'http://127.0.0.1:8080' );
PHP
	cat > "$dir/semina.php" <<'PHP'
<?php
$_SERVER['HTTP_HOST'] = '127.0.0.1:8080'; $_SERVER['REQUEST_URI'] = '/';
require __DIR__ . '/wp/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
activate_plugin( 'sqlite-database-integration/load.php' );
activate_plugin( 'posta-locale.php' );
switch_theme( 'igea' );
update_option( 'permalink_structure', '/%postname%/' );
update_option( 'timezone_string', 'Europe/Rome' );
$pg = fn( $t, $s, $c, $g = 0 ) => wp_insert_post( [ 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $t, 'post_name' => $s, 'post_content' => $c, 'post_parent' => $g ] );
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $pg( 'Home', 'home', '' ) );
update_option( 'page_for_posts', $pg( 'News', 'news', '' ) );
$pg( 'Contatti', 'contatti', '<p>Scrivici per un preventivo.</p>' );
$serv = $pg( 'Servizi', 'servizi', '<p>Tutti i servizi.</p>' );
set_theme_mod( 'igea_pagina_servizi', $serv );
set_theme_mod( 'igea_pagina_sicurezza', $pg( 'Salute e sicurezza sul lavoro', 'sicurezza-sul-lavoro', "<h2>Valutazione dei rischi</h2>\n\nTesto di prova.\n\n<ul><li>DVR</li><li>RSPP esterno</li></ul>", $serv ) );
set_theme_mod( 'igea_pagina_ambiente', $pg( 'Igiene ambientale', 'igiene-ambientale', '[vc_row][vc_column][vc_custom_heading text="Pagina WPBakery" font_container="tag:h2"][vc_column_text]Testo dentro WPBakery.' . "\n\n" . 'Secondo paragrafo.[/vc_column_text][vc_btn title="Chiedi un preventivo" link="url:%2Fcontatti%2F"][/vc_column][/vc_row]', $serv ) );
set_theme_mod( 'igea_pagina_alimenti', $pg( 'Igiene degli alimenti', 'haccp', '<p>HACCP di prova.</p>', $serv ) );
set_theme_mod( 'igea_pagina_medicina', $pg( 'Medicina del lavoro', 'medicina-del-lavoro', '<p>Sorveglianza sanitaria di prova.</p>', $serv ) );
$pg( 'Formazione', 'formazione', '<p>Corsi di formazione.</p>' );
$pg( 'Cookie policy', 'cookie-policy', '<p>Testo di prova.</p>' );
update_option( 'wp_page_for_privacy_policy', $pg( 'Privacy policy', 'privacy-policy', '<p>Testo di prova.</p>' ) );
for ( $i = 1; $i <= 5; $i++ ) {
	wp_insert_post( [ 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => "Notizia di prova $i", 'post_content' => "<p>Testo della notizia $i.</p>", 'post_date' => gmdate( 'Y-m-d H:i:s', strtotime( "-$i days" ) ) ] );
}
flush_rewrite_rules( false );
PHP
	( cd "$dir" && php installa.php && php semina.php )
	echo "Creato. Accesso amministratore in $dir/accesso-locale.txt. Avvio: bash $0 avvia $dir"
}

avvia() {
	[ -f "$dir/server.pid" ] && kill -0 "$(cat "$dir/server.pid")" 2>/dev/null && { echo "Già avviato."; return; }
	( cd "$dir" && PHP_CLI_SERVER_WORKERS=4 nohup php -S 127.0.0.1:8080 -t wp router.php > server.log 2>&1 & echo $! > "$dir/server.pid" )
	echo "Sito su http://127.0.0.1:8080/ (registro: $dir/server.log)"
}

ferma() {
	[ -f "$dir/server.pid" ] && kill "$(cat "$dir/server.pid")" 2>/dev/null || true
	rm -f "$dir/server.pid"; echo "Fermato."
}

case "$azione" in crea) crea ;; avvia) avvia ;; ferma) ferma ;; *) sed -n '4,10p' "$0"; exit 2 ;; esac
