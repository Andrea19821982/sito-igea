<?php
defined( 'ABSPATH' ) || exit;

$igea_corsi = igea_url_pagina( 'formazione' );
?>
<section class="blocco formazione" id="formazione" aria-labelledby="t-formazione">
	<div class="wrap">
		<div class="intesta rivela">
			<div><span class="eyebrow">Formazione</span><h2 id="t-formazione">Corsi e scadenze, sotto controllo.</h2></div>
			<?php if ( $igea_corsi ) : ?><a class="link-fr" href="<?php echo esc_url( $igea_corsi ); ?>">Tutti i corsi →</a><?php endif; ?>
		</div>
		<div class="formazione-griglia">
			<div class="formazione-testo rivela">
				<p>Corsi per lavoratori, preposti, dirigenti, datori di lavoro e addetti alle emergenze. Nella tabella, ogni quanto va aggiornata la formazione di ciascuna figura.</p>
				<?php if ( igea_mostra_segnaposto() ) : ?><p><?php echo igea_dc( 'DA CONFERMARE: modalità (aula, videoconferenza, e-learning) e catalogo del sito attuale da migrare, 23 corsi in 6 categorie' ); // phpcs:ignore ?></p><?php endif; ?>
				<a class="btn btn-pieno" href="#contatti" data-area="formazione">Chiedi le prossime date <span class="fr" aria-hidden="true">→</span></a>
			</div>
			<?php get_template_part( 'template-parts/formazione-tabella' ); ?>
		</div>
	</div>
</section>
