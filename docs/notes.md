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

## 6 novembre 2026 (7) — Test link interni: tema v2.2 con snippet una-tantum mwt_link_test

- Luca sceglie di testare l'inserimento sulle prime 10 righe del report (score 21-19). Le 10 occorrenze verificate offline nel contenuto reale del dump (post #1546, #2026, #2235, #1662, #1875, #2210, #625, #7846, #8338, #9443): tutte in testo libero subito dopo lo shortcode, prima menzione del toponimo nel paragrafo di apertura.
- Tema v2.2 (snippet una-tantum `mwt_link_test`): `?mwt_link_test=1` dry-run / `&go=1` applica; prima occorrenza libera (case-insensitive, confini di parola, skip dentro <a>/tag/shortcode), anchor con maiuscole originali, update $wpdb senza revisioni, `w3tc_flush_post` automatico in go, idempotente. Elenco righe hardcoded in `mwt_link_test_items()`.
- Deploy (Luca): FTP del solo functions.php in wp-content/themes/meteo-webcam/; aprire in wp-admin `?mwt_link_test=1` (dry-run: controllare i 10 contesti) poi `?mwt_link_test=1&go=1`; verificare una pagina dal frontend (es. /gran-sasso-rifugio-le-fontari-a-campo-imperatore/ col link su "Campo Imperatore" → /lago-di-campotosto/).
- Se il test è OK: prossimo passo = rollout delle righe rimanenti del report (stesso snippet con elenco esteso o per lotti), poi rimozione snippet nella versione pulita v2.3.

## 6 novembre 2026 (8) — Test link interni: tema v2.2.1, bug del dry-run su #1546 (commenti HTML)

- Luca esegue il dry-run del test delle 10 righe (v2.2 caricata in produzione) e nota un contesto anomalo su #1546. Riverifica sul dump: 9/10 righe OK (prima menzione in prosa visibile subito dopo lo shortcode); su #1546 lo snippet puntava a "Campo Imperatore" dentro un blocco legacy commentato `<!-- [caption]…` prima dello shortcode [mwp_webcam] → il link sarebbe stato invisibile sul frontend. Nota cosmetica: i "contesti" multi-riga del dry-run sono normali, sono gli a-capo reali del contenuto dei post.
- Fix v2.2.1 (commit su main): `mwt_link_test_find` salta le occorrenze dentro commenti HTML, controllando `<!--` vs `-->` sul prefisso intero del contenuto (non sul tail di 500 char: i blocchi legacy commentati possono essere lunghi). Dopo il fix #1546 punta a "L'Hotel Campo Imperatore" (primo paragrafo di prosa); le altre 9 righe non cambiano.
- Deploy (Luca): FTP del solo functions.php v2.2.1 in wp-content/themes/meteo-webcam/; nuovo dry-run `?mwt_link_test=1` (verificare #1546 con contesto "L'Hotel Campo Imperatore" e le altre 9 invariate), poi `?mwt_link_test=1&go=1` e verifica frontend.
- Per il futuro rollout delle ~786 righe: i blocchi legacy commentati prima dello shortcode sono un pattern sistematico del sito → il check dei commenti HTML deve restare nello snippet definitivo; valutare anche un check sulle coppie [caption]…[/caption] non dentro commenti (caption visibili in pagina ma linkare lì non è l'obiettivo).

## 6 novembre 2026 (9) — Test link interni: tema v2.2.2, controllo link concorrenti Yoast

- Luca esegue il go delle 10 righe e Yoast segnala sulla pagina /gran-sasso-rifugio-le-fontari-a-campo-imperatore/ l'avviso "Link concorrenti": l'anchor "Campo Imperatore" applicato (→ /lago-di-campotosto/) contiene la frase chiave del post → il link dice a Google che l'altra pagina compete per la stessa frase chiave. Righe tipiche a rischio: le autoreferenziali del report (anchor = toponimo della pagina stessa).
- Fix v2.2.2 (commit su main): (a) `mwt_link_test` legge `_yoast_wpseo_focuskw` di ogni post e salta le righe in cui la frase chiave compare integralmente nell'anchor (normalizzata: minuscole, punteggiatura→spazio, confini di parola), con messaggio esplicito in dry-run/go; (b) nuova modalità `&undo=1` che rimuove i link applicati dallo snippet (solo la coppia esatta href+anchor) ripristinando il testo originale.
- Sequenza deploy (Luca): FTP del solo functions.php v2.2.2 → dry-run `?mwt_link_test=1` (vedere quali righe ora saltano) → `&undo=1` (pulizia dei 10 applicati) → `&go=1` (riapplica solo le righe valide) → controllo Yoast su Gran Sasso.
- Per il rollout delle ~786 righe: anche il check frase chiave Yoast va tenuto nello snippet definitivo (oltre a commenti HTML e, da valutare, coppie [caption] visibili); le righe autoreferenziali del report sono le candidate naturali allo skip.

## 6 novembre 2026 (10) — Test link interni: tema v2.2.3, anchor alternativi per le righe concorrenti

- Go v2.2.2 (Luca): 2/10 applicati (#7846 "lago di Garda" → Sirmione, #8338 "Valle Aurina" → Cadipietra), 8 SKIP concorrenti — l'anchor coincide con la frase chiave Yoast del post in 8 casi su 10: le righe a score più alto del report sono proprio quelle col toponimo nel titolo della pagina, che è anche la sua frase chiave. Luca sceglie la strada degli anchor alternativi.
- Scan alias offline (wp_posts.csv): per le 8 righe skippate si cercano menzioni dei toponimi dei target nel testo degli stessi post. Alternative pulite in prosa per 4 righe: #2026 e #2235 → "isola del Giglio", #1662 → "Cridola", #1875 → "Monte Amiata". Nessuna alternativa per #1546 (Campotosto assente), #2210 (Capo Coda Cavallo assente), #625 (Observation Hill assente), #9443 (Canevara/Grondilice assenti): restano SKIP concorrente.
- v2.2.3 (commit su main): `mwt_link_test_items` aggiornato con i 4 anchor alternativi (commenti inline con l'anchor originale); nessun nuovo controllo: i check runtime esistenti valgono anche per i nuovi anchor.
- Deploy (Luca): FTP del solo functions.php v2.2.3 → dry-run (4 righe col nuovo contesto, 4 SKIP concorrente, 2 "già presente") → `&go=1` (4 link) → verifica Yoast su #2026/#2235 (nessun link concorrente) e frontend dei 4 link.
- Prossimo passo per il rollout ~786 righe: Luca esporta wp_postmeta filtrato per meta_key='_yoast_wpseo_focuskw' (CSV phpMyAdmin, come gli altri dump) → riprocessare il report per etichettare le righe concorrenti con le frasi chiave reali e cercare anchor alternativi massivamente con lo stesso metodo dello scan alias.

## 6 novembre 2026 (11) — Link interni: riprocessamento del report con le frasi chiave Yoast (pm.csv)

- Luca esporta da phpMyAdmin wp_postmeta filtrato per meta_key='_yoast_wpseo_focuskw' (pm.csv: 506 post con frase chiave). Riprocessamento offline delle 796 righe del report: **45 CONC** (l'anchor contiene la frase chiave Yoast della pagina A → avviso "link concorrenti") e **751 OK** (5,6% di righe concorrenti). Zero auto-link A→A (verificato per slug su tutte le 796 righe; la sospetta riga "Firenze - Vista su Ponte Vecchio" #2508 è in realtà un link verso un'altra pagina distinta con lo stesso titolo, il target #833 è "target senza testo": da valutare).
- Scan di anchor alternativi sulle 45 CONC (menzioni dei toponimi dei target nel testo degli stessi post, stesso metodo dello scan alias del test): **13 alternative pulite in prosa**. Le 4 già nel tema v2.2.3 (#2026/#2235 "isola del Giglio", #1662 "Cridola", #1875 "Monte Amiata") più 9 nuove: #842 "Regina Margherita" (→ Capanna Regina Margherita - Vista sul Lyskamm), #175 "soraga" (→ Lago di Soraga - Moena), #3414 "catinaccio" (→ Catinaccio da Nova Levante), #1474 "Carestiato" (→ Monte Agner), #2513 "firenze" (→ Firenze panoramica sulla città), #3079 "Isola d'Elba" (→ Lacona), #3469 "Civetta" (→ Cima Fertazza), #1343 "Antartide" (→ Scott Base), #2508 "Firenze" (→ Ponte Vecchio #833, target quasi senza testo).
- **32 righe CONC senza alternativa valida**: restano skip a runtime (il check frase chiave Yoast dello snippet v2.2.2+ le salta automaticamente). Un falso positivo scartato a mano: il candidato "2000" (da "Rifugio Capanna 2000") agganciava un numero puro nel testo di #7500 ("dai 1150 ai 2000 metri") → regola: mai candidati puramente numerici.
- Report (canvas Vibe) aggiornato con la colonna "Stato (controllo kw Yoast)" per ognuna delle 796 righe: OK / CONC con anchor alternativo (le 4 del tema v2.2.3 marcate) / CONC senza alternativa: skip / "applicato nel test" sulle 2 righe già sul sito (#7846 Bardolino→Sirmione, #8338 Valle Aurina→Cadipietra).
- Nessuna modifica a tema/plugin in questo lavoro: nessun bump di versione. Prossimi passi: deploy della v2.2.3 (i 4 anchor alternativi del test), poi rollout delle 751 righe OK a lotti (snippet con elenco righe per lotto, tutti i check attivi: commenti HTML + frase chiave Yoast + idempotenza + flush W3TC), infine rimozione dello snippet nella versione pulita v2.3.

## 6 novembre 2026 (12) — Rollout link interni: tema v2.2.4, snippet mwt_link_rollout a lotti

- Luca conferma la v2.2.3 caricata in produzione e dà via libera al rollout. Scelta esplicita di Luca: includere TUTTE le 764 righe applicabili (751 OK + 13 anchor alternativi), anche le 421 "ambigue" (target canonico scelto dall'audit) e le 90 "target senza testo": il dry-run per lotto fa da revisione e l'undo per lotto consente il ripensamento.
- Elenco completo generato offline dal report incrociato con pm.csv: mapping pagina A → post ID verificato su tutte le 796 righe (titoli univoci, zero auto-link); verifica anchor-vs-contenuto sul dump: 763/764 frasi presenti verbatim; unica correzione #842 "rifugio città di Mantova" (accento ripristinato dal testo reale). I target senza frase chiave Yoast (90) non sono un problema: il check concorrente riguarda solo la frase chiave della pagina A.
- v2.2.4: nuovo snippet `mwt_link_rollout` con i 764 item embedded (nowdoc "ID\tfrase\tslug", ordine di score del report), lotti da 50 (16 totali). Parametri: `?mwt_link_rollout=1` riepilogo con stato per lotto; `&batch=N` dry-run; `&batch=N&go=1` applica; `&batch=N&undo=1` undo del lotto; `&undo=1` undo totale. Registro in opzione `mwt_link_rollout_applied` ("lotto|ID|frase|slug") per idempotenza e undo; i 6 link del test v2.2.2/v2.2.3 (lotto 1) vengono rilevati come "già presente" e saltati.
- Deploy (Luca): FTP del solo functions.php v2.2.4 → `?mwt_link_rollout=1` (riepilogo) → `&batch=1` dry-run → `&batch=1&go=1` → procedere per lotti 2-16 (dry-run + go ciascuno) → verifica Yoast e frontend su un campione → a lavoro finito rimozione snippet nella versione pulita v2.3 (tenere l'opzione mwt_link_rollout_applied finché serve l'undo).
- NOTA workflow: in questa sessione è emerso che l'esecuzione PARALLELA di bash e run_typescript azzera i file scratch della cartella di lavoro (il primo run_typescript del turno ha cancellato la pipeline ricostruita). Regola: mai chiamate parallele bash+run_typescript nel progetto meteo-webcam.

## 6 novembre 2026 (13) — Rollout eseguito in produzione: 750/764 registrati, 756 link totali

- Luca ha caricato la v2.2.4 e completato tutti i 16 lotti (dry-run + go ciascuno). Riepilogo: **750/764 nel registro**; il lotto 1 risulta 44/50 SOLO perché i 6 link del test v2.2.2/v2.2.3 sono già sul sito e vengono rilevati "già presente" senza entrare nel registro (per progetto). **Link effettivi inseriti col report: 756** su ~370 pagine, tutti con flush W3TC per post.
- **8 skip runtime**: lotto 4 (1), lotto 7 (2), lotto 11 (3), lotto 12 (2). Diagnosi offline: coincidono ESATTAMENTE (per lotto: 1+2+3+2) con gli 8 item che hanno anchor sovrapposto a un link già applicato sulla stessa pagina in un lotto precedente e occorrenza unica (Occ=1 nel report): #883 "porto di Genova" (l'unica occorrenza contiene "Genova", già linkato → porto antico nel lotto 3), #1343 "Antartide" (occorrenza già linkata da "Antartide" → Scott Base nel lotto 4), #10192/#12375/#1466/#1468 "cortina" (unica occorrenza dentro "Cortina d'Ampezzo", già linkato nel lotto 5), #11516 "maddalena" (dentro "Santa Maddalena", già linkato nel lotto 5), #2902 "ossola" (pagina dove "domodossola" è stato applicato nello stesso lotto 7). Cause protettive, NON errori: la frase non ha una seconda occorrenza libera dopo l'inserimento del link correlato.
- Conferma disponibile con i dry-run dei lotti 4/7/11/12: le righe non registrate mostrano il motivo (atteso "NESSUNA occorrenza libera"). Nessuna azione correttiva prevista: sono 8 link "in più" che non trovano spazio, le pagine hanno già il link topico.
- Resta da fare: verifica campione su frontend + Yoast (nessun avviso "link concorrenti"), poi rimozione dello snippet nella versione pulita v2.3 (l'opzione mwt_link_rollout_applied va tenuta finché serve l'undo, poi eliminabile a mano).

## 6 novembre 2026 (14) — Tema v2.3: versione pulita, rimosso lo snippet mwt_link_rollout

- Luca dà via libera alla versione pulita dopo il rollout completato (756 link, vedi sezione (13)). v2.3 = functions.php v2.2.4 senza l'intera sezione snippet `mwt_link_rollout` (764 item nowdoc, helper `mwt_link_find`/`mwt_link_norm_phrase`/`mwt_link_competing` e pagina admin `mwt_link_rollout_run`): il file passa da 1406 a 345 righe, zero riferimenti residui allo snippet (i 4 "mwt_link" rimasti sono nei commenti di versione e nella nota di deploy 8).
- Nessuna modifica funzionale: diff v2.2.4 → v2.3 verificato, uniche differenze = define MWT_VERSION 2.3, nota di deploy 8 aggiunta (deploy + percorso di undo), sezione snippet rimossa; hook add_action/add_filter invariati (14=14), parentesi graffe bilanciate (44/44).
- Il registro dei 750 link applicati resta nell'opzione `mwt_link_rollout_applied`; l'undo via URL non esiste più in v2.3. Per un eventuale undo: ricaricare temporaneamente la v2.2.4 e usare `?mwt_link_rollout=1&undo=1` (globale o `&batch=N&undo=1` per lotto), poi ri-caricare la v2.3. A lavoro chiuso l'opzione si elimina da wp_options.
- Deploy (Luca): FTP del solo functions.php v2.3 in wp-content/themes/meteo-webcam/ + flush page cache W3TC. Verifica rapida: una pagina campione dal frontend con i link al loro posto e `?ver=2.3` sul CSS nel sorgente.


## 6 novembre 2026 (15) — Tema v2.4: frase chiave Yoast automatica per i post senza (snippet mwt_focuskw)

- Luca chiede di impostare automaticamente la frase chiave Yoast su tutti i post in cui manca. Dal censimento del 6/11 ~506 post su 532 ce l'hanno già (pm.csv, `_yoast_wpseo_focuskw`): lo snippet copre i rimanenti (~26).
- Regola (scelta di Luca su proposta Vibe): frase chiave = titolo del post in minuscolo (minuscolo, spazi normalizzati), come le frasi chiave già in uso ("monte amiata", "isola del giglio", "soraga"); SOLO post pubblicati: niente pagine, niente bozze. Ipotesi alternativa scartata: "webcam" + titolo (target più esplicito ma densità bassissima nei testi Wikipedia-style, e la convenzione esistente del sito è il toponimo puro).
- v2.4: snippet una-tantum `mwt_focuskw_fill` in functions.php — `?mwt_focuskw=1` dry-run (tabella ID/titolo/frase chiave proposta), `&go=1` applica con update_post_meta su `_yoast_wpseo_focuskw`. Idempotente (skip dei post già forniti), riservato a manage_options, hook template_redirect + admin_init. Zero impatto frontend: solo postmeta, nessuna revisione, nessun flush di cache richiesto.
- Nota attesa: i post quasi senza testo (i "target senza testo" del report link, es. Roma #1016) mostreranno check Yoast rossi di densità dopo l'assegnazione; titoli molto lunghi (es. "Portovenere, Isola Palmaria e Isola del Tino") daranno keyphrase lunga. È la conseguenza naturale della regola scelta, da accettare o ritoccare a mano sui singoli casi.
- Deploy (Luca): FTP del solo functions.php v2.4 → aprire da amministratore `?mwt_focuskw=1` (dry-run, revisione lista) → `?mwt_focuskw=1&go=1` → campione nell'editor Yoast. Poi rimozione dello snippet nella versione pulita v2.5.
- Contesto collegato: stessa sessione = decisione di lasciare il suggerimento Yoast "Frase chiave nel sottotitolo" (arancione, impatto minimo) e di disattivare il toggle Yoast Site features → AI tools → LLMS.txt (il file llms.txt del sito è quello artigianale del 1/11, Yoast non deve generarlo).
