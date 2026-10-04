<?php
/**
 * Intestazione: logo, menu principale, pulsante del preventivo.
 */

defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="salta" href="#contenuto">Vai al contenuto</a>

<header id="testata">
	<div class="wrap">
		<a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php igea_logo( 'IGEA, Qualità Ambiente Sicurezza: torna alla home' ); ?></a>
		<button class="menu-btn" id="menu-btn" type="button" aria-expanded="false" aria-controls="menu">Menu</button>
		<nav id="menu" aria-label="Principale">
			<?php
			wp_nav_menu( [
				'theme_location' => 'principale',
				'container'      => false,
				'depth'          => 1,
				'fallback_cb'    => 'igea_menu_ripiego',
			] );
			?>
		</nav>
		<a class="btn btn-pieno" href="<?php echo esc_url( igea_url_preventivo() ); ?>">Richiedi un preventivo <span class="fr" aria-hidden="true">→</span></a>
	</div>
</header>

<main id="contenuto">
