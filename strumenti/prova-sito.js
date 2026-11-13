#!/usr/bin/env node
/*
 * Prova automatica del sito nel browser (Chromium con Playwright), da lanciare su staging o in locale.
 *
 * Uso:   node strumenti/prova-sito.js https://staging.esempio.it/ [--invia]
 *
 * Cosa controlla, su desktop (1440 px) e su telefono (390 px):
 *  - ogni pagina collegata da menu e piè di pagina, più una ricerca e un indirizzo inesistente (404);
 *  - nessun errore JavaScript e nessuna violazione della Content Security Policy;
 *  - nessuno scorrimento orizzontale, un solo titolo h1, nessuna immagine senza testo alternativo;
 *  - dati strutturati validi e immagine dell'anteprima social raggiungibile;
 *  - accessibilità con axe-core, se installato (npm install --prefix strumenti axe-core);
 *  - home: menu del telefono, test "Quali adempimenti ti riguardano?", controlli del modulo contatti.
 * Con --invia spedisce anche una richiesta vera dal modulo: usarlo solo su staging, dove le email
 * vanno a IGEA_MAIL_STAGING. Il sito accetta al massimo 5 invii l'ora dallo stesso indirizzo.
 *
 * Esce con il numero di errori (0 = tutto bene). Non modifica nulla sul sito, salvo con --invia.
 */
'use strict';
const path = require('path');
const fs = require('fs');

function carica(nome, alternative) {
	for (const p of [nome, ...alternative]) {
		try { return require(p); } catch (e) { /* prova la successiva */ }
	}
	return null;
}
let radiceGlobale = '';
try { radiceGlobale = require('child_process').execFileSync('npm', ['root', '-g'], { stdio: ['ignore', 'pipe', 'ignore'] }).toString().trim(); } catch (e) { /* npm assente */ }
const playwright = carica('playwright', [path.join(__dirname, 'node_modules', 'playwright'), radiceGlobale && path.join(radiceGlobale, 'playwright')].filter(Boolean));
if (!playwright) {
	console.error('Serve Playwright: npm install --prefix strumenti playwright && npx --prefix strumenti playwright install chromium');
	process.exit(2);
}
const fileAxe = [path.join(__dirname, 'node_modules', 'axe-core', 'axe.min.js')].find(f => fs.existsSync(f));

const argomenti = process.argv.slice(2);
const base = (argomenti.find(a => /^https?:\/\//.test(a)) || '').replace(/\/+$/, '');
const invia = argomenti.includes('--invia');
if (!base) {
	console.error('Uso: node strumenti/prova-sito.js https://indirizzo-del-sito/ [--invia]');
	process.exit(2);
}

let errori = 0, avvisi = 0;
const ko = (m) => { errori++; console.log('ERRORE  ' + m); };
const avviso = (m) => { avvisi++; console.log('AVVISO  ' + m); };
const ok = (m) => console.log('OK      ' + m);

async function pagineDaProvare(page) {
	await page.goto(base + '/', { waitUntil: 'load' });
	const link = await page.evaluate((origine) => [...document.querySelectorAll('header a[href], footer a[href], nav a[href]')]
		.map(a => a.href).filter(h => h.startsWith(origine) && !/\/wp-(admin|login)|\.(pdf|docx?|xlsx?|zip|jpe?g|png|webp)(\?|$)/i.test(h)), base);
	const visti = new Set(['/']);
	for (const h of link) {
		const u = new URL(h);
		visti.add(u.pathname + u.search);
	}
	const elenco = [...visti].slice(0, 30).map(p => ({ p, stato: 200 }));
	elenco.push({ p: '/?s=sicurezza', stato: 200 });
	elenco.push({ p: '/pagina-inesistente-prova-' + Date.now() + '/', stato: 404 });
	return elenco;
}

async function provaPagina(ctx, voce, schermo) {
	const page = await ctx.newPage();
	const problemi = [];
	let conAxe = false; // axe-core entra come script in linea: le segnalazioni CSP successive sono sue
	page.on('pageerror', e => problemi.push('errore JavaScript: ' + e.message));
	page.on('console', m => {
		const t = m.text();
		if (/Content Security Policy/i.test(t)) { if (!conAxe) problemi.push('CSP: ' + t.slice(0, 160)); }
		else if (m.type() === 'error' && !/Failed to load resource/.test(t)) problemi.push('console: ' + t.slice(0, 160));
	});
	const risposta = await page.goto(base + voce.p, { waitUntil: 'load' });
	const stato = risposta ? risposta.status() : 0;
	if (stato !== voce.stato) problemi.push(`stato HTTP ${stato}, atteso ${voce.stato}`);
	await page.waitForTimeout(150);
	const dati = await page.evaluate(() => ({
		largo: document.documentElement.scrollWidth - window.innerWidth,
		h1: document.querySelectorAll('h1').length,
		senzaAlt: [...document.images].filter(i => !i.hasAttribute('alt')).map(i => i.currentSrc || i.src).slice(0, 3),
		lingua: document.documentElement.lang,
		// Elementi che escono dal bordo destro: tabelle, schede, percorso e pulsanti del menu.
		fuori: [...document.querySelectorAll('.tabella, .schede, .percorso, .altre-news, #menu .solo-telefono')]
			.filter(e => e.getClientRects().length && e.getBoundingClientRect().right > window.innerWidth + 1).map(e => e.className).slice(0, 3),
		descrizione: (document.querySelector('meta[name="description"]') || {}).content,
	}));
	if (schermo === 'desktop' && voce.stato === 200) {
		// Dati strutturati leggibili e immagine dell'anteprima social raggiungibile.
		const meta = await page.evaluate(() => ({
			ld: [...document.querySelectorAll('script[type="application/ld+json"]')].map(x => { try { JSON.parse(x.textContent); return ''; } catch (e) { return x.textContent.slice(0, 60); } }).filter(Boolean),
			img: (document.querySelector('meta[property="og:image"]') || {}).content || '',
		}));
		meta.ld.forEach(x => problemi.push('dati strutturati non validi: ' + x));
		if (meta.img) {
			const r = await page.request.get(meta.img).catch(() => null);
			if (!r || r.status() !== 200 || !/^image\//.test(r.headers()['content-type'] || '')) problemi.push('immagine di anteprima non raggiungibile: ' + meta.img);
		}
	}
	if (dati.largo > 1) problemi.push(`scorrimento orizzontale di ${dati.largo} px`);
	if (dati.fuori.length) problemi.push('elementi più larghi dello schermo: ' + dati.fuori.join(', '));
	if (dati.descrizione !== undefined && (dati.descrizione.trim() === '' || dati.descrizione.length > 160)) problemi.push(`descrizione per i motori di ricerca vuota o troppo lunga (${dati.descrizione.length} caratteri)`);
	if (dati.h1 !== 1) problemi.push(`titoli h1: ${dati.h1} (deve essercene uno)`);
	if (dati.senzaAlt.length) problemi.push('immagini senza alt: ' + dati.senzaAlt.join(', '));
	if (schermo === 'desktop' && !/^it\b/i.test(dati.lingua)) avviso(`${voce.p}: lingua della pagina "${dati.lingua}" (in produzione deve essere italiano)`);
	if (fileAxe) {
		conAxe = true;
		await page.addScriptTag({ path: fileAxe });
		const r = await page.evaluate(async () => (await window.axe.run(document, { runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'] } })).violations
			.map(v => ({ id: v.id, impatto: v.impact, n: v.nodes.length, dove: v.nodes[0] && v.nodes[0].target.join(' ') })));
		for (const v of r) {
			const testo = `${voce.p} [${schermo}] accessibilità ${v.id} (${v.n}): ${v.dove}`;
			if (v.impatto === 'serious' || v.impatto === 'critical') problemi.push(testo.replace(`${voce.p} [${schermo}] `, ''));
			else avviso(testo);
		}
	}
	await page.close();
	if (problemi.length) problemi.forEach(x => ko(`${voce.p} [${schermo}] ${x}`));
	return problemi.length === 0;
}

async function provaHome(browser) {
	const ctx = await browser.newContext({ viewport: { width: 390, height: 844 }, hasTouch: true, reducedMotion: 'reduce' });
	const page = await ctx.newPage();
	await page.goto(base + '/', { waitUntil: 'load' });

	// Menu del telefono: si apre, il fuoco va alla prima voce, Esc lo chiude e riporta il fuoco al pulsante.
	const btn = page.locator('#menu-btn');
	if (await btn.isVisible()) {
		await btn.click();
		const aperto = await page.evaluate(() => document.getElementById('menu-btn').getAttribute('aria-expanded') === 'true' && document.getElementById('menu').contains(document.activeElement));
		const invito = page.locator('#menu .solo-telefono');
		if (await invito.count()) (await invito.isVisible()) ? ok('menu del telefono: pulsante del preventivo visibile') : ko('menu del telefono: pulsante del preventivo nascosto');
		await page.keyboard.press('Escape');
		const chiuso = await page.evaluate(() => document.getElementById('menu-btn').getAttribute('aria-expanded') === 'false' && document.activeElement.id === 'menu-btn');
		aperto && chiuso ? ok('menu del telefono: apertura, fuoco ed Esc') : ko('menu del telefono: apertura o chiusura non corrette');
	} else {
		avviso('pulsante del menu non visibile a 390 px');
	}

	// Test degli adempimenti: lavoratori sì, da 1 a 15, alimenti, nessun adempimento già presente.
	if (await page.locator('#test').count()) {
		const scegli = async (sel) => { await page.locator(sel).check(); await page.locator('#test-avanti').click(); };
		await page.locator('#test-avanti').click();
		const erroreVuoto = (await page.locator('#test-errore').textContent() || '').trim();
		await scegli('input[name="lavoratori"][value="si"]');
		await scegli('input[name="quante"][value="1-15"]');
		await scegli('input[name="attivita"][value="alimenti"]');
		await page.locator('#test-avanti').click();
		const voci = await page.locator('#esiti li').count();
		erroreVuoto && voci >= 6 ? ok(`test degli adempimenti: ${voci} voci nel risultato`) : ko(`test degli adempimenti: errore senza risposta "${erroreVuoto}", voci ${voci}`);
		if (await page.locator('#modulo').count()) {
			await page.locator('#test-invia').click();
			const pre = await page.evaluate(() => ({ area: document.getElementById('area').value, msg: document.getElementById('messaggio').value }));
			pre.area === 'sicurezza' && pre.msg.includes('HACCP') ? ok('il risultato del test precompila il modulo') : ko(`precompilazione del modulo: area "${pre.area}"`);
		}
	}
	await ctx.close();
}

async function provaModulo(browser) {
	const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 }, reducedMotion: 'reduce' });
	const page = await ctx.newPage();
	await page.goto(base + '/', { waitUntil: 'load' });
	let url = await page.evaluate(() => { const a = [...document.querySelectorAll('a[href*="contatti"]')].find(x => !x.hash || x.pathname !== location.pathname); return a ? a.href.split('#')[0] : ''; });
	if (!url) url = base + '/contatti/';
	await page.goto(url, { waitUntil: 'load' });
	if (!(await page.locator('#modulo').count())) { ko('modulo contatti non trovato in ' + url); await ctx.close(); return; }

	// Invio vuoto: tre errori, fuoco sul primo campo, nessun invio al server.
	await page.locator('#modulo button[type="submit"]').click();
	const vuoto = await page.evaluate(() => ({ errori: document.querySelectorAll('#modulo .errore').length, fuoco: document.activeElement.id, url: location.href }));
	vuoto.errori === 3 && vuoto.fuoco === 'nome' && vuoto.url.split('#')[0] === url.split('#')[0] ? ok('modulo: controlli nel browser') : ko(`modulo: errori ${vuoto.errori}, fuoco su "${vuoto.fuoco}"`);
	await page.fill('#nome', 'Prova automatica');
	await page.fill('#email', 'prova@example.com');
	await page.check('#privacy');
	const rimasti = await page.locator('#modulo .errore').count();
	rimasti === 0 ? ok('modulo: gli errori spariscono quando i campi sono corretti') : ko(`modulo: ${rimasti} errori rimasti dopo la correzione`);

	if (invia) {
		await page.fill('#messaggio', 'Messaggio di prova inviato da strumenti/prova-sito.js. Ignorare.');
		await page.waitForTimeout(4500); // il server scarta gli invii fatti entro 4 secondi dal caricamento
		await Promise.all([page.waitForNavigation({ waitUntil: 'load' }), page.locator('#modulo button[type="submit"]').click()]);
		await page.waitForTimeout(300);
		const esito = await page.evaluate(() => ({ url: location.href, fuoco: document.activeElement && document.activeElement.id, testo: (document.getElementById('esito-modulo') || {}).textContent || '' }));
		/inviato=1/.test(esito.url) && esito.fuoco === 'esito-modulo' ? ok('modulo: invio vero riuscito, conferma con il fuoco') : ko(`modulo: invio non riuscito (${esito.url}) ${esito.testo.trim().slice(0, 120)}`);
	}
	await ctx.close();
}

(async () => {
	const browser = await playwright.chromium.launch();
	console.log(`Prova di ${base}${fileAxe ? '' : ' (axe-core non installato: controllo di accessibilità saltato)'}`);
	const esplora = await browser.newPage();
	const elenco = await pagineDaProvare(esplora);
	await esplora.close();
	for (const [schermo, viewport] of [['desktop', { width: 1440, height: 900 }], ['telefono', { width: 390, height: 844 }]]) {
		const ctx = await browser.newContext({ viewport, reducedMotion: 'reduce' });
		let buone = 0;
		for (const voce of elenco) if (await provaPagina(ctx, voce, schermo)) buone++;
		ok(`${schermo}: ${buone} pagine su ${elenco.length} senza problemi`);
		await ctx.close();
	}
	await provaHome(browser);
	await provaModulo(browser);
	await browser.close();
	console.log(errori ? `Errori: ${errori}, avvisi: ${avvisi}` : `Tutto bene (avvisi: ${avvisi}).`);
	process.exit(Math.min(errori, 100));
})().catch(e => { console.error(e); process.exit(2); });
