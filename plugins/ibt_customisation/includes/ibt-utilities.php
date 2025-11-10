<?php
/**
 * =========================================================================
 * IBT Utilities
 * =========================================================================
 * Small utility bits of code that don't fit anywhere else
 * 
 * 1/ Search result order
 * 2/ Boost search results for products. 
 * 
 * =========================================================================
 */

 if ( ! defined( 'ABSPATH' ) ) exit;

// -------------------------------------------------------------------------
// 1: Order front-end search results by Relevanssi relevance.
//    (Block JSON is 'unhappy' if we try to do it in template!)
// -------------------------------------------------------------------------

add_action( 'pre_get_posts', function( $q ) {
    if ( $q->is_main_query() && $q->is_search() && ! is_admin() ) {
        $q->set( 'orderby', 'relevance' );
    }
});


// -------------------------------------------------------------------------
// 2. Boost WooCommerce products in Relevanssi search results.
//    Uses ibt_safe() wrapper to avoid fatal errors and log issues.
// -------------------------------------------------------------------------

add_action( 'init', function() {

    // If Relevanssi is not active, log and exit safely.
    if ( ! function_exists( 'relevanssi_do_query' ) ) {
        error_log( '[IBT Customisation] (relevanssi-boost) Relevanssi not active, boost skipped.' );
        return;
    }

    add_filter(
        'relevanssi_match',
        ibt_safe( 'relevanssi-boost', function( $match ) {

            // Defensive: object may not have ->doc or ->weight in future versions.
            if ( empty( $match->doc ) ) {
                return $match;
            }

            // Only boost WooCommerce products.
            if ( get_post_type( $match->doc ) === 'product' ) {
                // Adjust multiplier here; 2.0 means "double weight"
                $match->weight *= 1.5;
            }

            return $match;
        })
    );
});
