<?php
/* -------------------------------------------------------------------------
   IBT EVENTS SHORTCODES
   -------------------------------------------------------------------------
   Purpose:
   - Register public shortcodes for the IBT Events system.
   - [ibt_event_field] → single field output (calls ibt_events_render_field()).
   - [ibt_events_list] → event list output (calls ibt_events_render_list()).

   Notes:
   - No HTML or query logic here — this file only connects shortcode
     attributes to display-layer functions.
   - Returns strings; never echoes.
------------------------------------------------------------------------- */

if ( ! defined( 'ABSPATH' ) ) exit;


// -------------------------------------------------------------------------
// [ibt_event_field] — render a single field via display-field layer
// -------------------------------------------------------------------------
add_shortcode( 'ibt_event_field', function( $atts ) {
    $atts = shortcode_atts( array( 'key' => '' ), $atts, 'ibt_event_field' );
    $key  = sanitize_key( $atts['key'] );

    if ( empty( $key ) ) {
        return '';
    }

    $post_id = get_the_ID();
    if ( ! $post_id && ( $q = get_queried_object() ) ) {
        $post_id = isset( $q->ID ) ? (int) $q->ID : 0;
    }

    if ( ! $post_id && function_exists( 'get_block_context' ) ) {
        $ctx = get_block_context();
        if ( isset( $ctx['postId'] ) ) {
            $post_id = (int) $ctx['postId'];
        }
    }

    if ( ! $post_id ) {
        return '';
    }

    // Whitelist of safe public keys
    $allowed_keys = array(
        'ibt_event_start',
        'ibt_event_end',
        'ibt_event_price_public',
        'ibt_event_price_member',
        'ibt_event_presenter',
        'ibt_event_online',
        'ibt_event_venue',
        'ibt_event_venue_name',
        'ibt_event_venue_address',
        'ibt_event_excerpt',
        'ibt_event_map_button',
    );
    if ( ! in_array( $key, $allowed_keys, true ) ) {
        return '';
    }

    // Delegate actual rendering
    return ibt_events_render_field( $post_id, $key );
});


// -------------------------------------------------------------------------
// [ibt_events_list] — render list of upcoming events via display-list layer
// -------------------------------------------------------------------------
add_shortcode( 'ibt_events_list', 'ibt_events_render_list' );
