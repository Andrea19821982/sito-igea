<?php
/**
 * Home: le sezioni del prototipo approvato, nello stesso ordine.
 * Il contenuto dell'eventuale pagina statica impostata come home non viene mostrato.
 */

defined( 'ABSPATH' ) || exit;

get_header();
get_template_part( 'template-parts/home/hero' );
get_template_part( 'template-parts/home/servizi' );
get_template_part( 'template-parts/home/adempimenti' );
get_template_part( 'template-parts/home/metodo' );
get_template_part( 'template-parts/home/formazione' );
get_template_part( 'template-parts/home/chi-siamo' );
get_template_part( 'template-parts/home/news' );
get_template_part( 'template-parts/contatti' );
get_footer();
