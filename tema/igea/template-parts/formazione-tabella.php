<?php
/**
 * Tabella delle scadenze di aggiornamento della formazione: in home e nella pagina Formazione.
 * [DA VALIDARE] sull'Accordo Stato-Regioni del 17 aprile 2025 prima della pubblicazione.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="tabella rivela" role="region" aria-labelledby="cap-scadenze" tabindex="0">
	<table>
		<caption id="cap-scadenze">Ogni quanto si aggiorna la formazione <?php echo igea_dc( "DA VALIDARE sull'Accordo Stato-Regioni del 17 aprile 2025" ); // phpcs:ignore ?></caption>
		<thead><tr><th scope="col">Figura</th><th scope="col">Aggiornamento</th><th scope="col">Riferimento</th></tr></thead>
		<tbody>
			<tr><th scope="row">Lavoratori</th><td>Ogni 5 anni</td><td>Accordo Stato-Regioni</td></tr>
			<tr><th scope="row">Preposti</th><td>Ogni 2 anni</td><td>D.Lgs. 81/08, art. 37, c. 7-ter</td></tr>
			<tr><th scope="row">Dirigenti</th><td>Ogni 5 anni</td><td>Accordo Stato-Regioni</td></tr>
			<tr><th scope="row">RLS</th><td>Ogni anno: 4 ore da 15 a 50 lavoratori, 8 ore oltre 50</td><td>D.Lgs. 81/08, art. 37, c. 11</td></tr>
			<tr><th scope="row">Addetti antincendio</th><td>Ogni 5 anni</td><td>DM 2 settembre 2021</td></tr>
			<tr><th scope="row">Addetti al primo soccorso</th><td>Ogni 3 anni</td><td>DM 388/2003</td></tr>
			<?php if ( igea_mostra_segnaposto() ) : ?>
				<tr><th scope="row">Alimentaristi</th><td><?php echo igea_dc( 'DA CONFERMARE' ); // phpcs:ignore ?></td><td><?php echo igea_dc( 'DA CONFERMARE: norma regionale del Lazio' ); // phpcs:ignore ?></td></tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>
