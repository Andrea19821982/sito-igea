<?php
/**
 * Piè di pagina: dati aziendali, servizi, link legali, pausa delle animazioni.
 */

defined( 'ABSPATH' ) || exit;

$igea_privacy = get_privacy_policy_url();
$igea_cookie  = igea_url_pagina( 'cookie' );
?>
</main>

<footer class="piede">
	<div class="nastri" aria-hidden="true"><svg class="onde n-piede" viewBox="0 0 1440 420" data-seme="7"></svg></div>
	<div class="wrap">
		<div class="griglia">
			<div>
				<a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php igea_logo( 'IGEA, Qualità Ambiente Sicurezza', true ); ?></a>
				<address>
					<?php echo esc_html( igea_dato( 'ragione_sociale' ) ); ?><br>
					<?php echo esc_html( igea_indirizzo() ); ?><br>
					<?php if ( '' !== igea_dato( 'piva' ) ) : ?>P. IVA <?php echo esc_html( igea_dato( 'piva' ) ); ?><br><?php endif; ?>
					<?php
					$igea_legali = [];
					if ( '' !== igea_dato( 'rea' ) ) {
						$igea_legali[] = 'REA ' . esc_html( igea_dato( 'rea' ) );
					} elseif ( igea_mostra_segnaposto() ) {
						$igea_legali[] = 'REA ' . igea_dc( 'DA CONFERMARE' );
					}
					if ( '' !== igea_dato( 'capitale' ) ) {
						$igea_legali[] = 'Capitale sociale ' . esc_html( igea_dato( 'capitale' ) );
					} elseif ( igea_mostra_segnaposto() ) {
						$igea_legali[] = 'Capitale sociale ' . igea_dc( 'DA CONFERMARE' );
					}
					echo implode( ' · ', $igea_legali ); // phpcs:ignore WordPress.Security.EscapeOutput -- parti già escapate.
					?>
				</address>
			</div>
			<div>
				<h2>Servizi</h2>
				<ul>
					<?php foreach ( igea_servizi() as $igea_chiave => $igea_s ) : ?>
						<li><a href="<?php echo esc_url( igea_url_pagina( $igea_chiave ) ?: igea_url_home( 'servizi' ) ); ?>"><?php echo esc_html( $igea_s['titolo'] ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<div>
				<h2>Azienda</h2>
				<?php
				wp_nav_menu( [
					'theme_location' => 'piede',
					'container'      => false,
					'depth'          => 1,
					'fallback_cb'    => 'igea_menu_piede_ripiego',
				] );
				?>
			</div>
		</div>
		<div class="legale">
			<span>
				© <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php echo esc_html( igea_dato( 'ragione_sociale' ) ); ?>
				<?php if ( $igea_privacy ) : ?> · <a href="<?php echo esc_url( $igea_privacy ); ?>">Privacy policy</a><?php else : ?> <?php echo igea_dc( 'DA CONFERMARE: pagina Privacy policy' ); // phpcs:ignore ?><?php endif; ?>
				<?php if ( $igea_cookie ) : ?> · <a href="<?php echo esc_url( $igea_cookie ); ?>">Cookie policy</a><?php else : ?> <?php echo igea_dc( 'DA CONFERMARE: pagina Cookie policy' ); // phpcs:ignore ?><?php endif; ?>
			</span>
			<button type="button" class="pausa" data-pausa>Pausa animazioni</button>
		</div>
		<div class="marchio" aria-hidden="true">IGEA</div>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
