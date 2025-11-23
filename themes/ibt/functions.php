<?php
/**======================================================
 * ibt — Theme functions
 * ====================================================== */

// Core supports and editor styles.
add_action( 'after_setup_theme', function () {
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'responsive-embeds' );
	// theme supports editor styles. Handled in dev/prod block at end of file.
	add_theme_support( 'align-wide' );
	add_theme_support( 'comments' );
    add_theme_support( 'custom-line-height' );
    add_theme_support( 'custom-spacing' );

	// WooCommerce basics.
    // Remove zoom (doesn't suit theme)
    // Remove slider (single image per product so adds weight without value)
    // See WOO_FILTERS section for enforecement (otherwise Woo loads them anyway)
	add_theme_support( 'woocommerce' );
	//add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	//add_theme_support( 'wc-product-gallery-slider' );

	// Modern HTML5 markup.
	add_theme_support( 'html5', [
		'comment-form', 'comment-list', 'search-form', 'gallery', 'caption', 'style', 'script'
	] );
} );

// ----- WOO_FILTERS -----
// Explicit filters because some Woo templates enque even without theme support declaration

// Disable WooCommerce image zoom on single product pages (not needed for book covers).
add_filter( 'woocommerce_single_product_zoom_enabled', '__return_false', 20 );

// Disable WooCommerce FlexSlider assets on single product pages
add_action( 'wp_enqueue_scripts', function() {
	if ( is_product() ) {
		wp_dequeue_script( 'flexslider' );
		wp_deregister_script( 'flexslider' );
		wp_dequeue_style( 'woocommerce_flexslider_css' );
		wp_deregister_style( 'woocommerce_flexslider_css' );
	}
}, 30 );

// ----------------------

//  Register IBT button styles.
add_action( 'init', function() {

	register_block_style(
		'core/button',
		array(
			'name'  => 'primary-solid',
			'label' => __( 'Primary Solid', 'ibt' ),
		)
	);
	register_block_style(
		'core/button',
		array(
			'name'  => 'primary-outline',
			'label' => __( 'Primary Outline', 'ibt' ),
		)
	);
	register_block_style(
		'core/button',
		array(
			'name'  => 'buy-solid',
			'label' => __( 'Buy Solid', 'ibt' ),
		)
	);
	register_block_style(
		'core/button',
		array(
			'name'  => 'buy-outline',
			'label' => __( 'Buy Outline', 'ibt' ),
		)
	);

}, 20 );

// Add title + aria-label to Woo Account icon. 
// Note - Cart doesn't work, js or css required if ever needed.
//        Woo default meets standards requirements for the cart.
add_filter( 'render_block', function( $block_content, $block ) {
	if ( 'woocommerce/customer-account' === $block['blockName'] ) {
		if ( str_contains( $block_content, '<a' ) ) {
			$block_content = str_replace(
				'<a',
				'<a title="Your account" aria-label="Your account"',
				$block_content
			);
		}
	}
	return $block_content;
}, 10, 2 );


/**
 * IBT Navigation – Active / Ancestor / Virtual-Ancestor Highlighter
 * ------------------------------------------------------------------
 * Adds data-ibt-state attributes (active, ancestor, virtual-ancestor) to WordPress
 * navigation markup so CSS can highlight the current page's parent menu.
 */

add_filter('render_block', 'ibt_highlight_navigation', 10, 2);

function ibt_highlight_navigation($content, $block) {
    if (empty($block['blockName']) || $block['blockName'] !== 'core/navigation') {
        return $content;
    }

    $class = $block['attrs']['className'] ?? '';
    if (strpos($class, 'ibt-header-nav-desktop') === false) {
        return $content;
    }

    // --- Config: alias prefixes -------------------------------------------
    // Purpose: remap URL prefixes for section parents without pages.
    // Example: '/more' => '' means /more/about behaves like /about.
    // Add or remove entries as site structure changes.
    $alias_map = [
        '/more' => '',   // toggle off later if not needed
    ];

    // --- Current path (original + normalised) -----------------------------
    $original_path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    $original_path = strtolower(rtrim($original_path, '/'));
    if ($original_path === '') $original_path = '/';

    $current_path = $original_path;
    foreach ($alias_map as $prefix => $replacement) {
        if ($prefix !== '' && str_starts_with($current_path, $prefix . '/')) {
            $current_path = $replacement . substr($current_path, strlen($prefix));
            if ($current_path === '') $current_path = '/';
            $current_path = strtolower(rtrim($current_path, '/'));
            if ($current_path === '') $current_path = '/';
            break; // apply first matching alias only
        } elseif ($current_path === $prefix) {
            $current_path = $replacement ?: '/';
        }
    }

    // --- Gather all <a class="...wp-block-navigation-item__content..."> ----
    if (!preg_match_all('/<a[^>]*>/i', $content, $all_a_tags)) {
        return $content;
    }

    $anchors = [];
    foreach ($all_a_tags[0] as $tag) {
        if (!preg_match('/class="[^"]*\bwp-block-navigation-item__content\b[^"]*"/i', $tag)) continue;
        if (!preg_match('/href="([^"]+)"/i', $tag, $m)) continue;

        $href = $m[1];
        $is_internal = str_starts_with($href, '/') || str_starts_with($href, home_url('/'));
        if (!$is_internal) continue;

        $link_path = parse_url($href, PHP_URL_PATH) ?? '/';
        $link_path = strtolower(rtrim($link_path, '/'));
        if ($link_path === '') $link_path = '/';

        $anchors[] = [
            'full' => $tag,
            'href' => $href,
            'path' => $link_path,
        ];
    }

    // Helper: add data-ibt-state="X" to a specific <a ...> opening tag
    $add_state = function (string $tag, string $state) {
        return preg_replace('/>$/', ' data-ibt-state="' . $state . '">', $tag, 1);
    };

    $state = 'none';

    // --- 1) Exact match ---------------------------------------------------
    foreach ($anchors as $a) {
        if ($a['path'] === $current_path) {
            $content = str_replace($a['full'], $add_state($a['full'], 'active'), $content);
            $state = 'active';
            break;
        }
    }

    // --- 2) Ancestor match (prefix + segment boundary; skip root) ---------
    if ($state === 'none') {
        foreach ($anchors as $a) {
            if ($a['path'] === '/' || $a['path'] === '') continue;
            $prefix = $a['path'] . '/';
            if (str_starts_with($current_path, $prefix)) {
                $content = str_replace($a['full'], $add_state($a['full'], 'ancestor'), $content);
                $state = 'ancestor';
                break;
            }
        }
    }

    // --- 3) Virtual ancestor (submenu parent button highlight) ------------
    if (preg_match_all('/<li[^>]+has-child[^>]*>.*?<button[^>]*>.*?<\/button>/is', $content, $subs)) {
        foreach ($subs[0] as $submenu) {
            if (preg_match('/data-ibt-state="(?:active|ancestor)"/i', $submenu)) {
                $new = preg_replace(
                    '/(<button[^>]*)(>)/i',
                    '$1 data-ibt-state="ancestor"$2',
                    $submenu,
                    1
                );
                $content = str_replace($submenu, $new, $content);
                $state = 'virtual-ancestor';
            }
        }
    }

    // --- Optional log (uncomment for debugging) ---------------------------
    // error_log('[IBT NAV] state=' . $state . ' path=' . $current_path);

    return $content;
}


/**
 * IBT Favicons — inject favicon & app icon metadata into <head>.
 * Keeps icons portable across sandbox, staging and production.
 */
add_action( 'wp_head', function () {

    $base = get_stylesheet_directory_uri() . '/assets/icons';

    ?>
    <!-- IBT Favicons -->
    <link rel="icon" href="<?php echo esc_url( $base . '/favicon.ico' ); ?>" sizes="any" type="image/x-icon">
    <link rel="icon" href="<?php echo esc_url( $base . '/favicon.svg' ); ?>" type="image/svg+xml">
    <link rel="icon" href="<?php echo esc_url( $base . '/favicon-32.png' ); ?>" sizes="32x32" type="image/png">
    <link rel="icon" href="<?php echo esc_url( $base . '/favicon-96.png' ); ?>" sizes="96x96" type="image/png">
    <link rel="icon" href="<?php echo esc_url( $base . '/favicon-192.png' ); ?>" sizes="192x192" type="image/png">
    <link rel="apple-touch-icon" href="<?php echo esc_url( $base . '/apple-touch-icon.png' ); ?>">
    <link rel="manifest" href="<?php echo esc_url( $base . '/site.webmanifest' ); ?>">
    <meta name="theme-color" content="#ffffff">
    <!-- End IBT Favicons -->
    <?php
});

/**
 * Disable WordPress’s default Site Icon injection.
 * We provide our own favicon tags above.
 */
add_filter( 'site_icon_meta_tags', '__return_empty_array' );


/* ========================================================
   PRODUCTION ASSET LOADER
   Enable ONLY in release branches
   Loads ibt.min.css + ibt-editor.css (versioned)
   Loads ibt-header.js (versioned)
   ======================================================== */


// Define a version based on current version in style.css
if ( ! defined( 'IBT_VERSION' ) ) {
    $theme = wp_get_theme( get_template() );
    define( 'IBT_VERSION', $theme ? $theme->get( 'Version' ) : '0.0.0' );
}


add_action( 'after_setup_theme', function () {
    add_theme_support( 'editor-styles' );
    add_editor_style( [ 'assets/css/ibt.min.css', 'assets/css/ibt-editor.css' ] );
} );

add_action( 'wp_enqueue_scripts', function () {

    // ----- Front-end CSS (ibt.min.css, versioned) -----
    wp_enqueue_style(
        'ibt-theme',
        get_stylesheet_directory_uri() . '/assets/css/ibt.min.css',
        [],
        IBT_VERSION
    );

    // ----- Header JS (versioned) -----
    wp_enqueue_script(
        'ibt-header',
        get_stylesheet_directory_uri() . '/assets/js/ibt-header.js',
        [ 'wp-dom-ready' ],
        IBT_VERSION,
        true
    );

}, 20 );