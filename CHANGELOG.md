## Tema Meteo Webcam — functions.php v1.5 (30 ottobre 2026) — featured images JPEG → WebP

- Snippet una-tantum `mwt_fi_webp`: converte in WebP (qualità 82) le immagini in evidenza dei post/pagine pubblicati — il file pieno (solo se larghezza ≤ 2560px: oltre si rischia la memoria GD sull'hosting condiviso), la taglia `large` (featured del post singolo, ricodificando il file già ridimensionato) e `mwt-card` 640×360 (se manca viene generata dal pieno: rigenero delle thumbnail rinviato).
- Aggiorna `_wp_attached_file`, `_wp_attachment_metadata` e `post_mime_type`; i vecchi .jpg restano su disco come backup (rollback via FTP, pulibili a fine conversione).
- Pagina strumento in wp-admin: `?mwt_fi_webp=1` dry-run, `&go=1` converte 10 allegati per richiesta, `&auto=1` auto-riguarda la pagina fino a fine coda, `&reset=1` riscansiona. Progresso nell'opzione `mwt_fi_webp_todo`; a fine coda flush W3TC automatico.
- SNIPPET TEMPORANEO: rimuovere a conversione completata e verificata (la versione pulita successiva = v1.6).

## v3.39 (30 ottobre 2026) — autenticazione HTTP Basic/Digest per URL con credenziali

- Gli URL con credenziali (`http://utente:password@host/...`, tipico delle webcam Hikvision via ISAPI, es. `/ISAPI/Streaming/channels/102/picture`) fallivano con 401 pur funzionando da browser: curl con le credenziali dentro l'URL tenta SOLO il Basic auth, mentre questi server richiedono il Digest.
- Fix: le credenziali vengono estratte dall'URL (nuova helper `mwp_split_auth`) e passate con `CURLOPT_USERPWD` + `CURLAUTH_ANY`, così libcurl negozia Basic o Digest automaticamente. Applicato sia al fetch singolo (`mwp_fetch` / pulsante Test) sia al fetch parallelo del cron (`mwp_curl_multi_fetch`).

## v3.38 (29 ottobre 2026) — retry HTTP/1.1 forzato dopo errori di framing HTTP/2

errno 92 ("Stream error in the HTTP/2 framing layer") o 16: la libcurl dell'hosting negozia HTTP/2 con certi server (es. su Google Cloud) e lo stream muore a metà. Caso reale: il Test su https://includi.insidesrl.eu/dueporti/dueporti.jpg falliva in 470 ms con errno 92 (server 35.214.219.154, GCP), DNS e connessione OK.

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
- Rimosso il log temporaneo su wp-content/mwp-cron-debug.log (introdotto in v3.33): la diagnosi del cron è chiusa con esito positivo — bug wrapper v3.32 fixato (v3.33), backoff progressivo attivo (v3.34). Verifica del 28/10 ore 16:27-17:03: run fino a 120 fetch riuscite in ~30s, cicli completi sul parco webcam, run vuoti (0 fetch, 0s) quando tutto è fresco; ~1.575 fetch riuscite in ~35 minuti contro le ~1,5-2/minuto pre-fix.
- Nessun altro cambiamento funzionale. Il file mwp-cron-debug.log esistente può essere cancellato dall'hosting.

## v3.34 (28 ottobre 2026) — backoff progressivo dei fetch falliti
- Il log v3.33 ha mostrato che ~95 webcam morte (404/403/500/503/connessioni) restavano per sempre in testa alla coda stale e bruciavano l'intero budget dei run (4 run su 5 con 0 successi, 42-78 fail l'uno).
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
- Paginazione server-side di elenco e anteprime: 10/25/50/100 per pagina (menu a tendina) con frecce avanti/indietro.
- Ordinamento per data di inserimento decrescente: nuovo campo `added` (timestamp Unix) alla creazione (form admin o import CSV); le webcam pre-3.30, senza timestamp, restano in fondo in ordine alfabetico.
- Salvataggio del form riscritto a "merge per pagina": il POST contiene solo le righe visibili (marker `mwp_page_slugs[]`), eliminazione esplicita via `mwp_delete_slugs[]` (cache pulita), webcam delle altre pagine intatte. Con 100/page il POST resta sotto ~1000 variabili: `max_input_vars` non è più un limite nemmeno per la tabella.

## v3.29 (ottobre 2026) — cache busting
- Lo shortcode emette le URL con `?v={mtime del file pieno}`.
- Scoperta: Tophost aggiunge via nginx una `Cache-Control: max-age=31536000` (+expires 1 anno) a TUTTE le immagini per MIME-type (policy globale, non removibile da PHP); il browser onora quella, non la max-age=600 del plugin — webcam ferma 30+ min verificata. Con il version parameter ogni refresh del cron produce URL nuovi e la cache lunga diventa innocua.

## v3.28 (ottobre 2026)
- URL pubblici senza estensione: `/webcam/{slug}`, `/webcam/{slug}/full`, `/webcam/{slug}/{w}`; retrocompatibilità totale con i vecchi `.jpg`/`-full.webp`/`-{w}.webp`.

## v3.27 (ottobre 2026)
- Fix del campo Referer: normalizzazione difensiva in `mwp_normalize_cam` (via prefissi `://` e `//`, schema aggiunto se manca, referer senza host valido → auto); campi con autocomplete="off".
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
- Bug del salvataggio risolto in v3.13–v3.16: aggiungere webcam oltre la 40ª falliva — il campo credito della webcam "ostuni" conteneva ~32 MB di backslash raddoppiati a ogni salvataggio → POST da 50 MB → opzione serializzata oltre il max_allowed_packet di MySQL → update_option rifiutata in silenzio.
- Revisione CWV della v3.21: i problemi erano fetch remoto sincrono in richiesta, varianti rigenerate dal primo visitatore dopo ogni refresh, `<img>` senza dimensioni (CLS), refresh JS inefficace con srcset, placeholder assente nel frontend.
