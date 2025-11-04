<?php
// Registers the two core custom post types used by the Events module (Events and Venues).

// TIMEZONE POLICY – IBT Events
// - Stored and displayed in site local time (Europe/London).
// - Any exports and APIs to normalise to UTC on output (single conversion point).


if ( ! defined( 'ABSPATH' ) ) exit;


// Registers the two core custom post types used by the Events module.

function ibt_events_register_cpts() {

	// ======= CPT: ibt_event =======

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
	    'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'custom-fields' ),
		'has_archive'        => 'events',
		'rewrite'            => array(
			'slug'           => 'events',
			'with_front'     => false,
			'feeds'          => false,
		),
		'publicly_queryable' => true,
		'map_meta_cap'       => true,
		'capability_type'    => 'post',
		'show_in_admin_bar'  => true,
	);

	register_post_type( 'ibt_event', $event_args );


	// ======= CPT: ibt_venue =======

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
		'public'             => false,
		'show_ui'            => true,
		'show_in_menu'       => 'edit.php?post_type=ibt_event',
		'show_in_rest'       => true,
		'supports'           => array( 'title', 'thumbnail' ),
		'has_archive'        => false,
		'publicly_queryable' => false,
		'rewrite'            => array(
			'slug'           => 'venues',
			'with_front'     => false,
		),
		'map_meta_cap'       => true,
		'capability_type'    => 'post',
	);

	register_post_type( 'ibt_venue', $venue_args );
}

add_action( 'init', 'ibt_events_register_cpts' );


/**
 * Registers Event Type taxonomy for ibt_event.
 * - Hierarchical (like categories) to allow parent/child types if ever needed.
 * - show_in_rest true so it appears in the block editor sidebar.
 */
function ibt_events_register_taxonomies() {
	$labels = array(
		'name'              => __( 'Event Types', 'ibt-events' ),
		'singular_name'     => __( 'Event Type', 'ibt-events' ),
		'search_items'      => __( 'Search Event Types', 'ibt-events' ),
		'all_items'         => __( 'All Event Types', 'ibt-events' ),
		'edit_item'         => __( 'Edit Event Type', 'ibt-events' ),
		'update_item'       => __( 'Update Event Type', 'ibt-events' ),
		'add_new_item'      => __( 'Add New Event Type', 'ibt-events' ),
		'new_item_name'     => __( 'New Event Type Name', 'ibt-events' ),
		'menu_name'         => __( 'Event Types', 'ibt-events' ),
	);

	$args = array(
		'labels'            => $labels,
		'hierarchical'      => true, // categories-style
		'public'            => true,
		'show_ui'           => true,
		'show_in_rest'      => true, // needed for editor sidebar panel
		'show_admin_column' => true,
		'rewrite'           => array(
			'slug'       => 'event-type',
			'with_front' => false,
		),
	);

	register_taxonomy( 'ibt_event_type', array( 'ibt_event' ), $args );
}

add_action( 'init', 'ibt_events_register_taxonomies' );
