<?php
/**
 * Le quattro aree di servizio come schede: sotto il testo della pagina collegata come "Pagina con tutti i servizi".
 * Testi e fotografie sono quelli della home (inc/servizi.php). Ogni scheda porta alla pagina del servizio, se è
 * collegata in Personalizza; altrimenti al modulo contatti con l'area già scelta.
 */

defined( 'ABSPATH' ) || exit;
?>
<ul class="schede">
	<?php
	foreach ( igea_servizi() as $igea_chiave => $igea_s ) :
		$igea_url = igea_url_pagina( $igea_chiave );
		[ $igea_foto, $igea_alt, $igea_l, $igea_h ] = $igea_s['foto'];
		?>
		<li class="scheda">
			<div class="scheda-foto"><?php igea_foto( $igea_foto, $igea_alt, $igea_l, $igea_h ); ?></div>
			<div class="scheda-testo">
				<span class="eyebrow"><?php echo esc_html( $igea_s['norma'] ); ?></span>
				<h2><?php echo esc_html( $igea_s['titolo'] ); ?></h2>
				<p><?php echo esc_html( $igea_s['testo'] ); ?></p>
				<?php if ( $igea_url ) : ?>
					<a class="link-fr" href="<?php echo esc_url( $igea_url ); ?>">Scopri il servizio<span class="vh">: <?php echo esc_html( $igea_s['titolo'] ); ?></span> →</a>
				<?php else : ?>
					<a class="link-fr" href="<?php echo esc_url( igea_url_contatti( $igea_chiave ) ); ?>">Chiedi informazioni<span class="vh">: <?php echo esc_html( $igea_s['titolo'] ); ?></span> →</a>
				<?php endif; ?>
			</div>
		</li>
	<?php endforeach; ?>
</ul>
