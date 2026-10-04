<?php
/**
 * Tema IGEA: caricamento delle parti.
 *
 * Il modulo contatti, le fonti ufficiali e le intestazioni di sicurezza sono mu-plugin
 * (cartella mu-plugins del repository): restano attivi anche se si cambia tema.
 */

defined( 'ABSPATH' ) || exit;

const IGEA_VERSIONE = '0.4.0';

require_once __DIR__ . '/inc/setup.php';
require_once __DIR__ . '/inc/dati.php';
require_once __DIR__ . '/inc/servizi.php';
require_once __DIR__ . '/inc/modelli.php';
require_once __DIR__ . '/inc/seo.php';
