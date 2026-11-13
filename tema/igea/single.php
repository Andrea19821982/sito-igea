<?php
/**
 * Articolo delle news.
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$igea_cat  = igea_categoria();
	$igea_news = igea_url_news();

	get_template_part( 'template-parts/testata-pagina', null, [
		'titolo'      => get_the_title(),
		'eyebrow'     => $igea_cat ? $igea_cat->name : ( $igea_news ? '' : 'News' ),
		'eyebrow_url' => $igea_cat ? (string) get_category_link( $igea_cat ) : '',
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
				<?php
				the_post_navigation( [
					'prev_text'          => '<span class="verso">← Precedente</span><span class="titolo">%title</span>',
					'next_text'          => '<span class="verso">Successiva →</span><span class="titolo">%title</span>',
					'screen_reader_text' => 'Altre news',
					'aria_label'         => 'Altre news',
					'class'              => 'altre-news',
				] );
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
