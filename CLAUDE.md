# Progetto: restyling di igeagroup.it

Tema WordPress e mu-plugin del nuovo sito di IGEA Group S.r.l. Tutti i documenti, i commenti e i messaggi sono in **italiano**.

## Regole (valgono per chiunque lavori qui)

1. **Backup completo di file e database prima di qualunque modifica**, verificato con una prova di ripristino, non solo lanciato.
2. **Mai lavorare in produzione.** Solo staging.
3. **Permalink invariati.** Il tema non cambia gli indirizzi delle pagine: le collega da Aspetto > Personalizza > Pagine collegate. Se un URL deve cambiare serve un redirect 301 documentato.
4. **Nessuna credenziale** nei file, nei commit o in chat. `wp-config.php` resta fuori dal repository. Le costanti SMTP e gli indirizzi email li inserisce Andrea sul server, con la guida in `docs/installazione.md`.
5. **Nessun dato aziendale inventato** (anni di attività, clienti, certificazioni, recapiti, testimonianze). Nei testi: `[DA CONFERMARE: …]`; nel tema: campo vuoto in Personalizza e la funzione `igea_dc()`, che mostra il segnaposto solo fuori dalla produzione.
6. Ogni modifica va **provata su un telefono vero**, non solo con il simulatore.
7. **Il tema è la fonte unica** di CSS, JavaScript e immagini: `tema/igea/assets/`. `prototipo/` è la copia del prototipo approvato, solo come riferimento: non si modifica.
8. **Prima di consegnare**: `bash strumenti/verifica.sh` deve finire senza errori. Per le app e il software, alla fine si esegue anche `/security-review`.

## Struttura

```
tema/igea/          tema WordPress (PHP 8.2+, WordPress 6.6+, provato su 7.1.2)
  assets/           css, js, font in locale (OFL), immagini
  inc/              impostazioni, dati aziendali, servizi, funzioni dei modelli, SEO
  template-parts/   sezioni della home, modulo contatti, testate, elenchi
mu-plugins/         modulo contatti, fonti ufficiali, sicurezza (restano attivi anche cambiando tema)
docs/               installazione, back-end, cose da confermare
strumenti/          controlli automatici
prototipo/          prototipo HTML approvato (versione 7 dell'anteprima)
```

## Stato

Vedi `docs/da-confermare.md` per i dati e le decisioni ancora aperti.
