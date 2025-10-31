<?php
/**
 * Plugin Name: IBT Customisation
 * Description: Custom functionality for Islands Book Trust (WooCommerce extras, etc.).
 * Version: 0.3.0
 * Author: Ben Sheppard & ChatGPT
 * License: GPL-2.0-or-later
 *
 * Changelog
 * 0.3.0 – Shortcode-only presentation; removed block registration and debug logs. Added micro-cache + signature fixes.
 * 0.2.1 – Added ibt_safe() error-handling wrapper.
 * 0.2.0 – Added WooCommerce presence check (auto-deactivate + log) and free-text ISBN.
 * 0.1.0 – Initial test release.
 */

/**
 * ---------------------------------------------------------------------
 * PURPOSE
 * Adds "Author" and "ISBN" custom fields for WooCommerce products
 * in the Books category, displaying them in listings and single views.
 *
 * SAFE LOAD & ERROR HANDLING
 * • Auto-deactivates if WooCommerce is missing.
 * • All runtime callbacks wrapped in ibt_safe() – errors logged,
 *   should never be fatal.
 * • Logs written via error_log(); no admin notices or UI output.

 * ---------------------------------------------------------------------
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Error-handling wrapper: logs but never breaks execution.
 * Usage: add_action( 'hook', ibt_safe( 'context-name' , fn() => { ... }) );
 */
if ( ! function_exists( 'ibt_safe' ) ) {
	function ibt_safe( string $context, callable $fn ): callable {
		return function ( ...$args ) use ( $fn, $context ) {
			try {
				return $fn( ...$args );
			} catch ( \Throwable $e ) {
				error_log( sprintf(
					'[IBT Customisation] (%s) Handler error: %s',
					$context,
					$e->getMessage()
				) );
				return null;
			}
		};
	}
}



/**
 * SETTINGS
 */
const IBT_BOOKS_CATEGORY_SLUG = 'books';

/**
 * Basic guard – only load Woo-dependent code after plugins are ready.
 */
add_action( 'plugins_loaded', function() {

	// Check whether WooCommerce is loaded.
	if ( ! class_exists( 'WooCommerce' ) ) {
		if ( function_exists( 'error_log' ) ) {
			error_log( '[IBT Customisation] WooCommerce check failed — plugin not initialised (auto-deactivated).' );
		}
		if ( function_exists( 'deactivate_plugins' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
		}
		return;
	}

	// Load our included files after Woo check. Fails ungracefully if missing.
	require_once __DIR__ . '/includes/register-taxonomy-types.php';
	require_once __DIR__ . '/includes/author-isbn-fields.php';

	require_once __DIR__ . '/includes/events/ibt-customisation-events-core.php';
	require_once __DIR__ . '/includes/events/ibt-customisation-events-metabox.php';
	require_once __DIR__ . '/includes/events/ibt-customisation-events-helpers.php';
	require_once __DIR__ . '/includes/events/ibt-customisation-events-display-shortcode.php';
	require_once __DIR__ . '/includes/events/ibt-customisation-events-display-archive.php';
	require_once __DIR__ . '/includes/events/ibt-customisation-events-display-single.php';

	//dev
	//require_once __DIR__ . '/blocks/events-archive/index.php';

	//dev
	//require_once __DIR__ . '/blocks/events-archive/test-block.php';

	// Probably staying
	require_once __DIR__ . '/blocks/events-archive-php/block-register.php';
	
	
	// dev--- Ensure the editor stub for the Events Archive block is loaded in Site Editor ---
	add_action( 'enqueue_block_editor_assets', function() {
		wp_enqueue_script(
			'ibt-events-archive-editor',
			plugins_url( 'blocks/events-archive/editor.js', __FILE__ ),
			array( 'wp-blocks', 'wp-element' ),
			filemtime( plugin_dir_path( __FILE__ ) . 'blocks/events-archive/editor.js' )
		);
});

});

