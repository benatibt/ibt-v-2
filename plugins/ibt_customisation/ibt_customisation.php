<?php
/**
 * Plugin Name: IBT Customisation
 * Description: Custom functionality for Islands Book Trust (WooCommerce extras, etc.).
 * Version: 0.2.0
 * Author: Ben Sheppard & ChatGPT
 * License: GPL-2.0-or-later
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 *
 * Changelog
 * 0.2.0 – Security/stability upgrade: Woo guards, nonce check, strict types,
 *         capped admin error notices, capability checks.
 * 0.1.0 – Initial test release.
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * SETTINGS
 */
const IBT_BOOKS_CATEGORY_SLUG = 'books';
const IBT_MIN_WC_VERSION      = '10.1'; // enforce Woo ≥10.1

/**
 * Utility: Is WooCommerce active and at/above our minimum version?
 */
function ibt_is_woocommerce_compatible(): bool {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return false;
    }
    $version = null;
    if ( function_exists( 'WC' ) && is_object( WC() ) && property_exists( WC(), 'version' ) ) {
        $version = WC()->version;
    } elseif ( defined( 'WC_VERSION' ) ) {
        $version = WC_VERSION;
    }
    if ( empty( $version ) ) {
        return false;
    }
    return version_compare( $version, IBT_MIN_WC_VERSION, '>=' );
}

/**
 * Activation guard
 */
register_activation_hook( __FILE__, function() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die(
            esc_html__( 'IBT Customisation requires WooCommerce to be installed and active. Please activate WooCommerce and try again.', 'ibt' ),
            esc_html__( 'Plugin dependency not met', 'ibt' ),
            array( 'back_link' => true )
        );
    }
    if ( ! ibt_is_woocommerce_compatible() ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die(
            sprintf(
                esc_html__( 'IBT Customisation requires WooCommerce version %s or newer. Please update WooCommerce and try again.', 'ibt' ),
                esc_html( IBT_MIN_WC_VERSION )
            ),
            esc_html__( 'Plugin dependency not met', 'ibt' ),
            array( 'back_link' => true )
        );
    }
} );

/**
 * Admin notice if Woo is missing or too old
 */
function ibt_admin_notice_missing_woo(): void {
    if ( ! current_user_can( 'activate_plugins' ) ) {
        return;
    }
    echo '<div class="notice notice-error"><p><strong>'
        . esc_html__( 'IBT Customisation requires WooCommerce 10.1+ to run. WooCommerce is inactive or too old, so the plugin is idle.', 'ibt' )
        . '</strong></p></div>';
}

/**
 * Load Woo-dependent code only when Woo is fully loaded AND version is OK.
 */
add_action( 'plugins_loaded', function() {
    if ( ! ibt_is_woocommerce_compatible() ) {
        add_action( 'admin_notices', 'ibt_admin_notice_missing_woo' );
        return;
    }
    add_action( 'woocommerce_loaded', function() {
        if ( ! ibt_is_woocommerce_compatible() ) {
            add_action( 'admin_notices', 'ibt_admin_notice_missing_woo' );
            return;
        }
        require_once __DIR__ . '/includes/author-isbn-fields.php';
    }, 1 );
}, 1 );
