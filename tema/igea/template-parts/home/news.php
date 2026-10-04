<?php
defined( 'ABSPATH' ) || exit;

$igea_news = new WP_Query( [
	'post_type'           => 'post',
	'posts_per_page'      => 3,
	'no_found_rows'       => true,
	'ignore_sticky_posts' => true,
] );
$igea_tutte = igea_url_news();
?>
<section class="blocco news" id="news" aria-labelledby="t-news">
	<div class="nastri" aria-hidden="true"><svg class="onde n-news" viewBox="0 0 720 420" data-seme="6"></svg></div>
	<div class="wrap">
		<div class="intesta rivela">
			<div><span class="eyebrow">News e approfondimenti</span><h2 id="t-news">Norme che cambiano, spiegate in breve.</h2></div>
			<?php if ( $igea_tutte ) : ?><a class="link-fr" href="<?php echo esc_url( $igea_tutte ); ?>">Tutte le news →</a><?php endif; ?>
		</div>
		<?php if ( $igea_news->have_posts() ) : ?>
			<ul class="news-lista">
				<?php
				while ( $igea_news->have_posts() ) :
					$igea_news->the_post();
					get_template_part( 'template-parts/voce-news', null, [ 'livello' => 3 ] );
				endwhile;
				wp_reset_postdata();
				?>
			</ul>
		<?php elseif ( igea_mostra_segnaposto() ) : ?>
			<p><?php echo igea_dc( 'DA CONFERMARE: nessun articolo pubblicato, qui compaiono gli ultimi tre' ); // phpcs:ignore ?></p>
		<?php endif; ?>
		<?php get_template_part( 'template-parts/fonti' ); ?>
	</div>
</section>
