# Cose da confermare prima della pubblicazione

In staging ciascuna compare come segnaposto giallo; in produzione sparisce, quindi va chiusa prima.

## Dati aziendali (li inserisci in Aspetto > Personalizza > Dati aziendali)

- [ ] Telefono
- [ ] Email per i contatti
- [ ] PEC
- [ ] Numero REA
- [ ] Capitale sociale
- [ ] Tempi di risposta alle richieste ("Ti rispondiamo entro …")

## Testi e contenuti

- [ ] Modelli D.Lgs. 231/2001, SA 8000 e Seveso: IGEA li offre ancora? (riga "Anche:" sotto i servizi)
- [ ] Regole del test "Quali adempimenti ti riguardano?": da validare con un tecnico IGEA (`tema/igea/assets/js/igea.js`, funzione `risultato`).
- [ ] Tabella della formazione: da validare sull'Accordo Stato-Regioni del 17 aprile 2025; alimentaristi, norma regionale del Lazio.
- [ ] Formazione: modalità (aula, videoconferenza, e-learning) e catalogo attuale da migrare (23 corsi in 6 categorie di LearnPress), con i redirect dei vecchi indirizzi.
- [ ] Pagine servizio: quale pagina esistente corrisponde a ciascuna delle quattro aree (Aspetto > Personalizza > Pagine collegate).

## Legale e privacy

- [ ] Testo dell'informativa privacy e della cookie policy, dal consulente privacy.
- [ ] Base giuridica della casella "Ho letto l'informativa privacy" (art. 6.1.b GDPR): conferma del consulente.
- [ ] Durata di conservazione del registro della presa visione (`IGEA_CF_CONSERVAZIONE`, oggi 24 mesi).
- [ ] D14: licenza delle fotografie di stock usate nei servizi. Senza ricevuta: riacquisto o altre immagini.

## Tecnico

- [ ] Accesso a Plesk per creare lo staging (lo esegui tu, con la guida in `docs/installazione.md`).
- [ ] Nome del nuovo dominio.
- [ ] Indirizzi dei feed di INAIL e Ministero del Lavoro, verificati dallo staging.
