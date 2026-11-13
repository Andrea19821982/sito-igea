<?php
/**
 * Risultati della ricerca.
 */

defined( 'ABSPATH' ) || exit;

get_header();

global $wp_query;
$igea_trovati = (int) $wp_query->found_posts;

get_template_part( 'template-parts/testata-pagina', null, [
	'titolo'  => 'Risultati per «' . esc_html( get_search_query( false ) ) . '»',
	'eyebrow' => 'Ricerca',
	'lead'    => 1 === $igea_trovati ? 'Un risultato.' : $igea_trovati . ' risultati.',
] );
?>
<div class="corpo senza-lato">
	<div class="wrap">
		<div class="vuoto">
			<?php get_search_form(); ?>
		</div>
		<?php if ( have_posts() ) : ?>
			<ul class="news-lista spazio-sopra">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/voce-news', null, [ 'livello' => 2, 'estratto' => true ] );
				endwhile;
				?>
			</ul>
			<?php the_posts_pagination( [ 'mid_size' => 1, 'prev_text' => '← Precedenti', 'next_text' => 'Successivi →', 'aria_label' => 'Pagine dei risultati' ] ); ?>
		<?php else : ?>
			<p>Nessun risultato. Prova con un'altra parola, oppure <a href="<?php echo esc_url( igea_url_contatti() ); ?>">scrivici</a>.</p>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();
