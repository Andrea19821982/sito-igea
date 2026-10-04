<?php
/**
 * Plugin Name: IGEA – Dalle fonti ufficiali
 * Description: Titolo, data e link delle notizie di INAIL e Ministero del Lavoro, aggiornati dal cron due volte al giorno. Nessun testo copiato.
 * Requires PHP: 8.2
 *
 * Il riquadro lo disegna il tema (template-parts/fonti.php) con igea_fonti_voci().
 * Prima prova manuale: wp cron event run igea_aggiorna_fonti
 */

defined( 'ABSPATH' ) || exit;

const IGEA_FONTI = [
	'inail'  => [ 'nome' => 'INAIL', 'url' => 'https://www.inail.it/portale/it.rss.news.xml' ],
	'lavoro' => [
		'nome'   => 'Ministero del Lavoro',
		'url'    => 'https://www.lavoro.gov.it/_layouts/15/Lavoro.Web/AppPages/RSS',
		'filtro' => '/sicurezza|salute|infortun|ispettorat|formazione/i', // il feed copre anche welfare e politiche sociali
	],
];

add_action( 'init', static function (): void {
	if ( ! wp_next_scheduled( 'igea_aggiorna_fonti' ) ) {
		wp_schedule_event( time() + 300, 'twicedaily', 'igea_aggiorna_fonti' );
	}
} );

add_action( 'igea_aggiorna_fonti', 'igea_aggiorna_fonti' );
function igea_aggiorna_fonti(): void {
	include_once ABSPATH . WPINC . '/feed.php';
	$salvate = get_option( 'igea_fonti', [] );
	$salvate = is_array( $salvate ) ? $salvate : [];
	foreach ( IGEA_FONTI as $id => $f ) {
		$feed = fetch_feed( $f['url'] );
		if ( is_wp_error( $feed ) ) {
			continue; // fonte giù: restano le voci dell'ultimo aggiornamento riuscito.
		}
		$host = wp_parse_url( $f['url'], PHP_URL_HOST );
		$voci = [];
		foreach ( $feed->get_items( 0, 20 ) as $item ) {
			$titolo = trim( wp_strip_all_tags( html_entity_decode( (string) $item->get_title(), ENT_QUOTES, 'UTF-8' ) ) );
			$link   = esc_url_raw( (string) $item->get_permalink(), [ 'https' ] );
			if ( '' === $titolo || '' === $link || wp_parse_url( $link, PHP_URL_HOST ) !== $host ) {
				continue; // solo link https verso il dominio della fonte.
			}
			if ( isset( $f['filtro'] ) && ! preg_match( $f['filtro'], $titolo ) ) {
				continue;
			}
			$voci[] = [ 'fonte' => $f['nome'], 'titolo' => mb_substr( $titolo, 0, 200 ), 'link' => $link, 'data' => (int) $item->get_date( 'U' ) ];
		}
		$salvate[ $id ] = array_slice( $voci, 0, 5 );
	}
	update_option( 'igea_fonti', $salvate, false );
}

/**
 * Le voci più recenti di tutte le fonti, dalla più nuova. La pagina non contatta mai le fonti mentre si carica.
 *
 * @return list<array{fonte: string, titolo: string, link: string, data: int}>
 */
function igea_fonti_voci( int $quante = 4 ): array {
	$salvate = get_option( 'igea_fonti', [] );
	$voci    = [];
	foreach ( is_array( $salvate ) ? $salvate : [] as $lista ) {
		foreach ( is_array( $lista ) ? $lista : [] as $v ) {
			if ( is_array( $v ) && isset( $v['fonte'], $v['titolo'], $v['link'], $v['data'] ) && str_starts_with( (string) $v['link'], 'https://' ) ) {
				$voci[] = [ 'fonte' => (string) $v['fonte'], 'titolo' => (string) $v['titolo'], 'link' => (string) $v['link'], 'data' => (int) $v['data'] ];
			}
		}
	}
	usort( $voci, static fn( $a, $b ) => $b['data'] <=> $a['data'] );
	return array_slice( $voci, 0, max( 0, $quante ) );
}
