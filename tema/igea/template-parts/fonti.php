<?php
/**
 * Riquadro "Dalle fonti ufficiali": titolo, data e link dai feed di INAIL e Ministero del Lavoro.
 * Le voci le salva il mu-plugin igea-fonti.php con il cron: la pagina non contatta mai le fonti.
 */

defined( 'ABSPATH' ) || exit;

$igea_voci = function_exists( 'igea_fonti_voci' ) ? igea_fonti_voci( 4 ) : [];
if ( ! $igea_voci && ! igea_mostra_segnaposto() ) {
	return;
}
?>
<aside class="fonti rivela" aria-labelledby="t-fonti">
	<div class="fonti-intesta"><h3 id="t-fonti">Dalle fonti ufficiali</h3><span class="aggiornato"><i aria-hidden="true"></i>Si aggiorna da solo due volte al giorno</span></div>
	<?php if ( $igea_voci ) : ?>
		<ul class="fonti-lista">
			<?php foreach ( $igea_voci as $igea_v ) : ?>
				<li><a href="<?php echo esc_url( $igea_v['link'] ); ?>" target="_blank" rel="noopener noreferrer"><span class="fonte"><?php echo esc_html( $igea_v['fonte'] ); ?></span><span class="quando"><time datetime="<?php echo esc_attr( wp_date( 'Y-m-d', $igea_v['data'] ) ); ?>"><?php echo esc_html( wp_date( 'j F Y', $igea_v['data'] ) ); ?></time></span><span class="est" aria-hidden="true">↗</span><span class="titolo"><?php echo esc_html( $igea_v['titolo'] ); ?><span class="vh"> (si apre in una nuova scheda)</span></span></a></li>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<p><?php echo igea_dc( 'Nessuna voce ancora: compaiono dopo il primo aggiornamento del cron (mu-plugin igea-fonti.php)' ); // phpcs:ignore ?></p>
	<?php endif; ?>
	<p class="nota-fonti">Mostriamo solo titolo, data e link: la notizia si legge sul sito della fonte.</p>
</aside>
