<?php
/**
 * Plugin Name: MWP Fast Images
 * Description: Corsia veloce per le immagini del plugin Meteo Webcam Proxy:
 *              serve le varianti WebP gia' pronte in cache PRIMA che
 *              WordPress carichi i plugin normali e il tema.
 * Version: 1.0
 *
 * Va in wp-content/mu-plugins/ (NON in plugins/): i must-use plugin vengono
 * caricati da wp-settings.php prima di tutti i plugin normali, quindi una
 * richiesta /webcam/... servita qui non carica Yoast, Site Kit, CF7, GTM4WP,
 * W3TC (a parte il suo advanced-cache, che per /webcam/ non fa nulla), ne'
 * il tema. Misura del 18/11/2026: lo stesso WebP rispondeva in 650-1300 ms
 * via /webcam/colla/768 (bootstrap completo di WordPress) contro ~90 ms
 * come file statico.
 *
 * Changelog 1.0 (18/11/2026):
 *   - Prima versione. Copre SOLO il caso normale: webcam nota, originale in
 *     cache non piu' vecchio della soglia di cortesia, variante WebP gia'
 *     generata e non piu' vecchia dell'originale. In tutti gli altri casi
 *     la richiesta prosegue e la gestisce il plugin come prima (cold-start,
 *     immagine di cortesia, rigenerazione varianti, redirect di /full in
 *     navigazione, anteprime admin ?mwp_preview, mwp-status, 404).
 *   - Header identici al plugin (Cache-Control con max-age = TTL della
 *     webcam + stale-while-revalidate, Last-Modified = mtime dell'originale,
 *     Vary: Sec-Fetch-Dest su /full), piu' Content-Length, risposta 304 a
 *     If-Modified-Since e X-MWP-Fast: 1 per verificare che la corsia veloce
 *     sia attiva.
 *
 * ALLINEAMENTO COL PLUGIN: qui i filtri WordPress non sono ancora
 * disponibili (tema e plugin non caricati), quindi valgono i default del
 * plugin: larghezze 480/768/1024/1600 (mwp_sizes), soglia di cortesia 24h
 * (mwp_stale_max_age), stale-while-revalidate 300 (mwp_stale_while_revalidate),
 * TTL di default 300 e minimo 30. Se un giorno si aggiunge uno di quei filtri
 * nel tema, aggiornare anche le costanti qui sotto.
 *
 * @package meteo-webcam
 */

if (!defined('ABSPATH')) {
    exit;
}

const MWPF_SIZES          = [480, 768, 1024, 1600];
const MWPF_STALE_MAX_AGE  = 86400;
const MWPF_SWR            = 300;

mwpf_maybe_serve();

function mwpf_maybe_serve() {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method !== 'GET' && $method !== 'HEAD') {
        return;
    }

    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
    if (strncmp($path, '/webcam/', 8) !== 0) {
        return; // tutte le altre richieste: nessun costo oltre a questo confronto
    }

    // Anteprime admin: le gestisce il plugin (serve anche cache stantia).
    if (isset($_GET['mwp_preview'])) {
        return;
    }

    // Stessi percorsi del plugin (v3.28 senza estensione + vecchi .jpg/.webp).
    $slug = null;
    $variant = null;   // null = immagine piena
    $fullwebp = false; // true sui percorsi /full e -full.webp (come nel plugin)
    if (preg_match('#^/webcam/([a-z0-9\-]+)$#', $path, $m)
        || preg_match('#^/webcam/([a-z0-9\-]+)\.jpg$#', $path, $m)) {
        $slug = $m[1];
    } elseif (preg_match('#^/webcam/([a-z0-9\-]+)/full$#', $path, $m)
        || preg_match('#^/webcam/([a-z0-9\-]+)-full\.webp$#', $path, $m)) {
        $slug = $m[1];
        $fullwebp = true;
    } elseif (preg_match('#^/webcam/([a-z0-9\-]+)/(\d+)$#', $path, $m)
        || preg_match('#^/webcam/([a-z0-9\-]+)-(\d+)\.webp$#', $path, $m)) {
        $slug = $m[1];
        $variant = (int) $m[2];
        if (!in_array($variant, MWPF_SIZES, true)) {
            return; // il plugin risponde 404 "Variante non valida"
        }
    } else {
        return;
    }

    if ($slug === 'mwp-status') {
        return;
    }

    $isFull = ($variant === null);

    // /full aperto come pagina (click, link, barra indirizzi): redirect del plugin.
    if ($fullwebp && (($_SERVER['HTTP_SEC_FETCH_DEST'] ?? '') === 'document')) {
        return;
    }

    // Plugin disattivato = nessuna immagine servita, come senza questo file.
    if (!mwpf_plugin_active()) {
        return;
    }

    $dir  = WP_CONTENT_DIR . '/uploads/mwp-cache';
    $orig = $dir . '/' . $slug . '-full.jpg';
    $file = $isFull ? $dir . '/' . $slug . '-full.webp' : $dir . '/' . $slug . '-' . $variant . '.webp';

    clearstatcache();
    $origTime = @filemtime($orig);
    $fileTime = @filemtime($file);
    if (!$origTime || !$fileTime) {
        return; // cold-start o variante non ancora generata: plugin
    }
    if ($fileTime < $origTime) {
        return; // variante piu' vecchia dell'originale: la rigenera il plugin
    }
    if ((time() - $origTime) > MWPF_STALE_MAX_AGE) {
        return; // webcam offline da oltre 24h: immagine di cortesia del plugin
    }

    // TTL della webcam dalla config (opzione non autoload: una query).
    $ttl = mwpf_webcam_ttl($slug);
    if ($ttl === null) {
        return; // webcam sconosciuta: 404 del plugin
    }

    $size = @filesize($file);
    if (!$size) {
        return;
    }

    $lastModified = gmdate('D, d M Y H:i:s', $origTime) . ' GMT';

    header('Content-Type: image/webp');
    header('Cache-Control: public, max-age=' . $ttl . ', stale-while-revalidate=' . MWPF_SWR);
    if ($fullwebp) {
        header('Vary: Sec-Fetch-Dest');
    }
    header('Last-Modified: ' . $lastModified);
    header('X-MWP-Fast: 1');

    $ims = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '';
    if ($ims !== '') {
        $imsTime = strtotime($ims);
        if ($imsTime !== false && $imsTime >= $origTime) {
            http_response_code(304);
            exit;
        }
    }

    header('Content-Length: ' . $size);
    if ($method === 'HEAD') {
        exit;
    }
    readfile($file);
    exit;
}

function mwpf_plugin_active() {
    $active = get_option('active_plugins', []);
    if (!is_array($active)) {
        return false;
    }
    foreach ($active as $p) {
        if (basename((string) $p) === 'meteo-webcam-proxy.php') {
            return true;
        }
    }
    return false;
}

/** TTL come nel plugin: max(30, ttl della webcam, default 300). null = slug sconosciuto. */
function mwpf_webcam_ttl($slug) {
    $cams = get_option('mwp_webcams', null);
    if (!is_array($cams)) {
        return null;
    }
    foreach ($cams as $cam) {
        if (isset($cam['slug']) && $cam['slug'] === $slug) {
            return max(30, (int) ($cam['ttl'] ?? 300));
        }
    }
    return null;
}
