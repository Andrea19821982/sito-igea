<?php defined( 'ABSPATH' ) || exit; ?>
<section class="blocco adempimenti" id="adempimenti" aria-labelledby="t-adempimenti">
	<div class="nastri" aria-hidden="true"><svg class="onde n-test" viewBox="0 0 720 420" data-seme="8"></svg></div>
	<div class="wrap test-griglia">
		<div class="test-intesta rivela">
			<span class="eyebrow">Da dove iniziare</span>
			<h2 id="t-adempimenti">Quali adempimenti ti riguardano? <em>Quattro domande.</em></h2>
			<p>Un primo orientamento in un minuto, senza lasciare dati. Il risultato è indicativo e non sostituisce la valutazione dei rischi.</p>
			<?php if ( igea_mostra_segnaposto() ) : ?><p><?php echo igea_dc( 'DA VALIDARE: regole del test da far confermare a un tecnico IGEA' ); // phpcs:ignore ?></p><?php endif; ?>
		</div>
		<noscript><p class="test-senza-js">Il test funziona con JavaScript attivo. Puoi anche <a href="#contatti">scriverci direttamente</a>.</p></noscript>
		<?php /* Il modulo compare solo con JavaScript attivo: senza, non funzionerebbe. Nessun dato lascia il browser. */ ?>
		<form class="test rivela" id="test" novalidate aria-labelledby="t-adempimenti" hidden>
			<ol class="test-passi" aria-hidden="true"><li class="on"></li><li></li><li></li><li></li></ol>
			<fieldset class="domanda" data-passo="1">
				<legend><span class="n">Domanda 1 di 4</span>Nella tua attività lavora qualcuno oltre a te?</legend>
				<p class="aiuto">Dipendenti, soci lavoratori, tirocinanti, collaboratori.</p>
				<div class="scelte">
					<label class="scelta"><input type="radio" name="lavoratori" value="si"><span>Sì</span></label>
					<label class="scelta"><input type="radio" name="lavoratori" value="no"><span>No, lavoro da solo</span></label>
				</div>
			</fieldset>
			<fieldset class="domanda" data-passo="2" hidden>
				<legend><span class="n">Domanda 2 di 4</span>Quante persone lavorano in azienda?</legend>
				<div class="scelte">
					<label class="scelta"><input type="radio" name="quante" value="1-15"><span>Da 1 a 15</span></label>
					<label class="scelta"><input type="radio" name="quante" value="16-50"><span>Da 16 a 50</span></label>
					<label class="scelta"><input type="radio" name="quante" value="50+"><span>Oltre 50</span></label>
				</div>
			</fieldset>
			<fieldset class="domanda" data-passo="3" hidden>
				<legend><span class="n">Domanda 3 di 4</span>Cosa succede nella tua attività?</legend>
				<p class="aiuto">Puoi scegliere più risposte.</p>
				<div class="scelte">
					<label class="scelta"><input type="checkbox" name="attivita" value="alimenti"><span>Prepari, trasformi, vendi o somministri alimenti</span></label>
					<label class="scelta"><input type="checkbox" name="attivita" value="ambiente"><span>Hai emissioni, scarichi o rifiuti speciali</span></label>
					<label class="scelta"><input type="checkbox" name="attivita" value="agenti"><span>Si usano prodotti chimici o macchine rumorose o che vibrano</span></label>
					<label class="scelta"><input type="checkbox" name="attivita" value="nessuna"><span>Nessuna di queste</span></label>
				</div>
			</fieldset>
			<fieldset class="domanda" data-passo="4" hidden>
				<legend><span class="n">Domanda 4 di 4</span>Cosa hai già?</legend>
				<p class="aiuto">Puoi scegliere più risposte, o nessuna.</p>
				<div class="scelte">
					<label class="scelta" data-serve="si"><input type="checkbox" name="gia" value="dvr"><span>DVR aggiornato</span></label>
					<label class="scelta" data-serve="si"><input type="checkbox" name="gia" value="rspp"><span>RSPP nominato</span></label>
					<label class="scelta" data-serve="si"><input type="checkbox" name="gia" value="medico"><span>Medico competente</span></label>
					<label class="scelta" data-serve="si"><input type="checkbox" name="gia" value="formazione"><span>Formazione in regola</span></label>
					<label class="scelta" data-serve="alimenti"><input type="checkbox" name="gia" value="haccp"><span>Piano HACCP</span></label>
				</div>
			</fieldset>
			<p class="test-errore" id="test-errore" role="alert"></p>
			<div class="test-azioni" id="test-azioni">
				<button type="button" class="btn btn-bordo" id="test-indietro" hidden>Indietro</button>
				<button type="submit" class="btn btn-pieno" id="test-avanti">Avanti <span class="fr" aria-hidden="true">→</span></button>
			</div>
			<div class="test-esito" id="test-esito" hidden>
				<h3 id="t-esito" tabindex="-1">Ecco cosa ti riguarda</h3>
				<ul class="esiti" id="esiti"></ul>
				<p class="avviso">Risultato indicativo: non sostituisce la valutazione dei rischi.</p>
				<div class="test-azioni">
					<a class="btn btn-pieno" href="#contatti" id="test-invia">Invia il risultato a IGEA <span class="fr" aria-hidden="true">→</span></a>
					<button type="button" class="btn btn-bordo" id="test-rifai">Rifai il test</button>
				</div>
			</div>
		</form>
	</div>
</section>
