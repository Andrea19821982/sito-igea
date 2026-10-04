<?php
/**
 * Descrizione per i motori di ricerca e dati strutturati.
 * Se è attivo un plugin SEO, la descrizione la gestisce lui (una sola fonte).
 */

defined( 'ABSPATH' ) || exit;

function igea_plugin_seo_attivo(): bool {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' ) || defined( 'THE_SEO_FRAMEWORK_VERSION' );
}

add_action( 'wp_head', static function (): void {
	if ( igea_plugin_seo_attivo() ) {
		return;
	}
	$descrizione = '';
	if ( is_front_page() ) {
		$descrizione = get_bloginfo( 'description' );
	} elseif ( is_singular() && has_excerpt() ) {
		$descrizione = get_the_excerpt();
	} elseif ( is_category() || is_tag() ) {
		$descrizione = term_description();
	}
	$descrizione = trim( wp_strip_all_tags( (string) $descrizione ) );
	if ( '' !== $descrizione ) {
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( mb_substr( $descrizione, 0, 160 ) ) );
	}
}, 1 );

/** ProfessionalService solo con i dati compilati in Personalizza: niente di inventato. */
add_action( 'wp_head', static function (): void {
	if ( ! is_front_page() ) {
		return;
	}
	$dati = [
		'@context' => 'https://schema.org',
		'@type'    => 'ProfessionalService',
		'name'     => igea_dato( 'ragione_sociale' ),
		'url'      => home_url( '/' ),
		'logo'     => get_theme_file_uri( 'assets/img/logo-igea-340.png' ),
		'image'    => get_theme_file_uri( 'assets/img/logo-igea-340.png' ),
	];
	if ( '' !== igea_dato( 'via' ) ) {
		$dati['address'] = array_filter( [
			'@type'           => 'PostalAddress',
			'streetAddress'   => igea_dato( 'via' ),
			'postalCode'      => igea_dato( 'cap' ),
			'addressLocality' => igea_dato( 'citta' ),
			'addressRegion'   => igea_dato( 'provincia' ),
			'addressCountry'  => 'IT',
		] );
	}
	if ( '' !== igea_dato( 'piva' ) ) {
		$dati['vatID'] = 'IT' . igea_dato( 'piva' );
	}
	if ( '' !== igea_dato( 'telefono' ) ) {
		$dati['telephone'] = igea_dato( 'telefono' );
	}
	if ( '' !== igea_dato( 'email' ) ) {
		$dati['email'] = igea_dato( 'email' );
	}
	// JSON_HEX_TAG: nessun "</script>" può chiudere il blocco in anticipo.
	echo '<script type="application/ld+json">' . wp_json_encode( $dati, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n";
} );
