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

// === 1.0 – Register Event & Venue CPTs ===

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

// === 2.0 – Event Date & Time Meta ===

/**
 * Register event datetime meta and admin UI.
 * 
 * Fields:
 * - ibt_event_start (datetime, Y-m-d H:i:s)
 * - ibt_event_end   (datetime, Y-m-d H:i:s)
 */

// 2.1 – Register meta keys for REST and sanitisation
function ibt_events_register_meta() {

	$datetime_args = array(
		'type'              => 'string',
		'single'            => true,
		'show_in_rest'      => true,
		'sanitize_callback' => 'sanitize_text_field',
		'auth_callback'     => function() { return current_user_can( 'edit_posts' ); },
	);

	register_post_meta( 'ibt_event', 'ibt_event_start', $datetime_args );
	register_post_meta( 'ibt_event', 'ibt_event_end',   $datetime_args );
}
add_action( 'init', 'ibt_events_register_meta' );

// 2.2 – Add admin meta box
function ibt_events_add_datetime_metabox() {
	add_meta_box(
		'ibt_event_datetime',
		__( 'Event Date & Time', 'ibt-events' ),
		'ibt_events_render_datetime_metabox',
		'ibt_event',
		'normal',
		'default'
	);
}
add_action( 'add_meta_boxes', 'ibt_events_add_datetime_metabox' );

// 2.3 – Render fields with native pickers
function ibt_events_render_datetime_metabox( $post ) {

	wp_nonce_field( 'ibt_events_save_meta', 'ibt_events_meta_nonce' );

	$start = get_post_meta( $post->ID, 'ibt_event_start', true );
	$end   = get_post_meta( $post->ID, 'ibt_event_end', true );

	// Split stored datetimes into date/time parts for inputs
	$start_date = $start ? date( 'Y-m-d', strtotime( $start ) ) : '';
	$start_time = $start ? date( 'H:i',   strtotime( $start ) ) : '';
	$end_date   = $end   ? date( 'Y-m-d', strtotime( $end ) )   : '';
	$end_time   = $end   ? date( 'H:i',   strtotime( $end ) )   : '';

	echo '<p><strong>' . esc_html__( 'Start Date & Time', 'ibt-events' ) . '</strong></p>';
	echo '<p><label>' . esc_html__( 'Date:', 'ibt-events' ) . ' ';
	echo '<input type="date" name="ibt_event_start_date" value="' . esc_attr( $start_date ) . '" /></label> ';
	echo '<label>' . esc_html__( 'Time:', 'ibt-events' ) . ' ';
	echo '<input type="time" name="ibt_event_start_time" value="' . esc_attr( $start_time ) . '" /></label></p>';

	echo '<p><strong>' . esc_html__( 'End Date & Time', 'ibt-events' ) . '</strong></p>';
	echo '<p><label>' . esc_html__( 'Date:', 'ibt-events' ) . ' ';
	echo '<input type="date" name="ibt_event_end_date" value="' . esc_attr( $end_date ) . '" /></label> ';
	echo '<label>' . esc_html__( 'Time:', 'ibt-events' ) . ' ';
	echo '<input type="time" name="ibt_event_end_time" value="' . esc_attr( $end_time ) . '" /></label></p>';
}

// 2.4 – Save handler
function ibt_events_save_datetime_meta( $post_id ) {

	// Verify nonce
	if ( ! isset( $_POST['ibt_events_meta_nonce'] ) ||
	     ! wp_verify_nonce( $_POST['ibt_events_meta_nonce'], 'ibt_events_save_meta' ) ) {
		return;
	}

	// Skip autosaves
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Permission check
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Retrieve and sanitise inputs
	$start_date = sanitize_text_field( $_POST['ibt_event_start_date'] ?? '' );
	$start_time = sanitize_text_field( $_POST['ibt_event_start_time'] ?? '' );
	$end_date   = sanitize_text_field( $_POST['ibt_event_end_date']   ?? '' );
	$end_time   = sanitize_text_field( $_POST['ibt_event_end_time']   ?? '' );

	// Combine and save
	$start_combined = ibt_events_combine_datetime( $start_date, $start_time );
	$end_combined   = ibt_events_combine_datetime( $end_date,   $end_time );

	update_post_meta( $post_id, 'ibt_event_start', $start_combined );
	update_post_meta( $post_id, 'ibt_event_end',   $end_combined );
}
add_action( 'save_post_ibt_event', 'ibt_events_save_datetime_meta' );

// 2.5 – Helper: merge date and time safely
function ibt_events_combine_datetime( $date, $time ) {
	if ( empty( $date ) ) {
		return '';
	}
	$time = $time ?: '00:00';
	$ts = strtotime( "$date $time" );
	return $ts ? date( 'Y-m-d H:i:s', $ts ) : '';
}
