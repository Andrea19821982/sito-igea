<?php
/**
 * Una riga dell'elenco news e dei risultati di ricerca. Argomenti: 'livello' del titolo (2 negli archivi, 3 in home;
 * solo in home compare allo scorrimento), 'estratto' (bool: riassunto scritto a mano o inizio del testo).
 * Le pagine non hanno data: al suo posto c'è la scritta "Pagina". La categoria predefinita non si mostra.
 */

defined( 'ABSPATH' ) || exit;

$igea_tag      = 2 === ( $args['livello'] ?? 3 ) ? 'h2' : 'h3';
$igea_articolo = 'post' === get_post_type();
$igea_cat      = $igea_articolo ? igea_categoria() : null;
$igea_estratto = ! empty( $args['estratto'] ) ? igea_estratto() : '';
?>
<li<?php echo 3 === ( $args['livello'] ?? 3 ) ? ' class="rivela"' : ''; ?>><a href="<?php the_permalink(); ?>">
	<?php if ( $igea_articolo ) : ?>
		<?php echo igea_data(); // phpcs:ignore WordPress.Security.EscapeOutput -- escapato in igea_data(). ?>
	<?php else : ?>
		<span class="tipo">Pagina</span>
	<?php endif; ?>
	<div>
		<?php if ( $igea_cat ) : ?><span class="cat"><?php echo esc_html( $igea_cat->name ); ?></span><?php endif; ?>
		<<?php echo $igea_tag; // phpcs:ignore ?>><?php echo esc_html( wp_strip_all_tags( get_the_title() ) ); ?></<?php echo $igea_tag; // phpcs:ignore ?>>
		<?php if ( '' !== $igea_estratto ) : ?><p class="estratto"><?php echo esc_html( $igea_estratto ); ?></p><?php endif; ?>
	</div>
	<span class="tondo" aria-hidden="true">→</span>
</a></li>
