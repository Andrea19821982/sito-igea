<?php
/**
 * Le quattro aree di servizio della home. Testi approvati nel prototipo.
 * Le chiavi coincidono con le aree del modulo contatti (mu-plugin igea-contatti.php).
 */

defined( 'ABSPATH' ) || exit;

function igea_servizi(): array {
	return apply_filters( 'igea_servizi', [
		'sicurezza' => [
			'titolo'    => 'Salute e sicurezza sul lavoro',
			'norma'     => 'D.Lgs. 81/08',
			'testo'     => "Valutazione dei rischi, documenti obbligatori, formazione e consulenza: per mettere l'azienda in regola e tenerla in regola.",
			'etichette' => [ 'Valutazione dei rischi', 'DVR', 'Formazione', 'Misure strumentali' ],
			'foto'      => [ 'dpi', '', 900, 600 ],
		],
		'ambiente'  => [
			'titolo'    => 'Igiene ambientale e industriale',
			'norma'     => 'D.Lgs. 152/06 · D.Lgs. 81/08',
			'testo'     => "Emissioni, rifiuti, agenti fisici e chimici: misuriamo e ti aiutiamo a tenere sotto controllo l'impatto della tua attività, dentro e fuori l'azienda.",
			'etichette' => [ 'Emissioni', 'Rifiuti', 'Agenti fisici', 'Agenti chimici' ],
			'foto'      => [ 'ambiente', 'Pale eoliche in un prato', 900, 600 ],
		],
		'alimenti'  => [
			'titolo'    => 'Igiene degli alimenti',
			'norma'     => 'Reg. (CE) 852/2004',
			'testo'     => 'Piani di autocontrollo basati sui principi HACCP e certificazioni volontarie di filiera per chi produce, trasforma, distribuisce o somministra alimenti.',
			'etichette' => [ 'HACCP', 'ISO 22000', 'IFS', 'BRCGS' ],
			'foto'      => [ 'alimenti', 'Frutta e verdura fresche', 900, 675 ],
		],
		'medicina'  => [
			'titolo'    => 'Medicina del lavoro',
			'norma'     => 'D.Lgs. 81/08, art. 41',
			'testo'     => 'Sorveglianza sanitaria e medico competente: visite, protocolli e giudizi di idoneità per i tuoi lavoratori.',
			'etichette' => [ 'Sorveglianza sanitaria', 'Idoneità', 'Protocollo sanitario' ],
			'foto'      => [ 'medicina', 'Medico con stetoscopio', 900, 513 ],
		],
	] );
}

/** Frase di apertura della home (testo approvato nel prototipo): la usano la hero e la descrizione per i motori di ricerca. */
function igea_testo_apertura(): string {
	return 'Salute e sicurezza sul lavoro, igiene ambientale e industriale, igiene degli alimenti e medicina del lavoro. Ti affianchiamo in ogni adempimento, così puoi pensare alla tua azienda.';
}

/**
 * Nella pagina "tutti i servizi" le quattro schede compaiono da sole, a meno che il testo della pagina
 * non porti già ad almeno due pagine servizio (in quel caso l'elenco c'è già e non va ripetuto).
 */
function igea_schede_servizi_utili( int $id ): bool {
	$testo    = (string) get_post_field( 'post_content', $id );
	$presenti = 0;
	foreach ( array_keys( igea_servizi() ) as $chiave ) {
		$pagina = igea_id_pagina( $chiave );
		$slug   = $pagina ? (string) get_post_field( 'post_name', $pagina ) : '';
		if ( '' !== $slug && str_contains( $testo, '/' . $slug ) ) {
			++$presenti;
		}
	}
	return (bool) apply_filters( 'igea_schede_servizi', $presenti < 2, $id );
}
