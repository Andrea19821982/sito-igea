<?php
/**
 * Pagine: servizi, formazione, contatti, privacy, cookie e ogni altra pagina.
 * Il ruolo della pagina si sceglie in Aspetto > Personalizza > Pagine collegate:
 * gli indirizzi delle pagine esistenti non cambiano.
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$igea_tipo     = igea_tipo_pagina( get_the_ID() );
	$igea_servizio = igea_servizi()[ $igea_tipo ] ?? null;
	$igea_legale   = 'cookie' === $igea_tipo || (int) get_option( 'wp_page_for_privacy_policy' ) === get_the_ID();

	get_template_part( 'template-parts/testata-pagina', null, [
		'titolo'  => get_the_title(),
		'eyebrow' => $igea_servizio ? $igea_servizio['norma'] : '',
		'lead'    => has_excerpt() ? get_the_excerpt() : '',
	] );

	if ( 'contatti' === $igea_tipo ) :
		if ( '' !== trim( (string) get_the_content() ) ) :
			?>
			<div class="corpo senza-lato"><div class="wrap"><div class="prosa"><?php the_content(); ?></div></div></div>
			<?php
		endif;
		get_template_part( 'template-parts/contatti' );
	else :
		?>
		<article class="corpo<?php echo $igea_legale ? ' senza-lato' : ''; ?>">
			<div class="wrap">
				<div class="prosa">
					<?php
					the_content();
					wp_link_pages();
					if ( 'formazione' === $igea_tipo ) {
						get_template_part( 'template-parts/formazione-tabella' );
					}
					?>
				</div>
				<?php
				if ( ! $igea_legale ) {
					get_template_part( 'template-parts/lato', null, [ 'area' => $igea_servizio || 'formazione' === $igea_tipo ? $igea_tipo : '' ] );
				}
				?>
			</div>
		</article>
		<?php
	endif;
endwhile;

get_footer();
