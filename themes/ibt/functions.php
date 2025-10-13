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


/* ---------------------------------------------------------------------
   IBT – Navigation Active State (production, light log)
   • Runs once per desktop navigation block.
   • Marks current link as data-ibt-state="active" or "ancestor".
   • Adds one short log line per request for optional diagnostics.
--------------------------------------------------------------------- */

add_filter( 'render_block_core/navigation', function( $content, $block ) {

  // 🔒 1️⃣ Limit to desktop nav only
  $class_name = $block['attrs']['className'] ?? '';
  if ( strpos( $class_name, 'ibt-header-nav-desktop' ) === false ) {
    return $content; // skip mobile or other navs
  }

  // ---- 2️⃣ Get current path ------------------------------------------------
  $current_path = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
  $current_path = untrailingslashit( strtolower( $current_path ?: '/' ) );

  // ---- 3️⃣ Extract <a href> links ------------------------------------------
  preg_match_all( '/<a[^>]+href="([^"]+)"[^>]*>/i', $content, $matches, PREG_OFFSET_CAPTURE );
  $links = [];
  foreach ( $matches[1] as $i => $match ) {
    $href      = $match[0];
    // only keep internal links (starting with "/")
    if ( ! str_starts_with( $href, '/' ) ) continue;
    $link_path = untrailingslashit( strtolower( parse_url( $href, PHP_URL_PATH ) ?: '/' ) );
    $links[] = [
      'href' => $href,
      'path' => $link_path,
      'full' => $matches[0][$i][0],
    ];
  }

  // ---- 4️⃣ Pass 1: exact match --------------------------------------------
  $found = 'none';
  foreach ( $links as $link ) {
    if ( $link['path'] === $current_path ) {
      $replacement = str_replace('<a ', '<a data-ibt-state="active" ', $link['full']);
      $content = str_replace($link['full'], $replacement, $content);
      $found = 'active';
      break;
    }
  }

  // ---- 5️⃣ Pass 2: ancestor match -----------------------------------------
  if ( $found === 'none' ) {
    foreach ( $links as $link ) {
      // skip root to prevent false positives
      if ( $link['path'] === '/' || $link['path'] === '' ) continue;
      if ( str_starts_with( $current_path, $link['path'] . '/' ) ) {
        $replacement = str_replace('<a ', '<a data-ibt-state="ancestor" ', $link['full']);
        $content = str_replace($link['full'], $replacement, $content);
        $found = 'ancestor';
        break;
      }
    }
  }

  // ---- 6️⃣ Optional single log line ---------------------------------------
  error_log("[IBT NAV] {$found} match applied for {$current_path}");

  // ---- 7️⃣ Return modified HTML ------------------------------------------
  return $content;

}, 10, 2 );

