<?php
/**
 * Plugin Name: IGEA – Sicurezza
 * Description: Intestazioni HTTP di sicurezza, Content Security Policy, XML-RPC spento, nomi utente non esposti, commenti chiusi.
 * Requires PHP: 8.2
 *
 * Costanti facoltative in wp-config.php:
 * - IGEA_CSP_ATTIVA (true): la CSP passa da sola segnalazione (Report-Only) a definitiva.
 *   Prima va provata in staging guardando la console del browser su tutte le pagine.
 * - IGEA_HSTS_MAX_AGE (secondi): HSTS a passi, per esempio 300, poi 86400, poi 31536000.
 * - IGEA_COMMENTI (true): riapre commenti e ping, chiusi per impostazione.
 */

defined( 'ABSPATH' ) || exit;

/** Intestazioni sulle pagine del sito (non su wp-admin). */
add_action( 'send_headers', static function (): void {
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	header( 'X-Frame-Options: SAMEORIGIN' );
	header( 'Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()' );

	if ( is_ssl() && defined( 'IGEA_HSTS_MAX_AGE' ) && (int) IGEA_HSTS_MAX_AGE > 0 ) {
		header( 'Strict-Transport-Security: max-age=' . (int) IGEA_HSTS_MAX_AGE );
	}

	// Chi è collegato ha la barra di amministrazione, che usa script in linea: niente CSP per lui.
	if ( is_user_logged_in() ) {
		return;
	}
	$csp = implode( '; ', [
		"default-src 'self'",
		"script-src 'self' 'inline-speculation-rules'",
		"style-src 'self' 'unsafe-inline'", // stili in linea del core (global-styles) e variabili CSS dei tracciati.
		"img-src 'self' data:",
		"font-src 'self'",
		"connect-src 'self'",
		"frame-src 'self'",
		"object-src 'none'",
		"base-uri 'self'",
		"form-action 'self'",
		"frame-ancestors 'self'",
	] );
	$nome = defined( 'IGEA_CSP_ATTIVA' ) && IGEA_CSP_ATTIVA ? 'Content-Security-Policy' : 'Content-Security-Policy-Report-Only';
	header( "{$nome}: {$csp}" );
} );

// XML-RPC spento: non serve al sito ed è un bersaglio per i tentativi di accesso.
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'wp_headers', static function ( array $h ): array {
	unset( $h['X-Pingback'] );
	return $h;
} );
remove_action( 'wp_head', 'rsd_link' );

// Versione di WordPress non dichiarata nelle pagine.
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );

/** Elenco utenti della REST API solo per chi scrive sul sito (l'editor lo usa per scegliere l'autore). */
add_filter( 'rest_endpoints', static function ( array $e ): array {
	if ( ! current_user_can( 'edit_posts' ) ) {
		foreach ( array_keys( $e ) as $rotta ) {
			if ( str_starts_with( $rotta, '/wp/v2/users' ) ) {
				unset( $e[ $rotta ] );
			}
		}
	}
	return $e;
} );

/**
 * Archivi per autore e ?author=N: 404 prima che il reindirizzamento canonico riveli il nome utente.
 */
add_action( 'template_redirect', static function (): void {
	if ( is_author() && ! current_user_can( 'edit_posts' ) ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
		remove_action( 'template_redirect', 'redirect_canonical' );
	}
}, 1 );

/** Gli embed degli articoli non riportano nome e pagina dell'autore. */
add_filter( 'oembed_response_data', static function ( array $d ): array {
	unset( $d['author_name'], $d['author_url'] );
	return $d;
} );

// Sitemap del core senza utenti e senza tag.
add_filter( 'wp_sitemaps_add_provider', static fn( $provider, string $nome ) => 'users' === $nome ? false : $provider, 10, 2 );
add_filter( 'wp_sitemaps_taxonomies', static function ( array $t ): array {
	unset( $t['post_tag'] );
	return $t;
} );

// Commenti e ping chiusi, salvo IGEA_COMMENTI.
if ( ! defined( 'IGEA_COMMENTI' ) || ! IGEA_COMMENTI ) {
	add_filter( 'comments_open', '__return_false', 20 );
	add_filter( 'pings_open', '__return_false', 20 );
}
