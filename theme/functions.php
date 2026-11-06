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

define( 'MWT_VERSION', '2.4.1' ); // v2.4.1: regola "parte specifica" per lo snippet mwt_focuskw (tra le due parti del titolo divise da "-" vince la meno generica, più specifica e non usata da altri post). v2.4 (titolo intero) mai caricata. Storia: v2.3 pulita post-rollout link; v2.1 fix XSLT sitemap

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

/* ============ Sitemap Yoast: via lo stylesheet XSLT (v2.1) ============ */

/*
 * Chrome 158 (17 novembre 2026) rimuove il supporto XSLT: le sitemap di
 * Yoast dichiarano `<?xml-stylesheet type="text/xsl" ... main-sitemap.xsl?>`
 * solo per la vista formattata degli umani, e Chrome mostra già il banner
 * "This site uses XSLT...". I crawler (Google/Bing) leggono l'XML grezzo:
 * lo stylesheet non ha alcun ruolo SEO, questa è una fix cosmetica.
 *
 * Il filtro svuota l'istruzione finché è di tipo text/xsl. Quando Yoast
 * (>= 28.9) la sostituirà con un foglio di stile CSS (type="text/css"),
 * questo passa intatto e la vista leggibile torna da sé.
 */
add_filter( 'wpseo_stylesheet_url', function ( $stylesheet ) {
	if ( false !== strpos( (string) $stylesheet, 'text/xsl' ) ) {
		return '';
	}
	return $stylesheet;
} );

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

/* ============ Video SEO: VideoObject JSON-LD per embed YouTube e file video (v1.8/v1.9) ============ */

/**
 * GSC (novembre 2026): le pagine con video embed NON venivano indicizzate
 * come pagine video senza dati strutturati VideoObject. Questo snippet emette
 * un VideoObject per ogni video trovato nel contenuto del post:
 *
 * v1.8 — embed YouTube (oEmbed WP e iframe incollati a mano):
 *   youtube.com/embed/{ID}  youtube.com/live/{ID}  youtube.com/v/{ID}
 *   youtube-nocookie.com/embed/{ID}  youtu.be/{ID}
 *
 * v1.9 — file video diretti nei tag <video> (caso Panomax:
 *   video.panomax.com/cams/{id}/videos/{id}_fullHD.mp4) e qualsiasi
 *   URL .mp4/.webm/.ogv/.m3u8 presente nel contenuto.
 */
function mwt_youtube_video_ids( $content ) {
	$ids = array();
	if ( preg_match_all( '#(?:youtube(?:-nocookie)?\.com/(?:embed|live|v)/([A-Za-z0-9_-]{11})|youtu\.be/([A-Za-z0-9_-]{11}))#i', (string) $content, $m ) ) {
		$count = count( $m[0] );
		for ( $i = 0; $i < $count; $i++ ) {
			$id = $m[1][ $i ] ? $m[1][ $i ] : $m[2][ $i ];
			if ( $id ) {
				$ids[ $id ] = true; // dedup: stesso video embeddato più volte
			}
		}
	}
	return array_keys( $ids );
}

/**
 * URL di file video diretti (mp4/webm/ogv/m3u8) presenti nel contenuto,
 * deduplicati. Copre i <source> dei player HTML5 (Panomax) e gli mp4
 * incollati come link/anchor nel testo.
 */
function mwt_video_file_urls( $content ) {
	$urls = array();
	if ( preg_match_all( '#https?://[^\s"\'<>]+?\.(mp4|webm|ogv|m3u8)(?=[\s"\'<>?]|$)#i', (string) $content, $m ) ) {
		foreach ( $m[0] as $url ) {
			$urls[ $url ] = true;
		}
	}
	return array_keys( $urls );
}

/**
 * Stampa in <head> un VideoObject JSON-LD per ogni video del post.
 * Campi richiesti da Google: name, thumbnailUrl, uploadDate (+ embedUrl o contentUrl).
 */
function mwt_video_schema() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}
	$post = get_post();
	if ( ! $post || 'publish' !== $post->post_status ) {
		return;
	}

	$ids  = mwt_youtube_video_ids( $post->post_content );
	$urls = mwt_video_file_urls( $post->post_content );
	if ( empty( $ids ) && empty( $urls ) ) {
		return;
	}

	// Thumbnail: la featured image del post (nostro dominio, sempre raggiungibile);
	// per YouTube fallback la miniatura standard di YouTube.
	$thumb = '';
	if ( has_post_thumbnail( $post ) ) {
		$thumb = get_the_post_thumbnail_url( $post, 'large' );
	}

	$desc = wp_strip_all_tags( get_the_excerpt( $post ) );
	$desc = mb_substr( $desc, 0, 200 );

	$graph = array();

	// Embed YouTube: embedUrl + contentUrl, thumbnail con fallback ytimg.
	foreach ( $ids as $id ) {
		$graph[] = array(
			'@type'         => 'VideoObject',
			'@id'           => get_permalink( $post ) . '#video-' . $id,
			'name'          => get_the_title( $post ) . ' — video',
			'description'   => $desc,
			'thumbnailUrl'  => array( $thumb ? $thumb : 'https://i.ytimg.com/vi/' . $id . '/maxresdefault.jpg' ),
			'uploadDate'    => get_the_date( 'c', $post ),
			'embedUrl'      => 'https://www.youtube.com/embed/' . $id,
			'contentUrl'    => 'https://www.youtube.com/watch?v=' . $id,
			'isFamilyFriendly' => true,
			'inLanguage'    => 'it-IT',
		);
	}

	// File video diretti (Panomax ecc.): solo contentUrl; serve una thumbnail
	// (requisito Google): la featured image; senza featured si salta il file.
	foreach ( $urls as $url ) {
		$slug = md5( $url );
		if ( ! $thumb ) {
			continue;
		}
		$graph[] = array(
			'@type'         => 'VideoObject',
			'@id'           => get_permalink( $post ) . '#video-' . $slug,
			'name'          => get_the_title( $post ) . ' — video',
			'description'   => $desc,
			'thumbnailUrl'  => array( $thumb ),
			'uploadDate'    => get_the_date( 'c', $post ),
			'contentUrl'    => $url,
			'isFamilyFriendly' => true,
			'inLanguage'    => 'it-IT',
		);
	}

	// Filtro per disattivare o modificare l'output (return array vuoto = niente schema).
	$graph = apply_filters( 'mwt_video_schema', $graph, $post );
	if ( empty( $graph ) ) {
		return;
	}

	echo '<script type="application/ld+json">'
		. wp_json_encode( $graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
		. '</script>' . "\n";
}
add_action( 'wp_head', 'mwt_video_schema', 20 );

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

/* ============ Yoast: frase chiave automatica per i post senza (v2.4.1, snippet una-tantum) ============ */

/*
 * Assegna la frase chiave Yoast ai post pubblicati che non ce l'hanno
 * (dal censimento del 6/11: ~506 post su 532 ce l'hanno già, restano ~26).
 *
 * Regola scelta da Luca (v2.4.1, rivede la v2.4 mai caricata): se il titolo
 * ha due parti divise da "-", si usa la parte MENO GENERICA e PIÙ SPECIFICA
 * che non sia già la frase chiave di un altro post. Casi di Luca:
 *   "Rimini - Vista sulla spiaggia"                      -> "rimini"
 *   "Antartide - Stazione McMurdo (The Observation Hill)" -> "stazione mcmurdo"
 * Titoli senza separatore: frase chiave = titolo intero in minuscolo.
 * Le parti tra parentesi tonde vengono scartate prima ("Stazione McMurdo
 * (The Observation Hill)" -> "stazione mcmurdo").
 *
 * Come vince la parte (punteggio piu' basso):
 *   - SQUALIFICATA: già frase chiave di un altro post (o assegnata prima in questa run)
 *   - +100: formula generica da titolo webcam (inizia con vista/veduta/
 *     panorama/scorcio/immagine/guardando o contiene "vista sul/la/dal...")
 *   - +10 per ogni ALTRO titolo pubblicato che contiene la parte
 *     (più citata = più generica)
 *   - a parità vince la parte DESTRA (il soggetto specifico sta spesso a destra)
 * Se nessuna parte è utilizzabile, la riga esce come "MANUALE": nessuna
 * assegnazione, il post resta da sistemare a mano (meglio che assegnare
 * una keyword sbagliata).
 *
 * Pagina strumento (da loggati come amministratore):
 *   ?mwt_focuskw=1        dry-run: tabella ID / titolo / frase chiave proposta / nota
 *   ?mwt_focuskw=1&go=1   applica con update_post_meta su _yoast_wpseo_focuskw
 *
 * Idempotente: i post con frase chiave già impostata vengono saltati
 * (rilanciare è sicuro). Solo postmeta letto dall'analisi Yoast in admin:
 * NESSUN impatto sul frontend, nessuna revisione, nessun flush richiesto.
 *
 * SNIPPET TEMPORANEO: rimuovere a lavoro completato (versione pulita v2.5).
 */

/** Normalizzazione: parentesi tonde rimosse, minuscolo, spazi collassati. */
function mwt_focuskw_norm( $s ) {
	$s = preg_replace( '/\s*\([^)]*\)/u', '', (string) $s );
	$s = mb_strtolower( $s );
	$s = preg_replace( '/\s+/u', ' ', $s );
	return trim( (string) $s );
}

/** true se la parte è una formula generica da titolo webcam ("vista sulla spiaggia"...). */
function mwt_focuskw_generic( $s ) {
	return (bool) preg_match( '/^(vista|veduta|panorama|panoramica|scorcio|immagine|guardando)/u', (string) $s )
		|| (bool) preg_match( '/\b(vista|veduta|panorama)\s+(su|sul|sulla|sui|sulle|dal|dalla|dai|dagli|degli|delle|del|dello|verso)\b/u', (string) $s );
}

/** Quanti ALTRI titoli pubblicati contengono la parte (più citata = più generica). */
function mwt_focuskw_freq( $candidate, $my_id, $titles ) {
	$n = 0;
	foreach ( $titles as $id => $t ) {
		if ( (int) $id === (int) $my_id || '' === $t ) {
			continue;
		}
		if ( false !== mb_strpos( $t, $candidate ) ) {
			$n++;
		}
	}
	return $n;
}

/**
 * Sceglie la frase chiave per un post: titolo a due parti divise da "-"
 * -> regola; altrimenti titolo intero. Ritorna array( frase_chiave, nota ),
 * frase chiave vuota = MANUALE (non assegnare, rivedere a mano).
 */
function mwt_focuskw_pick( $id, $title, $titles, $used ) {
	$base = mwt_focuskw_norm( $title );
	if ( '' === $base ) {
		return array( '', 'titolo vuoto' );
	}

	$parts = preg_split( '/\s+[-–—]\s+/u', $base, 2 );
	if ( 2 === count( $parts ) && '' !== trim( $parts[0] ) && '' !== trim( $parts[1] ) ) {
		$scored = array();
		foreach ( array( trim( $parts[0] ), trim( $parts[1] ) ) as $part ) {
			if ( isset( $used[ $part ] ) ) {
				$scored[] = array( $part, -1, 'già usata da un altro post' );
				continue;
			}
			$score = 0;
			$why   = array();
			if ( mwt_focuskw_generic( $part ) ) {
				$score += 100;
				$why[] = 'generica';
			}
			$freq = mwt_focuskw_freq( $part, $id, $titles );
			if ( $freq > 0 ) {
				$score += 10 * $freq;
				$why[] = 'citata in ' . $freq . ' altri titoli';
			}
			$scored[] = array( $part, $score, implode( ', ', $why ) );
		}

		// Punteggio più basso vince; a parità vince la parte destra (più specifica).
		$best_i     = -1;
		$best_score = null;
		foreach ( $scored as $i => $sc ) {
			if ( $sc[1] < 0 ) {
				continue;
			}
			if ( null === $best_score || $sc[1] <= $best_score ) {
				$best_i     = $i;
				$best_score = $sc[1];
			}
		}
		if ( $best_i < 0 || $best_score >= 50 ) {
			return array( '', 'nessuna parte utilizzabile (già usate o generiche): rivedere a mano' );
		}

		$winner = $scored[ $best_i ];
		$other  = $scored[ 1 - $best_i ];
		$note   = 'scelto "' . $winner[0] . '" (' . $winner[1] . ' ' . $winner[2] . ') su "' . $other[0] . '" (' . $other[1] . ' ' . $other[2] . ')';
		return array( $winner[0], $note );
	}

	// Nessun separatore: frase chiave = titolo intero (già normalizzato).
	if ( isset( $used[ $base ] ) ) {
		return array( '', 'frase chiave identica già usata da un altro post: rivedere a mano' );
	}
	return array( $base, 'titolo senza separatore: keyword = titolo' );
}

function mwt_focuskw_fill() {
	if ( ! isset( $_GET['mwt_focuskw'] ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Riservato agli amministratori.' );
	}

	$go = ( isset( $_GET['go'] ) && '1' === $_GET['go'] );

	// Tutti i post pubblicati: titoli normalizzati (frequenza) e frase
	// chiave esistente di ogni post (esclusività).
	$q_all = new WP_Query( array(
		'post_type'              => 'post',
		'post_status'            => 'publish',
		'posts_per_page'         => -1,
		'no_found_rows'          => true,
		'update_post_term_cache' => false,
	) );
	$titles = array();
	foreach ( $q_all->posts as $p ) {
		$titles[ $p->ID ] = mwt_focuskw_norm( get_the_title( $p ) );
	}
	$total = count( $q_all->posts );

	global $wpdb;
	$used = array();
	foreach ( (array) $wpdb->get_results( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_yoast_wpseo_focuskw'" ) as $row ) {
		$v = mwt_focuskw_norm( (string) $row->meta_value );
		if ( '' !== $v ) {
			$used[ $v ] = true;
		}
	}

	$with_kw = 0; // post che hanno già la frase chiave
	$todo    = 0; // post senza frase chiave (candidati)
	$manual  = 0; // candidati non risolvibili: da fare a mano
	$applied = 0;
	$rows    = array();

	foreach ( $q_all->posts as $p ) {
		$existing = (string) get_post_meta( $p->ID, '_yoast_wpseo_focuskw', true );
		if ( '' !== trim( $existing ) ) {
			$with_kw++;
			continue;
		}

		list( $kw, $note ) = mwt_focuskw_pick( $p->ID, get_the_title( $p ), $titles, $used );
		$todo++;

		if ( '' === $kw ) {
			$manual++;
			$rows[] = array( $p->ID, get_the_title( $p ), 'MANUALE', $note );
			continue;
		}

		$rows[] = array( $p->ID, get_the_title( $p ), $kw, $note );

		// Segnata come usata anche nel dry-run: la run è coerente col go.
		$used[ $kw ] = true;
		if ( $go ) {
			update_post_meta( $p->ID, '_yoast_wpseo_focuskw', $kw );
			$applied++;
		}
	}

	header( 'Content-Type: text/html; charset=utf-8' );
	echo '<!DOCTYPE html><html lang="it"><head><meta charset="utf-8"><title>mwt_focuskw</title></head><body>';
	echo '<h2>Yoast: frase chiave automatica (tema v2.4.1)</h2>';
	echo '<p>Post pubblicati: <strong>' . intval( $total ) . '</strong> &mdash; con frase chiave gi&agrave; impostata: <strong>' . intval( $with_kw ) . '</strong> &mdash; senza: <strong>' . intval( $todo ) . '</strong> &mdash; di cui MANUALE (da rivedere): <strong>' . intval( $manual ) . '</strong>';
	if ( $go ) {
		echo ' &mdash; applicate ora: <strong>' . intval( $applied ) . '</strong>';
	}
	echo '</p>';
	echo '<p>Modalit&agrave;: <strong>' . ( $go ? 'GO (applicato)' : 'DRY-RUN (nessuna modifica)' ) . '</strong>. Regola: tra le parti del titolo divise da &quot;-&quot; vince la meno generica, pi&ugrave; specifica e non usata da altri post; senza separatore = titolo intero.</p>';
	echo '<table border="1" cellpadding="6" cellspacing="0"><tr><th>ID</th><th>Titolo</th><th>Frase chiave</th><th>Nota</th></tr>';
	foreach ( $rows as $r ) {
		echo '<tr><td>' . intval( $r[0] ) . '</td><td>' . esc_html( $r[1] ) . '</td><td>' . esc_html( $r[2] ) . '</td><td>' . esc_html( $r[3] ) . '</td></tr>';
	}
	echo '</table>';
	echo '<p><a href="?mwt_focuskw=1">&larr; dry-run</a>';
	if ( ! $go ) {
		echo ' &nbsp; <a href="?mwt_focuskw=1&amp;go=1"><strong>APPLICA (go=1)</strong></a>';
	}
	echo '</p>';
	echo '</body></html>';
	exit;
}
add_action( 'template_redirect', 'mwt_focuskw_fill' );
add_action( 'admin_init', 'mwt_focuskw_fill' );


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
 * 5) v1.8: dopo il deploy, flush della page cache W3TC; verificare nel sorgente
 *    di una pagina con embed (es. /san-pellegrino-in-alpe/) il nuovo blocco
 *    <script type="application/ld+json"> con i VideoObject, poi richiedere la
 *    reindicizzazione in GSC e controllare il report Video dopo qualche giorno.
 * 6) v1.9: verificare il VideoObject con contentUrl panomax su una pagina con
 *    player Panomax (es. /cortina-dampezzo-cinque-torri/). NOTA: video.panomax.com
 *    blocca i client automatizzati sull'mp4 (verificato 2/11) — se Googlebot non
 *    riesce a scaricare il file, il video non verrà indicizzato comunque: in quel
 *    caso l'alternativa è contattare Panomax o accettare il limite.
 * 7) v2.1: dopo il deploy FTP del solo functions.php, flush page cache W3TC
 *    (le sitemap XML sono page-cached: il footer W3TC compare nel sorgente),
 *    poi riaprire /sitemap_index.xml: niente banner XSLT e nessuna riga
 *    <?xml-stylesheet?> nel sorgente. SEO invariato: i crawler leggono l'XML.
 * 8) v2.3: cleanup — rimosso lo snippet una-tantum mwt_link_rollout (rollout
 *    completato il 6/11: 756 link interni su ~370 pagine). Deploy = FTP del solo
 *    functions.php + flush page cache W3TC; nessun'altra modifica funzionale.
 *    Il registro mwt_link_rollout_applied resta nel DB: per un eventuale undo
 *    ricaricare temporaneamente la v2.2.4 e usare ?mwt_link_rollout=1&undo=1,
 *    poi tornare alla v2.3 (l'opzione si elimina da wp_options quando non serve).
 * 9) v2.4/v2.4.1: snippet una-tantum mwt_focuskw — assegna la frase chiave
 *    Yoast ai post pubblicati che non ce l'hanno. Regola v2.4.1 (rivede la
 *    v2.4 mai caricata, che usava il titolo intero): nei titoli a due parti
 *    divise da "-" vince la parte meno generica, più specifica e non già
 *    usata da altri post (es. "Rimini - Vista sulla spiaggia" -> "rimini",
 *    "Antartide - Stazione McMurdo (The Observation Hill)" -> "stazione
 *    mcmurdo"); i casi non risolvibili escono come MANUALE e restano a mano.
 *    Deploy = FTP del solo functions.php; da loggati come admin aprire
 *    /?mwt_focuskw=1 (dry-run: controllare la tabella) poi /?mwt_focuskw=1&go=1.
 *    Nessun flush di cache richiesto: tocca solo il postmeta _yoast_wpseo_focuskw,
 *    l'output delle pagine non cambia. I post con testi molto corti mostreranno
 *    poi qualche check Yoast rosso di densità: atteso, non è un errore.
 *    A lavoro completato rimuovere lo snippet nella versione pulita v2.5.
 */


