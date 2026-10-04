<?php
/**
 * Funzioni di supporto per i modelli: immagini, link, menu di ripiego.
 */

defined( 'ABSPATH' ) || exit;

/** Fotografia del tema in WebP con ripiego JPEG, dimensioni dichiarate per evitare salti di impaginazione. */
function igea_foto( string $nome, string $alt, int $larghezza, int $altezza, bool $differita = true ): void {
	printf(
		'<picture><source srcset="%s" type="image/webp"><img src="%s" alt="%s" width="%d" height="%d"%s></picture>',
		esc_url( get_theme_file_uri( "assets/img/{$nome}.webp" ) ),
		esc_url( get_theme_file_uri( "assets/img/{$nome}.jpg" ) ),
		esc_attr( $alt ),
		$larghezza,
		$altezza,
		$differita ? ' loading="lazy" decoding="async"' : ''
	);
}

function igea_logo( string $alt, bool $differita = false ): void {
	printf(
		'<picture><source srcset="%s" type="image/webp"><img src="%s" alt="%s" width="340" height="170"%s></picture>',
		esc_url( get_theme_file_uri( 'assets/img/logo-igea-340.webp' ) ),
		esc_url( get_theme_file_uri( 'assets/img/logo-igea-340.png' ) ),
		esc_attr( $alt ),
		$differita ? ' loading="lazy" decoding="async"' : ''
	);
}

/**
 * SVG del tema stampato in linea (serve per animarne i tratti).
 * Solo file del tema, da un elenco fisso: nessun percorso arriva dall'esterno.
 */
function igea_svg( string $nome ): void {
	$ammessi = [ 'dea' ];
	if ( ! in_array( $nome, $ammessi, true ) ) {
		return;
	}
	$file = get_theme_file_path( "assets/img/{$nome}.svg" );
	if ( is_readable( $file ) ) {
		echo file_get_contents( $file ); // phpcs:ignore WordPress.Security.EscapeOutput -- file statico del tema.
	}
}

/** Link a una sezione della home, da qualunque pagina. */
function igea_url_home( string $ancora ): string {
	return home_url( '/' ) . '#' . $ancora;
}

/** Pagina Contatti, oppure la sezione contatti della home. */
function igea_url_contatti( string $area = '' ): string {
	$url = igea_url_pagina( 'contatti' );
	if ( '' === $url ) {
		return igea_url_home( 'contatti' );
	}
	return ( '' !== $area ? add_query_arg( 'area', rawurlencode( $area ), $url ) : $url ) . '#contatti';
}

/** Dove porta "Richiedi un preventivo": in home il modulo è nella pagina stessa. */
function igea_url_preventivo(): string {
	return is_front_page() ? '#contatti' : igea_url_contatti();
}

/** Indirizzo a cui il modulo contatti invia i dati. */
function igea_url_azione_modulo(): string {
	$url = igea_url_pagina( 'contatti' );
	return '' !== $url ? $url : home_url( '/' );
}

/** Archivio delle news: la "pagina articoli" di Impostazioni > Lettura, se impostata. */
function igea_url_news(): string {
	$id = (int) get_option( 'page_for_posts' );
	return $id && 'publish' === get_post_status( $id ) ? (string) get_permalink( $id ) : '';
}

/** Voci del menu principale quando in Aspetto > Menu non ne è assegnato uno. */
function igea_voci_principali(): array {
	return [
		[ 'Servizi', igea_url_pagina( 'servizi' ) ?: igea_url_home( 'servizi' ) ],
		[ 'Formazione', igea_url_pagina( 'formazione' ) ?: igea_url_home( 'formazione' ) ],
		[ 'Chi siamo', igea_url_home( 'chi-siamo' ) ],
		[ 'News', igea_url_news() ?: igea_url_home( 'news' ) ],
		[ 'Contatti', igea_url_pagina( 'contatti' ) ?: igea_url_home( 'contatti' ) ],
	];
}

function igea_voci_piede(): array {
	return [
		[ 'Chi siamo', igea_url_home( 'chi-siamo' ) ],
		[ 'Metodo', igea_url_home( 'metodo' ) ],
		[ 'Formazione', igea_url_pagina( 'formazione' ) ?: igea_url_home( 'formazione' ) ],
		[ 'News', igea_url_news() ?: igea_url_home( 'news' ) ],
		[ 'Contatti', igea_url_pagina( 'contatti' ) ?: igea_url_home( 'contatti' ) ],
	];
}

function igea_stampa_voci( array $voci ): void {
	// Solo il percorso, per confronto: non viene mai stampato.
	$attuale = untrailingslashit( (string) strtok( (string) ( $_SERVER['REQUEST_URI'] ?? '/' ), '?' ) );
	echo '<ul>';
	foreach ( $voci as [ $etichetta, $url ] ) {
		$corrente = ! str_contains( $url, '#' ) && untrailingslashit( (string) wp_parse_url( $url, PHP_URL_PATH ) ) === $attuale;
		printf(
			'<li><a href="%s"%s>%s</a></li>',
			esc_url( $url ),
			$corrente ? ' aria-current="page"' : '',
			esc_html( $etichetta )
		);
	}
	echo '</ul>';
}

function igea_menu_ripiego(): void {
	igea_stampa_voci( igea_voci_principali() );
}

function igea_menu_piede_ripiego(): void {
	igea_stampa_voci( igea_voci_piede() );
}

/** Data breve per elenchi e testate. */
function igea_data(): string {
	return sprintf(
		'<time datetime="%s">%s</time>',
		esc_attr( get_the_date( 'Y-m-d' ) ),
		esc_html( get_the_date( 'j F Y' ) )
	);
}
