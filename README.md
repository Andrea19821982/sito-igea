# Sito IGEA Group

Nuovo tema WordPress di [igeagroup.it](https://www.igeagroup.it), costruito dal prototipo approvato da Andrea il 4 ottobre 2026.

- **Tema** `tema/igea/`: home con sfera interattiva, servizi in scorrimento orizzontale, test "Quali adempimenti ti riguardano?", metodo, tabella della formazione, chi siamo, news e contatti; pagine interne, articoli, archivio, ricerca e 404.
- **mu-plugin** `mu-plugins/`:
  - `igea-contatti.php`: modulo contatti con antispam senza CAPTCHA, SMTP autenticato e registro della presa visione dell'informativa;
  - `igea-fonti.php`: riquadro "Dalle fonti ufficiali" con i feed di INAIL e Ministero del Lavoro;
  - `igea-sicurezza.php`: intestazioni HTTP, Content Security Policy, XML-RPC spento, nomi utente non esposti;
  - `igea-controlli.php`: in Strumenti > Salute del sito dice cosa manca alla configurazione.
- **Documenti** `docs/`: [installazione in staging](docs/installazione.md), [note sul back-end](docs/backend.md), [cose da confermare](docs/da-confermare.md).

## Requisiti

PHP 8.2 o successivo (consigliato 8.3), WordPress 6.6 o successivo. Provato su WordPress 7.1.2 con PHP 8.3.

## Controlli

```bash
composer install -d strumenti                               # una volta: regole di sicurezza di WordPress (WPCS)
bash strumenti/verifica.sh                                  # controlli statici, da far passare prima di consegnare
bash strumenti/sito-locale.sh crea ../wp-locale             # WordPress locale con SQLite e pagine di prova
bash strumenti/sito-locale.sh avvia ../wp-locale            # http://127.0.0.1:8080/
node strumenti/prova-sito.js http://127.0.0.1:8080/ --invia # prova nel browser, desktop e telefono
```

`prova-sito.js` usa Playwright; con `npm install --prefix strumenti axe-core` controlla anche l'accessibilità (WCAG 2.2 AA). Le modifiche alla grafica vanno provate comunque su un telefono vero.

Le novità di ogni versione sono in [CHANGELOG.md](CHANGELOG.md).

Le regole di progetto sono in [CLAUDE.md](CLAUDE.md).
