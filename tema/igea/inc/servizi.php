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
