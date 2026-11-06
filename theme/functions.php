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

define( 'MWT_VERSION', '2.2.1' ); // v2.2: snippet una-tantum mwt_link_test (test 10 link interni contestuali); v2.2.1: salta le occorrenze dentro i commenti HTML

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
 */

/* ============ Snippet una-tantum: test link interni contestuali (v2.2 / v2.2.1) ============ */

/*
 * TEST del 6/11: inserisce 10 link interni contestuali (prime 10 righe del
 * report "Link interni contestuali: 796 suggerimenti"). SNIPPET TEMPORANEO:
 * rimuovere dopo il test (prossima versione pulita = v2.3).
 *
 * wp-admin → ?mwt_link_test=1            dry-run (nessuna scrittura)
 * wp-admin → ?mwt_link_test=1&go=1       applica e riporta l'esito
 *
 * Per ogni riga cerca nel post la PRIMA occorrenza libera della frase
 * (case-insensitive, confini di parola, non dentro <a> esistenti, non
 * dentro tag HTML, shortcode o commenti HTML <!-- ... -->) e la avvolge in
 * <a href="https://www.meteo-webcam.it/{slug}/">…</a> preservando
 * maiuscole/accenti originali. Update via $wpdb senza revisioni;
 * flush W3TC per ogni post toccato. Idempotente: se il link è già
 * presente nel post, la riga viene saltata.
 */

function mwt_link_test_items() {
	return array(
		// array( post_ID, frase da linkare, slug della pagina target )
		array( 1546, 'Campo Imperatore', 'lago-di-campotosto' ),
		array( 2026, 'Costa Concordia', 'isola-del-giglio-porto' ),
		array( 2235, 'Costa Concordia', 'isola-del-giglio-porto' ),
		array( 1662, 'Forni di Sopra', 'monte-cridola-da-laggio-di-cadore' ),
		array( 1875, 'Santa Fiora', 'vetta-monte-amiata' ),
		array( 2210, 'San Teodoro', 'capo-coda-cavallo-san-teodoro' ),
		array( 625,  'stazione McMurdo', 'antartide-stazione-mcmurdo-the-observation-hill' ),
		array( 7846, 'lago di Garda', 'sirmione-panorama-sul-lago-di-garda' ),
		array( 8338, 'valle Aurina', 'cadipietra-klausberg-ski-arena' ),
		array( 9443, 'Alpi Apuane', 'canevara-vista-sul-monte-grondilice-e-sul-monte-cavallo' ),
	);
}

function mwt_link_test_find( $content, $phrase ) {
	$len = mb_strlen( $phrase, 'UTF-8' );
	$off = 0;
	while ( $off < mb_strlen( $content, 'UTF-8' ) ) {
		$pos = mb_stripos( $content, $phrase, $off, 'UTF-8' );
		if ( false === $pos ) {
			return false;
		}
		// Confini di parola: il carattere prima e dopo non devono essere lettere/cifre.
		if ( $pos > 0 ) {
			$prev = mb_substr( $content, $pos - 1, 1, 'UTF-8' );
			if ( preg_match( '/[\pL\pN]/u', $prev ) ) {
				$off = $pos + 1;
				continue;
			}
		}
		$next = mb_substr( $content, $pos + $len, 1, 'UTF-8' );
		if ( '' !== $next && preg_match( '/[\pL\pN]/u', $next ) ) {
			$off = $pos + 1;
			continue;
		}
		// Non dentro un <a> esistente, non dentro un tag, non dentro uno shortcode.
		$tail = mb_substr( $content, max( 0, $pos - 500 ), min( 500, $pos ), 'UTF-8' );
		$la = mb_strrpos( $tail, '<a ', 0, 'UTF-8' );
		$lb = mb_strrpos( $tail, '</a>', 0, 'UTF-8' );
		$t1 = mb_strrpos( $tail, '<', 0, 'UTF-8' );
		$t2 = mb_strrpos( $tail, '>', 0, 'UTF-8' );
		$s1 = mb_strrpos( $tail, '[', 0, 'UTF-8' );
		$s2 = mb_strrpos( $tail, ']', 0, 'UTF-8' );
		// Non dentro un commento HTML: i blocchi legacy <!-- ... --> prima dello
		// shortcode (vecchie caption/img commentate) non sono visibili in pagina.
		// Sul prefisso intero, non sul tail: un commento puo' essere lungo.
		$head = mb_substr( $content, 0, $pos, 'UTF-8' );
		$c1 = mb_strrpos( $head, '<!--', 0, 'UTF-8' );
		$c2 = mb_strrpos( $head, '-->', 0, 'UTF-8' );
		$bad = ( false !== $la && ( false === $lb || $la > $lb ) )
			|| ( false !== $t1 && ( false === $t2 || $t1 > $t2 ) )
			|| ( false !== $s1 && ( false === $s2 || $s1 > $s2 ) )
			|| ( false !== $c1 && ( false === $c2 || $c1 > $c2 ) );
		if ( $bad ) {
			$off = $pos + 1;
			continue;
		}
		return $pos;
	}
	return false;
}

function mwt_link_test_run() {
	if ( ! isset( $_GET['mwt_link_test'] ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Solo gli amministratori possono usare questo strumento.' );
	}
	$go = isset( $_GET['go'] ) && '1' === $_GET['go'];
	header( 'Content-Type: text/plain; charset=utf-8' );
	echo '== Test link interni contestuali (10 righe) — ' . ( $go ? 'APPLICA' : 'DRY-RUN (nessuna scrittura)' ) . " ==\n\n";
	global $wpdb;
	$done = 0;
	foreach ( mwt_link_test_items() as $item ) {
		$id     = (int) $item[0];
		$phrase = $item[1];
		$href   = 'https://www.meteo-webcam.it/' . $item[2] . '/';
		$post   = get_post( $id );
		if ( ! $post || 'publish' !== $post->post_status ) {
			echo "[{$id}] SKIP: post non trovato o non pubblicato\n";
			continue;
		}
		if ( false !== strpos( $post->post_content, $href ) ) {
			echo "[{$id}] SKIP: link a {$href} gia' presente\n";
			continue;
		}
		$pos = mwt_link_test_find( $post->post_content, $phrase );
		if ( false === $pos ) {
			echo "[{$id}] NESSUNA occorrenza libera di \"{$phrase}\"\n";
			continue;
		}
		$len = mb_strlen( $phrase, 'UTF-8' );
		$raw = mb_substr( $post->post_content, $pos, $len, 'UTF-8' );
		$ctx = str_replace( "\n", ' ', mb_substr( $post->post_content, max( 0, $pos - 35 ), $len + 70, 'UTF-8' ) );
		if ( $go ) {
			$new = mb_substr( $post->post_content, 0, $pos, 'UTF-8' )
				. '<a href="' . $href . '">' . $raw . '</a>'
				. mb_substr( $post->post_content, $pos + $len, mb_strlen( $post->post_content, 'UTF-8' ), 'UTF-8' );
			$ok = $wpdb->update( $wpdb->posts, array( 'post_content' => $new ), array( 'ID' => $id ) );
			if ( false === $ok ) {
				echo "[{$id}] ERRORE: update wpdb fallito\n";
				continue;
			}
			if ( function_exists( 'w3tc_flush_post' ) ) {
				w3tc_flush_post( $id );
			}
			$done++;
			echo "[{$id}] INSERITO: \"{$raw}\" → {$href}\n    contesto: …{$ctx}…\n";
		} else {
			echo "[{$id}] applicherebbe: \"{$raw}\" → {$href}\n    contesto: …{$ctx}…\n";
		}
	}
	echo $go
		? "\nCompletato: {$done}/10 link inseriti."
		: "\nDry-run completato. Per applicare, riapri con ?mwt_link_test=1&go=1";
	exit;
}
add_action( 'admin_init', 'mwt_link_test_run' );
