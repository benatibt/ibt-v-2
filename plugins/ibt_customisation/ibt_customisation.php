<?php
/**
 * Plugin Name: IBT Customisation
 * Description: Custom functionality for Islands Book Trust (WooCommerce extras, etc.).
 * Version: 0.2.1
 * Author: Ben Sheppard & ChatGPT
 * License: GPL-2.0-or-later
 *
 * Changelog
 * 0.2.1 – Added ibt_safe() error-handling wrapper; production-stable release.
 * 0.2.0 – Added WooCommerce presence check (auto-deactivate + log) and free-text ISBN.
 * 0.1.0 – Initial test release.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SETTINGS
 */
const IBT_BOOKS_CATEGORY_SLUG = 'books';

/**
 * Basic guard – only load Woo-dependent code after plugins are ready.
 */
add_action( 'plugins_loaded', function() {

	// If WooCommerce missing, write to log and deactivate.
	if ( ! class_exists( 'WooCommerce' ) ) {
		if ( function_exists( 'error_log' ) ) {
			error_log( '[IBT Customisation] WooCommerce not detected – plugin auto-deactivated.' );
		}
		if ( function_exists( 'deactivate_plugins' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
		}
		return;
	}

	// Safe to include our main logic.
	require_once __DIR__ . '/includes/author-isbn-fields.php';
} );
