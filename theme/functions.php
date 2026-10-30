<?php
/**
 * Meteo Webcam Theme — functions.php
 * Tema leggero per meteo-webcam.it, costruito per integrarsi con:
 *  - plugin "Meteo Webcam Proxy" (shortcode [mwp_webcam], routing /webcam/...)
 *  - Google Site Kit / AdSense (annunci automatici e manuali)
 *  - W3 Total Cache (page cache, lazy load con Exclude Words: mwp)
 *
 * Filosofia: zero JavaScript nel tema, un solo foglio di stile, nessuna
 * richiesta esterna (font, icone, ecc.). Ogni modifica qui = bump versione MWT_VERSION.
 *
 * @package meteo-webcam
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MWT_VERSION', '1.5' ); // v1.5: snippet una-tantum featured images JPEG->WebP (rimuovere a fine conversione)

/* ============ Setup ============ */

function mwt_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'responsive-embeds' );

	// Dimensione dedicata per le card della griglia (16:9, crop centrato).
	add_image_size( 'mwt-card', 640, 360, true );

	register_nav_menus( array(
		'primary' => __( 'Menu principale', 'meteo-webcam' ),
	) );
}
add_action( 'after_setup_theme', 'mwt_setup' );

/* ============ Asset: un solo CSS, zero JS ============ */

function mwt_assets() {
	// Nessun jQuery, nessuno script del tema: il plugin webcam porta già
	// i suoi asset lightbox/refresh solo dove servono.
	wp_enqueue_style( 'mwt', get_stylesheet_uri(), array(), MWT_VERSION );
}
add_action( 'wp_enqueue_scripts', 'mwt_assets' );

/**
 * Preconnect verso i domini AdSense: anticipa la connessione per gli
 * annunci inseriti da Site Kit senza toccare i suoi script.
 */
function mwt_adsense_resource_hints( $hints, $relation_type ) {
	if ( 'preconnect' === $relation_type ) {
		$hints[] = array(
			'href'        => 'https://pagead2.googlesyndication.com',
			'crossorigin' => 'anonymous',
		);
	}
	return $hints;
}
add_filter( 'wp_resource_hints', 'mwt_adsense_resource_hints', 10, 2 );

/* ============ Pulizia header (leggero = meno byte) ============ */

remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'admin_print_styles', 'print_emoji_styles' );
remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );

remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wp_shortlink_wp_head' );
remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );

// Stili e script dei block pattern remoti: non servono.
remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
remove_action( 'wp_enqueue_scripts', 'wp_enqueue_classic_theme_styles' );
remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );

// XML-RPC non usato: meno superficie di attacco.
add_filter( 'xmlrpc_enabled', '__return_false' );

/* ============ Contenuto ============ */

function mwt_excerpt_length( $length ) {
	return 24;
}
add_filter( 'excerpt_length', 'mwt_excerpt_length' );

function mwt_excerpt_more( $more ) {
	return '…';
}
add_filter( 'excerpt_more', 'mwt_excerpt_more' );

/**
 * Ricerca: form leggero senza script aggiuntivi.
 */
function mwt_search_form( $form, $args = array() ) {
	$form = '<form role="search" method="get" class="search-form" action="' . esc_url( home_url( '/' ) ) . '">
		<label class="screen-reader-text" for="mwt-s">' . esc_html__( 'Cerca nel sito', 'meteo-webcam' ) . '</label>
		<input type="search" id="mwt-s" name="s" value="' . esc_attr( get_search_query() ) . '" placeholder="' . esc_attr__( 'Cerca…', 'meteo-webcam' ) . '" required>
		<button type="submit">' . esc_html__( 'Cerca', 'meteo-webcam' ) . '</button>
	</form>';
	return $form;
}
add_filter( 'get_search_form', 'mwt_search_form' );

/**
 * Featured image: WordPress (>= 6.3) gestisce già loading="lazy" e
 * fetchpriority="high" sul primo contenuto immagine. Forziamo solo le
 * dimensioni esplicite per evitare CLS: vengono dal size dell'immagine,
 * nessuna magia extra necessaria.
 */
function mwt_featured_image_attrs( $attr ) {
	// L'immagine in evidenza del post singolo è above the fold: eager.
	if ( is_singular() ) {
		$attr['loading'] = 'eager';
	} else {
		$attr['loading'] = 'lazy';
	}
	$attr['decoding'] = 'async';
	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'mwt_featured_image_attrs' );

/**
 * Slot per annunci/banner manuali oltre a quelli automatici di Site Kit.
 * Esempio (in un plugin di snippet o child functions):
 *   add_action( 'mwt_before_content', function () { echo '<ins class="adsbygoogle" …></ins><script>(adsbygoogle=…).push({});</script>'; } );
 * Gli annunci automatici di AdSense (inseriti da Site Kit) NON richiedono nulla qui.
 */
function mwt_ad_slot_before_content() {
	do_action( 'mwt_before_content' );
}

/* ============ Sidebar: opzionale, solo se ci sono widget ============ */

function mwt_widgets_init() {
	register_sidebar( array(
		'name'          => __( 'Sotto l\'elenco articoli', 'meteo-webcam' ),
		'id'            => 'footer-widgets',
		'description'   => __( 'Widget mostrati sotto home e archivi (es. unità AdSense).', 'meteo-webcam' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	) );
}
add_action( 'widgets_init', 'mwt_widgets_init' );

/* ============ Meteo Webcam Proxy — cron (scala ~713 webcam) ============ */

// Con 713 webcam a TTL 600 servirebbero ~71 fetch/minuto. Il cron esterno
// (cron-job.org) spara wp-cron.php ogni 60s: il budget deve restare ben
// sotto quel minuto per non sovrapporre i run sui worker PHP condivisi.

// Batch: tetto massimo di webcam provate per run (default plugin: 8).
// Non è il vincolo reale (lo è il budget), ma non deve troncare la coda.
add_filter( 'mwp_cron_batch', function () { return 120; } );

// Budget: massimo secondi di lavoro per run (default plugin: 20).
// 55s: con ~2s/webcam della v3.31 ≈ 25-27 fetch per run → ~25-27/min,
// ciclo completo su 713 webcam in ~26-28 minuti. NON salire oltre ~55s
// con cron ogni minuto: i run si accavallerebbero (Tophost, worker limitati).
add_filter( 'mwp_cron_time_budget', function () { return 55; } );

/* ============ Note per la messa in produzione ============ */

/*
 * 1) W3 Total Cache: la pagina "/webcam/" è già in "Never cache the following
 *    pages" e il Lazy Load ha Exclude Words "mwp" — non toccare.
 * 2) Attivare il tema, poi Site Kit > AdSense non richiede modifiche:
 *    gli annunci automatici si agganciano da soli a wp_head/wp_footer.
 * 3) Lasciare le modifiche solo via bump versione qui e in style.css.
 * 4) v1.4: dopo il deploy tenere d'occhio /webcam/mwp-status per qualche ora.
 *    Attesa in regime: fresh stabile >0 (equilibrio ~30-40% del parco), le
 *    "missing" colmate nel primo giro completo (~30 min). Se fresh resta 0
 *    il collo di bottiglia è altrove (per-fetch >4s): prossima mossa =
 *    fetch parallelo (curl_multi) lato plugin, v3.32.
 */

/* ============ SNIPPET UNA-TANTUM (v1.5): featured images JPEG -> WebP ============ */
/*
 * Converte in WebP (qualità 82) le immagini in evidenza dei contenuti pubblicati.
 * Il tema le serve in due taglie: "large" (post singolo) e "mwt-card" 640x360
 * (card della griglia home/archivi). Lo snippet converte:
 *  - il file pieno (SOLO se larghezza <= 2560px: oltre, memoria GD rischiosa
 *    sull'hosting condiviso — quegli allegati restano JPEG nel srcset alta risoluzione)
 *  - la taglia "large" se esiste (ricodifica del file già ridimensionato: economico)
 *  - la taglia "mwt-card" (se manca, la genera dal pieno: ne approfitta per fare
 *    il riginenero delle thumbnail rinviato)
 * Aggiorna _wp_attached_file, _wp_attachment_metadata e post_mime_type.
 * I vecchi file .jpg NON vengono cancellati: restano su disco come backup
 * (rollback possibile via FTP; pulibili a conversione finita e verificata).
 *
 * Uso (da amministratore, loggato in wp-admin):
 *   /wp-admin/?mwt_fi_webp=1              dry-run: conteggio coda + featured PNG escluse
 *   /wp-admin/?mwt_fi_webp=1&go=1          converte 10 allegati per richiesta
 *   /wp-admin/?mwt_fi_webp=1&go=1&auto=1   la pagina si auto-riguarda fino a fine coda
 *   /wp-admin/?mwt_fi_webp=1&reset=1       riscansiona la coda da zero
 * Progresso nell'opzione "mwt_fi_webp_todo". A fine coda: flush W3TC automatico.
 * SNIPPET TEMPORANEO: a conversione completata rimuovere tutta questa sezione
 * (la versione pulita successiva = v1.6).
 */

function mwt_fi_webp_ext( $p ) {
	return strtolower( pathinfo( $p, PATHINFO_EXTENSION ) );
}

function mwt_fi_webp_dst( $p ) {
	$dot = strrpos( $p, '.' );
	if ( false === $dot ) {
		return $p;
	}
	return substr( $p, 0, $dot ) . '.webp';
}

function mwt_fi_webp_one( $id ) {
	$orig = get_attached_file( $id );
	if ( ! $orig || ! file_exists( $orig ) ) {
		return array( false, 'file originale assente' );
	}
	$ext = mwt_fi_webp_ext( $orig );
	if ( 'webp' === $ext ) {
		return array( true, 'già webp' );
	}
	if ( ! in_array( $ext, array( 'jpg', 'jpeg', 'png' ), true ) ) {
		return array( false, 'estensione inattesa: ' . $ext );
	}
	$meta = wp_get_attachment_metadata( $id );
	if ( ! is_array( $meta ) ) {
		$meta = array();
	}
	$dir  = dirname( $orig );
	$log  = array();
	$full = false;

	$mk = function ( $src, $dst ) {
		$e = wp_get_image_editor( $src );
		if ( is_wp_error( $e ) ) {
			return $e;
		}
		$e->set_quality( 82 );
		return $e->save( $dst, 'image/webp' );
	};

	// 1) Pieno: converti solo originali non giganti.
	$w = isset( $meta['width'] ) ? (int) $meta['width'] : 0;
	if ( $w > 0 && $w <= 2560 ) {
		$dst = mwt_fi_webp_dst( $orig );
		if ( file_exists( $dst ) ) {
			$r = array( 'path' => $dst, 'width' => $w, 'height' => isset( $meta['height'] ) ? (int) $meta['height'] : 0 );
		} else {
			$r = $mk( $orig, $dst );
		}
		if ( is_wp_error( $r ) ) {
			return array( false, 'pieno: ' . $r->get_error_message() );
		}
		$rel = _wp_relative_upload_path( $dst );
		update_post_meta( $id, '_wp_attached_file', $rel );
		$meta['file']   = $rel;
		$meta['width']  = (int) $r['width'];
		$meta['height'] = (int) $r['height'];
		$full = true;
		$log[] = 'pieno';
	}

	// 2) Taglie usate dal tema.
	$targets = array(
		'large'    => array( 1024, 1024, false ),
		'mwt-card' => array( 640, 360, true ),
	);
	foreach ( $targets as $size => $d ) {
		$entry = isset( $meta['sizes'][ $size ] ) ? $meta['sizes'][ $size ] : null;
		$src   = null;
		$dims  = null;
		if ( $entry && ! empty( $entry['file'] ) ) {
			$p = $dir . '/' . $entry['file'];
			if ( file_exists( $p ) ) {
				$fe = mwt_fi_webp_ext( $p );
				if ( in_array( $fe, array( 'jpg', 'jpeg', 'png' ), true ) ) {
					$src  = $p;
					$dims = $entry;
				} elseif ( 'webp' === $fe ) {
					$log[] = $size . ' (già webp)';
					continue;
				}
			}
		}
		if ( $src ) {
			// Taglia già generata: ricodifica il file ridimensionato così com'è.
			$dst = mwt_fi_webp_dst( $src );
			if ( ! file_exists( $dst ) ) {
				$r = $mk( $src, $dst );
				if ( is_wp_error( $r ) ) {
					$log[] = $size . ': ' . $r->get_error_message();
					continue;
				}
				$dims = array( 'width' => $r['width'], 'height' => $r['height'] );
			}
			$meta['sizes'][ $size ] = array(
				'file'      => wp_basename( $dst ),
				'width'     => (int) $dims['width'],
				'height'    => (int) $dims['height'],
				'mime-type' => 'image/webp',
			);
			$log[] = $size;
		} else {
			// Taglia assente (es. mwt-card non rigenerata): genera dal pieno.
			$src = $orig;
			if ( $full && ! empty( $meta['file'] ) ) {
				$p = $dir . '/' . $meta['file'];
				if ( file_exists( $p ) ) {
					$src = $p;
				}
			}
			$e = wp_get_image_editor( $src );
			if ( is_wp_error( $e ) ) {
				$log[] = $size . ': ' . $e->get_error_message();
				continue;
			}
			$e->set_quality( 82 );
			$e->resize( $d[0], $d[1], $d[2] );
			$base = wp_basename( $orig );
			$dot  = strrpos( $base, '.' );
			$name = ( false === $dot ) ? $base : substr( $base, 0, $dot );
			$dst  = $dir . '/' . $name . '-' . $d[0] . 'x' . $d[1] . '.webp';
			$r = $e->save( $dst, 'image/webp' );
			if ( is_wp_error( $r ) ) {
				$log[] = $size . ': ' . $r->get_error_message();
				continue;
			}
			$meta['sizes'][ $size ] = array(
				'file'      => wp_basename( $r['path'] ),
				'width'     => (int) $r['width'],
				'height'    => (int) $r['height'],
				'mime-type' => 'image/webp',
			);
			$log[] = $size . ' (nuova)';
		}
	}

	wp_update_attachment_metadata( $id, $meta );
	if ( $full ) {
		global $wpdb;
		$wpdb->update( $wpdb->posts, array( 'post_mime_type' => 'image/webp' ), array( 'ID' => $id ) );
	}
	return array( true, implode( ', ', $log ) );
}

function mwt_fi_webp_run() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( ! isset( $_GET['mwt_fi_webp'] ) ) {
		return;
	}
	$go   = isset( $_GET['go'] ) && '1' === $_GET['go'];
	$auto = isset( $_GET['auto'] ) && '1' === $_GET['auto'];
	if ( isset( $_GET['reset'] ) && '1' === $_GET['reset'] ) {
		delete_option( 'mwt_fi_webp_todo' );
		delete_option( 'mwt_fi_webp_total' );
	}

	$todo = get_option( 'mwt_fi_webp_todo' );
	if ( ! is_array( $todo ) ) {
		global $wpdb;
		$ids = $wpdb->get_col(
			"SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm
			 JOIN {$wpdb->posts} a ON a.ID = pm.meta_value AND a.post_type = 'attachment' AND a.post_mime_type = 'image/jpeg'
			 JOIN {$wpdb->posts} p ON p.ID = pm.post_id AND p.post_status = 'publish' AND p.post_type IN ('post','page')
			 WHERE pm.meta_key = '_thumbnail_id'
			 ORDER BY CAST(pm.meta_value AS UNSIGNED)"
		);
		$todo = array_map( 'intval', $ids );
		update_option( 'mwt_fi_webp_todo', $todo, false );
	}
	$total = (int) get_option( 'mwt_fi_webp_total' );
	if ( ! $total ) {
		$total = count( $todo );
		update_option( 'mwt_fi_webp_total', $total, false );
	}

	header( 'Content-Type: text/html; charset=utf-8' );
	echo '<!DOCTYPE html><meta name="viewport" content="width=device-width,initial-scale=1">';
	echo '<h2>Featured images JPEG &rarr; WebP (tema v1.5)</h2>';

	if ( ! $go ) {
		global $wpdb;
		$png = (int) $wpdb->get_var(
			"SELECT COUNT(DISTINCT pm.meta_value) FROM {$wpdb->postmeta} pm
			 JOIN {$wpdb->posts} a ON a.ID = pm.meta_value AND a.post_mime_type = 'image/png'
			 JOIN {$wpdb->posts} p ON p.ID = pm.post_id AND p.post_status = 'publish' AND p.post_type IN ('post','page')
			 WHERE pm.meta_key = '_thumbnail_id'"
		);
		echo '<p><strong>Dry-run.</strong> Allegati JPEG in coda: <strong>' . count( $todo ) . '</strong> (censiti in totale: ' . $total . '). Featured PNG escluse: ' . $png . '.</p>';
		echo '<p>Per convertire: <code>?mwt_fi_webp=1&amp;go=1</code> (10 allegati per richiesta). Aggiungi <code>&amp;auto=1</code> per l'auto-ripetizione fino a fine coda.</p>';
		exit;
	}

	@set_time_limit( 120 );
	$batch = array_splice( $todo, 0, 10 );
	update_option( 'mwt_fi_webp_todo', $todo, false );

	echo '<p>Batch di ' . count( $batch ) . ' allegati:</p><ul>';
	foreach ( $batch as $id ) {
		list( $ok, $msg ) = mwt_fi_webp_one( (int) $id );
		echo '<li>#' . (int) $id . ' — ' . ( $ok ? 'OK' : 'ERRORE' ) . ': ' . esc_html( $msg ) . '</li>';
	}
	echo '</ul>';

	$remaining = count( $todo );
	$done      = $total - $remaining;
	echo '<p>Progresso: <strong>' . $done . ' / ' . $total . '</strong> — restano ' . $remaining . '.</p>';

	if ( 0 === $remaining ) {
		if ( function_exists( 'w3tc_flush_all' ) ) {
			w3tc_flush_all();
			echo '<p>Cache W3TC svuotata. <strong>CONVERSIONE COMPLETATA.</strong> Ora rimuovi questa sezione da functions.php (versione pulita v1.6).</p>';
		} else {
			echo '<p><strong>CONVERSIONE COMPLETATA.</strong> Svuota la cache W3TC, poi rimuovi questa sezione da functions.php (versione pulita v1.6).</p>';
		}
	} elseif ( $auto ) {
		echo '<script>setTimeout(function(){location.href="?mwt_fi_webp=1&go=1&auto=1";},2000);</script>';
	}
	exit;
}
add_action( 'init', 'mwt_fi_webp_run' );

/* ============ FINE SNIPPET UNA-TANTUM v1.5 ============ */
