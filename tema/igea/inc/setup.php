<?php
/**
 * Impostazioni del tema, file CSS e JS, pulizia dell'intestazione.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', static function (): void {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'html5', [ 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ] );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/editor.css' );

	register_nav_menus( [
		'principale' => 'Menu principale (in alto)',
		'piede'      => 'Menu del piè di pagina, colonna "Azienda"',
	] );
} );

/** Versione dei file dalla data di modifica: dopo ogni aggiornamento il browser scarica quelli nuovi. */
function igea_versione_file( string $relativo ): string {
	$file = get_theme_file_path( $relativo );
	return is_readable( $file ) ? (string) filemtime( $file ) : IGEA_VERSIONE;
}

add_action( 'wp_enqueue_scripts', static function (): void {
	wp_enqueue_style( 'igea', get_theme_file_uri( 'assets/css/igea.css' ), [], igea_versione_file( 'assets/css/igea.css' ) );
	// JavaScript in un file esterno: requisito della Content Security Policy con script-src 'self'.
	wp_enqueue_script(
		'igea',
		get_theme_file_uri( 'assets/js/igea.js' ),
		[],
		igea_versione_file( 'assets/js/igea.js' ),
		[ 'in_footer' => true, 'strategy' => 'defer' ]
	);

	// La home non usa blocchi: niente CSS dell'editor.
	if ( is_front_page() ) {
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'classic-theme-styles' );
	}
} );

/** I due caratteri usati sopra la piega arrivano prima. */
add_action( 'wp_head', static function (): void {
	foreach ( [ 'inter-latin-wght-normal', 'inter-tight-latin-wght-normal' ] as $font ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( get_theme_file_uri( "assets/fonts/{$font}.woff2" ) )
		);
	}
}, 2 );

// Emoji: script e stili in linea che il sito non usa (e che la CSP bloccherebbe).
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

/** Staging e copie locali non vanno indicizzati. */
add_filter( 'wp_robots', static function ( array $robots ): array {
	if ( 'production' !== wp_get_environment_type() ) {
		$robots['noindex']  = true;
		$robots['nofollow'] = true;
	}
	return $robots;
} );

/** Titoli d'archivio senza il prefisso "Categoria:". */
add_filter( 'get_the_archive_title_prefix', '__return_empty_string' );

add_filter( 'excerpt_more', static fn(): string => '…' );
add_filter( 'excerpt_length', static fn(): int => 28 );
