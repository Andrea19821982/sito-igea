<?php
/**
 * Riquadro laterale delle pagine interne: invito a chiedere un preventivo.
 * Argomenti: 'area', per preselezionarla nel modulo; 'servizi' (bool), per elencare sotto le pagine dei servizi
 * collegate in Personalizza, con quella corrente evidenziata.
 */

defined( 'ABSPATH' ) || exit;

$igea_tel     = igea_dato( 'telefono' );
$igea_servizi = [];
if ( ! empty( $args['servizi'] ) ) {
	foreach ( igea_servizi() as $igea_chiave => $igea_s ) {
		$igea_id = igea_id_pagina( $igea_chiave );
		if ( $igea_id ) {
			$igea_servizi[] = [ $igea_s['titolo'], (string) get_permalink( $igea_id ), get_queried_object_id() === $igea_id ];
		}
	}
}
?>
<aside class="lato" aria-labelledby="t-lato">
	<span class="eyebrow">Parliamone</span>
	<h2 id="t-lato">Da dove iniziare con la tua azienda?</h2>
	<p>Raccontaci di cosa ti occupi e quante persone lavorano con te: ti diciamo quali adempimenti ti riguardano.</p>
	<a class="btn btn-pieno" href="<?php echo esc_url( igea_url_contatti( (string) ( $args['area'] ?? '' ) ) ); ?>">Richiedi un preventivo <span class="fr" aria-hidden="true">→</span></a>
	<?php if ( '' !== $igea_tel ) : ?>
		<p>Oppure chiama il <a href="<?php echo esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $igea_tel ) ); ?>"><?php echo esc_html( $igea_tel ); ?></a>.</p>
	<?php endif; ?>
	<?php if ( count( $igea_servizi ) > 1 ) : ?>
		<nav class="lato-servizi" aria-labelledby="t-lato-servizi">
			<h3 id="t-lato-servizi">Tutti i servizi</h3>
			<ul>
				<?php foreach ( $igea_servizi as [ $igea_titolo, $igea_url, $igea_qui ] ) : ?>
					<li><a href="<?php echo esc_url( $igea_url ); ?>"<?php echo $igea_qui ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $igea_titolo ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</nav>
	<?php endif; ?>
</aside>
