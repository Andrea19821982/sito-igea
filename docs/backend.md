# Back-end: come funziona e cosa resta da fare sul server

Revisione del 4 ottobre 2026, aggiornata con il tema 0.4.0. Il codice è stato provato su **WordPress 7.1.2 con PHP 8.3** in una copia locale con dati di prova; non ancora sul server di IGEA.

## Modulo contatti (`mu-plugins/igea-contatti.php`)

- Il markup è nel tema (`template-parts/contatti.php`), in home e nella pagina Contatti. Il modulo della home invia alla pagina Contatti.
- Antispam senza CAPTCHA:
  - nonce di WordPress;
  - tempo di compilazione firmato con HMAC: meno di 4 secondi o più di 2 ore non passano;
  - campo trappola `igea_hp`: se è pieno, il bot riceve una finta conferma e non parte nulla;
  - al massimo 5 invii all'ora per IP, salvato solo come hash.
- Aree e numero di persone accettati solo da un elenco fisso; testi ripuliti dai tag; nessun dato personale nell'indirizzo della pagina dopo l'invio.
- Dopo un invio riuscito: redirect 303 con `?inviato=1`. Con errori: la pagina si ridisegna con i valori scritti, il riepilogo degli errori e i messaggi collegati ai campi.
- Email con SMTP autenticato, `Reply-To` solo con l'email già validata.
- Fuori dalla produzione tutte le email vanno a `IGEA_MAIL_STAGING` (copie Cc e Ccn tolte); se la costante manca non parte nulla.
- Registro della presa visione dell'informativa: data, versione dell'informativa ed email solo come HMAC. Lo vedono solo gli amministratori; si svuota da solo dopo `IGEA_CF_CONSERVAZIONE`.

**Cache di pagina:** escludere `/contatti/` e la home, oppure tenerle in cache meno di 2 ore. Una pagina in cache più a lungo ha un token scaduto: la persona deve premere di nuovo Invia (il modulo resta compilato).

## Fonti ufficiali (`mu-plugins/igea-fonti.php`)

Titolo, data e link dai feed RSS di INAIL e del Ministero del Lavoro, aggiornati dal cron due volte al giorno. La pagina non contatta mai le fonti mentre si carica. Solo link `https` verso il dominio della fonte; nessun testo copiato. Gli indirizzi dei feed vanno verificati dallo staging: da qui le fonti non erano raggiungibili.

## Sicurezza (`mu-plugins/igea-sicurezza.php`)

- Intestazioni: `X-Content-Type-Options`, `Referrer-Policy`, `X-Frame-Options`, `Permissions-Policy`; HSTS solo con `IGEA_HSTS_MAX_AGE`.
- **Content Security Policy** con `script-src 'self'`: il JavaScript del tema è in un file esterno, senza script in linea. Parte in sola segnalazione (`Report-Only`); diventa definitiva con `IGEA_CSP_ATTIVA` dopo la prova in staging. Non si applica a chi è collegato, per la barra di amministrazione.
- XML-RPC spento, versione di WordPress nascosta, elenco utenti della REST API solo per chi scrive sul sito, archivi per autore e `?author=N` in 404, autore tolto dagli embed, sitemap senza utenti e tag, commenti chiusi (riapribili con `IGEA_COMMENTI`).

## Tema

- Caratteri Inter e Inter Tight **in locale** (licenza SIL OFL, in `assets/fonts/`): nessuna richiesta a Google, nessun banner cookie necessario per i font.
- Staging e copie locali con `noindex` automatico.
- Descrizione per i motori di ricerca dal riassunto della pagina, solo se non c'è un plugin SEO attivo.
- Dati strutturati `ProfessionalService` in home, solo con i dati compilati in Personalizza.

## Da fare sul server (li esegue Andrea)

1. **PHP 8.3 e WordPress 7.1.x** in staging, poi in produzione. Oggi la produzione è su PHP 7.1.33.
2. **SPF, DKIM e DMARC** sul dominio del mittente `IGEA_MAIL_FROM`.
3. **Niente PHP eseguibile in `wp-content/uploads`** (regola di Plesk o `.htaccess`).
4. **Verifica in due passaggi** per gli amministratori e un limite ai tentativi di accesso (plugin dedicato o Fail2Ban di Plesk).
5. **Backup**: database ogni giorno, file ogni settimana, su un altro server; prova di ripristino ogni tre mesi.
6. **Aggiornamenti** mensili, prima in staging.
7. **Cache e compressione**: cache di pagina con un solo plugin leggero o la cache nginx di Plesk, con le esclusioni dette sopra; Brotli o gzip; OPcache; file statici in cache per un anno (i nomi dei file del tema cambiano a ogni modifica).
8. **Cambio di dominio**: certificato valido anche sul vecchio dominio, redirect 301 con un solo salto, `wp search-replace` provato prima con `--dry-run`, "Cambio di indirizzo" in Search Console, vecchio WordPress spento.
9. **Monitoraggio**: disponibilità del sito, scadenza del certificato, errori 404 nelle quattro settimane dopo il trasloco.
