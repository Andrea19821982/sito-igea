<?php
/**
 * Tabella delle scadenze di aggiornamento della formazione: in home e nella pagina Formazione.
 * Sul telefono ogni riga diventa un blocco (figura, scadenza, riferimento): i ruoli ARIA tengono la tabella
 * leggibile dagli screen reader anche così.
 * [DA VALIDARE] sull'Accordo Stato-Regioni del 17 aprile 2025 prima della pubblicazione.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="tabella rivela" role="region" aria-labelledby="cap-scadenze" tabindex="0">
	<table role="table">
		<caption id="cap-scadenze">Ogni quanto si aggiorna la formazione <?php echo igea_dc( "DA VALIDARE sull'Accordo Stato-Regioni del 17 aprile 2025" ); // phpcs:ignore ?></caption>
		<thead role="rowgroup"><tr role="row"><th scope="col" role="columnheader">Figura</th><th scope="col" role="columnheader">Aggiornamento</th><th scope="col" role="columnheader">Riferimento</th></tr></thead>
		<tbody role="rowgroup">
			<tr role="row"><th scope="row" role="rowheader">Lavoratori</th><td role="cell">Ogni 5 anni</td><td role="cell">Accordo Stato-Regioni</td></tr>
			<tr role="row"><th scope="row" role="rowheader">Preposti</th><td role="cell">Ogni 2 anni</td><td role="cell">D.Lgs. 81/08, art. 37, c. 7-ter</td></tr>
			<tr role="row"><th scope="row" role="rowheader">Dirigenti</th><td role="cell">Ogni 5 anni</td><td role="cell">Accordo Stato-Regioni</td></tr>
			<tr role="row"><th scope="row" role="rowheader">RLS</th><td role="cell">Ogni anno: 4 ore da 15 a 50 lavoratori, 8 ore oltre 50</td><td role="cell">D.Lgs. 81/08, art. 37, c. 11</td></tr>
			<tr role="row"><th scope="row" role="rowheader">Addetti antincendio</th><td role="cell">Ogni 5 anni</td><td role="cell">DM 2 settembre 2021</td></tr>
			<tr role="row"><th scope="row" role="rowheader">Addetti al primo soccorso</th><td role="cell">Ogni 3 anni</td><td role="cell">DM 388/2003</td></tr>
			<?php if ( igea_mostra_segnaposto() ) : ?>
				<tr role="row"><th scope="row" role="rowheader">Alimentaristi</th><td role="cell"><?php echo igea_dc( 'DA CONFERMARE' ); // phpcs:ignore ?></td><td role="cell"><?php echo igea_dc( 'DA CONFERMARE: norma regionale del Lazio' ); // phpcs:ignore ?></td></tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>
