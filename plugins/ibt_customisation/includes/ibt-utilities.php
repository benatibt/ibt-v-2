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

// -------------------------------------------------------------------------
// 3. Suppress past Events from Relevanssi search results.
//    Mirrors default Events archive behaviour (future events only).
// -------------------------------------------------------------------------

add_action( 'init', function() {

	// If Relevanssi is not active, exit safely.
	if ( ! function_exists( 'relevanssi_do_query' ) ) {
		return;
	}

	add_filter(
		'relevanssi_match',
		ibt_safe( 'relevanssi-events', function( $match ) {

			if ( empty( $match->doc ) ) {
				return $match;
			}

			// Only apply to IBT Events.
			if ( get_post_type( $match->doc ) !== 'ibt_event' ) {
				return $match;
			}

			$event_end = get_post_meta( $match->doc, 'ibt_event_end', true );
			if ( empty( $event_end ) ) {
				// No end date – leave searchable.
				return $match;
			}

			// Mirror Events archive timezone + comparison.
			try {
				$now = new DateTime( 'now', new DateTimeZone( 'Europe/London' ) );
				$end = new DateTime( $event_end, new DateTimeZone( 'Europe/London' ) );
			} catch ( Exception $e ) {
				// Defensive: if parsing fails, do not suppress.
				return $match;
			}

			// Past event → suppress from search.
			if ( $end < $now ) {
				return false;
			}

			return $match;
		})
	);
});
