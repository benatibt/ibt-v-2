<?php
// Adds and manages metaboxes for Events and Venues in the admin editor.


// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


// Enqueue admin-only CSS for Events & Venues metabox styling
// DEV - includes cache buster

add_action( 'admin_enqueue_scripts', function( $hook ) {
	global $post_type;

	if ( in_array( $post_type, array( 'ibt_event', 'ibt_venue' ), true ) ) {
		$css_rel  = '/css/ibt-events-admin.css';
		$css_path = dirname( dirname( __DIR__ ) ) . $css_rel;
		$css_url  = plugins_url( basename( dirname( dirname( __DIR__ ) ) ) . $css_rel );

		if ( file_exists( $css_path ) ) {
			wp_enqueue_style(
				'ibt-events-admin',
				$css_url,
				array(),
				filemtime( $css_path )
			);
		}
	}
});



// ================================== EVENTS =======================================

// Register the Event Details metabox

add_action( 'add_meta_boxes', 'ibt_events_add_metabox' );
function ibt_events_add_metabox() {
	add_meta_box(
		'ibt_event_details',
		__( 'Event Details', 'ibt-events' ),
		'ibt_events_render_metabox',
		'ibt_event',
		'normal',
		'default'
	);
}


// Render the metabox with event fields.

function ibt_events_render_metabox( $post ) {

	// Shared nonce for all event fields
	wp_nonce_field( 'ibt_events_save_meta', 'ibt_events_meta_nonce' );

	// --- Retrieve stored meta values ---
	$start     = get_post_meta( $post->ID, 'ibt_event_start', true );
	$end       = get_post_meta( $post->ID, 'ibt_event_end', true );
	$venue_id  = get_post_meta( $post->ID, 'ibt_event_venue_id', true );
	$remote    = get_post_meta( $post->ID, 'ibt_event_remote', true );
	$price_pub = get_post_meta( $post->ID, 'ibt_event_price_public', true );
	$price_mem = get_post_meta( $post->ID, 'ibt_event_price_member', true );
	$featured  = (bool) get_post_meta( $post->ID, 'ibt_event_featured', true );
	$notes     = get_post_meta( $post->ID, 'ibt_event_notes', true );

	// Split datetimes into parts for inputs
	$start_date = $start ? date( 'Y-m-d', strtotime( $start ) ) : '';
	$start_time = $start ? date( 'H:i',   strtotime( $start ) ) : '';
	$end_date   = $end   ? date( 'Y-m-d', strtotime( $end ) )   : '';
	$end_time   = $end   ? date( 'H:i',   strtotime( $end ) )   : '';

	// --- Retrieve dynamic field data before rendering ---
	$presenter = get_post_meta( $post->ID, 'ibt_event_presenter', true );

	$venues = get_posts( array(
		'post_type'      => 'ibt_venue',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	) );

	// === Row 1: What (col 1) + Where (col 2) ============================
	echo '<div class="ibt-event-row">';

		// Column 1 - What
		echo '<div class="ibt-event-group ibt-event-admin-col1">';
		echo '<p><strong>' . esc_html__( 'What:', 'ibt-events' ) . '</strong></p>';
		echo '<p><label>' . esc_html__( 'Presenter:', 'ibt-events' ) . ' ';
		echo '<input type="text" name="ibt_event_presenter" value="' . esc_attr( $presenter ) . '" style="width:100%;" /></label></p>';
		echo '<p><label><input type="checkbox" name="ibt_event_featured" value="1" ' .
			checked( $featured, true, false ) . ' /> ' . esc_html__( 'Featured event', 'ibt-events' ) . '</label></p>';
		echo '</div>';

		// Column 2 - Where
		echo '<div class="ibt-event-group ibt-event-admin-col2">';
		echo '<p><strong>' . esc_html__( 'Where:', 'ibt-events' ) . '</strong></p>';
		echo '<p><label>' . esc_html__( 'Venue:', 'ibt-events' ) . '</label><br>';
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
		echo '<p><label><input type="checkbox" id="ibt_event_remote" name="ibt_event_remote" value="1" ' .
			checked( $remote, '1', false ) . ' /> ' . esc_html__( 'Online event available', 'ibt-events' ) . '</label></p>';
		echo '</div>';

	echo '</div>'; // end row 1


	// === Row 2: When (col 1) + Cost (col 2) =============================
	echo '<div class="ibt-event-row">';

		// Column 1 - When
		echo '<div class="ibt-event-group ibt-event-admin-col1">';
		echo '<p><strong>' . esc_html__( 'When:', 'ibt-events' ) . '</strong></p>';
		echo '<p><label>' . esc_html__( 'Start Date:', 'ibt-events' ) . ' ';
		echo '<input type="date" name="ibt_event_start_date" value="' . esc_attr( $start_date ) . '" /></label> ';
		echo '<label>' . esc_html__( 'Time:', 'ibt-events' ) . ' ';
		echo '<input type="time" name="ibt_event_start_time" value="' . esc_attr( $start_time ) . '" /></label></p>';
		echo '<p><label>' . esc_html__( 'End Date:', 'ibt-events' ) . ' ';
		echo '<input type="date" name="ibt_event_end_date" value="' . esc_attr( $end_date ) . '" /></label> ';
		echo '<label>' . esc_html__( 'Time:', 'ibt-events' ) . ' ';
		echo '<input type="time" name="ibt_event_end_time" value="' . esc_attr( $end_time ) . '" /></label></p>';
		echo '</div>';

		// Column 2 - Cost
		echo '<div class="ibt-event-group ibt-event-admin-col2">';
		echo '<p><strong>' . esc_html__( 'Cost (£):', 'ibt-events' ) . '</strong></p>';
		echo '<p><label>' . esc_html__( 'Public:', 'ibt-events' ) . ' ';
		echo '<input type="text" name="ibt_event_price_public" value="' . esc_attr( $price_pub ) . '" size="8" /></label> ';
		echo '<label>' . esc_html__( 'Member:', 'ibt-events' ) . ' ';
		echo '<input type="text" name="ibt_event_price_member" value="' . esc_attr( $price_mem ) . '" size="8" /></label></p>';
		echo '</div>';

	echo '</div>'; // end row 2


	// === Row 3: Notes (full width) ======================================
	echo '<div class="ibt-event-row">';
		echo '<div class="ibt-event-group ibt-event-admin-full">';
		echo '<p><strong>' . esc_html__( 'Internal Notes:', 'ibt-events' ) . '</strong></p>';
		echo '<textarea name="ibt_event_notes" rows="4" style="width:100%;">' .
			esc_textarea( $notes ) . '</textarea>';
		echo '</div>';
	echo '</div>'; // end row 3

}


// Handles saving of all event-related meta fields from the admin metabox:
// date/time, venue, online flag, prices, featured toggle, and notes.

add_action( 'save_post_ibt_event', 'ibt_events_save_all_meta' );
function ibt_events_save_all_meta( $post_id ) {

	// --- Verify nonce and permissions ---
	if ( ! isset( $_POST['ibt_events_meta_nonce'] ) ||
	     ! wp_verify_nonce( $_POST['ibt_events_meta_nonce'], 'ibt_events_save_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	// --- 1. Date/time ---
	$start_date = sanitize_text_field( $_POST['ibt_event_start_date'] ?? '' );
	$start_time = sanitize_text_field( $_POST['ibt_event_start_time'] ?? '' );
	$end_date   = sanitize_text_field( $_POST['ibt_event_end_date']   ?? '' );
	$end_time   = sanitize_text_field( $_POST['ibt_event_end_time']   ?? '' );

	$start_combined = ibt_events_combine_datetime( $start_date, $start_time );
	$end_combined   = ibt_events_combine_datetime( $end_date,   $end_time );

	update_post_meta( $post_id, 'ibt_event_start', $start_combined );
	update_post_meta( $post_id, 'ibt_event_end',   $end_combined );

	// --- 2. Venue & remote flag ---
	$venue_id = absint( $_POST['ibt_event_venue_id'] ?? 0 );
	$remote   = ! empty( $_POST['ibt_event_remote'] ) ? '1' : '0';

	update_post_meta( $post_id, 'ibt_event_venue_id', $venue_id );
	update_post_meta( $post_id, 'ibt_event_remote', $remote );

	// --- 2b. Presenter ---
	$presenter = sanitize_text_field( $_POST['ibt_event_presenter'] ?? '' );
	update_post_meta( $post_id, 'ibt_event_presenter', $presenter );


	// --- 3. Pricing ---
	$price_pub = ibt_events_sanitize_price( $_POST['ibt_event_price_public'] ?? '' );
	$price_mem = ibt_events_sanitize_price( $_POST['ibt_event_price_member'] ?? '' );

	update_post_meta( $post_id, 'ibt_event_price_public', $price_pub );
	update_post_meta( $post_id, 'ibt_event_price_member', $price_mem );

	// --- 4. Featured flag ---
	$featured = ! empty( $_POST['ibt_event_featured'] ) ? 1 : 0;
	update_post_meta( $post_id, 'ibt_event_featured', $featured );

	// --- 5. Notes ---
	$notes = sanitize_textarea_field( $_POST['ibt_event_notes'] ?? '' );
	update_post_meta( $post_id, 'ibt_event_notes', $notes );
}


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

// Callback to render the venue meta box fields

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

	// --- Island field ---
	$island = get_post_meta( $post->ID, 'ibt_venue_island', true );
	?>
	<p><label for="ibt_venue_island"><strong><?php _e( 'Island', 'ibt-events' ); ?></strong></label></p>
	<input type="text" name="ibt_venue_island" id="ibt_venue_island"
		value="<?php echo esc_attr( $island ); ?>" style="width:100%;" maxlength="100" />
	<p class="description"><?php _e( 'Short location name for summaries, e.g. "Isle of Lewis".', 'ibt-events' ); ?></p>
	<?php
   
}

// Save venue handler

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

	if ( isset( $_POST['ibt_venue_island'] ) ) {
    $val = substr( sanitize_text_field( $_POST['ibt_venue_island'] ), 0, 100 );
    update_post_meta( $post_id, 'ibt_venue_island', $val );
	}

});