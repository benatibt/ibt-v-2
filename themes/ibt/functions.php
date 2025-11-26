<?php
/**======================================================
 * ibt — Theme functions
 * DEV VERSION WITH CACHE BUSTER - CHANGE BEFORE RELEASE
 * ====================================================== */

// Load navigation state markup program for desktop menu highlighting
require_once get_theme_file_path( '/includes/ibt-nav-states.php' );

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
    // No zoom (doesn't suit theme), No slider (single image per book so adds weight without value)
	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-lightbox' );

	// Modern HTML5 markup.
	add_theme_support( 'html5', [
		'comment-form', 'comment-list', 'search-form', 'gallery', 'caption', 'style', 'script'
	] );
} );

// WOO_FILTERS - Explicit filters because some Woo templates enque even without theme support declaration

// Disable WooCommerce image zoom on single product pages
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


// IBT Favicons — inject favicon & app icon metadata into <head>.
// Keeps icons portable across sandbox, staging and production.
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


// Disable WordPress’s default Site Icon injection.
// We provide our own favicon tags above.
add_filter( 'site_icon_meta_tags', '__return_empty_array' );


/* ========================================================
   DEV ASSET LOADER (ACTIVE IN DEV)
   Loads ibt.css and ibt-editor.css with cache-busting
   Loads ibt-header.js with cache-busting
   DELETE WHOLE BLOCK AND UNCOMMENT 
   PRODUCTION ASSET LOADER FOR RELEASE
   ======================================================== */

// Define a version for cache-busting.
if ( ! defined( 'IBT_VERSION' ) ) {
	$theme = wp_get_theme( get_template() );
	$ver   = $theme ? $theme->get( 'Version' ) : null;
	define( 'IBT_VERSION', $ver ?: time() );
}

add_action( 'after_setup_theme', function () {
    add_theme_support( 'editor-styles' );
    add_editor_style( [ 'assets/css/ibt.css', 'assets/css/ibt-editor.css' ] );
} );

add_action( 'wp_enqueue_scripts', function () {

    // ----- Front-end CSS (ibt.css with cache-buster) -----
    $css_rel  = 'assets/css/ibt.css';
    $css_path = get_stylesheet_directory() . '/' . $css_rel;
    $css_ver  = file_exists( $css_path ) ? filemtime( $css_path ) : IBT_VERSION;

    wp_enqueue_style(
        'ibt-theme',
        get_stylesheet_directory_uri() . '/' . $css_rel,
        [],
        $css_ver
    );

    // ----- Header JS (cache-buster) -----
    $js_rel  = 'assets/js/ibt-header.js';
    $js_path = get_stylesheet_directory() . '/' . $js_rel;
    $js_ver  = file_exists( $js_path ) ? filemtime( $js_path ) : IBT_VERSION;

    wp_enqueue_script(
        'ibt-header',
        get_stylesheet_directory_uri() . '/' . $js_rel,
        [ 'wp-dom-ready' ],
        $js_ver,
        true
    );

}, 20 );


/* ========================================================
   PRODUCTION ASSET LOADER
   Loads ibt.min.css + ibt-editor.css (versioned)
   Loads ibt-header.js (versioned)
   ======================================================== */

/*
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
*/