<?php
defined( 'ABSPATH' ) || exit;

$igea_servizi = igea_servizi();
$igea_totale  = count( $igea_servizi );
$igea_n       = 0;
$igea_tutti   = igea_url_pagina( 'servizi' );
?>
<section class="blocco" id="servizi" aria-labelledby="t-servizi">
	<div class="wrap">
		<div class="intesta rivela">
			<div><span class="eyebrow">Servizi</span><h2 id="t-servizi">Quattro aree. Una sola regia sugli adempimenti.</h2></div>
			<?php if ( $igea_tutti ) : ?><a class="link-fr" href="<?php echo esc_url( $igea_tutti ); ?>">Tutti i servizi →</a><?php endif; ?>
		</div>
		<div class="cifre rivela">
			<div class="cifra"><strong data-conta="4">4</strong><span>aree di intervento, dalla sicurezza agli alimenti</span></div>
			<div class="cifra"><strong data-conta="12">12</strong><span>norme e standard citati nei nostri servizi</span></div>
			<div class="cifra"><strong data-conta="1">1</strong><span>referente unico per la tua azienda</span></div>
		</div>
	</div>
	<div class="oriz" id="oriz">
		<div class="oriz-fisso">
			<div class="nastri" aria-hidden="true"><svg class="onde n-oriz" id="nastro-oriz" viewBox="0 0 2400 420" data-seme="1"></svg></div>
			<div class="oriz-traccia" id="traccia">
				<?php
				foreach ( $igea_servizi as $igea_chiave => $igea_s ) :
					++$igea_n;
					$igea_url = igea_url_pagina( $igea_chiave );
					[ $igea_foto, $igea_alt, $igea_l, $igea_h ] = $igea_s['foto'];
					?>
					<article class="pannello">
						<div class="pannello-foto"><?php igea_foto( $igea_foto, $igea_alt, $igea_l, $igea_h ); ?></div>
						<div class="pannello-testo">
							<span class="conta" aria-hidden="true"><?php echo (int) $igea_n; ?> / <?php echo (int) $igea_totale; ?></span>
							<span class="eyebrow"><?php echo esc_html( $igea_s['norma'] ); ?></span>
							<h3><?php echo esc_html( $igea_s['titolo'] ); ?></h3>
							<p><?php echo esc_html( $igea_s['testo'] ); ?></p>
							<ul class="etichette">
								<?php foreach ( $igea_s['etichette'] as $igea_e ) : ?><li><?php echo esc_html( $igea_e ); ?></li><?php endforeach; ?>
							</ul>
							<?php if ( $igea_url ) : ?>
								<a class="link-fr" href="<?php echo esc_url( $igea_url ); ?>">Scopri il servizio<span class="vh">: <?php echo esc_html( $igea_s['titolo'] ); ?></span> →</a>
							<?php else : ?>
								<a class="link-fr" href="#contatti" data-area="<?php echo esc_attr( $igea_chiave ); ?>">Chiedi informazioni<span class="vh">: <?php echo esc_html( $igea_s['titolo'] ); ?></span> →</a>
							<?php endif; ?>
						</div>
					</article>
				<?php endforeach; ?>
				<article class="pannello invito">
					<svg class="onde n-invito" aria-hidden="true" viewBox="0 0 720 420" data-seme="3" data-tono="bianco"></svg>
					<div class="pannello-testo">
						<span class="eyebrow">Da dove iniziare</span>
						<h3>Non sai quali adempimenti ti riguardano?</h3>
						<p>Quattro domande per un primo orientamento. Poi, se vuoi, ne parliamo con te.</p>
						<a class="btn btn-bianco" href="#adempimenti">Fai il test in 4 domande <span class="fr" aria-hidden="true">→</span></a>
					</div>
				</article>
			</div>
			<div class="oriz-avanzamento wrap" aria-hidden="true"><span>Scorri</span><i><b id="oriz-barra"></b></i></div>
		</div>
	</div>
	<div class="wrap"><p class="anche rivela"><strong>Anche:</strong> sistemi di gestione · organizzazione · servizi tecnico-amministrativi · addestramento <?php echo igea_dc( 'DA CONFERMARE: modelli D.Lgs. 231/2001, SA 8000, Seveso' ); // phpcs:ignore ?></p></div>
</section>
