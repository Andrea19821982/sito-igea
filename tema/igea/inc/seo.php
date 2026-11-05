<?php
/**
 * Descrizione per i motori di ricerca, anteprima per i social (Open Graph) e dati strutturati.
 * Se è attivo un plugin SEO, descrizione, anteprima e percorso li gestisce lui (una sola fonte).
 */

defined( 'ABSPATH' ) || exit;

function igea_plugin_seo_attivo(): bool {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' ) || defined( 'THE_SEO_FRAMEWORK_VERSION' );
}

/** Descrizione della pagina: riassunto scritto a mano, descrizione del sito o della categoria. Mai inventata. */
function igea_descrizione(): string {
	$descrizione = '';
	if ( is_front_page() ) {
		$descrizione = get_bloginfo( 'description' );
	} elseif ( is_singular() && has_excerpt() ) {
		$descrizione = get_the_excerpt();
	} elseif ( is_category() || is_tag() ) {
		$descrizione = term_description();
	}
	return trim( wp_strip_all_tags( (string) $descrizione ) );
}

/** Servizio collegato alla pagina in Personalizza, se c'è. */
function igea_servizio_pagina(): ?array {
	return is_page() ? ( igea_servizi()[ igea_tipo_pagina( (int) get_queried_object_id() ) ] ?? null ) : null;
}

add_action( 'wp_head', static function (): void {
	if ( igea_plugin_seo_attivo() ) {
		return;
	}
	$descrizione = igea_descrizione();
	if ( '' !== $descrizione ) {
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( mb_substr( $descrizione, 0, 160 ) ) );
	}
}, 1 );

/**
 * Anteprima quando un link del sito viene condiviso (LinkedIn, WhatsApp, Facebook, email).
 * Immagine: quella in evidenza dell'articolo, la foto del servizio o il logo su fondo bianco.
 */
add_action( 'wp_head', static function (): void {
	if ( igea_plugin_seo_attivo() || is_404() || is_search() ) {
		return;
	}
	$servizio    = igea_servizio_pagina();
	$descrizione = igea_descrizione();
	if ( '' === $descrizione && $servizio ) {
		$descrizione = $servizio['testo'];
	}
	$tag = [
		'og:locale'    => get_locale(),
		'og:site_name' => get_bloginfo( 'name' ),
		'og:type'      => is_singular( 'post' ) ? 'article' : 'website',
		'og:title'     => wp_strip_all_tags( match ( true ) {
			is_front_page() => get_bloginfo( 'name' ),
			is_singular()   => get_the_title(),
			is_home()       => get_the_title( (int) get_option( 'page_for_posts' ) ) ?: 'News',
			default         => get_the_archive_title(),
		} ),
	];
	if ( '' !== $descrizione ) {
		$tag['og:description'] = mb_substr( $descrizione, 0, 200 );
	}
	if ( is_front_page() ) {
		$tag['og:url'] = home_url( '/' );
	} elseif ( is_singular() ) {
		$tag['og:url'] = (string) wp_get_canonical_url();
	} elseif ( is_home() ) {
		$tag['og:url'] = igea_url_news();
	}
	$img = [ get_theme_file_uri( 'assets/img/condivisione.jpg' ), 1200, 630, '' ];
	if ( is_singular() && has_post_thumbnail() ) {
		$src = wp_get_attachment_image_src( (int) get_post_thumbnail_id(), 'large' );
		if ( $src ) {
			$img = [ $src[0], (int) $src[1], (int) $src[2], (string) get_post_meta( (int) get_post_thumbnail_id(), '_wp_attachment_image_alt', true ) ];
		}
	} elseif ( $servizio ) {
		[ $nome, $alt, $larghezza, $altezza ] = $servizio['foto'];
		$img = [ get_theme_file_uri( "assets/img/{$nome}.jpg" ), $larghezza, $altezza, $alt ];
	}
	$tag['og:image']        = $img[0];
	$tag['og:image:width']  = (string) $img[1];
	$tag['og:image:height'] = (string) $img[2];
	if ( '' !== $img[3] ) {
		$tag['og:image:alt'] = $img[3];
	}
	if ( is_singular( 'post' ) ) {
		$tag['article:published_time'] = (string) get_the_date( DATE_W3C );
		$tag['article:modified_time']  = (string) get_the_modified_date( DATE_W3C );
	}
	foreach ( $tag as $proprieta => $valore ) {
		if ( '' !== $valore ) {
			printf( '<meta property="%s" content="%s">' . "\n", esc_attr( $proprieta ), esc_attr( $valore ) );
		}
	}
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
}, 2 );

/** Percorso della pagina (Home > Servizi > Pagina) per i risultati di Google. Solo dati strutturati, nessun cambio grafico. */
add_action( 'wp_head', static function (): void {
	if ( igea_plugin_seo_attivo() || is_front_page() || is_404() || is_search() ) {
		return;
	}
	$voci = [ [ 'Home', home_url( '/' ) ] ];
	if ( is_page() ) {
		foreach ( array_reverse( get_post_ancestors( (int) get_queried_object_id() ) ) as $antenato ) {
			if ( 'publish' === get_post_status( $antenato ) ) {
				$voci[] = [ get_the_title( $antenato ), (string) get_permalink( $antenato ) ];
			}
		}
		$voci[] = [ get_the_title(), (string) get_permalink() ];
	} elseif ( is_singular( 'post' ) || is_category() || is_tag() ) {
		$news = (int) get_option( 'page_for_posts' );
		if ( '' !== igea_url_news() ) {
			$voci[] = [ get_the_title( $news ), igea_url_news() ];
		}
		$voci[] = is_singular() ? [ get_the_title(), (string) get_permalink() ] : [ single_term_title( '', false ), (string) get_term_link( get_queried_object() ) ];
	} elseif ( is_home() && '' !== igea_url_news() ) {
		$voci[] = [ get_the_title( (int) get_option( 'page_for_posts' ) ), igea_url_news() ];
	} else {
		return;
	}
	$elenco = [];
	foreach ( $voci as $i => [ $nome, $url ] ) {
		$elenco[] = [ '@type' => 'ListItem', 'position' => $i + 1, 'name' => wp_strip_all_tags( (string) $nome ), 'item' => $url ];
	}
	$dati = [ '@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $elenco ];
	echo '<script type="application/ld+json">' . wp_json_encode( $dati, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n";
} );

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
