<?php
/**
 * Rete di sicurezza per le pagine costruite con WPBakery.
 *
 * Se il plugin WPBakery è attivo non fa nulla: le pagine le disegna lui.
 * Se il plugin viene disattivato, senza questo file le pagine mostrerebbero
 * i codici [vc_row], [vc_column] e simili come testo. Qui i codici più comuni
 * diventano HTML semplice (titoli, testi, immagini, pulsanti) e quelli
 * sconosciuti spariscono lasciando il loro contenuto.
 *
 * Non sostituisce la conversione in blocchi: serve solo a non perdere il testo.
 */

defined( 'ABSPATH' ) || exit;

function igea_wpbakery_attivo(): bool {
	return defined( 'WPB_VC_VERSION' ) || function_exists( 'vc_map' );
}

/**
 * Legge il formato dei link di WPBakery: "url:https%3A%2F%2F...|title:Testo|target:_blank".
 *
 * @return array{url:string,title:string,target:string}
 */
function igea_wpbakery_link( string $valore ): array {
	$link = [ 'url' => '', 'title' => '', 'target' => '' ];
	foreach ( explode( '|', $valore ) as $parte ) {
		$coppia = explode( ':', $parte, 2 );
		if ( 2 === count( $coppia ) && array_key_exists( $coppia[0], $link ) ) {
			$link[ $coppia[0] ] = trim( rawurldecode( $coppia[1] ) );
		}
	}
	return $link;
}

function igea_wpbakery_pulsante( string $url, string $testo, string $target = '' ): string {
	$url = esc_url( $url );
	if ( '' === $url || '' === trim( $testo ) ) {
		return '';
	}
	$nuova = '_blank' === trim( $target );
	return sprintf(
		'<p><a class="btn btn-bordo" href="%s"%s>%s%s</a></p>',
		$url,
		$nuova ? ' target="_blank" rel="noopener noreferrer"' : '',
		esc_html( $testo ),
		$nuova ? '<span class="vh"> (si apre in una nuova scheda)</span>' : ''
	);
}

function igea_wpbakery_ha_codici( string $html ): bool {
	return false !== strpos( $html, '[vc_' ) || false !== strpos( $html, '[/vc_' );
}

/** Una pagina costruita con WPBakery comincia sempre con una riga o una sezione. */
function igea_wpbakery_pagina( string $html ): bool {
	return 1 === preg_match( '#\[vc_(?:row|section)[\s\]]#', $html );
}

/** Testo di un blocco: paragrafi automatici solo qui dentro, poi gli eventuali codici interni. */
function igea_wpbakery_testo( string $contenuto ): string {
	return do_shortcode( shortcode_unautop( wpautop( trim( $contenuto ) ) ) );
}

function igea_wpbakery_immagine( int $id, string $dimensione = 'large' ): string {
	if ( $id <= 0 || ! wp_attachment_is_image( $id ) ) {
		return '';
	}
	return wp_get_attachment_image( $id, $dimensione, false, [ 'loading' => 'lazy', 'decoding' => 'async' ] );
}

add_action( 'init', static function (): void {
	if ( igea_wpbakery_attivo() ) {
		return;
	}

	// Contenitori: restituiscono solo il loro contenuto.
	$contenitori = [
		'vc_row', 'vc_row_inner', 'vc_column', 'vc_column_inner', 'vc_section',
		'vc_tta_tabs', 'vc_tta_tour', 'vc_tta_accordion', 'vc_tta_pageable', 'vc_accordion', 'vc_tabs', 'vc_tour',
	];
	foreach ( $contenitori as $codice ) {
		add_shortcode( $codice, static fn( $a, $contenuto = '' ): string => do_shortcode( (string) $contenuto ) );
	}

	// Blocchi di testo: i paragrafi si formano qui, come fa WPBakery.
	foreach ( [ 'vc_column_text', 'vc_wp_text' ] as $codice ) {
		add_shortcode( $codice, static fn( $a, $contenuto = '' ): string => igea_wpbakery_testo( (string) $contenuto ) );
	}

	// Sezioni con titolo (schede, fisarmoniche, interruttori).
	$con_titolo = static function ( $a, $contenuto = '' ): string {
		$a      = shortcode_atts( [ 'title' => '' ], $a );
		$titolo = '' !== trim( $a['title'] ) ? '<h3>' . esc_html( $a['title'] ) . '</h3>' : '';
		return $titolo . igea_wpbakery_testo( (string) $contenuto );
	};
	foreach ( [ 'vc_tta_section', 'vc_accordion_tab', 'vc_tab', 'vc_toggle' ] as $codice ) {
		add_shortcode( $codice, $con_titolo );
	}

	add_shortcode( 'vc_custom_heading', static function ( $a ): string {
		$a   = shortcode_atts( [ 'text' => '', 'font_container' => '' ], $a );
		$tag = 'h2';
		if ( preg_match( '/(?:^|\|)tag:(h[1-6]|p|div)/', $a['font_container'], $m ) ) {
			$tag = 'h1' === $m[1] ? 'h2' : $m[1];
		}
		return '' === trim( $a['text'] ) ? '' : sprintf( '<%1$s>%2$s</%1$s>', $tag, esc_html( $a['text'] ) );
	} );

	add_shortcode( 'vc_text_separator', static function ( $a ): string {
		$a = shortcode_atts( [ 'title' => '' ], $a );
		return '' === trim( $a['title'] ) ? '<hr>' : '<h2>' . esc_html( $a['title'] ) . '</h2>';
	} );
	add_shortcode( 'vc_separator', static fn(): string => '<hr>' );
	add_shortcode( 'vc_empty_space', static fn(): string => '' );

	add_shortcode( 'vc_single_image', static function ( $a ): string {
		$a = shortcode_atts( [ 'image' => 0, 'img_size' => 'large' ], $a );
		$dimensione = in_array( $a['img_size'], [ 'thumbnail', 'medium', 'medium_large', 'large', 'full' ], true ) ? $a['img_size'] : 'large';
		$img = igea_wpbakery_immagine( (int) $a['image'], $dimensione );
		return '' === $img ? '' : '<figure>' . $img . '</figure>';
	} );

	$galleria = static function ( $a ): string {
		$a   = shortcode_atts( [ 'images' => '' ], $a );
		$out = '';
		foreach ( array_filter( array_map( 'intval', explode( ',', $a['images'] ) ) ) as $id ) {
			$out .= igea_wpbakery_immagine( $id, 'medium_large' );
		}
		return '' === $out ? '' : '<div class="vc-galleria">' . $out . '</div>';
	};
	add_shortcode( 'vc_gallery', $galleria );
	add_shortcode( 'vc_images_carousel', $galleria );

	add_shortcode( 'vc_btn', static function ( $a ): string {
		$a    = shortcode_atts( [ 'title' => '', 'link' => '' ], $a );
		$link = igea_wpbakery_link( $a['link'] );
		return igea_wpbakery_pulsante( $link['url'], $a['title'], $link['target'] );
	} );
	add_shortcode( 'vc_button2', static function ( $a ): string {
		$a    = shortcode_atts( [ 'title' => '', 'link' => '' ], $a );
		$link = igea_wpbakery_link( $a['link'] );
		return igea_wpbakery_pulsante( $link['url'], $a['title'], $link['target'] );
	} );
	add_shortcode( 'vc_button', static function ( $a ): string {
		$a = shortcode_atts( [ 'title' => '', 'href' => '', 'target' => '' ], $a );
		return igea_wpbakery_pulsante( $a['href'], $a['title'], $a['target'] );
	} );

	add_shortcode( 'vc_cta', static function ( $a, $contenuto = '' ): string {
		$a    = shortcode_atts( [ 'h2' => '', 'h4' => '', 'btn_title' => '', 'btn_link' => '' ], $a );
		$link = igea_wpbakery_link( $a['btn_link'] );
		$out  = '' !== trim( $a['h2'] ) ? '<h2>' . esc_html( $a['h2'] ) . '</h2>' : '';
		$out .= '' !== trim( $a['h4'] ) ? '<p><strong>' . esc_html( $a['h4'] ) . '</strong></p>' : '';
		return $out . do_shortcode( (string) $contenuto ) . igea_wpbakery_pulsante( $link['url'], $a['btn_title'], $link['target'] );
	} );

	add_shortcode( 'vc_message', static fn( $a, $contenuto = '' ): string => '<div class="vc-messaggio">' . igea_wpbakery_testo( (string) $contenuto ) . '</div>' );

	add_shortcode( 'vc_video', static function ( $a ): string {
		$a = shortcode_atts( [ 'link' => '', 'title' => '' ], $a );
		return igea_wpbakery_pulsante( $a['link'], '' !== trim( $a['title'] ) ? $a['title'] : 'Guarda il video', '_blank' );
	} );

	// HTML grezzo: WPBakery lo salva in base64. Passa dal filtro di WordPress per i contenuti.
	add_shortcode( 'vc_raw_html', static function ( $a, $contenuto = '' ): string {
		$html = base64_decode( trim( wp_strip_all_tags( (string) $contenuto ) ), true );
		return false === $html ? '' : wp_kses_post( rawurldecode( $html ) );
	} );
	// JavaScript grezzo: mai stampato (e la Content Security Policy lo bloccherebbe comunque).
	add_shortcode( 'vc_raw_js', static fn(): string => '' );
}, 20 );

/**
 * Sulle pagine WPBakery i paragrafi automatici di WordPress (wpautop) avvolgerebbero
 * in <p> anche titoli e pulsanti: si spengono per questa pagina e si riaccendono subito dopo,
 * come fa WordPress con i blocchi. I paragrafi li forma igea_wpbakery_testo().
 */
add_filter( 'the_content', static function ( string $html ): string {
	if ( igea_wpbakery_attivo() || ! igea_wpbakery_pagina( $html ) ) {
		return $html;
	}
	$priorita = has_filter( 'the_content', 'wpautop' );
	if ( false !== $priorita ) {
		remove_filter( 'the_content', 'wpautop', $priorita );
		$ripristina = static function ( string $h ) use ( $priorita, &$ripristina ): string {
			add_filter( 'the_content', 'wpautop', $priorita );
			remove_filter( 'the_content', $ripristina, 13 );
			return $h;
		};
		add_filter( 'the_content', $ripristina, 13 );
	}
	return $html;
}, 8 );

/**
 * Codici WPBakery non riconosciuti: si tolgono le parentesi e resta il contenuto.
 * Gira dopo do_shortcode (priorità 11).
 */
add_filter( 'the_content', static function ( string $html ): string {
	if ( igea_wpbakery_attivo() || ! igea_wpbakery_ha_codici( $html ) ) {
		return $html;
	}
	$html = preg_replace( '#\[/?vc_[a-z0-9_]*(?:\s[^\]]*)?\]#i', '', $html );
	return preg_replace( '#<p>(?:\s|&nbsp;|<br\s*/?>)*</p>#i', '', (string) $html );
}, 12 );
