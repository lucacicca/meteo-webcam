# meteo-webcam

Codice e documentazione per **meteo-webcam.it** — directory di webcam meteo.

## Componenti

### Plugin "Meteo Webcam Proxy"
Plugin WordPress che integra le webcam bloccando l'hotlinking, facendo da proxy locale.
Versione corrente: **v3.29** (ottobre 2026). Il file va caricato in `wp-content/plugins/`.

### Tema custom "Meteo Webcam"
Tema leggero v1.0 (ottobre 2026, attivo): 3 file (`style.css`, `functions.php`, `index.php`), zero JS nel tema, template unico, featured image eager + fetchpriority, card 16:9 con `add_image_size mwt-card` 640×360.

## Architettura (plugin)

- Config in opzione DB `mwp_webcams`; admin "Webcam" in wp-admin (slug auto, campo Referer, TTL, shortcode copia-incolla, griglia anteprime)
- Routing URL pubblici: `/webcam/{slug}`, `/webcam/{slug}/full`, `/webcam/{slug}/{w}` — retrocompatibile con i vecchi `.jpg` / `-full.webp` / `-{w}.webp`
- Cache in `wp-content/uploads/mwp-cache/` (originale `{slug}-full.jpg` + varianti WebP); tutte le immagini servite sono **WebP**
- Cache busting: URL con `?v={mtime del file pieno}` (necessario perché nginx di Tophost aggiunge Cache-Control max-age=31536000 a tutte le immagini, non rimovibile da PHP)
- Shortcode `[mwp_webcam slug="..." refresh="300"]`; lightbox al click; placeholder inline "Webcam non raggiungibile"
- Backoff 60s sui fetch falliti; fallback stale fino a 24h
- Refresh su WP-Cron (`mwp_cron_refresh`, ogni minuto): batch max 8 fetch/run (filtro `mwp_cron_batch`), webcam più vecchie prima, budget di tempo per run (`mwp_cron_time_budget`), warm-up full WebP + varianti dopo ogni fetch riuscito
- In richiesta utente nessun fetch remoto se il file pieno esiste (si serve lo stale)
- Import/export CSV: colonne `name,url,referer,ttl,credit,slug`, auto-rilevamento separatore (`,` `;` tab), gestione BOM UTF-8, unione non distruttiva (max 2000 righe / 2 MB), casella "Aggiorna le webcam esistenti"
- Endpoint di monitoraggio `/webcam/mwp-status?key=...` (chiave in opzione `mwp_status_key`): JSON totali fresh/stale/backoff/missing, HTTP 503 se >50% stantie o >10% senza cache
- Pulsante "Test" per webcam in admin (fetch immediato con esito dettagliato)
- Fetch con header browser-like (Sec-Fetch-*, Accept-Language) + HTTP/2

## Stato attuale (ottobre 2026)

- 88 webcam caricate; TTL di default 600s (le webcam a 60s restano intatte)
- Filtri attivi nel functions.php del tema: `mwp_cron_batch` = 24, `mwp_cron_time_budget` = 25s

## Hosting e cron

- Hosting condiviso Tophost; `DISABLE_WP_CRON` attivo in wp-config.php
- Cron esterno: cron-job.org "Meteo Webcam Cron" → `https://www.meteo-webcam.it/wp-cron.php?doing_wp_cron` ogni 1 minuto
- Secondo job cron-job.org sull'endpoint `/mwp-status`; status page pubblica cron-job.org (monitor "Meteo Webcam", disponibilità ~99,9%)
- WP Crontrol installato per ispezionare i cron

## Cache W3 Total Cache

- Page cache: "Never cache the following pages" contiene `/webcam/`
- Lazy Load attivo con **Exclude Words: `mwp` + `featured-img`** — senza l'esclusione il lazy loader di W3TC annulla il cache-busting `?ts`
- Minify JS disattivato (bundle W3TC causavano "jQuery is not defined")

## Plugin attivi sul sito

W3 Total Cache, Yoast, Jetpack, Really Simple SSL, Google Site Kit, Bing Webmaster Tools (cron indexnow ogni minuto). Disinstallati a fine 2026: UpdraftPlus, WP Smush, Broken Link Checker (eventi cron orfani rimossi via WP Crontrol).

## Struttura repo

- `CHANGELOG.md` — cronologia plugin v3.13 → v3.29
- `docs/notes.md` — casi limite webcam, bug noti, attività da fare, note infrastruttura
- `plugin/` — sorgente del plugin Meteo Webcam Proxy
- `theme/` — sorgente del tema custom Meteo Webcam
