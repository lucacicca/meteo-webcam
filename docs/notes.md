# Note operative e casi limite


## 30 ottobre 2026 — tema v1.5: conversione featured images JPEG → WebP

- Obiettivo: alleggerire il caricamento delle pagine convertendo le immagini in evidenza da JPEG a WebP. Le immagini webcam del plugin sono già tutte WebP; questa conversione riguarda solo la Media Library (featured images).
- Il tema le serve in due taglie: `large` nel post singolo (index.php) e `mwt-card` 640×360 nelle card di home/archivi.
- Snippet una-tantum in functions.php v1.5 (repo GitHub, commit su main): pagina admin `?mwt_fi_webp=1` (dry-run) / `&go=1` (10 allegati per richiesta) / `&auto=1` (auto-refresh fino a fine coda) / `&reset=1`.
- Full convertito solo se larghezza ≤ 2560px (limite memoria GD su Tophost condiviso): gli originali più grandi restano JPEG come candidato alto del srcset.
- I vecchi .jpg NON vengono cancellati (backup su disco); a conversione verificata si può pulire uploads/ dai jpg residui e rimuovere lo snippet (versione pulita v1.6).
- Verifica post-conversione: una pagina post singolo + la home con DevTools (Content-Type image/webp su featured e card), W3TC flush, LCP in calo su PageSpeed.

## 28 ottobre 2026 (pomeriggio) — diagnosi cron CHIUSA: v3.33 + v3.34 + v3.35

- **v3.33 (85ee7fd)**: bugfix critico — il cron parallelo della v3.32 passava il wrapper ['age','cam'] invece della webcam → zero fetch dal cron (le poche osservate arrivavano dai cold-start dei visitatori). Aggiunto log temporaneo su wp-content/mwp-cron-debug.log.
- **v3.34 (c9d5026)**: backoff progressivo dei fallimenti (60s→2m→4m→…→max 24h, raddoppio per fallimento consecutivo, marker -fail.marker col conteggio) — le ~95 webcam morte non affamano più la coda.
- **v3.35 (d433bb9)**: rimosso il log di diagnosi. Nessun altro cambiamento funzionale.
- Verifica finale (log ore 16:27-17:03): run fino a 120 fetch riuscite in ~29-53s, run vuoti (0 fetch, 0s) quando tutto è fresco, ~1.575 fetch riuscite in ~35 minuti contro le ~1,5-2/minuto pre-fix.
- Da fare: caricare v3.35 via FTP in wp-content/plugins/ e cancellare mwp-cron-debug.log dall'hosting; poi dedup 31 webcam, pulizia ~95 webcam morte (liste nei log del 28/10), migrazione residua hotlink.

## Configurazioni webcam particolari

| Webcam | Caso | Configurazione |
| --- | --- | --- |
| calata-genova (Comune di Genova) | si aggiorna al minuto | TTL 60, default referer — funziona |
| boccarifugio (Bocca della Selva, turismometeo) | hotlink referer-based | referer `https://www.turismometeo.it/` — travestimento funzionante ma fragile, da stabilizzare eventualmente contattando turismometeo |
| moena (stazione Belchertown su alett.altervista.org) | Altervista buca il traffico dagli IP datacenter (TCP mai aperto) | URL remoto = proxy pubblico images.weserv.nl (`https://images.weserv.nl/?url=...`) — soluzione confermata funzionante |
| riale (lagomaggiorexperience.it, incorporata da distrettolaghi.it) | WAF anti-bot AWS che filtra sul fingerprint (403 ai client non-browser, non referer-based) | Referer vuoto funzionava, ma da fine ottobre 2026 i fetch ricominciano a fallire (età del file in cache crescente + backoff nel mwp-status). Applicare il fallback: URL remoto = `https://images.weserv.nl/?url=https%3A%2F%2Flagomaggiorexperience.it%2Fwebcam%2Fpublic%2Friale.jpg` |

## Bug noti / risolti

- ~~Il campo Referer dell'admin concatena il valore precompilato con l'input senza schema~~ — **risolto in v3.27**: normalizzazione difensiva in `mwp_normalize_cam` (via prefissi `://` e `//`, schema aggiunto se manca, referer senza host valido → auto) + `autocomplete="off"` sui campi.

## Censimento e migrazione hotlink→plugin (ottobre 2026)

- Censimento completato con lo snippet una-tantum `?mwt_hotlink_audit` (v2, righe `pagina=`): 452 pagine con hotlink, 634 hotlink totali tutti risolti (proxyplugin, hotlink diretti tollerati o rimossi).
- CSV di import pronto: 628 webcam (colonne `name,url,referer,ttl,credit,slug`, TTL 600). 18 webcam su Altervista già wrappate su images.weserv.nl; 2 hotlink da web.archive.org esclusi (pisa-tuttomondo, expo-milano-2015).
- Mappa di migrazione pagina→slug pronta per la conversione post per post.

## Da fare

- Webcam "riale": applicare il fallback images.weserv.nl (vedi tabella sopra).
- 4 webcam "missing" nel mwp-status: identificarle col pulsante Test e sistemarle.
- Ordine operativo: sistemare riale + 4 missing → deploy v3.31 → import CSV (con "Aggiorna le webcam esistenti") → migrazione post per post con la mappa.
- A migrazione completata: mettere in pausa il tag GTM "CHTML - Monitoraggio Errori Webcam" e l'evento GA4 webcam_offline; rimuovere lo snippet audit una-tantum da functions.php.
- Da valutare: TTL della page cache W3TC sui post per stringere il lag delle webcam.
- Tema: thumbnail `mwt-card` 640×360 da rigenerare (rinviato — la fa lo snippet v1.5 per le featured).

## Note tematiche (tema "Meteo Webcam" v1.1)

- Zero JS nel tema; template unico con wp_head/wp_footer inline.
- functions.php porta i filtri cron del plugin: `mwp_cron_batch=60`, `mwp_cron_time_budget=50` (deployati e collaudiati: mwp-status healthy, fresh 62–83/88 con budget 50s/minuto).
- CSS che blocca stiramenti immagini legacy (width/height inline, figure.wp-caption con width inline dal shortcode caption).
- OMGF/Inter non più richiesto dal nuovo tema.
- Exclude Words del lazy load W3TC: `mwp` + `featured-img`.
- CWV verificati su /piazza-del-campo-siena: LCP 1420ms, CLS 0, INP 0.

## Cache e infrastruttura

- W3TC Lazy Load: senza Exclude Words `mwp` il lazy loader ripristina URL senza query string e annulla il cache-busting `?ts`.
- W3TC aggiunge `auto,` all'attributo sizes (innocuo).
- nginx di Tophost aggiunge una seconda Cache-Control max-age=31536000 su tutte le immagini per MIME-type (policy globale, non removibile da PHP; il browser onora quella, non la max-age del plugin). **Neutralizzata in v3.29** con cache busting `?v={mtime}`; il routing senza estensione della **v3.28** (`/webcam/{slug}`, `/webcam/{slug}/{w}`, retrocompatibile) tiene gli URL lontani dall'handler statico per estensione.
- Per gli hotlink non ancora convertiti: placeholder "webcam-non-disponibile" nella Media Library, con fallback `onerror` nel tag img.

## 1 novembre 2026 — Tema v1.7: embed video responsive
- Su mobile gli iframe YouTube dei post (editor classico, width="560" fissa) sforavano la colonna del contenuto (es. /abetone-campo-scuola-e-piazzale/). responsive-embeds copre solo i blocchi Gutenberg.
- style.css: iframe/object/embed max-width 100%; iframe YouTube full-width aspect-ratio 16:9. MWT_VERSION 1.7 per il cache-bust. Commits adf5de1, 6d9fdbe, b8e3e77. Da caricare via FTP (style.css + functions.php) e flush page cache W3TC.

## 1 novembre 2026 (2) — llms.txt + plugin v3.40 (nota webcam visibile agli agenti AI)
- llms.txt (inglese, commit ab26ed2): descrizione del sito per gli agenti AI; da caricare in ROOT del sito via FTP, accanto a robots.txt. URL segnalazioni corretto in /segnalazioni/.
- Plugin v3.40 (4b5f110): lo shortcode emette la nota "Webcam in tempo reale: l'immagine si aggiorna automaticamente." sotto ogni immagine (testo statico nel DOM, leggibile da GPTBot/ClaudeBot/PerplexityBot). Filtro mwp_refresh_note per personalizzarla/disattivarla (stringa vuota). Nessun impatto CLS (testo dopo l'img già dimensionata).
- Da fare: caricare via FTP plugin v3.40 in wp-content/plugins/ + llms.txt in root, flush page cache W3TC, verificare una pagina (es. Abetone) da mobile e nel sorgente HTML.
