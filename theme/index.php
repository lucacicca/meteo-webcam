<?php
/**
 * Meteo Webcam Theme — index.php
 * Template unico: copre home, archivi, pagina singola, pagina statica, 404.
 * Header e footer sono inline per non moltiplicare i file (e le richieste
 * non cambiano: WordPress carica lo stesso un solo tema per richiesta).
 *
 * Le immagini live delle webcam arrivano dallo shortcode [mwp_webcam] del
 * plugin Meteo Webcam Proxy dentro the_content(): il tema non fa nulla di
 * speciale, serve solo largo e senza CLS (vedi style.css).
 *
 * v2.9: nelle categorie con sottocategorie (regione, zona geografica,
 * ambiente...) compare prima la griglia delle figlie (mwt_subcategories).
 *
 * v2.7: la prima card della griglia (home/archivi) esce eager + fetchpriority
 * high, le altre lazy.
 *
 * v2.6: ricerca nell'header (icona lente che apre un overlay col form, ~15
 * righe di JS inline: nessun asset esterno); nel post singolo compaiono la
 * nav webcam precedente/successiva (mwt_post_nav) e il form di ricerca
 * (mwt_post_search).
 *
 * @package meteo-webcam
 */

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
	<div class="wrap site-header-inner">
		<a class="site-title" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>
		<nav class="site-nav" aria-label="<?php esc_attr_e( 'Menu principale', 'meteo-webcam' ); ?>">
			<?php
			wp_nav_menu( array(
				'theme_location' => 'primary',
				'container'      => false,
				'fallback_cb'    => 'mwt_menu_fallback',
				'depth'          => 1,
			) );
			?>
			<button type="button" class="search-toggle" aria-expanded="false" aria-controls="mwt-search-overlay">
				<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
					<circle cx="11" cy="11" r="7"></circle>
					<line x1="16.5" y1="16.5" x2="21" y2="21"></line>
				</svg>
				<span class="screen-reader-text"><?php esc_html_e( 'Cerca nel sito', 'meteo-webcam' ); ?></span>
			</button>
		</nav>
	</div>
</header>

<div id="mwt-search-overlay" class="search-overlay" hidden>
	<div class="wrap">
		<?php get_search_form(); ?>
	</div>
</div>

<main class="wrap site-main">

<?php if ( have_posts() ) : ?>

	<?php if ( is_singular() ) : ?>

		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'post' ); ?>>
				<h1 class="post-title"><?php the_title(); ?></h1>
				<p class="post-meta">
					<?php
					printf(
						/* translators: 1: data di pubblicazione */
						esc_html__( 'Pubblicato il %s', 'meteo-webcam' ),
						esc_html( get_the_date() )
					);
					?>
				</p>

				<?php
				if ( has_post_thumbnail() ) {
					the_post_thumbnail(
						'large',
						array( 'class' => 'featured-img' )
					);
				}
				?>

				<?php mwt_ad_slot_before_content(); ?>

				<div class="post-content">
					<?php
					the_content();
					wp_link_pages(
						array(
							'before' => '<nav class="pagination">',
							'after'  => '</nav>',
						)
					);
					?>
				</div>

				<?php mwt_post_nav(); ?>
				<?php mwt_post_search(); ?>
			</article>

			<?php
			if ( comments_open() || get_comments_number() ) {
				comments_template();
			}
			?>
		<?php endwhile; ?>

	<?php else : ?>

		<h1 class="page-title">
			<?php
			if ( is_search() ) {
				printf(
					/* translators: %s: termine cercato */
					esc_html__( 'Risultati per: %s', 'meteo-webcam' ),
					'<strong>' . esc_html( get_search_query() ) . '</strong>'
				);
			} elseif ( is_archive() ) {
				the_archive_title();
			} elseif ( is_home() ) {
				esc_html_e( 'Ultime webcam e segnalazioni', 'meteo-webcam' );
			}
			?>
		</h1>

		<?php
		// v2.9: nelle categorie con figli, prima la griglia delle sottocategorie.
		// Se c'e', l'immagine sopra la piega e' li': i post partono tutti lazy.
		$mwt_has_subcats = mwt_subcategories();
		?>

		<div class="cards">
			<?php
			$mwt_first_card = ! $mwt_has_subcats;
			while ( have_posts() ) :
				the_post();
				?>
				<article id="post-<?php the_ID(); ?>" <?php post_class( 'card' ); ?>>
					<a class="card-thumb" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
						<?php
						if ( has_post_thumbnail() ) {
							// v2.7: la prima card e' l'LCP della pagina: eager + priorita' alta.
							// Le altre: lazy (default di WordPress).
							the_post_thumbnail(
								'mwt-card',
								$mwt_first_card ? array( 'loading' => 'eager', 'fetchpriority' => 'high' ) : array( 'loading' => 'lazy' )
							);
						}
						$mwt_first_card = false;
						?>
					</a>
					<div class="card-body">
						<h2 class="card-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
						<p class="card-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 24, '…' ) ); ?></p>
						<p class="card-meta">
							<?php echo esc_html( get_the_date() ); ?>
							<?php
							$cats = get_the_category_list( ', ' );
							if ( $cats ) {
								echo ' · ' . wp_kses_post( $cats );
							}
							?>
						</p>
					</div>
				</article>
			<?php endwhile; ?>
		</div>

		<?php
		the_posts_pagination( array(
			'mid_size'  => 1,
			'prev_text' => '‹ ' . __( 'Precedenti', 'meteo-webcam' ),
			'next_text' => __( 'Successivi', 'meteo-webcam' ) . ' ›',
		) );
		?>

		<?php if ( is_active_sidebar( 'footer-widgets' ) ) : ?>
			<div class="widget-area">
				<?php dynamic_sidebar( 'footer-widgets' ); ?>
			</div>
		<?php endif; ?>

	<?php endif; ?>

<?php else : ?>

	<div class="no-results">
		<h1 class="post-title"><?php esc_html_e( 'Nessun contenuto trovato', 'meteo-webcam' ); ?></h1>
		<p><?php esc_html_e( 'Prova con una ricerca o torna alla home.', 'meteo-webcam' ); ?></p>
		<?php get_search_form(); ?>
		<p><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( '← Torna alla home', 'meteo-webcam' ); ?></a></p>
	</div>

<?php endif; ?>

</main>

<footer class="site-footer">
	<div class="wrap">
		<span>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></span>
		<span><?php bloginfo( 'description' ); ?></span>
	</div>
</footer>

<?php wp_footer(); ?>

<script>
(function () {
	var toggle = document.querySelector('.search-toggle');
	var overlay = document.getElementById('mwt-search-overlay');
	if (!toggle || !overlay) { return; }
	function open() { overlay.hidden = false; toggle.setAttribute('aria-expanded', 'true'); var f = overlay.querySelector('input[type="search"]'); if (f) { f.focus(); } }
	function close() { overlay.hidden = true; toggle.setAttribute('aria-expanded', 'false'); toggle.focus(); }
	toggle.addEventListener('click', function () { overlay.hidden ? open() : close(); });
	document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !overlay.hidden) { close(); } });
	document.addEventListener('click', function (e) {
		if (!overlay.hidden && !overlay.contains(e.target) && !toggle.contains(e.target)) { close(); }
	});
})();
</script>
</body>
</html>
