<?php
/**======================================================
 * ibt — Theme functions
 * DEV VERSION WITH CACHE BUSTER - CHANGE BEFORE RELEASE
 * ====================================================== */

// Define a version for cache-busting.
if ( ! defined( 'IBT_VERSION' ) ) {
	$theme = wp_get_theme( get_template() );
	$ver   = $theme ? $theme->get( 'Version' ) : null;
	define( 'IBT_VERSION', $ver ?: time() );
}

// Core supports and editor styles.
add_action( 'after_setup_theme', function () {
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/ibt.css' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'comments' );

	// WooCommerce basics.
	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );

	// Modern HTML5 markup.
	add_theme_support( 'html5', [
		'comment-form', 'comment-list', 'search-form', 'gallery', 'caption', 'style', 'script'
	] );
} );


// Load ibt.css with cache buster version number.
add_action( 'wp_enqueue_scripts', function () {
	$rel  = 'assets/css/ibt.css';
	$path = get_stylesheet_directory() . '/' . $rel;
	$ver  = file_exists( $path ) ? filemtime( $path ) : IBT_VERSION;

	wp_enqueue_style(
		'islands-book-trust',
		get_stylesheet_directory_uri() . '/' . $rel,
		[],
		$ver
	);
}, 20 );


// Load ibt-header.js for header search toggle
add_action( 'wp_enqueue_scripts', function () {
	$rel  = 'assets/js/ibt-header.js';
	$path = get_stylesheet_directory() . '/' . $rel;
	$ver  = file_exists( $path ) ? filemtime( $path ) : IBT_VERSION;

	wp_enqueue_script(
		'ibt-header',
		get_stylesheet_directory_uri() . '/' . $rel,
		[],     // no dependencies
		$ver,   // version from filemtime (cache-bust in dev)
		true    // load in footer
	);
}, 20 );


// Register custom Button style variations
add_action( 'init', function() {
	register_block_style(
		'core/button',
		array(
			'name'  => 'buy-solid',
			'label' => __( 'Buy Solid', 'ibt' )
		)
	);
	register_block_style(
		'core/button',
		array(
			'name'  => 'buy-outline',
			'label' => __( 'Buy Outline', 'ibt' )
		)
	);
} );


// Add title + aria-label to Woo Account icon. 
// Note - Cart doesn't work, js or css required if ever needed.
//        Woo default meets standards requirements for the cart.
add_filter( 'render_block', function( $block_content, $block ) {
	if ( 'woocommerce/customer-account' === $block['blockName'] ) {
		$block_content = str_replace(
			'<a',
			'<a title="Your account" aria-label="Your account"',
			$block_content
		);
	}
	return $block_content;
}, 10, 2 );






/**
 * IBT Navigation: mark links as active/ancestor for CSS :has()
 * Verbose logging + optional alias mapping (e.g., "/more" → "")
 */

add_filter('render_block', function ($content, $block) {
    if (empty($block['blockName']) || $block['blockName'] !== 'core/navigation') {
        return $content;
    }
    $class = $block['attrs']['className'] ?? '';
    if (strpos($class, 'ibt-header-nav-desktop') === false) {
        return $content;
    }

    // --- Config: alias prefixes (apply to current path before matching) ---
    // Example: '/more' => '' turns /more/about into /about for comparison
    $alias_map = [
        '/more' => '',   // <— toggle this off later if you don’t want it
    ];

    // --- Current path (original + normalised) ---
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

    // --- Gather all <a class="...wp-block-navigation-item__content..."> ---
    if (!preg_match_all('/<a[^>]*>/i', $content, $all_a_tags)) {
        error_log('[IBT NAV] no <a> tags found');
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

    // --- Logging: list candidates ---
    error_log('====[IBT NAV] start ====');
    error_log('[IBT NAV] original=' . $original_path . ' normalised=' . $current_path);
    foreach ($anchors as $i => $a) {
        error_log(sprintf('[IBT NAV] link %02d: href=%s path=%s', $i + 1, $a['href'], $a['path']));
    }

    // Helper: add data-ibt-state="X" to a specific <a ...> opening tag
    $add_state = function (string $tag, string $state) {
        return preg_replace('/>$/', ' data-ibt-state="' . $state . '">', $tag, 1);
    };

    // --- 1) Exact match ---
    foreach ($anchors as $i => $a) {
        error_log(sprintf('[IBT NAV] [EXACT] cmp %02d: %s === %s ?', $i + 1, $a['path'], $current_path));
        if ($a['path'] === $current_path) {
            $content = str_replace($a['full'], $add_state($a['full'], 'active'), $content);
            error_log(sprintf('[IBT NAV] [EXACT] ✅ match on %s', $a['path']));
            error_log('====[IBT NAV] end (state=active) ====');
            return $content;
        }
    }

    // --- 2) Ancestor match (prefix + segment boundary; skip root) ---
    foreach ($anchors as $i => $a) {
        if ($a['path'] === '/' || $a['path'] === '') continue;
        $prefix = $a['path'] . '/';
        $is_match = str_starts_with($current_path, $prefix);
        error_log(sprintf('[IBT NAV] [ANCESTOR] cmp %02d: current=%s starts_with %s ? %s',
            $i + 1, $current_path, $prefix, $is_match ? 'YES' : 'no'));
        if ($is_match) {
            $content = str_replace($a['full'], $add_state($a['full'], 'ancestor'), $content);
            error_log(sprintf('[IBT NAV] [ANCESTOR] ✅ match on %s', $a['path']));
            error_log('====[IBT NAV] end (state=ancestor) ====');
            return $content;
        }
    }

    error_log('====[IBT NAV] end (state=none) ====');
    return $content;
}, 10, 2);


