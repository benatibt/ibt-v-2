<?php
/**
 * =========================================================================
 * IBT Utilities
 * =========================================================================
 * Small utility bits of code that don't fit anywhere else
 * 
 * 1/ Search result order
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