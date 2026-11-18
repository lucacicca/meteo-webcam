# meteo-webcam

Codice e documentazione per **meteo-webcam.it** — directory di webcam meteo.

## Componenti

### Plugin "Meteo Webcam Proxy"
Plugin WordPress che integra le webcam bloccando l'hotlinking, facendo da proxy locale.
Versione corrente: **v3.52** (18 novembre 2026). Il file va caricato in `wp-content/plugins/`.

### Tema custom "Meteo Webcam"
Tema leggero **v2.8** (novembre 2026, attivo): 3 file (`style.css`, `functions.php`, `index.php`), zero JS nel tema, template unico, featured image eager + fetchpriority, card 16:9 con `add_image_size mwt-card` 640×360, VideoObject JSON-LD per embed YouTube e player Panomax (v1.8/v1.9), fix XSLT sitemap Yoast (v2.1).

## Architettura (plugin)

- Config in opzione DB `mwp_webcams`; admin "Webcam" in wp-admin a scala 600+ (paginazione server-side, ordinamento per data, ricerca per nome/slug/credit, campo Referer, TTL, shortcode copia-incolla, griglia anteprime `?mwp_preview`)
- Routing URL pubblici: `/webcam/{slug}`, `/webcam/{slug}/full`, `/webcam/{slug}/{w}` — retrocompatibile con i vecchi `.jpg` / `-full.webp` / `-{w}.webp`
- Cache in `wp-content/uploads/mwp-cache/` (originale `{slug}-full.jpg` + varianti WebP); tutte le immagini servite sono **WebP**
- Cache busting: URL con `?v={mtime del file pieno}` (necessario perché nginx di Tophost aggiunge Cache-Control max-age=31536000 a tutte le immagini, non rimovibile da PHP)
- Shortcode `[mwp_webcam slug="..." refresh="300"]`; lightbox al click; nota "Webcam in tempo reale" nel DOM (v3.40, leggibile dai crawler)
- Backoff progressivo dei fetch falliti 60s→2m→4m→…max 24h (v3.34), azzerato al primo successo; immagine di cortesia SVG quando lo stale supera `mwp_stale_max_age` (default 24h, v3.42)
- Refresh su WP-Cron (`mwp_cron_refresh`, ogni minuto): fetch paralleli curl_multi (`mwp_cron_parallel`, default 6), batch `mwp_cron_batch` + budget di tempo `mwp_cron_time_budget`, webcam più vecchie prima; dalla v3.31 il cron scrive solo l'originale e le varianti WebP si rigenerano alla prima richiesta (dalla corsia veloce MWP Fast Images 1.1, senza caricare WordPress), retry HTTP/1.1 forzato sui framing error HTTP/2 (v3.38), autenticazione Basic/Digest per URL con credenziali (v3.39)
- In richiesta utente nessun fetch remoto se il file pieno esiste (si serve lo stale)
- Corsia veloce (mu-plugin MWP Fast Images, 18/11/2026): le varianti WebP vengono servite (e se serve rigenerate, v1.1) da `wp-content/mu-plugins/` prima del caricamento di plugin e tema (header `X-MWP-Fast: 1`); i casi particolari passano al plugin
- Integrazione GA4/GTM: evento `webcam_offline` via dataLayer.push quando una webcam supera la soglia di fallimenti (`mwp_ga_offline_threshold`, default 3, v3.42); dataLayer di base con `webcam_slug`/`webcam_name` (prima webcam) + `webcam_slugs`/`webcam_names` (tutte le webcam della pagina) via filtro `gtm4wp_compile_datalayer` (v3.43/v3.44)
- Import/export CSV: colonne `name,url,referer,ttl,credit,slug`, auto-rilevamento separatore (`,` `;` tab), gestione BOM UTF-8, unione non distruttiva con aggiornamento delle esistenti (max 2000 righe / 2 MB)
- Endpoint di monitoraggio `/webcam/mwp-status?key=...` (chiave in opzione `mwp_status_key`): JSON totali fresh/stale/backoff/missing, HTTP 503 se >50% stantie o >10% senza cache
- Pulsante "Test" per webcam in admin (fetch immediato con esito dettagliato)
- Fetch con header browser-like (Sec-Fetch-*, Accept-Language) + HTTP/2; per le sorgenti che filtrano i client datacenter (es. Altervista): proxy `images.weserv.nl`

## Stato attuale (novembre 2026)

- **632 webcam in config** (ottobre 2026: 88 → picco 713 dopo l'import da hotlink → 632 dopo la pulizia delle webcam morte del 30/10 e la dedup; pulizia chiusa il 2/11)
- TTL di default 600s (le webcam a 60s restano intatte)
- Filtri attivi: `mwp_cron_batch` = 120, `mwp_cron_time_budget` = 55s (tema v1.4+), `mwp_cron_parallel` = 6 (default plugin)
- mwp-status del 2/11: **healthy true**, fresh 614/632 (97%), stale 13, backoff 6, missing 5

## Hosting e cron

- Hosting condiviso Tophost; `DISABLE_WP_CRON` attivo in wp-config.php
- Cron esterno: cron-job.org "Meteo Webcam Cron" → `https://www.meteo-webcam.it/wp-cron.php?doing_wp_cron` ogni 1 minuto
- Secondo job cron-job.org sull'endpoint `/mwp-status`; status page pubblica cron-job.org (monitor "Meteo Webcam", disponibilità ~99,9%)

## Cache W3 Total Cache

- Page cache: "Never cache the following pages" contiene `/webcam/`
- Lazy Load attivo con **Exclude Words: `mwp` + `featured-img`** — senza l'esclusione il lazy loader di W3TC annulla il cache-busting
- Minify JS disattivo (bundle W3TC causavano "jQuery is not defined")

## Plugin attivi sul sito (lista reale del 2/11/2026)

W3 Total Cache, Yoast, Really Simple Security (ex Really Simple SSL), Google Site Kit, IndexNow (cron ogni minuto), Limit Login Attempts, Akismet, Contact Form 7 (segnalazioni webcam), GTM4WP, Redirection, Contextual Related Posts. Disinstallati a fine ottobre/novembre 2026: UpdraftPlus, WP Smush, Broken Link Checker, Jetpack, TermsFeed AutoTerms, Converter for Media, Disable & Remove Google Fonts, Importatore WordPress.

## Struttura repo

- `CHANGELOG.md` — cronologia plugin v3.13 → v3.44 e tema v1.0 → v2.5
- `docs/notes.md` — casi limite webcam, bug noti, diagnosi cron, note infrastruttura
- `plugin/` — sorgente del plugin Meteo Webcam Proxy
- `plugin/mu-plugins/mwp-fast-images.php` — corsia veloce per le immagini (va in `wp-content/mu-plugins/`)
- `theme/` — sorgente del tema custom Meteo Webcam
- `llms.txt` — istruzioni per agenti AI (da caricare nella root del sito accanto a robots.txt)

