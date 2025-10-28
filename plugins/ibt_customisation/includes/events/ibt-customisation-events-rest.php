<?php
/**
 * Registers event and venue meta fields for REST API exposure.
 * Part of Events in the IBT Customisation plugin.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Registers datetime meta fields for events (start and end) with REST support.

function ibt_events_register_meta() {

	$datetime_args = array(
		'type'              => 'string',
		'single'            => true,
		'show_in_rest'      => true,
		'sanitize_callback' => 'sanitize_text_field',
        'auth_callback'     => '__return_true',

	);

	register_post_meta( 'ibt_event', 'ibt_event_start', $datetime_args );
	register_post_meta( 'ibt_event', 'ibt_event_end',   $datetime_args );
}
add_action( 'init', 'ibt_events_register_meta' );


// Registers additional event meta fields (venue, prices, featured, notes) for REST API use.
function ibt_events_register_detail_meta() {

	$base_args = array(
		'single'        => true,
		'show_in_rest'  => true,
        'auth_callback' => '__return_true',
	);

	register_post_meta( 'ibt_event', 'ibt_event_venue_id', array_merge(
		$base_args,
		array( 'type' => 'integer', 'sanitize_callback' => 'absint' )
	));

	register_post_meta( 'ibt_event', 'ibt_event_price_public', array_merge(
		$base_args,
		array( 'type' => 'string', 'sanitize_callback' => 'ibt_events_sanitize_price' )
	));

	register_post_meta( 'ibt_event', 'ibt_event_price_member', array_merge(
		$base_args,
		array( 'type' => 'string', 'sanitize_callback' => 'ibt_events_sanitize_price' )
	));

	register_post_meta( 'ibt_event', 'ibt_event_featured', array_merge(
		$base_args,
		array( 'type' => 'boolean', 'sanitize_callback' => 'rest_sanitize_boolean' )
	));

	register_post_meta( 'ibt_event', 'ibt_event_notes', array_merge(
		$base_args,
		array( 'type' => 'string', 'sanitize_callback' => 'sanitize_textarea_field' )
	));
}
add_action( 'init', 'ibt_events_register_detail_meta' );