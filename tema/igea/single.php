<?php
/**
 * Articolo delle news.
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$igea_cats = get_the_category();
	$igea_news = igea_url_news();

	get_template_part( 'template-parts/testata-pagina', null, [
		'titolo'      => get_the_title(),
		'eyebrow'     => $igea_cats ? $igea_cats[0]->name : 'News',
		'eyebrow_url' => $igea_cats ? (string) get_category_link( $igea_cats[0] ) : '',
		'data'        => true,
	] );
	?>
	<article class="corpo">
		<div class="wrap">
			<div class="prosa">
				<?php
				if ( has_post_thumbnail() ) {
					the_post_thumbnail( 'large', [ 'loading' => 'eager' ] );
				}
				the_content();
				wp_link_pages();
				?>
				<?php if ( $igea_news ) : ?>
					<p class="torna"><a class="link-fr" href="<?php echo esc_url( $igea_news ); ?>">← Tutte le news</a></p>
				<?php endif; ?>
			</div>
			<?php get_template_part( 'template-parts/lato' ); ?>
		</div>
	</article>
	<?php
endwhile;

get_footer();
