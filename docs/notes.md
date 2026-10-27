# Note operative e casi limite

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
- Tema: thumbnail `mwt-card` 640×360 da rigenerare (rinviato).

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
