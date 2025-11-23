<?php
/**
 * Plugin Name: IBT Customisation
 * Description: Custom functionality for Islands Book Trust. Requires WooCommerce.
 * Version: 0.4.3
 * Author: Ben Sheppard & ChatGPT 5
 * License: GPL-2.0-or-later
 */

/**
 * ---------------------------------------------------------------------
 * SAFE LOAD & ERROR HANDLING
 * • Auto-deactivates if WooCommerce is missing.
 * • All runtime callbacks wrapped in ibt_safe() – errors logged,
 *   should never be fatal.
 * • Logs written via error_log(); no admin notices or UI output.

 * ---------------------------------------------------------------------
 */


if ( ! defined( 'ABSPATH' ) ) {	exit; }


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
	require_once __DIR__ . '/includes/ibt-utilities.php' ;

	// --- Events module (core CPT + helpers + shortcodes) ---
	require_once __DIR__ . '/includes/events/ibt-events-core.php';
	require_once __DIR__ . '/includes/events/ibt-events-metabox.php';
	require_once __DIR__ . '/includes/events/ibt-events-helpers.php';
	require_once __DIR__ . '/includes/events/ibt-events-display-field.php';
	require_once __DIR__ . '/includes/events/ibt-events-display-list.php';
	require_once __DIR__ . '/includes/events/ibt-events-shortcode.php';

	// --- Active production blocks ---
	require_once __DIR__ . '/blocks/events-archive-php/block-register.php';
	require_once __DIR__ . '/blocks/post-type-label/block-register.php';

});

