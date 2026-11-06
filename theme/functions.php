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

define( 'MWT_VERSION', '2.2.4' ); // v2.2: snippet una-tantum mwt_link_test; v2.2.1: salta le occorrenze nei commenti HTML; v2.2.2: skip anchor = frase chiave Yoast (link concorrenti) + modo undo; v2.2.3: anchor alternativi per 4 righe concorrenti; v2.2.4: rollout completo a lotti (764 righe, snippet mwt_link_rollout)

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


/* ============ Snippet una-tantum: ROLLOUT link interni contestuali (v2.2.4) ============ */

/*
 * ROLLOUT del 6/11: inserisce i 764 link interni contestuali del report
 * "Link interni contestuali: 796 suggerimenti" — 751 righe OK + 13 righe con
 * anchor alternativo (per non competere con la frase chiave Yoast). Escluse
 * le 32 righe concorrenti senza alternativa. SNIPPET TEMPORANEO: rimuovere
 * a lavoro finito (prossima versione pulita = v2.3).
 *
 * Lavoro a lotti da 50 righe (16 lotti), ogni lotto con dry-run prima del go:
 *   ?mwt_link_rollout=1                     riepilogo lotti e stato
 *   ?mwt_link_rollout=1&batch=1             dry-run del lotto 1 (nessuna scrittura)
 *   ?mwt_link_rollout=1&batch=1&go=1        applica il lotto 1
 *   ?mwt_link_rollout=1&batch=1&undo=1      rimuove i link applicati dal lotto 1
 *   ?mwt_link_rollout=1&undo=1              rimuove TUTTI i link applicati
 *
 * Controlli ereditati da v2.2.x, tutti attivi: prima occorrenza libera
 * (case-insensitive, confini di parola, non dentro <a> esistenti, tag HTML,
 * shortcode o commenti HTML <!-- ... -->), anchor con il testo originale del
 * contenuto (maiuscole/accenti), skip se l'anchor contiene la frase chiave
 * Yoast del post (_yoast_wpseo_focuskw, "link concorrenti"), skip se un link
 * verso il target esiste gia' nel post, update $wpdb senza revisioni,
 * w3tc_flush_post per ogni post toccato.
 *
 * Ogni link applicato viene registrato nell'opzione mwt_link_rollout_applied
 * come "lotto|ID|frase|slug": il registro alimenta l'undo per lotto/globale e
 * rende il go idempotente. I 6 link gia' applicati dal test v2.2.2/v2.2.3
 * (righe 1-10 del report, qui nel lotto 1) vengono rilevati come
 * "gia' presente" e saltati senza errore.
 */

function mwt_link_rollout_items() {
	// 764 righe "ID \t frase da linkare \t slug target", ordine del report
	// (score decrescente). Generate offline dal report del 6/11 incrociato
	// con il dump delle frasi chiave Yoast; le 13 righe con anchor alternativo
	// portano gia' la frase alternativa. "rifugio città di Mantova" conserva
	// l'accento del testo reale.
	$s = <<<'MWTITEMS'
2026	isola del giglio	isola-del-giglio-porto
2235	isola del giglio	isola-del-giglio-porto
1662	cridola	monte-cridola-da-laggio-di-cadore
1875	monte amiata	vetta-monte-amiata
7846	lago di garda	sirmione-panorama-sul-lago-di-garda
8338	valle aurina	cadipietra-klausberg-ski-arena
1170	pale di san martino	rifugio-velo-della-madonna-pale-di-san-martino
1263	tunnel munt la schera	passo-del-gallo-tunnel-munt-la-schera
2277	lago maggiore	belgirate-lago-maggiore
2426	lago maggiore	belgirate-lago-maggiore
2451	val di fiemme	alpe-del-cermis-vista-su-cavalese-e-sulle-piste
2841	pale di san martino	passo-giau
3873	pale di san martino	rifugio-velo-della-madonna-pale-di-san-martino
6956	giogo di santa maria	giogo-di-santa-maria-o-passo-dellumbrail
842	Regina Margherita	capanna-regina-margherita-vista-sul-lyskamm
1194	ischia	ischia-vista-sul-castello-aragonese-e-sugli-scogli-di-santanna
1458	tofana di rozes	rifugio-di-bona-vista-sulla-tofana-di-rozes
1479	marmolada	marmolada
1665	val di fiemme	predazzo-vista-sul-paese-e-sul-monte-feudo
175	soraga	moena-lago-di-soraga
175	val di fiemme	alpe-del-cermis-vista-su-cavalese-e-sulle-piste
1972	lago di garda	sirmione-panorama-sul-lago-di-garda
2053	ostuni	ostuni-vista-verso-est
2089	monte argentario	porto-ercole-vista-sul-porto
2189	monte grappa	rifugio-primaneve-sul-monte-tomba
2235	porto di genova	porto-di-genova-terminal-traghetti
2244	vigo di fassa	vigo-di-fassa-vista-sul-sassolungo
2508	Firenze	firenze-vista-su-ponte-vecchio
2893	lago maggiore	belgirate-lago-maggiore
2902	lago maggiore	belgirate-lago-maggiore
3119	lago maggiore	belgirate-lago-maggiore
3179	bardonecchia	bardonecchia-vista-sul-bar-chesal
7280	auronzo	rifugio-auronzo-sulla-forcella-longeres
8089	lago di garda	sirmione-panorama-sul-lago-di-garda
1169	pozza di fassa	pozza-di-fassa
1396	passo del tonale	adamello-ghiacciaio-presena
1466	lagazuoi	rifugio-lagazuoi-vista-sud-est
1658	spluga	montespluga-in-valle-spluga
175	vigo di fassa	vigo-di-fassa-vista-sul-sassolungo
1769	gardena	selva-gardena
2244	pozza di fassa	pozza-di-fassa
2355	san francesco	assisi-basilica-di-san-francesco
2451	pale di san martino	rifugio-velo-della-madonna-pale-di-san-martino
2483	riviera di ponente	porto-di-andora
2513	firenze	firenze-vista-su-ponte-vecchio
3414	catinaccio	vista-sul-gruppo-del-catinaccio-dallhotel-nigglhof-posizionato-a-1200-metri-sul-livello-del-mare-sopra-nova-levante
3653	marina degli aregai	santo-stefano-al-mare-marina-degli-aregai
3653	porto turistico	genova-nervi-porticciolo
3873	cortina d'ampezzo	cortina-dampezzo-cinque-torri
396	cinque terre	riomaggiore-cinque-terre
4553	santa croce	firenze-vista-panoramica-su-santa-croce-e-sul-duomo
7932	punta parrot	capanna-regina-margherita-vista-sulla-punta-parrot
10808	lago della ninfa	lago-della-ninfa
11283	riviera ligure di levante	sestri-levante-baia-del-silenzio
1170	passo gardena	passo-gardena-vista-dal-rifugio-frara
12415	monte argentario	porto-ercole-vista-sul-porto
1474	Carestiato	rifugio-carestiato-vista-sul-monte-agner-e-sulle-vette-feltrine
1767	val gardena	selva-gardena
1854	valle aurina	san-giacomo-in-valle-aurina
1911	lago di como	fraciscio-valchiavenna
2026	costa crociere	genova-vista-sul-relitto-della-costa-concordia
2079	isole svalbard	ny-alesund-isole-svalbard
2084	monte argentario	porto-santo-stefano-il-moletto-e-piazzale-dei-rioni
2119	san francesco	la-verna-vista-sul-santuario-sul-sagrato-e-sulla-vallata
2381	palermo	teatro-massimo-palermo
2513	firenze	firenze-vista-panoramica-sulla-citta
2655	lago maggiore	belgirate-lago-maggiore
2673	castello scaligero	sirmione-panorama-sul-lago-di-garda
2813	val di fassa	val-di-fassa-vista-sul-catinaccio
2978	monte bianco	courmayer-courba-dzeleuna
317	monte bianco	monte-bianco-aiguille-du-midi-skyway-monte-bianco
3403	firenze	firenze-vista-su-ponte-vecchio
6996	monti sibillini	castelluccio-di-norcia-e-monte-vettore
718	riviera ligure di levante	sestri-levante-baia-del-silenzio
7788	porto turistico	genova-nervi-porticciolo
8278	riviera ligure di levante	sestri-levante-baia-del-silenzio
842	rifugio città di mantova	rifugio-citta-di-mantova
9419	pale di san martino	rifugio-velo-della-madonna-pale-di-san-martino
10184	yacht club	marina-di-scarlino
103	monte amiata	monte-amiata-prato-delle-macinaie
1222	costiera amalfitana	ravello-vista-su-maiori-e-su-minori
12392	val di fiemme	alpe-del-cermis-vista-su-cavalese-e-sulle-piste
1263	livigno	livigno-vista-sul-paese
13007	costiera amalfitana	positano-costiera-amalfitana
1404	cortina d'ampezzo	cortina-dampezzo-cinque-torri
1458	cortina d'ampezzo	cortina-dampezzo-cinque-torri
1479	passo pordoi	passo-pordoi
1658	lago di como	lago-di-como-colico
1763	val gardena	selva-gardena
1767	santa cristina	santa-cristina-vista-sul-sassolungo-e-sullla-pista-saslong
2026	porto di genova	porto-di-genova-terminal-traghetti
2084	circolo nautico	finale-ligure-circolo-nautico
2235	costa crociere	trapani-stazione-marittima
2244	val di fiemme	alpe-del-cermis-vista-su-cavalese-e-sulle-piste
2251	lago maggiore	belgirate-lago-maggiore
2255	lago maggiore	belgirate-lago-maggiore
228	val di fassa	val-di-fassa-vista-sul-catinaccio
2337	costiera amalfitana	positano-costiera-amalfitana
2343	napoli	napoli-vista-sul-golfo-e-sul-vesuvio
2426	isole borromee	le-isole-borromee-viste-da-baveno-sul-lago-maggiore
2483	porto turistico	genova-nervi-porticciolo
2483	santo stefano	porto-santo-stefano-il-moletto-e-piazzale-dei-rioni
2889	colle bettaforca	colle-bettaforca
2902	isole borromee	le-isole-borromee-viste-da-baveno-sul-lago-maggiore
2923	colle bettaforca	colle-bettaforca
2932	val di fassa	val-di-fassa-vista-sul-catinaccio
3014	costa crociere	genova-vista-sul-relitto-della-costa-concordia
3079	Isola d'Elba	isola-delba-lacona
3357	passo gardena	passo-gardena-vista-dal-rifugio-frara
3414	val di fassa	val-di-fassa-vista-sul-catinaccio
3416	val di fassa	val-di-fassa-vista-sul-catinaccio
3469	Civetta	ristoro-belvedere-a-cima-fertazza-vista-sul-civetta
3480	monte amiata	monte-amiata-prato-delle-macinaie
3795	passo gardena	passo-gardena-vista-dal-rifugio-frara
3833	golfo borromeo	belgirate-lago-maggiore
3858	ponte vecchio	firenze-vista-su-ponte-vecchio
3873	cortina	cortina-monte-cristallo
406	monti sibillini	pintura-di-bolognola
713	cinque terre	monterosso-cinque-terre
737	castello scaligero	soave-vista-sul-castello-scaligero
7635	sangro	monte-pallano-val-di-sangro
7638	genova	genova-porto-antico
7638	ponte morandi	ponte-san-giorgio-genova-ex-ponte-morandi
7638	stazione marittima	trapani-stazione-marittima
7854	val di fassa	val-di-fassa-vista-sul-catinaccio
7895	genova	genova-porto-antico
7932	rifugio pastore	rifugio-pastore-monte-rosa
8240	val pusteria	monguelfo-in-val-pusteria
8456	val di fassa	val-di-fassa-vista-sul-catinaccio
883	genova	genova-dal-monte-fasce-vista-ponente
884	porto turistico	porto-turistico-di-vibo-valentia-marina
8869	san pietro	carloforte-isola-di-san-pietro
9261	monte amiata	monte-amiata-prato-delle-macinaie
10192	yacht club	marina-di-scarlino
103	lago di bolsena	marta-vista-sul-lago-di-bolsena
10808	monte cimone	abetone-campo-scuola-e-piazzale
10813	sestola	sestola
11319	porto di andora	porto-di-andora
11516	plan de corones	plan-de-corones
1170	gardena	selva-gardena
1194	ponte di legno	ponte-di-legno-tonale
12023	lago santo modenese	lago-santo-modenese
1223	circolo polare artico	rovaniemi-finlandia
12435	riviera di ponente	santo-stefano-al-mare-marina-degli-aregai
1343	Antartide	antartide-scott-base-arrival-heights
1424	cinque terre	monterosso-cinque-terre
1658	mera	alpe-di-mera
1875	monte amiata	monte-amiata-prato-delle-macinaie
2208	san teodoro	san-teodoro-spiaggia-de-la-cinta
2241	nova ponente	nova-ponente
2244	canazei	canazei-belvedere
2244	moena	moena-paese
2477	golfo dei poeti	tellaro
2481	porto turistico	genova-nervi-porticciolo
2504	san candido	monguelfo-in-val-pusteria
2785	lago d'orta	lago-dorta-vista-da-omegna
2893	lago di garda	sirmione-panorama-sul-lago-di-garda
2963	auronzo	lago-auronzo-lago-di-santa-caterina
2963	lago di misurina	lago-di-misurina
3014	isole egadi	favignana-cala-rossa
3043	palermo	palermo-vista-sul-duomo
3228	favignana	favignana-vista-sul-paese
4553	lago di garda	sirmione-panorama-sul-lago-di-garda
6989	riviera di ponente	santo-stefano-al-mare-marina-degli-aregai
7638	duca degli abruzzi	rifugio-duca-degli-abruzzi-gran-sasso
7742	lago d'orta	lago-dorta-vista-da-omegna
7756	livorno	terrazza-mascagni-livorno
7786	valle spluga	fraciscio-valchiavenna
7949	auronzo	rifugio-auronzo-sulla-forcella-longeres
7949	lago di misurina	lago-di-misurina
8055	val pusteria	plan-de-corones
8240	valle di casies	valle-di-casies-o-val-casies
8278	golfo dei poeti	tellaro
8393	pisa	pisa-tuttomondo-di-keith-haring
8693	lago maggiore	belgirate-lago-maggiore
883	porto di genova	porto-di-genova-terminal-traghetti
92	lago di bolsena	marta-vista-sul-lago-di-bolsena
9595	santa caterina valfurva	santa-caterina-valfurva
103	santa fiora	santa-fiora-vista-sulla-peschiera
1121	torrente nervi	genova-nervi-porticciolo
11919	porto turistico	genova-nervi-porticciolo
1221	san francesco	assisi-basilica-di-san-francesco
134	val di fassa	val-di-fassa-vista-sul-catinaccio
1458	cortina	cortina-monte-cristallo
1479	val di fassa	val-di-fassa-vista-sul-catinaccio
1485	monte pelmo	rifugio-citta-di-fiume-vista-sul-monte-pelmo
1531	lago di como	fraciscio-valchiavenna
2011	santa maddalena	valle-di-casies-o-val-casies
2082	porto turistico	genova-nervi-porticciolo
2084	yacht club	marina-di-scarlino
2235	genova	genova-porto-antico
2314	costa smeralda	porto-cervo-in-costa-smeralda
2355	monte penna	parco-nazionale-foreste-casentinesi-vista-sul-monte-penna
2725	alpi liguri	finale-ligure-circolo-nautico
2813	val gardena	selva-gardena
3005	santa fiora	vetta-monte-amiata
3228	isole egadi	trapani-stazione-marittima
3712	santo stefano	porto-santo-stefano-il-moletto-e-piazzale-dei-rioni
3858	santa croce	firenze-vista-panoramica-su-santa-croce-e-sul-duomo
3873	alta badia	passo-gardena-vista-dal-rifugio-frara
481	santo stefano	porto-santo-stefano-il-moletto-e-piazzale-dei-rioni
6972	reggio calabria	reggio-calabria-vista-sullo-stretto
6975	san francesco	assisi-basilica-di-san-francesco
7169	livigno	livigno-vista-sul-paese
718	porto venere	portovenere-isola-palmaria-e-isola-del-tino
7496	prealpi orobie	rifugio-luigi-albani
7500	val brembana	alta-val-brembana-vista-del-paese-piazzatorre
788	cortina	cortina-monte-cristallo
788	passo giau	passo-giau
8066	cala rossa	favignana-cala-rossa
8100	monte amiata	monte-amiata-prato-delle-macinaie
8278	cinque terre	monterosso-cinque-terre
8366	lecce	maluha-bay-torre-chianca-lecce
8456	val gardena	selva-gardena
884	genova	genova-porto-antico
9089	monti sibillini	castelluccio-di-norcia-e-monte-vettore
9645	monte vettore	castelluccio-di-norcia-e-monte-vettore
9666	monte cavallo	canevara-vista-sul-monte-grondilice-e-sul-monte-cavallo
999	thuile	la-thuile
10192	cortina d'ampezzo	cortina-dampezzo-cinque-torri
1026	santo stefano	porto-santo-stefano-il-moletto-e-piazzale-dei-rioni
103	monte argentario	porto-ercole-vista-sul-porto
103	monte terminillo	monte-terminillo
103	monti sibillini	castelluccio-di-norcia-e-monte-vettore
1121	alpi marittime	punta-maladecia
11315	alpi marittime	punta-maladecia
11503	san francesco	assisi-basilica-di-san-francesco
11516	santa maddalena	valle-di-casies-o-val-casies
11633	isole tremiti	isole-tremiti-san-domino
11633	porto turistico	genova-nervi-porticciolo
11919	regione calabria	reggio-calabria-vista-sullo-stretto
11986	firenze	firenze-vista-su-ponte-vecchio
12052	san francesco	assisi-basilica-di-san-francesco
1221	san pietro	carloforte-isola-di-san-pietro
12292	san pietro	carloforte-isola-di-san-pietro
12375	cortina d'ampezzo	cortina-dampezzo-cinque-torri
12392	val di fassa	val-di-fassa-vista-sul-catinaccio
12435	san francesco	assisi-basilica-di-san-francesco
12479	lerici	lerici-vista-sul-golfo-della-spezia
12494	bocca serriola	bocca-serriola
1323	san francesco	assisi-basilica-di-san-francesco
1325	bellaria	bellaria
1327	san francesco	assisi-basilica-di-san-francesco
134	monte civetta	ristoro-belvedere-a-cima-fertazza-vista-sul-civetta
1357	prealpi luganesi	campo-dei-fiori-osservatorio-schiaparelli
1404	cortina	cortina-vista-delle-tofane-dal-rifugio-duca-daosta
1466	cortina d'ampezzo	cortina-dampezzo-cinque-torri
1468	cortina d'ampezzo	cortina-dampezzo-cinque-torri
1468	tofane	cortina-vista-delle-tofane-dal-rifugio-duca-daosta
1535	prealpi venete	rifugio-primaneve-sul-monte-tomba
1767	val di fassa	val-di-fassa-vista-sul-catinaccio
2026	genova	genova-porto-antico
2026	monte argentario	porto-ercole-vista-sul-porto
2069	porto turistico	genova-nervi-porticciolo
2084	porto turistico	genova-nervi-porticciolo
2107	san pietro	nova-ponente-vista-sul-monte-san-pietro
2115	santo stefano	porto-santo-stefano-il-moletto-e-piazzale-dei-rioni
2142	san francesco	assisi-basilica-di-san-francesco
2235	monte argentario	porto-ercole-vista-sul-porto
2279	monte calvario	pescocostanzo-vista-sul-monte-calvario
2314	porto turistico	genova-nervi-porticciolo
2355	falco pellegrino	pirellone-grattacielo-pirelli-nido-falchi-pellegrini
2381	san francesco	assisi-basilica-di-san-francesco
2424	firenze	firenze-vista-su-ponte-vecchio
2451	val di fassa	val-di-fassa-vista-sul-catinaccio
2513	san francesco	assisi-basilica-di-san-francesco
2572	brembana	alta-val-brembana-vista-del-paese-piazzatorre
2656	san francesco	assisi-basilica-di-san-francesco
2893	lago di como	fraciscio-valchiavenna
2908	domodossola	domodossola-vista-sul-paese-e-sulla-val-dossola
2908	lago maggiore	belgirate-lago-maggiore
3264	san francesco	assisi-basilica-di-san-francesco
3377	macugnaga	macugnaga-vista-sul-ghiacciaio-belvedere-del-monte-rosa
3392	antartide	antartide-stazione-mcmurdo
3401	isole tremiti	isole-tremiti-san-domino
3418	alpi marittime	punta-maladecia
4567	falco pellegrino	pirellone-grattacielo-pirelli-nido-falchi-pellegrini
4994	canazei	canazei-belvedere
4994	val di fassa	val-di-fassa-vista-sul-catinaccio
537	isole tremiti	isole-tremiti-san-domino
627	san pietro	carloforte-isola-di-san-pietro
6972	santo stefano	porto-santo-stefano-il-moletto-e-piazzale-dei-rioni
6989	alpi marittime	punta-maladecia
7464	piombino	salivoli-piombino-vista-sullisola-delba
7492	ponte vecchio	firenze-vista-su-ponte-vecchio
7498	prealpi orobie	rifugio-luigi-albani
7500	prealpi orobie	rifugio-luigi-albani
7951	val gardena	selva-gardena
8070	falco pellegrino	pirellone-grattacielo-pirelli-nido-falchi-pellegrini
8100	siena	siena-vico-alto
8693	lago d'orta	lago-dorta-vista-da-omegna
8837	val di fassa	val-di-fassa-vista-sul-catinaccio
8905	val di tures	campo-tures-val-di-tures
9443	santo stefano	porto-santo-stefano-il-moletto-e-piazzale-dei-rioni
9595	san pietro	carloforte-isola-di-san-pietro
9657	monti sibillini	castelluccio-di-norcia-e-monte-vettore
9674	vette feltrine	rifugio-carestiato-vista-sul-monte-agner-e-sulle-vette-feltrine
987	fanano	fanano
10192	portofino	portofino-vista-sul-porticciolo
1026	perugia	ponte-doddi-perugia
11041	venezia	venezia-vista-del-canal-grande
1222	san pietro	carloforte-isola-di-san-pietro
12297	livorno	terrazza-mascagni-livorno
12435	alpi apuane	canevara-vista-sul-monte-grondilice-e-sul-monte-cavallo
1299	sangro	monte-pallano-val-di-sangro
1343	antartide	antartide-stazione-mcmurdo
1391	versante est	etna-versante-est
1446	alpi apuane	canevara-vista-sul-monte-grondilice-e-sul-monte-cavallo
1456	plateau rosa	cervino-plateau-rosa
1466	tofane	cortina-vista-delle-tofane-dal-rifugio-duca-daosta
1857	canazei	canazei-belvedere
1857	sassolungo	santa-cristina-vista-sul-sassolungo-e-sullla-pista-saslong
1948	siena	siena-vico-alto
2011	san pietro	carloforte-isola-di-san-pietro
2082	yacht club	marina-di-scarlino
2142	san pietro	carloforte-isola-di-san-pietro
2200	brembana	val-brembana-vista-dal-paese-roncobello
2244	soraga	moena-lago-di-soraga
2244	val gardena	selva-gardena
2251	pella	pella-sul-lago-dorta
2275	ravenna	punta-marina-terme-di-ravenna
2279	ossola	ornavasso-vista-sulla-val-dossola
228	predazzo	predazzo-vista-sul-paese-e-sul-monte-feudo
22	marmarole	rifugio-chiggiato-sulle-marmarole-centrali
22	tofane	cortina-vista-delle-tofane-dal-rifugio-duca-daosta
2314	yacht club	marina-di-scarlino
2353	ischia	ischia-porto
2424	civetta	ristoro-belvedere-a-cima-fertazza-vista-sul-civetta
2429	fano	fano-vista-sulla-campagna
2453	pisa	pisa-tuttomondo-di-keith-haring
2483	genova	genova-porto-antico
2638	piombino	salivoli-piombino-vista-sullisola-delba
2655	omegna	lago-dorta-vista-da-omegna
2656	maddalena	la-maddalena-vista-isola-santo-stefano
2673	venezia	venezia-vista-del-canal-grande
2902	domodossola	domodossola-vista-sul-paese-e-sulla-val-dossola
2902	ossola	ornavasso-vista-sulla-val-dossola
2978	cervino	cervino-plateau-rosa
3357	gardena	selva-gardena
3401	san pietro	carloforte-isola-di-san-pietro
3403	futa	passo-della-futa
3414	carezza	carezza-vista-sul-rosengarten-catinaccio
3414	latemar	rifugio-torre-di-pisa-sul-latemar
3416	latemar	rifugio-torre-di-pisa-sul-latemar
3648	iseo	iseo-vista-sul-lago
3653	capo mele	capo-mele-laigueglia
3795	gardena	selva-gardena
3873	tofane	cortina-vista-delle-tofane-dal-rifugio-duca-daosta
4503	firenze	firenze-vista-su-ponte-vecchio
4567	rimini	rimini-bagno-39
627	roma	roma-vista-panoramica-sud-est
718	lerici	lerici-vista-sul-golfo-della-spezia
7677	firenze	firenze-vista-su-ponte-vecchio
788	lagazuoi	rifugio-lagazuoi-vista-sulle-tofane
7999	maddalena	la-maddalena-vista-isola-santo-stefano
8005	monte pelmo	rifugio-citta-di-fiume-vista-sul-monte-pelmo
8089	limone	riserva-bianca-limone
8091	sassolungo	santa-cristina-vista-sul-sassolungo-e-sullla-pista-saslong
8100	pitigliano	pitigliano
8152	versante est	etna-versante-est
8240	cortina	cortina-monte-cristallo
8263	livigno	livigno-vista-sul-paese
8263	sestriere	sestriere-vista-sul-paese
8270	mugello	lago-di-bilancino-mugello
8400	valfurva	santa-caterina-valfurva
8456	canazei	canazei-belvedere
8556	valfurva	santa-caterina-valfurva
8860	alpi apuane	canevara-vista-sul-monte-grondilice-e-sul-monte-cavallo
9563	champoluc	rifugio-ferraro-vista-su-champoluc-e-la-valle-di-nana
999	monte bianco	courmayer-courba-dzeleuna
103	gran sasso	gran-sasso-rifugio-le-fontari-a-campo-imperatore
103	monte penna	la-verna-vista-sul-santuario-sul-sagrato-e-sulla-vallata
103	siena	siena-vico-alto
10813	monte cimone	abetone-campo-scuola-e-piazzale
10821	monte cimone	abetone-campo-scuola-e-piazzale
1121	alpi pennine	alpe-di-mera
1121	san pietro	carloforte-isola-di-san-pietro
11283	san pietro	carloforte-isola-di-san-pietro
11319	versante est	etna-versante-est
11516	val pusteria	monguelfo-in-val-pusteria
12023	alpi apuane	canevara-vista-sul-monte-grondilice-e-sul-monte-cavallo
12435	monte amiata	monte-amiata-prato-delle-macinaie
1296	san pietro	carloforte-isola-di-san-pietro
13007	napoli	napoli-vista-sul-golfo-e-sul-vesuvio
1875	siena	siena-vico-alto
1943	firenze	firenze-vista-su-ponte-vecchio
2018	venezia	venezia-vista-del-canal-grande
2053	san nicola	isole-tremiti-san-domino
2069	monte bianco	courmayer-courba-dzeleuna
2077	new york	new-york-vista-dalla-torcia-della-statua-della-liberta
2115	porto antico	genova-porto-antico
2232	cinque terre	monterosso-cinque-terre
2244	col rodella	col-rodella-vista-sul-sella-e-sul-pordoi
2251	ossola	ornavasso-vista-sulla-val-dossola
2381	san pietro	carloforte-isola-di-san-pietro
2451	latemar	rifugio-torre-di-pisa-sul-latemar
2477	cinque terre	monterosso-cinque-terre
2548	new york	new-york-vista-dalla-torcia-della-statua-della-liberta
2673	san pietro	carloforte-isola-di-san-pietro
2705	mar ionio	soverato
2902	versante est	etna-versante-est
3200	santa croce	firenze-vista-panoramica-su-santa-croce-e-sul-duomo
3233	isole egadi	trapani-stazione-marittima
3298	versante est	etna-versante-est
3368	versante est	etna-versante-est
3374	san nicola	isole-tremiti-san-domino
3377	alpi pennine	alpe-di-mera
3611	alpi pennine	alpe-di-mera
3683	genova	genova-porto-antico
537	san pietro	carloforte-isola-di-san-pietro
6736	genova	genova-porto-antico
7498	val brembana	alta-val-brembana-vista-del-paese-piazzatorre
7677	san pietro	carloforte-isola-di-san-pietro
7750	san pietro	carloforte-isola-di-san-pietro
7799	ossola	ornavasso-vista-sulla-val-dossola
7863	monte cusna	monte-cusna
7874	firenze	firenze-vista-su-ponte-vecchio
7919	monte penna	la-verna-vista-sul-santuario-sul-sagrato-e-sulla-vallata
7930	alpi pennine	alpe-di-mera
7932	monte bianco	courmayer-courba-dzeleuna
7932	versante est	etna-versante-est
7934	pusteria	monguelfo-in-val-pusteria
8310	nervi	genova-nervi-porticciolo
8332	san nicola	isole-tremiti-san-domino
8338	san pietro	carloforte-isola-di-san-pietro
8366	sant'andrea	isola-delba-capo-santandrea
8475	val brembana	alta-val-brembana-vista-del-paese-piazzatorre
8584	versante est	etna-versante-est
8662	monte grappa	cima-grappa-vista-sul-sacrario-militare
8672	santa croce	firenze-vista-panoramica-su-santa-croce-e-sul-duomo
8693	alpi pennine	alpe-di-mera
8693	ossola	ornavasso-vista-sulla-val-dossola
8693	san pietro	carloforte-isola-di-san-pietro
8693	versante est	etna-versante-est
8703	modena	modena-vista-sulla-ghirlandina
8762	pescara	pescara-vista-da-colle-marino
8794	dora riparia	monginevro-viste-dalla-serre-thibaud
8846	alpi pennine	alpe-di-mera
9563	alpi pennine	alpe-di-mera
10646	firenze	firenze-vista-su-ponte-vecchio
11319	genova	genova-porto-antico
11503	pisa	pisa-tuttomondo-di-keith-haring
1150	siena	siena-vico-alto
11840	venezia	venezia-vista-del-canal-grande
1221	genova	genova-porto-antico
12392	latemar	rifugio-torre-di-pisa-sul-latemar
12399	ravenna	punta-marina-terme-di-ravenna
12435	livorno	terrazza-mascagni-livorno
12479	genova	genova-porto-antico
12494	fano	marotta-di-fano-vista-sulla-spiaggia
12496	urbino	urbino-osservatorio
1323	modena	modena-vista-sulla-ghirlandina
1327	urbino	urbino-osservatorio
1446	modena	modena-vista-sulla-ghirlandina
1662	venezia	venezia-vista-del-canal-grande
175	latemar	rifugio-torre-di-pisa-sul-latemar
2011	maddalena	la-maddalena-vista-isola-santo-stefano
2055	napoli	napoli-vista-sul-golfo-e-sul-vesuvio
2069	genova	genova-porto-antico
2071	venezia	venezia-vista-del-canal-grande
2208	tavolara	loiri-porto-san-paolo-vista-sullisola-di-tavolara
2224	latemar	rifugio-torre-di-pisa-sul-latemar
2255	ossola	ornavasso-vista-sulla-val-dossola
2281	genova	genova-porto-antico
2337	capri	capri
2353	napoli	napoli-vista-sul-golfo-e-sul-vesuvio
2429	urbino	urbino-osservatorio
2431	urbino	urbino-osservatorio
2433	genova	genova-porto-antico
2461	napoli	napoli-vista-sul-golfo-e-sul-vesuvio
2588	sila	camigliatello-silano-e-monte-curcio
2619	firenze	firenze-vista-su-ponte-vecchio
2705	sant'andrea	isola-delba-capo-santandrea
2725	imperia	piaggia-di-mendatica-imperia
2965	fanano	fanano
2965	modena	modena-vista-sulla-ghirlandina
3264	venezia	venezia-vista-del-canal-grande
3398	modena	modena-vista-sulla-ghirlandina
3621	magdeleine	la-magdeleine
3803	livorno	terrazza-mascagni-livorno
3833	mottarone	mottarone-vista-sulle-piste
451	sangro	monte-pallano-val-di-sangro
6920	napoli	napoli-vista-sul-golfo-e-sul-vesuvio
6996	bona	rifugio-di-bona-vista-sulla-tofana-di-rozes
7294	firenze	firenze-vista-su-ponte-vecchio
7666	morcone	isola-delba-spiaggia-di-morcone-capoliveri
7673	urbino	urbino-osservatorio
7951	canazei	canazei-belvedere
7951	sassolungo	santa-cristina-vista-sul-sassolungo-e-sullla-pista-saslong
8055	venezia	venezia-vista-del-canal-grande
8270	firenze	firenze-vista-su-ponte-vecchio
832	groenlandia	groenlandia-summit-station
8383	genova	genova-porto-antico
8383	pisa	pisa-tuttomondo-di-keith-haring
863	venezia	venezia-vista-del-canal-grande
8703	firenze	firenze-vista-su-ponte-vecchio
8846	lyskamm	capanna-regina-margherita-vista-sul-lyskamm
8962	pale	rifugio-velo-della-madonna-pale-di-san-martino
9185	modena	modena-vista-sulla-ghirlandina
9225	rimini	rimini-bagno-39
9431	cortina	cortina-monte-cristallo
9804	maddalena	la-maddalena-vista-isola-santo-stefano
987	modena	modena-vista-sulla-ghirlandina
10157	napoli	napoli-vista-sul-golfo-e-sul-vesuvio
10184	genova	genova-porto-antico
10184	palau	palau-vista-sulla-maddalena
10184	venezia	venezia-vista-del-canal-grande
10192	alghero	alghero-baia-di-tramariglio
10192	cortina	cortina-monte-cristallo
10379	firenze	firenze-vista-su-ponte-vecchio
10379	maddalena	la-maddalena-vista-isola-santo-stefano
10379	messico	messico-punta-cancun-a-cancun
10379	silenzio	sestri-levante-baia-del-silenzio
103	piombino	salivoli-piombino-vista-sullisola-delba
10519	capanne	monte-capanne-isola-delba
10808	modena	modena-vista-sulla-ghirlandina
10813	modena	modena-vista-sulla-ghirlandina
11041	jesolo	jesolo-viste-sulla-spiaggia
1121	cervino	cervino-plateau-rosa
1121	genova	genova-porto-antico
11283	genova	genova-porto-antico
11356	imperia	piaggia-di-mendatica-imperia
1136	consuma	passo-della-consuma
11400	bernina	passo-del-bernina
11400	mera	alpe-di-mera
11503	genova	genova-porto-antico
11516	maddalena	la-maddalena-vista-isola-santo-stefano
11579	firenze	firenze-vista-su-ponte-vecchio
11579	siena	siena-vico-alto
11633	maiella	rifugio-pomilio-sulla-maiella
11705	pusteria	monguelfo-in-val-pusteria
11840	rimini	rimini-bagno-39
1194	napoli	napoli-vista-sul-golfo-e-sul-vesuvio
12052	perugia	ponte-doddi-perugia
1221	imperia	piaggia-di-mendatica-imperia
1221	portofino	portofino-vista-sul-porticciolo
1221	sant'andrea	isola-delba-capo-santandrea
1221	sellano	castello-di-orsano-sellano
1222	gallipoli	gallipoli-riviera-bastiano-sauro
1222	napoli	napoli-vista-sul-golfo-e-sul-vesuvio
1222	pisa	pisa-tuttomondo-di-keith-haring
12297	cecina	cecina-piazza-della-liberta
12297	pisa	pisa-tuttomondo-di-keith-haring
12375	cortina	cortina-monte-cristallo
12375	lagazuoi	rifugio-lagazuoi-vista-sulle-tofane
12392	soraga	moena-lago-di-soraga
12479	pisa	pisa-tuttomondo-di-keith-haring
12494	perugia	ponte-doddi-perugia
12494	sant'angelo	monte-santangelo
12496	fano	marotta-di-fano-vista-sulla-spiaggia
12637	nervi	genova-nervi-porticciolo
1299	pescasseroli	pescasseroli-vista-sul-paese
13021	modena	modena-vista-sulla-ghirlandina
13021	sestola	sestola
1327	fano	marotta-di-fano-vista-sulla-spiaggia
134	canazei	canazei-belvedere
134	venezia	venezia-vista-del-canal-grande
1352	livigno	livigno-vista-sul-paese
1357	cervino	cervino-plateau-rosa
1391	cattolica	cattolica-vista-sulla-spiaggia
1396	venezia	venezia-vista-del-canal-grande
1466	cortina	cortina-monte-cristallo
1468	cortina	cortina-monte-cristallo
1474	bonetti	spiaggia-bonetti-portonovo
1483	padova	padova-vista-sul-centro-sulla-basilica-s-antonio
1483	venezia	venezia-vista-del-canal-grande
1485	padova	padova-vista-sul-centro-sulla-basilica-s-antonio
1487	moiazza	rifugio-carestiato-vista-sul-rifugio-e-sulla-moiazza
1546	maddalena	la-maddalena-vista-isola-santo-stefano
1546	ponza	ponza
1546	sestriere	sestriere-vista-sul-paese
1882	genova	genova-porto-antico
1909	maloja	passo-del-maloja
1915	bormio	pista-stelvio-di-bormio
1915	foscagno	passo-del-foscagno
1948	firenze	firenze-vista-su-ponte-vecchio
1948	ischia	ischia-porto
1997	venezia	venezia-vista-del-canal-grande
2053	lecce	maluha-bay-torre-chianca-lecce
2089	fano	marotta-di-fano-vista-sulla-spiaggia
2107	genova	genova-porto-antico
2115	ischia	ischia-porto
2115	napoli	napoli-vista-sul-golfo-e-sul-vesuvio
2115	nerone	monte-nerone
2124	eiffel	parigi-vista-sulla-torre-eiffel
2124	maddalena	la-maddalena-vista-isola-santo-stefano
2142	napoli	napoli-vista-sul-golfo-e-sul-vesuvio
2146	livorno	terrazza-mascagni-livorno
2185	giglio	isola-del-giglio-porto
2189	venezia	venezia-vista-del-canal-grande
2197	gela	gela-vista-sulla-spiaggia-di-manfria
2197	sila	camigliatello-silano-e-monte-curcio
2275	rimini	rimini-bagno-39
2277	ossola	ornavasso-vista-sulla-val-dossola
2297	ischia	ischia-porto
2297	napoli	napoli-vista-sul-golfo-e-sul-vesuvio
22	auronzo	rifugio-auronzo-sulla-forcella-longeres
2343	capri	capri
2343	ischia	ischia-porto
2351	morandi	ponte-san-giorgio-genova-ex-ponte-morandi
2353	sant'angelo	monte-santangelo
2375	genova	genova-porto-antico
2385	fumaiolo	monte-fumaiolo
2389	venezia	venezia-vista-del-canal-grande
2415	genova	genova-porto-antico
2424	fano	marotta-di-fano-vista-sulla-spiaggia
2424	napoli	napoli-vista-sul-golfo-e-sul-vesuvio
2426	domodossola	domodossola-vista-sul-paese-e-sulla-val-dossola
2426	ossola	ornavasso-vista-sulla-val-dossola
2431	sant'angelo	monte-santangelo
2435	perugia	ponte-doddi-perugia
2483	imperia	piaggia-di-mendatica-imperia
2483	pisa	pisa-tuttomondo-di-keith-haring
2513	padova	padova-vista-sul-centro-sulla-basilica-s-antonio
2513	siena	siena-vico-alto
2572	venezia	venezia-vista-del-canal-grande
2596	teramo	webcam-tortoreto-di-teramo
2604	sant'angelo	monte-santangelo
2615	ischia	ischia-porto
2615	positano	positano-costiera-amalfitana
2638	livorno	terrazza-mascagni-livorno
2655	monterosso	monterosso-cinque-terre
2655	ossola	ornavasso-vista-sulla-val-dossola
2656	carloforte	carloforte-isola-di-san-pietro
2656	pisa	pisa-tuttomondo-di-keith-haring
2673	maddalena	la-maddalena-vista-isola-santo-stefano
2705	lecce	maluha-bay-torre-chianca-lecce
2719	urbino	urbino-osservatorio
2725	genova	genova-porto-antico
2748	capanne	monte-capanne-isola-delba
2785	domodossola	domodossola-vista-sul-paese-e-sulla-val-dossola
2813	sassolungo	santa-cristina-vista-sul-sassolungo-e-sullla-pista-saslong
2893	firenze	firenze-vista-su-ponte-vecchio
2893	malghera	rifugio-malghera-e-chiesa-di-malghera-valgrosina
2916	avio	avio-vallagarina
2965	mantova	rifugio-citta-di-mantova
2965	pisa	pisa-tuttomondo-di-keith-haring
2965	ravenna	punta-marina-terme-di-ravenna
3005	ischia	ischia-porto
3014	livorno	terrazza-mascagni-livorno
3014	tirrenia	tirrenia-vista-sulla-spiaggia
3016	trapani	trapani-stazione-marittima
317	cervinia	cervinia-area-plan-maison
3204	napoli	napoli-vista-sul-golfo-e-sul-vesuvio
3264	gemelli	rifugio-laghi-gemelli
3298	ossola	ornavasso-vista-sulla-val-dossola
3357	fassa	val-di-fassa-vista-sul-catinaccio
3368	ossola	ornavasso-vista-sulla-val-dossola
3401	etna	etna
3405	nido	pirellone-grattacielo-pirelli-nido-falchi-pellegrini
345	consuma	passo-della-consuma
3611	cervinia	cervinia-area-plan-maison
3653	sanremo	sanremo-portosole
3712	genova	genova-porto-antico
3803	piombino	salivoli-piombino-vista-sullisola-delba
396	genova	genova-porto-antico
396	pisa	pisa-tuttomondo-di-keith-haring
4503	cavallaro	monte-cimone-vista-su-pian-cavallaro
451	napoli	napoli-vista-sul-golfo-e-sul-vesuvio
451	pescara	pescara-vista-da-colle-marino
4553	cansiglio	pian-cansiglio
4567	civetta	ristoro-belvedere-a-cima-fertazza-vista-sul-civetta
4567	urbino	urbino-osservatorio
4665	napoli	napoli-vista-sul-golfo-e-sul-vesuvio
4787	firenze	firenze-vista-su-ponte-vecchio
481	livorno	terrazza-mascagni-livorno
4917	venezia	venezia-vista-del-canal-grande
4994	sassolungo	santa-cristina-vista-sul-sassolungo-e-sullla-pista-saslong
537	etna	etna
6920	sant'angelo	monte-santangelo
6930	pescara	pescara-vista-da-colle-marino
6949	firenze	firenze-vista-su-ponte-vecchio
6956	bormio	pista-stelvio-di-bormio
6992	perugia	ponte-doddi-perugia
718	capri	capri
718	genova	genova-porto-antico
718	pisa	pisa-tuttomondo-di-keith-haring
7263	asiago	asiago-vista-sullaltopiano
7280	misurina	lago-di-misurina
7464	livorno	terrazza-mascagni-livorno
7496	adamello	adamello-ghiacciaio-presena
7496	brembana	alta-val-brembana-vista-del-paese-piazzatorre
7498	alben	monte-alben-nelle-prealpi-orobie
7498	bernina	passo-del-bernina
7498	cervino	cervino-plateau-rosa
7516	teramo	webcam-tortoreto-di-teramo
7533	lanciano	passo-lanciano
7533	sangro	castel-di-sangro-aq
7635	pescasseroli	pescasseroli-vista-sul-paese
7666	napoli	napoli-vista-sul-golfo-e-sul-vesuvio
7742	ossola	ornavasso-vista-sulla-val-dossola
7750	carpegna	eremo-monte-carpegna
7750	fano	marotta-di-fano-vista-sulla-spiaggia
7750	perugia	ponte-doddi-perugia
7750	sant'andrea	isola-delba-capo-santandrea
7750	sant'angelo	monte-santangelo
7756	firenze	firenze-vista-su-ponte-vecchio
7756	versilia	lido-di-camaiore-in-versilia
7776	padova	padova-vista-sul-centro-sulla-basilica-s-antonio
7776	venezia	venezia-vista-del-canal-grande
7788	ravenna	punta-marina-terme-di-ravenna
7799	genova	genova-porto-antico
7825	calvario	pescocostanzo-vista-sul-monte-calvario
7874	cascia	cascia-vista-sul-paese
7889	livorno	calignaia-livorno
7919	modena	modena-vista-sulla-ghirlandina
7934	padova	padova-vista-sul-centro-sulla-basilica-s-antonio
8005	civetta	ristoro-belvedere-a-cima-fertazza-vista-sul-civetta
8055	mantova	rifugio-citta-di-mantova
8070	civetta	ristoro-belvedere-a-cima-fertazza-vista-sul-civetta
8070	perugia	ponte-doddi-perugia
8091	civetta	ristoro-belvedere-a-cima-fertazza-vista-sul-civetta
8091	lagazuoi	rifugio-lagazuoi-vista-sulle-tofane
8115	padova	padova-vista-sul-centro-sulla-basilica-s-antonio
8240	venezia	venezia-vista-del-canal-grande
8265	fanano	fanano
8300	zoldo	rifugio-palafavera-val-di-zoldo
8310	locatelli	rifugio-locatelli-tre-cime-di-lavaredo
8332	resia	lago-di-resia
8356	genova	genova-porto-antico
8393	ercole	porto-ercole-vista-sul-porto
842	grigna	grigna-vista-dal-rifugio-brioschi
8556	nana	rifugio-ferraro-vista-su-champoluc-e-la-valle-di-nana
8556	venezia	venezia-vista-del-canal-grande
8637	genova	genova-porto-antico
8637	pisa	pisa-tuttomondo-di-keith-haring
8637	verna	la-verna-vista-sul-santuario-sul-sagrato-e-sulla-vallata
8648	genova	genova-porto-antico
8648	sestriere	sestriere-vista-sul-paese
8662	agordino	rifugio-scarpa-vista-sullagordino
8672	sant'andrea	isola-delba-capo-santandrea
8794	cervino	cervino-plateau-rosa
8860	piombino	salivoli-piombino-vista-sullisola-delba
8882	finlandia	rovaniemi-finlandia
8882	pusteria	monguelfo-in-val-pusteria
8900	bernina	passo-del-bernina
8900	valfurva	santa-caterina-valfurva
8962	misurina	lago-di-misurina
8962	venezia	venezia-vista-del-canal-grande
9070	civetta	ristoro-belvedere-a-cima-fertazza-vista-sul-civetta
9070	modena	modena-vista-sulla-ghirlandina
9070	urbino	urbino-osservatorio
9089	napoli	napoli-vista-sul-golfo-e-sul-vesuvio
9185	napoli	napoli-vista-sul-golfo-e-sul-vesuvio
9225	cattolica	cattolica-vista-sulla-spiaggia
9225	urbino	urbino-osservatorio
9261	siena	siena-vico-alto
9341	gallipoli	gallipoli-riviera-bastiano-sauro
9341	lecce	maluha-bay-torre-chianca-lecce
939	sestriere	sestriere-vista-sul-paese
9431	firenze	firenze-vista-su-ponte-vecchio
9443	versilia	lido-di-camaiore-in-versilia
9586	livigno	livigno-vista-sul-paese
9647	maddalena	la-maddalena-vista-isola-santo-stefano
9666	venezia	venezia-vista-del-canal-grande
9674	civetta	ristoro-belvedere-a-cima-fertazza-vista-sul-civetta
9674	marmolada	marmolada
9674	zoldo	rifugio-palafavera-val-di-zoldo
9810	arbatax	arbatax-vista-sugli-scogli-rossi
9810	pisa	pisa-tuttomondo-di-keith-haring
987	abetone	abetone-campo-scuola-e-piazzale
987	firenze	firenze-vista-su-ponte-vecchio
987	venezia	venezia-vista-del-canal-grande
999	morgex	foyer-du-fond-arpy-morgex
MWTITEMS;
	$items = array();
	foreach ( preg_split( '/\n/', $s ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		$parts = explode( "\t", $line, 3 );
		if ( 3 === count( $parts ) ) {
			$items[] = array( (int) $parts[0], $parts[1], $parts[2] );
		}
	}
	return $items;
}

function mwt_link_find( $content, $phrase ) {
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

/*
 * Normalizzazione per il confronto anchor/frase chiave: minuscole,
 * punteggiatura e simboli → spazi, spazi multipli accorpati.
 */
function mwt_link_norm_phrase( $s ) {
	$s = mb_strtolower( (string) $s, 'UTF-8' );
	$s = preg_replace( '/[\pP\pS]+/u', ' ', (string) $s );
	return trim( (string) preg_replace( '/\s+/u', ' ', (string) $s ) );
}

/*
 * Controllo "link concorrenti" di Yoast: vero se la frase chiave del post
 * compare integralmente nell'anchor del nuovo link (confini di parola).
 */
function mwt_link_competing( $post_id, $phrase ) {
	$kw = mwt_link_norm_phrase( get_post_meta( $post_id, '_yoast_wpseo_focuskw', true ) );
	if ( '' === $kw ) {
		return false;
	}
	$an = mwt_link_norm_phrase( $phrase );
	if ( '' === $an ) {
		return false;
	}
	return false !== mb_strpos( ' ' . $an . ' ', ' ' . $kw . ' ', 0, 'UTF-8' );
}

function mwt_link_rollout_run() {
	if ( ! isset( $_GET['mwt_link_rollout'] ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Solo gli amministratori possono usare questo strumento.' );
	}
	$go        = isset( $_GET['go'] ) && '1' === $_GET['go'];
	$undo      = isset( $_GET['undo'] ) && '1' === $_GET['undo'];
	$has_batch = isset( $_GET['batch'] );
	$batch     = $has_batch ? max( 1, (int) $_GET['batch'] ) : 1;
	header( 'Content-Type: text/plain; charset=utf-8' );
	global $wpdb;

	$items   = mwt_link_rollout_items();
	$per     = 50;
	$total   = count( $items );
	$nbat    = (int) ceil( $total / $per );
	$applied = get_option( 'mwt_link_rollout_applied', array() );
	if ( ! is_array( $applied ) ) {
		$applied = array();
	}

	// Modo riepilogo: senza batch/go/undo stampa lo stato di tutti i lotti.
	if ( ! $go && ! $undo && ! $has_batch ) {
		echo "== Rollout link interni contestuali — riepilogo ==\n";
		echo "Righe totali: {$total} in {$nbat} lotti da {$per}\n";
		echo "Link applicati finora: " . count( $applied ) . "\n\n";
		$cnt = array();
		foreach ( $applied as $rec ) {
			$b = (int) substr( $rec, 0, strpos( $rec, '|' ) );
			if ( ! isset( $cnt[ $b ] ) ) {
				$cnt[ $b ] = 0;
			}
			$cnt[ $b ]++;
		}
		for ( $b = 1; $b <= $nbat; $b++ ) {
			$done = isset( $cnt[ $b ] ) ? $cnt[ $b ] : 0;
			$size = (int) min( $per, $total - ( $b - 1 ) * $per );
			$mark = ( $done < $size ) ? '  ← da completare' : '  OK';
			echo "  lotto {$b}: {$done}/{$size} applicati{$mark}\n";
		}
		echo "\nUso: ?mwt_link_rollout=1&batch=N (dry-run) → aggiungi &go=1 per applicare → &undo=1&batch=N rimuove il lotto N → &undo=1 rimuove tutto.\n";
		exit;
	}

	if ( $batch > $nbat ) {
		echo "Lotto {$batch} inesistente: i lotti vanno da 1 a {$nbat}.\n";
		exit;
	}

	$mode = $undo ? 'UNDO (rimuove i link applicati)' : ( $go ? 'APPLICA' : 'DRY-RUN (nessuna scrittura)' );
	echo "== Rollout link interni — lotto {$batch}/{$nbat} — {$mode} ==\n\n";

	$done = 0;
	if ( $undo ) {
		$kept = array();
		foreach ( $applied as $rec ) {
			$p = explode( '|', $rec, 4 );
			if ( 4 !== count( $p ) ) {
				continue;
			}
			if ( $has_batch && (int) $p[0] !== $batch ) {
				$kept[] = $rec;
				continue;
			}
			$id     = (int) $p[1];
			$phrase = $p[2];
			$slug   = $p[3];
			$post   = get_post( $id );
			if ( ! $post ) {
				echo "[{$id}] SKIP: post non trovato (registrazione scartata: \"{$phrase}\")\n";
				continue;
			}
			$href    = 'https://www.meteo-webcam.it/' . $slug . '/';
			$pattern = '#<a href="' . preg_quote( $href, '#' ) . '">(' . preg_quote( $phrase, '#' ) . ')</a>#ui';
			$new     = preg_replace( $pattern, '$1', $post->post_content );
			if ( null === $new || $new === $post->post_content ) {
				echo "[{$id}] niente da rimuovere (\"{$phrase}\" → {$slug})\n";
				continue;
			}
			$ok = $wpdb->update( $wpdb->posts, array( 'post_content' => $new ), array( 'ID' => $id ) );
			if ( false === $ok ) {
				echo "[{$id}] ERRORE: update wpdb fallito (registrazione mantenuta)\n";
				$kept[] = $rec;
				continue;
			}
			if ( function_exists( 'w3tc_flush_post' ) ) {
				w3tc_flush_post( $id );
			}
			$done++;
			echo "[{$id}] RIMOSSO: \"{$phrase}\" → {$slug}\n";
		}
		update_option( 'mwt_link_rollout_applied', array_values( $kept ) );
		$scope = $has_batch ? "lotto {$batch}" : 'TUTTI i lotti';
		echo "\nUndo completato: {$done} link rimossi ({$scope}). Riepilogo: ?mwt_link_rollout=1\n";
		exit;
	}

	// Dry-run / go del lotto.
	$slice    = array_slice( $items, ( $batch - 1 ) * $per, $per );
	$registry = array();
	foreach ( $applied as $rec ) {
		$p = explode( '|', $rec, 4 );
		if ( 4 === count( $p ) ) {
			$registry[ $p[1] . '|' . $p[2] . '|' . $p[3] ] = (int) $p[0];
		}
	}
	foreach ( $slice as $item ) {
		$id     = (int) $item[0];
		$phrase = $item[1];
		$slug   = $item[2];
		$key    = $id . '|' . $phrase . '|' . $slug;
		if ( isset( $registry[ $key ] ) ) {
			echo "[{$id}] SKIP: gia' applicato nel lotto " . $registry[ $key ] . " (\"{$phrase}\")\n";
			continue;
		}
		$post = get_post( $id );
		if ( ! $post || 'publish' !== $post->post_status ) {
			echo "[{$id}] SKIP: post non trovato o non pubblicato\n";
			continue;
		}
		$kw_raw = (string) get_post_meta( $id, '_yoast_wpseo_focuskw', true );
		if ( '' !== $kw_raw && mwt_link_competing( $id, $phrase ) ) {
			echo "[{$id}] SKIP: link concorrente Yoast — l'anchor \"{$phrase}\" contiene la frase chiave del post (\"{$kw_raw}\")\n";
			continue;
		}
		$href = 'https://www.meteo-webcam.it/' . $slug . '/';
		if ( false !== strpos( $post->post_content, $href ) ) {
			echo "[{$id}] SKIP: link a {$slug} gia' presente nel post\n";
			continue;
		}
		$pos = mwt_link_find( $post->post_content, $phrase );
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
			$applied[] = $batch . '|' . $id . '|' . $phrase . '|' . $slug;
			$done++;
			echo "[{$id}] INSERITO: \"{$raw}\" → {$slug}\n    contesto: …{$ctx}…\n";
		} else {
			echo "[{$id}] applicherebbe: \"{$raw}\" → {$slug}\n    contesto: …{$ctx}…\n";
		}
	}
	if ( $go ) {
		update_option( 'mwt_link_rollout_applied', $applied );
		$tot = count( $applied );
		echo "\nLotto {$batch} completato: {$done} link inseriti (totale registrato: {$tot}).\n";
		if ( $batch < $nbat ) {
			$next = $batch + 1;
			echo "Prossimo: ?mwt_link_rollout=1&batch={$next} (dry-run), poi aggiungi &go=1.\n";
		} else {
			echo "Ultimo lotto: rollout completo. Verifica su Yoast alcune pagine toccate, poi rimuovere lo snippet (v2.3 pulita).\n";
		}
	} else {
		echo "\nDry-run del lotto {$batch} completato. Per applicare: ?mwt_link_rollout=1&batch={$batch}&go=1\n";
	}
	exit;
}
add_action( 'admin_init', 'mwt_link_rollout_run' );
