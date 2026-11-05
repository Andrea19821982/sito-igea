<?php
/**
 * Dati aziendali e pagine collegate, in Aspetto > Personalizza.
 *
 * Regola di progetto: nessun dato aziendale inventato. I campi non confermati restano vuoti:
 * in staging compare il segnaposto giallo [DA CONFERMARE], in produzione la riga non compare.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Campi dei dati aziendali: chiave => [etichetta, valore iniziale, tipo].
 * I valori iniziali sono quelli già pubblicati sul sito attuale.
 */
function igea_dati_campi(): array {
	return [
		'ragione_sociale' => [ 'Ragione sociale', 'IGEA Group S.r.l.', 'text' ],
		'via'             => [ 'Indirizzo (via e numero)', "Via Casole d'Elsa, 28", 'text' ],
		'cap'             => [ 'CAP', '00139', 'text' ],
		'citta'           => [ 'Città', 'Roma', 'text' ],
		'provincia'       => [ 'Sigla della provincia', 'RM', 'text' ],
		'piva'            => [ 'Partita IVA', '13227571000', 'text' ],
		'rea'             => [ 'Numero REA (es. RM-1234567)', '', 'text' ],
		'capitale'        => [ 'Capitale sociale (es. 10.000 € i.v.)', '', 'text' ],
		'telefono'        => [ 'Telefono', '', 'tel' ],
		'email'           => [ 'Email per i contatti', '', 'email' ],
		'pec'             => [ 'PEC', '', 'email' ],
		'tempi'           => [ 'Tempi di risposta, dopo "Ti rispondiamo entro" (es. due giorni lavorativi)', '', 'text' ],
	];
}

/** Pagine collegate: chiave => [etichetta, slug di ripiego se la scelta è vuota]. */
function igea_pagine_campi(): array {
	return [
		'contatti'   => [ 'Pagina Contatti (con il modulo)', 'contatti' ],
		'servizi'    => [ 'Pagina con tutti i servizi', '' ],
		'sicurezza'  => [ 'Servizio: Salute e sicurezza sul lavoro', '' ],
		'ambiente'   => [ 'Servizio: Igiene ambientale e industriale', '' ],
		'alimenti'   => [ 'Servizio: Igiene degli alimenti', '' ],
		'medicina'   => [ 'Servizio: Medicina del lavoro', '' ],
		'formazione' => [ 'Pagina Formazione (con la tabella delle scadenze)', 'formazione' ],
		'cookie'     => [ 'Cookie policy', 'cookie-policy' ],
	];
}

function igea_dato( string $chiave ): string {
	$campi = igea_dati_campi();
	return trim( (string) get_theme_mod( 'igea_' . $chiave, $campi[ $chiave ][1] ?? '' ) );
}

/** I segnaposto si vedono ovunque tranne che in produzione (WP_ENVIRONMENT_TYPE). */
function igea_mostra_segnaposto(): bool {
	return 'production' !== wp_get_environment_type();
}

/** Segnaposto giallo per un dato da confermare; stringa vuota in produzione. Il testo viene sempre escapato. */
function igea_dc( string $testo ): string {
	return igea_mostra_segnaposto() ? '<span class="dc">[' . esc_html( $testo ) . ']</span>' : '';
}

function igea_indirizzo(): string {
	$citta = trim( igea_dato( 'cap' ) . ' ' . igea_dato( 'citta' ) );
	if ( '' !== igea_dato( 'provincia' ) ) {
		$citta .= ' (' . igea_dato( 'provincia' ) . ')';
	}
	return implode( ' · ', array_filter( [ igea_dato( 'via' ), $citta ] ) );
}

/** ID della pagina collegata: scelta in Personalizza, altrimenti lo slug di ripiego. 0 se non c'è. */
function igea_id_pagina( string $chiave ): int {
	static $cache = [];
	if ( isset( $cache[ $chiave ] ) ) {
		return $cache[ $chiave ];
	}
	$id = absint( get_theme_mod( 'igea_pagina_' . $chiave, 0 ) );
	if ( ! $id || 'publish' !== get_post_status( $id ) || 'page' !== get_post_type( $id ) ) {
		$id   = 0;
		$slug = igea_pagine_campi()[ $chiave ][1] ?? '';
		$p    = '' !== $slug ? get_page_by_path( $slug ) : null;
		if ( $p && 'publish' === $p->post_status ) {
			$id = (int) $p->ID;
		}
	}
	return $cache[ $chiave ] = $id;
}

function igea_url_pagina( string $chiave ): string {
	$id = igea_id_pagina( $chiave );
	return $id ? (string) get_permalink( $id ) : '';
}

/** Che ruolo ha questa pagina nel sito: 'contatti', 'formazione', un servizio, 'cookie' o ''. */
function igea_tipo_pagina( int $id ): string {
	foreach ( array_keys( igea_pagine_campi() ) as $chiave ) {
		if ( $id && igea_id_pagina( $chiave ) === $id ) {
			return $chiave;
		}
	}
	return '';
}

add_action( 'customize_register', static function ( WP_Customize_Manager $c ): void {
	$c->add_section( 'igea_dati', [
		'title'       => 'Dati aziendali',
		'description' => 'Compaiono nel piè di pagina, nei contatti e nei dati strutturati per i motori di ricerca. Lascia vuoto ciò che non è confermato.',
		'priority'    => 30,
	] );
	foreach ( igea_dati_campi() as $chiave => [ $etichetta, $iniziale, $tipo ] ) {
		$c->add_setting( 'igea_' . $chiave, [
			'default'           => $iniziale,
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => match ( $tipo ) {
				'email' => 'sanitize_email',
				'tel'   => static fn( $v ) => mb_substr( (string) preg_replace( '/[^0-9+ ().\-]/', '', (string) $v ), 0, 30 ),
				default => static fn( $v ) => mb_substr( sanitize_text_field( (string) $v ), 0, 120 ),
			},
		] );
		$c->add_control( 'igea_' . $chiave, [ 'label' => $etichetta, 'section' => 'igea_dati', 'type' => $tipo ] );
	}

	$c->add_section( 'igea_pagine', [
		'title'       => 'Pagine collegate',
		'description' => 'Le pagine esistenti a cui portano i pulsanti del sito. Gli indirizzi delle pagine non cambiano.',
		'priority'    => 31,
	] );
	foreach ( igea_pagine_campi() as $chiave => [ $etichetta ] ) {
		$c->add_setting( 'igea_pagina_' . $chiave, [
			'default'           => 0,
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'absint',
		] );
		$c->add_control( 'igea_pagina_' . $chiave, [ 'label' => $etichetta, 'section' => 'igea_pagine', 'type' => 'dropdown-pages', 'allow_addition' => false ] );
	}
} );
