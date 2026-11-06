<?php
/**
 * Plugin Name: Meteo Webcam Proxy
 * Description: Proxya su meteo-webcam.it le webcam che bloccano l'hotlinking.
 *              Varianti responsive in WebP, click per aprire l'immagine a dimensione piena.
 *              Webcam gestite da una pagina di amministrazione in wp-admin.
 * Version: 3.41
 * Changelog 3.41 (evento GA4 webcam_offline + immagine di cortesia):
 *   - NOTIFICHE GA4 (Measurement Protocol, server-side, nessun GTM):
 *     quando una webcam accumula N fallimenti consecutivi (filtro
 *     'mwp_ga_offline_threshold', default 3, ~7 minuti dal guasto) il cron
 *     invia l'evento GA4 'webcam_offline' con webcam_slug, webcam_name e
 *     failure_count; quando la webcam torna online invia 'webcam_online'.
 *     Per le webcam ancora morte un promemoria al giorno (il fail marker
 *     ora conta fino a 1000 invece di 20: oltre il count 12 il backoff e'
 *     gia' di 24h, quindi ogni nuovo marker e' un giorno di morte in piu').
 *     Configurazione in admin, sezione "Notifiche webcam offline su Google
 *     Analytics": Measurement ID (G-...) + API secret (opzioni
 *     mwp_ga_measurement_id / mwp_ga_api_secret, l'API secret si crea in GA4
 *     in Amministrazione → Flussi dati → Measurement Protocol). Senza
 *     entrambi: nessun evento. Pulsante di test che valida il payload
 *     sull'endpoint debug di GA (/debug/mp/collect).
 *   - IMMAGINE DI CORTESIA: la webcam con cache stantia oltre il filtro
 *     'mwp_stale_max_age' (default 86400s = 24h: oltre, l'istantanea e'
 *     fuorviante) viene servita con un SVG "Webcam temporaneamente
 *     indisponibile" invece dell'ultima immagine; anche la webcam senza
 *     cache riceve la cortesia (prima: 503 + placeholder onerror). Lo
 *     shortcode emette ?v=off{mtime} quando la webcam e' in cortesia:
 *     URL nuova, quindi il browser non serve dalla SUA cache l'immagine
 *     vecchia. Le anteprime admin (?mwp_preview) continuano a mostrare la
 *     cache reale per la diagnosi.
 *   - RECUPERO v3.40: la nota "Webcam in tempo reale: l'immagine si
 *     aggiorna automaticamente." era dichiarata nella changelog della
 *     v3.40 ma mai emessa dal codice (il filtro mwp_refresh_note non
 *     esisteva): ora c'e' davvero, sotto l'immagine e sopra il credito.
 * Changelog 3.40 (nota standard sotto la webcam, testo visibile anche agli agenti AI):
 *   - Lo shortcode emette ora una piccola nota sotto l'immagine: "Webcam in
 *     tempo reale: l'immagine si aggiorna automaticamente." Serve al lettore
 *     e, essendo testo statico nel DOM, anche ai crawler/agenti AI, che
 *     altrimenti non hanno modo di sapere che l'immagine e' una livecam
 *     aggiornata e non una foto d'archivio.
 *   - Personalizzabile col filtro 'mwp_refresh_note' (callback che riceve
 *     $cam): restituire una stringa vuota per disattivare la nota.
 * Changelog 3.39 (autenticazione HTTP Basic/Digest per URL con credenziali):
 *   - Gli URL con credenziali (http://utente:password@host/..., tipico delle
 *     webcam Hikvision via ISAPI, es. /ISAPI/Streaming/channels/102/picture)
 *     fallivano con 401 pur funzionando da browser: curl con le credenziali
 *     dentro l'URL tenta SOLO il Basic auth, mentre questi server richiedono
 *     il Digest (il browser gestisce il challenge e infatti funziona).
 *   - Fix: le credenziali vengono estratte dall'URL (mwp_split_auth) e passate
 *     con CURLOPT_USERPWD + CURLAUTH_ANY, cosi' libcurl negozia Basic o
 *     Digest automaticamente. Applicato sia al fetch singolo (mwp_fetch /
 *     pulsante Test) sia al fetch parallelo del cron (mwp_curl_multi_fetch).
 * Changelog 3.38 (retry HTTP/1.1 forzato dopo errori di framing HTTP/2):
 *   - errno 92 ("Stream error in the HTTP/2 framing layer", curl) o 16:
 *     la libcurl dell'hosting negozia HTTP/2 con certi server (es. su
 *     Google Cloud) e lo stream muore a meta'. Ora mwp_fetch riprova
 *     automaticamente forzando HTTP/1.1 (con eventuale secondo tentativo
 *     insecure se serve), e il cron manda in coda di retry sequenziale
 *     i fetch paralleli falliti per questi errori. Caso reale (29/10):
 *     includi.insidesrl.eu/dueporti/dueporti.jpg -> Test fallito in 470ms
 *     con errno 92, server 35.214.219.154 (GCP).
 * Changelog 3.37 (il truncamento da budget non e' piu' un fallimento):
 *   - Sintomo (29/10): webcam sane (macinaie-3, campotosto) diventavano
 *     oldest_slug e non venivano MAI ricaricate dal cron, pur riuscendo
 *     al pulsante Test in <100ms. Meccanismo: le ~94 webcam morte senza
 *     cache hanno age=PHP_INT_MAX e siedono per sempre in testa alla coda
 *     stale; quando il loro backoff scade (assieme, ogni ~4,3h: il cap
 *     dei 24h era irraggiungibile, v. sotto) bruciano l'intero budget del
 *     run (10-25s per gruppo di 6), e TUTTO cio' che sta dietro il fronte
 *     - a partire dalla webcam sana piu' vecchia - viene raccolto come
 *     errno 0 + http 0 (richiesta mai completata) e marcato come FAIL:
 *     il suo backoff si sincronizza con quello delle webcam morte e la
 *     webcam resta affamata per sempre. Il ruolo di vittima passa alla
 *     successiva piu' vecchia appena la vittima viene eliminata.
 *   - Fix: errno 0 + http 0 (richiesta troncata dal budget del gruppo)
 *     NON e' un fallimento della webcam: niente marker di backoff, la
 *     webcam resta in coda e il run successivo la serve con budget pieno.
 *     Il fail con backoff resta solo per errori veri (errno > 0 o
 *     http >= 400).
 *   - Backoff portato davvero a 24h: count fino a 12 (60s * 2^11,
 *     cap 86400): le webcam morte escono dal backoff una volta al giorno,
 *     il "temporale" di fetch morti all'inizio della coda diventa raro.
 * Changelog 3.36 (retry sequenziale per i fetch paralleli mai completati):
 *   - Nel cron parallelo un fetch che non ha mai completato la richiesta
 *     (errno 0 + http 0: handle ancora in volo quando scade il budget del
 *     gruppo, o stall di curl_multi su quel server) veniva marcato come
 *     fallito e condannato al backoff progressivo SENZA retry: era il caso
 *     delle webcam OK al pulsante Test (mwp_fetch sequenziale) ma sempre
 *     http 0 nel cron (es. macinaie-monte-amiata-3: Test OK 1920x1080 in
 *     84 ms, cron FAIL http 0 a ogni run, oldest 42h). Ora questi casi
 *     entrano nella coda di retry sequenziale di fine run, stesso
 *     percorso del Test: se il retry riesce, il fail marker viene
 *     cancellato e il backoff si azzera.
 * Changelog 3.35 (rimozione log diagnosi):
 *   - Rimosso il log temporaneo su mwp-cron-debug.log introdotto in
 *     v3.33: la diagnosi e' chiusa (bug wrapper fixato in v3.33, coda
 *     sana col backoff progressivo della v3.34: fino a 120 fetch
 *     riuscite per run in ~30s, cicli completi sul parco webcam, run
 *     vuoti quando tutto e' fresco).
 *   - Nessun altro cambiamento funzionale.
 * Changelog 3.34 (backoff progressivo):
 *   - Un fetch fallito non va ritentato ogni minuto per sempre: le webcam
 *     morte (404/403/timeout) restavano per sempre le piu' vecchie in coda
 *     e si mangiavano l'intero budget di ogni run (log v3.33: 4 run su 5
 *     con 0 successi e 42-78 fail). Ora ogni fallimento consecutivo
 *     raddoppia l'attesa: 60s, 2m, 4m, 8m ... fino a 24h; il primo successo
 *     azzera il contatore. Il marker -fail.marker ora contiene il numero
 *     di fallimenti consecutivi (retrocompatibile: marker vuoto = 1).
 *   - Il log di diagnosi v3.33 resta attivo: rimuoverlo in v3.35 dopo la
 *     verifica che la coda gira pulita.
 * Changelog 3.33 (bugfix critico fetch parallelo + log diagnosi):
 *   - BUGFIX: nella v3.32 il cron parallelo passava a mwp_curl_multi_fetch
 *     e ai cicli di scrittura il wrapper ['age' =>, 'cam' =>] invece della
 *     webcam: $cam['url'] e $cam['slug'] risultavano assenti → zero richieste
 *     in volo, ogni webcam stale riceveva solo il fail.marker e non veniva
 *     MAI scaricata dal cron (le poche fetch osservate arrivavano dai
 *     cold-start dei visitatori). Questo fix riporta il ciclo completo a
 *     ~5-10 minuti come progettato in v3.32.
 *   - LOG TEMPORANEO (rimuovere in v3.34 dopo diagnosi): ogni run scrive su
 *     wp-content/mwp-cron-debug.log start (batch/budget/parallel), esito
 *     SUCCESS/FAIL per webcam con motivo, conteggi finali.
 * Changelog 3.32 (fetch parallelo nel cron):
 *   - Il cron scarica le webcam in parallelo con curl_multi, in gruppi da
 *     mwp_cron_parallel richieste (default 6, filtro configurabile). Con
 *     centinaia di webcam, molte lente (timeout 10s/25s ciascuna), il ciclo
 *     sequenziale bruciava l'intero budget del run su poche richieste appese:
 *     il costo dominante del run diventa il fetch piu' lento del gruppo, non
 *     la somma di tutti i fetch.
 *   - I fallback rari (certificato self-signed → secondo tentativo senza
 *     verifica; https morto in timeout → ritento in http) restano, ma in coda
 *     sequenziale a fine run e solo se resta tempo nel budget: nel parallelo
 *     riprovarli subito costava piu' del fetch stesso.
 *   - Su hosting senza curl_multi il cron torna automaticamente al fetch
 *     sequenziale di sempre (mwp_fetch), stesso comportamento della 3.31.
 *   - Con il tema functions.php v1.4 (budget 55s, cron-job.org ogni minuto)
 *     e per-fetch medio ~2s, il ciclo completo su ~700 webcam passa da
 *     ~26-28 minuti a ~5-10 minuti.
 * Changelog 3.31 (cron leggero + ricerca admin):
 *   - Il cron ora fa SOLO fetch + scrittura dell'immagine piena: niente più
 *     conversioni WebP (full + 4 varianti = 5 conversioni per webcam) dentro
 *     il budget di tempo. Il costo per webcam scende da ~5s a ~2s, quindi
 *     con lo stesso budget/minuto passano ~2,5 volte più webcam. Le
 *     varianti restano quelle che erano in cache e vengono rigenerate
 *     pigramente alla PRIMA RICHIESTA utile (il routing le rigenera già
 *     se più vecchie dell'originale): il costo di conversione lo paga
 *     il primo visitatore della variante che il suo browser chiede davvero,
 *     non più il cron per tutte le taglie. Il cron non cancella più le
 *     varianti a ogni refresh: il confronto filemtime nel routing fa sì
 *     che nessuno riceva mai una variante più vecchia dell'originale.
 *   - Campo di ricerca nella pagina admin (nome, slug o attribuzione):
 *     si combina con la paginazione — cerca "tellaro" e vedi solo le
 *     corrispondenze con Test e shortcode nella riga.
 * Changelog 3.30 (admin a 700 webcam):
 *   - Paginazione dell'elenco e delle anteprime: 10/25/50/100 webcam per
 *     pagina (menu a tendina) con frecce avanti/indietro. Con centinaia di
 *     webcam la pagina admin resta leggera sia nel DOM sia nel POST.
 *   - Ordinamento per data di inserimento decrescente (la più recente prima):
 *     ogni webcam riceve un campo "added" (timestamp Unix) alla creazione
 *     (form admin o import CSV); le webcam create prima della v3.30, senza
 *     timestamp, restano in fondo nell'ordine alfabetico.
 *   - Il salvataggio del form è ora "merge" per pagina: si salvano solo le
 *     righe visibili, le webcam delle altre pagine restano intatte; la
 *     rimozione con "×" viene registrata in un campo nascosto esplicito
 *     (mwp_delete_slugs) e la cache della webcam eliminata viene pulita.
 *     Questo abbassa anche il rischio di superare max_input_vars: con
 *     100 per pagina il POST resta sotto ~1000 variabili.
 * Changelog 3.29 (cache busting obbligatorio):
 *   - Lo shortcode emette le URL con ?v={mtime del file pieno in cache}:
 *     il server aggiunge Cache-Control max-age=31536000 a tutte le
 *     immagini (policy Tophost a livello nginx, per MIME-type: colpisce
 *     anche gli URL senza estensione), e il browser onora quella, non la
 *     max-age=600 del plugin (verificato: webcam ferma a 30+ minuti).
 *     Con il version parameter ogni refresh del cron produce URL nuovi
 *     → i visitatori vedono sempre l'immagine corrente, e la cache lunga
 *     diventa un beneficio (copie immutabili per versione).
 * Changelog 3.28 (routing senza estensione):
 *   - Nuovi URL pubblici senza estensione, che nginx non scambia per file
 *     statici (elimina la doppia header Cache-Control aggiunta da nginx
 *     sulle URL .jpg del proxy):
 *       /webcam/{slug}        → immagine piena in WebP
 *       /webcam/{slug}/full   → immagine piena in WebP (per il lightbox)
 *       /webcam/{slug}/{w}    → variante ridimensionata WebP
 *   - Retrocompatibilità totale: i vecchi URL /{slug}.jpg, -full.webp e
 *     -{w}.webp continuano a essere serviti (post e cache già pubblicati
 *     non si rompono); shortcode, lightbox e anteprime admin usano i
 *     nuovi URL.
 * Changelog 3.27 (admin):
 *   - Fix referer: il campo Referer veniva a volte salvato concatenando il
 *     valore precompilato con l'input senza schema (bug del caso "riale":
 *     https://lagomaggiorexperience.it/://www.distrettolaghi.it/). Ora
 *     mwp_normalize_cam ripulisce e ricostruisce il referer lato server:
 *     rimuove prefissi "://" o "//", aggiunge lo schema se manca, e scarta
 *     valori senza host valido. Campi Referer con autocomplete="off" per
 *     evitare che il browser fonda autofill e valore.
 *   - Import CSV con casella "Aggiorna le webcam esistenti": se spuntata,
 *     le righe con slug esistente aggiornano nome/url/referer/ttl/credito
 *     (azzerando la cache della webcam se cambia URL o referer, come il
 *     salvataggio manuale); se deselezionata, comportamento 3.23-3.26
 *     (esistenti intatte). Avviso con conteggi espliciti: aggiorna,
 *     nuove, lasciate intatte, scartate.
 * Changelog 3.26 (monitoraggio):
 *   - Endpoint di stato /webcam/mwp-status?key=... : JSON con conteggio
 *     webcam totali/fresche/stale/in backoff/senza cache, età della
 *     più vecchia e verdetto "healthy". HTTP 200 se il sistema è sano,
 *     503 se più della metà delle webcam è stantia o più del 10% è
 *     senza immagine in cache. Chiave segreta generata automaticamente
 *     e mostrata nella pagina admin (opzione mwp_status_key, confronto
 *     hash_equals). Copre il buco del monitor su wp-cron.php: il 200
 *     del cron dice solo "campanello suonato", questo dice se le
 *     immagini sono davvero fresche.
 * Changelog 3.25 (admin):
 *   - Anteprime admin di nuovo cliccabili: l'apertura a dimensione piena
 *     ora è un vero link <a target="_blank"> invece di window.open su
 *     onclick (il popup blocker di alcuni browser bloccava la finestra
 *     aperta da onclick su un <img>); la thumb aggiunge anche ?ts per
 *     evitare copie in cache quando si ricontrolla dopo un refresh.
 * Changelog 3.24 (fix import CSV):
 *   - L'import riconosce automaticamente il separatore del CSV (virgola,
 *     punto e virgola o tab) annusando la prima riga: Excel in italiano
 *     salva in CSV con ';' e con la 3.23 quelle righe venivano lette come
 *     un unico campo e scartate tutte ("Scartate 82 righe").
 *   - Tollerato il BOM UTF-8 all'inizio del file (Excel / Notepad).
 *   - Il testo di aiuto in admin lo dice esplicitamente.
 * Changelog 3.23 (scala: centinaia di webcam):
 *   - Import CSV (nome,url,referer,ttl,credito[,slug]) con unione sicura:
 *     le webcam con slug già esistente NON vengono toccate, le nuove aggiunte;
 *     righe non valide scartate con conteggio esplicito nell'avviso.
 *   - Export CSV della configurazione completa (backup / ri-importo).
 *   - Cron: oltre al batch (filtro mwp_cron_batch, default 8) un budget di
 *     tempo per run (filtro mwp_cron_time_budget, default 20 secondi):
 *     il run si interrompe prima di occupare troppo a lungo un worker PHP
 *     dell'hosting condiviso; le webcam rimaste vengono prese dal run dopo.
 *   - Anteprime admin: mostrate solo le ultime 10 per non caricare centinaia
 *     di thumb; pulsante "Mostra tutte" per il resto. La tabella di modifica
 *     resta completa (il salvataggio posta tutte le righe: con centinaia di
 *     webcam chiedere all'hosting di alzare max_input_vars, il canarino
 *     protegge comunque da salvataggi troncati).
 * Changelog 3.22 (Core Web Vitals): refresh delle webcam spostato su WP-Cron
 *   (nessun fetch remoto in richiesta, solo cold-start) con warm-up delle
 *   varianti WebP e batch limitato per run (filtro mwp_cron_batch, default 8,
 *   webcam più vecchie prima — pensato per hosting condiviso tipo Tophost);
 *   width/height sull'<img> dello shortcode (CLS);
 *   placeholder frontend su onerror; refresh JS che aggiorna anche srcset;
 *   header stale-while-revalidate.
 *   3.21: rimossa la scrittura del log temporaneo dei fetch
 *   (mwp-fetch-debug.log); resta la diagnostica usata dal pulsante Test.
 *   3.20: pulsante "Test" per webcam in admin; per gli hosting che bucano
 *   il traffico dagli IP datacenter (es. Altervista) usare come URL remoto
 *   un proxy immagini pubblico (es. images.weserv.nl). 3.19: fallback HTTP e
 *   diagnostica per fase. 3.18: timeout 10s/25s, IPv4. 3.17: header
 *   browser-like, HTTP/2. 3.16: tetti campi, esito scrittura verificato,
 *   anteprime senza fetch, canarino.
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ============================================================
 * CONFIGURAZIONE / STORAGE
 * ============================================================ */

/**
 * Ogni webcam: [
 *   'slug'   => 'nome-univoco',   // URL pubblico: /webcam/{slug}.jpg (piena)
 *                                     e /webcam/{slug}-{larghezza}.webp (varianti)
 *   'name'   => 'Nome visibile',
 *   'url'    => 'URL dell'immagine remota A DIMENSIONE PIENA',
 *   'credit' => 'Attribuzione opzionale',
 *   'ttl'    => secondi di cache lato server,
 * ]
 */
function mwp_get_webcams() {
    $cams = get_option('mwp_webcams', null);
    if (!is_array($cams)) {
        $cams = [
            [
                'slug'   => 'calata-genova',
                'name'   => 'Calata — Genova',
                'url'    => 'https://strumenti.comune.genova.it/webcams/turistiche/calata/current.jpg',
                'credit' => 'Comune di Genova',
                'ttl'    => 60,
                'referer' => '',
            ],
        ];
        update_option('mwp_webcams', $cams, false);
    }
    return $cams;
}

function mwp_get_webcam($slug) {
    foreach (mwp_get_webcams() as $cam) {
        if ($cam['slug'] === $slug) {
            return $cam;
        }
    }
    return null;
}

/** Larghezze delle varianti responsive, dalla più piccola alla più grande. */
function mwp_sizes() {
    return (array) apply_filters('mwp_sizes', [480, 768, 1024, 1600]);
}

/* ============================================================
 * CACHE E CONVERSIONE
 * Directory: wp-content/uploads/mwp-cache/
 *   {slug}-full.jpg      = originale a dimensione piena (fonte per le varianti)
 *   {slug}-full.webp      = originale riconvertito in WebP (servito al pubblico)
 *   {slug}-{w}.webp      = variante ridimensionata in WebP
 * ============================================================ */

function mwp_cache_dir() {
    $dir = WP_CONTENT_DIR . '/uploads/mwp-cache';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir;
}

function mwp_full_path($slug) {
    return mwp_cache_dir() . '/' . $slug . '-full.jpg';
}

function mwp_fullwebp_path($slug) {
    return mwp_cache_dir() . '/' . $slug . '-full.webp';
}

function mwp_variant_path($slug, $w) {
    return mwp_cache_dir() . '/' . $slug . '-' . (int) $w . '.webp';
}

/* v3.34: backoff progressivo dei fetch falliti. Prima: attesa fissa di
 * mwp_backoff_seconds (60s) → una webcam morta veniva ritentata a ogni run
 * per sempre, restando in testa alla coda stale (le piu' vecchie prima) e
 * bruciando il budget delle sane. Ora il marker contiene il conteggio dei
 * fallimenti consecutivi e l'attesa raddoppia fino a 24h; al primo successo
 * il marker viene cancellato e si riparte da zero. */
function mwp_fail_marker_path($slug) {
    return mwp_cache_dir() . '/' . $slug . '-fail.marker';
}

function mwp_fail_backoff($slug) {
    $f = mwp_fail_marker_path($slug);
    if (!is_file($f)) {
        return 0;
    }
    $count = max(1, (int) @file_get_contents($f)); // marker vuoto (pre-3.34) = 1 fallimento
    $base = max(5, (int) apply_filters('mwp_backoff_seconds', 60));
    // v3.37: count fino a 12, cosi' il tetto di 86400s (24h) e' raggiungibile
    // (con min($count, 9) il massimo reale era 60*2^8 = ~4,3h e le webcam
    // morte rientravano in testa alla coda ogni ~4 ore).
    return (int) min(86400, $base * (2 ** (min($count, 12) - 1)));
}

function mwp_in_backoff($slug) {
    $f = mwp_fail_marker_path($slug);
    return is_file($f) && (time() - (int) filemtime($f)) < mwp_fail_backoff($slug);
}

function mwp_mark_fail($slug) {
    $f = mwp_fail_marker_path($slug);
    $count = is_file($f) ? max(0, (int) @file_get_contents($f)) : 0;
    if ($count <= 0) {
        $count = 0; // marker pre-3.34 senza numero: parti da 1
    }
    // v3.41: il conteggio cresce anche oltre il vecchio cap di 20 (il backoff
    // resta capped: oltre il count 12 l'attesa e' gia' 24h), cosi' le webcam
    // ancora morte possono mandare un promemoria GA4 al giorno
    $new = min($count + 1, 1000);
    @file_put_contents($f, (string) $new);
    @touch($f);
    mwp_ga_maybe_alert_offline($slug, $new);
}

function mwp_clear_fail($slug) {
    $f = mwp_fail_marker_path($slug);
    if (!is_file($f)) {
        return;
    }
    // v3.41: se la webcam era ufficialmente offline (soglia GA superata),
    // avvisa che e' tornata online
    $prev = max(0, (int) @file_get_contents($f));
    if (mwp_ga_cfg() && $prev >= mwp_ga_offline_threshold()) {
        $cam = mwp_get_webcam($slug);
        mwp_ga_track('webcam_online', [
            'webcam_slug' => $slug,
            'webcam_name' => $cam['name'] ?? $slug,
        ]);
    }
    @unlink($f);
}

/* ============================================================
 * v3.41: IMMAGINE DI CORTESIA + NOTIFICHE GA4
 * ============================================================ */

/**
 * Oltre questa eta' la cache stantia non viene piu' servita al pubblico:
 * il proxy risponde con l'immagine di cortesia. Default 24h: entro,
 * l'ultima istantanea e' ancora utile ("com'era fino a poco fa");
 * oltre, mostrare giorni fa come "ora" e' fuorviante. Filtro configurabile.
 */
function mwp_stale_max_age() {
    return (int) apply_filters('mwp_stale_max_age', 86400);
}

/** true se la cache della webcam e' piu' vecchia della soglia di cortesia. */
function mwp_is_stale_beyond_courtesy($slug) {
    $full = mwp_full_path($slug);
    if (!is_file($full)) {
        return false;
    }
    return (time() - (int) filemtime($full)) > mwp_stale_max_age();
}

/**
 * SVG di cortesia servito dal proxy quando la webcam e' indisponibile
 * (cache assente o stantia oltre la soglia). Cache breve (5 minuti):
 * appena la webcam torna, l'URL con ?v={mtime} nuova serve l'immagine.
 */
function mwp_serve_courtesy() {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 360">'
        . '<rect width="640" height="360" fill="#e9ecef"/>'
        . '<g fill="none" stroke="#adb5bd" stroke-width="10">'
        . '<circle cx="320" cy="140" r="46"/>'
        . '<circle cx="320" cy="140" r="16" fill="#adb5bd"/>'
        . '</g>'
        . '<line x1="284" y1="104" x2="356" y2="176" stroke="#b32d2e" stroke-width="10"/>'
        . '<text x="320" y="245" font-family="sans-serif" font-size="26" fill="#6c757d" text-anchor="middle">Webcam temporaneamente indisponibile</text>'
        . '</svg>';
    header('Content-Type: image/svg+xml');
    header('Cache-Control: public, max-age=300');
    echo $svg;
    exit;
}

/** Config GA4 Measurement Protocol: servono entrambi i valori, o niente eventi. */
function mwp_ga_cfg() {
    $mid = trim((string) get_option('mwp_ga_measurement_id', ''));
    $secret = trim((string) get_option('mwp_ga_api_secret', ''));
    if ($mid === '' || $secret === '' || !preg_match('/^G-[A-Za-z0-9_\-]+$/', $mid)) {
        return null;
    }
    return ['measurement_id' => $mid, 'api_secret' => $secret];
}

/** client_id stabile per gli eventi server-side (un solo "visitatore": il sito). */
function mwp_ga_client_id() {
    $cid = (string) get_option('mwp_ga_client_id', '');
    if ($cid === '' || !preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $cid)) {
        $cid = function_exists('wp_generate_uuid4') ? wp_generate_uuid4() : md5(site_url() . microtime());
        update_option('mwp_ga_client_id', $cid, false);
    }
    return $cid;
}

/**
 * Invio server-side di un evento GA4 via Measurement Protocol.
 * $debug = true usa l'endpoint /debug/mp/collect, che RISPONDE con gli
 * errori di validazione (l'endpoint normale risponde 204 anche con API
 * secret sbagliato, silenzioso): serve al pulsante di test in admin.
 * Ritorna ['ok' => bool, 'msg' => string].
 */
function mwp_ga_track($name, $params, $debug = false) {
    $cfg = mwp_ga_cfg();
    if (!$cfg) {
        return ['ok' => false, 'msg' => 'GA non configurato: servono Measurement ID e API secret.'];
    }
    $endpoint = $debug
        ? 'https://www.google-analytics.com/debug/mp/collect'
        : 'https://www.google-analytics.com/mp/collect';
    $res = wp_remote_post(
        $endpoint . '?measurement_id=' . rawurlencode($cfg['measurement_id'])
            . '&api_secret=' . rawurlencode($cfg['api_secret']),
        [
            'timeout' => 3,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => wp_json_encode([
                'client_id' => mwp_ga_client_id(),
                'events' => [
                    ['name' => $name, 'params' => $params],
                ],
            ]),
        ]
    );
    if (is_wp_error($res)) {
        return ['ok' => false, 'msg' => $res->get_error_message()];
    }
    $code = (int) wp_remote_retrieve_response_code($res);
    $body = (string) wp_remote_retrieve_body($res);
    if ($debug) {
        $j = json_decode($body, true);
        $msgs = is_array($j) ? ($j['validationMessages'] ?? []) : [];
        if ($code >= 200 && $code < 300 && empty($msgs)) {
            return ['ok' => true, 'msg' => 'Payload valido (debug endpoint GA)'];
        }
        return ['ok' => false, 'msg' => $msgs ? wp_json_encode($msgs) : ('HTTP ' . $code)];
    }
    return ['ok' => $code >= 200 && $code < 300, 'msg' => ''];
}

/** Soglia di fallimenti consecutivi che accende l'evento webcam_offline. */
function mwp_ga_offline_threshold() {
    return max(1, (int) apply_filters('mwp_ga_offline_threshold', 3));
}

/**
 * Chiamato da mwp_mark_fail a ogni fallimento registrato: invia l'evento
 * webcam_offline al raggiungimento della soglia (es. 3° fail consecutivo,
 * ~7 minuti dal guasto) e poi, per le webcam ancora morte, un promemoria
 * al giorno (dal count 13 in poi: oltre il 12 il backoff e' di 24h, quindi
 * ogni nuovo fail marker e' un giorno di morte in piu').
 */
function mwp_ga_maybe_alert_offline($slug, $count) {
    if (!mwp_ga_cfg()) {
        return;
    }
    $threshold = mwp_ga_offline_threshold();
    if ($count !== $threshold && $count < 13) {
        return;
    }
    $cam = mwp_get_webcam($slug);
    mwp_ga_track('webcam_offline', [
        'webcam_slug'   => $slug,
        'webcam_name'   => $cam['name'] ?? $slug,
        'failure_count' => $count,
    ]);
}

/**
 * Scarica l'immagine remota aggirando i blocchi anti-hotlink più comuni.
 */
function mwp_fetch($url, $refererMode = '') {
    $host = parse_url($url, PHP_URL_HOST);
    if (!$host || !filter_var($url, FILTER_VALIDATE_URL)
        || !in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'])) {
        return null;
    }

    $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36';

    // Referer: vuoto = stesso dominio dell'immagine (default);
    // 'none' = nessun referer; URL = referer del sito che incorpora la webcam
    $referer = 'https://' . $host . '/';
    if ($refererMode === 'none') {
        $referer = '';
    } elseif ($refererMode !== '' && $refererMode !== 'host') {
        $referer = $refererMode;
    }

    if (function_exists('curl_init')) {
        $res = mwp_curl_get($url, $ua, $referer, false);
        if ($res['ok']) {
            return $res['data'];
        }
        // 2° tentativo se il problema è il certificato: self-signed è comune
        // sui server webcam con hostname dinamici (dyndns, ddns, no-ip...)
        if ($res['ssl_error']) {
            $res = mwp_curl_get($url, $ua, $referer, true);
            if ($res['ok']) {
                return $res['data'];
            }
        }

        // v3.38: errori di framing HTTP/2 (curl 92 "Stream error in the
        // HTTP/2 framing layer", 16 "HTTP/2 stream error"): la libcurl
        // dell'hosting negozia h2 con certi server e lo stream muore.
        // Riprova forzando HTTP/1.1, che quei server servono senza problemi.
        $last = $GLOBALS['mwp_last_fetch'] ?? [];
        if (in_array((int) ($last['errno'] ?? 0), [16, 92], true)) {
            $res = mwp_curl_get($url, $ua, $referer, false, true);
            if ($res['ok']) {
                return $res['data'];
            }
            if ($res['ssl_error']) {
                $res = mwp_curl_get($url, $ua, $referer, true, true);
                if ($res['ok']) {
                    return $res['data'];
                }
            }
        }

        // 3° tentativo (3.19): se HTTPS muore in timeout (connessione mai
        // aperta, es. porta 443 bucherata dall'hosting remoto), riprova
        // in HTTP sulla porta 80
        $last = $GLOBALS['mwp_last_fetch'] ?? [];
        if (strpos($url, 'https://') === 0 && (int) ($last['errno'] ?? 0) === 28) {
            $GLOBALS['mwp_last_fetch_https'] = $last;
            $urlHttp = 'http://' . substr($url, 8);
            $res = mwp_curl_get($urlHttp, $ua, $referer, false);
            if ($res['ok']) {
                return $res['data'];
            }
        }

        return null;
    }

    $http = [
        'timeout'         => 10,
        'user_agent'      => $ua,
        'follow_location' => 1,
        'max_redirects'   => 3,
    ];
    if ($referer !== '') {
        $http['referer'] = $referer;
    }
    $ctx = stream_context_create(['http' => $http]);
    $data = @file_get_contents($url, false, $ctx);
    return ($data !== false && strlen($data) > 500) ? $data : null;
}

/**
 * Esegue una singola richiesta cURL.
 * $insecure = true disattiva la verifica del certificato (per i self-signed).
 */
/**
 * Opzioni cURL browser-like condivise dal fetch singolo (mwp_curl_get) e
 * da quello parallelo del cron (mwp_curl_multi_fetch, v3.32).
 * $insecure = true disattiva la verifica del certificato (self-signed).
 */
/**
 * v3.39: estrae le credenziali HTTP da un URL (utente:password@host).
 * Ritorna [URL senza credenziali, 'utente:password' decodificato].
 * Serve perche' con le credenziali dentro l'URL curl fa SOLO Basic auth,
 * mentre i server ISAPI (Hikvision) richiedono il Digest: passandole con
 * CURLOPT_USERPWD + CURLAUTH_ANY libcurl negozia il metodo giusto.
 */
function mwp_split_auth($url) {
    if (!preg_match('#^(https?://)([^@/:]+):([^@/]+)@#i', (string) $url, $m)) {
        return [$url, ''];
    }
    $clean = $m[1] . substr($url, strlen($m[0]));
    return [$clean, rawurldecode($m[2]) . ':' . rawurldecode($m[3])];
}

/**
 * Opzioni cURL browser-like condivise dal fetch singolo (mwp_curl_get) e
 * da quello parallelo del cron (mwp_curl_multi_fetch, v3.32).
 * $insecure = true disattiva la verifica del certificato (self-signed).
 * v3.39: $userpwd = credenziali HTTP ('utente:password') estratte dall'URL.
 */
function mwp_curl_opts($ua, $referer, $insecure, $forceHttp1 = false, $userpwd = '') {
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 3,
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_USERAGENT      => $ua,
        CURLOPT_SSL_VERIFYPEER => !$insecure,
        CURLOPT_SSL_VERIFYHOST => $insecure ? 0 : 2,
        // Intestazioni da browser reale: alcuni anti-hotlink/anti-bot
        // (es. Altervista) filtrano sul fingerprint della richiesta, non solo
        // sul referer: mancano Sec-Fetch-* e Accept-Language? blocco.
        CURLOPT_HTTPHEADER     => [
            'Accept: image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
            'Accept-Language: it-IT,it;q=0.9,en;q=0.8',
            'Sec-Fetch-Dest: image',
            'Sec-Fetch-Mode: no-cors',
            'Sec-Fetch-Site: cross-site',
        ],
    ];
    // v3.38: $forceHttp1 = retry dopo errori di framing HTTP/2 (curl 92/16):
    // certi server negoziano h2 e poi rompono lo stream. Altrimenti HTTP/2
    // solo se libcurl lo supporta (come da 3.17).
    $ver = function_exists('curl_version') ? curl_version() : [];
    $h2ok = defined('CURL_HTTP_VERSION_2TLS') && !empty($ver['features']) && ($ver['features'] & CURL_VERSION_HTTP2);
    if ($forceHttp1 && defined('CURL_HTTP_VERSION_1_1')) {
        $opts[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_1;
    } elseif ($h2ok) {
        $opts[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_2TLS;
    }
    // Preferisci IPv4: su molti shared hosting la risoluzione IPv6 di certi
    // server resta appesa fino allo scatto del timeout (errno 28 senza byte)
    if (defined('CURL_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
        $opts[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
    }
    // v3.39: autenticazione HTTP Basic/Digest (es. webcam Hikvision ISAPI con
    // URL http://utente:password@host/...). CURLAUTH_ANY lascia a libcurl la
    // negoziazione: con le credenziali nell'URL farebbe solo Basic e i server
    // ISAPI rispondono 401 anche se l'URL funziona da browser.
    if ($userpwd !== '' && defined('CURLAUTH_ANY')) {
        $opts[CURLOPT_USERPWD] = $userpwd;
        $opts[CURLOPT_HTTPAUTH] = CURLAUTH_ANY;
    }
    if ($referer !== '') {
        $opts[CURLOPT_REFERER] = $referer;
    }
    return $opts;
}

/**
 * Esegue una singola richiesta cURL.
 * $insecure = true disattiva la verifica del certificato (per i self-signed).
 */
function mwp_curl_get($url, $ua, $referer, $insecure, $forceHttp1 = false) {
    // v3.39: credenziali nell'URL (utente:password@host) fuori dall'URL e
    // dentro CURLOPT_USERPWD con CURLAUTH_ANY (Basic o Digest).
    list($fetchUrl, $userpwd) = mwp_split_auth($url);
    $ch = curl_init($fetchUrl);
    curl_setopt_array($ch, mwp_curl_opts($ua, $referer, $insecure, $forceHttp1, $userpwd));
    $data  = curl_exec($ch);
    $code  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $type  = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $errno = curl_errno($ch);

    // Esito dell'ultimo fetch: usato dal pulsante "Test" in admin
    $info = curl_getinfo($ch);
    curl_close($ch);
    $GLOBALS['mwp_last_fetch'] = [
        'code'   => $code,
        'type'   => $type,
        'bytes'  => $data === false ? -1 : strlen($data),
        'errno'  => $errno,
        'err'    => $errno ? (curl_strerror($errno) ?: '') : '',
        'dns_ms' => isset($info['namelookup_time']) ? round($info['namelookup_time'] * 1000) : null,
        'conn_ms' => isset($info['connect_time']) ? round($info['connect_time'] * 1000) : null,
        'ip'     => $info['primary_ip'] ?? '',
    ];

    $typeOk = str_starts_with($type, 'image/')
        || $type === 'application/octet-stream'
        || $type === ''; // alcuni server webcam non dichiarano il tipo
    return [
        'ok'        => $data !== false && $code >= 200 && $code < 300 && $typeOk && strlen($data) > 500,
        'data'      => $data,
        'ssl_error' => in_array($errno, [35, 51, 58, 60, 64, 66, 77, 82, 83, 84, 90, 91, 98], true),
    ];
}

/**
 * Referer risolto per una webcam: stessa logica di mwp_fetch(), fattorizzata
 * per riusarla nel fetch parallelo del cron (v3.32).
 */
function mwp_referer_for($cam) {
    $host = parse_url($cam['url'], PHP_URL_HOST);
    $mode = (string) ($cam['referer'] ?? '');
    if ($mode === 'none') {
        return '';
    }
    if ($mode === '' || $mode === 'host') {
        return 'https://' . $host . '/';
    }
    return $mode;
}

/**
 * Fetch PARALLELO di un gruppo di webcam (v3.32, curl_multi).
 * $group  = array di webcam; $maxWait = secondi di attesa massima per il
 * gruppo (il residuo del budget del run, gestito dal chiamante).
 * Ritorna [slug => ['data' => string|null, 'errno' => int, 'code' => int,
 * 'ssl_error' => bool]]. Le webcam senza risultato (URL non valido) sono
 * assenti: il chiamante le tratta come fetch fallito.
 */
function mwp_curl_multi_fetch($group, $maxWait = 30) {
    $results = [];
    $map = [];
    $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36';
    $mh = curl_multi_init();
    if (!$mh) {
        return $results;
    }
    foreach ($group as $item) {
        // v3.33: $item può essere la webcam oppure il wrapper ['age','cam']
        // usato dalla coda stale del cron (bug v3.32: non unwrappato).
        $cam = is_array($item) && isset($item['cam']) ? $item['cam'] : $item;
        $url = (string) ($cam['url'] ?? '');
        if (!filter_var($url, FILTER_VALIDATE_URL)
            || !in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
            continue;
        }
        // v3.39: credenziali nell'URL -> CURLOPT_USERPWD + CURLAUTH_ANY
        list($fetchUrl, $userpwd) = mwp_split_auth($url);
        $ch = curl_init($fetchUrl);
        if (!$ch) {
            continue;
        }
        curl_setopt_array($ch, mwp_curl_opts($ua, mwp_referer_for($cam), false, false, $userpwd));
        curl_multi_add_handle($mh, $ch);
        $map[] = ['ch' => $ch, 'slug' => $cam['slug']];
    }

    if ($map) {
        $deadline = microtime(true) + max(1, (float) $maxWait);
        do {
            $status = curl_multi_exec($mh, $active);
            if ($active && $status === CURLM_OK) {
                // Breve attesa invece di loop stretto: il worker respira
                curl_multi_select($mh, 0.2);
            }
        } while ($active && $status === CURLM_OK && microtime(true) < $deadline);
    }

    foreach ($map as $m) {
        $ch = $m['ch'];
        $slug = $m['slug'];
        $data = curl_multi_getcontent($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $type = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $errno = curl_errno($ch);
        $typeOk = str_starts_with($type, 'image/')
            || $type === 'application/octet-stream'
            || $type === '';
        $ok = $data !== false && $code >= 200 && $code < 300 && $typeOk && strlen((string) $data) > 500;
        $results[$slug] = [
            'data'      => $ok ? $data : null,
            'errno'     => $errno,
            'code'      => $code,
            'type'      => $type,
            'error'     => (string) curl_error($ch),
            'ssl_error' => in_array($errno, [35, 51, 58, 60, 64, 66, 77, 82, 83, 84, 90, 91, 98], true),
        ];
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    curl_multi_close($mh);
    return $results;
}

/**
 * Garantisce che l'originale pieno sia in cache.
 * NON fa mai fetch in una richiesta utente se il file esiste già (anche stale):
 * il refresh fa capo al cron (v3.22). $allow_fetch serve solo al cold-start
 * (prima richiesta assoluta, prima dell'esecuzione del cron) e al cron stesso.
 * Ritorna il path del file pieno, oppure null.
 */
function mwp_ensure_full($cam, $allow_fetch = true) {
    $slug = $cam['slug'];
    $full = mwp_full_path($slug);
    $ttl  = max(30, (int) ($cam['ttl'] ?? 300));

    // Backoff: se un fetch è appena fallito, non ritentare per 60 secondi.
    // Senza, una webcam offline genera una richiesta remota per ogni visitatore
    // (o peggio, centinaia da un loop di immagini rotte).
    // v3.34: backoff progressivo (v. helper sopra)
    $recentFail = mwp_in_backoff($slug);

    $fresh = is_file($full) && (time() - filemtime($full)) < $ttl;

    if ($allow_fetch && !$fresh && !$recentFail) {
        $data = mwp_fetch($cam['url'], $cam['referer'] ?? '');
        if ($data !== null) {
            @file_put_contents($full, $data);
            // Valida il contenuto: alcuni server webcam rispondono 200
            // con una pagina HTML o un messaggio di errore al posto dell'immagine
            if (!@getimagesize($full)) {
                @unlink($full);
                mwp_mark_fail($slug);
            } else {
                mwp_clear_fail($slug);
                // v3.31: le varianti NON vengono più eliminate né rigenerate
                // dopo il fetch (cron leggero): le rigenera la prima
                // richiesta utile, confrontando i filemtime. Nessuno riceve
                // mai una variante più vecchia dell'originale nuovo.
            }
        } else {
            mwp_mark_fail($slug);
        }
    }

    if (is_file($full)) {
        return $full;
    }
    return null;
}

/**
 * Rigenera il full WebP e tutte le varianti più strette dell'originale.
 * Chiamato dal cron dopo ogni refresh riuscito (warm-up): così nessun
 * visitatore paga mai la conversione in richiesta.
 */
function mwp_warm_variants($slug) {
    $full = mwp_full_path($slug);
    if (!is_file($full)) {
        return;
    }
    $fw = mwp_fullwebp_path($slug);
    if (!is_file($fw) || filemtime($fw) < filemtime($full)) {
        mwp_make_variant($full, $fw, 0, false);
    }
    $info  = @getimagesize($full);
    $origW = $info ? (int) $info[0] : 0;
    foreach (mwp_sizes() as $w) {
        if ($origW && $w >= $origW) {
            break; // niente upscaling
        }
        $vp = mwp_variant_path($slug, $w);
        if (!is_file($vp) || filemtime($vp) < filemtime($full)) {
            mwp_make_variant($full, $vp, $w);
        }
    }
}

/**
 * Ritorna il content-type reale dell'immagine piena in cache,
 * qualunque sia il formato di origine (JPEG, PNG, GIF...).
 */
function mwp_full_mime($path) {
    $info = @getimagesize($path);
    return $info ? image_type_to_mime_type($info[2]) : 'image/jpeg';
}

/**
 * Genera la variante ridimensionata in WebP.
 * Usa l'editor immagini di WordPress (GD o Imagick), che supporta WebP da WP 5.8.
 * Non effettua upscaling: se l'originale è più stretto di $w, fallisce e
 * il chiamante servirà l'immagine piena.
 */
function mwp_make_variant($fullPath, $variantPath, $w, $resize = true) {
    if (!function_exists('wp_get_image_editor')) {
        return false;
    }
    $editor = wp_get_image_editor($fullPath);
    if (is_wp_error($editor)) {
        return false;
    }
    $size = $editor->get_size();
    if ($resize && (empty($size['width']) || $w >= $size['width'])) {
        return false; // niente upscaling
    }
    $editor->set_quality((int) apply_filters('mwp_webp_quality', 82));
    if ($resize) {
        $editor->resize((int) $w, 0, false);
    }
    $res = $editor->save($variantPath, 'image/webp');
    return !is_wp_error($res) && is_file($variantPath);
}

/* ============================================================
 * CRON DI REFRESH (v3.22, budget di tempo in v3.23)
 * Ogni minuto (o al primo traffico utile, se WP-Cron è lazy):
 *   - webcam con cache scaduta → fetch remoto (le più vecchie prima)
 *   - fetch riuscito → rigenerazione di full.webp + varianti (warm-up)
 *   - fetch fallito → marker di backoff; in richiesta si serve lo stale
 * Scala a centinaia di webcam: il run si ferma al numero di fetch del batch
 * (mwp_cron_batch) O allo scadere del budget di tempo (mwp_cron_time_budget),
 * qualunque dei due arrivi prima. Il resto lo prende il run successivo.
 * ============================================================ */

add_filter('cron_schedules', function ($schedules) {
    $schedules['mwp_minute'] = [
        'interval' => 60,
        'display'  => 'Meteo Webcam Proxy: ogni minuto',
    ];
    return $schedules;
});

// Auto-riparazione della schedulazione (es. dopo un reset delle opzioni
// cron o l'aggiornamento del plugin via FTP, senza riattivazione).
// Solo in admin: un check in più non deve pesare sul frontend.
add_action('init', function () {
    if (is_admin() && !wp_next_scheduled('mwp_cron_refresh')) {
        wp_schedule_event(time() + 60, 'mwp_minute', 'mwp_cron_refresh');
    }
});

add_action('mwp_cron_refresh', 'mwp_cron_refresh_webcams');

function mwp_cron_refresh_webcams() {
    // Su hosting condiviso (es. Tophost) un run non deve occupare un worker
    // PHP per minuti: limitiamo il numero di webcam per esecuzione...
    $batch = max(1, (int) apply_filters('mwp_cron_batch', 8));
    // ...il tempo complessivo del run...
    $budget = max(5, (int) apply_filters('mwp_cron_time_budget', 20));
    // ...e da 3.32 scarichiamo le webcam in PARALLELO (curl_multi): gruppi
    // di mwp_cron_parallel richieste in volo insieme (default 6). Con
    // centinaia di webcam, molte lente (timeout 10s/25s ciascuna), il ciclo
    // sequenziale sprecava il budget del run in attese una per volta.
    $parallel = max(1, (int) apply_filters('mwp_cron_parallel', 6));

    $stale = [];
    foreach (mwp_get_webcams() as $cam) {
        $slug = $cam['slug'];
        $full = mwp_full_path($slug);
        $ttl  = max(30, (int) ($cam['ttl'] ?? 300));

        // v3.34: backoff progressivo (v. helper sopra)
        $recentFail = mwp_in_backoff($slug);
        $fresh      = is_file($full) && (time() - filemtime($full)) < $ttl;

        if ($fresh || $recentFail) {
            continue;
        }
        $age = is_file($full) ? (time() - filemtime($full)) : PHP_INT_MAX;
        $stale[] = ['age' => $age, 'cam' => $cam];
    }

    usort($stale, function ($a, $b) {
        return $b['age'] <=> $a['age']; // più vecchia prima
    });
    $stale = array_slice($stale, 0, $batch);

    // Scrittura in cache + marker di backoff: unica funzione per tutti i percorsi
    $store = function ($cam, $data) {
        $slug = $cam['slug'];
        $full = mwp_full_path($slug);
        if ($data === null) {
            mwp_mark_fail($slug);
            return false;
        }
        @file_put_contents($full, $data);
        if (!@getimagesize($full)) {
            // 200 con contenuto non-immagine: scarta e marca il backoff,
            // in richiesta continuerà a essere servito lo stale precedente
            @unlink($full);
            mwp_mark_fail($slug);
            return false;
        }
        mwp_clear_fail($slug);
        // v3.31 (cron leggero): il cron scrive solo l'immagine piena; le
        // varianti le rigenera pigramente la prima richiesta utile.
        return true;
    };

    $start = microtime(true);
    $useMulti = function_exists('curl_multi_init');
    $retryQueue = [];

    foreach (array_chunk($stale, $parallel) as $group) {
        $elapsed = microtime(true) - $start;
        if ($elapsed > $budget) {
            break; // tempo scaduto: il resto al run successivo
        }

        if ($useMulti) {
            $results = mwp_curl_multi_fetch($group, $budget - $elapsed);
            foreach ($group as $item) {
                // v3.33 BUGFIX: il gruppo contiene wrapper ['age','cam'],
                // in v3.32 si usava il wrapper come se fosse la webcam.
                $cam = $item['cam'];
                $slug = $cam['slug'];
                $info = $results[$slug] ?? null;
                $data = is_array($info) ? $info['data'] : null;
                if ($data !== null) {
                    $store($cam, $data);
                } else {
                    // v3.37: errno 0 + http 0 = la richiesta non ha MAI
                    // completato (handle ancora in volo quando il budget
                    // del gruppo e' scaduto, tipicamente perche' le webcam
                    // in testa alla coda hanno bruciato il run). NON e' un
                    // fallimento della webcam: niente marker di backoff,
                    // resta in coda e il run successivo la serve con budget
                    // pieno. Questo rompe il ciclo per cui la webcam sana
                    // piu' vecchia (oldest_slug) restava affamata per
                    // sempre col backoff sincronizzato con le webcam morte.
                    $truncated = is_array($info)
                        && (int) ($info['errno'] ?? 0) === 0
                        && (int) ($info['code'] ?? 0) === 0;
                    if (!$truncated) {
                        mwp_mark_fail($slug);
                    }
                    $retryQueue[] = ['cam' => $cam, 'info' => $info];
                }
            }
        } else {
            // Hosting senza curl_multi: ciclo sequenziale di sempre
            foreach ($group as $item) {
                // v3.33 BUGFIX: unwrappa come nel ramo parallelo
                $cam = $item['cam'];
                if ((microtime(true) - $start) > $budget) {
                    break;
                }
                $store($cam, mwp_fetch($cam['url'], $cam['referer'] ?? ''));
            }
        }
    }

    // Fallback rari a fine run, solo con tempo residuo nel budget: certificato
    // self-signed (secondo tentativo senza verifica) e https morto in timeout
    // (ritento in http). mwp_fetch li gestisce gia' internamente (3.19/3.17).
    foreach ($retryQueue as $item) {
        if ((microtime(true) - $start) > $budget) {
            break;
        }
        $info = $item['info'];
        if (!is_array($info)) {
            continue; // URL non valido: nessun ritento puo' salvarla
        }
        $cam = $item['cam'];
        $errno = (int) ($info['errno'] ?? 0);
        $code = (int) ($info['code'] ?? 0);
        // v3.36: errno 0 + http 0 = la richiesta parallela non ha mai
        // completato (truncamento da budget o stall di curl_multi): non
        // condannare la webcam al backoff senza prima riprovare in
        // sequenziale, che e' il percorso con cui il pulsante Test
        // riesce (mwp_fetch) sulle stesse URL.
        // v3.38: anche gli errori di framing HTTP/2 (16/92): il retry
        // sequenziale in mwp_fetch forza HTTP/1.1 e quei server rispondono
        $worth = !empty($info['ssl_error'])
            || ($errno === 28 && strpos($cam['url'], 'https://') === 0)
            || in_array($errno, [16, 92], true)
            || ($errno === 0 && $code === 0);
        if (!$worth) {
            continue;
        }
        $store($cam, mwp_fetch($cam['url'], $cam['referer'] ?? ''));
    }
}

/* ============================================================
 * PROXY: /webcam/{slug}.jpg        → immagine piena in WebP
 *         /webcam/{slug}-full.webp → immagine piena in WebP (stesso contenuto)
 *         /webcam/{slug}-{w}.webp  → variante ridimensionata WebP
 * v3.22: in richiesta NON si fa mai fetch remoto se il file pieno esiste
 * (si serve lo stale); il fetch spetta al cron. L'unico fetch in richiesta
 * è il cold-start (file pieno mai scaricato).
 * ============================================================ */

add_action('init', function () {
    // Endpoint di stato per il monitoraggio esterno (v3.26).
    // Non usa nonce: i monitor tipo cron-job.org non sono autenticati WP,
    // quindi protegge una chiave segreta nell'URL (opzione mwp_status_key).
    // Nessun fetch, solo filemtime: costa pochissimo anche con molte webcam.
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
    if ($path !== '/webcam/mwp-status') {
        return;
    }
    $key = (string) get_option('mwp_status_key', '');
    $given = (string) ($_GET['key'] ?? '');
    if ($key === '' || !hash_equals($key, $given)) {
        status_header(403);
        exit('Chiave non valida');
    }

    $now    = time();
    $total  = 0;
    $fresh  = 0;
    $stale  = 0;
    $backoff = 0;
    $missing = 0;
    $oldestAge = 0;
    $oldestSlug = '';

    foreach (mwp_get_webcams() as $cam) {
        $total++;
        $slug = $cam['slug'];
        $ttl  = max(30, (int) ($cam['ttl'] ?? 300));
        $full = mwp_full_path($slug);
        if (!is_file($full)) {
            $missing++;
            continue;
        }
        $age = $now - (int) filemtime($full);
        if ($age > $oldestAge) {
            $oldestAge  = $age;
            $oldestSlug = $slug;
        }
        if ($age <= $ttl) {
            $fresh++;
        } else {
            $stale++;
        }
        if (mwp_in_backoff($slug)) {
            $backoff++;
        }
    }

    // Sano se: la maggioranza delle webcam è fresca E quasi tutte hanno
    // l'immagine in cache. Soglie volutamente lasche: un paio di webcam
    // lente non devono far scattare l'allerta a ogni ping.
    $unhealthy = ($stale > ceil($total / 2)) || ($missing > ceil(max($total, 10) / 10));
    status_header($unhealthy ? 503 : 200);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo wp_json_encode([
        'healthy'     => !$unhealthy,
        'total'       => $total,
        'fresh'       => $fresh,
        'stale'       => $stale,
        'backoff'     => $backoff,
        'missing'     => $missing,
        'oldest_slug' => $oldestSlug,
        'oldest_age'  => $oldestAge,
        'checked_at'  => gmdate('c'),
    ]);
    exit;
});

add_action('init', function () {
    // Solo il path, SENZA query string: il lightbox e il refresh
    // aggiungono ?ts=... alla URL e la richiesta non deve andare in 404
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
    $slug = null;
    $variant = null;
    $fullwebp = false;

    // v3.28: URL senza estensione (nginx li tratta come richieste dinamiche,
    // niente doppia Cache-Control). I vecchi percorsi restano validi.
    if (preg_match('#^/webcam/([a-z0-9\-]+)$#', $path, $m)) {
        $slug = $m[1];
    } elseif (preg_match('#^/webcam/([a-z0-9\-]+)/full$#', $path, $m)) {
        $slug = $m[1];
        $fullwebp = true;
    } elseif (preg_match('#^/webcam/([a-z0-9\-]+)/(\d+)$#', $path, $m)) {
        $slug    = $m[1];
        $variant = (int) $m[2];
        if (!in_array($variant, mwp_sizes(), true)) {
            http_response_code(404);
            exit('Variante non valida');
        }
    } elseif (preg_match('#^/webcam/([a-z0-9\-]+)\.jpg$#', $path, $m)) {
        $slug = $m[1];
    } elseif (preg_match('#^/webcam/([a-z0-9\-]+)-full\.webp$#', $path, $m)) {
        $slug = $m[1];
        $fullwebp = true;
    } elseif (preg_match('#^/webcam/([a-z0-9\-]+)-(\d+)\.webp$#', $path, $m)) {
        $slug    = $m[1];
        $variant = (int) $m[2];
        if (!in_array($variant, mwp_sizes(), true)) {
            http_response_code(404);
            exit('Variante non valida');
        }
    } else {
        return;
    }

    $cam = mwp_get_webcam($slug);
    if (!$cam) {
        http_response_code(404);
        exit('Webcam sconosciuta');
    }
    $ttl = max(30, (int) ($cam['ttl'] ?? 300));

    // Anteprime della pagina admin: nessun fetch remoto (vedi changelog 3.13).
    // Serve solo cio' che e' gia' in cache, anche se non piu' fresh.
    $preview = isset($_GET['mwp_preview']);

    // v3.22: fetch in richiesta SOLO in cold-start (file pieno assente).
    // Con il file presente si serve subito, anche se stale: il cron
    // aggiornerà la cache in background senza bloccare nessun visitatore.
    $coldStart = !$preview && !is_file(mwp_full_path($slug));
    $full = mwp_ensure_full($cam, $coldStart);
    if (!$full) {
        // v3.41: prima 503 + placeholder onerror lato client; ora il proxy
        // risponde sempre con un'immagine: la cortesia (cache assente)
        mwp_serve_courtesy();
    }

    // v3.41: webcam offline da troppo tempo (stantia oltre mwp_stale_max_age,
    // default 24h): l'ultima istantanea e' fuorviante, serviamo la cortesia.
    // Le anteprime admin continuano a vedere la cache reale (diagnosi col Test).
    if (!$preview && mwp_is_stale_beyond_courtesy($slug)) {
        mwp_serve_courtesy();
    }

    // stale-while-revalidate: il browser può riusare la copia scaduta
    // mentre scarica la nuova, senza mostrare buchi né attendere
    $swr = (int) apply_filters('mwp_stale_while_revalidate', 300);
    header('Cache-Control: public, max-age=' . $ttl . ', stale-while-revalidate=' . $swr);
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($full)) . ' GMT');

    // Immagine piena: SEMPRE in WebP, sia da /{slug}.jpg che da /{slug}-full.webp
    // (l'originale JPEG scaricato resta solo come sorgente interna in cache)
    if ($variant === null || $fullwebp) {
        $fwFile = mwp_fullwebp_path($slug);
        if (!is_file($fwFile) || filemtime($fwFile) < filemtime($full)) {
            if (!mwp_make_variant($full, $fwFile, 0, false)) {
                // Unica eccezione: WebP non generabile (GD senza supporto),
                // servi l'originale non ricompresso
                header('Content-Type: ' . mwp_full_mime($full));
                readfile($full);
                exit;
            }
        }
        header('Content-Type: image/webp');
        readfile($fwFile);
        exit;
    }

    // Variante ridimensionata WebP, rigenerata se più vecchia dell'originale
    $variantFile = mwp_variant_path($slug, $variant);
    if (!is_file($variantFile) || filemtime($variantFile) < filemtime($full)) {
        if (!mwp_make_variant($full, $variantFile, $variant)) {
            // WebP non generabile ( GD senza supporto, o upscaling ):
            // servi l'immagine piena, il browser la ridimensiona
            header('Content-Type: ' . mwp_full_mime($full));
            readfile($full);
            exit;
        }
    }

    header('Content-Type: image/webp');
    readfile($variantFile);
    exit;
});

/* ============================================================
 * SHORTCODE
 * [mwp_webcam slug="calata-genova" refresh="300"]
 *
 * - <img> con srcset/sizes: il browser scarica la variante adatta alla pagina
 * - width/height espliciti dall'originale in cache → nessun CLS (v3.22)
 * - onerror → placeholder incorporato, senza salto di layout (v3.22)
 * - click sull'immagine → lightbox con l'immagine a dimensione piena
 * ============================================================ */

add_shortcode('mwp_webcam', function ($atts) {
    $a = shortcode_atts([
        'slug'    => '',
        'refresh' => 0,
    ], $atts, 'mwp_webcam');

    $cam = mwp_get_webcam($a['slug']);
    if (!$cam) {
        return '<!-- mwp_webcam: slug sconosciuto -->';
    }

    $slug  = $cam['slug'];
    $full  = mwp_full_path($slug);

    // Assicuriamoci di avere l'originale per conoscerne la larghezza
    // (fetch solo se non è mai stato scaricato: cold-start)
    if (!is_file($full)) {
        $fetched = mwp_ensure_full($cam, true);
        if (!$fetched) {
            // v3.41: prima si restituiva solo un commento HTML invisibile
            // (un buco nella pagina). Ora l'<img> viene emesso lo stesso e
            // il proxy serve l'immagine di cortesia: la webcam e' gia' in
            // backoff dopo il cold-start fallito, quindi nessun fetch
            // remoto extra per i visitatori successivi.
        }
    }

    $origW = 0;
    $origH = 0;
    $info  = @getimagesize($full);
    if ($info) {
        $origW = $info[0];
        $origH = $info[1];
    }

    // Attributi dimensionali: il browser riserva lo spazio giusto
    // prima del download → zero layout shift
    $dims = ($origW && $origH) ? ' width="' . (int) $origW . '" height="' . (int) $origH . '"' : '';

    // v3.29: versione = mtime dell'originale in cache. Il server aggiunge
    // una Cache-Control di un anno su tutte le immagini (policy hosting):
    // con l'URL versionato, quando il cron aggiorna la webcam la pagina
    // emette un URL nuovo e il browser non può servire la copia vecchia.
    $v = is_file($full) ? (int) filemtime($full) : time();

    // v3.41: webcam in cortesia (stantia oltre la soglia): versione "off".
    // Su quella URL il proxy serve l'immagine di cortesia; senza questo
    // cambio di versione il browser continuerebbe a mostrare dalla SUA
    // cache l'ultima istantanea ricevuta quando la webcam era viva.
    // Quando la webcam torna, il fetch riuscito cambia l'mtime → ?v= nuova.
    if (mwp_is_stale_beyond_courtesy($slug)) {
        $v = 'off' . $v;
    }

    // Costruisci srcset solo con varianti più strette dell'originale
    $srcset = [];
    foreach (mwp_sizes() as $w) {
        if ($origW && $w >= $origW) {
            break; // non serve upscaling: ci fermiamo alla larghezza utile
        }
        $srcset[] = "/webcam/{$slug}/{$w}?v={$v} {$w}w";
    }

    $id = 'mwp-' . $slug . '-' . wp_unique_id();
    $sizes = esc_attr(apply_filters('mwp_sizes_attr', '(max-width: 768px) 100vw, 1024px'));

    // Placeholder se la webcam è offline al momento del caricamento
    $onerror = ' onerror="mwpErr(this)"';

    if ($srcset) {
        $first = explode(' ', $srcset[0])[0];
        $img = '<img id="' . esc_attr($id) . '"'
            . ' src="' . esc_url($first) . '"'
            . ' srcset="' . esc_attr(implode(', ', $srcset)) . '"'
            . ' sizes="' . $sizes . '"'
            . ' alt="Webcam ' . esc_attr($cam['name']) . '"'
            . $dims
            . ' loading="lazy" decoding="async"'
            . $onerror
            . ' style="width:100%;height:auto">';
    } else {
        // Originale piccolo o WebP non disponibile: immagine piena diretta
        $img = '<img id="' . esc_attr($id) . '" src="/webcam/' . esc_attr($slug) . '?v=' . esc_attr((string) $v) . '"'
            . ' alt="Webcam ' . esc_attr($cam['name']) . '"'
            . $dims
            . ' loading="lazy" decoding="async"'
            . $onerror
            . ' style="width:100%;height:auto">';
    }

    $html  = '<figure class="mwp-webcam">';
    $html .= '<a href="/webcam/' . esc_attr($slug) . '/full" class="mwp-lightbox" target="_blank" rel="noopener" title="Apri a dimensione piena">' . $img . '</a>';

    // v3.40 (recuperata in v3.41): nota standard sotto l'immagine. Testo
    // statico nel DOM: utile al lettore e leggibile dai crawler/agenti AI,
    // che altrimenti non sanno che l'immagine e' una livecam aggiornata.
    // Filtro 'mwp_refresh_note' (riceve $cam): stringa vuota = disattivata.
    $note = apply_filters('mwp_refresh_note', 'Webcam in tempo reale: l\'immagine si aggiorna automaticamente.', $cam);
    if (is_string($note) && $note !== '') {
        $html .= '<div class="mwp-refresh-note" style="font-size:12.5px;color:#6c757d;margin:6px 0 0">' . esc_html($note) . '</div>';
    }

    if (!empty($cam['credit'])) {
        $html .= '<figcaption>Foto: ' . esc_html($cam['credit']) . '</figcaption>';
    }
    $html .= '</figure>';

    if ((int) $a['refresh'] > 0) {
        $sec = (int) $a['refresh'];
        // v3.22: il cache-busting va applicato anche a srcset, altrimenti
        // il browser continua a usare la variante in cache senza ?ts
        $html .= "<script>(function(){var i=document.getElementById('" . esc_js($id) . "');"
            . "var b=i.getAttribute('src').split('?')[0];"
            . "var s=i.getAttribute('srcset');"
            . "setInterval(function(){var t='?ts='+Date.now();i.src=b+t;"
            . "if(s){i.srcset=s.replace(/([^ ,]+)( \\d+w)/g,function(m,u,w){return u+t+w;});} "
            . "}," . $sec * 1000 . ");})();</script>";
    }

    // Lightbox + CSS + helper del placeholder, stampati una sola volta per pagina
    static $assets = false;
    if (!$assets) {
        $assets = true;
        $html .= mwp_lightbox_assets();
    }

    return $html;
});

function mwp_lightbox_assets() {
