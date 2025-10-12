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
   IBT – Navigation: add active / ancestor flags (path-aware, same-host)
   WHY: Gutenberg Navigation often lacks reliable state classes.
   WHAT: Adds .wp-block-navigation-item--active / --ancestor on <a>,
         plus aria-current="page" for the exact match.
   Notes: Only flags same-host links; exact match wins over ancestor.
--------------------------------------------------------------------- */
add_filter( 'render_block_core/navigation-link', function( $content, $block ) {
	// Must have a URL attr and an <a> in rendered content
	if ( empty( $block['attrs']['url'] ) || stripos( $content, '<a ' ) === false ) {
		return $content;
	}

	// Resolve current request and link to comparable parts
	$current_url  = ( isset( $_SERVER['REQUEST_SCHEME'], $_SERVER['HTTP_HOST'] ) ? $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] : home_url() ) . ( $_SERVER['REQUEST_URI'] ?? '/' );
	$current_host = parse_url( $current_url, PHP_URL_HOST );
	$current_path = untrailingslashit( parse_url( $current_url, PHP_URL_PATH ) ?: '/' );

	$link_url     = $block['attrs']['url'];
	$link_host    = parse_url( $link_url, PHP_URL_HOST );         // null for relative
	$link_path    = untrailingslashit( parse_url( $link_url, PHP_URL_PATH ) ?: '/' );

	// Skip external links (different host)
	if ( $link_host && $current_host && strcasecmp( $link_host, $current_host ) !== 0 ) {
		return $content;
	}

	// Decide class to add
	$add_class = '';

	// Exact match → active
	if ( $link_path === $current_path ) {
		$add_class = 'wp-block-navigation-item--active';
	}
	// Ancestor path → ancestor (skip root '/'), compare by segments
	elseif ( $link_path !== '' && $link_path !== '/' ) {
		$current_parts = array_values( array_filter( explode( '/', trim( $current_path, '/' ) ) ) );
		$link_parts    = array_values( array_filter( explode( '/', trim( $link_path, '/' ) ) ) );

		if ( count( $link_parts ) < count( $current_parts )
			&& array_slice( $current_parts, 0, count( $link_parts ) ) === $link_parts ) {
			$add_class = 'wp-block-navigation-item--ancestor';
		}
	}

	// Inject class safely (preserve existing), and aria-current for active
	if ( $add_class ) {
		if ( preg_match( '/class="([^"]*)"/i', $content ) ) {
			$content = preg_replace( '/class="([^"]*)"/i', 'class="$1 ' . $add_class . '"', $content, 1 );
		} else {
			$content = preg_replace( '/<a\s+/i', '<a class="' . $add_class . '" ', $content, 1 );
		}
		if ( $add_class === 'wp-block-navigation-item--active' && stripos( $content, 'aria-current=' ) === false ) {
			$content = preg_replace( '/<a\s+/i', '<a aria-current="page" ', $content, 1 );
		}
	}

	return $content;
}, 10, 2 );

