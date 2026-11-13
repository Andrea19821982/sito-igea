<?php
/**
 * Plugin Name: IGEA – Controlli della configurazione
 * Description: In Strumenti > Salute del sito dice cosa manca alla configurazione del sito IGEA: email del modulo, pagine collegate, dati aziendali, lingua, icona, fonti ufficiali, sicurezza, pagine WPBakery.
 * Requires PHP: 8.2
 *
 * Solo lettura: non cambia nulla. Dei valori sensibili dice solo se sono impostati, mai quanto valgono.
 * I controlli li vede solo chi amministra il sito.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Risultato nel formato di Salute del sito.
 *
 * @param string   $stato  good, recommended o critical.
 * @param string[] $voci   Righe dell'elenco, già in HTML sicuro.
 * @param array    $azione [ url, testo ] facoltativa.
 */
function igea_controllo( string $test, string $titolo, string $stato, string $testo, array $voci = [], array $azione = [] ): array {
	$descrizione = '<p>' . esc_html( $testo ) . '</p>';
	if ( $voci ) {
		$descrizione .= '<ul><li>' . implode( '</li><li>', $voci ) . '</li></ul>';
	}
	return [
		'test'        => $test,
		'label'       => $titolo,
		'status'      => $stato,
		'badge'       => [ 'label' => 'IGEA', 'color' => 'good' === $stato ? 'green' : ( 'critical' === $stato ? 'red' : 'orange' ) ],
		'description' => $descrizione,
		'actions'     => $azione ? sprintf( '<p><a href="%s">%s</a></p>', esc_url( $azione[0] ), esc_html( $azione[1] ) ) : '',
	];
}

/** Elenco di nomi in <code>, per le costanti. */
function igea_controllo_codici( array $nomi ): string {
	return implode( ', ', array_map( static fn( $n ) => '<code>' . esc_html( $n ) . '</code>', $nomi ) );
}

function igea_controllo_componenti(): array {
	$mancano = [];
	if ( 'igea' !== get_template() ) {
		$mancano[] = esc_html( 'Tema IGEA non attivo (Aspetto > Temi)' );
	}
	foreach ( [ 'igea-contatti.php' => 'igea_cf_elabora', 'igea-fonti.php' => 'igea_fonti_voci' ] as $file => $funzione ) {
		if ( ! function_exists( $funzione ) ) {
			$mancano[] = '<code>' . esc_html( "wp-content/mu-plugins/{$file}" ) . '</code> mancante';
		}
	}
	if ( ! defined( 'IGEA_SICUREZZA_CARICATO' ) ) {
		$mancano[] = '<code>wp-content/mu-plugins/igea-sicurezza.php</code> mancante';
	}
	return $mancano
		? igea_controllo( 'igea_componenti', 'Parti del sito IGEA mancanti', 'critical', 'Senza queste parti il modulo, le fonti o le protezioni non funzionano. Vedi docs/installazione.md, passo 2.', $mancano )
		: igea_controllo( 'igea_componenti', 'Tema e mu-plugin IGEA presenti', 'good', 'Tema attivo; modulo contatti, fonti ufficiali e sicurezza caricati.' );
}

function igea_controllo_posta(): array {
	$test = 'igea_posta';
	if ( ! defined( 'IGEA_MAIL_TO' ) || ! is_email( (string) IGEA_MAIL_TO ) ) {
		return igea_controllo( $test, 'Il modulo contatti non sa a chi scrivere', 'critical', 'Manca la casella che riceve le richieste, o non è un indirizzo valido. Le richieste vanno perse. Vedi docs/installazione.md, passo 3.', [ igea_controllo_codici( [ 'IGEA_MAIL_TO' ] ) ] );
	}
	if ( 'production' !== wp_get_environment_type() && ( ! defined( 'IGEA_MAIL_STAGING' ) || ! is_email( (string) IGEA_MAIL_STAGING ) ) ) {
		return igea_controllo( $test, 'In staging le email del modulo non partono', 'critical', 'Fuori dalla produzione le email vanno solo alla casella di prova. Senza una casella valida non parte nulla, e il modulo risponde con un errore.', [ igea_controllo_codici( [ 'IGEA_MAIL_STAGING' ] ) ] );
	}
	$mancano = array_values( array_filter( [ 'IGEA_MAIL_FROM', 'IGEA_SMTP_HOST', 'IGEA_SMTP_USER', 'IGEA_SMTP_PASS' ], static fn( $c ) => ! defined( $c ) || '' === (string) constant( $c ) ) );
	if ( $mancano ) {
		return igea_controllo( $test, 'Email del modulo senza SMTP autenticato', 'recommended', 'Il modulo funziona, ma senza SMTP e mittente sul dominio le email finiscono più facilmente nello spam. Costanti da aggiungere in wp-config.php:', [ igea_controllo_codici( $mancano ) ] );
	}
	$dove = 'production' === wp_get_environment_type() ? 'alla casella delle richieste' : 'solo alla casella di prova (staging)';
	return igea_controllo( $test, 'Email del modulo configurate', 'good', "Le richieste partono con SMTP autenticato e arrivano {$dove}." );
}

function igea_controllo_pagine(): array {
	if ( ! function_exists( 'igea_pagine_campi' ) || ! function_exists( 'igea_id_pagina' ) ) {
		return igea_controllo( 'igea_pagine', 'Pagine collegate: tema IGEA non attivo', 'recommended', 'Il controllo vale solo con il tema IGEA.' );
	}
	$mancano = [];
	foreach ( igea_pagine_campi() as $chiave => [ $etichetta ] ) {
		if ( ! igea_id_pagina( $chiave ) ) {
			$mancano[] = esc_html( $etichetta );
		}
	}
	$azione = [ admin_url( 'customize.php?autofocus[section]=igea_pagine' ), 'Apri Personalizza > Pagine collegate' ];
	return $mancano
		? igea_controllo( 'igea_pagine', 'Pagine non ancora collegate al tema', 'recommended', 'Finché non sono collegate, i link portano alle sezioni della home invece che alle pagine esistenti.', $mancano, $azione )
		: igea_controllo( 'igea_pagine', 'Pagine collegate al tema', 'good', 'Contatti, servizi, Formazione e Cookie policy puntano alle pagine esistenti.', [], $azione );
}

function igea_controllo_dati(): array {
	if ( ! function_exists( 'igea_dati_campi' ) || ! function_exists( 'igea_dato' ) ) {
		return igea_controllo( 'igea_dati', 'Dati aziendali: tema IGEA non attivo', 'recommended', 'Il controllo vale solo con il tema IGEA.' );
	}
	$vuoti = [];
	foreach ( igea_dati_campi() as $chiave => [ $etichetta ] ) {
		if ( '' === igea_dato( $chiave ) ) {
			$vuoti[] = esc_html( $etichetta );
		}
	}
	$azione = [ admin_url( 'customize.php?autofocus[section]=igea_dati' ), 'Apri Personalizza > Dati aziendali' ];
	return $vuoti
		? igea_controllo( 'igea_dati', 'Dati aziendali da completare', 'recommended', 'Questi campi sono vuoti: sul sito pubblico non compaiono, in staging mostrano un segnaposto giallo. Vanno compilati solo con dati confermati.', $vuoti, $azione )
		: igea_controllo( 'igea_dati', 'Dati aziendali completi', 'good', 'Tutti i campi di Personalizza > Dati aziendali sono compilati.', [], $azione );
}

function igea_controllo_impostazioni(): array {
	$voci = [];
	if ( ! str_starts_with( get_locale(), 'it' ) ) {
		$voci[] = esc_html( 'Lingua del sito non italiana (Impostazioni > Generali > Lingua del sito)' );
	}
	if ( ! has_site_icon() ) {
		$voci[] = esc_html( "Icona del sito mancante: nella scheda del browser compare quella di WordPress (Personalizza > Identità del sito)" );
	}
	$privacy = (int) get_option( 'wp_page_for_privacy_policy' );
	if ( ! $privacy || 'publish' !== get_post_status( $privacy ) ) {
		$voci[] = esc_html( "Pagina dell'informativa privacy non scelta o non pubblicata (Impostazioni > Privacy): il modulo la cita" );
	}
	if ( '' === (string) get_option( 'permalink_structure' ) ) {
		$voci[] = esc_html( 'Permalink semplici (?p=123): servono quelli del sito attuale (Impostazioni > Permalink)' );
	}
	if ( 'page' !== get_option( 'show_on_front' ) || ! (int) get_option( 'page_for_posts' ) ) {
		$voci[] = esc_html( 'Home statica e pagina News non impostate (Impostazioni > Lettura)' );
	}
	return $voci
		? igea_controllo( 'igea_impostazioni', 'Impostazioni di WordPress da sistemare', 'recommended', 'Controlli della guida di installazione, passo 4:', $voci )
		: igea_controllo( 'igea_impostazioni', 'Impostazioni di WordPress a posto', 'good', 'Lingua, icona, privacy, permalink, home e news sono impostati.' );
}

function igea_controllo_fonti(): array {
	if ( ! defined( 'IGEA_FONTI' ) ) {
		return igea_controllo( 'igea_fonti', 'Fonti ufficiali: mu-plugin mancante', 'recommended', 'Il riquadro "Dalle fonti ufficiali" resta nascosto.' );
	}
	$stato = get_option( 'igea_fonti_stato', [] );
	$stato = is_array( $stato ) ? $stato : [];
	$voci  = [];
	foreach ( IGEA_FONTI as $id => $f ) {
		$ok = (int) ( $stato[ $id ]['ok'] ?? 0 );
		if ( $ok < time() - 3 * DAY_IN_SECONDS ) {
			$errore = (string) ( $stato[ $id ]['errore'] ?? '' );
			$voci[] = esc_html( $f['nome'] . ': ' . ( $ok ? 'ultimo aggiornamento riuscito il ' . wp_date( 'j F Y', $ok ) : 'mai aggiornata' ) . ( '' !== $errore ? ". Errore: {$errore}" : '' ) );
		}
	}
	if ( ! wp_next_scheduled( 'igea_aggiorna_fonti' ) ) {
		$voci[] = esc_html( 'Aggiornamento automatico non pianificato: il cron di WordPress non sta girando' );
	}
	return $voci
		? igea_controllo( 'igea_fonti', 'Fonti ufficiali non aggiornate', 'recommended', 'Il sito mostra le ultime voci scaricate. Se il server non raggiunge i feed, va chiesto al fornitore di aprire le connessioni verso inail.it e lavoro.gov.it.', $voci )
		: igea_controllo( 'igea_fonti', 'Fonti ufficiali aggiornate', 'good', 'INAIL e Ministero del Lavoro aggiornati negli ultimi tre giorni.' );
}

function igea_controllo_sicurezza(): array {
	$voci       = [];
	$produzione = 'production' === wp_get_environment_type();
	if ( ! defined( 'IGEA_CSP_ATTIVA' ) || ! IGEA_CSP_ATTIVA ) {
		$voci[] = 'Content Security Policy in sola segnalazione. Dopo la prova del passo 7 della guida si rende definitiva con ' . igea_controllo_codici( [ 'IGEA_CSP_ATTIVA' ] );
	}
	if ( $produzione && is_ssl() && ( ! defined( 'IGEA_HSTS_MAX_AGE' ) || (int) IGEA_HSTS_MAX_AGE <= 0 ) ) {
		$voci[] = 'HSTS spento: si accende a passi con ' . igea_controllo_codici( [ 'IGEA_HSTS_MAX_AGE' ] ) . ' (300, poi 86400, poi 31536000)';
	}
	if ( ! defined( 'DISALLOW_FILE_EDIT' ) || ! DISALLOW_FILE_EDIT ) {
		$voci[] = 'Modifica dei file da wp-admin attiva: in wp-config.php ' . igea_controllo_codici( [ "define( 'DISALLOW_FILE_EDIT', true )" ] );
	}
	if ( defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		$voci[] = 'Errori PHP mostrati ai visitatori: ' . igea_controllo_codici( [ "define( 'WP_DEBUG_DISPLAY', false )" ] );
	}
	return $voci
		? igea_controllo( 'igea_sicurezza', 'Protezioni del sito da completare', 'recommended', 'Passi della guida di installazione ancora da fare:', $voci )
		: igea_controllo( 'igea_sicurezza', 'Protezioni del sito complete', 'good', 'Content Security Policy definitiva, modifica dei file spenta, errori nascosti' . ( $produzione && is_ssl() ? ', HSTS attivo.' : '.' ) );
}

function igea_controllo_wpbakery(): array {
	global $wpdb;
	$attivo = defined( 'WPB_VC_VERSION' ) || function_exists( 'vc_map' );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- conteggio in sola lettura, solo nella pagina Salute del sito.
	$pagine = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ('page','post') AND post_content LIKE %s ORDER BY post_title LIMIT 50", '%' . $wpdb->esc_like( '[vc_' ) . '%' ) );
	if ( ! $pagine ) {
		return igea_controllo( 'igea_wpbakery', 'Nessuna pagina costruita con WPBakery', 'good', $attivo ? 'Il plugin WPBakery si può disattivare.' : 'Nessuna pagina dipende da WPBakery.' );
	}
	$voci = array_map( static fn( $p ) => sprintf( '<a href="%s">%s</a>', esc_url( (string) get_edit_post_link( (int) $p->ID ) ), esc_html( '' !== $p->post_title ? $p->post_title : '(senza titolo)' ) ), $pagine );
	$n = count( $pagine );
	return $attivo
		? igea_controllo( 'igea_wpbakery', 1 === $n ? '1 pagina dipende da WPBakery' : "{$n} pagine dipendono da WPBakery", 'good', 'Il plugin è attivo e le disegna lui. Prima di disattivarlo, convertire queste pagine in blocchi una alla volta, confrontando i testi:', $voci )
		: igea_controllo( 'igea_wpbakery', 1 === $n ? '1 pagina WPBakery mostrata in forma semplice' : "{$n} pagine WPBakery mostrate in forma semplice", 'recommended', 'Il plugin è spento: il tema mostra testi, titoli, immagini e pulsanti di queste pagine uno sotto l\'altro. Vanno convertite in blocchi o ricontrollate:', $voci );
}

add_filter( 'site_status_tests', static function ( array $test ): array {
	$elenco = [
		'igea_componenti'   => [ 'Parti del sito IGEA', 'igea_controllo_componenti' ],
		'igea_posta'        => [ 'Email del modulo contatti', 'igea_controllo_posta' ],
		'igea_pagine'       => [ 'Pagine collegate al tema', 'igea_controllo_pagine' ],
		'igea_dati'         => [ 'Dati aziendali', 'igea_controllo_dati' ],
		'igea_impostazioni' => [ 'Impostazioni di WordPress', 'igea_controllo_impostazioni' ],
		'igea_fonti'        => [ 'Fonti ufficiali', 'igea_controllo_fonti' ],
		'igea_sicurezza'    => [ 'Protezioni del sito', 'igea_controllo_sicurezza' ],
		'igea_wpbakery'     => [ 'Pagine WPBakery', 'igea_controllo_wpbakery' ],
	];
	foreach ( $elenco as $chiave => [ $nome, $funzione ] ) {
		$test['direct'][ $chiave ] = [ 'label' => $nome, 'test' => $funzione ];
	}
	return $test;
} );
