<?php
/**
 * Una riga dell'elenco news. Argomenti: 'livello' del titolo (2 negli archivi, 3 in home; solo in home compare
 * allo scorrimento), 'estratto' (bool).
 */

defined( 'ABSPATH' ) || exit;

$igea_tag  = 2 === ( $args['livello'] ?? 3 ) ? 'h2' : 'h3';
$igea_cats = 'post' === get_post_type() ? get_the_category() : [];
$igea_cat  = $igea_cats ? $igea_cats[0]->name : ( 'page' === get_post_type() ? 'Pagina' : '' );
?>
<li<?php echo 3 === ( $args['livello'] ?? 3 ) ? ' class="rivela"' : ''; ?>><a href="<?php the_permalink(); ?>">
	<?php echo igea_data(); // phpcs:ignore WordPress.Security.EscapeOutput -- escapato in igea_data(). ?>
	<div>
		<?php if ( $igea_cat ) : ?><span class="cat"><?php echo esc_html( $igea_cat ); ?></span><?php endif; ?>
		<<?php echo $igea_tag; // phpcs:ignore ?>><?php echo esc_html( wp_strip_all_tags( get_the_title() ) ); ?></<?php echo $igea_tag; // phpcs:ignore ?>>
		<?php if ( ! empty( $args['estratto'] ) && has_excerpt() ) : ?><p class="estratto"><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?>
	</div>
	<span class="tondo" aria-hidden="true">→</span>
</a></li>
