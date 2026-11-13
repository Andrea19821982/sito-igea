<?php
/**
 * Descrizione per i motori di ricerca, anteprima per i social (Open Graph) e dati strutturati.
 * Se è attivo un plugin SEO, descrizione, anteprima e percorso li gestisce lui (una sola fonte).
 */

defined( 'ABSPATH' ) || exit;

function igea_plugin_seo_attivo(): bool {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' ) || defined( 'THE_SEO_FRAMEWORK_VERSION' );
}

/**
 * Accorcia un testo alla lunghezza data senza spezzare l'ultima parola.
 */
function igea_taglia( string $testo, int $massimo ): string {
	if ( mb_strlen( $testo ) <= $massimo ) {
		return $testo;
	}
	$taglio = mb_substr( $testo, 0, $massimo - 1 );
	$spazio = mb_strrpos( $taglio, ' ' );
	if ( false !== $spazio && $spazio > $massimo / 2 ) {
		$taglio = mb_substr( $taglio, 0, $spazio );
	}
	return rtrim( $taglio, ' ,;:.-' ) . '…';
}

/**
 * Descrizione della pagina, mai inventata: il riassunto scritto a mano, altrimenti un testo già presente sul sito
 * (frase di apertura della home, testo del servizio, inizio della pagina, descrizione della categoria).
 */
function igea_descrizione(): string {
	$descrizione = '';
	if ( is_front_page() ) {
		$descrizione = get_bloginfo( 'description' );
		if ( '' === trim( (string) $descrizione ) ) {
			$descrizione = igea_testo_apertura();
		}
	} elseif ( is_singular() ) {
		$servizio = igea_servizio_pagina();
		if ( has_excerpt() || ! $servizio ) {
			$descrizione = igea_estratto( (int) get_queried_object_id(), 30 );
		} else {
			$descrizione = $servizio['testo'];
		}
	} elseif ( is_home() ) {
		$news = (int) get_option( 'page_for_posts' );
		if ( $news && has_excerpt( $news ) ) {
			$descrizione = get_the_excerpt( $news );
		}
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
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( igea_taglia( $descrizione, 160 ) ) );
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
		$tag['og:description'] = igea_taglia( $descrizione, 200 );
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

/**
 * Percorso della pagina, da Home alla pagina corrente: [[nome, indirizzo], …]. Vuoto dove non serve
 * (home, ricerca, 404). Lo usano i dati strutturati qui sotto e la riga in cima alle pagine interne.
 */
function igea_percorso(): array {
	if ( is_front_page() || is_404() || is_search() ) {
		return [];
	}
	$voci = [ [ 'Home', home_url( '/' ) ] ];
	$news = igea_url_news();
	if ( is_page() ) {
		foreach ( array_reverse( get_post_ancestors( (int) get_queried_object_id() ) ) as $antenato ) {
			if ( 'publish' === get_post_status( $antenato ) ) {
				$voci[] = [ get_the_title( $antenato ), (string) get_permalink( $antenato ) ];
			}
		}
		$voci[] = [ get_the_title( (int) get_queried_object_id() ), (string) get_permalink( (int) get_queried_object_id() ) ];
	} elseif ( is_singular( 'post' ) || is_category() || is_tag() ) {
		if ( '' !== $news ) {
			$voci[] = [ get_the_title( (int) get_option( 'page_for_posts' ) ), $news ];
		}
		if ( is_singular() ) {
			$voci[] = [ get_the_title( (int) get_queried_object_id() ), (string) get_permalink( (int) get_queried_object_id() ) ];
		} else {
			$link   = get_term_link( get_queried_object() );
			$voci[] = [ single_term_title( '', false ), is_wp_error( $link ) ? '' : (string) $link ];
		}
	} elseif ( is_home() && '' !== $news ) {
		$voci[] = [ get_the_title( (int) get_option( 'page_for_posts' ) ), $news ];
	} else {
		return [];
	}
	return array_map( static fn( array $v ): array => [ wp_strip_all_tags( (string) $v[0] ), (string) $v[1] ], $voci );
}

/** Opzioni di json_encode per i dati strutturati: nessun "</script>" può chiudere il blocco in anticipo. */
const IGEA_JSON = JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

/** Percorso (Home > Servizi > Pagina) per i risultati di Google. */
add_action( 'wp_head', static function (): void {
	$voci = igea_plugin_seo_attivo() ? [] : igea_percorso();
	if ( count( $voci ) < 2 ) {
		return;
	}
	$elenco = [];
	foreach ( $voci as $i => [ $nome, $url ] ) {
		$elenco[] = array_filter( [ '@type' => 'ListItem', 'position' => $i + 1, 'name' => $nome, 'item' => $url ] );
	}
	$dati = [ '@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $elenco ];
	echo '<script type="application/ld+json">' . wp_json_encode( $dati, IGEA_JSON ) . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- JSON con JSON_HEX_TAG.
} );

/** Articolo delle news per Google: titolo, date ed editore. L'autore è l'azienda, non il nome utente. */
add_action( 'wp_head', static function (): void {
	if ( igea_plugin_seo_attivo() || ! is_singular( 'post' ) ) {
		return;
	}
	$id      = (int) get_queried_object_id();
	$azienda = [ '@type' => 'Organization', 'name' => igea_dato( 'ragione_sociale' ), 'url' => home_url( '/' ) ];
	$dati    = [
		'@context'         => 'https://schema.org',
		'@type'            => 'Article',
		'headline'         => mb_substr( wp_strip_all_tags( get_the_title( $id ) ), 0, 110 ),
		'datePublished'    => (string) get_the_date( DATE_W3C, $id ),
		'dateModified'     => (string) get_the_modified_date( DATE_W3C, $id ),
		'mainEntityOfPage' => (string) get_permalink( $id ),
		'author'           => $azienda,
		'publisher'        => $azienda + [ 'logo' => [ '@type' => 'ImageObject', 'url' => get_theme_file_uri( 'assets/img/logo-igea-340.png' ) ] ],
	];
	$immagine = has_post_thumbnail( $id ) ? wp_get_attachment_image_src( (int) get_post_thumbnail_id( $id ), 'large' ) : false;
	if ( $immagine ) {
		$dati['image'] = $immagine[0];
	}
	$descrizione = igea_descrizione();
	if ( '' !== $descrizione ) {
		$dati['description'] = $descrizione;
	}
	echo '<script type="application/ld+json">' . wp_json_encode( $dati, IGEA_JSON ) . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- JSON con JSON_HEX_TAG.
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
