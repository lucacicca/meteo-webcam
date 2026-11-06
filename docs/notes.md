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
- nginx di Tophost aggiunge una seconda Cache-Control max-age=31536000 su tutte le immagini per MIME-type (policy globale, non removibile da PHP; il browser onora quella, non la max-age=600 del plugin). **Neutralizzata in v3.29** con cache busting `?v={mtime}`; il routing senza estensione della **v3.28** (`/webcam/{slug}`, `/webcam/{slug}/{w}`, retrocompatibile) tiene gli URL lontani dall'handler statico per estensione.
- Per gli hotlink non ancora convertiti: placeholder "webcam-non-disponibile" nella Media Library, con fallback `onerror` nel tag img.

## 1 novembre 2026 — Tema v1.7: embed video responsive
- Su mobile gli iframe YouTube dei post (editor classico, width="560" fissa) sforavano la colonna del contenuto (es. /abetone-campo-scuola-e-piazzale/). responsive-embeds copre solo i blocchi Gutenberg.
- style.css: iframe/object/embed max-width 100%; iframe YouTube full-width aspect-ratio 16:9. MWT_VERSION 1.7 per il cache-bust. Commits adf5de1, 6d9fdbe, b8e3e77. Da caricare via FTP (style.css + functions.php) e flush page cache W3TC.

## 1 novembre 2026 (2) — llms.txt + plugin v3.40 (nota webcam visibile agli agenti AI)
- llms.txt (inglese, commit ab26ed2): descrizione del sito per gli agenti AI; da caricare in ROOT del sito via FTP, accanto a robots.txt. URL segnalazioni corretto in /segnalazioni/.
- Plugin v3.40 (4b5f110): lo shortcode emette la nota "Webcam in tempo reale: l'immagine si aggiorna automaticamente." sotto ogni immagine (testo statico nel DOM, leggibile da GPTBot/ClaudeBot/PerplexityBot). Filtro mwp_refresh_note per personalizzarla/disattivarla (stringa vuota). Nessun impatto CLS (testo dopo l'img già dimensionata).
- Da fare: caricare via FTP plugin v3.40 in wp-content/plugins/ + llms.txt in root, flush page cache W3TC, verificare una pagina (es. Abetone) da mobile e nel sorgente HTML.

## 2 novembre 2026 — Tema v1.8: VideoObject JSON-LD per gli embed YouTube (indicizzazione video GSC)
- Diagnosi dal report Video di Google Search Console: le pagine con video YouTube embed NON risultavano indicizzate come pagine video (es. /san-pellegrino-in-alpe/), mentre quelle con file .mp4 direttamente nell'HTML sì (es. /tre-cime-di-lavaredo-dalla-malga-langalm/). L'ispezione GSC della pagina San Pellegrino mostra che Google vede il video (content URL = iframe embed YuvgiDTzxhU, thumbnail fornita da YouTube) ma lo classifica "supplementary content": senza dati strutturati VideoObject la pagina watch di YouTube resta l'unica ospite canonica del video e la pagina del sito non entra nel registro video.
- Fix in functions.php v1.8: `mwt_video_schema` su `wp_head` (prio 20) — sui post singoli pubblicati estrae gli ID YouTube dal contenuto (youtube.com/embed|live|v, youtube-nocookie.com, youtu.be; oEmbed WP e iframe manuali) ed emette un VideoObject per ogni video unico: name (titolo post), description (excerpt 200 char), thumbnailUrl (featured image `large`, fallback i.ytimg.com maxresdefault), uploadDate (data del post), embedUrl + contentUrl. Filtro `mwt_video_schema` per disattivare.
- ESITO: caricata in produzione da Luca il 2/11 e verificata (VideoObject presente su /san-pellegrino-in-alpe/ e /porto-di-pisa-marina-di-pisa/); avviata in GSC la validazione del motivo "Video isn't on a watch page". Attesa ~2-3 settimane; segnali di successo: report Video, conteggio pagine in errore in "SEE DETAILS", rich results test. Se restano pagine non indicizzate → prossima mossa video sitemap.

## 2 novembre 2026 (2) — Tema v1.9: VideoObject anche per i player Panomax (file video diretti)
- Luca segnala che anche le pagine con player HTML5 Panomax non risultano video indicizzati (es. /cortina-dampezzo-cinque-torri/): embed `<video><source src="https://video.panomax.com/cams/6678/videos/6678_fullHD.mp4">` — mp4 direttamente nell'HTML come nel caso tre-cime (che invece è indicizzato).
- Verifica 2/11: l'mp4 di video.panomax.com BLOCCA i client automatizzati (open_url: "site blocked automated access") → probabile protezione anti-hotlink; possibile che anche Googlebot non riesca a scaricare il file (causa prima della non-indicizzazione, indipendente dal markup).
- Fix v1.9 in functions.php: nuova `mwt_video_file_urls` raccoglie tutte le URL .mp4/.webm/.ogv/.m3u8 del contenuto (dedup) — copre i `<source>` Panomax e gli mp4 come link; `mwt_video_schema` emette VideoObject con contentUrl = file (senza embedUrl), thumbnail = featured image obbligatoria (senza featured il file è saltato).
- Da fare: FTP del functions.php v1.9, flush W3TC, verificare il VideoObject con contentUrl panomax su /cortina-dampezzo-cinque-torri/, reindicizzare in GSC. ATTENZIONE: se Googlebot non riesce a scaricare l'mp4 (blocco anti-bot panomax), il video non verrà indicizzato comunque — in quel caso le opzioni sono contattare Panomax o accettare il limite; lo schema resta la premessa necessaria.

## 2 novembre 2026 — Tema v2.0: header description + tags per la preview

- Chiusura del lavoro sui temi: style.css portato a `Version: 2.0` con Description riscritta per la schermata Aspetto → Temi e riga `Tags:` aggiunta (blog, news, one-column, grid-layout, featured-images, custom-menu, translation-ready). Nessun cambio CSS né funzionale.
- `MWT_VERSION` allineata a 2.0 (cache-bust). Screenshot `screenshot.png` 1200×900 da caricare via FTP nella cartella del tema.
- Comunità WP: il tema NON è pubblicabile su wp.org perché dipende dal plugin privato Meteo Webcam Proxy (sito-specifico); resta d'uso interno + eventuale repo GitHub pubblico a solo scopo dimostrativo.

## 2 novembre 2026 — Pulizia WP: DB, plugin e cartelle

- Disinstallati i plugin TermsFeed AutoTerms, Converter for Media e Disable & Remove Google Fonts (ridondanti: webcam già WebP via MWP, featured convertite a mano in v1.5, tema senza font esterni). Rimosso anche Importatore WordPress; confermata assenza di Jetpack (già rimosso in precedenza).
- Pulizia DB: DROP di 11 tabelle orfane di plugin disinstallati (wp_jetpack_sync_queue; wp_wpforms_ ×4; wp_smush_dir_images; wp_tm_taskmeta/wp_tm_tasks di WP-Optimize; wp_actionscheduler_ ×4 usate da TermsFeed) + opzioni orfane in wp_options (jetpack%, wpforms_%, smush%, actionscheduler%, termsfeed/autoterms, converter_for_media, drgf). Tenute: core, IndexNow ×3, Redirection ×4, Yoast ×7; wp_related_post_stats resta finché Contextual Related Posts è attivo.
- Verifica post-pulizia su /webcam/mwp-status: healthy true, total 632, fresh 614 (97%), stale 13, backoff 6, missing 5 — nessun impatto sul plugin (MWP non usa tabelle MySQL, solo option).
- Da verificare in WP Crontrol (reinstallandolo se rimosso) gli eventi cron orfani updraftplus/smush/blc e di altri plugin rimossi.

## 5 novembre 2026 — Tema v2.1: rimozione stylesheet XSLT dalle sitemap Yoast

- Sintomo (Luca, 5/11): aprendo /sitemap_index.xml Chrome mostra il banner "This site uses XSLT; that functionality is being removed from this browser very soon". È la deprecazione XSLT: Chrome 158 (17/11/2026) rimuove il supporto (feature chromestatus 4709671889534976); anche Firefox e WebKit hanno annunciato l'intenzione di rimuoverlo.
- L'istruzione `<?xml-stylesheet type="text/xsl" href=".../wordpress-seo/css/main-sitemap.xsl"?>` emessa da Yoast serve SOLO alla vista formattata della sitemap per gli umani. I crawler (Google/Bing) leggono l'XML grezzo senza applicare lo stylesheet → NESSUN impatto SEO né sulla scansione/indicizzazione.
- Fix in functions.php v2.1: filtro su `wpseo_stylesheet_url` che svuota l'istruzione finché contiene `text/xsl` (XML grezzo, niente banner). Quando Yoast ≥ 28.9 (in uscita il 17/11) la sostituirà con un foglio di stile CSS (`type="text/css"`), che il filtro passa intatto: la vista leggibile tornerà automaticamente con l'aggiornamento del plugin.
- NOTA deploy: le sitemap XML sono page-cached da W3TC (il footer W3TC compare nel sorgente) → dopo il deploy FTP del solo functions.php, flush della page cache, poi riaprire /sitemap_index.xml e verificare l'assenza dell'istruzione xml-stylesheet nel sorgente.
- WordPress core farà lo stesso in WP 7.2 (8/12/2026) per wp-sitemap.xml; qui non è coinvolto perché le sitemap le genera Yoast.

## 6 novembre 2026 — Plugin v3.41: evento GA4 webcam_offline + immagine di cortesia

- Obiettivo di Luca: accorgersi quando una webcam non è più scaricabile dal plugin (hotlink morto) e non mostrare al visitatore un'immagine stale fuorviante.
- **Notifiche GA4 server-side via Measurement Protocol** (nessun tag GTM, diverso dal vecchio evento client-side chiuso il 30/10): al 3° fallimento consecutivo di fetch (filtro `mwp_ga_offline_threshold`, default 3, ~7 minuti dal guasto) il cron invia l'evento GA4 `webcam_offline` con parametri `webcam_slug`, `webcam_name`, `failure_count`; alla ripresa invia `webcam_online` (se il conteggio era ≥ soglia); le webcam ancora morte mandano un promemoria 1/giorno (fail marker ora conta fino a 1000 invece di 20: oltre count 12 ogni marker = un giorno di morte in più, per il promemoria quotidiano). Hook in `mwp_mark_fail`/`mwp_clear_fail`.
- Configurazione: admin → "Notifiche webcam offline su Google Analytics" — opzioni `mwp_ga_measurement_id` / `mwp_ga_api_secret` / `mwp_ga_client_id` (senza Measurement ID + secret: nessun evento). Pulsante "Invia evento di test" che usa l'endpoint DEBUG `/debug/mp/collect` (il normale `/mp/collect` risponde 204 anche con secret sbagliato, quindi non verificherebbe nulla).
- **Immagine di cortesia**: webcam stantia oltre `mwp_stale_max_age` (filtro, default 86400s = 24h) servita con SVG "Webcam temporaneamente indisponibile" al posto dell'ultima istantanea; anche senza cache (prima: 503). Lo shortcode emette `?v=off{mtime}` in cortesia così il browser non serve dalla sua cache l'immagine vecchia; alla ripresa l'mtime cambia e l'URL torna quella buona. Anteprime admin (`?mwp_preview`) invariate (mostrano la cache reale per diagnosi).
- **Bug v3.40 trovato e recuperato**: la nota "Webcam in tempo reale: l'immagine si aggiorna automaticamente." era dichiarata nella changelog v3.40 (4b5f110) ma mai emessa dal codice (filtro `mwp_refresh_note` inesistente nel corpo). Ora esiste davvero, sotto l'immagine e sopra il figcaption del credito, disattivabile col filtro. Testo placeholder client-side unificato ("Webcam temporaneamente indisponibile").
- **NOTA deploy**: (1) creare l'API secret in GA4: Amministrazione → Flussi dati → flusso web → Measurement Protocol; (2) registrare `webcam_slug` come dimensione personalizzata evento in GA4; (3) FTP del plugin in wp-content/plugins/, poi in admin incollare Measurement ID G-… e API secret e usare il pulsante Test. Al primo giro di cron post-upload tutte le webcam già morte sparano subito un `webcam_offline` → inventario immediato dei guasti in GA4.

## 6 novembre 2026 (2) — Plugin v3.42: webcam_offline via dataLayer/GTM (revisione della logica GA)

- Luca chiede di gestire l'evento con **Google Tag Manager** invece che col Measurement Protocol server-side: la v3.41 (mai caricata in produzione) viene revisionata prima del deploy.
- **Logica v3.42**: la rilevazione resta server-side (fail marker del cron, cap del conteggio tornato a 20 come in v3.40); l'invio è client-side — quando lo shortcode renderizza una webcam con fallimenti consecutivi ≥ soglia (filtro `mwp_ga_offline_threshold`, default 3, ~7 minuti dal guasto), emette `window.dataLayer.push({event:'webcam_offline', webcam_slug, webcam_name, failure_count})`, una volta per pagina per webcam. Nuova funzione `mwp_ga_datalayer_offline($cam)`; la webcam_online e il promemoria giornaliero della v3.41 non esistono più (client-side non c'è modo pulito di segnalare la ripresa: quando la webcam torna, la pagina smette semplicemente di emettere il push).
- **Setup GTM (Luca)**: trigger di evento personalizzato `webcam_offline` → tag evento GA4 con i parametri; `webcam_slug` da registrare come dimensione personalizzata evento in GA4.
- **Rimossi**: sezione admin "Notifiche webcam offline su Google Analytics" (Measurement ID + API secret + pulsante test via endpoint debug), handler `admin_post_mwp_save_ga`/`wp_ajax_mwp_ga_test` + relativo JS, helper `mwp_ga_cfg`/`mwp_ga_client_id`/`mwp_ga_track`/`mwp_ga_maybe_alert_offline`, opzioni `mwp_ga_*` (mai usate in produzione).
- **Caveat page cache (W3TC)**: l'HTML col push viene cachato → l'evento continua a sparare a ogni visualizzazione finché la webcam non torna e la pagina viene rigenerata. Se dovesse dare fastidio nei numeri, rimedi lato GTM (frequency capping del tag) o accorciando la TTL della page cache.
- **NOTA deploy**: FTP del solo plugin in wp-content/plugins/ (niente config admin); verifica: aprire in preview GTM una pagina con una webcam già morta e vedere il `webcam_offline` tra gli eventi, oppure il pannello Data Layer di GTM Preview.

## 6 novembre 2026 (3) — Plugin v3.43: webcam_slug/webcam_name nel dataLayer di base (GTM4WP)

- Luca pubblica la v3.42 (trigger webcam_offline + parametri come event parameters in GTM) e chiede: webcam_slug e webcam_name anche nel dataLayer **di base** di tutte le pagine, compatibile con GTM4WP 2.1.1.
- **Verifica compatibilità**: GTM4WP 2.0 è rewrite OO ma mantiene tutti i nomi di filtro/azione (`gtm4wp_compile_datalayer` incluso); la documentazione conferma che il filtro costruisce il dataLayer in `<head>` prima del container; la 2.1 fixa la coercizione JSON_NUMERIC_CHECK che alterava le stringhe aggiunte con quel filtro → 2.1.1 pienamente compatibile.
- **Implementazione (v3.43)**: `add_filter('gtm4wp_compile_datalayer', ...)` — su pagine singolari cerca il primo shortcode `[mwp_webcam slug="…"]` nel contenuto e aggiunge `webcam_slug` + `webcam_name` (nome dalla config, fallback slug) al dataLayer di base. Più webcam → vince la prima; pagine senza shortcode → chiavi assenti; GTM4WP disattivato → filtro mai eseguito.
- **In GTM (Luca)**: variabili Data Layer `webcam_slug`/`webcam_name`; per portarle sul page_view, aggiungerle come parametri evento nel tag Google (GA4). Dimensione evento `webcam_slug` già registrata; per `webcam_name` nei report serve una seconda dimensione personalizzata evento.
- Caveat page cache: il dataLayer è HTML cachato → nome/slug riflettono la config al momento della generazione della pagina.
- **NOTA deploy**: FTP del solo plugin; verifica in GTM Preview: su un post webcam il primo push (Container Load) mostra webcam_slug/webcam_name; su un post senza webcam le chiavi non ci sono.





## 6 novembre 2026 (4) — Plugin v3.44: dataLayer di base con TUTTE le webcam della pagina

- Luca chiede di tracciare tutte le webcam quando una pagina ne contiene più di una (la v3.43 portava solo la "principale", la prima).
- **Implementazione**: il filtro `gtm4wp_compile_datalayer` ora raccoglie tutti gli shortcode `[mwp_webcam]` del contenuto (slug deduplicati in ordine) ed emette quattro chiavi: `webcam_slug`/`webcam_name` (la prima webcam, retrocompatibili) + `webcam_slugs`/`webcam_names` (lista completa separata da virgole, stringhe perché GA4 non accetta array; emesse anche con una sola webcam → schema costante).
- **In GTM (Luca)**: variabili Data Layer `webcam_slugs`/`webcam_names`; per i report GA4 registrare `webcam_slugs` come dimensione personalizzata evento (limite ~100 caratteri: liste lunghissime vengono troncate). Per l'analisi per-singola-webcam restano `webcam_slug` e l'evento `webcam_offline` (un push per ogni webcam offline, invariato).
- **NOTA deploy**: FTP del solo plugin; verifica in GTM Preview su un post con più webcam: Container Load mostra `webcam_slugs` con la lista completa.
- **COLLAUDO 6/11 COMPLETATO** (Luca): v3.44 in produzione e funzionante; webcam pitigliano offline → immagine di cortesia + evento `webcam_offline` verificati in GTM/GA4; chiavi `webcam_slugs`/`webcam_names` visibili nel Container Load. Pitigliano era già nel foglio di pulizia come "da rivedere" (403 WAF/referer): da sistemare col pulsante Test/referer o da eliminare.


## 6 novembre 2026 (5) — README aggiornato allo stato di novembre

- Luca segnala che la sezione "Stato attuale (ottobre 2026)" del README contava ancora 88 webcam: era ferma a prima dell'import da hotlink. README portato allo stato reale: **632 webcam in config** (88 → picco 713 post-import → 632 dopo pulizia del 30/10 e dedup), filtri cron attivi (batch 120 / budget 55s / parallel 6), mwp-status del 2/11 (healthy true, fresh 614/632 = 97%, stale 13, backoff 6, missing 5).
- Aggiornate anche le altre sezioni obsolete: versione plugin v3.29 → v3.44, tema v1.0 → v2.1 (VideoObject JSON-LD, fix XSLT sitemap), architettura (backoff progressivo v3.34, cortesia SVG v3.42, integrazione GA4/GTM v3.42-v3.44, curl_multi, retry HTTP/1.1, auth Basic/Digest), lista plugin attivi del 2/11 (niente Jetpack/Bing Webmaster Tools; aggiunti GTM4WP, CF7, Limit Login, Redirection, Contextual Related Posts), struttura repo con llms.txt.
- Nessun cambiamento al codice: solo documentazione, nessun bump di versione.

## 6 novembre 2026 (6) — Audit link interni contestuali (solo analisi, nessun codice)

- Obiettivo di Luca: aumentare i link interni del sito con **anchor contestuali nel testo** dei post (es. la pagina di Misurina cita "le Tofane" → link alla pagina webcam delle Tofane). ESCLUSA la sezione "correlate" a fondo post (già coperta da Contextual Related Posts).
- Metodo (analisi offline sui dump phpMyAdmin wp_posts.csv / tr.csv / wp_options.csv del 6/11): 537 contenuti (532 post + 5 pagine), 371 post con shortcode webcam, 642 webcam in config. Pipeline awk: estrazione entità dai titoli, dai nomi webcam della config e dai tag di luogo (frequenza ≤ 8), normalizzate (minuscole, accenti rimossi, varianti piena e strippata dei generici tipo monte/lago/passo); matching sul testo normalizzato con confini di parola, saltando le coppie già linkate e le menzioni già dentro `<a href>`.
- Regole di cura del report: anchor multi-parola con score ≥ 8; anchor a parola singola ammessa solo se presente nel titolo/nome webcam del target, con score ≥ 7 e coerenza geografica (categoria o tag condiviso); blacklist di sostantivi/aggettivi comuni che generavano falsi positivi (grazie, neve, cavallo, forno, falco, versante est, porto turistico, yacht club, ...); target "redirect" esclusi; massimo 10 suggerimenti per pagina.
- **Risultato: 796 suggerimenti di link su 372 pagine** (score medio 10,3; 251 con score ≥ 12). Report tabellare consegnato a Luca in canvas Vibe ("Link interni contestuali: 796 suggerimenti"): pagina A, anchor, pagina B target, URL B, occorrenze, score, note e frase di contesto (testo normalizzato senza accenti, per trovarla con la ricerca dell'editor).
- Note del report: "ambigua (N target)" = l'entità mappa su N pagine e il target canonico è l'id più basso (verificare a mano se è la pagina giusta, es. "costa crociere" → Trapani invece che Genova-relitto); "autoref." = la pagina A cita il toponimo di cui è essa stessa target → il link va alla pagina "fratello" dello stesso luogo (es. Moena → Lago di Soraga), da valutare; "target senza testo" = pagine quasi senza contenuto (es. Roma #1016) da verificare prima di linkarle.
- Prossimi passi: Luca esamina il report e sceglie i link da inserire; poi si deciderà se applicarli con uno snippet una-tantum (update wpdb) o a mano nell'editor. Nessun bump di versione: nessuna modifica a plugin/tema in questo lavoro.
