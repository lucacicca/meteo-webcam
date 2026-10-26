# Note operative e casi limite

## Configurazioni webcam particolari

| Webcam | Caso | Configurazione |
| --- | --- | --- |
| calata-genova (Comune di Genova) | si aggiorna al minuto | TTL 60, default referer — funziona |
| boccarifugio (Bocca della Selva, turismometeo) | hotlink referer-based | referer `https://www.turismometeo.it/` — travestimento funzionante ma fragile, da stabilizzare eventualmente contattando turismometeo |
| moena (stazione Belchertown su alett.altervista.org) | Altervista buca il traffico dagli IP datacenter (TCP mai aperto) | URL remoto = proxy pubblico images.weserv.nl (`https://images.weserv.nl/?url=...`) — soluzione confermata funzionante |
| riale (lagomaggiorexperience.it, incorporata da distrettolaghi.it) | WAF anti-bot AWS che filtra sul fingerprint (403 ai client non-browser, non referer-based) | **Referer vuoto** (il Test passa, 265 ms). Fallback previsto: images.weserv.nl come URL remoto |

## Bug noti / risolti

- ~~Il campo Referer dell'admin concatena il valore precompilato con l'input senza schema~~ — **risolto in v3.27**: normalizzazione difensiva in `mwp_normalize_cam` (via prefissi `://` e `//`, schema aggiunto se manca, referer senza host valido → auto) + `autocomplete="off"` sui campi.

## Da fare

- Migrazione completa hotlink→plugin nei post: censimento con snippet una-tantum `?mwt_hotlink_audit` (cerca `<img>` con src esterno in tutti i post).
- A migrazione completata: mettere in pausa il tag GTM "CHTML - Monitoraggio Errori Webcam" e l'evento GA4 webcam_offline.
- Da valutare: TTL della page cache W3TC sui post per stringere il lag delle webcam.
- Tema: thumbnail `mwt-card` 640×360 da rigenerare (rinviato).

## Note tematiche (tema "Meteo Webcam" v1.0)

- Zero JS nel tema; template unico con wp_head/wp_footer inline.
- CSS che blocca stiramenti immagini legacy (width/height inline, figure.wp-caption con width inline dal shortcode caption).
- OMGF/Inter non più richiesto dal nuovo tema.
- Exclude Words del lazy load W3TC: `mwp` + `featured-img`.
- CWV verificati su /piazza-del-campo-siena: LCP 1420ms, CLS 0, INP 0.

## Cache e infrastruttura

- W3TC Lazy Load: senza Exclude Words `mwp` il lazy loader ripristina URL senza query string e annulla il cache-busting `?ts`.
- W3TC aggiunge `auto,` all'attributo sizes (innocuo).
- nginx di Tophost aggiunge una seconda Cache-Control max-age=31536000 su tutte le immagini per MIME-type (policy globale, non removibile da PHP; il browser onora quella, non la max-age del plugin). **Neutralizzata in v3.29** con cache busting `?v={mtime}`; il routing senza estensione della **v3.28** (`/webcam/{slug}`, `/webcam/{slug}/{w}`, retrocompatibile) tiene gli URL lontani dall'handler statico per estensione.
- Per gli hotlink non ancora convertiti: placeholder "webcam-non-disponibile" nella Media Library, con fallback `onerror` nel tag img.
