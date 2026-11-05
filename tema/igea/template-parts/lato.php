<?php
/**
 * Riquadro laterale delle pagine interne: invito a chiedere un preventivo.
 * Argomento: 'area', per preselezionarla nel modulo.
 */

defined( 'ABSPATH' ) || exit;

$igea_tel = igea_dato( 'telefono' );
?>
<aside class="lato" aria-labelledby="t-lato">
	<span class="eyebrow">Parliamone</span>
	<h2 id="t-lato">Da dove iniziare con la tua azienda?</h2>
	<p>Raccontaci di cosa ti occupi e quante persone lavorano con te: ti diciamo quali adempimenti ti riguardano.</p>
	<a class="btn btn-pieno" href="<?php echo esc_url( igea_url_contatti( (string) ( $args['area'] ?? '' ) ) ); ?>">Richiedi un preventivo <span class="fr" aria-hidden="true">→</span></a>
	<?php if ( '' !== $igea_tel ) : ?>
		<p>Oppure chiama il <a href="<?php echo esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $igea_tel ) ); ?>"><?php echo esc_html( $igea_tel ); ?></a>.</p>
	<?php endif; ?>
</aside>
