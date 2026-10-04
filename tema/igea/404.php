<?php
/**
 * Pagina non trovata.
 */

defined( 'ABSPATH' ) || exit;

get_header();
get_template_part( 'template-parts/testata-pagina', null, [
	'titolo'  => 'Pagina non trovata',
	'eyebrow' => 'Errore 404',
	'lead'    => "L'indirizzo potrebbe essere cambiato. Cerca nel sito oppure riparti da una di queste pagine.",
] );
?>
<div class="corpo senza-lato">
	<div class="wrap">
		<div class="vuoto">
			<?php get_search_form(); ?>
			<p><a class="link-fr" href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> &nbsp; <a class="link-fr" href="<?php echo esc_url( igea_url_pagina( 'servizi' ) ?: igea_url_home( 'servizi' ) ); ?>">Servizi</a> &nbsp; <a class="link-fr" href="<?php echo esc_url( igea_url_contatti() ); ?>">Contatti</a></p>
		</div>
	</div>
</div>
<?php
get_footer();
