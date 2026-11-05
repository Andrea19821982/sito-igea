<?php
/**
 * Elenco delle news (pagina articoli, categorie, tag, archivi) e ripiego generale.
 */

defined( 'ABSPATH' ) || exit;

get_header();

$igea_pagina_news = (int) get_option( 'page_for_posts' );
if ( is_home() ) {
	$igea_titolo = $igea_pagina_news ? get_the_title( $igea_pagina_news ) : 'News e approfondimenti';
	$igea_lead   = $igea_pagina_news && has_excerpt( $igea_pagina_news ) ? get_the_excerpt( $igea_pagina_news ) : '';
} else {
	$igea_titolo = get_the_archive_title();
	$igea_lead   = wp_strip_all_tags( (string) get_the_archive_description() );
}

get_template_part( 'template-parts/testata-pagina', null, [
	'titolo'      => $igea_titolo,
	'eyebrow'     => is_home() ? 'News' : 'Archivio',
	'eyebrow_url' => is_home() ? '' : igea_url_news(),
	'lead'        => trim( $igea_lead ),
] );
?>
<div class="corpo senza-lato">
	<div class="wrap">
		<div>
			<?php if ( have_posts() ) : ?>
				<ul class="news-lista">
					<?php
					while ( have_posts() ) :
						the_post();
						get_template_part( 'template-parts/voce-news', null, [ 'livello' => 2, 'estratto' => true ] );
					endwhile;
					?>
				</ul>
				<?php
				the_posts_pagination( [
					'mid_size'           => 1,
					'prev_text'          => '← Precedenti',
					'next_text'          => 'Successivi →',
					'screen_reader_text' => 'Pagine delle news',
					'aria_label'         => 'Pagine delle news',
				] );
				?>
			<?php else : ?>
				<div class="vuoto">
					<p>Non ci sono ancora articoli qui.</p>
					<?php get_search_form(); ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
<?php
get_footer();
