Diario delle sessioni. **Le voci dal 12/11/2026 in poi stanno qui in cima, dalla più recente alla più vecchia**; le voci fino al 9/11 sono più in basso, sotto "Note operative e casi limite", in ordine cronologico. Le nuove voci vanno aggiunte in cima.

## 27/11/2026 — Core update di dicembre, titoli e meta description Yoast (nessun codice)

Core update di dicembre 2026: preannunciato da Google il 24/11 (prima volta in anticipo, per le regole CMA sul ranking equo), partirà a dicembre senza data. Riguarda query lunghe/complesse e risultati personalizzati, nessun requisito tecnico nuovo. Controllo del repo: tema 2.9.3, plugin 3.53 e mu-plugin 1.2 non toccano mai post_modified / dateModified; l'unica data nei dati strutturati è uploadDate del VideoObject (= data del post). Nessuna modifica a tema e plugin. robots.txt invariato: il token `__usage_ai-training` sarà supportato da Google nel 2027 e non conta per il ranking.
- Post con ID vecchio e data 6/11 o 12/11 (villar-perosa, madonna-di-campiglio-vista-sul-paese, novellara, marine-village-litorale-romano): erano bozze pubblicate a novembre, data corretta.
- Da valutare da Luca (pannelli): "Nome pubblico" dell'autore (ora il nome utente WordPress in JSON-LD e meta author) e Organization in Yoast.
- Il repo risulta pubblico (clonabile senza credenziali): segnalato a Luca. Nessun segreto nei file.

Search Console, 16 mesi (155K clic, 9,09M impressioni), query che contengono: "webcam" 111K clic; "meteo" 3,68K (CTR 0,6%, pos. 12, di cui 980 il brand "meteowebcam"); "live" 1,76K (gonfiato da livigno/livata); "diretta" 1,55K (CTR 2,3%, pos. 6,8); "neve" 763 su 299K impressioni (CTR 0,3%); "tempo reale" 543; anno 0.

Yoast (modifiche di Luca, verificate da visitatore con cache rigenerata):
- Titolo SEO articoli: da `Webcam %%title%%: Meteo Live e Tempo Reale 2026` (289/482 oltre 60 caratteri) a **`Webcam %%title%% in diretta`**. I 40 titoli ancora lunghi sono stati riscritti a mano nel singolo post; Monte Capanne riportato allo schema. Esito: 482 post, nessun titolo oltre 60 caratteri (massimo 60, Groenlandia).
- Meta description articoli: da `🔴 Guarda la Webcam di %%title%% in diretta. Situazione meteo, neve e immagini aggiornate in tempo reale per %%title%%. Controlla ora.` (mediana 168, max 236, "neve" anche al mare) a **`🔴 Webcam di %%title%% in diretta, aggiornata ogni pochi minuti. Guarda adesso che tempo fa`** (max 141). Tutti i 482 post la usano (svuotate le description personalizzate di abbadia-lariana e san-vincenzo). `%%primary_category%%` scartato: i post hanno più categorie.
- Titoli dei post corretti: santa-cristina "sullla" → "sulla" (slug invariato), porto-santo-stefano "tragetti" → "traghetti", arbatax senza "meteo", "Sella Nevea", doppi spazi tolti (rifugio-zamboni-zappa, calitri, la-verna, lago-dorta-vista-da-omegna, groenlandia-summit-station, monte-zoncolan).
- Idea aperta: description personalizzate con "neve" per le località sciistiche.
- Da controllare: CTR in GSC sui titoli nuovi tra 3-4 settimane; dopo il rollout dell'update, confronto con dicembre 2025 (stagionalità neve).

## 26/11/2026 (sera) — Pulizia degli slug inutilizzati, Paola chiusa (nessun codice)

- Config MWP da 684 a **635 webcam**. Controllo finale: zero slug inutilizzati, nessuno usato solo in bozze. Metodo: config letta da wp-admin + contenuto grezzo di post e pagine via REST `context=edit` (482 post pubblicati, 53 bozze, 4 pagine); "usato" = shortcode `[mwp_webcam slug=...]` o URL `/webcam/{slug}` fuori dai commenti HTML.
- Eliminazioni su richiesta di Luca, con lo stesso invio del pulsante "×" + "Salva webcam" (marker `mwp_delete_slugs[]`, il plugin pulisce anche la cache), verificando dopo ogni giro che gli slug usati in pagina restassero con URL invariato e rispondessero da visitatore (X-MWP-Fast 1.2):
  - 35 doppioni (stesso URL di uno slug usato), es. auronzo-di-cadore-dal-monte-agudo → agudo, capanna-regina-margherita/-2 → margherita2/margherita1, rifugio-citta-di-fiume/-2/-3 → fiume2/fiume3/fiume1, salivoli-piombino-… → salivoli1/salivoli2, sorrento-vista-sul-golfo-e-sul-vesuvio → sorrento;
  - 10 non usati senza doppione (tra cui antartide-scott-base-arrival-heights, pozza-di-fassa-vista-sul-sassolungo, sorrento-vista-sul-vesuvio; passo-falzarego-rifugio-col-gallina eliminato da Luca, resta `gallina`);
  - 11 non usati con hotlink ancora in pagina (civago-2, livigno-2/-3, molveno-2, monte-amiata-2, passo-del-muraglione-colla-dei-pratiglioni, san-giacomo-2, urbino-2, paola-2/3/4): sistemati da Luca (shortcode o eliminazione).
- **Paola chiusa**: le 3 webcam meteoincalabria (paola-2/3/4) servivano la schermata "Cam: No signal / Error 8000274c", che il plugin considera fresca; su meteoincalabria.com non esistono più webcam di Paola; anche la Skyline di Paola era offline. Luca ha eliminato le 4 webcam e importato il redirect 301 `/paola/` → `/category/regione/calabria/` (verificato da visitatore: 200 sulla categoria).
- **Amantea** (slug `amantea`, Altervista via weserv): data stampata 11/11/2026 18:22, ferma da ~15 giorni ma servita come fresca. Da verificare.
- **Limite noto del plugin**: una webcam che restituisce un'immagine valida ma ferma, o una schermata d'errore, non va in backoff né in "stale" (casi Sestriere Savoy, Paola, Amantea). Si scopre solo dalla data stampata nell'immagine.

## 26/11/2026 — Search Console, report Pagine (dati del 23/11)

- Totali: 605 indicizzate, 821 non indicizzate. "Discovered - not indexed" da 560 (18/11) a 34, effetto tag noindex + post reindirizzati tolti dalla sitemap.
- Crawled - currently not indexed: 626 (convalida "Failed", non rilanciare). ~70% immagini `/webcam/{slug}/full`, poi tag, feed, paginazioni, wp-emoji.js con ?ver: normale. Post veri (7): abbadia-lariana, islanda-isola-di-vestmannaeyjar, villar-perosa, alassio-marina, fanano, novellara, chamois-colle-fontana-fredda. Canonical/robots ok, ~600-800 parole: scelta di Google.
- Fix di Luca: Redirection → Options → Default query matching = "Ignore all query parameters" (prima Exact): le URL reindirizzate con parametri (`?utm_source=rss…`, `?_gl=`) davano 404. Verificato da visitatore anche sulle regole esistenti.
- Fix di Luca: link Wikipedia senza `https://` in /punta-marina-terme-di-ravenna/ (generava un 404 interno). Verificato da visitatore.
- Soft 404 (1): `/webcam/tirrenia-bagno-maddalena/full` (segnaposto SVG con 200; webcam già "da sistemare").
- 404 legittimi, nessuna azione: belgirate1/2 e torbole1 `/full`, `/*` e `/wp-content/*` (precaricamento WordPress), /wpautoterms/terms-and-conditions/, polyfills.js di twentytwentyone, paginazioni oltre la fine. Previsti: noindex 52, canonical alternativo 24, duplicato senza canonical 1, bloccata da robots 1, redirect 59.

## 26/11/2026 — Prato Nevoso ferma al 19/11: plugin v3.53 (segnaposto __ts__)

Segnalazione di Luca. Controllo da visitatore alle 12:13: /webcam/prato-nevoso/full 200, X-MWP-Fast 1.2, Last-Modified di 3 minuti prima, ma l'immagine (3840×2160) porta la stampa "giovedì 19 novembre 2026, 07:04". La sorgente `https://www.vigliettisport.com/webcam.jpg` è servita con max-age 31 giorni; la pagina di Viglietti la carica con `?time=<ms>` e così è del 26/11 11:09. Config in admin corretta (URL nudo, referer pagina Viglietti, TTL 600). Il pulsante Test non basta qui: dice solo byte e dimensioni, non l'età della foto.
Fix: plugin v3.53, `__ts__` nell'URL diventa il timestamp a ogni download (opt-in). Deploy di Luca alle 12:20 (plugin v3.53 + URL `https://www.vigliettisport.com/webcam.jpg?time=__ts__`). Collaudo da visitatore 12:21: full/480/768 tutte 200, X-MWP-Fast 1.2, full 3840×2160 con stampa "giovedì 26 novembre 2026, 11:19:19" (la camera stampa un'ora indietro rispetto all'ora italiana, come sul sito di Viglietti): allineata alla sorgente.
Regola generale: per le webcam "ferme" guardare data e ora stampate nell'immagine; il nostro Last-Modified dice solo che il file è stato riscritto.

## 23/11/2026 (2) — Pagine senza shortcode [mwp_webcam], redirect e recupero di due post (nessun codice)

- Controllo in sola lettura: contenuti da `/wp-json/wp/v2/posts` e `/pages` come visitatore (500 post + 4 pagine pubblicati), config dall'export CSV dell'admin (667 webcam). Una pagina conta come "con webcam" se ha `<figure class="mwp-webcam">`. Pagine senza: 76 (72 post + 4 pagine di servizio).
- Sistemate da Luca: gli hotlink con slug pronto (Malghera, Punta Marina, Santa Croce, Catinaccio, Casentinesi, Avio, Piaggia, Canevara), Gambarie e Camigliatello (Windy 1646056997, Telemaconet/Innovia). /sorrento-vista-sul-golfo-e-sul-vesuvio/ ora ha la webcam.
- Decisione di Luca: una webcam sostitutiva vale solo se riprende lo stesso soggetto dallo stesso posto (per /lago-di-livigno/ non vanno bene altre webcam di Livigno, per /vigo-di-fassa-vista-sul-sassolungo/ non va bene Pozza di Fassa). Le pagine di webcam chiuse senza sostituto vanno in redirect.
- Giro su Windy (mappa webcam centrata sulla località + "Webcam più vicine"), inserite da Luca e collaudate: Cattedrale di Palermo (slug cattedrale-di-palermo, Windy 1663280572), Stretto di Messina su /reggio-calabria-vista-sullo-stretto/ (slug strettomessina, Windy 1560713889, TTL 300; la camera è di meteomessina.it), Vigo di Fassa (slug vigo, Windy 1521018065). Le immagini Windy arrivano con ~10 minuti di ritardo sull'ora stampata. La Maddalena in redirect: l'unica alternativa era dello stesso fornitore di /palau-vista-sulla-maddalena/.
- Monte Penna: proposta webcam Meteo Bibbiena `https://www.meteobibbiena.it/api/webcam/badia-tagliata/image` (2560×1440, inquadratura del Penna non confermata).
- Alpe di Mera (#9197): le webcam di alpedimera.it danno 404, la Pro Loco è ferma al 21/03/2026: stagionali, ricontrollare a dicembre.
- Redirect: CSV di 26 regole 301 importato da Luca (target relativi; es. Monte Tomba → /cima-grappa-vista-sul-sacrario-militare/, Vallombrosa → /reggello-in-provincia-di-firenze/, Trapani e Favignana ×2 → /spiaggia-di-san-vito-lo-capo/, Monte della Neve e Lago di Livigno → /livigno-vista-sul-paese/, relitto Concordia → /porto-di-genova-terminal-traghetti/). Collaudo da visitatore 26/26 → 200. Luca ha eliminato definitivamente i 26 post reindirizzati: pubblicati da 500 a 474. Senza destinazione affine e ancora pubblicati: Madagascar (#2661), Monte Rushmore (#2128), Trani (#1202), decisione in sospeso.
- **Incidente**: eliminando i post sono stati cancellati per errore anche i post 939 (livigno-vista-sul-paese) e 7638 (porto-di-genova-terminal-traghetti), destinazioni dei redirect. Recuperati dal backup del database del 17/11: script SQL mirato (wp_posts con ID originali, postmeta utili senza cache dei plugin, term_relationships, 2 commenti con commentmeta, yoast_primary_term, post_parent dell'allegato 7707, ricalcolo count dei termini, `SET NAMES utf8mb4`), provato su una copia locale del backup e importato da Luca in phpMyAdmin. Collaudo: pagine 200, accenti corretti, webcam ok, commenti, immagine in evidenza, canonical e sitemap ok. Le modifiche tra il 17/11 e il 23/11 su questi due post sono perse. **Lezione: prima di eliminare post reindirizzati, controllare che nessuno sia destinazione di un redirect.**
- Pagine con solo player esterni (29, da lasciare): Panomax, YouTube, Twitch, feratel, mediaportal Regione Lombardia, mp4; 3 Skyline (#8072, #7500, #2200, le gestisce Luca); 2 web.archive.org defunte (#1122, #2764); i 2 mp4 pwad.it già segnalati morti il 20/11.
- 70 webcam in config non usate da nessuna pagina pubblicata: ripulite il 26/11 (vedi voce del 26/11 sera).

## 23/11/2026 (1) — INP su mobile sopra 200 ms (solo diagnosi, nessun codice)

- CrUX origine, telefono, finestra 25/10–21/11: LCP 1842 ms, **INP 202 ms** (needs improvement, in salita da 5-6 settimane), CLS 0,06. Search Console (dati del 22/11): tutte le 450 URL mobile "INP issue: longer than 200ms", due gruppi: post (430 URL, es. /riale-in-val-formazza/) 203 ms e home (20 URL) 202 ms. Desktop 450/450 buone. Le barre gialle di fine ottobre nello stesso grafico erano il problema CLS, ora "Passed".
- Il valore è uguale in tutto il sito, home compresa, dove non c'è il lightbox delle webcam: la causa è comune a tutte le pagine, non il lightbox.
- Esclusi: il JS del tema (solo il toggle della ricerca); il tag GTM "CHTML - Monitoraggio Errori Webcam" (non c'è più, Luca); il messaggio AdSense per gli ad blocker (pubblicato dal 13/11/2023).
- Visto da visitatore su /piazza-del-campo-siena/: 10 spazi AdSense, annuncio ancorato, vignetta (`#google_vignette`, compare al tocco sui link), inserti "ad intents" ("Libri Geologia"), script Funding Choices. Al caricamento il tempo di thread principale più alto era di fundingchoicesmessages (51 ms sul PC).
- Lightbox (plugin 3.52): gestore del clic 1-18 ms. Misure dal Chrome di Luca con l'estensione: di solito 30-90 ms, con punte di 160-1680 ms anche sulla sola chiusura, che toglie un elemento dal DOM. Una variante provata solo nella pagina (immagine già caricata subito, versione grande dopo decode()) non ha migliorato. Le punte sono quasi certamente rumore dello strumento (clic e screenshot dell'estensione): misure non utilizzabili. Decisione di Luca: niente v3.53 senza dati.
- Proposta scartata da Luca: niente tag GTM con web-vitals attribution per misurare l'INP dei visitatori ("codice inutile"). Non riproporlo.
- Incidente: durante le prove da loggato è comparsa una vignetta e sono partiti 4-5 clic su annunci (schede adclick.g.doubleclick.net, chiuse). Da allora Luca ha attivato in Site Kit "Exclude from Ads: All logged-in users": i collaudi da admin non mostrano più annunci. Per misurare gli annunci serve una sessione da visitatore senza cliccare.
- Decisione di Luca (23/11): aspettare il prossimo aggiornamento settimanale di CrUX. Se l'INP p75 mobile torna sotto 200 ms non si fa niente; altrimenti Luca riduce il carico degli annunci automatici in AdSense (una modifica alla volta, effetto visibile in CrUX dopo ~4 settimane).
- Impostazioni annunci automatici al 23/11: in-page Banner + Multiplex, massimo 11 annunci per pagina, distanza minima 350 px; overlay ancorati + laterali + vignetta; formati "intent-driven" (beta) attivi.
- Esperimento AdSense avviato da Luca il 23/11: variante con massimo **6** annunci in pagina invece di 11, tutto il resto invariato. Durata consigliata almeno 2-3 settimane (traffico molto variabile col meteo). L'esperimento misura la revenue, non l'INP: CrUX è per origine e mescola le due metà, quindi l'effetto sull'INP si vede solo dopo aver applicato la variante a tutti. Candidati successivi, uno alla volta: vignetta spenta, formati intent-driven spenti.

## 22 novembre 2026 — Sestriere (bandablu) e censimento Paesaggi Digitali

- Sestriere: la webcam Hotel Savoy (`hotelsavoysestriere.com/camera/savoy.jpg`, slug sestriere-vista-sul-paese) è ferma alla fonte (data stampata 12/10/2026; su Windy, id 1352919961, ferma da 41 giorni). Il plugin la serve come fresca perché il cron riscarica sempre lo stesso file vecchio: il Last-Modified del nostro file non dice nulla sull'età della foto.
- Luca ha aggiunto `sestriere2`, Tana della Volpe: `https://webcam.bandablu.it/memfs/ca1729bd-6536-401c-9ac5-4e783862b7de.jpg` (2560×1440). URL fisso, immagine aggiornata ogni minuto circa (Last-Modified, byte e ora stampata cambiano), niente Cache-Control né ETag. Collaudo da visitatore: 200, X-MWP-Fast 1.2. Credito: Unione Montana Comuni Olimpici Vialattea (pubblicata su meteopinerolese.it); in pagina il credito mancava, segnalato. Inquadratura del paese non verificata (nebbia). Nella pagina c'è ancora anche la Savoy.
- Stessa rete bandablu: Alpette `…/memfs/d01e6532-36d2-45b3-bede-1e876eccbc28.jpg` (piste), Fraiteve `…/memfs/09949c18-928d-4e20-96c6-5818ec2ba85c.jpg`. Metodo: iframe filesusr.com di meteopinerolese.it → `webcam.bandablu.it/{uuid}.html` → `/memfs/{uuid}.jpg`. Panomax Tappeti Jolly `live-image.panomax.com/cams/5861/recent_reduced.jpg` è 800×450 e inquadra solo le piste. Su Windy nessun'altra webcam attiva su Sestriere.
- Paesaggi Digitali (paesaggidigitali.it): 52 post WordPress (REST `/wp-json/wp/v2/posts?per_page=100`). Le pagine mostrano stream HLS (`live.paesaggidigitali.it/live/{nome}/index.m3u8`), ma l'og:image di ogni pagina è il JPG fisso aggiornato, in `/webcam/{nome}.jpg` o `/webcam1/{nome}.jpg`. Anche le varianti `-small` sono 1920×1080. Le directory danno 403 e i nomi inesistenti rispondono con HTML: i nomi vanno presi dall'og:image.
- Confronto con la config (export CSV del 22/11, 666 webcam): 37 webcam con URL paesaggidigitali. 35 corrispondono a pagine attuali; 2 non sono più linkate e sono ferme: manarola-cinque-terre-2 (manarola-regina.jpg, ~12,5 giorni) e portovenere-isola-palmaria-e-isola-del-tino (portovenere-small.jpg, ~2,5 anni). Ferma anche manarola-cinque-terre-3 (manarola-small.jpg, ~1,6 giorni).
- Mancano 17 webcam. Località nuove (5): Castiglione di Garfagnana (webcam1/castiglione-g), Frabosa Ski campo scuola (webcam1/frabosaski-1), Frabosa Ski Monte Malanotte (webcam1/frabosaski-2, ferma da ~5 giorni), Cagliari (webcam1/cagliari), Baia Blu (webcam/baiablu). Località già presenti con altre camere (12): Livigno ×4 (webcam/livigno-sanrocco-2, webcam/livigno-san-rocco, webcam/livigno, webcam1/livigno-planel), Sappada ×2 (webcam1/sappada-centro-1 e -2), Pietra di Bismantova (webcam1/bismantova), Cerreto Laghi lago (webcam1/cerretolaghi-cristallo), Carrara (webcam1/carrara), La Spezia Piazza Saint Bon (webcam/laspezia-saintbon), Ponte di Legno (webcam/pontedilegno), San Terenzo (webcam1/santerenzo).

## 20 novembre 2026 (sera) — SkylineWebcams: nuova regola e censimento

- Regola di Luca (20/11): non inserire più webcam di SkylineWebcams (skylinewebcams.com, embed.skylinewebcams.com), nemmeno come ultima spiaggia. Se per una località c'è solo Skyline, la località resta senza webcam. Quelle già presenti le elimina Luca un po' per volta (non riproporlo come lavoro sospeso).
- Censimento: 27 webcam Skyline in config su 664. rimini-vista-sulla-spiaggia non è usata da nessuna pagina (post cestinato il 18/11), firenze-vista-su-ponte-vecchio-2 solo nella bozza 2508. 3 post pubblicati con hotlink diretto a embed.skylinewebcams.com/img/{ID}.jpg: #8072 stintino-la-pelosetta, #7500 rifugio-capanna-2000-alpe-arera, #2200 alta-val-brembana-vista-del-paese-piazzatorre. Link testuali a skylinewebcams.com anche in #2122, #1136, #1086 e in 4 bozze: vanno tolti insieme alle webcam.
- Alternative trovate per 18 righe su 30 (Tramariglio, Canazei, Deiva Marina, Firenze, Gabicce, Lampedusa, Pescasseroli, Pescocostanzo ×2, Rimini, Rosignano, Sestri Levante, San Vito Lo Capo, Sulzano, Venezia, Stintino, Capanna 2000). Nessuna alternativa per Alberobello, Arbatax, Soave, Lago Arvo, Marina di Bibbona, Monte Santa Maria Tiberina, Ostuni, Paola, Porto Santo Stefano, Portofino, Ravello, Piazzatorre. Molti siti ripubblicano Skyline (giglionews, webcamsardinia, silaonline, spotcameras, worldcam, webcamgalore): non sono alternative.
- Metodo Windy: dalla pagina windy.com, `node.windy.com/webcams/v2.0/list?…&nearby={lat},{lon}&radius={km}` dà le webcam vicine con lastUpdate; il link "sito del fornitore" della pagina webcam spesso è l'immagine originale.

## 20 novembre 2026 — Video Scenari Digitali nelle pagine e tema 2.9.2

- Controllo (solo lettura) delle pagine con webcam Scenari Digitali: config esportata dall'admin (41 righe SD), contenuti da `/wp-json/wp/v2/posts` da visitatore (497 post). 29 pagine pubblicate con webcam SD, 22 avevano già il timelapse `https://api.scenaridigitali.com/media/{slug}/timelapse.mp4`.
- Senza video: /foligno/, /monte-subasio/, /frontignano-di-ussita/ (2 webcam), /auronzo-di-cadore-dal-monte-agudo/. Luca ha inserito i video il 20/11.
- Video solo per una parte delle webcam: /monte-castel-manardo/ (manca bolognolaski-castelmanardo2), /pintura-di-bolognola/ (manca bolognola-pintura).
- /rifugio-barba-ferrero-sul-monte-rosa/ usa ancora il vecchio `tl.scenaridigitali.com/monterosa4/video/timelapse.mp4`, che dal browser non risponde; il nuovo `api.scenaridigitali.com/media/monterosa-rifugio-barba-ferrero/timelapse.mp4` funziona. È l'unica pagina col vecchio dominio.
- Webcam SD in config senza pagina pubblicata: molini-di-sellano-e-cammoro, torino-museo-della-montagna. Le altre righe SD senza pagina sono doppioni di webcam già pubblicate (agudo, margherita, pretare).
- Tema 2.9.2: video del lettore WordPress a tutta larghezza (vedi CHANGELOG). Caricato da Luca e confermato il 20/11: il video ora ha la stessa larghezza della webcam.
- Ricontrollo da visitatore (20/11, 12:20): tutte le 29 pagine con webcam Scenari Digitali hanno il timelapse di ogni webcam presente, tutti i video si caricano. Sistemate da Luca anche /pintura-di-bolognola/ (bolognola-pintura), /monte-castel-manardo/ (bolognolaski-castelmanardo2), /rifugio-barba-ferrero-sul-monte-rosa/ (passata ad api.scenaridigitali.com, nessun tl.scenaridigitali.com rimasto) e /frontignano-di-ussita/ (3 webcam: frontignano-saliere, frontignano-belvedere, frontignano, ciascuna col suo video). Restano senza pagina pubblicata molini-di-sellano-e-cammoro e torino-museo-della-montagna.
- 20/11 pomeriggio: pubblicate da Luca /torino-vista-dal-museo-della-montagna/ (torino-museo-della-montagna) e /cammoro/ (molini-di-sellano-e-cammoro). Collaudo da visitatore: pagine 200 con tema 2.9.2, webcam 200 image/webp con X-MWP-Fast 1.2 e immagine di pochi secondi prima, timelapse presente e caricabile (81 e 85 s). Ora tutte le webcam Scenari Digitali in config hanno una pagina con il loro video.
- Scoperta: le pagine Scenari Digitali non hanno il VideoObject del tema. Il video è inserito come `[embed]https://api.scenaridigitali.com/media/{slug}/timelapse.mp4[/embed]` e la regex di `mwt_video_file_urls` accetta dopo l'estensione solo spazio, virgolette, `<`, `>`, `?` o fine testo, non `[`. Panomax (`<source src="...mp4">`) e YouTube invece lo hanno. Non corretto: va deciso con Luca, visto che il 18/11 ha scelto di non intervenire sui video in Search Console.
- Tema 2.9.3 (richiesta di Luca, 20/11): VideoObject anche per i video in `[embed]URL[/embed]`, tutti gli host (vedi CHANGELOG). Caricato da Luca, cache svuotata. Collaudo da visitatore: `style.css?ver=2.9.3`, JSON-LD valido con VideoObject (contentUrl, thumbnail, uploadDate) su /spoleto/, /cammoro/, /frontignano-di-ussita/ (3), /corno-alle-scale/, /tarvisio-monte-lussari/, /salivoli-piombino-vista-sullisola-delba/; invariato su /cortina-dampezzo-cinque-torri/ (Panomax, 3) e /san-pellegrino-in-alpe/ (YouTube).
- Video esterni negli [embed] verificati nel browser: funzionano Ramsat Napoli (25 s), Livata (120 s), Oltreleali Salivoli (webm 50 s). **Morti**: feratel di /corno-alle-scale/ e /tarvisio-monte-lussari/ (HTTP 400: gli URL feratel `stsstoreNNN/..._hashVid.mp4` sono temporanei) e pwad.it di /torre-specchia-ruggeri-melendugno/ (morelido.mp4) e /maluha-bay-torre-chianca-lecce/ (maluha.mp4), host che non risponde né in http né in https (in più erano http su pagina https). Proposto a Luca di togliere questi 4 [embed]: player vuoto per i visitatori e VideoObject che punta a un file inesistente.
- Camere feratel sul sito (censimento 20/11). Nei post: solo Tarvisio (6270), dove Luca ha sostituito l'mp4 morto con l'iframe del player `https://webtv.feratel.com/webtv/?design=v5&pg=…&cam=6270`, e Corno alle Scale (6135), da cui Luca ha tolto l'[embed]. In config, solo immagini (tutte fresche, WebP da corsia veloce): cadipietra-klausberg-ski-arena-2 (wtvpano 6250), colle-bettaforca (6283), piz-boe-sul-gruppo-sella (6321), san-candido-monte-baranci-2 (wtvpict 6198).
- Player feratel: gli mp4 `stsstoreNNN/…_hashVid.mp4` sono temporanei e non vanno incorporati; si incorpora l'iframe webtv. Il `pg` preso da bergfex (Tarvisio) apre anche 6283, 6321 e 6198, quindi è un codice partner di bergfex, non di PromoTurismoFVG. Con quel pg non si aprono 6135 (Corno alle Scale, su bergfex ora non più feratel) e 6250 (Klausberg). Feratel chiama la 6198 "Sexten - Rotwand Bergstation" (Croda Rossa di Sesto), mentre la pagina del sito è "San Candido vista sul rifugio Baranci": da verificare.
- Corno alle Scale (6135): la camera feratel è viva, ma solo col `pg` del comprensorio, preso da https://cornoallescale.org/webcam-corno/ (`pg=DA7D2F22-8600-464D-9D4F-CDB04014A6C5`, loro usano design=v4, lg=de). Con design=v5 e lg=it: timelapse 1920×1080 di 53 s, "Corno alle Scale 1497 m". Il player carica `https://stsfc001.feratel.com/streams/latest/1/{cam}.mp4`, URL stabile ma non incorporabile da altri siti (errore per 6135, 6270, 6283, 6321, 6198, 6250): resta l'iframe. Col pg di Corno alle Scale la 6250 (Klausberg) non si apre.
- Player feratel inseriti da Luca (20/11 pomeriggio), collaudo da visitatore (HTML in cache e contenuti REST): iframe webtv design=v5 con aspect-ratio 16:9 su /tarvisio-monte-lussari/ (6270), /colle-bettaforca/ (6283), /piz-boe-sul-gruppo-sella/ (6321), /san-candido-vista-sul-rifugio-baranci/ (6198), tutti col pg di bergfex; /corno-alle-scale/ (6135, pg del comprensorio, lg=it) con player 740×416 che mostra Corno alle Scale 1497 m alle 14:49. Nessun mp4 feratel temporaneo rimasto. /cadipietra-klausberg-ski-arena/ (6250) senza player, nessuno disponibile. Queste pagine non hanno VideoObject (il tema lo genera solo per YouTube e file video).
- Vedetta.org, video (20/11): le pagine webcam hanno una diretta HLS (Wowza, `https://5e0add8153fcd.streamlock.net:1936/vedetta2/{slug}.stream/playlist.m3u8`, player video.js + nuevo, poster = image-client/{slug}/image.jpg), non un timelapse. Tecnicamente servirebbe hls.js (il lettore di WordPress non legge m3u8). Ma i Termini e condizioni di vedetta.org (https://vedetta.org/termini-e-condizioni/) consentono solo la visione e la condivisione tramite i loro link di condivisione, e vietano espressamente di trasmettere, pubblicare o riprodurre i contenuti, riprese live comprese. Come per MilanoCam: non incorporare senza autorizzazione scritta (contatti: https://vedetta.org/contatti/). Gli stessi termini riguardano anche le 28 immagini Vedetta già in config: segnalato a Luca.

## 19 novembre 2026 (sera) — Ricerche Google Trends e nuove sorgenti

- Google Trends: la "Explore" classica (`trends.google.it/trends/explore?...&q=webcam`) si legge dal Chrome di Luca (prima l'accesso automatico era bloccato); Google avvisa che verrà dismessa a favore della nuova Explore. Per le liste complete Luca esporta i CSV "top" e "rising" dalla nuova Explore.
- "webcam", Italia, ultimi 30 giorni: picco a 100 il 19/11 (prima 16–57). Regioni in testa: Valle d'Aosta, Trentino-Alto Adige, Molise, Liguria, Veneto. Le ricerche in crescita sono quasi tutte località sciistiche.
- Confronto con le 496 pagine del sito (REST API da visitatore): coperte quasi tutte le località cercate. Mancanti: Roma (c'è solo Cervara di Roma), Torino, Verona, Pila, Verena, Campocatino, Campo Felice, Arabba, Paganella, Campigna, Obereggen, Sella Nevea, Passo San Pellegrino. Coperte in parte: Milano (solo Expo 2015 e Pirellone), Alta Badia (Colfosco), Selva di Val Gardena (Tubla), Livigno centro, Grostè (Monte Spinale), Via Lattea (Sestriere).
- Sorgenti trovate per le scoperte, quasi tutte via **Windy**: immagine `https://imgproxy.windy.com/_/full/plain/current/{id}/original.jpg` (1920×1080 nella maggior parte dei casi), pagina `https://www.windy.com/webcams/{id}`. Windy ricampiona Panomax e feratel: l'immagine può essere qualche minuto indietro. L'età si legge nella pagina Windy ("HH:MM · N min. fa"); diverse camere Windy sono ferme da mesi (Portavescovo 371 giorni, Col Margherita 66, Verona Monte Baldo 191) e vanno scartate.
- Verena: immagine originale `https://www.asiago.it/images/webcam/viewverena.jpg` (1920×1080, ogni 5 min). Verona: trovata solo SkylineWebcams (Arena), ultima spiaggia.
- **Prima webcam via Windy in config**: Duomo di Milano (Windy 1170927034), inserita da Luca, Test ok 1920×1080, 277 KB, 123 ms. Da verificare chi gestisce la camera (escludere se è MilanoCam, che vieta l'hotlink).

## 19 novembre 2026 (pomeriggio) — Sorgenti webcam e tema 2.9.1

- Scenari Digitali: sitemap = 44 webcam; 8 attive mancanti aggiunte da Luca via CSV (acquasanta-terme, bolognolaski-castelmanardo2, bolognola-pintura, foligno-ite-scarpellini, spoleto, molini-cammoro, caitorino-museo-montagna, pernazza-group-cantiere-cascia), config a 664; 6 offline non aggiunte. gran-zebru (ghiacciaioforni-gran-zebru) fuori dalla sitemap SD ma funzionante: resta.
- MeteoProject: 14 webcam in config; trovate 50 candidate su due server (`stazioni.meteoproject.it/webcam/...` e `www.meteoproject.it/ftp/webcam/...`, non intercambiabili). Attenzione: il Last-Modified di stazioni.* può essere fresco con immagine vecchia (Guardiagrele ferma a gennaio 2026). Troppe richieste ravvicinate a www.meteoproject.it fanno bloccare temporaneamente l'IP del browser.
- Vedetta: 28 in config, 180 candidate da `/wp-json/wp/v2/posts` (slug `image-client/{slug}` nel contenuto); 126 con file aggiornato, 54 fermi. In config risultano ferme lato Vedetta levanto (~4,5 giorni) e ponza (~42 giorni).
- Gli elenchi di lavoro delle candidate (MeteoProject, Scenari Digitali, Vedetta) li tiene Luca fuori dal repo.
- Tema 2.9.1: margine sotto `.wp-video` (vedi CHANGELOG). In produzione e collaudato da visitatore: HTML con `style.css?ver=2.9.1`, su /pintura-di-bolognola/ 16 px tra ogni video e la webcam (prima 0). Confermato anche da Luca.

## 19 novembre 2026 — Controllo compatibilità WordPress 7.1.5 (solo verifica, nessun codice)

- Luca ha aggiornato il core a WordPress 7.1.5 il 18/11 (footer admin: "Versione 7.1.5").
- Analisi statica: plugin v3.52, mu-plugin 1.2 e tema v2.9 usano solo API stabili del core (init, cron_schedules, shortcode, admin_menu/admin_post/wp_ajax, wp_get_image_editor, add_image_size, wp_get_attachment_image, transient). Nessun jQuery/jQuery UI (JS admin in vanilla fetch), nessuna list table, nessun codice nell'editor a blocchi: le novità 7.1 (editor sempre in iframe, jQuery UI 1.14.2, toolbar persistente, markup list table, media lato client) non li toccano.
- Collaudo dal vivo: pagina Webcam in admin senza notice PHP; Test OK su bordighera2 (162 ms), bordighera3 (193 ms), marinevillage1 (562 ms); mwp-status healthy (total 656, fresh 631, stale 20, backoff 14, missing 5).
- Da visitatore: home, ricerca, sitemap_index, 8 post a caso (bettmeralp, vietri-sul-mare, pragelato, sestola, isole-borromee, passo-della-colla-di-casaglia, rifugio-quintino-sella, ortisei) tutti 200, tema ?ver=2.9, nessun errore PHP nell'HTML. 10 immagini /webcam/ tutte 200 image/webp con X-MWP-Fast 1.2 (157–255 ms); /webcam/colla/full come <img> → WebP.
- Sottocategorie (pagine rigenerate da W3TC stamattina, quindi già col core 7.1.5): regione 20, zona 8, ambiente 9, alpi-orientali 1 — come il collaudo del 18/11. CF7 solo su /segnalazioni/. Lightbox su sestola ok (overlay col full). global-styles inline presente come prima (widget di ricerca a blocchi, noto).
- Console da admin: solo "InvalidStateError: Transition was aborted ... ViewTransition opt-in disabled", non presente nell'HTML del visitatore: non viene dal nostro codice.
- Nota: nella lista plugin risultano attivi anche Classic Editor 1.7.0 e WP Crontrol 2.0.0 (la knowledge li dava assenti/disinstallati).

## 19/11/2026 — controllo del mattino: preload W3TC (configurazione del 18/11) OK

Controllo automatico delle 9:07 (Chrome di Luca, fetch anonimi senza cache). Pagine: 25 post presi a caso da 5 pagine della REST API, tutti 200: **19/25 già in page cache** (generate da 50 min a 12 h prima, coerente con la durata di 12 h) con risposta mediana **66 ms**; 6/25 generate al momento (zinola-di-savona, reggello, ovindoli-monte-magnola, piz-boe, carloforte, manarola) con mediana **372 ms** (ieri i miss erano 0,8–1,2 s). Prima del preload quasi tutte le pagine webcam erano fuori cache. Home: 200, in cache dalle 00:13. Immagini: 10 webcam a caso (/768), tutte 200 con X-MWP-Fast 1.2, mediana 286 ms, max 434 ms (prima richiesta, variante rigenerata). Nessuna azione necessaria.

Secondo controllo ~9:12 (chat, altro campione casuale, stesso metodo): 25 post su 494, tutti 200; **19/25 in cache** (generate 4–12 h prima), mediana **64 ms**; 6/25 generate al momento (camigliatello, monte-pora, vigo-di-fassa, paola, marina-di-bibbona, carloforte), mediana **374 ms**, max 476 ms. Home in cache dalle 00:13, 76 ms. Immagini: 10 webcam a caso (barrea, scott-base, paola, cristallo, carloforte, roccaraso, rosignano, capracotta, ponza, rapallo), tutte 200, webp, X-MWP-Fast 1.2, 100–410 ms, Last-Modified 0–10 min fa → la cache non serve foto vecchie.
Perché ~1 pagina su 4 è fuori cache: il preload fa 12 pagine/15 min ≈ 48/h, quindi ~10 h per ripassare ~494 post, con lifetime 12 h; una parte delle pagine scade prima del ripasso (garbage collection 1 h). Possibili ritocchi, da decidere con Luca (modifica W3TC): 20–24 pagine/15 min oppure lifetime più lungo; prima valutare il carico su Tophost.

## 18/11/2026 (19) — Tema v2.9 in produzione e collaudato

Luca ha caricato i 3 file alle 19:00. Verifica da visitatore: ?ver=2.9; /category/regione/ "Scegli la regione" 20 card, /category/zona-geografica/ "Scegli la zona" 8 (Alpi Orientali 110 = conteggio con Dolomiti), /category/ambiente/ "Scegli l'ambiente" 9, alpi-orientali → "Sottocategorie" con Dolomiti (48); tutte con miniatura, la prima eager/high; abruzzo, pagina 2 e home senza griglia (prima card eager/high come prima); nessun errore PHP. Nota: sulla parola "webcam" di "Tutte le webcam" AdSense aggiunge un link con icona (classe google-anno, annotazioni di ricerca delle Auto ads): non è del tema, si disattiva dalle impostazioni Auto ads di AdSense.

## 18/11/2026 (18) — Tema v2.9 pronto, deploy da fare

Sottocategorie in testa alle categorie madri (regione, zona-geografica, ambiente e ogni categoria con figlie): commit 97cf6cf. File consegnati a Luca a fine sessione (functions.php 27.026 B, index.php 7.214 B, style.css 11.943 B): NON ancora caricati. Prossima sessione: chiedere se li ha caricati, poi verifiche del CHANGELOG v2.9. Luca non vuole togliere il prefisso "Categoria:" dal titolo degli archivi.

## 18/11/2026 (17) — Pagina Chi siamo riscritta + sitemap sistemate

Luca ha applicato le correzioni del controllo sitemap: /privacy-policy/ → /privacy-policy-2/ (redirect), Uncategorized fuori sitemap. Pagina /about/ (titolo "Chi siamo") riscritta col testo proposto da Claude (chi-siamo.txt): 243 parole, H2 Immagini sempre aggiornate / Come trovare una webcam / Le fonti / Segnalazioni, galleria del 2013-14 tolta, rimozione su richiesta "entro 24 ore", meta description Yoast impostata. Verifica da visitatore 18:30: 200, cache rigenerata, 5 link interni tutti 200. Proposta di testo anche nel Claude Doc "Pagina Chi siamo – proposta di testo".

## 18/11/2026 (16) — Controllo sitemap (solo lettura)

sitemap_index = post (495 URL, tutti 200, 482 immagini, lastmod ovunque), page (6), category (46). Tutte le URL di page e category rispondono 200, nessun noindex o canonical diverso. Da sistemare (proposte a Luca): due privacy policy quasi identiche, /privacy-policy/ (2019) e /privacy-policy-2/ (11/12/2024), nessuna delle due linkata dalla home → tenere quella impostata in Impostazioni › Privacy e reindirizzare l'altra; 2 post in "Uncategorized" (passo-falzarego-rifugio-col-gallina, passo-sella-dal-rifugio-salei) da spostare nelle categorie giuste (poi la categoria esce dalla sitemap da sola); /about/ con titolo "Meteo Webcam - Meteo Webcam" e 106 parole (minore).

## 18/11/2026 (15) — Chiusura sessione Search Console

Verifica finale 18:01: Gardeccia ripristinato (200, in sitemap), ciampedie e gran-sasso cestinati (redirect attivi, fuori sitemap), post-sitemap 495 URL tutti 200, gruppo "Modified Posts" vuoto. Knowledge del progetto aggiornata e divisa: documento principale `export-knowledge-meteo-webcam-per-claude.md` (stato, regole, decisioni) + appendici storiche in `claude/appendice-storia-versioni.md`, `claude/appendice-storia-versioni-2.md`, `claude/appendice-routine-trends.md`.

## 18/11/2026 (14) — Post reindirizzati nel cestino: verifica

Luca ha cestinato i post reindirizzati. Verifica 17:55: post-sitemap 496 URL, tutti 200 tranne ciampedie-ski-area-catinaccio e gran-sasso-vista-da-fano-adriano (ancora pubblicati, RDR); i 43 indirizzi reindirizzano tutti. Nel cestino 42 post: 41 dell'elenco + post 8837 "Rifugio Gardeccia sul Catinaccio" (/rifugio-gardeccia-sul-catinaccio/), che non era nell'elenco, prima era 200 e ora dà 404 → probabile errore, da ripristinare se non voluto.

## 18/11/2026 (13) — Home redirect, post reindirizzati in sitemap, robots.txt

- Le 5 pagine reindirizzate alla home restano così (decisione di Luca: non ci sono webcam analoghe). Non riproporre.
- Ricontrollati i post in sitemap dopo l'import: 43 su 539 reindirizzati da Redirection ma ancora pubblicati, elenco in docs/post-reindirizzati-in-sitemap.txt. Da mettere in bozza (dedup di Luca).
- robots.txt: aggiunta la riga Sitemap: https://www.meteo-webcam.it/sitemap_index.xml (copia in root/robots.txt, 123 byte). Il resto va bene così.

## 18/11/2026 (12) — Tag: rendimento in Search Console (solo lettura)

16 mesi, filtro URL contenente /tag/: 124 clic (0,08% dei 156K del sito), 26,3K impressioni, CTR 0,5%, posizione 13,1; 176 tag con almeno un'impressione. Top: laguna-del-mort 95 clic (unico post: jesolo-viste-sulla-spiaggia, che cita "laguna del mort" una volta), san-foca 15 (torre-specchia-ruggeri-melendugno), tresche-conca 8 clic su 15.977 impressioni (in concorrenza col post tresche-conca), poi 0-2 clic. 639 tag: 459 con un solo post, 101 con 2, 5 vuoti; tutti index,follow (Yoast). Proposta: tag in noindex e fuori sitemap (Yoast › Impostazioni › Tassonomie › Tag), con l'OK di Luca; unico costo stimato i ~95 clic di laguna-del-mort, recuperabili rafforzando la menzione nel post di Jesolo.
Applicato da Luca alle 17:23 (Yoast, tag fuori dai risultati). Verifica da visitatore: sitemap_index = post, page, category; post_tag-sitemap.xml 404; pagine tag 200 con robots "noindex, follow" (anche dalla page cache, rigenerata 17:23); post invariati (index). Le pagine tag usciranno dall'indice man mano che Google le riscansiona.

## 18/11/2026 (11) — Icone Apple touch in produzione

Luca ha caricato i 4 PNG di root/ in /htdocs/ e tolto le 4 regole Redirection apple-touch-icon* (restano 76 regole). Verifica da visitatore: le 4 URL rispondono 200 image/png alle dimensioni giuste (180×180 4.694 B, 120×120 5.438 B), favicon.ico invariato (200, 9.540 B).

## 18/11/2026 (10) — Check dei redirect (plugin Redirection, export CSV di Luca: 86 regole)

- Loop: regola /marmolada/ → https://www.meteo-webcam.it/marmolada/ (si rimanda a se stessa, 10.261 hit): rende irraggiungibili /marmolada/ e chi ci punta (gran-vernel-sulla-marmolada, rifugio-pian-dei-fiacconi-vista-sulla-marmolada). Fix: eliminare la regola (il post /marmolada/ è pubblicato e in sitemap).
- 48 target in http://www… → un passaggio in più verso https (e i fetch del browser falliscono): meglio target relativi "/slug/".
- Catene: webcam-selva-gardena → selva-gardena → tubla…; …cima-fertazza → ristoro-belvedere… → monte-civetta…; rifugio-lagazuoi-vista-sud-est → rifugio-lagazuoi-vista-sulle-tofane → cortina-…; /2015/09/16/rifugio-ferraro… → rifugio-ferraro… → home.
- Doppioni: /2014/06/19/lignano-sabbiadoro/, /2016/05/10/marsico-nuovo/, /2014/10/17/sanremo-portosole/, /val-brembana-vista-dal-paese-di-branzi/.
- /antartide-scott-base-arrival-heights senza slash finale: la versione con slash risponde 200 (non reindirizzata).
- 5 pagine reindirizzate alla home (rifugio-ferraro, rifugio-chiggiato, carestiato ×2, roma-vista-panoramica-sud-est): Google le tratta come soft 404 → meglio una webcam vicina o 410. 4 regole apple-touch-icon*.png → home (~40k hit): meglio un'icona vera in root.
- Regex /201x/(.*)/(.*)/(.*)/ → http://www…/$3 senza slash: meglio "/$3/".
- Consegnato a Luca redirects-corretti.csv (81 regole: target relativi, catene accorciate, loop e doppioni tolti, slash aggiunto). Home redirect e apple-touch-icon lasciati invariati, da decidere.

- Esito (16:45): Luca ha corretto a mano loop Marmolada e catene, poi ha importato redirects-import.csv (80 regole, gruppo Redirections). Verifica da visitatore, sequenziale (in parallelo Tophost risponde 503): 79/80 OK, un solo salto verso un target 200; /marmolada/ si apre. Unica eccezione /antartide-scott-base-arrival-heights/: servita dalla page cache W3TC (Disk Enhanced, copia delle 15:22, prima della regola con slash) che salta PHP e quindi Redirection → serve il flush. Lezione: dopo una nuova regola su un URL di un post pubblicato, svuotare la cache di quella pagina.

## 18/11/2026 (9) — Check generale Search Console (solo lettura, nessun codice)

- Rendimento: 156K clic e 9,02M impressioni in 16 mesi, posizione media 8,3. Il calo da fine agosto è stagionale: nel 2025 c'è lo stesso andamento (picco estivo, poi calo a settembre, picchi invernali con la neve).
- Indicizzazione: 595 indicizzate, ~1,3K no. Crawled-not-indexed 586: quasi tutte /webcam/{slug}/full (immagini), tag, categorie, paginazione, wp-emoji js, URL con ?_gl= (linker GA); solo ~6 post veri (chamois-colle-fontana-fredda, landsort, barrea, santa-cristina, canevara, castel-di-sangro). Discovered-not-indexed 560: ~184+ tag, 20 categorie, 46 post/pagine, quasi tutti post che fanno redirect.
- **Sitemap post-sitemap.xml: 43 URL su 539 fanno redirect** (post doppi reindirizzati da Redirection ma ancora pubblicati; es. selva-gardena → tubla..., etna-versante-est → etna, livigno-hotel-alpina → livigno-vista-sul-paese, passo-dello-stelvio-confine → passo-dello-stelvio). 21 di questi non si seguono da fetch (catena con un passaggio su un altro host/protocollo, da verificare in Redirection).
- **Redirect error (2): /marmolada/ e /gran-vernel-sulla-marmolada/ mostrano una pagina di errore nel browser (probabile loop di redirect)**, ed entrambe sono in sitemap.
- Sitemap index: 1.225 URL di cui 634 tag; i tag sono la maggior parte del non indicizzato.
- robots.txt senza riga Sitemap (minore: le sitemap sono inviate in GSC).
- Core Web Vitals: 326/326 buone su mobile e desktop. Breadcrumb: 381 validi, 0 errori. Azioni manuali: nessuna.
- Da decidere con Luca: correzione del loop marmolada; post reindirizzati fuori dalla sitemap (bozza/cestino o noindex) come parte della sua dedup; eventuale noindex dei tag in Yoast (prima controllare se qualche tag ha impressioni).

## 18/11/2026 (8) — GSC: video non indicizzati, decisione "non intervenire" (nessun codice)

Report Video di Search Console (dati al 16/11): 70 video rilevati, 19 indicizzati, 51 no, tutti con un solo motivo, "Video isn't on a watch page" (convalida avviata il 2/11, fallita il 5/11). Il video non è il contenuto principale delle pagine webcam (prima l'immagine mwp-webcam, poi l'embed YouTube o il timelapse mp4): è il comportamento previsto da Google. Confronto da visitatore tra 6 pagine indicizzate e 6 no: struttura uguale, VideoObject presente in entrambe, sorgenti miste in entrambi i gruppi → i 19 indicizzati sono casi al limite. Riguarda solo i risultati video: le pagine restano nella ricerca web. Decisione di Luca: non intervenire (niente video sopra l'immagine, niente pagine video dedicate, VideoObject lasciato) e non rilanciare la convalida. Da non riproporre.

## 18/11/2026 (7) — tema v2.8: lente di ricerca in linea col menu

La lente (v2.6) andava a capo sotto il menu anche su desktop (.site-nav blocco). Ora .site-nav flex; mobile: titolo centrato, lente in alto a destra, menu su una riga (verificato con anteprima a 390 e 360px). Niente hamburger: 3 voci di menu.

## 18/11/2026 (6) — W3TC: preload e durata della cache pagine (configurazione, nessun codice)

Prima: durata cache pagine 3600 s, preload 5 pagine ogni 900 s (20/h) dalla sitemap_index (1.225 URL, di cui 634 tag) → quasi tutte le pagine webcam fredde (TTFB 0,8–1,2 s contro 80 ms in cache). Applicato da Claude con OK di Luca (Performance > Page Cache, "Plugin configuration successfully updated"): Sitemap URL del preload = https://www.meteo-webcam.it/post-sitemap.xml (539 post), Pages per interval 12, Update interval 900 s (48/h ≈ 576 in 12 h), Maximum lifetime 43200 s (12 h). Invariati: garbage collection 3600 s, purge su modifica post (home, post, feed), /webcam in "Never cache". Freschezza immagini non toccata: l'HTML contiene solo l'URL, il server serve sempre l'ultima immagine e Chrome rispetta il max-age = TTL (verificato). Il preload gira sul WP-Cron (cron-job.org ogni minuto) insieme al cron MWP; il lock di wp-cron evita run sovrapposti. Wp-config: aggiunti WP_DEBUG_DISPLAY false + display_errors 0 (Luca, 15:05).

## 18/11/2026 (5) — mu-plugin 1.2: fix HTTP 500 della 1.1 (file incompleto sul server)

Dopo il deploy della 1.1 le varianti da rigenerare davano 500 "undefined function mwpf_make_webp()" (righe da 1.0: file arrivato incompleto). 1.2: avvio in fondo al file con guardia function_exists + header X-MWP-Fast = versione. display_errors attivo in produzione (il 500 mostrava il path del server): da spegnere in wp-config.
Collaudo 1.2 in produzione (15:03): X-MWP-Fast 1.2; 60 richieste su due campioni nuovi, tutte 200 (le webcam in 500 tornate a posto). Prima richiesta di una variante da rigenerare: mediana 257 ms (768) / 593 ms (1024, conversione GD inclusa); richieste successive: mediana 97 ms. Non in corsia veloce solo le webcam con originale più stretto della variante (landsort ×3, parigi-torre-eiffel-2): il plugin serve l'originale JPEG, comportamento previsto.

## 18/11/2026 (4) — mu-plugin MWP Fast Images v1.1: la corsia veloce ora rigenera le varianti

Scoperto verificando la freschezza delle immagini per la proposta W3TC: su 20 webcam a caso 0 passavano dalla corsia veloce. Il cron (dalla v3.31) scrive solo l'originale, le varianti si rigenerano alla prima richiesta → per le pagine poco visitate la 1.0 cadeva quasi sempre sul plugin. La 1.1 rigenera la variante nel mu-plugin (GD/Imagick, q82, atomico) e la serve subito. Il README va corretto: niente warm-up delle varianti nel cron dalla v3.31.
Verifica freschezza immagini (test dal vivo, 18/11): Chrome usa il PRIMO max-age del Cache-Control doppio (quello del plugin, = TTL), non il max-age=31536000 di nginx: dopo 602 s la stessa URL /webcam/colla/480?... è stata riscaricata dal server con un'immagine più recente (Last-Modified 13:41:22 → 13:46:56). Quindi l'età dell'HTML in cache non rende vecchia l'immagine: conta solo il TTL della webcam.

## 18/11/2026 (3) — plugin v3.52: lightbox con srcset (variante adatta allo schermo)

Lightbox: srcset delle varianti + full alla larghezza reale, sizes calcolato sullo spazio effettivo (96vw o 96vh × proporzioni). Colla: telefoni e laptop 1366 → 1600 (503 KB) invece del full (781 KB); schermi grandi/retina → full come prima. CSS inline dei blocchi lasciato: il widget di ricerca a blocchi (home/archivi) usa global-styles per il pulsante, guadagno ~1–2 KB compressi. Cache-Control doppio sulle immagini: lo aggiunge nginx di Tophost, non correggibile da PHP (gia' noto dalla v3.29). Resta: Cache Preload W3TC (configurazione).

## 18/11/2026 (2) — tema v2.7: priorità immagini corretta + Contact Form 7 solo dove serve

Punti 3 e 4 dell'audit velocità. (3) mwt_featured_image_attrs ora tocca solo la featured del post singolo; prima card di home/archivi eager + fetchpriority high in index.php; le altre immagini le decide WordPress (prima la home aveva l'LCP lazy e i post caricavano subito le miniature Related Posts). (4) CF7: script e CSS solo dove c'è un modulo (mwt_page_has_cf7 su wpcf7_load_js/css: shortcode o blocco CF7, più la pagina "segnalazioni"; unico modulo del sito in /segnalazioni/, confermato da Luca). Tracker form GTM4WP lasciato com'è (tocca gli analytics). Punto 5 (sizes) chiuso senza modifiche: WP aggiunge già sizes="auto" alle webcam lazy. Collaudo v2.7 in produzione OK (prima card eager/high, CF7 solo in /segnalazioni/) tranne le miniature CRP, forzate eager dal plugin CRP stesso → v2.7.1 con mwt_crp_lazy_thumbs (the_content, PHP_INT_MAX, solo img.crp_thumb). Collaudo v2.7.1 OK (?ver=2.7.1; su Colla e Bordighera le miniature CRP non sono più eager: ora le prende il Lazy Load di W3TC, data-src + classe lazy, come previsto per le img lazy). Restano: punto 2 (Cache Preload W3TC, configurazione) e i minori (CSS inline blocchi, lightbox su full ~800 KB, Cache-Control doppio di nginx).

## 18/11/2026 — mu-plugin MWP Fast Images v1.0: corsia veloce per le immagini webcam

Audit velocità del 18/11 (codice + misure dal browser come visitatore anonimo). Problema n.1: ogni immagine /webcam/... passava dal bootstrap completo di WordPress → 650–1300 ms contro ~90 ms del file statico (stesso WebP colla-768). Fix: `plugin/mu-plugins/mwp-fast-images.php` in wp-content/mu-plugins/, serve la variante già pronta prima dei plugin normali; ogni caso non ordinario prosegue verso il plugin (cold-start, cortesia >24h, varianti da rigenerare, /full in navigazione, ?mwp_preview, mwp-status, 404). Header come il plugin + Content-Length + 304 + `X-MWP-Fast: 1`. Costanti allineate ai default del plugin (sizes, 24h, swr 300): da aggiornare se si aggiungono quei filtri. Plugin MWP invariato (v3.51).
Collaudo in produzione 18/11 (Luca ha caricato il file; fetch senza cache da browser anonimo): colla/480 ~90-250 ms, /768 ~110-130 ms, /1024 ~130-175 ms, /full (750 KB) ~190-265 ms, tutte con X-MWP-Fast: 1 (prima 650-1300 ms). Una richiesta occasionale passa al plugin (~390 ms) mentre il cron rigenera le varianti: comportamento previsto. Redirect di /full in navigazione, mwp-status e Test OK (verifiche di Luca).
Altri punti emersi dall'audit, ancora da fare: (2) cache pagine W3TC spesso fredda (miss 0,8–1,2 s, hit 80 ms) → attivare Cache Preload da sitemap e rivedere durata/regole di svuotamento; (3) tema: mwt_featured_image_attrs forza loading su tutte le immagini (home: prima card lazy+fetchpriority high; post: miniature Related Posts eager) → eager solo sulla featured; (4) CF7 (4 JS + CSS) e tracker form GTM4WP caricati in ogni pagina → solo sulla pagina segnalazioni; (5) shortcode: sizes "1024px" ma colonna 740px → il desktop scarica la variante 1024; minori: ~16 KB CSS inline dei blocchi (global-styles/block-library: i remove_action del tema non funzionano più), lightbox sul full da ~800 KB (Colla), Cache-Control doppio (max-age del plugin + max-age=31536000 di nginx) sulle immagini.

## 18/11/2026 — tema v2.6: webcam precedente/successiva + ricerca nell'header (overlay) e nel post singolo

Tre richieste di Luca, tutte sul tema:
1. Prev/next nel post singolo: `mwt_post_nav()` con get_previous_post(true)/get_next_post(true) (stessa categoria) etichettate "‹ Webcam precedente: …" / "Webcam successiva: … ›"; CSS .post-nav; assente sui post senza adiacenti.
2. Ricerca nel post singolo: `mwt_post_search()` — form .post-search sotto il contenuto.
3. Ricerca nell'header: pulsante icona lente (SVG inline) nella nav che apre overlay .search-overlay fixed in alto col form; focus automatico, chiusura con ESC/click fuori/re-click; aria-expanded. ~15 righe di JS inline in index.php (unica eccezione alla filosofia zero-JS, nessun asset esterno).
MWT_VERSION 2.6, style.css Version 2.6, CHANGELOG aggiornato. Commit afb590e su main. Deploy: FTP di functions.php + style.css + index.php in wp-content/themes/meteo-webcam/ + flush page cache W3TC. Verifiche: ?ver=2.6 sul CSS, aprire/chiudere l'overlay (click, ESC, click fuori), prev/next in fondo a una pagina webcam, form di ricerca nello stesso punto.

## MilanoCam.it (12 novembre 2026) — CHIUSO: policy anti-hotlink esplicita

- Richiesta di Luca: estrarre l'hotlink per 4 webcam di milanocam.it (ParcoVergani, Duomo, PortaNuova_1, CityLife_1).
- Diagnosi: le pagine caricano l'immagine via `/_modules/cam_template/display_image.php?image_dir={dir}&image_filename={file}_1920x1080&pid={n}` (niente .jpg statici full; il parametro `rotate=` vuoto causa errori intermittenti squid). Il sito (hosting Aruba) dichiara le immagini con content-type text/html → fix generico nel plugin v3.51 (helper mwp_body_is_image, magic bytes; applicato a mwp_curl_get e al curl_multi del cron).
- ESITO: `display_image.php` restituisce alle richieste esterne il PLACEHOLDER anti-hotlink "High resolution webcam images linking is not allowed. Please link www.milanocam.it" (640×465, ~59 KB): il Test "OK" della v3.51 scaricava quello. Il gestore vieta esplicitamente l'hotlink; weserv e il server Tophost vengono comunque bloccati/serviti con l'errore.
- DECISIONE (Luca + Vibe): non aggirare la policy. Eliminare le 4 webcam dalla config e trasformare gli shortcode in link; aggiungere MilanoCam.it alla pagina /siti-di-webcam-meteo/. La v3.51 resta in repo come fix generico (server che dichiarano text/html per immagini vere).
- Slug: ricordo che le rotte /webcam/{slug} accettano solo [a-z0-9-] (niente underscore).

## 12/11/2026 — plugin v3.50: rimosso il link testuale nell'overlay

Decisione Luca dopo il collaudo v3.49: il link "Torna alla pagina della webcam" non serve, qualsiasi click nell'overlay chiude. Rimosso link e CSS .mwp-back; click su immagine o sfondo chiude, ESC chiude. Deploy: FTP + flush W3TC.

## 12/11/2026 — plugin v3.49: link di ritorno nell'overlay a posizione stabile

Collaudo v3.48 di Luca (screenshot): mentre l'immagine caricava il link "Torna alla pagina della webcam" era centrato nel mezzo (flex column, immagine ad altezza 0) e saltava in alto all'apparire dell'immagine, troppo attaccato all'immagine. Fix v3.49: link in CSS absolute, top:14px, tutta larghezza, testo centrato — posizione stabile durante il caricamento. Deploy: FTP + flush W3TC.

## 12/11/2026 — plugin v3.48: eliminata la pagina full, link di ritorno nel lightbox

(1) Collaudo Luca: la pagina /full (v3.46) mostrava l'immagine alla stessa dimensione del lightbox (CSS max-width/max-height al viewport) e il click non ingrandiva nulla; inoltre il link "Torna" andava quasi sempre in home (slug webcam != slug post, es. "colla" vs "passo-della-colla-di-casaglia").
(2) Decisione Luca: eliminata la pagina HTML /full. Navigazione top-level su /webcam/{slug}/full = redirect 302 alla pagina della webcam, cercata per shortcode [mwp_webcam slug="..."] (wpdb LIKE, poi get_page_by_path, fallback home) — nuovo helper mwp_webcam_page_url().
(3) Lightbox: click sull'immagine = chiude l'overlay (torna alla pagina); link testuale "← Torna alla pagina della webcam" in alto a sinistra nell'overlay (chiude l'overlay); tooltip "Clicca per ingrandire" nel post, "Clicca per chiudere" in overlay; overlay img max-height 92vh per fare spazio al link.
(4) Invariato: richieste <img> su /full = WebP raw; Vary: Sec-Fetch-Dest; anteprime admin linkano /webcam/{slug}?ts=. Deploy: FTP plugin + flush W3TC.

# Note operative e casi limite

AGGIORNAMENTO 9/11 (v3.47, fix dopo collaudo v3.46): la pagina full con link di ritorno compariva solo dopo Ctrl+F5 perché il click dal lightbox navigava alla STESSA URL appena scaricata come <img> (cache browser max-age 300 serviva il WebP raw). Fix: ts nuovo al momento del click + Vary: Sec-Fetch-Dest sulle risposte immagine di /full. Nota deploy di Luca: il tema v2.5 era GIÀ stato caricato giorni prima (il deploy del 6/11 annullato per FTP giù era stato recuperato dopo).

AGGIORNAMENTO 9/11 (v3.46, dopo collaudo v3.45 di Luca): pagina full HTML con link "← Torna alla pagina della webcam." (navigazioni top-level su /webcam/{slug}/full, via Sec-Fetch-Dest: document; le <img> ricevono ancora il WebP raw) + tooltip "Clicca per vedere l'immagine alla massima risoluzione" sull'immagine nel post e nel lightbox. Deploy FTP pendente.

## 30 ottobre 2026 — tema v1.5: conversione featured images JPEG → WebP

## 9 novembre 2026

(1) Plugin v3.45 (commit 8a1102f): lightbox — click SULL'IMMAGINE aperta in overlay ora porta alla pagina full (/webcam/{slug}/full?ts=..., stessa URL cache-bustata dell'src dell'overlay); chiusura con ESC/click fuori invariata; cursore pointer sull'immagine in overlay. Nessun altro cambiamento (fetch, cron, cache, dataLayer, admin). Deploy pendente: FTP del plugin in wp-content/plugins/ + flush W3TC; verifica lightbox su una pagina webcam.

(2) Check settimanale "webcam più cercate" rilanciato manualmente su richiesta di Luca (oltre al run automatico del mattino): 529 pagine webcam confermate (il run automatico del mattino ne vedeva 431, artefatto di paginazione REST non riprodotto); 75 presenti / 142 mancanti / 22 viste specifiche. Top 7 invariata (abisko/stoccolma estere, rovaniemi/milano/asiago/etna presenti, marina di ragusa da aggiungere). Nuove candidate vs 7/11: caorle, deiva marina, ezzi mannu, enego, forte dei marmi; uscite: fermo, fossacesia, foppolo, gizzeria. Snapshot in Knowledge progetti-web (meteo-webcam-trends-weekly.md).

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
- Diagnosi dal report Video di Google Search Console: le pagine con video YouTube embed NON risultavano indicizzate come pagine video (es. /san-pellegrino-in-alpe/), mentre quelle con file .mp4 direttamente nell'HTML sì (es. /tre-cime-di-lavaredo-dalla-malga-langalm/). L'ispezione GSC della pagina San Pellegrino mostra che Google vede il video (content URL = iframe embed YuvgiDTzxhU, thumbnail fornito da YouTube) ma lo classifica "supplementary content": senza dati strutturati VideoObject la pagina watch di YouTube resta l'unica ospite canonica del video e la pagina del sito non entra nel registro video.
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
- **Notifiche GA4 server-side via Measurement Protocol** (nessun tag GTM, diverso dal vecchio evento client-side chiuso il 30/10): al 3° fallimento consecutivo di fetch (filtro `mwp_ga_offline_threshold`, default 3, ~7 minuti dal guasto) il cron invia l'evento GA4 `webcam_offline` con parametri `webcam_slug`, `webcam_name`, `failure_count`; alla ripresa invia `webcam_online` (se il conteggio era ≥ soglia); le webcam ancora morte mandano un promemoria 1/giorno (fail marker ora conta fino a 1000 invece di 20: oltre count 12 ogni marker = un giorno di morte in più, per il promemorio quotidiano). Hook in `mwp_mark_fail`/`mwp_clear_fail`.
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

- Luca esegue il dry-run del test delle 10 righe (v2.2 caricata in produzione) e nota un contesto anomalo su #1546. Riverifica sul dump: 9/10 righe OK (prima menzione in prosa visibile subito dopo lo shortcode); su #1546 lo snippet puntava a "Campo Imperatore" dentro un blocco legacy commentato `<!-- [caption]…` prima dello shortcode [mwp_webcam] → il link sarebbe stato invisibile sul frontend. Nota cosmetica: i "contesti" multi-riga del dry-run sono normali, sono gli a-capò reali del contenuto dei post.
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

## 6 novembre 2026 (16) — Tema v2.4.1: regola "parte specifica" per le frasi chiave (rivede la v2.4)

- Luca rivede la regola v2.4 (titolo intero, MAI caricata in produzione): "usa delle due parti divise da '-' la keyword meno generica, più specifica e non usata da altri post". Esempi suoi: "Rimini - Vista sulla spiaggia" → "rimini" (NON "rimini – vista sulla spiaggia"); "Antartide - Stazione McMurdo (The Observation Hill)" → "stazione mcmurdo" (NON "antartide").
- v2.4.1 (commit del 6/11): mwt_focuskw_pick valuta le due parti normalizzate (parentesi tonde scartate, minuscolo): squalifica le già usate come frase chiave (+ set runtime coerente tra dry-run e go), +100 alle formule generiche (vista/veduta/panorama/scorcio/immagine/guardando o "vista sul/la/dal..."), +10 per ogni altro titolo che contiene la parte, parità → vince la destra (specificità). Nessuna parte utilizzabile → riga MANUALE, post lasciato senza frase chiave da sistemare a mano.
- Collaudo con porting TypeScript della logica su corpus campione: McMurdo ✓ (0 vs 0 → destra), Rimini ✓ (generica 100 perde), Rimini-con-kw-già-usata → MANUALE ✓ (esito voluto), Le Grazie ✓ (portovenere citata in altro titolo), Lerici ✓ (destra generica).
- Tie-break "vince la destra": verificato che non danneggia i casi vista-* (la generica è squalificata dal +100 ≥ 50); "Cortina d'Ampezzo - Cinque Torri" → "cinque torri" (accettabile, la dry-run consente revisione caso per caso).
- Deploy (Luca): identico alla v2.4 — FTP del solo functions.php v2.4.1 → `?mwt_focuskw=1` (dry-run con nota e punteggi) → `?mwt_focuskw=1&go=1` → i MANUALE restano a mano; poi v2.5 pulita.

## 6 novembre 2026 (17) — Tema v2.5: versione pulita, keyword Yoast a mano + style.css a 2.5

- Luca non è convinto delle keyword proposte dallo snippet mwt_focuskw: inserirà le frasi chiave a mano nei ~26 post senza. La generazione automatica (v2.4 titolo intero, v2.4.1 regola "parte specifica") non è MAI entrata in produzione — l'FTP di Tophost era irraggiungibile nel pomeriggio del 6/11, il deploy è stato rimandato e poi annullato.
- v2.5 = v2.3 + nota di deploy 9 aggiornata: snippet rimosso, diff riga per riga verificato (uniche differenze: riga MWT_VERSION e nota 9), graffe bilanciate 44/44 come v2.3.
- **Allineato l'header `Version:` di style.css da 2.0 a 2.5** (richiesta esplicita di Luca: è il numero mostrato da WordPress nella pagina Temi, wp-admin/themes.php; era fermo a 2.0 dal lavoro di chiusura del 2/11). Nessuna regola CSS toccata. README.md aggiornato di conseguenza (tema v2.1 → v2.5).
- Deploy (Luca, quando l'FTP torna su): FTP di functions.php + style.css in wp-content/themes/meteo-webcam/ + flush page cache W3TC. Verifiche: `?ver=2.5` sul CSS nel sorgente, versione 2.5 in wp-admin/themes.php, `?mwt_focuskw=1` non risponde più.
- Le frasi chiave mancanti restano un lavoro manuale di Luca nell'editor Yoist (nessuna scadenza); per i titoli a due parti la sua regola resta valida come guida: la parte meno generica, più specifica e non usata da altri post.

## 6 novembre 2026 (18) — Audit Scenari Digitali (solo analisi, nessun codice)

- **Hotlink: ZERO attivi.** Via REST API di meteo-webcam.it (search=scenaridigitali) → 27 pagine con riferimenti a Scenari Digitali; ispezione post per post: nessun `<img>` attivo con src da scenaridigitali.com. La migrazione hotlink→plugin è completa per SD.
- Residui SD nelle 27 pagine (tutti legittimi): ~21 pagine con timelapse video .mp4 (api./tl.scenaridigitali.com — da tenere, come YouTube/Panomax per convenzione); ~23 pagine con crediti "Foto: api.scenaridigitali.com" + link di cortesia a www.scenaridigitali.com/webcam/...; 3 pagine con vecchi [caption] dentro commenti HTML innocui (#9645 pretare, #7949 auronzo/monte-agudo, #842 capanna-regina-margherita ×2 — webcam già via plugin: slug pretare, agudo, margherita1, margherita2).
- **BUG TROVATO**: post #7951 (passo-sella-dal-rifugio-salei) ha l'URL del timelapse CORROTTA: `https://api.scenaridigitali.com/media/a%20href=https://www.meteo-webcam.it/santa-cristina.../sassolungo/a/timelapse.mp4` (contiene "a href=...", probabile danno collaterale del rollout link interni del 6/11). URL corretta: `https://api.scenaridigitali.com/media/sassolungo/timelapse.mp4`. Da correggere a mano nell'editor.
- **Copertura**: 42 webcam SD identificate, ~31 sul sito, 11 mancanti. Enumerazione NON garantita esaustiva (SD è una JS app; sitemap.xml troncato dal fetcher a 32KB e ×7 lingue; api.scenaridigitali.com blocca i bot; web.archive.org/CDX irraggiungibile). Match per nome/slug/credito.
- **11 mancanti** (verificate una per una su scenaridigitali.com/webcam/{slug}): LIVE (candidate all'aggiunta): acquasanta-terme (Ascoli Piceno), molini-cammoro (Sellano PG), frontignano-belvedere (2ª cam di Frontignano, la base è già sul sito), bolognolaski-castelmanardo2 (2ª cam Castel Manardo, la #1 è sul sito), foligno-ite-scarpellini (PG). OFFLINE: ripatransone (dal 2020), piani-ragnolo (dal 2022), montebianco-rifugio-torino (dal 2024), monterosa-piramide-vincent, valsorda (Rifugio Monte Maggio), montealago (Nocera Umbra).
- ~31 sul sito = 28 confermate + camposcuola/porte-berro coperte solo come timelapse sulla pagina pintura-di-bolognola (plugin slug pintura-di-bolognola e pintura-di-bolognola-2).
- **Follow-up (istruzioni di Luca)**: sulle pagine SD il timelapse è la vista di default; il bottone "Ultima immagine" (classe sd-btn) apre l'immagine statica più recente. Pattern dell'immagine statica recuperato dai vecchi hotlink nei commenti HTML di #842/#9645: `https://api.scenaridigitali.com/media/{slug}/image.jpg` (1280×720 o 1920×1080). È l'URL da configurare nel plugin per le aggiunte future (le 5 candidate LIVE: acquasanta-terme, molini-cammoro, frontignano-belvedere, bolognolaski-castelmanardo2, foligno-ite-scarpellini). api.scenaridigitali.com blocca i client automatizzati (verificato: "site blocked automated access" su tutti gli slug testati) → la freschezza delle immagini delle 6 OFFLINE non è verificabile da fuori; le ~31 webcam SD già via plugin dimostrano che il fetch del plugin su quell'host funziona.
- Nessuna modifica a plugin/tema in questo lavoro: nessun bump di versione. Nota tecnica: open_url tronca le risposte a 32KB e decodifica le sequenze %XX (rompe JSON.parse) → per la REST usare regex tolleranti sul raw, paginazione con per_page piccolo, _fields=id,slug,content, pattern URL ammettenti i backslash.
