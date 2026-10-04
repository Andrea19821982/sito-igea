# Installazione in staging

Guida passo passo per Andrea. Ogni passo con un accesso (Plesk, FTP, wp-admin) lo esegui tu; le password non vanno mai scritte qui, nei commit o in chat.

## 0. Prima di tutto

1. **Backup completo** di file e database del sito attuale, scaricato su un altro computer.
2. **Prova di ripristino** del backup in un sottodominio vuoto: il backup vale solo se si ripristina.
3. **Staging** su un sottodominio separato, mai sulla produzione.

## 1. Server di staging

- PHP **8.3** (minimo 8.2). La produzione oggi è su PHP 7.1.33: il tema lì non funziona.
- WordPress aggiornato alla **7.1.x** prima di attivare il tema.
- In `wp-config.php` dello staging:

```php
define( 'WP_ENVIRONMENT_TYPE', 'staging' ); // segnaposto gialli visibili, sito non indicizzato
define( 'DISALLOW_FILE_EDIT', true );       // niente modifica dei file da wp-admin
```

## 2. Caricare i file

| Dal repository | Sul server |
|---|---|
| `tema/igea/` | `wp-content/themes/igea/` |
| `mu-plugins/igea-contatti.php` | `wp-content/mu-plugins/igea-contatti.php` |
| `mu-plugins/igea-fonti.php` | `wp-content/mu-plugins/igea-fonti.php` |
| `mu-plugins/igea-sicurezza.php` | `wp-content/mu-plugins/igea-sicurezza.php` |

La cartella `mu-plugins` va creata se non c'è. I file lì dentro sono sempre attivi e non compaiono fra i plugin da attivare.

## 3. Costanti in `wp-config.php`

Le scrivi tu, sopra la riga `/* That's all, stop editing! */`. Qui ci sono solo i nomi.

| Costante | Cosa contiene | Obbligatoria |
|---|---|---|
| `IGEA_MAIL_TO` | casella che riceve le richieste del modulo | sì |
| `IGEA_MAIL_FROM` | mittente sul dominio del sito (per SPF e DKIM) | sì |
| `IGEA_MAIL_STAGING` | casella di prova: fuori dalla produzione tutte le email vanno qui | in staging sì |
| `IGEA_SMTP_HOST`, `IGEA_SMTP_PORT`, `IGEA_SMTP_USER`, `IGEA_SMTP_PASS` | server SMTP autenticato | sì |
| `IGEA_CF_CONSERVAZIONE` | quanto tenere il registro della presa visione, es. `'24 months ago'` | no, [DA CONFERMARE] con il titolare |
| `IGEA_CSP_ATTIVA` | `true` rende definitiva la Content Security Policy | no, solo dopo la prova del passo 7 |
| `IGEA_HSTS_MAX_AGE` | secondi di HSTS: 300, poi 86400, poi 31536000 | no, solo in produzione con HTTPS valido |
| `IGEA_COMMENTI` | `true` riapre commenti e ping, chiusi per impostazione | no |

Fuori dalla produzione, se `IGEA_MAIL_STAGING` manca, le email non partono affatto: nessun messaggio di prova arriva a indirizzi veri.

## 4. Attivare e impostare il tema

1. **Aspetto > Temi**: attiva "IGEA".
2. **Aspetto > Personalizza > Dati aziendali**: ragione sociale, indirizzo e partita IVA sono già compilati con i dati del sito attuale. Telefono, email, PEC, REA, capitale sociale e tempi di risposta restano vuoti finché non li confermi.
3. **Aspetto > Personalizza > Pagine collegate**: scegli le pagine **esistenti** per Contatti, servizi, Formazione e Cookie policy. Gli indirizzi non cambiano. Contatti, Formazione e Cookie policy si collegano da soli se hanno lo slug `contatti`, `formazione` e `cookie-policy`.
4. **Impostazioni > Lettura**: home "Una pagina statica", con una pagina qualsiasi come home e la pagina News come "Pagina articoli". Così l'archivio delle news ha il suo indirizzo.
5. **Impostazioni > Privacy**: scegli la pagina dell'informativa. Il link compare nel modulo e nel piè di pagina.
6. **Aspetto > Menu**: facoltativo. Senza menu assegnati il tema mostra Servizi, Formazione, Chi siamo, News e Contatti.

## 5. Notizie dalle fonti ufficiali

Il primo aggiornamento parte da solo cinque minuti dopo l'attivazione. Per provarlo subito, da WP-CLI:

```bash
wp cron event run igea_aggiorna_fonti
```

Consigliato un cron reale da Plesk ogni 15 minuti al posto di quello di WordPress: `define( 'DISABLE_WP_CRON', true );` in `wp-config.php` e in Plesk un'operazione pianificata `wp cron event run --due-now`.

## 6. Pagine con WPBakery

Le pagine servizio attuali sono costruite con WPBakery. Finché il plugin resta attivo, il loro contenuto si vede dentro il nuovo tema; la conversione in blocchi è un passo separato, da fare pagina per pagina e confrontando i testi prima e dopo.

## 7. Controlli in staging

- Ogni pagina servizio, Contatti, Formazione, News, un articolo, la ricerca e un indirizzo inesistente (404).
- Il modulo: un invio corretto, uno con l'email sbagliata, uno senza la casella privacy. L'email arriva a `IGEA_MAIL_STAGING`.
- Su un telefono vero: menu, test in quattro domande, modulo.
- Console del browser (F12) su tutte le pagine senza essere collegati: nessun messaggio "Content Security Policy". Se non ce ne sono, si può definire `IGEA_CSP_ATTIVA`.
- I segnaposto gialli rimasti sono l'elenco di ciò che manca: vedi `docs/da-confermare.md`.

## 8. Andata in produzione

Solo dopo l'approvazione dello staging e con i punti di `docs/da-confermare.md` chiusi. In produzione `WP_ENVIRONMENT_TYPE` va tolto o messo a `'production'`: i segnaposto spariscono e il sito torna indicizzabile.
