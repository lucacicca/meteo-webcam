<?php
/**
 * Meteo Webcam Theme — functions.php
 * Tema leggero per meteo-webcam.it, costruito per integrarsi con:
 *  - plugin "Meteo Webcam Proxy" (shortcode [mwp_webcam], routing /webcam/...)
 *  - Google Site Kit / AdSense (annunci automatici e manuali)
 *  - W3 Total Cache (page cache, lazy load con Exclude Words: mwp)
 *
 * Filosofia: zero JavaScript nel tema, un solo foglio di stile, nessuna
 * richiesta esterna. Eccezione v2.6: poche righe inline per il toggle della
 * ricerca nell'header (nessun asset esterno). Ogni modifica qui = bump MWT_VERSION.
 *
 * @package meteo-webcam
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MWT_VERSION', '2.9.1' ); // v2.9.1: spazio sotto i video del lettore WordPress (.wp-video), la webcam che segue non resta attaccata. v2.9: sottocategorie (card con miniatura e conteggio) in testa alle categorie che ne hanno. v2.8: lente di ricerca sulla stessa riga del menu (desktop) e in alto a destra (mobile). v2.7.1: miniature Related Posts (CRP) lazy. v2.7: loading/fetchpriority solo sulla featured del post singolo + prima card eager in home; asset Contact Form 7 solo dove c'e' un modulo (/segnalazioni/). v2.6: webcam precedente/successiva nel post singolo + ricerca nell'header (icona con overlay) e nel post singolo. Storia: v2.5 pulita post-mwt_focuskw; v2.3 pulita post-rollout link; v2.1 fix XSLT sitemap

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

/* ============ Contact Form 7: asset solo dove c'e' un modulo (v2.7) ============ */

/*
 * CF7 carica in OGNI pagina 4 script (hooks, i18n, swv, index) + il suo CSS,
 * ma l'unico modulo del sito e' in /segnalazioni/. I filtri ufficiali
 * wpcf7_load_js / wpcf7_load_css vengono letti da CF7 in wp_enqueue_scripts,
 * quando la pagina richiesta e' gia' nota: tornano true solo dove serve.
 * Regola generale (un eventuale modulo aggiunto altrove funziona da solo):
 * contenuto con lo shortcode [contact-form-7] o il blocco CF7; in piu' la
 * pagina "segnalazioni" per nome, come rete di sicurezza.
 */
function mwt_page_has_cf7() {
	// Nessuna cache statica: se CF7 chiedesse prima che la query sia pronta,
	// un "false" memorizzato bloccherebbe gli asset anche in /segnalazioni/.
	// Prima della query (admin, REST, ajax) lasciamo il default di CF7.
	if ( ! did_action( 'wp' ) ) {
		return true;
	}
	if ( ! is_singular() ) {
		return false;
	}
	$post = get_queried_object();
	if ( ! ( $post instanceof WP_Post ) ) {
		return false;
	}
	return has_shortcode( $post->post_content, 'contact-form-7' )
		|| has_block( 'contact-form-7/contact-form-selector', $post )
		|| 'segnalazioni' === $post->post_name;
}
add_filter( 'wpcf7_load_js', 'mwt_page_has_cf7' );
add_filter( 'wpcf7_load_css', 'mwt_page_has_cf7' );

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
 * v2.6: webcam precedente/successiva nel post singolo.
 *
 * Link agli adiacenti nell'ordine cronologico, restando nella stessa
 * categoria (le webcam sono organizzate per categoria regione): stesso
 * comportamento di get_adjacent_post ma con etichette dedicate.
 * Sugli articoli senza post adiacenti la nav non compare.
 */
function mwt_post_nav() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}

	$prev = get_previous_post( true );
	$next = get_next_post( true );

	if ( ! $prev && ! $next ) {
		return;
	}

	echo '<nav class="post-nav" aria-label="' . esc_attr__( 'Webcam vicine', 'meteo-webcam' ) . '">';
	if ( $prev ) {
		echo '<a class="nav-prev" href="' . esc_url( get_permalink( $prev ) ) . '" rel="prev">‹ ' . esc_html__( 'Webcam precedente', 'meteo-webcam' ) . ': ' . esc_html( get_the_title( $prev ) ) . '</a>';
	}
	if ( $next ) {
		echo '<a class="nav-next" href="' . esc_url( get_permalink( $next ) ) . '" rel="next">' . esc_html__( 'Webcam successiva', 'meteo-webcam' ) . ': ' . esc_html( get_the_title( $next ) ) . ' ›</a>';
	}
	echo '</nav>';
}

/**
 * v2.6: ricerca anche nel post singolo (sotto il contenuto).
 * La ricerca nell'header copre tutte le pagine; questo form in pagina
 * è il punto d'arrivo naturale a fine lettura di una webcam.
 */
function mwt_post_search() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}
	echo '<div class="post-search">' . get_search_form( false ) . '</div>';
}

/**
 * v2.9: sottocategorie in testa alle categorie che ne hanno.
 *
 * Su /category/regione/, /category/zona-geografica/, /category/ambiente/ (e
 * su ogni categoria con figli, es. alpi-orientali → dolomiti) mostra una
 * griglia di card con le categorie figlie: nome, numero di webcam (contando
 * anche le sotto-sottocategorie) e la miniatura dell'ultima webcam con
 * immagine in evidenza. Solo a pagina 1; sotto restano tutte le webcam
 * della categoria, come prima. Categorie figlie vuote: escluse.
 *
 * I dati (figli, conteggi, id della miniatura) stanno in un transient per
 * 12 h, come la page cache: una pagina fredda non rifa' le query per ogni
 * figlia. La chiave contiene una versione (opzione mwt_subcats_ver) che sale
 * quando cambia una categoria o si pubblica/ritira un post.
 *
 * Ritorna true se ha stampato la griglia (index.php allora non mette la
 * prima card dei post in eager: l'immagine sopra la piega e' qui).
 */
function mwt_subcategories() {
	if ( ! is_category() || is_paged() ) {
		return false;
	}
	$parent = get_queried_object();
	if ( ! $parent instanceof WP_Term ) {
		return false;
	}

	$key   = 'mwt_subcats_' . (int) get_option( 'mwt_subcats_ver', 1 ) . '_' . $parent->term_id;
	$items = get_transient( $key );
	if ( ! is_array( $items ) ) {
		$items    = array();
		// Tutte le categorie, poi solo le figlie dirette: pad_counts conta i
		// post delle discendenti solo se le discendenti sono nella stessa
		// lista (con 'parent' resterebbero fuori, es. alpi-orientali → dolomiti).
		$all = get_terms( array(
			'taxonomy'   => 'category',
			'hide_empty' => false,
			'pad_counts' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
		) );
		if ( ! is_wp_error( $all ) ) {
			foreach ( $all as $child ) {
				if ( (int) $child->parent !== (int) $parent->term_id || $child->count < 1 ) {
					continue;
				}
				$thumb = get_posts( array(
					'cat'                 => $child->term_id,
					'posts_per_page'      => 1,
					'fields'              => 'ids',
					'no_found_rows'       => true,
					'ignore_sticky_posts' => true,
					'meta_key'            => '_thumbnail_id', // phpcs:ignore WordPress.DB.SlowDBQuery
				) );
				$items[] = array(
					'id'    => $child->term_id,
					'name'  => $child->name,
					'count' => (int) $child->count,
					'thumb' => $thumb ? (int) get_post_thumbnail_id( $thumb[0] ) : 0,
				);
			}
		}
		set_transient( $key, $items, 12 * HOUR_IN_SECONDS );
	}

	if ( ! $items ) {
		return false;
	}

	$labels = array(
		'regione'         => __( 'Scegli la regione', 'meteo-webcam' ),
		'zona-geografica' => __( 'Scegli la zona', 'meteo-webcam' ),
		'ambiente'        => __( "Scegli l'ambiente", 'meteo-webcam' ),
	);
	$title = isset( $labels[ $parent->slug ] ) ? $labels[ $parent->slug ] : __( 'Sottocategorie', 'meteo-webcam' );

	echo '<section class="subcats" aria-labelledby="mwt-subcats-title">';
	echo '<h2 id="mwt-subcats-title" class="subcats-title">' . esc_html( $title ) . '</h2>';
	echo '<ul class="subcats-grid">';
	$first = true;
	foreach ( $items as $item ) {
		$link = get_category_link( $item['id'] );
		if ( ! $link ) {
			continue;
		}
		echo '<li><a class="subcat" href="' . esc_url( $link ) . '">';
		echo '<span class="subcat-thumb">';
		if ( $item['thumb'] ) {
			echo wp_get_attachment_image(
				$item['thumb'],
				'mwt-card',
				false,
				$first ? array( 'alt' => '', 'loading' => 'eager', 'fetchpriority' => 'high' ) : array( 'alt' => '', 'loading' => 'lazy' )
			);
			$first = false;
		}
		echo '</span>';
		echo '<span class="subcat-name">' . esc_html( $item['name'] ) . '</span>';
		echo '<span class="subcat-count">' . esc_html(
			sprintf(
				/* translators: %d: numero di webcam nella categoria */
				_n( '%d webcam', '%d webcam', $item['count'], 'meteo-webcam' ),
				$item['count']
			)
		) . '</span>';
		echo '</a></li>';
	}
	echo '</ul></section>';
	echo '<h2 class="subcats-all">' . esc_html__( 'Tutte le webcam', 'meteo-webcam' ) . '</h2>';
	return true;
}

/**
 * v2.9: svuota i transient delle sottocategorie quando cambiano le
 * categorie (nuova, rinominata, spostata, eliminata) o si pubblica un post.
 */
function mwt_subcategories_flush() {
	// Cambiare la versione rende vecchie tutte le chiavi: funziona anche con
	// un object cache persistente (W3TC), dove i transient non stanno in
	// wp_options. Le chiavi vecchie scadono da sole dopo 12 h.
	update_option( 'mwt_subcats_ver', (int) get_option( 'mwt_subcats_ver', 1 ) + 1, false );
}
add_action( 'created_category', 'mwt_subcategories_flush' );
add_action( 'edited_category', 'mwt_subcategories_flush' );
add_action( 'delete_category', 'mwt_subcategories_flush' );
add_action( 'transition_post_status', function ( $new, $old ) {
	if ( 'publish' === $new || 'publish' === $old ) {
		mwt_subcategories_flush();
	}
}, 10, 2 );

/**
 * Featured image del post singolo: eager + fetchpriority high (e' above the
 * fold, e' l'LCP). v2.7: SOLO quella. Prima il filtro forzava loading su
 * TUTTE le immagini allegate: in home la prima card usciva con
 * loading="lazy" insieme a fetchpriority="high" (LCP caricato in ritardo) e
 * nei post diventavano eager anche le miniature dei Related Posts in fondo
 * pagina. Per tutte le altre immagini decide WordPress (>= 6.3: prima
 * immagine del contenuto eager + fetchpriority high, le altre lazy);
 * la prima card della home la imposta index.php.
 */
function mwt_featured_image_attrs( $attr ) {
	if ( is_singular() && isset( $attr['class'] ) && false !== strpos( $attr['class'], 'featured-img' ) ) {
		$attr['loading']       = 'eager';
		$attr['fetchpriority'] = 'high';
		$attr['decoding']      = 'async';
	}
	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'mwt_featured_image_attrs' );

/**
 * v2.7.1: miniature dei Related Posts (plugin Contextual Related Posts) in
 * lazy. Collaudo della v2.7 (18/11): in fondo al post le miniature uscivano
 * ancora con loading="eager" anche senza il vecchio filtro del tema: e' CRP
 * stesso a forzarlo (WordPress le aveva gia' marcate lazy: c'e' il suo
 * sizes="auto"). CRP aggiunge il blocco in coda a the_content: questo filtro
 * gira per ultimo e cambia SOLO le <img> con classe crp_thumb.
 */
function mwt_crp_lazy_thumbs( $content ) {
	if ( false === strpos( $content, 'crp_thumb' ) ) {
		return $content;
	}
	return preg_replace_callback(
		'#<img\b[^>]*\bcrp_thumb\b[^>]*>#i',
		function ( $m ) {
			return preg_replace( '#\sloading=(["\'])eager\1#i', ' loading="lazy"', $m[0] );
		},
		$content
	);
}
add_filter( 'the_content', 'mwt_crp_lazy_thumbs', PHP_INT_MAX );

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
 * 8) v2.3: cleanup — rimosso lo snippet una-tantum mwt_link_rollout (rollout
 *    completato il 6/11: 756 link interni su ~370 pagine). Deploy = FTP del solo
 *    functions.php + flush page cache W3TC; nessun'altra modifica funzionale.
 *    Il registro mwt_link_rollout_applied resta nel DB: per un eventuale undo
 *    ricaricare temporaneamente la v2.2.4 e usare ?mwt_link_rollout=1&undo=1,
 *    poi tornare alla v2.3 (l'opzione si elimina da wp_options quando non serve).
 * 9) v2.5: cleanup — rimosso lo snippet una-tantum mwt_focuskw (v2.4/v2.4.1
 *    MAI caricate in produzione: Luca ha deciso di inserire le frasi chiave
 *    Yoast a mano nei ~26 post senza, la generazione automatica non lo
 *    convinceva). In questa versione è anche allineato l'header `Version:` di
 *    style.css a 2.5: è quello che WordPress mostra nella pagina Temi
 *    (wp-admin/themes.php); nessuna regola CSS modificata. Deploy = FTP di
 *    functions.php + style.css in wp-content/themes/meteo-webcam/ + flush page
 *    cache W3TC (l'HTML cachato porta il ?ver= del CSS). Verifiche: ?ver=2.5
 *    sul CSS nel sorgente, pagina Temi con versione 2.5, ?mwt_focuskw=1 NON
 *    risponde più (corretto).
 * 10) v2.6: webcam precedente/successiva nel post singolo (nav .post-nav con
 *     get_previous/get_next_post in_same_term=true, quindi resta nella stessa
 *     categoria) + ricerca:
 *     (a) header: icona lente .search-toggle apre un overlay .search-overlay
 *         con il form (markup e ~15 righe di JS inline in index.php, nessun
 *         asset esterno; chiusura con ESC o click fuori);
 *     (b) post singolo: form .post-search sotto il contenuto (le pagine webcam
 *         hanno così la ricerca anche a fine lettura, oltre a quella globale
 *         nell'header).
 *     Deploy = FTP di functions.php + style.css + index.php in
 *     wp-content/themes/meteo-webcam/ + flush page cache W3TC. Verifiche:
 *     ?ver=2.6 sul CSS nel sorgente; icona lente nell'header che apre/chiude
 *     l'overlay (click, ESC, click fuori); links precedente/successiva in fondo
 *     a una pagina webcam; form di ricerca sotto lo stesso contenuto; niente
 *     nav su pagine senza adiacenti.
 * 11) v2.7: velocita' (audit del 18/11). (a) mwt_featured_image_attrs tocca
 *     solo la featured del post singolo (eager + fetchpriority high); le
 *     altre immagini le decide WordPress. index.php: la PRIMA card di home
 *     e archivi esce eager + fetchpriority high (e' l'LCP), le altre lazy.
 *     (b) Contact Form 7: script e CSS solo nelle pagine con un modulo
 *     (mwt_page_has_cf7: shortcode/blocco CF7 o pagina "segnalazioni").
 *     Deploy = FTP di functions.php + index.php + style.css in
 *     wp-content/themes/meteo-webcam/ + flush page cache W3TC. Verifiche:
 *     ?ver=2.7 sul CSS; in home la prima <img> della griglia ha
 *     loading="eager" fetchpriority="high", le altre loading="lazy"; in una
 *     pagina webcam le miniature Related Posts sono loading="lazy"; in
 *     /segnalazioni/ il modulo invia (prova con una segnalazione di test) e
 *     nel sorgente ci sono contact-form-7/includes/js/index.js e swv; in
 *     home e nelle pagine webcam NON ci sono.
 * 12) v2.7.1: collaudo v2.7 OK tranne le miniature Related Posts, ancora
 *     eager perche' le forza CRP stesso: mwt_crp_lazy_thumbs (the_content,
 *     priorita' PHP_INT_MAX) le porta a loading="lazy" (solo <img> con classe
 *     crp_thumb). Deploy = FTP di functions.php + style.css + flush page
 *     cache W3TC. Verifica: ?ver=2.7.1 sul CSS e miniature CRP lazy in una
 *     pagina webcam.
 * 13) v2.8: header. La lente di ricerca andava a capo sotto le voci di menu,
 *     anche su desktop (.site-nav era un blocco: <ul> e pulsante uno sotto
 *     l'altro). Ora .site-nav e' flex: menu e lente sulla stessa riga. Sotto
 *     i 640px: titolo centrato, lente in alto a destra (position absolute
 *     nell'header), voci di menu su una riga sotto il titolo (0.9rem, gap
 *     14px). Solo CSS, nessun hamburger (3 voci di menu: non serve).
 *     Deploy = FTP di style.css + functions.php + flush page cache W3TC.
 *     Verifiche: desktop tutto su una riga; telefono: lente in alto a
 *     destra accanto al titolo, menu su una riga; la lente apre ancora
 *     l'overlay di ricerca.
 */
