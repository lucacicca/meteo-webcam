<?php
/**
 * Plugin Name: Meteo Webcam Proxy
 * Description: Proxya su meteo-webcam.it le webcam che bloccano l'hotlinking.
 *              Varianti responsive in WebP, click per aprire l'immagine a dimensione piena.
 *              Webcam gestite da una pagina di amministrazione in wp-admin.
 * Version: 3.32
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
function mwp_curl_opts($ua, $referer, $insecure) {
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
    // HTTP/2 solo se libcurl lo supporta (altrimenti la richiesta fallirebbe)
    $ver = function_exists('curl_version') ? curl_version() : [];
    if (defined('CURL_HTTP_VERSION_2TLS') && !empty($ver['features']) && ($ver['features'] & CURL_VERSION_HTTP2)) {
        $opts[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_2TLS;
    }
    // Preferisci IPv4: su molti shared hosting la risoluzione IPv6 di certi
    // server resta appesa fino allo scatto del timeout (errno 28 senza byte)
    if (defined('CURL_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
        $opts[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
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
function mwp_curl_get($url, $ua, $referer, $insecure) {
    $ch = curl_init($url);
    curl_setopt_array($ch, mwp_curl_opts($ua, $referer, $insecure));
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
    foreach ($group as $cam) {
        $url = (string) ($cam['url'] ?? '');
        if (!filter_var($url, FILTER_VALIDATE_URL)
            || !in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
            continue;
        }
        $ch = curl_init($url);
        if (!$ch) {
            continue;
        }
        curl_setopt_array($ch, mwp_curl_opts($ua, mwp_referer_for($cam), false));
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
    $failFile   = mwp_cache_dir() . '/' . $slug . '-fail.marker';
    $backoffSec = (int) apply_filters('mwp_backoff_seconds', 60);
    $recentFail = is_file($failFile) && (time() - filemtime($failFile)) < $backoffSec;

    $fresh = is_file($full) && (time() - filemtime($full)) < $ttl;

    if ($allow_fetch && !$fresh && !$recentFail) {
        $data = mwp_fetch($cam['url'], $cam['referer'] ?? '');
        if ($data !== null) {
            @file_put_contents($full, $data);
            // Valida il contenuto: alcuni server webcam rispondono 200
            // con una pagina HTML o un messaggio di errore al posto dell'immagine
            if (!@getimagesize($full)) {
                @unlink($full);
                @touch($failFile);
            } else {
                @unlink($failFile);
                // v3.31: le varianti NON vengono più eliminate né rigenerate
                // dopo il fetch (cron leggero): le rigenera la prima
                // richiesta utile, confrontando i filemtime. Nessuno riceve
                // mai una variante più vecchia dell'originale nuovo.
            }
        } else {
            @touch($failFile);
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

        $failFile   = mwp_cache_dir() . '/' . $slug . '-fail.marker';
        $backoffSec = (int) apply_filters('mwp_backoff_seconds', 60);
        $recentFail = is_file($failFile) && (time() - filemtime($failFile)) < $backoffSec;
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
            @touch(mwp_cache_dir() . '/' . $slug . '-fail.marker');
            return false;
        }
        @file_put_contents($full, $data);
        if (!@getimagesize($full)) {
            // 200 con contenuto non-immagine: scarta e marca il backoff,
            // in richiesta continuerà a essere servito lo stale precedente
            @unlink($full);
            @touch(mwp_cache_dir() . '/' . $slug . '-fail.marker');
            return false;
        }
        @unlink(mwp_cache_dir() . '/' . $slug . '-fail.marker');
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
            foreach ($group as $cam) {
                $info = $results[$cam['slug']] ?? null;
                $data = is_array($info) ? $info['data'] : null;
                if ($data !== null) {
                    $store($cam, $data);
                } else {
                    // Fallita (o rimasta in volo quando il budget e' scaduto):
                    // backoff + coda per i fallback rari di fine run
                    @touch(mwp_cache_dir() . '/' . $cam['slug'] . '-fail.marker');
                    $retryQueue[] = ['cam' => $cam, 'info' => $info];
                }
            }
        } else {
            // Hosting senza curl_multi: ciclo sequenziale di sempre
            foreach ($group as $cam) {
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
        $worth = !empty($info['ssl_error'])
            || ($errno === 28 && strpos($cam['url'], 'https://') === 0);
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
        $failFile = mwp_cache_dir() . '/' . $slug . '-fail.marker';
        if (is_file($failFile) && ($now - (int) filemtime($failFile)) < 60) {
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
        http_response_code(503);
        header('Retry-After: 60');
        exit;
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
            return '<!-- mwp_webcam: immagine remota non raggiungibile -->';
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
        $img = '<img id="' . esc_attr($id) . '" src="/webcam/' . esc_attr($slug) . '?v=' . (int) $v . '"'
            . ' alt="Webcam ' . esc_attr($cam['name']) . '"'
            . $dims
            . ' loading="lazy" decoding="async"'
            . $onerror
            . ' style="width:100%;height:auto">';
    }

    $html  = '<figure class="mwp-webcam">';
    $html .= '<a href="/webcam/' . esc_attr($slug) . '/full" class="mwp-lightbox" target="_blank" rel="noopener" title="Apri a dimensione piena">' . $img . '</a>';
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
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 360">'
        . '<rect width="640" height="360" fill="#e9ecef"/>'
        . '<circle cx="320" cy="145" r="46" fill="none" stroke="#adb5bd" stroke-width="10"/>'
        . '<circle cx="320" cy="145" r="16" fill="#adb5bd"/>'
        . '<text x="320" y="245" font-family="sans-serif" font-size="26" fill="#6c757d" text-anchor="middle">Webcam non raggiungibile</text>'
        . '</svg>';
    $placeholderJs = 'data:image/svg+xml,' . rawurlencode($svg);
    return <<<HTML
<style>
.mwp-lightbox{display:block;cursor:zoom-in}
.mwp-overlay{position:fixed;inset:0;background:rgba(0,0,0,.88);display:flex;align-items:center;justify-content:center;z-index:99999;cursor:zoom-out;padding:2vw}
.mwp-overlay img{max-width:96vw;max-height:96vh;width:auto;height:auto;box-shadow:0 4px 32px rgba(0,0,0,.5)}
</style>
<script>
// Placeholder per webcam offline: sostituisce l'src ma NON tocca
// width/height, quindi nessun layout shift quando scatta l'errore
window.mwpErr = function (img) {
  img.onerror = null;
  img.src = '{$placeholderJs}';
};
document.addEventListener('DOMContentLoaded', function () {
  document.addEventListener('click', function (e) {
    var a = e.target.closest('a.mwp-lightbox');
    if (a && !e.target.closest('.mwp-overlay')) {
      e.preventDefault();
      var ov = document.createElement('div');
      ov.className = 'mwp-overlay';
      var img = document.createElement('img');
      img.src = a.href + (a.href.indexOf('?') > -1 ? '&' : '?') + 'ts=' + Date.now();
      img.alt = a.title || '';
      ov.appendChild(img);
      document.body.appendChild(ov);
      return;
    }
    var ov = document.querySelector('.mwp-overlay');
    if (ov && e.target === ov) {
      ov.remove();
    }
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      var ov = document.querySelector('.mwp-overlay');
      if (ov) ov.remove();
    }
  });
});
</script>
HTML;
}

/**
 * Placeholder SVG incorporato, mostrato quando una webcam non è raggiungibile.
 * onerror=null è essenziale: senza, un placeholder mancante genererebbe
 * un nuovo errore e quindi un loop infinito di richieste.
 */
function mwp_placeholder_uri() {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 360">'
        . '<rect width="640" height="360" fill="#e9ecef"/>'
        . '<circle cx="320" cy="145" r="46" fill="none" stroke="#adb5bd" stroke-width="10"/>'
        . '<circle cx="320" cy="145" r="16" fill="#adb5bd"/>'
        . '<text x="320" y="245" font-family="sans-serif" font-size="26" fill="#6c757d" text-anchor="middle">Webcam non raggiungibile</text>'
        . '</svg>';
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

/* ============================================================
 * PAGINA DI AMMINISTRAZIONE
 * Menu: Webcam → Tutte le webcam
 * ============================================================ */

add_action('admin_menu', function () {
    // Icona webcam custom (SVG incorporato, nessun file extra da caricare)
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g fill="#a7aaad">'
        . '<path d="M12 2a7 7 0 0 0-7 7 7 7 0 0 0 7 7 7 7 0 0 0 7-7 7 7 0 0 0-7-7zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm0-6.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5z"/>'
        . '<rect x="10.5" y="15" width="3" height="4"/><rect x="7" y="19" width="10" height="2" rx="1"/>'
        . '</g></svg>';
    $icon = 'data:image/svg+xml;base64,' . base64_encode($svg);

    add_menu_page(
        'Webcam',
        'Webcam',
        'manage_options',
        'mwp-webcams',
        'mwp_admin_page',
        $icon,
        58
    );
});

add_action('admin_post_mwp_save_webcams', 'mwp_handle_save');

/**
 * Normalizza una singola webcam da input (form admin o riga CSV).
 * Stesse regole del salvataggio: slug auto dal nome, tetti di lunghezza,
 * URL http(s) obbligatorio, referer auto/none/URL.
 * Ritorna l'array webcam o null (e appende l'etichetta a $skipped).
 * (v3.23: fattorizzata dal save handler per riusarla nell'import CSV.)
 */
function mwp_normalize_cam($row, &$skipped) {
    $slug = sanitize_key($row['slug'] ?? '');
    $name = sanitize_text_field($row['name'] ?? '');
    $url  = esc_url_raw($row['url'] ?? '');

    if ($slug === '' && $name !== '') {
        $slug = sanitize_title($name);
    }

    if ($slug === '' && $url === '') {
        return null; // riga vuota: ignora senza segnalare
    }

    $label = $name !== '' ? $name : ($slug !== '' ? $slug : '(riga senza nome)');

    $url = substr($url, 0, 2048);
    if (strlen((string) ($row['url'] ?? '')) > 2048) {
        $skipped[] = $label . ' (URL troppo lungo, oltre 2048 caratteri)';
        return null;
    }
    if ($url === '' || !preg_match('#^https?://#', $url) || $slug === '') {
        $skipped[] = $label;
        return null;
    }

    // Referer personalizzato: vuoto (auto), 'none', oppure URL del sito
    // che incorpora la webcam (per i server che filtrano il referer).
    // v3.27: normalizzazione difensiva — l'input non deve mai finire
    // concatenato al valore precedente o salvato senza schema/validità.
    $refRaw = trim((string) ($row['referer'] ?? ''));
    // Prefissi spezzati ("//host", "://host"): via, e ricostruisci
    $refRaw = preg_replace('#^(\s*:?//)#', '', $refRaw);
    if ($refRaw === '') {
        $referer = '';
    } elseif (strtolower($refRaw) === 'none') {
        $referer = 'none';
    } elseif (in_array(strtolower($refRaw), ['auto', 'host'], true)) {
        $referer = '';
    } else {
        if (!preg_match('#^https?://#i', $refRaw)) {
            $refRaw = 'https://' . $refRaw;
        }
        // Niente host valido (es. residuo di una concatenazione): auto
        $referer = parse_url($refRaw, PHP_URL_HOST) ? esc_url_raw($refRaw) : '';
    }

    return [
        'slug'   => $slug,
        'name'   => mb_substr($name !== '' ? $name : $slug, 0, 200),
        'url'    => $url,
        'credit' => mb_substr(sanitize_text_field($row['credit'] ?? ''), 0, 200),
        'ttl'    => max(30, (int) ($row['ttl'] ?? 300)),
        'referer' => mb_substr($referer, 0, 2048),
    ];
}

/* Pulsante "Test" nella pagina admin: fetch immediato lato server con
 * esito leggibile, per diagnosticare le webcam senza leggere i log. */
add_action('wp_ajax_mwp_test_webcam', function () {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permessi insufficienti.', 403);
    }
    check_ajax_referer('mwp_admin', 'nonce');

    $slug = sanitize_key($_POST['slug'] ?? '');
    $cam = mwp_get_webcam($slug);
    if (!$cam) {
        wp_send_json_error('Webcam sconosciuta.');
    }

    $t0 = microtime(true);
    $data = mwp_fetch($cam['url'], $cam['referer'] ?? '');
    $ms = (int) round((microtime(true) - $t0) * 1000);

    if ($data === null) {
        wp_send_json_success([
            'ok'   => false,
            'ms'   => $ms,
            'info' => $GLOBALS['mwp_last_fetch'] ?? [],
        ]);
    }

    $tmp  = tempnam(get_temp_dir(), 'mwp-test-');
    @file_put_contents($tmp, $data);
    $size = @getimagesize($tmp);
    @unlink($tmp);

    wp_send_json_success([
        'ok'     => (bool) $size,
        'ms'     => $ms,
        'bytes'  => strlen($data),
        'width'  => $size[0] ?? null,
        'height' => $size[1] ?? null,
    ]);
});

/* ============================================================
 * EXPORT CSV (v3.23)
 * Scarica la configurazione completa: nome,url,referer,ttl,credito,slug
 * Ri-importabile tale e quale (l'import riconosce l'header).
 * ============================================================ */

add_action('admin_post_mwp_export_csv', function () {
    if (!current_user_can('manage_options')) {
        wp_die('Permessi insufficienti.');
    }
    check_admin_referer('mwp_export_csv');

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="meteo-webcam-webcams-' . gmdate('Ymd-Hi') . '.csv"');
    header('Cache-Control: no-store');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['name', 'url', 'referer', 'ttl', 'credit', 'slug']);
    foreach (mwp_get_webcams() as $cam) {
        fputcsv($out, [
            $cam['name'],
            $cam['url'],
            $cam['referer'] ?? '',
            (int) ($cam['ttl'] ?? 300),
            $cam['credit'] ?? '',
            $cam['slug'],
        ]);
    }
    fclose($out);
    exit;
});

/* ============================================================
 * IMPORT CSV (v3.23)
 * Colonne: name,url,referer,ttl,credit[,slug]  (header opzionale ma
 * consigliato: se la prima riga contiene "url" viene saltata).
 * Unione NON distruttiva: le webcam con slug esistente restano intatte,
 * le nuove vengono aggiunte, le righe non valide scartate e conteggiate.
 * ============================================================ */

add_action('admin_post_mwp_import_csv', function () {
    if (!current_user_can('manage_options')) {
        wp_die('Permessi insufficienti.');
    }
    check_admin_referer('mwp_import_csv');

    $notice = function ($msg) {
        set_transient('mwp_admin_notice_' . get_current_user_id(), $msg, 300);
    };

    $file = $_FILES['mwp_csv'] ?? null;
    if (!$file || ($file['error'] ?? 1) !== UPLOAD_ERR_OK) {
        $notice('Nessun file CSV ricevuto (errore di upload: ' . ($file['error'] ?? 'nessun file') . ').');
        wp_safe_redirect(admin_url('admin.php?page=mwp-webcams&mwp_import=1'));
        exit;
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        $notice('File CSV troppo grande (massimo 2 MB).');
        wp_safe_redirect(admin_url('admin.php?page=mwp-webcams&mwp_import=1'));
        exit;
    }

    $fh = @fopen($file['tmp_name'], 'r');
    if (!$fh) {
        $notice('Impossibile leggere il file CSV caricato.');
        wp_safe_redirect(admin_url('admin.php?page=mwp-webcams&mwp_import=1'));
        exit;
    }

    // v3.24: auto-rilevamento del separatore dalla prima riga.
    // Excel in italiano salva i CSV con ';' (virgola usata come separatore
    // decimale): con la 3.23 quelle righe risultavano un unico campo e
    // finivano tutte tra gli scarti. Conteggi su ';' ',' e tab, vince il più
    // frequente; la prima riga è quella di header (o comunque di nomi/URL).
    $firstLine = (string) fgets($fh);
    rewind($fh);
    $sepCounts = [
        ';'   => substr_count($firstLine, ';'),
        ','   => substr_count($firstLine, ','),
        "\t"  => substr_count($firstLine, "\t"),
    ];
    arsort($sepCounts);
    $sep = (string) array_key_first($sepCounts);
    if ($sepCounts[$sep] === 0) {
        $sep = ',';
    }

    $existing = mwp_get_webcams();
    $bySlug = [];
    foreach ($existing as $c) {
        $bySlug[$c['slug']] = true;
    }

    // v3.27: modalità aggiornamento — se la casella è spuntata, le righe con
    // slug esistente aggiornano la webcam invece di essere ignorate
    $updateMode = !empty($_POST['mwp_import_update']);

    $added = [];
    $skipped = [];
    $duplicates = 0;
    $updated = 0;
    $line = 0;

    while (($row = fgetcsv($fh, 0, $sep)) !== false) {
        $line++;
        if ($line > 2001) { // tetto: 2000 webcam per import
            $skipped[] = '(import fermato a 2000 righe)';
            break;
        }
        // BOM UTF-8 all'inizio del primo campo (Excel / Notepad): via
        if ($line === 1 && isset($row[0])) {
            $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $row[0]);
        }
        // Riga vuota
        if (count(array_filter($row, function ($v) { return trim((string) $v) !== ''; })) === 0) {
            continue;
        }
        // Header opzionale: la prima riga con "url" tra i valori viene saltata
        $lower = array_map(function ($v) { return strtolower(trim((string) $v)); }, $row);
        if ($line === 1 && in_array('url', $lower, true)) {
            continue;
        }

        // Mappa colonne: name,url,referer,ttl,credit,slug
        $data = [
            'name'    => $row[0] ?? '',
            'url'     => $row[1] ?? '',
            'referer' => $row[2] ?? '',
            'ttl'     => $row[3] ?? '',
            'credit'  => $row[4] ?? '',
            'slug'    => $row[5] ?? '',
        ];

        $cam = mwp_normalize_cam($data, $skipped);
        if ($cam === null) {
            continue; // riga vuota o scartata (già segnalata in $skipped)
        }
        if (isset($bySlug[$cam['slug']])) {
            if ($updateMode) {
                // Aggiorna la webcam esistente; se cambia URL o referer,
                // azzera la sua cache (stessa regola del salvataggio manuale)
                foreach ($existing as $idx => $old) {
                    if ($old['slug'] === $cam['slug']) {
                        if (($old['url'] ?? '') !== $cam['url']
                            || ($old['referer'] ?? '') !== $cam['referer']) {
                            foreach (glob(mwp_cache_dir() . '/' . $cam['slug'] . '-*') as $f) {
                                @unlink($f);
                            }
                        }
                        // 3.30: conserva la data di inserimento originale
                        $cam['added'] = (int) ($old['added'] ?? 0);
                        $existing[$idx] = $cam;
                        break;
                    }
                }
                $updated++;
            } else {
                $duplicates++;
            }
            continue; // esistente: aggiunta già gestita (o lasciata intatta)
        }
        $bySlug[$cam['slug']] = true;
        $cam['added'] = time(); // 3.30: data di inserimento per l'ordinamento
        $added[] = $cam;
    }
    fclose($fh);
    @unlink($file['tmp_name']);

    if ($added) {
        $cams = array_merge($existing, $added);
        if (!update_option('mwp_webcams', $cams, false)) {
            $after = get_option('mwp_webcams');
            if (!(is_array($after) && count($after) === count($cams))) {
                $notice('Import NON riuscito: il database ha rifiutato la scrittura. Nessuna webcam è stata modificata.');
                wp_safe_redirect(admin_url('admin.php?page=mwp-webcams&mwp_import=1'));
                exit;
            }
        }
    }

    $parts = ['Importate ' . count($added) . ' nuove webcam.'];
    if ($updated) {
        $parts[] = 'Aggiornate ' . $updated . ' webcam esistenti.';
    }
    if ($duplicates) {
        $parts[] = $duplicates . ' già esistenti lasciate intatte.';
    }
    if ($skipped) {
        $parts[] = 'Scartate ' . count($skipped) . ' righe: ' . implode(', ', array_map('esc_html', array_slice($skipped, 0, 10)));
    }
    $notice(implode(' ', $parts));
    wp_safe_redirect(admin_url('admin.php?page=mwp-webcams&updated=1&saved=' . (count($existing) + count($added)) . '&mwp_import=1'));
    exit;
});

function mwp_handle_save() {
    if (!current_user_can('manage_options')) {
        wp_die('Permessi insufficienti.');
    }
    check_admin_referer('mwp_save_webcams');

    // POST troncato da max_input_vars? Il canarino (ultimo campo del form)
    // sarebbe stato tagliato: bloccando il save evitiamo di salvare una lista
    // parziale e di cancellare la cache delle webcam "rimosse" per sbaglio.
    if (($_POST['mwp_canary'] ?? '') !== 'ok') {
        $maxVars = (int) (ini_get('max_input_vars') ?: 1000);
        set_transient(
            'mwp_admin_notice_' . get_current_user_id(),
            'Il POST e\' arrivato troncato: probabilmente max_input_vars (' . $maxVars . ') e\' troppo basso per tante webcam. Salvataggio ANNULLATO per sicurezza: chiedi all\'hosting di aumentarlo.',
            300
        );
        wp_safe_redirect(admin_url('admin.php?page=mwp-webcams'));
        exit;
    }

    // v3.30: il form mostra una sola pagina di webcam: il POST contiene
    // solo le righe visibili. Il salvataggio è un "merge":
    //   - righe elencate in mwp_page_slugs[] → aggiornate dai dati POST
    //   - webcam eliminate con "×" (mwp_delete_slugs[]) → rimosse, cache pulita
    //   - tutte le altre webcam (altre pagine) → restano intatte
    // Prima della 3.30 il form postava TUTTE le webcam: con la paginazione
    // quel modello avrebbe cancellato le pagine non visualizzate.
    $raw         = $_POST['mwp_webcams'] ?? [];
    $pageKeys    = array_map('sanitize_text_field', (array) ($_POST['mwp_page_slugs'] ?? []));
    $deleteSlugs = array_map('sanitize_key', (array) ($_POST['mwp_delete_slugs'] ?? []));
    $skipped     = [];
    $posted      = []; // slug => cam normalizzata, solo righe della pagina

    foreach ($pageKeys as $key) {
        if (!isset($raw[$key]) || !is_array($raw[$key])) {
            continue;
        }
        $cam = mwp_normalize_cam($raw[$key], $skipped);
        if ($cam !== null) {
            $posted[$cam['slug']] = $cam;
        }
    }

    $existing = mwp_get_webcams();
    $cams     = [];

    foreach ($existing as $old) {
        if (in_array($old['slug'], $deleteSlugs, true)) {
            // Rimossa con "×": pulisci la sua cache
            foreach (glob(mwp_cache_dir() . '/' . $old['slug'] . '-*') as $f) {
                @unlink($f);
            }
            continue;
        }
        if (isset($posted[$old['slug']])) {
            $new = $posted[$old['slug']];
            // Se l'URL o il referer di una webcam è cambiato, azzera la sua
            // cache: altrimenti continuerebbe a servire la vecchia immagine
            if (($old['url'] ?? '') !== $new['url']
                || ($old['referer'] ?? '') !== $new['referer']) {
                foreach (glob(mwp_cache_dir() . '/' . $old['slug'] . '-*') as $f) {
                    @unlink($f);
                }
            }
            // 3.30: conserva la data di inserimento originale
            $new['added'] = (int) ($old['added'] ?? 0);
            $cams[] = $new;
            unset($posted[$old['slug']]);
        } else {
            // Webcam di un'altra pagina (o non modificata): intatta
            $cams[] = $old;
        }
    }

    // Nuove webcam (riga "+ Aggiungi webcam" o slug inedito): data di adesso
    $seen = [];
    foreach ($cams as $c) {
        $seen[$c['slug']] = true;
    }
    foreach ($posted as $new) {
        if (isset($seen[$new['slug']])) {
            $skipped[] = $new['name'] . ' (slug duplicato: esiste già su un\'altra pagina)';
            continue;
        }
        $new['added'] = time(); // 3.30: data di inserimento
        $cams[] = $new;
    }
    $cams = array_values($cams);

    $mwp_res = update_option('mwp_webcams', $cams, false);

    // Esito reale della scrittura: senza questo controllo l'admin poteva
    // vedere "salvate 41 webcam" mentre MySQL aveva rifiutato la scrittura
    if (!$mwp_res) {
        $mwp_after = get_option('mwp_webcams');
        if (!(is_array($mwp_after) && count($mwp_after) === count($cams))) {
            set_transient(
                'mwp_admin_notice_' . get_current_user_id(),
                'Salvataggio NON riuscito: il database ha rifiutato la scrittura (probabile campo troppo grande o limite MySQL dell\'hosting). Controlla i campi URL e riprova.',
                300
            );
            wp_safe_redirect(admin_url('admin.php?page=mwp-webcams&mwp_error=1'));
            exit;
        }
    }

    // Righe scartate: mostrale come avviso, niente sparizioni silenziose
    if ($skipped) {
        set_transient(
            'mwp_admin_notice_' . get_current_user_id(),
            'Non salvate (URL mancante o non valido): ' . implode(', ', array_map('esc_html', $skipped)),
            120
        );
    }

    wp_safe_redirect(admin_url('admin.php?page=mwp-webcams&updated=1&saved=' . count($cams)));
    exit;
}

function mwp_admin_page() {
    $cams  = mwp_get_webcams();
    $nonce = wp_nonce_field('mwp_save_webcams', '_wpnonce', true, false);

    // Chiave per l'endpoint di stato (v3.26): generata al primo accesso
    $statusKey = (string) get_option('mwp_status_key', '');
    if ($statusKey === '') {
        $statusKey = wp_generate_password(24, false, false);
        update_option('mwp_status_key', $statusKey, false);
    }
    $statusUrl = home_url('/webcam/mwp-status?key=' . rawurlencode($statusKey));

    // v3.30: ordinamento per data di inserimento decrescente (la webcam più
    // recente in cima, sia nell'elenco sia nelle anteprime). Le webcam
    // create prima della 3.30 non hanno il campo "added" e restano in
    // fondo, in ordine alfabetico.
    usort($cams, function ($a, $b) {
        $aa = (int) ($a['added'] ?? 0);
        $ab = (int) ($b['added'] ?? 0);
        if ($aa === $ab) {
            return strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        }
        return $ab <=> $aa;
    });

    // v3.30: paginazione server-side dell'elenco e delle anteprime.
    // Il form di salvataggio contiene solo le righe della pagina corrente:
    // il save handler fa il "merge" con le webcam delle altre pagine.
    $perPageAllowed = [10, 25, 50, 100];
    $perPage = (int) ($_GET['mwp_per_page'] ?? 10);
    if (!in_array($perPage, $perPageAllowed, true)) {
        $perPage = 10;
    }
    // v3.31: ricerca admin (nome, slug o attribuzione) — si combina con la
    // paginazione: la ricerca filtra, la paginazione scorre i risultati.
    $search = sanitize_text_field(wp_unslash($_GET['mwp_s'] ?? ''));
    if ($search !== '') {
        $needle = mb_strtolower($search);
        $cams = array_values(array_filter($cams, function ($cam) use ($needle) {
            return strpos(mb_strtolower((string) ($cam['name'] ?? '')), $needle) !== false
                || strpos(mb_strtolower((string) ($cam['slug'] ?? '')), $needle) !== false
                || strpos(mb_strtolower((string) ($cam['credit'] ?? '')), $needle) !== false;
        }));
    }

    $total    = count($cams);
    $pages    = max(1, (int) ceil($total / $perPage));
    $paged    = max(1, min($pages, (int) ($_GET['mwp_paged'] ?? 1)));
    $pageCams = array_slice($cams, ($paged - 1) * $perPage, $perPage);
    $pageUrl  = function ($p) use ($perPage, $search) {
        $q = 'page=mwp-webcams&mwp_per_page=' . $perPage . '&mwp_paged=' . (int) $p;
        if ($search !== '') {
            $q .= '&mwp_s=' . rawurlencode($search);
        }
        return admin_url('admin.php?' . $q);
    };
    ?>
    <div class="wrap">
        <h1>Webcam di meteo-webcam.it</h1>

        <?php if (isset($_GET['updated'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p>Salvate <strong><?php echo (int) ($_GET['saved'] ?? 0); ?></strong> webcam.</p>
            </div>
        <?php endif; ?>

        <?php $warn = get_transient('mwp_admin_notice_' . get_current_user_id()); ?>
        <?php if ($warn) : delete_transient('mwp_admin_notice_' . get_current_user_id()); ?>
            <div class="notice notice-warning is-dismissible">
                <p><?php echo $warn; ?></p>
            </div>
        <?php endif; ?>

        <p>
            Inserisci l'URL dell'immagine remota <strong>a dimensione piena</strong> (es.
            <code>current.jpg</code>, non <code>thumb/current.jpg</code>):
            il plugin genera automaticamente le varianti ridimensionate in WebP
            e serve l'immagine piena a <code>/webcam/{slug}.jpg</code>.
            Inseriscile nel sito con <code>[mwp_webcam slug="slug" refresh="300"]</code>.
        </p>

        <p>
            <strong>Referer</strong>: vuoto = automatico (stesso dominio dell'immagine);
            <code>none</code> = nessun referer; URL = il sito che incorpora la webcam
            (es. <code>https://www.turismometeo.it/</code>). Molti server accettano l'immagine
            solo con il referer "giusto": se un'anteprima mostra "Webcam non raggiungibile",
            prova a incollare qui l'URL del sito che la mostra correttamente.
        </p>

        <?php /* v3.30/v3.31: barra di ricerca + paginazione — elenco e anteprime
              mostrano la stessa pagina (stesso filtro di ricerca), ordinate
              dalla webcam più recente. */ ?>
        <p style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
            <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>"
                  style="display:inline-flex;gap:6px;align-items:center;margin:0">
                <input type="hidden" name="page" value="mwp-webcams">
                <input type="search" name="mwp_s" value="<?php echo esc_attr($search); ?>"
                       placeholder="Cerca webcam (nome, slug o attribuzione)…"
                       style="min-width:240px">
                <?php submit_button('Cerca', 'secondary', 'submit', false); ?>
                <label style="margin-left:10px">per pagina:
                    <select name="mwp_per_page" onchange="this.form.submit()">
                        <?php foreach ($perPageAllowed as $n) : ?>
                            <option value="<?php echo (int) $n; ?>"<?php selected($n, $perPage); ?>><?php echo (int) $n; ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </form>
        </p>
        <p style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-top:4px">
            <?php if ($search !== '') : ?>
                <span class="notice notice-info" style="margin:0;padding:4px 10px">
                    Ricerca &laquo;<?php echo esc_html($search); ?>&raquo;: <strong><?php echo (int) $total; ?></strong> corrispondenze
                    &nbsp;<a href="<?php echo esc_url(admin_url('admin.php?page=mwp-webcams&mwp_per_page=' . $perPage)); ?>">azzera ricerca</a>
                </span>
            <?php else : ?>
                <strong><?php echo (int) $total; ?></strong> webcam totali
            <?php endif; ?>
            <?php if ($paged > 1) : ?>
                <a class="button" href="<?php echo esc_url($pageUrl($paged - 1)); ?>">&larr; Precedente</a>
            <?php else : ?>
                <span class="button" style="opacity:.5;cursor:default">&larr; Precedente</span>
            <?php endif; ?>
            <span>Pagina <?php echo (int) $paged; ?> di <?php echo (int) $pages; ?></span>
            <?php if ($paged < $pages) : ?>
                <a class="button" href="<?php echo esc_url($pageUrl($paged + 1)); ?>">Successiva &rarr;</a>
            <?php else : ?>
                <span class="button" style="opacity:.5;cursor:default">Successiva &rarr;</span>
            <?php endif; ?>
        </p>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="mwp_save_webcams">
            <?php echo $nonce; ?>

            <table class="widefat striped" id="mwp-table" style="max-width:1100px">
                <thead>
                    <tr>
                        <th style="width:160px">Slug (auto dal nome se vuoto)</th>
                        <th style="width:180px">Nome</th>
                        <th style="width:170px">Referer</th>
                        <th>URL immagine remota (dimensione piena)</th>
                        <th style="width:180px">Attribuzione</th>
                        <th style="width:90px">Cache (sec)</th>
                        <th style="width:230px">Shortcode</th>
                        <th style="width:50px"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="mwp-row-template" style="display:none">
                        <td>
                            <!-- 3.30: marker di pagina: il save aggiorna solo le righe elencate qui -->
                            <input type="hidden" name="mwp_page_slugs[]" value="__i__">
                            <input type="text" name="mwp_webcams[__i__][slug]" placeholder="opzionale" style="width:100%">
                        </td>
                        <td><input type="text" name="mwp_webcams[__i__][name]" style="width:100%"></td>
                        <td><input type="text" name="mwp_webcams[__i__][referer]" placeholder="auto / none / URL" autocomplete="off" style="width:100%"></td>
                        <td><input type="url" name="mwp_webcams[__i__][url]" style="width:100%"></td>
                        <td><input type="text" name="mwp_webcams[__i__][credit]" style="width:100%"></td>
                        <td><input type="number" name="mwp_webcams[__i__][ttl]" value="300" min="30" style="width:70px"></td>
                        <td style="text-align:center;color:#a7aaad">— (dopo il salvataggio)</td>
                        <td><button type="button" class="button mwp-remove" title="Rimuovi">&times;</button></td>
                    </tr>
                    <?php foreach ($pageCams as $cam) : ?>
                        <tr>
                            <td>
                                <!-- 3.30: marker di pagina (vedi mwp_handle_save) -->
                                <input type="hidden" name="mwp_page_slugs[]" value="<?php echo esc_attr($cam['slug']); ?>">
                                <input type="text" name="mwp_webcams[<?php echo esc_attr($cam['slug']); ?>][slug]" value="<?php echo esc_attr($cam['slug']); ?>" style="width:100%" readonly>
                            </td>
                            <td><input type="text" name="mwp_webcams[<?php echo esc_attr($cam['slug']); ?>][name]" value="<?php echo esc_attr($cam['name']); ?>" style="width:100%"></td>
                            <td><input type="text" name="mwp_webcams[<?php echo esc_attr($cam['slug']); ?>][referer]" value="<?php echo esc_attr($cam['referer'] ?? ''); ?>" placeholder="auto / none / URL" autocomplete="off" style="width:100%"></td>
                            <td>
                                <input type="url" name="mwp_webcams[<?php echo esc_attr($cam['slug']); ?>][url]" value="<?php echo esc_attr($cam['url']); ?>" style="width:100%">
                                <?php if (strpos($cam['url'], '/thumb/') !== false || stripos($cam['url'], 'thumb') !== false) : ?>
                                    <p class="description" style="color:#b32d2e">⚠ Questo URL sembra la miniatura: usa l'immagine a dimensione piena</p>
                                <?php endif; ?>
                            </td>
                            <td><input type="text" name="mwp_webcams[<?php echo esc_attr($cam['slug']); ?>][credit]" value="<?php echo esc_attr($cam['credit']); ?>" style="width:100%"></td>
                            <td><input type="number" name="mwp_webcams[<?php echo esc_attr($cam['slug']); ?>][ttl]" value="<?php echo esc_attr($cam['ttl']); ?>" min="30" style="width:70px"></td>
                            <td>
                                <input type="text" readonly
                                       class="mwp-shortcode"
                                       value='[mwp_webcam slug="<?php echo esc_attr($cam['slug']); ?>" refresh="300"]'
                                       onclick="this.select()"
                                       style="width:100%;font-family:monospace;font-size:12px">
                                <button type="button" class="button button-small mwp-copy">Copia</button>
                                <button type="button" class="button button-small mwp-test" data-slug="<?php echo esc_attr($cam['slug']); ?>">Test</button>
                                <span class="mwp-test-result" style="display:block;font-size:11px;color:#50575e"></span>
                            </td>
                            <td><button type="button" class="button mwp-remove" title="Rimuovi">&times;</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p>
                <button type="button" class="button button-secondary" id="mwp-add-row">+ Aggiungi webcam</button>
                <?php submit_button('Salva webcam'); ?>
            </p>
            <?php /* Canarino: ultima variabile del POST. Se manca, il POST e stato troncato da max_input_vars: meglio bloccare il save piuttosto che salvare una lista parziale e cancellare la cache delle webcam rimosse per sbaglio. */ ?>
            <input type="hidden" name="mwp_canary" value="ok">
        </form>

        <h2>Monitoraggio esterno (cron-job.org / uptime)</h2>
        <p>
            URL di stato: risponde <strong>200</strong> con un JSON diagnostico se il
            sistema è sano, <strong>503</strong> se più della metà delle webcam è stantia
            o più del 10% è senza immagine in cache. Usalo come secondo job su
            cron-job.org (o in qualunque uptime monitor): copre ciò che il ping a
            wp-cron.php non dice.
        </p>
        <p>
            <input type="text" readonly value="<?php echo esc_attr($statusUrl); ?>"
                   onclick="this.select()"
                   style="width:100%;max-width:760px;font-family:monospace;font-size:12px">
        </p>

        <h2>Import / Export CSV</h2>
        <p>
            Colonne: <code>name,url,referer,ttl,credit,slug</code> (header con queste
            intestazioni consigliato; <code>referer</code>, <code>ttl</code>, <code>credit</code>
            e <code>slug</code> opzionali — slug generato dal nome se vuoto).
            Separatore a scelta: virgola, punto e virgola o tab (riconosciuto
            automaticamente — Excel italiano che salva con <code>;</code> va bene).
            L'import è <strong>non distruttivo</strong>: le webcam con slug già esistente
            restano intatte, vengono aggiunte solo le nuove — a meno che tu non
            spunti la casella "Aggiorna le webcam esistenti", che aggiorna anche
            le esistenti (nome/URL/referer/TTL/credito, cache azzerata se cambia
            URL o referer). Le webcam importate NON vengono scaricate subito:
            le prende il cron nei run successivi.
        </p>
        <p>
            <a class="button button-secondary"
               href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=mwp_export_csv'), 'mwp_export_csv')); ?>">
                ⬇ Esporta CSV (<?php echo count($cams); ?> webcam)
            </a>
        </p>
        <form method="post" enctype="multipart/form-data"
              action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
              style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
            <input type="hidden" name="action" value="mwp_import_csv">
            <?php wp_nonce_field('mwp_import_csv'); ?>
            <input type="file" name="mwp_csv" accept=".csv,text/csv" required>
            <label style="display:flex;gap:6px;align-items:center;font-weight:600">
                <input type="checkbox" name="mwp_import_update" value="1">
                Aggiorna le webcam esistenti
            </label>
            <?php submit_button('Importa CSV', 'secondary', 'submit', false); ?>
        </form>

        <h2>Anteprime (varianti responsive, servite dal tuo dominio)</h2>
        <p class="description">
            Pagina <?php echo (int) $paged; ?> di <?php echo (int) $pages; ?>:
            <?php echo count($pageCams); ?> webcam su <?php echo (int) $total; ?>,
            nello stesso ordine dell'elenco (la più recente prima).
        </p>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,100px);gap:14px;margin-top:8px" id="mwp-previews">
            <?php foreach ($pageCams as $cam) : ?>
                <figure style="margin:0;text-align:center"
                        class="mwp-preview">
                    <a href="/webcam/<?php echo esc_attr($cam['slug']); ?>/full?ts=<?php echo esc_attr(time()); ?>"
                       target="_blank" rel="noopener" title="Apri a dimensione piena"
                       style="display:block">
                        <img src="/webcam/<?php echo esc_attr($cam['slug']); ?>/480?ts=<?php echo esc_attr(time()); ?>&mwp_preview=1"
                             alt="<?php echo esc_attr($cam['name']); ?>"
                             width="100" height="100" loading="lazy"
                             style="width:100px;height:100px;object-fit:cover;display:block;border-radius:6px;cursor:pointer"
                             onerror="this.onerror=null;this.src='<?php echo esc_attr(mwp_placeholder_uri()); ?>'">
                    </a>
                    <figcaption style="font-size:11px;color:#50575e;margin-top:4px;max-width:100px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                                title="<?php echo esc_attr($cam['name']); ?>">
                        <?php echo esc_html($cam['name']); ?>
                    </figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
    var mwpAjax = <?php echo json_encode([
        'url'   => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mwp_admin'),
    ]); ?>;

    document.addEventListener('DOMContentLoaded', function () {
        var table = document.getElementById('mwp-table').getElementsByTagName('tbody')[0];
        var template = document.querySelector('.mwp-row-template');

        document.getElementById('mwp-add-row').addEventListener('click', function () {
            var row = template.cloneNode(true);
            row.style.display = '';
            row.className = 'mwp-row-new';
            // UNA sola chiave per tutta la riga: calcolarla una volta per campo
            // spedirebbe alla POST una riga "spezzata" in righe da un campo solo
            var key = 'new_' + Date.now() + '_' + Math.floor(Math.random() * 100000);
            row.querySelectorAll('[name]').forEach(function (input) {
                input.name = input.name.replace(/__i__/g, key);
                // 3.30: il marker di pagina nascosto porta la chiave nel value
                if (input.value === '__i__') {
                    input.value = key;
                }
            });
            table.appendChild(row);
        });

        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('mwp-remove')) {
                var tr = e.target.closest('tr');
                // 3.30: registra la rimozione esplicita: il save è un merge
                // per pagina, senza questo marker la webcam "scomparsa dal DOM"
                // sarebbe indistinguibile da una di un'altra pagina.
                var slugInput = tr.querySelector('input[name$="[slug]"]');
                var slug = slugInput ? slugInput.value : '';
                if (slug && slug.indexOf('new_') !== 0) {
                    var h = document.createElement('input');
                    h.type = 'hidden';
                    h.name = 'mwp_delete_slugs[]';
                    h.value = slug;
                    tr.closest('form').appendChild(h);
                }
                tr.remove();
            }
        });

        // Copia lo shortcode nella riga corrispondente
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.mwp-copy');
            if (!btn) return;
            var input = btn.closest('td').querySelector('input.mwp-shortcode');
            input.select();
            if (navigator.clipboard) {
                navigator.clipboard.writeText(input.value);
            } else {
                document.execCommand('copy');
            }
            btn.textContent = '✓ Copiato';
            setTimeout(function () { btn.textContent = 'Copia'; }, 1500);
        });

        // Test del fetch remoto di una webcam, con esito leggibile
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.mwp-test');
            if (!btn) return;
            var span = btn.parentElement.querySelector('.mwp-test-result');
            span.textContent = '…test in corso…';
            btn.disabled = true;
            fetch(mwpAjax.url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=mwp_test_webcam&slug=' + encodeURIComponent(btn.dataset.slug)
                    + '&nonce=' + encodeURIComponent(mwpAjax.nonce)
            }).then(function (r) { return r.json(); }).then(function (j) {
                btn.disabled = false;
                var d = j && j.data ? j.data : {};
                if (!j || !j.success) {
                    span.textContent = '⚠ Errore interno';
                    return;
                }
                if (d.ok) {
                    span.textContent = '✓ OK: ' + d.width + '×' + d.height + ' px, '
                        + d.bytes + ' byte, ' + d.ms + ' ms';
                } else {
                    span.textContent = '⚠ Fallita (' + d.ms + ' ms): '
                        + (d.info ? JSON.stringify(d.info) : 'contenuto non valido');
                }
            }).catch(function () {
                btn.disabled = false;
                span.textContent = '⚠ Errore di rete';
            });
        });
    });
    </script>
    <?php
}