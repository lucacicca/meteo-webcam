## v3.46 (9 novembre 2026) — pagina full con "Torna alla pagina della webcam" + tooltip

- Richiesta di Luca dopo il collaudo della v3.45: una volta arrivati sull'immagine piena non c'era modo di tornare indietro se non il back del browser.
- `/webcam/{slug}/full` aperto come **navigazione top-level** (click dal lightbox, link diretto, barra indirizzi) risponde ora con una pagina HTML minimale (dark, immagine a dimensione massima) con il link testuale **"← Torna alla pagina della webcam."** che rimanda al post/pagina con lo stesso slug (`get_page_by_path`, fallback home). Pagina `noindex`, `Cache-Control: no-store`.
- Le richieste `<img>` della stessa URL (lightbox, embed, admin, og:image) continuano a ricevere il WebP raw: la distinzione usa l'header `Sec-Fetch-Dest: document` (tutti i browser moderni); i client che non lo mandano ricevono l'immagine come prima, zero rotture retroattive.
- Tooltip **"Clicca per vedere l'immagine alla massima risoluzione"**: title dell'anchor della webcam nella pagina (prima "Apri a dimensione piena") e title anche sull'`<img>` aperta nel lightbox.
- Deploy: FTP del plugin in wp-content/plugins/ + flush W3TC. Verifiche: (1) click sull'immagine nel lightbox → pagina full con link di ritorno; (2) il link riporta alla pagina webcam; (3) hover sull'immagine nel post e nel lightbox mostra il tooltip; (4) il lightbox dentro il post funziona ancora (l'<img> del lightbox non deve ricevere l'HTML: controllo visivo che l'overlay mostri l'immagine).

## v3.45 (9 novembre 2026) — lightbox: click sull'immagine → pagina full

- Richiesta di Luca: nel lightbox della webcam (shortcode `[mwp_webcam]`), il click SULL'IMMAGINE aperta in overlay ora porta alla pagina dell'immagine piena (`/webcam/{slug}/full?ts=...`, stessa URL cache-bustata già usata per l'src dell'overlay). Prima il click sull'immagine non faceva nulla.
- Chiusura lightbox invariata: ESC o click sullo sfondo fuori dall'immagine.
- CSS: cursore `pointer` sull'immagine in overlay (lo `zoom-out` resta sullo sfondo) per segnalare la navigazione.
- Nessun altro cambiamento: fetch, cron, cache, dataLayer e admin invariati.
- Deploy: FTP del file plugin in wp-content/plugins/ + flush page cache W3TC; verifica su una pagina webcam (aprire il lightbox → click sull'immagine → pagina full; ESC/Click fuori → chiusura).

## Tema Meteo Webcam — v2.5 (6 novembre 2026) — versione pulita: rimosso lo snippet mwt_focuskw + style.css allineato a 2.5

- Luca rivede le keyword generate dallo snippet e decide di inserire le frasi chiave Yoast **a mano** nei ~26 post senza: nessuna generazione automatica. Lo snippet una-tantum `mwt_focuskw` (v2.4/v2.4.1, MAI caricate in produzione — l'FTP di Tophost era giù) viene rimosso da functions.php come da piano.
- functions.php v2.5 = v2.3 + aggiornamento del commento di versione e della nota di deploy 9: diff verificato riga per riga, uniche differenze = riga MWT_VERSION e nota 9; parentesi graffe bilanciate 44/44 come la v2.3.
- **style.css: header `Version:` portato da 2.0 a 2.5** (richiesta di Luca: è il numero che WordPress mostra nella pagina Temi, wp-admin/themes.php, ed era rimasto indietro dal lavoro v2.0). Nessuna regola CSS modificata.
- README.md allineato: tema v2.1 → v2.5 nelle righe dei componenti e della struttura repo.
- `MWT_VERSION` 2.5 (cache-bust `?ver=2.5` sul CSS).
- Deploy: FTP di functions.php + style.css in wp-content/themes/meteo-webcam/ + flush page cache W3TC. Verifiche: `?ver=2.5` sul CSS nel sorgente, versione 2.5 nella pagina Temi di wp-admin, `?mwt_focuskw=1` non risponde più (corretto).

## Tema Meteo Webcam — functions.php v2.4.1 (6 novembre 2026) — mwt_focuskw: regola "parte specifica" (rivede la v2.4 mai caricata)

- Luca rivede la regola della v2.4 (titolo intero in minuscolo, mai caricata): nei titoli a due parti divise da "-" la frase chiave deve essere la parte MENO GENERICA, PIÙ SPECIFICA e NON USATA da altri post. Casi di Luca: "Rimini - Vista sulla spiaggia" → "rimini"; "Antartide - Stazione McMurdo (The Observation Hill)" → "stazione mcmurdo" (parentesi tonde scartate).
- Scoring nel pick (vince il punteggio più basso): SQUALIFICATA = già frase chiave di un altro post o assegnata prima nella stessa run (wpdb su _yoast_wpseo_focuskw + set runtime, anche nel dry-run per coerenza col go); +100 = formula generica da titolo webcam (inizia con vista/veduta/panorama/scorcio/immagine/guardando o contiene "vista sul/la/dal..."); +10 per ogni ALTRO titolo pubblicato che contiene la parte (più citata = più generica); a parità vince la parte destra (il soggetto specifico sta spesso a destra). Se nessuna parte è utilizzabile la riga esce come **MANUALE**: nessuna assegnazione, il post resta da sistemare a mano (meglio che assegnare keyword sbagliate).
- Dry-run arricchito: colonna Nota con punteggi delle due parti ("scelto X (0) su Y (100 generica)") e conteggio dei MANUALE nel riepilogo.
- Collaudo logica su casi campione (porting TS): "Antartide - Stazione McMurdo" → stazione mcmurdo ✓; "Rimini - Vista sulla spiaggia" → rimini, ma con "rimini" già usata da un altro post → MANUALE ✓; "Lerici - Vista sul Golfo dei Poeti..." → lerici ✓; "Le Grazie - Portovenere" → le grazie (portovenere citata in altro titolo) ✓.
- Invariato il resto: hook template_redirect + admin_init, manage_options, idempotenza, solo postmeta, nessun flush, snippet da rimuovere in v2.5. `MWT_VERSION` 2.4.1. Deploy identico alla v2.4: FTP del solo functions.php → dry-run → go=1 → campione in editor Yoast.

## Tema Meteo Webcam — functions.php v2.4 (6 novembre 2026) — Yoast: frase chiave automatica per i post senza (snippet una-tantum mwt_focuskw)

- Obiettivo: assegnare una frase chiave Yoast ai post pubblicati che non ce l'hanno. Dal censimento del 6/11 ~506 post su 532 ce l'hanno già (pm.csv): restano ~26. Regola scelta da Luca: frase chiave = titolo del post in minuscolo, coerente con le frasi chiave già in uso sul sito ("monte amiata", "isola del giglio", "soraga"); solo post pubblicati, niente pagine né bozze.
- Snippet una-tantum `mwt_focuskw_fill` (hook `template_redirect` + `admin_init`, riservato a `manage_options`): `?mwt_focuskw=1` dry-run con tabella ID/titolo/frase chiave proposta, `?mwt_focuskw=1&go=1` applica con `update_post_meta` sul postmeta `_yoast_wpseo_focuskw`. Idempotente: i post con frase chiave già impostata vengono saltati (rilanciare è sicuro).
- Nessun impatto sul frontend: è solo postmeta letto dall'analisi Yoast in admin; nessuna revisione creata, nessun flush di cache richiesto.
- Nota attesa: i post con testi molto corti mostreranno poi qualche check Yoast rosso (densità/keyphrase length): è il prezzo dell'analisi su testi sottili, non un errore dello snippet.
- SNIPPET TEMPORANEO: rimuovere a lavoro completato (versione pulita v2.5). `MWT_VERSION` bumpata a 2.4.
- Deploy: FTP del solo functions.php in wp-content/themes/meteo-webcam/ → dry-run `?mwt_focuskw=1` (revisione della lista) → `?mwt_focuskw=1&go=1` → controllo campione nell'editor di Yoast (l'analisi appare sui post nuovi). Nessun flush W3TC necessario.

## Tema Meteo Webcam — functions.php v2.3 (6 novembre 2026) — versione pulita: rimosso lo snippet mwt_link_rollout

- Rollout completato in produzione il 6/11: **756 link interni** su ~370 pagine (750 registrati + 6 del test v2.2.2/v2.2.3, vedi docs/notes.md 6/11 (13)). Lo snippet una-tantum `mwt_link_rollout` della v2.2.4 (764 item nowdoc + helper + pagina admin) viene rimosso da functions.php come previsto.
- Nessun altro cambiamento funzionale: tutte le funzioni permanenti invariate (filtro XSLT sitemap, VideoObject JSON-LD, filtri cron batch 120 / budget 55s, setup tema, widget, excerpt); diff verificato riga per riga (add_action/add_filter 14=14, parentesi graffe bilanciate).
- `MWT_VERSION` bumpata a 2.3 (cache-bust del CSS invariato). Nuova nota di deploy 8 nel blocco "Note per la messa in produzione".
- Il registro dei link applicati resta nell'opzione DB `mwt_link_rollout_applied` (l'undo via URL non esiste più in v2.3): per un eventuale undo ricaricare temporaneamente la v2.2.4 e usare `?mwt_link_rollout=1&undo=1` (globale o per lotto), poi ri-caricare la v2.3. Quando il lavoro è chiuso, l'opzione si può eliminare da wp_options.
- Deploy: FTP del solo functions.php in wp-content/themes/meteo-webcam/ + flush page cache W3TC.

## Tema Meteo Webcam — functions.php v2.2.4 (6 novembre 2026) — ROLLOUT link interni a lotti: snippet mwt_link_rollout (764 righe)

- Sostituisce lo snippet di test `mwt_link_test` (v2.2.x) con il rollout completo del report "Link interni contestuali: 796 suggerimenti": 764 righe = 751 OK + 13 con anchor alternativo (incluse le 4 del test v2.2.3); escluse le 32 righe concorrenti senza alternativa (le salterebbe comunque il check Yoast a runtime).
- Le 764 frasi sono state verificate offline sul dump: presenti verbatim nel contenuto del post (763 OK; una corretta a mano: #842 "rifugio città di Mantova" con l'accento reale del testo, l'audit l'aveva normalizzata senza).
- Lavoro a lotti da 50 righe (16 lotti): `?mwt_link_rollout=1` riepilogo con stato per lotto, `&batch=N` dry-run del lotto, `&batch=N&go=1` applica, `&batch=N&undo=1` rimuove i link del lotto, `&undo=1` rimuove TUTTI i link applicati.
- Registro dei link applicati nell'opzione `mwt_link_rollout_applied` (righe "lotto|ID|frase|slug"): go idempotente tra i lotti, undo per lotto o globale, riepilogo con contatore per lotto. I 6 link già applicati dal test v2.2.2/v2.2.3 risultano "già presente" e vengono saltati senza errore.
- Controlli ereditati da v2.2.x, tutti attivi: prima occorrenza libera (skip dentro <a>/tag HTML/shortcode/commenti HTML <!-- ... -->), anchor con il testo originale del contenuto, skip se l'anchor contiene la frase chiave Yoast del post ("link concorrenti"), skip se un link al target esiste già, update $wpdb senza revisioni, w3tc_flush_post per ogni post toccato.
- Deploy: FTP del solo functions.php v2.2.4 → `?mwt_link_rollout=1` (riepilogo) → `&batch=1` (dry-run: controllare i contesti) → `&batch=1&go=1` → lotto per lotto fino al 16 → verifica Yoast su alcune pagine toccate → a lavoro finito rimozione dello snippet (versione pulita v2.3).

## Tema Meteo Webcam — functions.php v2.2.3 (6 novembre 2026) — mwt_link_test: anchor alternativi per le righe concorrenti

- Dopo il go v2.2.2 (2/10 applicati, 8 SKIP concorrenti) Luca sceglie la strada degli anchor alternativi: per le righe in cui l'anchor originale coincide con la frase chiave Yoast della pagina, si cerca nel testo dello stesso post una menzione del toponimo del target che non sia concorrente.
- Scan offline sul dump per le 8 righe skippate (menzioni dei toponimi dei target nel testo dei post, verificate libere in prosa): 4 righe hanno un'alternativa pulita → `mwt_link_test_items` aggiornato: #2026 e #2235 "Costa Concordia" → **"isola del Giglio"** (→ /isola-del-giglio-porto/, prima occorrenza "nei pressi dell'isola del Giglio" / "ecosistema dell'isola del Giglio"), #1662 "Forni di Sopra" → **"Cridola"** (→ /monte-cridola-da-laggio-di-cadore/, "vette dolomitiche dei monti Cridola"), #1875 "Santa Fiora" → **"Monte Amiata"** (→ /vetta-monte-amiata/, "nell'area del Monte Amiata").
- Le altre 4 (#1546 Campo Imperatore, #2210 San Teodoro, #625 stazione McMurdo, #9443 Alpi Apuane) non hanno menzioni del toponimo del target nel testo del post: restano con l'anchor originale e vengono saltate dal controllo Yoast (nessun link su quelle pagine).
- I controlli runtime (commenti HTML, frase chiave Yoast, occorrenza libera, idempotenza, undo) si applicano invariati anche ai nuovi anchor. `MWT_VERSION` bumpata a 2.2.3.
- Deploy: FTP del solo functions.php → dry-run `?mwt_link_test=1` (le 4 righe nuove mostrano il contesto, le 4 senza alternativa mostrano SKIP concorrente, #7846/#8338 risultano "già presente") → `?mwt_link_test=1&go=1` → verifica Yoast su Costa Concordia Parbuckling / Genova relitto (nessun link concorrente).
- Per il rollout completo delle ~786 righe: serve l'export wp_postmeta (meta_key `_yoast_wpseo_focuskw`, CSV da phpMyAdmin) per riprocessare il report con le frasi chiave reali e cercare anchor alternativi per tutte le righe concorrenti (stesso metodo dello scan fatto per il test).

## Tema Meteo Webcam — functions.php v2.2.2 (6 novembre 2026) — mwt_link_test: controllo link concorrenti Yoast + modo undo

- Luca segnala l'avviso Yoast "Link concorrenti" sulla pagina /gran-sasso-rifugio-le-fontari-a-campo-imperatore/ dopo il go: l'anchor applicato "Campo Imperatore" contiene la frase chiave del post, e Yoast segnala ogni link il cui anchor utilizza la frase chiave ("hai un link che utilizza la tua frase chiave o un sinonimo come testo di ancoraggio") perché dice a Google che l'altra pagina compete per la stessa frase chiave. Le righe autoreferenziali del report (anchor = toponimo della pagina stessa) sono il caso tipico.
- Nuovo controllo in `mwt_link_test`: per ogni riga legge la frase chiave Yoast del post (`_yoast_wpseo_focuskw`) e SALTA la riga se la frase chiave compare integralmente nell'anchor (normalizzate: minuscole, punteggiatura/simboli → spazi, confini di parola). Dry-run e go riportano lo skip con la frase chiave trovata. Yoast free: solo frase chiave, niente sinonimi.
- Nuova modalità undo: `?mwt_link_test=1&undo=1` rimuove i link applicati dallo snippet (solo le coppie esatte href+anchor della lista), ripristinando il testo com'era (update $wpdb senza revisioni + `w3tc_flush_post` per post). Serve a ripulire le righe già applicate che il nuovo controllo ora salterebbe (es. #1546).
- `MWT_VERSION` bumpata a 2.2.2. Snippet una-tantum, da rimuovere a test/rollout completato (versione pulita v2.3).
- Deploy: FTP del solo functions.php → dry-run `?mwt_link_test=1` (le righe concorrenti mostrano lo SKIP) → `?mwt_link_test=1&undo=1` (rimuove i link applicati) → `?mwt_link_test=1&go=1` (riapplica solo le righe valide) → verifica Yoast sulla pagina Gran Sasso (avviso sparito).

## Tema Meteo Webcam — functions.php v2.2.1 (6 novembre 2026) — mwt_link_test: salta le occorrenze dentro i commenti HTML

- Bug emerso nel dry-run del test delle 10 righe (Luca, 6/11): nel post #1546 (Gran Sasso - Rifugio Le Fontari a Campo Imperatore) la prima occorrenza "libera" di "Campo Imperatore" trovata dallo snippet era dentro un blocco legacy commentato (`<!-- [caption]…[/caption] + <a><img></a> -->`) piazzato prima dello shortcode [mwp_webcam]: non visibile in pagina. Il link sarebbe finito dentro il commento HTML senza alcun effetto sul frontend (e la menzione buona in prosa, "L'Hotel Campo Imperatore", sarebbe restata non linkata).
- Fix: `mwt_link_test_find` salta anche le occorrenze dentro commenti HTML (`<!--` aperto senza `-->`), verificate sul prefisso intero del contenuto e non sul tail di 500 caratteri (un commento può essere lungo). Le altre 9 righe del test ricontrollate sul dump: tutte corrette (prima menzione in prosa visibile).
- `MWT_VERSION` bumpata a 2.2.1. Snippet una-tantum invariato nel resto (da rimuovere a test/rollout completato = versione pulita v2.3).
- Deploy: FTP del solo functions.php; nuovo dry-run `?mwt_link_test=1` (la riga #1546 ora punta a "L'Hotel Campo Imperatore"), poi `&go=1`.

## Tema Meteo Webcam — functions.php v2.2 (6 novembre 2026) — snippet una-tantum: test link interni contestuali

- Test del 6/11 sulle prime 10 righe del report "Link interni contestuali: 796 suggerimenti" (audit interno, vedi docs/notes.md 6/11 (6)): inserimento di 10 anchor contestuali nel testo dei post, NON nella sezione correlate.
- Nuovo snippet una-tantum `mwt_link_test` in functions.php: pagina strumento in wp-admin con `?mwt_link_test=1` (dry-run: per ogni post riporta la frase trovata, il contesto e il link previsto) e `&go=1` (applica).
- Per ogni riga cerca nel post la PRIMA occorrenza libera della frase (case-insensitive, confini di parola, non dentro `<a>` esistenti, non dentro tag HTML o shortcode), la avvolge in `<a href="https://www.meteo-webcam.it/{slug}/">` preservando maiuscole/accenti originali; update via $wpdb senza revisioni; `w3tc_flush_post` per ogni post toccato; idempotente (skip se il link è già presente).
- Le 10 righe del test: Campo Imperatore → Lago di Campotosto (#1546), Costa Concordia → Isola del Giglio Porto (#2026 e #2235), Forni di Sopra → Monte Cridola (#1662), Santa Fiora → Vetta Monte Amiata (#1875), San Teodoro → Capo Coda Cavallo (#2210), stazione McMurdo → Antartide Observation Hill (#625), lago di Garda → Sirmione (#7846), valle Aurina → Cadipietra Klausberg (#8338), Alpi Apuane → Canevara Monte Grondilice (#9443).
- SNIPPET TEMPORANEO: rimuovere dopo test/rollout completato (prossima versione pulita = v2.3). `MWT_VERSION` bumpata a 2.2.

## v3.44 (6 novembre 2026) — dataLayer di base: TUTTE le webcam della pagina

- Richiesta di Luca: tracciare tutte le webcam quando una pagina ne contiene più di una. Estende il filtro `gtm4wp_compile_datalayer` della v3.43: raccoglie **tutti** gli shortcode `[mwp_webcam]` del contenuto (slug deduplicati, in ordine di contenuto).
- Schema del dataLayer di base su ogni pagina con webcam: `webcam_slug` + `webcam_name` = webcam "principale" (la prima; retrocompatibili con le variabili GTM già configurate) e `webcam_slugs` + `webcam_names` = lista completa separata da virgole (es. `webcam_slugs: "riale,moena,pitigliano"`). Le liste sono stringhe, non array, perché GA4 accetta parametri stringa; anche con una sola webcam le chiavi sono emesse (lista di un elemento) → schema costante.
- In GTM: nuove variabili Data Layer `webcam_slugs` / `webcam_names`; per i report registrare `webcam_slugs` come dimensione personalizzata evento se si vuole la lista lato GA4 (attenzione: la dimensione ha un limite di ~100 caratteri, liste lunghissime vengono troncate da GA4). L'evento `webcam_offline` è invariato: spara comunque un push per ogni webcam offline, quindi le singole offline si vedono sempre una per una.
- Per l'analisi per-singola-webcam resta consigliato `webcam_slug`/l'evento `webcam_offline`; le liste servono per il contesto della pagina (page_view, content grouping).
- Nota page cache invariata: i valori riflettono la config webcam al momento della generazione dell'HTML (W3TC).

## v3.43 (6 novembre 2026) — webcam_slug e webcam_name nel dataLayer di base (GTM4WP)

- Richiesta di Luca: le variabili `webcam_slug` e `webcam_name` presenti sul dataLayer di base di tutte le pagine, non solo dentro l'evento `webcam_offline`. Implementato con `add_filter('gtm4wp_compile_datalayer', ...)`: GTM4WP compila il dataLayer in `<head>` prima del container e il filtro è API pubblica invariata dalla rewrite 2.0.
- Logica: sulle pagine singolari viene cercato il primo shortcode `[mwp_webcam slug="…"]` nel contenuto del post/pagina; la webcam trovata (la "principale"; con più webcam vince la prima) viene aggiunta al dataLayer come `webcam_slug` + `webcam_name` (nome dalla config del plugin, fallback = slug). Pagine senza shortcode (home, archivi) non emettono le chiavi; con GTM4WP disattivato il filtro non gira mai.
- **Compatibilità GTM4WP 2.1.1 verificata**: nomi di filtro/azione mantenuti nella 2.0; la 2.1 ha fixato la coercizione `JSON_NUMERIC_CHECK` che alterava i valori stringa aggiunti proprio via `gtm4wp_compile_datalayer` (es. slug tutto-numerici trasformati in numero).
- In GTM: due variabili Data Layer (`webcam_slug`, `webcam_name`) riutilizzabili ovunque; per arricchire il page_view aggiungerle come parametri evento nel tag Google (GA4). `webcam_slug` come dimensione personalizzata evento è già registrata per `webcam_offline` (stessa dimensione, vale per ogni evento che la porta); per `webcam_name` nei report serve una seconda dimensione personalizzata.
- Nota page cache: il dataLayer di base è parte dell'HTML cachato da W3TC → i valori riflettono la config webcam al momento della generazione della pagina.
- L'evento `webcam_offline` della v3.42 è invariato (push dedicato per ogni webcam offline, con failure_count).

## v3.42 (6 novembre 2026) — webcam_offline via dataLayer/GTM al posto del Measurement Protocol

- **Revisione richiesta da Luca prima del deploy** (la v3.41 non è mai stata caricata in produzione): niente più invio server-side via Measurement Protocol. La rilevazione dell'offline resta server-side (fail marker del cron col conteggio dei fallimenti consecutivi, cap tornato a 20 come in v3.40); l'invio diventa client-side, sotto il controllo del tag manager: quando lo shortcode renderizza una webcam con fallimenti consecutivi ≥ soglia (filtro `mwp_ga_offline_threshold`, default 3, ~7 minuti dal guasto), emette `window.dataLayer.push({event:'webcam_offline', webcam_slug:'…', webcam_name:'…', failure_count:N})` — una volta per pagina per webcam (dedup statica). Nuova funzione `mwp_ga_datalayer_offline($cam)`, richiamata dallo shortcode.
- **Setup GTM** (a cura di Luca): trigger di evento personalizzato `webcam_offline` → tag evento GA4 con i parametri; registrare `webcam_slug` come dimensione personalizzata evento in GA4 per vedere quale webcam nei report.
- **Rimossi** rispetto alla v3.41: sezione admin "Notifiche webcam offline su Google Analytics" (Measurement ID + API secret + pulsante test), handler `admin_post_mwp_save_ga` / `wp_ajax_mwp_ga_test`, helper Measurement Protocol (`mwp_ga_cfg`/`mwp_ga_client_id`/`mwp_ga_track`/`mwp_ga_maybe_alert_offline`), opzioni `mwp_ga_*` (mai usate in produzione), evento `webcam_online` e promemoria giornaliero.
- **Nota page cache**: l'HTML col push viene servito cachato da W3TC → l'evento continua a sparare a ogni visualizzazione finché la webcam non torna e la pagina viene rigenerata con l'immagine buona.
- L'immagine di cortesia e il recupero della nota v3.40 introdotti in v3.41 restano invariati.

## v3.41 (6 novembre 2026) — evento GA4 webcam_offline via Measurement Protocol + immagine di cortesia

- **Notifiche GA4 server-side (nessun tag GTM)**: al 3° fallimento consecutivo di fetch (filtro `mwp_ga_offline_threshold`, ~7 minuti dal guasto) il cron invia l'evento GA4 `webcam_offline` via Measurement Protocol con parametri `webcam_slug`, `webcam_name`, `failure_count`; quando la webcam torna online invia `webcam_online`; le webcam ancora morte mandano un promemoria al giorno (il fail marker ora conta fino a 1000 invece di 20, il backoff resta capped a 24h: oltre il count 12 ogni nuovo marker è un giorno di morte in più).
- Configurazione in admin → "Notifiche webcam offline su Google Analytics": Measurement ID (G-…) + API secret (opzioni `mwp_ga_measurement_id` / `mwp_ga_api_secret`; l'API secret si crea in GA4: Amministrazione → Flussi dati → flusso web → Measurement Protocol). Senza entrambi: nessun evento. Pulsante "Invia evento di test webcam_offline" che valida il payload sull'endpoint debug `/debug/mp/collect` (l'endpoint normale risponde 204 anche con API secret sbagliato, quindi non verificherebbe nulla). Registrare `webcam_slug` come dimensione personalizzata evento in GA4 per vederlo nei report.
- **Immagine di cortesia**: la webcam stantia oltre `mwp_stale_max_age` (filtro, default 86400s = 24h) è servita con un SVG "Webcam temporaneamente indisponibile" al posto dell'ultima istantanea (oltre un giorno è fuorviante); anche la webcam senza cache riceve la cortesia invece del 503 (lo shortcode ora emette l'`<img>` lo stesso invece del solo commento HTML invisibile). Lo shortcode emette `?v=off{mtime}` quando la webcam è in cortesia: URL diversa, quindi il browser non serve dalla propria cache l'immagine vecchia; alla ripresa il fetch riuscito cambia l'mtime e l'URL torna quella buona. Le anteprime admin (`?mwp_preview`) continuano a mostrare la cache reale per la diagnosi.
- **Recupero v3.40 (bug trovato il 6/11)**: la nota "Webcam in tempo reale: l'immagine si aggiorna automaticamente." era dichiarata nella changelog v3.40 (commit 4b5f110) ma mai emessa dal codice: il filtro `mwp_refresh_note` non esisteva nel file, solo nell'header. Ora esiste davvero, sotto l'immagine e sopra il figcaption del credito, disattivabile col filtro `mwp_refresh_note` (stringa vuota; riceve `$cam`). Testo dei placeholder client-side unificato in "Webcam temporaneamente indisponibile".
- Nota deploy: al primo giro di cron dopo l'upload, tutte le webcam già morte (fail marker ≥ soglia) sparano subito un `webcam_offline` → inventario immediato dei guasti in GA4, poi un promemoria al giorno finché non vengono sistemate.

## Tema Meteo Webcam — functions.php v2.1 (5 novembre 2026) — sitemap Yoast senza stylesheet XSLT (deprecazione XSLT nei browser)

- Sintomo: aprendo /sitemap_index.xml Chrome mostra il banner "This site uses XSLT; that functionality is being removed from this browser very soon". Chrome 158 (17/11/2026) rimuove il supporto XSLT; anche Firefox e WebKit hanno annunciato la stessa intenzione. L'istruzione `<?xml-stylesheet type="text/xsl" ... main-sitemap.xsl?>` emessa da Yoast serve SOLO alla vista formattata della sitemap per gli umani: Google/Bing leggono l'XML grezzo, quindi NESSUN impatto SEO né sulla scansione.
- Fix: nuovo filtro sul `wpseo_stylesheet_url` in functions.php — l'istruzione xml-stylesheet viene rimossa (XML grezzo, addio banner) finché è di tipo `text/xsl`. Quando Yoast ≥ 28.9 la sostituirà con un foglio di stile CSS (`type="text/css"`), questo passa intatto e la vista leggibile della sitemap torna da sola con l'aggiornamento del plugin.
- `MWT_VERSION` bumpata a 2.1 (cache-bust del CSS invariato, nessun cambio visivo).
- Deploy: caricare via FTP il solo functions.php in wp-content/themes/meteo-webcam/, flush page cache W3TC (le sitemap XML sono page-cached), riaprire /sitemap_index.xml e verificare nel sorgente l'assenza dell'istruzione xml-stylesheet e del banner.

## Tema Meteo Webcam — style.css/functions.php v2.0 (2 novembre 2026) — header description + tags per la preview del tema

- Preparazione alla pubblicazione/preview: header di style.css portato a `Version: 2.0` con Description riscritta (tema leggero per directory di webcam meteo e blog di viaggio, zero JS, un solo foglio di stile, integrazione Meteo Webcam Proxy / Site Kit / AdSense) e riga `Tags:` aggiunta (blog, news, one-column, grid-layout, featured-images, custom-menu, translation-ready).
- Nessun cambio CSS né funzionale: solo metadati dell'header per la schermata Aspetto → Temi e per un'eventuale pubblicazione.
- `MWT_VERSION` bumpata a 2.0 in functions.php per allineare il cache-bust all'header.
- Screenshot `screenshot.png` (1200×900) da caricare via FTP nella cartella del tema.

## Tema Meteo Webcam — functions.php v1.9 (2 novembre 2026) — VideoObject anche per i file video diretti (caso Panomax)

- Secondo caso dal report Video di GSC: anche le pagine con player HTML5 Panomax (`<video><source src="https://video.panomax.com/cams/{id}/videos/{id}_fullHD.mp4">`) non risultavano video indicizzati (es. /cortina-dampezzo-cinque-torri/), pur avendo l'mp4 direttamente nell'HTML come le pagine che funzionano (tre-cime, api.scenaridigitali.com).
- Verifica del 2/11: video.panomax.com BLOCCA i client automatizzati sull'mp4 (protezione anti-hotlink) — probabile causa prima della non-indicizzazione; il VideoObject fornisce comunque a Google tutti i metadati.
- Nuova `mwt_video_file_urls`: raccoglie (deduplicate) tutte le URL di file video `.mp4/.webm/.ogv/.m3u8` presenti nel contenuto del post — copre i `<source>` dei player HTML5 (Panomax) e gli mp4 incollati come link.
- `mwt_video_schema` emette ora un VideoObject anche per questi file: `contentUrl` = URL del file, senza `embedUrl`; thumbnail = featured image (`large`) obbligatoria (requisito Google; senza featured il file viene saltato), `uploadDate` = data del post. Se stessi YouTube incolonnati alla stessa pagina, schema a blocchi separati.
- `MWT_VERSION` bumpata a 1.9. Filtro `mwt_video_schema` invariato.
- Nota limite: se Googlebot non riesce a scaricare l'mp4 da video.panomax.com (blocco anti-bot verificato), il video non verrà indicizzato comunque — in quel caso le strade sono contattare Panomax o accettare il limite; lo schema è in ogni caso la premessa necessaria.

## Tema Meteo Webcam — functions.php v1.8 (2 novembre 2026) — VideoObject JSON-LD per embed YouTube (GSC video indexing)

- Report Video di Google Search Console: le pagine con video YouTube embed NON venivano indicizzate come pagine video (es. /san-pellegrino-in-alpe/), mentre quelle con file .mp4 direttamente nell'HTML sì (es. /tre-cime-di-lavaredo-dalla-malga-langalm/). Google vede l'iframe (l'ispezione GSC mostra video content URL + thumbnail fornita dalla piattaforma) ma senza dati strutturati il video resta "supplementary content" e la pagina watch di YouTube resta l'unica ospite canonica del video.
- Nuova `mwt_video_schema` (hook `wp_head`, prio 20): sui post singoli pubblicati scandisce il contenuto per ID YouTube — `youtube.com/embed|live|v/{ID}`, `youtube-nocookie.com/embed/{ID}`, `youtu.be/{ID}`, coprendo sia gli iframe oEmbed di WordPress sia quelli incollati a mano — ed emette un VideoObject per ogni video unico (dedup per ID).
- Campi: `name` (titolo post + " — video"), `description` (excerpt, 200 caratteri), `thumbnailUrl` (featured image in taglia `large`; fallback `i.ytimg.com/vi/{ID}/maxresdefault.jpg`), `uploadDate` (data del post), `embedUrl` + `contentUrl`, `isFamilyFriendly`, `inLanguage`.
- Filtro `mwt_video_schema` per modificare o disattivare l'output (array vuoto = niente schema). `MWT_VERSION` bumpata a 1.8 (cache-bust del CSS invariato, nessun cambio visivo).
- Deploy: caricare via FTP il functions.php, flush page cache W3TC, verificare il blocco `<script type="application/ld+json">` su una pagina con embed (es. /san-pellegrino-in-alpe/), poi richiedere la reindicizzazione in GSC e monitorare il report Video dopo qualche giorno.
- ESITO: caricata in produzione il 2/11, VideoObject verificato nel sorgente (san-pellegrino-in-alpe, porto-di-pisa-marina-di-pisa); validazione GSC del motivo "Video isn't on a watch page" avviata il 2/11.

## v3.40 (1 novembre 2026) — nota standard sotto la webcam (visibile anche agli agenti AI)

- Lo shortcode `[mwp_webcam]` emette ora una piccola nota sotto l'immagine: "Webcam in tempo reale: l'immagine si aggiorna automaticamente." — testo statico nel DOM, utile al lettore e fondamentale per i crawler/agenti AI (ChatGPT, Gemini, Perplexity), che altrimenti non possono sapere che l'immagine è una livecam e non una foto d'archivio.
- Nota piccola e neutra (12.5px, grigio), sotto l'immagine e sopra l'eventuale figcaption del credito; zero impatto su CLS perché è testo che segue l'immagine già dimensionata.
- Personalizzabile col filtro `mwp_refresh_note` (riceve `$cam`): restituire una stringa vuota per disattivarla.
- Nessun altro cambiamento: fetch, cron, cache e admin invariati.
- NOTA v3.41: la nota non era in realtà mai stata emessa dal codice (solo la changelog dell'header era stata aggiornata); recuperata e attivata nella v3.41.

## Tema Meteo Webcam — v1.7 (1 novembre 2026) — embed video responsive

- Su mobile gli iframe YouTube incollati nei post con l'editor classico mantengono width="560" fissa e sforano la colonna del contenuto (es. /abetone-campo-scuola-e-piazzale/): add_theme_support('responsive-embeds') copre solo i blocchi Gutenberg, non usati nei post storici.
- style.css: tutti gli iframe/object/embed del contenuto non possono superare la larghezza del contenitore (max-width: 100%); gli iframe YouTube (youtube.com / youtu.be / youtube-nocookie) diventano full-width con aspect-ratio 16:9 e height auto.
- Header di style.css portato a Version: 1.7 e MWT_VERSION bumpata a 1.7 per il cache-bust (?ver=1.7).

## Tema Meteo Webcam — functions.php v1.6 (31 ottobre 2026) — versione pulita post-conversione

- Rimossa l'intera sezione snippet una-tantum `mwt_fi_webp` della v1.5: conversione featured images JPEG→WebP completata in produzione (coda esaurita, esiti OK per ogni allegato: pieno/large/mwt-card).
- Nessun altro cambiamento funzionale: filtri cron `mwp_cron_batch` 120 e `mwp_cron_time_budget` 55s invariati.
- Pulizia successiva (via FTP, non urgente): i vecchi file .jpg delle featured restano in `wp-content/uploads/` come backup; cancellarli solo dopo qualche giorno di verifica visiva del sito.

## Tema Meteo Webcam — functions.php v1.5 (30 ottobre 2026) — featured images JPEG → WebP

- Snippet una-tantum `mwt_fi_webp`: converte in WebP (qualità 82) le immagini in evidenza dei post/pagine pubblicati — il file pieno (solo se larghezza ≤ 2560px: oltre si rischia la memoria GD sull'hosting condiviso), la taglia `large` (featured del post singolo, ricodificando il file già ridimensionato) e `mwt-card` 640×360 (se manca viene generata dal pieno: rigenero delle thumbnail rinviato).
- Aggiorna `_wp_attached_file`, `_wp_attachment_metadata` e `post_mime_type`; i vecchi .jpg restano su disco come backup (rollback via FTP, pulibili a fine conversione).
- Pagina strumento in wp-admin: `?mwt_fi_webp=1` dry-run, `&go=1` converte 10 allegati per richiesta, `&auto=1` auto-riguarda la pagina fino a fine coda, `&reset=1` riscansiona. Progresso nell'opzione `mwt_fi_webp_todo`; a fine coda flush W3TC automatico.
- SNIPPET TEMPORANEO: rimuovere a conversione completata e verificata (la versione pulita successiva = v1.6).

## v3.39 (30 ottobre 2026) — autenticazione HTTP Basic/Digest per URL con credenziali

- Gli URL con credenziali (`http://utente:password@host/...`, tipico delle webcam Hikvision via ISAPI, es. `/ISAPI/Streaming/channels/102/picture`) fallivano con 401 pur funzionando da browser: curl con le credenziali dentro l'URL tenta SOLO il Basic auth, mentre questi server richiedono il Digest.
- Fix: le credenziali vengono estratte dall'URL (nuova helper `mwp_split_auth`) e passate via `CURLOPT_USERPWD` + `CURLAUTH_ANY`, così libcurl negozia Basic o Digest automaticamente. Applicato sia al fetch singolo (`mwp_fetch` / pulsante Test) sia al fetch parallelo del cron (`mwp_curl_multi_fetch`).

## v3.38 (29 ottobre 2026) — retry HTTP/1.1 forzato dopo errori di framing HTTP/2

errno 92 ("Stream error in the HTTP/2 framing layer") o 16: la libcurl dell'hosting negozia HTTP/2 con certi server (es. su Google Cloud: caso reale includi.insidesrl.eu/dueporti/dueporti.jpg, Test fallito 470ms errno 92, server 35.214.219.154, GCP, DNS e connessione OK) e lo stream muore a metà.

- mwp_fetch ora riprova automaticamente forzando HTTP/1.1 dopo questi errori (con eventuale secondo tentativo insecure se serve il certificato).
- Il cron manda in coda di retry sequenziale di fine run anche i fetch paralleli falliti con errno 16/92: il retry sequenziale forza HTTP/1.1 e il server risponde.

## v3.37 (29 ottobre 2026) — il truncamento da budget non è più un fallimento

**Sintomo**: webcam sane (macinaie-monte-amiata-3, poi campotosto) diventavano oldest_slug e non venivano MAI ricaricate dal cron, pur riuscendo al pulsante Test in <100 ms. Elimina+ricrea apparentemente "guariva", ma il ruolo di vittima passava alla webcam sana successiva per età.

**Meccanismo**: le webcam morte senza cache hanno age = PHP_INT_MAX e siedono per sempre in testa alla coda stale. Quando il loro backoff scade (tutte insieme, ogni ~4,3 ore: il cap di 24h era irraggiungibile perché il raddoppio si fermava a count 9) bruciano l'intero budget del run (10-25s per gruppo di 6 parallelo), e tutto ciò che sta dietro il fronte — a partire dalla webcam sana più vecchia — viene raccolto come errno 0 + http 0 (richiesta mai completata) e marcato come FAIL. Il suo backoff si sincronizza con quello delle webcam morte e la webcam resta affamata per sempre.

**Fix**:
- errno 0 + http 0 = richiesta troncata dal budget del gruppo, NON un fallimento della webcam: niente marker di backoff, la webcam resta in coda e il run successivo la serve con budget pieno. Gli errori veri (errno > 0 o http >= 400) mantengono backoff e retry.
- Backoff realmente fino a 24h (count fino a 12): le webcam morte rientrano in coda una volta al giorno invece che ogni ~4 ore.

## v3.36 (29 ottobre 2026) — retry sequenziale per i fetch paralleli mai completati

Nel cron parallelo, un fetch che non ha MAI completato la richiesta (errno 0 + http 0: handle ancora in volo quando scade il budget del gruppo, o stall di curl_multi su quel server) veniva marcato come un fallimento e mandato in backoff progressivo senza alcun retry. Era il caso delle webcam che risultano OK al pulsante Test (mwp_fetch sequenziale) ma falliscono sempre nel cron: es. macinaie-monte-amiata-3 (Test OK 1920×1080, 224 KB, 84 ms; cron FAIL http 0 a ogni run, oldest 42h). Ora questi casi entrano nella coda di retry sequenziale di fine run (stesso percorso del Test): se il retry riesce, il fail marker viene cancellato e il backoff si azzera.

## v3.35 (28 ottobre 2026) — rimozione log diagnosi
- Rimosso il log temporaneo su wp-content/mwp-cron-debug.log (introdotto in v3.33): la diagnosi del cron è chiusa con esito positivo — bug wrapper v3.32 fixato (v3.33), backoff progressivo attivo (v3.34). Verifica del 28/10 ore 16:27-17:03: run fino a 120 fetch riuscite in ~30s, cicli completi sul parco webcam, run vuoti (0 fetch, 0s) quando tutto è fresco, ~1.575 fetch riuscite in ~35 minuti contro le ~1,5-2/minuto pre-fix.
- Nessun altro cambiamento funzionale. Il file mwp-cron-debug.log esistente può essere cancellato dall'hosting.

## v3.34 (28 ottobre 2026) — backoff progressivo dei fetch falliti
- Il log v3.33 ha mostrato che ~95 webcam morte (404/403/500/503/connessione) restavano per sempre in testa alla coda stale e bruciavano l'intero budget dei run (4 run su 5 con 0 successi e 42-78 fail l'uno).
- Ora ogni fallimento consecutivo raddoppia l'attesa prima del retry: 60s, 2m, 4m, 8m, ... fino a 24h (tetto min(86400, base * 2^(count-1)), base = mwp_backoff_seconds). Il marker -fail.marker contiene il conteggio dei fallimenti consecutivi (retrocompatibile: marker vuoto pre-3.34 = 1). Al primo successo marker cancellato, contatore azzerato.
- Helper: mwp_fail_marker_path/mwp_fail_backoff/mwp_in_backoff/mwp_mark_fail/mwp_clear_fail; cron, cold-start (mwp_ensure_full) e status endpoint usano tutti la stessa logica.
- Il log di diagnosi resta attivo, si rimuove in v3.35 dopo verifica.

## v3.33 (28 ottobre 2026) — bugfix critico fetch parallelo + log diagnosi
- BUGFIX v3.32: il cron parallelo passava a mwp_curl_multi_fetch e ai cicli di scrittura il wrapper ['age','cam'] invece della webcam — $cam['url']/$cam['slug'] assenti → zero richieste in volo, ogni webcam stale riceveva solo il fail.marker e non veniva MAI scaricata dal cron (le poche fetch osservate arrivavano dai cold-start dei visitatori). Il ciclo completo torna ai ~5-10 minuti progettati in v3.32.
- LOG TEMPORANEO di diagnosi (rimuovere in v3.34): ogni run scrive su wp-content/mwp-cron-debug.log start (stale_total/batch/budget/parallel/multi), esito SUCCESS/FAIL per webcam con motivo dell'errore (curl error/errno/http code/type), conteggi finali con elapsed.
- Nessun altro cambiamento: validazione e timeout restano quelli della v3.32.

## v3.32 (28 ottobre 2026) — fetch parallelo nel cron
- Il cron scarica le webcam in parallelo con `curl_multi`, in gruppi da `mwp_cron_parallel` richieste (nuovo filtro, default 6). Con ~700 webcam, molte lente (timeout 10s/25s ciascuna), il ciclo sequenziale bruciava l'intero budget del run su poche richieste appese: il costo dominante diventa il fetch più lento del gruppo, non la somma di tutti.
- I fallback rari (certificato self-signed → secondo tentativo senza verifica; https morto in timeout → ritento in http) restano, ma in coda sequenziale a fine run e solo con tempo residuo nel budget.
- Su hosting senza `curl_multi` degradazione automatica al ciclo sequenziale di sempre (`mwp_fetch`).
- Con functions.php v1.4 (budget 55s, cron-job.org ogni minuto) e per-fetch medio ~2s, il ciclo completo su ~700 webcam passa da ~26-28 minuti a ~5-10 minuti.

## Tema Meteo Webcam — functions.php v1.4 (28 ottobre 2026)
- Filtri cron alzati per il parco webcam post-import (~713): `mwp_cron_batch` 60 → 120, `mwp_cron_time_budget` 50 → 55s. Con cron esterno ogni 60s il budget resta volutamente sotto il minuto per evitare run sovrapposti sui worker PHP condivisi. Ciclo completo stimato ~26-28 min; se `fresh` resta 0 su /webcam/mwp-status il prossimo passo è il fetch parallelo (curl_multi) lato plugin.

## v3.31 (ottobre 2026) — cron leggero + ricerca admin
- Il cron fa SOLO fetch + scrittura dell'immagine piena: rimosse la cancellazione delle varianti e il warm-up (full WebP + 4 taglie = 5 conversioni GD per webcam) dal run. Le varianti si rigenerano pigramente alla prima richiesta (il routing confronta già i filemtime). Costo per webcam nel budget del cron: ~2s invece di ~5s → con lo stesso budget/minuto passano ~2,5× più webcam (ciclo a 700 webcam stimato ~25 min invece di ~65).
- Campo di ricerca nella pagina admin (nome, slug o attribuzione, param `mwp_s`), combinato con la paginazione.

## v3.30 (ottobre 2026) — admin a scala 700 webcam
- Paginazione server-side di elenco e anteprime: 10/25/50/100 per pagina (menu a tendina con frecce avanti/indietro, default 10).
- Ordinamento per data di inserimento decrescente: nuovo campo `added` (timestamp Unix) alla creazione (form admin o import CSV); le webcam create prima della v3.30, senza timestamp, restano in fondo in ordine alfabetico.
- Salvataggio del form riscritto a "merge per pagina": il POST contiene solo le righe visibili (marker `mwp_page_slugs[]`), eliminazione esplicita via `mwp_delete_slugs[]` (cache pulita), webcam delle altre pagine intatte. Con 100/page il POST resta sotto ~1000 variabili: `max_input_vars` non è più un limite nemmeno per la tabella.

## v3.29 (ottobre 2026) — cache busting
- Lo shortcode emette le URL con `?v={mtime del file pieno}`.
- Scoperta: Tophost aggiunge via nginx una `Cache-Control: max-age=31536000` (+expires 1 anno) a TUTTE le immagini per MIME-type (policy globale, non removibile da PHP); il browser onora quella, non la max-age=600 del plugin — webcam ferma 30+ min verificata. Con il version parameter ogni refresh del cron produce URL nuovi e la cache lunga diventa innocua. La doppia Cache-Control resta ma neutralizzata.

## v3.28 (ottobre 2026)
- URL pubblici senza estensione: `/webcam/{slug}`, `/webcam/{slug}/full`, `/webcam/{slug}/{w}`; retrocompatibilità totale con i vecchi `.jpg`/`-full.webp`/`-{w}.webp`.

## v3.27 (ottobre 2026) — fix del campo Referer e import CSV
- Normalizzazione difensiva in `mwp_normalize_cam` (via prefissi `://` e `//`, schema aggiunto se manca, referer senza host valido → auto) + `autocomplete="off"` sui campi (fix del bug che concatenava il valore precompilato con l'input senza schema).
- Import CSV con casella "Aggiorna le webcam esistenti" (aggiorna nome/URL/referer/TTL/credito; cache azzerata se cambia URL o referer; conteggi espliciti).

## v3.26 (ottobre 2026) — monitoraggio
- Endpoint `/webcam/mwp-status?key=...` con chiave segreta auto-generata (opzione `mwp_status_key`, URL mostrato in admin): JSON con totali fresh/stale/backoff/missing; HTTP 503 se >50% stantie o >10% senza cache. Agganciato come secondo job su cron-job.org.

## v3.25 (ottobre 2026)
- Anteprime admin cliccabili tramite vero link `<a target="_blank">` invece di window.open (popup blocker).

## v3.24 (ottobre 2026) — fix import CSV
- Auto-rilevamento del separatore (,`;` tab) dalla prima riga — Excel italiano salva i CSV con `;`; gestione del BOM UTF-8.

## v3.23 (ottobre 2026) — scala a centinaia di webcam
- Import CSV (colonne `name,url,referer,ttl,credit,slug`, header opzionale, unione NON distruttiva, max 2000 righe / 2 MB) + export CSV con nonce.
- Cron con budget di tempo per run oltre al batch.
- Anteprime admin limitate alle ultime 10 con pulsante "Mostra tutte" (filtro `mwp_preview_limit`).
- Validazione righe fattorizzata in `mwp_normalize_cam`.
- Nota: con centinaia di webcam il salvataggio manuale della tabella può superare `max_input_vars` (il canarino blocca il salvataggio) — usare l'import CSV.

## v3.22 (ottobre 2026) — ottimizzazione Core Web Vitals
- Refresh delle webcam spostato su WP-Cron (`mwp_cron_refresh`, ogni minuto, auto-schedulato da un check in admin, batch max 8 fetch/run con filtro `mwp_cron_batch`, webcam più vecchie prima) con warm-up di full WebP + varianti dopo ogni fetch riuscito.
- In richiesta utente nessun fetch remoto se il file pieno esiste (si serve lo stale; fetch solo in cold-start).
- `width`/`height` sull'`<img>` dello shortcode.
- Placeholder frontend via `onerror="mwpErr(this)"` negli asset lightbox (dimensioni preservate = no CLS).
- Script di refresh che cache-busta anche `srcset`.
- Header `stale-while-revalidate=300` (filtro `mwp_stale_while_revalidate`).

## v3.21 (ottobre 2026)
- Rimossi i log diagnostici temporanei (mwp-save-debug.log e mwp-fetch-debug.log); la diagnostica resta disponibile via pulsante Test.

## v3.20 (ottobre 2026)
- Aggiunto pulsante "Test" per webcam nella pagina admin (fetch immediato con esito: dimensioni, byte, ms o errore dettagliato).

## v3.19 (ottobre 2026)
- Diagnostica per fase: confermato che la connessione TCP verso alett.altervista.org non si apre MAI né su 443 né su 80 dall'IP dell'hosting di meteo-webcam.it (dns ok ~1ms, connect 0ms, ip vuoto) → Altervista buca silenziosamente il traffico dagli IP datacenter.
- Soluzione confermata funzionante (webcam "moena" ora serve regolarmente): per le webcam su Altervista usare come URL remoto il proxy immagini pubblico images.weserv.nl.

## v3.18 (ottobre 2026)
- Timeout 10s/25s e IPv4 — senza esito.

## v3.17 (ottobre 2026)
- Header browser-like nel fetch (Sec-Fetch-Dest/Mode/Site, Accept-Language) + HTTP/2 se libcurl lo supporta.

## v3.16 (ottobre 2026) — protezioni permanenti sul salvataggio
- Tetti di lunghezza sui campi (URL 2048, nome/credito 200, referer 2048).
- Verifica del reale esito di update_option con avviso d'errore esplicito.
- Anteprime admin senza fetch remoti (`?mwp_preview=1`, servono solo la cache), anteprime lazy.
- Canarino a fine form che blocca il save se il POST arriva troncato (max_input_vars). Post-fix: salvataggio a ~16 KB / 6 ms.

## v3.13 (ottobre 2026) — fix timeout admin
- Con ~40 webcam il salvataggio della pagina admin andava in timeout e perdeva le modifiche — causa: la griglia anteprime admin lanciava fetch remoti + conversioni WebP per ogni webcam, saturando i worker PHP dell'hosting condiviso.
- Fix: le anteprime admin (`?mwp_preview=1`) servono solo immagini già in cache senza fare fetch; anteprime `loading="lazy"`; canarino a fine form che blocca il save se il POST arriva troncato (max_input_vars).

## Note trasversali
- Bug del salvataggio risolto in v3.13–v3.16: aggiungere webcam oltre la 40ª falliva — il campo credito della webcam "ostuni" conteneva ~32 MB di backslash raddoppiati a ogni salvataggio → POST da 50 MB → opzione serializzata oltre il max_allowed_packet di MySQL → update_option rifiutata in silenzio mentre l'admin mostrava comunque "Salvate N webcam".
- Revisione CWV della v3.21: i problemi erano fetch remoto sincrono in richiesta, varianti rigenerate dal primo visitatore dopo ogni refresh, `<img>` senza dimensioni (CLS), refresh JS inefficace con srcset, placeholder assente nel frontend.






