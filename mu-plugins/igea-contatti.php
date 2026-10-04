<?php
/**
 * Plugin Name: IGEA – Modulo contatti
 * Description: Gestione lato server del modulo contatti (mu-plugin: resta attivo anche se si cambia tema).
 * Requires PHP: 8.2
 *
 * Credenziali SMTP e indirizzi: solo costanti in wp-config.php, fuori dal repository.
 * Il markup del modulo è nel tema (template-parts/contatti.php).
 */

defined( 'ABSPATH' ) || exit;

const IGEA_CF_AZIONE   = 'igea_contatto';
const IGEA_CF_MIN_SEC  = 4;                // compilato in meno di 4 s: bot.
const IGEA_CF_MAX_SEC  = 2 * HOUR_IN_SECONDS;
const IGEA_CF_LIMITE   = 5;                // invii per IP (come hash) ...
const IGEA_CF_FINESTRA = HOUR_IN_SECONDS;  // ... in un'ora.
const IGEA_CF_AREE     = [
	'sicurezza'  => 'Salute e sicurezza sul lavoro',
	'ambiente'   => 'Igiene ambientale e industriale',
	'alimenti'   => 'Igiene degli alimenti',
	'medicina'   => 'Medicina del lavoro',
	'formazione' => 'Corsi di formazione',
	'altro'      => 'Non lo so ancora',
];
const IGEA_CF_PERSONE  = [
	'1-15'  => 'Da 1 a 15',
	'16-50' => 'Da 16 a 50',
	'50+'   => 'Oltre 50',
];

/** Momento di apertura del modulo, firmato: il client non può falsificarlo. */
function igea_cf_token(): string {
	$t = (string) time();
	return $t . '.' . hash_hmac( 'sha256', $t, wp_salt( 'nonce' ) );
}

/** Secondi trascorsi dall'apertura del modulo, null se il token è assente o manomesso. */
function igea_cf_eta( string $token ): ?int {
	[ $t, $firma ] = array_pad( explode( '.', $token, 2 ), 2, '' );
	if ( ! ctype_digit( $t ) || ! hash_equals( hash_hmac( 'sha256', $t, wp_salt( 'nonce' ) ), $firma ) ) {
		return null;
	}
	return time() - (int) $t;
}

/** Da stampare dentro <form method="post" action="permalink di /contatti/">. */
function igea_cf_campi_nascosti(): void {
	wp_nonce_field( IGEA_CF_AZIONE, '_igea_nonce' );
	printf( '<input type="hidden" name="igea_t" value="%s">', esc_attr( igea_cf_token() ) );
	printf( '<input type="hidden" name="igea_azione" value="%s">', esc_attr( IGEA_CF_AZIONE ) );
}

add_action( 'template_redirect', 'igea_cf_gestisci' );
function igea_cf_gestisci(): void {
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || IGEA_CF_AZIONE !== ( $_POST['igea_azione'] ?? '' ) ) {
		return;
	}
	nocache_headers();
	$esito = igea_cf_elabora( wp_unslash( $_POST ) );
	if ( true === $esito ) {
		// Post/Redirect/Get: nessun dato personale nell'URL, il ricaricamento non reinvia.
		// La pagina che ha ricevuto il modulo (Contatti, o la home se la pagina non esiste).
		// L'ancora punta al messaggio di conferma, che riceve il focus.
		$pagina = is_singular() ? get_permalink( get_queried_object_id() ) : home_url( '/' );
		wp_safe_redirect( add_query_arg( 'inviato', '1', $pagina ) . '#esito-modulo', 303 );
		exit;
	}
	// Errori: la pagina si ridisegna in questa stessa richiesta con valori, riepilogo
	// (role="alert", focus) e messaggi collegati ai campi (aria-invalid, aria-describedby).
	$GLOBALS['igea_cf_esito'] = $esito;
}

/**
 * @return true|array{errori: array<string,string>, valori: array<string,string>}
 */
function igea_cf_elabora( array $in ): true|array {
	$s = static fn( string $k ): string => is_string( $in[ $k ] ?? null ) ? $in[ $k ] : '';

	// 1. Trappola e tempo minimo: scarto silenzioso, al bot sembra riuscito.
	if ( '' !== trim( $s( 'igea_hp' ) ) ) {
		return true;
	}
	$eta = igea_cf_eta( $s( 'igea_t' ) );
	if ( null !== $eta && $eta < IGEA_CF_MIN_SEC ) {
		return true;
	}

	// 2. Sanificazione: whitelist per la select, niente a capo nei campi su una riga.
	$v = [
		'nome'      => sanitize_text_field( $s( 'nome' ) ),
		'azienda'   => sanitize_text_field( $s( 'azienda' ) ),
		'email'     => sanitize_email( $s( 'email' ) ),
		'telefono'  => preg_replace( '/[^0-9+ ().\-]/', '', $s( 'telefono' ) ),
		'area'      => array_key_exists( $s( 'area' ), IGEA_CF_AREE ) ? $s( 'area' ) : 'altro',
		'persone'   => array_key_exists( $s( 'persone' ), IGEA_CF_PERSONE ) ? $s( 'persone' ) : '',
		'messaggio' => sanitize_textarea_field( $s( 'messaggio' ) ),
		'privacy'   => '' !== $s( 'privacy' ) ? '1' : '',
	];

	// 3. Nonce o token scaduti (pagina rimasta aperta, cache): si ripropone il modulo
	//    già compilato con token nuovi, senza perdere quello che la persona ha scritto.
	if ( null === $eta || $eta > IGEA_CF_MAX_SEC || ! wp_verify_nonce( $s( '_igea_nonce' ), IGEA_CF_AZIONE ) ) {
		return [ 'errori' => [ '_modulo' => 'Il modulo è rimasto aperto a lungo: controlla i dati e premi di nuovo Invia.' ], 'valori' => $v ];
	}

	// 4. Validazione con gli stessi limiti del markup.
	$e = [];
	if ( '' === $v['nome'] || mb_strlen( $v['nome'] ) > 100 ) {
		$e['nome'] = 'Scrivi nome e cognome.';
	}
	if ( ! is_email( $v['email'] ) || mb_strlen( $v['email'] ) > 160 ) {
		$e['email'] = 'Scrivi un indirizzo email valido.';
	}
	if ( mb_strlen( $v['azienda'] ) > 120 ) {
		$e['azienda'] = 'Al massimo 120 caratteri.';
	}
	if ( mb_strlen( $v['telefono'] ) > 30 ) {
		$e['telefono'] = 'Al massimo 30 caratteri.';
	}
	if ( mb_strlen( $v['messaggio'] ) > 3000 ) {
		$e['messaggio'] = 'Al massimo 3000 caratteri.';
	}
	if ( '1' !== $v['privacy'] ) {
		$e['privacy'] = 'Conferma di aver letto l’informativa privacy.';
	}
	if ( $e ) {
		return [ 'errori' => $e, 'valori' => $v ];
	}

	// 5. Limite di invii. Solo REMOTE_ADDR (X-Forwarded-For solo dietro un proxy noto), salvato come hash.
	$chiave = 'igea_cf_' . substr( hash_hmac( 'sha256', (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ), wp_salt() ), 0, 32 );
	$n      = (int) get_transient( $chiave );
	if ( $n >= IGEA_CF_LIMITE ) {
		return [ 'errori' => [ '_modulo' => 'Troppi invii ravvicinati. Riprova fra un’ora.' ], 'valori' => $v ];
	}
	set_transient( $chiave, $n + 1, IGEA_CF_FINESTRA );

	// 6. Invio: From sul dominio del sito (SPF/DKIM allineati), Reply-To = solo l'email validata.
	if ( ! defined( 'IGEA_MAIL_TO' ) || ! is_email( IGEA_MAIL_TO ) ) {
		return [ 'errori' => [ '_modulo' => 'Invio non riuscito. Riprova più tardi.' ], 'valori' => $v ];
	}
	$quando = gmdate( 'c' );
	$corpo  = implode( "\n", [
		'Nome: ' . $v['nome'],
		'Azienda: ' . $v['azienda'],
		'Email: ' . $v['email'],
		'Telefono: ' . $v['telefono'],
		'Area: ' . IGEA_CF_AREE[ $v['area'] ],
		'Persone in azienda: ' . ( '' !== $v['persone'] ? IGEA_CF_PERSONE[ $v['persone'] ] : 'non indicato' ),
		'',
		$v['messaggio'],
		'',
		'--',
		'Presa visione informativa: sì, ' . $quando . ', versione ' . igea_cf_versione_informativa(),
	] );
	$ok = wp_mail(
		IGEA_MAIL_TO,
		'Richiesta dal sito: ' . IGEA_CF_AREE[ $v['area'] ],
		$corpo,
		[ 'Content-Type: text/plain; charset=UTF-8', 'Reply-To: ' . $v['email'] ]
	);
	if ( ! $ok ) {
		// Il dettaglio va nel log (azione wp_mail_failed), senza dati personali.
		return [ 'errori' => [ '_modulo' => 'Invio non riuscito. Riprova più tardi.' ], 'valori' => $v ];
	}

	// 7. Prova della presa visione / del consenso: nessun testo del messaggio, email solo come HMAC.
	igea_cf_registra_prova( $v['email'], $quando );
	return true;
}

/** Versione dell'informativa: data di ultima modifica della pagina privacy di WordPress. */
function igea_cf_versione_informativa(): string {
	$id = (int) get_option( 'wp_page_for_privacy_policy' );
	return $id ? (string) get_post_modified_time( 'Y-m-d', true, $id ) : 'n.d.';
}

/** Registro minimo, consultabile solo dagli amministratori, da svuotare con un cron. */
function igea_cf_registra_prova( string $email, string $quando ): void {
	wp_insert_post( [
		'post_type'   => 'igea_consenso',
		'post_status' => 'private',
		'post_title'  => hash_hmac( 'sha256', strtolower( $email ), wp_salt( 'auth' ) ),
		'meta_input'  => [
			'quando'      => $quando,
			'informativa' => igea_cf_versione_informativa(),
			'testo'       => 'Ho letto l’informativa privacy.', // testo esatto della casella
			'pagina'      => esc_url_raw( wp_get_referer() ?: '' ),
		],
	] );
}

add_action( 'init', static function (): void {
	// Solo gli amministratori lo vedono; nessuno può creare voci a mano.
	$admin = 'manage_options';
	register_post_type( 'igea_consenso', [
		'label'        => 'Registro informativa',
		'public'       => false,
		'show_ui'      => true,
		'show_in_rest' => false,
		'map_meta_cap' => false,
		'capabilities' => [
			'edit_post'              => $admin,
			'read_post'              => $admin,
			'delete_post'            => $admin,
			'edit_posts'             => $admin,
			'edit_others_posts'      => $admin,
			'delete_posts'           => $admin,
			'delete_others_posts'    => $admin,
			'delete_private_posts'   => $admin,
			'edit_private_posts'     => $admin,
			'read_private_posts'     => $admin,
			'publish_posts'          => 'do_not_allow',
			'create_posts'           => 'do_not_allow',
			'edit_published_posts'   => 'do_not_allow',
			'delete_published_posts' => 'do_not_allow',
		],
		'supports'     => [ 'title', 'custom-fields' ],
	] );
	if ( ! wp_next_scheduled( 'igea_cf_pulizia' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'igea_cf_pulizia' );
	}
} );

/** Conservazione del registro: durata [DA CONFERMARE con il titolare]. */
add_action( 'igea_cf_pulizia', static function (): void {
	$durata = defined( 'IGEA_CF_CONSERVAZIONE' ) ? IGEA_CF_CONSERVAZIONE : '24 months ago';
	$vecchi = get_posts( [
		'post_type'   => 'igea_consenso',
		'post_status' => 'private',
		'fields'      => 'ids',
		'numberposts' => 200,
		'date_query'  => [ [ 'before' => $durata ] ],
	] );
	foreach ( $vecchi as $id ) {
		wp_delete_post( $id, true );
	}
} );

/** SMTP autenticato: valori solo in wp-config.php (li inserisce Andrea). */
add_action( 'phpmailer_init', static function ( $m ): void {
	if ( ! defined( 'IGEA_SMTP_HOST' ) || ! defined( 'IGEA_SMTP_USER' ) || ! defined( 'IGEA_SMTP_PASS' ) ) {
		return;
	}
	$m->isSMTP();
	$m->Host       = IGEA_SMTP_HOST;
	$m->Port       = defined( 'IGEA_SMTP_PORT' ) ? (int) IGEA_SMTP_PORT : 587;
	$m->SMTPAuth   = true;
	$m->SMTPSecure = 465 === $m->Port ? 'ssl' : 'tls';
	$m->Username   = IGEA_SMTP_USER;
	$m->Password   = IGEA_SMTP_PASS;
} );
add_filter( 'wp_mail_from', static fn( $da ) => defined( 'IGEA_MAIL_FROM' ) ? IGEA_MAIL_FROM : $da );

/**
 * Fuori dalla produzione nessuna email esce verso indirizzi reali: va tutto a IGEA_MAIL_STAGING.
 * Se la costante manca, le email non partono affatto.
 */
add_filter( 'wp_mail', static function ( array $a ): array {
	if ( 'production' !== wp_get_environment_type() && defined( 'IGEA_MAIL_STAGING' ) ) {
		$righe        = is_array( $a['headers'] ) ? $a['headers'] : explode( "\n", str_replace( "\r\n", "\n", (string) $a['headers'] ) );
		$a['to']      = IGEA_MAIL_STAGING;
		$a['headers'] = array_values( array_filter( $righe, static fn( $h ) => ! preg_match( '/^\s*b?cc\s*:/i', (string) $h ) ) );
	}
	return $a;
} );
add_filter( 'pre_wp_mail', static function ( $esito ) {
	if ( 'production' !== wp_get_environment_type() && ! defined( 'IGEA_MAIL_STAGING' ) ) {
		return false;
	}
	return $esito;
} );
