<?php
/**
 * Sezione Contatti con il modulo: in home e nella pagina Contatti.
 *
 * Il modulo lo gestisce il mu-plugin igea-contatti.php (nonce, tempo firmato, campo trappola,
 * limite di invii). Dopo un invio con errori il mu-plugin lascia in $GLOBALS['igea_cf_esito']
 * gli errori e i valori, che qui vengono riproposti con il riepilogo accessibile.
 * Riepilogo e conferma hanno id "esito-modulo": l'ancora dell'indirizzo li porta in vista
 * e dà loro il focus anche senza JavaScript.
 */

defined( 'ABSPATH' ) || exit;

$igea_attivo  = function_exists( 'igea_cf_campi_nascosti' ) && defined( 'IGEA_CF_AREE' ) && defined( 'IGEA_CF_PERSONE' );
$igea_esito   = isset( $GLOBALS['igea_cf_esito'] ) && is_array( $GLOBALS['igea_cf_esito'] ) ? $GLOBALS['igea_cf_esito'] : null;
$igea_err     = $igea_esito ? (array) ( $igea_esito['errori'] ?? [] ) : [];
$igea_val     = $igea_esito ? (array) ( $igea_esito['valori'] ?? [] ) : [];
$igea_inviato = ! $igea_esito && isset( $_GET['inviato'] ) && '1' === $_GET['inviato']; // phpcs:ignore WordPress.Security.NonceVerification -- solo lettura, confronto esatto.
$igea_aree    = defined( 'IGEA_CF_AREE' ) ? IGEA_CF_AREE : [];
$igea_persone = defined( 'IGEA_CF_PERSONE' ) ? IGEA_CF_PERSONE : [];

// Area preselezionata da un link (?area=formazione), solo se è fra quelle ammesse.
if ( ! isset( $igea_val['area'] ) && isset( $_GET['area'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
	$igea_a = sanitize_key( wp_unslash( $_GET['area'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	if ( isset( $igea_aree[ $igea_a ] ) ) {
		$igea_val['area'] = $igea_a;
	}
}

$igea_v = static fn( string $k ): string => is_string( $igea_val[ $k ] ?? null ) ? $igea_val[ $k ] : '';

/** Attributi di accessibilità del campo: aria-invalid e il collegamento al messaggio d'errore. */
$igea_attr = static function ( string $campo, string $descrizione = '' ) use ( $igea_err ): string {
	$ids = trim( $descrizione . ( isset( $igea_err[ $campo ] ) ? ' err-' . $campo : '' ) );
	return ( isset( $igea_err[ $campo ] ) ? ' aria-invalid="true"' : '' ) . ( '' !== $ids ? ' aria-describedby="' . esc_attr( $ids ) . '"' : '' );
};
$igea_errore = static function ( string $campo ) use ( $igea_err ): string {
	return isset( $igea_err[ $campo ] ) ? '<span class="errore" id="err-' . esc_attr( $campo ) . '">' . esc_html( $igea_err[ $campo ] ) . '</span>' : '';
};

$igea_tel   = igea_dato( 'telefono' );
$igea_email = igea_dato( 'email' );
$igea_pec   = igea_dato( 'pec' );
$igea_tempi = igea_dato( 'tempi' );

/** Righe dei recapiti: [etichetta, HTML già escapato]. Vuote: segnaposto in staging, niente in produzione. */
$igea_righe = [
	[ 'Sede', esc_html( igea_indirizzo() ) ],
	[ 'Telefono', '' !== $igea_tel ? '<a href="' . esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $igea_tel ) ) . '">' . esc_html( $igea_tel ) . '</a>' : '' ],
	[ 'Email', '' !== $igea_email ? '<a href="' . esc_url( 'mailto:' . antispambot( $igea_email ) ) . '">' . esc_html( antispambot( $igea_email ) ) . '</a>' : '' ],
	[ 'PEC', '' !== $igea_pec ? esc_html( antispambot( $igea_pec ) ) : '' ],
];

$igea_etichette = [
	'nome'      => 'Nome e cognome',
	'azienda'   => 'Azienda',
	'email'     => 'Email',
	'telefono'  => 'Telefono',
	'messaggio' => 'Messaggio',
	'privacy'   => 'Informativa privacy',
];
$igea_privacy = get_privacy_policy_url();
?>
<section class="contatti" id="contatti" aria-labelledby="t-contatti">
	<svg class="onde alto" aria-hidden="true" viewBox="0 0 720 420"></svg>
	<svg class="onde basso" aria-hidden="true" viewBox="0 0 720 420"></svg>
	<div class="wrap">
		<div class="rivela">
			<span class="eyebrow">Contatti</span>
			<h2 id="t-contatti">Parliamo della tua <em>azienda.</em></h2>
			<dl>
				<?php foreach ( $igea_righe as [ $igea_etichetta, $igea_html ] ) : ?>
					<?php if ( '' !== $igea_html || igea_mostra_segnaposto() ) : ?>
						<div><dt><?php echo esc_html( $igea_etichetta ); ?></dt><dd><?php echo '' !== $igea_html ? $igea_html : igea_dc( 'DA CONFERMARE' ); // phpcs:ignore WordPress.Security.EscapeOutput -- escapato sopra. ?></dd></div>
					<?php endif; ?>
				<?php endforeach; ?>
			</dl>
		</div>

		<?php if ( $igea_inviato ) : ?>
			<div class="inviato" id="esito-modulo" role="status" tabindex="-1" data-focus-avviso>
				<h3>Richiesta inviata.</h3>
				<p>Grazie, abbiamo ricevuto il tuo messaggio.<?php if ( '' !== $igea_tempi ) : ?> Ti rispondiamo entro <?php echo esc_html( $igea_tempi ); ?>.<?php endif; ?></p>
				<a class="link-fr" href="<?php echo esc_url( remove_query_arg( [ 'inviato', 'area' ] ) ); ?>#contatti">Invia un'altra richiesta</a>
			</div>

		<?php elseif ( $igea_attivo ) : ?>
			<form id="modulo" class="<?php echo $igea_esito ? '' : 'rivela'; ?>" method="post" action="<?php echo esc_url( igea_url_azione_modulo() ); ?>#esito-modulo" novalidate>
				<?php if ( $igea_err ) : ?>
					<div class="riepilogo-errori" id="esito-modulo" role="alert" tabindex="-1" data-focus-avviso>
						<p>Controlla il modulo:</p>
						<ul>
							<?php foreach ( $igea_err as $igea_campo => $igea_testo ) : ?>
								<?php if ( isset( $igea_etichette[ $igea_campo ] ) ) : ?>
									<li><a href="#<?php echo esc_attr( $igea_campo ); ?>"><?php echo esc_html( $igea_etichette[ $igea_campo ] . ': ' . $igea_testo ); ?></a></li>
								<?php else : ?>
									<li><?php echo esc_html( $igea_testo ); ?></li>
								<?php endif; ?>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

				<div class="campo"><label for="nome">Nome e cognome</label><input id="nome" name="nome" autocomplete="name" required maxlength="100" value="<?php echo esc_attr( $igea_v( 'nome' ) ); ?>"<?php echo $igea_attr( 'nome' ); // phpcs:ignore ?>><?php echo $igea_errore( 'nome' ); // phpcs:ignore ?></div>
				<div class="campo"><label for="azienda">Azienda <span class="facolt">(facoltativo)</span></label><input id="azienda" name="azienda" autocomplete="organization" maxlength="120" value="<?php echo esc_attr( $igea_v( 'azienda' ) ); ?>"<?php echo $igea_attr( 'azienda' ); // phpcs:ignore ?>><?php echo $igea_errore( 'azienda' ); // phpcs:ignore ?></div>
				<div class="campo"><label for="email">Email</label><input id="email" name="email" type="email" autocomplete="email" required maxlength="160" value="<?php echo esc_attr( $igea_v( 'email' ) ); ?>"<?php echo $igea_attr( 'email' ); // phpcs:ignore ?>><?php echo $igea_errore( 'email' ); // phpcs:ignore ?></div>
				<div class="campo"><label for="telefono">Telefono <span class="facolt">(facoltativo)</span></label><input id="telefono" name="telefono" type="tel" autocomplete="tel" maxlength="30" value="<?php echo esc_attr( $igea_v( 'telefono' ) ); ?>"<?php echo $igea_attr( 'telefono' ); // phpcs:ignore ?>><?php echo $igea_errore( 'telefono' ); // phpcs:ignore ?></div>
				<div class="campo"><label for="area">Di cosa hai bisogno</label>
					<select id="area" name="area">
						<option value="">Scegli un'area</option>
						<?php foreach ( $igea_aree as $igea_chiave => $igea_nome ) : ?>
							<option value="<?php echo esc_attr( $igea_chiave ); ?>"<?php selected( $igea_v( 'area' ), $igea_chiave ); ?>><?php echo esc_html( $igea_nome ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="campo"><label for="persone">Quante persone lavorano in azienda <span class="facolt">(facoltativo)</span></label>
					<select id="persone" name="persone">
						<option value="">Scegli</option>
						<?php foreach ( $igea_persone as $igea_chiave => $igea_nome ) : ?>
							<option value="<?php echo esc_attr( $igea_chiave ); ?>"<?php selected( $igea_v( 'persone' ), $igea_chiave ); ?>><?php echo esc_html( $igea_nome ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="campo largo"><label for="messaggio">Messaggio <span class="facolt">(facoltativo)</span></label><textarea id="messaggio" name="messaggio" maxlength="3000" placeholder="Es.: 12 dipendenti, officina meccanica, DVR da aggiornare"<?php echo $igea_attr( 'messaggio', 'nota-sanitari' ); // phpcs:ignore ?>><?php echo esc_textarea( $igea_v( 'messaggio' ) ); ?></textarea><span class="facolt" id="nota-sanitari">Non inserire dati sanitari.</span><?php echo $igea_errore( 'messaggio' ); // phpcs:ignore ?></div>
				<div class="trappola" aria-hidden="true"><label for="igea-hp">Lascia vuoto questo campo</label><input id="igea-hp" name="igea_hp" tabindex="-1" autocomplete="off"></div>
				<label class="consenso" for="privacy"><input id="privacy" name="privacy" type="checkbox" value="1" required<?php checked( '1', $igea_v( 'privacy' ) ); ?><?php echo $igea_attr( 'privacy' ); // phpcs:ignore ?>> <span>Ho letto l'<?php if ( $igea_privacy ) : ?><a href="<?php echo esc_url( $igea_privacy ); ?>">informativa privacy</a><?php else : ?>informativa privacy <?php echo igea_dc( 'DA CONFERMARE: pagina Privacy policy' ); // phpcs:ignore ?><?php endif; ?>.</span></label>
				<?php echo $igea_errore( 'privacy' ); // phpcs:ignore ?>
				<?php igea_cf_campi_nascosti(); ?>
				<div class="invio">
					<button class="btn btn-pieno" type="submit">Invia la richiesta <span class="fr" aria-hidden="true">→</span></button>
					<?php if ( '' !== $igea_tempi ) : ?>
						<span class="tempi">Ti rispondiamo entro <?php echo esc_html( $igea_tempi ); ?>.</span>
					<?php elseif ( igea_mostra_segnaposto() ) : ?>
						<span class="tempi">Ti rispondiamo entro <?php echo igea_dc( 'DA CONFERMARE: tempi' ); // phpcs:ignore ?>.</span>
					<?php endif; ?>
				</div>
			</form>

		<?php elseif ( current_user_can( 'manage_options' ) ) : ?>
			<p class="avviso-admin">Il modulo non è attivo: manca il mu-plugin <code>igea-contatti.php</code> in <code>wp-content/mu-plugins</code>. Lo vedono solo gli amministratori.</p>
		<?php endif; ?>

		<?php if ( $igea_attivo && ! defined( 'IGEA_MAIL_TO' ) && current_user_can( 'manage_options' ) ) : ?>
			<p class="avviso-admin">Manca la costante <code>IGEA_MAIL_TO</code> in <code>wp-config.php</code>: gli invii verrebbero rifiutati. Lo vedono solo gli amministratori.</p>
		<?php endif; ?>
	</div>
</section>
