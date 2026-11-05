<?php
/**
 * Apertura delle pagine interne.
 * Argomenti: 'titolo' (HTML del titolo WordPress), 'eyebrow', 'eyebrow_url', 'lead', 'data' (bool, data dell'articolo).
 */

defined( 'ABSPATH' ) || exit;

$igea_a = wp_parse_args( $args ?? [], [ 'titolo' => '', 'eyebrow' => '', 'eyebrow_url' => '', 'lead' => '', 'data' => false ] );
?>
<div class="testata-pagina">
	<div class="nastri" aria-hidden="true"><svg class="onde n-pagina" viewBox="0 0 720 420" data-seme="9"></svg></div>
	<div class="wrap">
		<?php if ( '' !== $igea_a['eyebrow'] ) : ?>
			<span class="eyebrow">
				<?php if ( '' !== $igea_a['eyebrow_url'] ) : ?>
					<a href="<?php echo esc_url( $igea_a['eyebrow_url'] ); ?>"><?php echo esc_html( $igea_a['eyebrow'] ); ?></a>
				<?php else : ?>
					<?php echo esc_html( $igea_a['eyebrow'] ); ?>
				<?php endif; ?>
			</span>
		<?php endif; ?>
		<h1><?php echo wp_kses( $igea_a['titolo'], [ 'em' => [], 'strong' => [], 'br' => [] ] ); ?></h1>
		<?php if ( '' !== $igea_a['lead'] ) : ?><p class="lead"><?php echo esc_html( $igea_a['lead'] ); ?></p><?php endif; ?>
		<?php if ( $igea_a['data'] ) : ?><p class="meta"><?php echo igea_data(); // phpcs:ignore ?></p><?php endif; ?>
	</div>
</div>
