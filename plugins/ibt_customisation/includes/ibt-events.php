<?php
/**
 * IBT Events – CPT registrations
 *
 * Registers:
 *  - ibt_event : Events with archive at /events/ (future-only filtering added later)
 *  - ibt_venue : Venues (no archive, not publicly queryable)
 *
 * Text domain: ibt-events
 * Prefix: ibt_events_
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register IBT Event and IBT Venue CPTs.
 */
function ibt_events_register_cpts() {

	// ---------------------------
	// CPT: ibt_event
	// ---------------------------
	$event_labels = array(
		'name'                  => __( 'Events', 'ibt-events' ),
		'singular_name'         => __( 'Event', 'ibt-events' ),
		'add_new'               => __( 'Add New', 'ibt-events' ),
		'add_new_item'          => __( 'Add New Event', 'ibt-events' ),
		'edit_item'             => __( 'Edit Event', 'ibt-events' ),
		'new_item'              => __( 'New Event', 'ibt-events' ),
		'view_item'             => __( 'View Event', 'ibt-events' ),
		'view_items'            => __( 'View Events', 'ibt-events' ),
		'search_items'          => __( 'Search Events', 'ibt-events' ),
		'not_found'             => __( 'No events found.', 'ibt-events' ),
		'not_found_in_trash'    => __( 'No events found in Trash.', 'ibt-events' ),
		'all_items'             => __( 'All Events', 'ibt-events' ),
		'archives'              => __( 'Event Archives', 'ibt-events' ),
		'attributes'            => __( 'Event Attributes', 'ibt-events' ),
		'insert_into_item'      => __( 'Insert into event', 'ibt-events' ),
		'uploaded_to_this_item' => __( 'Uploaded to this event', 'ibt-events' ),
		'item_published'        => __( 'Event published.', 'ibt-events' ),
		'item_updated'          => __( 'Event updated.', 'ibt-events' ),
		'menu_name'             => __( 'Events', 'ibt-events' ),
	);

	$event_args = array(
		'labels'             => $event_labels,
		'description'        => __( 'Islands Book Trust events.', 'ibt-events' ),
		'public'             => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'show_in_rest'       => true,
		'menu_icon'          => 'dashicons-calendar-alt',
		'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author' ),
		'has_archive'        => 'events', // Archive at /events/
		'rewrite'            => array(
			'slug'       => 'events',
			'with_front' => false,
			'feeds'      => false,
		),
		'publicly_queryable' => true,
		'map_meta_cap'       => true,
		'capability_type'    => 'post',
		'show_in_admin_bar'  => true,
	);

	register_post_type( 'ibt_event', $event_args );

	// ---------------------------
	// CPT: ibt_venue
	// ---------------------------
	$venue_labels = array(
		'name'               => __( 'Venues', 'ibt-events' ),
		'singular_name'      => __( 'Venue', 'ibt-events' ),
		'add_new'            => __( 'Add New', 'ibt-events' ),
		'add_new_item'       => __( 'Add New Venue', 'ibt-events' ),
		'edit_item'          => __( 'Edit Venue', 'ibt-events' ),
		'new_item'           => __( 'New Venue', 'ibt-events' ),
		'view_item'          => __( 'View Venue', 'ibt-events' ),
		'search_items'       => __( 'Search Venues', 'ibt-events' ),
		'not_found'          => __( 'No venues found.', 'ibt-events' ),
		'not_found_in_trash' => __( 'No venues found in Trash.', 'ibt-events' ),
		'all_items'          => __( 'All Venues', 'ibt-events' ),
		'menu_name'          => __( 'Venues', 'ibt-events' ),
	);

	$venue_args = array(
		'labels'             => $venue_labels,
		'description'        => __( 'Event venues for IBT events.', 'ibt-events' ),
		'public'             => false,             // Not publicly visible
		'show_ui'            => true,              // Manageable in admin
		'show_in_menu'       => 'edit.php?post_type=ibt_event', // Nest under Events
		'show_in_rest'       => true,
		'supports'           => array( 'title', 'thumbnail' ),
		'has_archive'        => false,
		'publicly_queryable' => false,             // No frontend single
		'rewrite'            => array(
			'slug'       => 'venues',
			'with_front' => false,
		),
		'map_meta_cap'       => true,
		'capability_type'    => 'post',
	);

	register_post_type( 'ibt_venue', $venue_args );
}
add_action( 'init', 'ibt_events_register_cpts' );
