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
	    'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'custom-fields' ),
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
        'auth_callback'     => '__return_true',

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

// === 3.0 – Event Details Meta ===

/**
 * Registers and manages additional event fields:
 * - ibt_event_venue_id
 * - ibt_event_price_public
 * - ibt_event_price_member
 * - ibt_event_featured
 * - ibt_event_notes
 */

// 3.1 – Register meta keys for REST + sanitisation
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

// 3.2 – Add admin meta box
function ibt_events_add_details_metabox() {
	add_meta_box(
		'ibt_event_details',
		__( 'Event Details', 'ibt-events' ),
		'ibt_events_render_details_metabox',
		'ibt_event',
		'normal',
		'default'
	);
}
add_action( 'add_meta_boxes', 'ibt_events_add_details_metabox' );

// 3.3 – Render meta box UI
function ibt_events_render_details_metabox( $post ) {

	wp_nonce_field( 'ibt_events_save_meta', 'ibt_events_meta_nonce' );

	$venue_id   = get_post_meta( $post->ID, 'ibt_event_venue_id', true );
	$price_pub  = get_post_meta( $post->ID, 'ibt_event_price_public', true );
	$price_mem  = get_post_meta( $post->ID, 'ibt_event_price_member', true );
	$featured   = (bool) get_post_meta( $post->ID, 'ibt_event_featured', true );
	$notes      = get_post_meta( $post->ID, 'ibt_event_notes', true );

	// Fetch venues for dropdown
	$venues = get_posts( array(
		'post_type'      => 'ibt_venue',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	) );

	echo '<p><strong>' . esc_html__( 'Venue', 'ibt-events' ) . '</strong><br />';
	echo '<select name="ibt_event_venue_id">';
	echo '<option value="0">' . esc_html__( '— Select venue —', 'ibt-events' ) . '</option>';
	foreach ( $venues as $v ) {
		printf(
			'<option value="%d" %s>%s</option>',
			$v->ID,
			selected( (int) $venue_id, $v->ID, false ),
			esc_html( $v->post_title )
		);
	}
	echo '</select></p>';

	// Online event checkbox (boolean)
	$remote = get_post_meta( $post->ID, 'ibt_event_remote', true );
	echo '<p>';
	echo '<label for="ibt_event_remote">';
	echo '<input type="checkbox" id="ibt_event_remote" name="ibt_event_remote" value="1" ' . checked( $remote, '1', false ) . ' />';
	echo ' ' . esc_html__( 'Online event available', 'ibt-events' );
	echo '</label>';
	echo '</p>';


	echo '<p><strong>' . esc_html__( 'Pricing (£)', 'ibt-events' ) . '</strong><br />';
	echo '<label>' . esc_html__( 'Public:', 'ibt-events' ) . ' ';
	echo '<input type="text" name="ibt_event_price_public" value="' . esc_attr( $price_pub ) . '" size="8" /></label> ';
	echo '<label>' . esc_html__( 'Member:', 'ibt-events' ) . ' ';
	echo '<input type="text" name="ibt_event_price_member" value="' . esc_attr( $price_mem ) . '" size="8" /></label></p>';

	echo '<p><label>';
	echo '<input type="checkbox" name="ibt_event_featured" value="1" ' . checked( $featured, true, false ) . ' />';
	echo ' ' . esc_html__( 'Mark as featured event', 'ibt-events' ) . '</label></p>';

	echo '<p><strong>' . esc_html__( 'Notes', 'ibt-events' ) . '</strong><br />';
	echo '<textarea name="ibt_event_notes" rows="4" style="width:100%;">' . esc_textarea( $notes ) . '</textarea></p>';
}

// 3.4 – Save handler
function ibt_events_save_details_meta( $post_id ) {

	// Verify nonce
	if ( ! isset( $_POST['ibt_events_meta_nonce'] ) ||
	     ! wp_verify_nonce( $_POST['ibt_events_meta_nonce'], 'ibt_events_save_meta' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Collect and sanitise
	$venue_id  = absint( $_POST['ibt_event_venue_id'] ?? 0 );
	$price_pub = ibt_events_sanitize_price( $_POST['ibt_event_price_public'] ?? '' );
	$price_mem = ibt_events_sanitize_price( $_POST['ibt_event_price_member'] ?? '' );
	$featured  = ! empty( $_POST['ibt_event_featured'] ) ? 1 : 0;
	$notes     = sanitize_textarea_field( $_POST['ibt_event_notes'] ?? '' );
	$remote = ! empty( $_POST['ibt_event_remote'] ) ? '1' : '0';

	update_post_meta( $post_id, 'ibt_event_venue_id', $venue_id );
	update_post_meta( $post_id, 'ibt_event_remote', $remote );
	update_post_meta( $post_id, 'ibt_event_price_public', $price_pub );
	update_post_meta( $post_id, 'ibt_event_price_member', $price_mem );
	update_post_meta( $post_id, 'ibt_event_featured', $featured );
	update_post_meta( $post_id, 'ibt_event_notes', $notes );
}
add_action( 'save_post_ibt_event', 'ibt_events_save_details_meta' );

// 3.5 – Helper: price sanitiser
function ibt_events_sanitize_price( $val ) {
	$val = preg_replace( '/[^0-9.]/', '', (string) $val );
	return substr( $val, 0, 10 ); // prevent absurdly long input
}


// === 5.5 – Generic Event-Field Shortcode (Venue + Map Button) ==============
//
// Provides [ibt_event_field key="meta_key"] for safe public fields only.
// Handles date formatting, venue details, and optional Google Maps button.
//
// Example usage in templates:
//   [ibt_event_field key="ibt_event_start"]
//   [ibt_event_field key="ibt_event_venue"]
//   [ibt_event_field key="ibt_event_map_button"]

add_shortcode( 'ibt_event_field', function( $atts ) {
	$atts = shortcode_atts( array( 'key' => '' ), $atts, 'ibt_event_field' );
	$key  = $atts['key'];
	if ( empty( $key ) ) {
		return '';
	}

	// ---- Determine current post ID safely ----
	$post_id = get_the_ID();
	if ( ! $post_id && ( $q = get_queried_object() ) ) {
		$post_id = $q->ID ?? 0;
	}
	if ( ! $post_id ) {
		return '';
	}

	// ---- Whitelist of public keys ----
	$allowed_keys = array(
		'ibt_event_start',
		'ibt_event_end',
		'ibt_event_price_public',
		'ibt_event_price_member',
		'ibt_event_remote',
		'ibt_event_venue',
		'ibt_event_map_button',
	);

	if ( ! in_array( $key, $allowed_keys, true ) ) {
		return '';
	}

	switch ( $key ) {
		// ----- Date fields -----
		case 'ibt_event_start':
			$value = get_post_meta( $post_id, 'ibt_event_start', true );
			$value = ibt_events_format_datetime( $value );
			return esc_html( $value );

		case 'ibt_event_end':
			$start = get_post_meta( $post_id, 'ibt_event_start', true );
			$end   = get_post_meta( $post_id, 'ibt_event_end', true );
			$value = ibt_events_format_end( $start, $end );
			return esc_html( $value );

		// ----- Venue details -----
		case 'ibt_event_venue':
			$venue_id = (int) get_post_meta( $post_id, 'ibt_event_venue_id', true );
			if ( ! $venue_id ) return '';
			$name = get_post_meta( $venue_id, 'ibt_venue_name', true );
			$addr = get_post_meta( $venue_id, 'ibt_venue_address', true );

			$out = esc_html( $name );
			if ( $addr ) {
				$out .= '<br>' . nl2br( esc_html( $addr ) );
			}
			return $out;

		// ----- Google Maps button -----
		case 'ibt_event_map_button':
			$venue_id = (int) get_post_meta( $post_id, 'ibt_event_venue_id', true );
			if ( ! $venue_id ) return '';
			$map = get_post_meta( $venue_id, 'ibt_venue_maplocation', true );
			if ( empty( $map ) ) return '';

			$url = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $map );
			return '<a class="wp-block-button__link ibt-button-map" target="_blank" rel="noopener" href="' .
			       esc_url( $url ) . '">View&nbsp;on&nbsp;Google&nbsp;Maps</a>';

		// ----- Prices & URLs (plain text) -----
		default:
			$value = get_post_meta( $post_id, $key, true );
			return esc_html( $value );
	}
});


// === 4.1 – Venue Meta Boxes =============================================

// Add meta boxes to the Venue CPT
add_action( 'add_meta_boxes', function() {
    add_meta_box(
        'ibt_venue_details',
        __( 'Venue Details', 'ibt-events' ),
        'ibt_venue_details_metabox',
        'ibt_venue',
        'normal',
        'default'
    );
});

// Callback to render the meta box fields
function ibt_venue_details_metabox( $post ) {
    // Security nonce
    wp_nonce_field( 'ibt_venue_meta_save', 'ibt_venue_meta_nonce' );

    $address     = get_post_meta( $post->ID, 'ibt_venue_address', true );
    $maplocation = get_post_meta( $post->ID, 'ibt_venue_maplocation', true );
    ?>

    <p><label for="ibt_venue_address"><strong><?php _e( 'Venue address', 'ibt-events' ); ?></strong></label></p>
    <textarea name="ibt_venue_address" id="ibt_venue_address" rows="4" style="width:100%;"><?php echo esc_textarea( $address ); ?></textarea>
    <p class="description"><?php _e( 'Use one line per address component. These line breaks are preserved for multiline view.', 'ibt-events' ); ?></p>

    <p><label for="ibt_venue_maplocation"><strong><?php _e( 'Map location (lat,long or Plus Code)', 'ibt-events' ); ?></strong></label></p>
    <input type="text" name="ibt_venue_maplocation" id="ibt_venue_maplocation"
           value="<?php echo esc_attr( $maplocation ); ?>" style="width:100%;" maxlength="100" />
    <p class="description"><?php _e( 'Example: 58.091639,-6.606250 or 39RV+JGF Balallan UK', 'ibt-events' ); ?></p>

    <?php
}

// Save handler
add_action( 'save_post_ibt_venue', function( $post_id ) {
    // Verify nonce
    if ( ! isset( $_POST['ibt_venue_meta_nonce'] ) ||
         ! wp_verify_nonce( $_POST['ibt_venue_meta_nonce'], 'ibt_venue_meta_save' ) ) {
        return;
    }

    // Skip autosaves / revisions
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( wp_is_post_revision( $post_id ) ) return;

    // Capability check
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    // Sanitise and save
    if ( isset( $_POST['ibt_venue_address'] ) ) {
        update_post_meta( $post_id, 'ibt_venue_address', sanitize_textarea_field( $_POST['ibt_venue_address'] ) );
    }

    if ( isset( $_POST['ibt_venue_maplocation'] ) ) {
        $val = substr( sanitize_text_field( $_POST['ibt_venue_maplocation'] ), 0, 100 );
        update_post_meta( $post_id, 'ibt_venue_maplocation', $val );
    }
});



// === 5.6 – Event Date Formatting Helpers ===================================
//
// Produces human-friendly, UK-style output such as:
//   12:15 pm on 18 October 25
//   3:45 pm (on same day shows time only)

if ( ! function_exists( 'ibt_events_format_datetime' ) ) {
	function ibt_events_format_datetime( $mysql_datetime ) {
		if ( empty( $mysql_datetime ) ) {
			return '';
		}

		// Convert MySQL datetime string to timestamp in site timezone
		$ts = strtotime( get_date_from_gmt( gmdate( 'Y-m-d H:i:s', strtotime( $mysql_datetime ) ) ) );
		if ( ! $ts ) {
			return '';
		}

		return date_i18n( 'g:ia \o\n j F y', $ts ); // e.g. 12:15 pm on 18 October 25
	}
}

if ( ! function_exists( 'ibt_events_format_end' ) ) {
	function ibt_events_format_end( $start, $end ) {
		if ( empty( $end ) ) {
			return '';
		}

		$start_ts = strtotime( $start );
		$end_ts   = strtotime( $end );
		if ( ! $start_ts || ! $end_ts ) {
			return '';
		}

		$same_day = ( date( 'Ymd', $start_ts ) === date( 'Ymd', $end_ts ) );

		return $same_day
			? date_i18n( 'g:ia', $end_ts ) // 3:45 pm
			: date_i18n( 'g:ia \o\n j F y', $end_ts ); // 3:45 pm on 19 October 25
	}
}
