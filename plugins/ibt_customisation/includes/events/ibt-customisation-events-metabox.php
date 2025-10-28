<?php
/**
 * Adds and manages metaboxes for Events and Venues in the admin editor.
 * Part of Events in the IBT Customisation plugin.
 */


// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


// ================================== EVENTS =======================================

// Adds the "Event Date & Time" metabox to the Event editor screen.
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


// Renders the "Event Date & Time" metabox fields with native date/time inputs.
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


// Adds the "Event Details" metabox for entering venue, prices, featured flag, and notes.
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


// Renders the "Event Details" metabox with venue dropdown, pricing, featured flag, and notes.
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





// Saves start and end datetime values when the Event post is saved.
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



// Saves Event Details fields (venue, prices, featured flag, notes, remote option) on post save.
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

// ================================== VENUE =======================================

// Adds and manages meta boxes for the Venue CPT (address and map location fields).

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

