<?php
/**
 * Modulo di ricerca.
 */

defined( 'ABSPATH' ) || exit;

$igea_id = wp_unique_id( 'cerca-' );
?>
<form role="search" method="get" class="cerca" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="vh" for="<?php echo esc_attr( $igea_id ); ?>">Cerca nel sito</label>
	<input type="search" id="<?php echo esc_attr( $igea_id ); ?>" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="Cerca nel sito" maxlength="100">
	<button class="btn btn-pieno" type="submit">Cerca</button>
</form>
