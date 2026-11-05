# Novità del tema IGEA

## 0.4.2, 5 novembre 2026

- **Anteprima dei link condivisi.** Su LinkedIn, WhatsApp e nelle email un link del sito mostra titolo, riassunto e immagine: l'immagine in evidenza, la foto del servizio o il logo su fondo bianco (`assets/img/condivisione.jpg`, 1200×630). Se è attivo un plugin SEO, il tema non aggiunge nulla.
- **Percorso per Google.** Le pagine interne e le news hanno i dati strutturati del percorso (Home > Servizi > Pagina), senza cambiare la grafica.
- **Stampa.** Le pagine si stampano pulite: solo il contenuto, senza menu, moduli, animazioni e decorazioni. Le tabelle non si spezzano a metà riga.
- **Controlli di sicurezza ufficiali di WordPress.** `phpcs.xml.dist` applica le regole di sicurezza di WPCS a tema e mu-plugin; `verifica.sh` le esegue dopo `composer install -d strumenti`. Nessun problema reale trovato: le poche segnalazioni (confronti con valori fissi, nonce già verificato più avanti) sono annotate con il motivo.
- `prova-sito.js` controlla anche che i dati strutturati siano validi e che l'immagine dell'anteprima esista.
- Guida di installazione: icona del sito, lingua italiana, anteprima dei link.

## 0.4.1, 5 novembre 2026

- **Accessibilità.** Controllo automatico WCAG 2.2 AA (axe-core) di tutte le pagine, su desktop e telefono, più contrasto misurato sui pixel, titoli, navigazione da tastiera e riflusso a 320 px. Corretto l'unico difetto trovato: il riquadro "Dalle fonti ufficiali" era un `aside` dentro la sezione News, ora è una `section`.
- **Prestazioni.** Il disegno della dea passa da 175 a 121 KB (da 64 a 44 KB compresso), con le coordinate relative al posto di quelle assolute. Il disegno è identico, verificato confrontando i pixel.
- **WPBakery.** Se il plugin viene disattivato, le pagine costruite con WPBakery mostrano titoli, testi, immagini e pulsanti in forma semplice, invece dei codici `[vc_row]` a vista (`inc/wpbakery.php`). L'HTML grezzo passa dal filtro di WordPress e il JavaScript inserito non viene mai eseguito.
- **mu-plugin.** I cron del modulo contatti e delle fonti non partono durante l'installazione di WordPress, quando le tabelle non esistono ancora.
- **Prove automatiche.**
  - `strumenti/prova-sito.js` prova il sito nel browser, su staging o in locale: pagine, errori JavaScript, Content Security Policy, accessibilità, menu del telefono, test degli adempimenti e modulo contatti. Con `--invia` fa anche un invio vero.
  - `strumenti/sito-locale.sh` crea un WordPress locale con SQLite e pagine di prova, comprese una pagina WPBakery e le email salvate su file.
- `strumenti/verifica.sh` controlla anche la sintassi dei nuovi script. Il controllo delle credenziali ora segnala solo le costanti con un valore.

## 0.4.0, 4 ottobre 2026

Prima versione del tema WordPress, costruita dal prototipo approvato: home, pagine interne, modulo contatti, fonti ufficiali e intestazioni di sicurezza.
