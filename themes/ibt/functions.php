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