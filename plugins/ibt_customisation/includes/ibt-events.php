<?php
/**
 * IBT Events — Custom Events and Venues
 * Part of ibt_customisation plugin
 */

// === 1. Register CPTs =======================================================
add_action( 'init', 'ibt_events_register_cpts' );
function ibt_events_register_cpts() {

	// --- Event CPT ---------------------------------------------------------
	register_post_type( 'ibt_event', array(
		'labels' => array(
			'name'          => __( 'Events', 'ibt-events' ),
			'singular_name' => __( 'Event', 'ibt-events' ),
		),
		'public'       => true,
		'has_archive'  => true,
		'rewrite'      => array( 'slug' => 'events' ),
		'show_in_rest' => true,
		'menu_icon'    => 'dashicons-calendar-alt',
		'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author' ),
	) );

	// --- Venue CPT ---------------------------------------------------------
	register_post_type( 'ibt_venue', array(
		'labels' => array(
			'name'          => __( 'Venues', 'ibt-events' ),
			'singular_name' => __( 'Venue', 'ibt-events' ),
		),
		'public'       => true,
		'has_archive'  => false,
		'rewrite'      => array( 'slug' => 'venues' ),
		'show_in_rest' => true,
		'menu_icon'    => 'dashicons-location-alt',
		'supports'     => array( 'title', 'thumbnail' ),
	) );
}

// === 2. Meta boxes setup ====================================================

// Add Event Details meta box
add_action( 'add_meta_boxes', function() {
	add_meta_box(
		'ibt_event_details',
		__( 'Event Details', 'ibt-events' ),
		'ibt_events_render_details_metabox',
		'ibt_event',
		'normal',
		'default'
	);
});

// Add Venue Details meta box
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

// === 3.3 – Render Event meta box UI ========================================
function ibt_events_render_details_metabox( $post ) {

	wp_nonce_field( 'ibt_events_save_meta', 'ibt_events_meta_nonce' );

	$venue_id   = get_post_meta( $post->ID, 'ibt_event_venue_id', true );
	$price_pub  = get_post_meta( $post->ID, 'ibt_event_price_public', true );
	$price_mem  = get_post_meta( $post->ID, 'ibt_event_price_member', true );
	$remote     = (int) get_post_meta( $post->ID, 'ibt_event_remote', true );
	$featured   = (bool) get_post_meta( $post->ID, 'ibt_event_featured', true );
	$notes      = get_post_meta( $post->ID, 'ibt_event_notes', true );

	// --- Venue -----------------------------------------------------------
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

	// --- Pricing ---------------------------------------------------------
	echo '<p><strong>' . esc_html__( 'Pricing (£)', 'ibt-events' ) . '</strong><br />';
	echo '<label>' . esc_html__( 'Public:', 'ibt-events' ) . ' ';
	echo '<input type="text" name="ibt_event_price_public" value="' . esc_attr( $price_pub ) . '" size="8" /></label> ';
	echo '<label>' . esc_html__( 'Member:', 'ibt-events' ) . ' ';
	echo '<input type="text" name="ibt_event_price_member" value="' . esc_attr( $price_mem ) . '" size="8" /></label></p>';

	// --- Remote / Online flag -------------------------------------------
	echo '<p><label for="ibt_event_remote">';
	echo '<input type="checkbox" name="ibt_event_remote" id="ibt_event_remote" value="1" ' . checked( $remote, 1, false ) . ' />';
	echo ' ' . esc_html__( 'Remote / Online event', 'ibt-events' ) . '</label></p>';
	echo '<p class="description">' . esc_html__( 'Tick if participants can attend remotely (Zoom, Teams, etc.). '
		. 'If a public link should be visible, include it in the post content.', 'ibt-events' ) . '</p>';

	// --- Featured --------------------------------------------------------
	echo '<p><label>';
	echo '<input type="checkbox" name="ibt_event_featured" value="1" ' . checked( $featured, true, false ) . ' />';
	echo ' ' . esc_html__( 'Mark as featured event', 'ibt-events' ) . '</label></p>';

	// --- Notes -----------------------------------------------------------
	echo '<p><strong>' . esc_html__( 'Notes', 'ibt-events' ) . '</strong><br />';
	echo '<textarea name="ibt_event_notes" rows="4" style="width:100%;">' . esc_textarea( $notes ) . '</textarea></p>';
}

// === 3.4 – Save handler =====================================================
function ibt_events_save_details_meta( $post_id ) {

	// --- Security checks -------------------------------------------------
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

	// --- Collect and sanitise -------------------------------------------
	$venue_id  = absint( $_POST['ibt_event_venue_id'] ?? 0 );
	$price_pub = ibt_events_sanitize_price( $_POST['ibt_event_price_public'] ?? '' );
	$price_mem = ibt_events_sanitize_price( $_POST['ibt_event_price_member'] ?? '' );
	$remote    = ! empty( $_POST['ibt_event_remote'] ) ? 1 : 0;
	$featured  = ! empty( $_POST['ibt_event_featured'] ) ? 1 : 0;
	$notes     = sanitize_textarea_field( $_POST['ibt_event_notes'] ?? '' );

	// --- Save ------------------------------------------------------------
	update_post_meta( $post_id, 'ibt_event_venue_id', $venue_id );
	update_post_meta( $post_id, 'ibt_event_price_public', $price_pub );
	update_post_meta( $post_id, 'ibt_event_price_member', $price_mem );
	update_post_meta( $post_id, 'ibt_event_remote', $remote );
	update_post_meta( $post_id, 'ibt_event_featured', $featured );
	update_post_meta( $post_id, 'ibt_event_notes', $notes );
}
add_action( 'save_post_ibt_event', 'ibt_events_save_details_meta' );

// === 3.5 – Helper: price sanitiser =========================================
function ibt_events_sanitize_price( $val ) {
	$val = preg_replace( '/[^0-9.]/', '', (string) $val );
	return substr( $val, 0, 10 ); // prevent absurdly long input
}

// === 4.1 – Venue Meta Boxes ================================================

// Add meta boxes to the Venue CPT
function ibt_venue_details_metabox( $post ) {
	wp_nonce_field( 'ibt_venue_meta_save', 'ibt_venue_meta_nonce' );

	$address     = get_post_meta( $post->ID, 'ibt_venue_address', true );
	$maplocation = get_post_meta( $post->ID, 'ibt_venue_maplocation', true );
	?>
	<p><label for="ibt_venue_address"><strong><?php _e( 'Venue address', 'ibt-events' ); ?></strong></label></p>
	<textarea name="ibt_venue_address" id="ibt_venue_address" rows="4" style="width:100%;"><?php echo esc_textarea( $address ); ?></textarea>
	<p class="description"><?php _e( 'Use one line per address component.', 'ibt-events' ); ?></p>

	<p><label for="ibt_venue_maplocation"><strong><?php _e( 'Map location (lat,long or Plus Code)', 'ibt-events' ); ?></strong></label></p>
	<input type="text" name="ibt_venue_maplocation" id="ibt_venue_maplocation"
		   value="<?php echo esc_attr( $maplocation ); ?>" style="width:100%;" maxlength="100" />
	<p class="description"><?php _e( 'Example: 58.091639,-6.606250 or 39RV+JGF Balallan UK', 'ibt-events' ); ?></p>
	<?php
}

add_action( 'save_post_ibt_venue', function( $post_id ) {
	if ( ! isset( $_POST['ibt_venue_meta_nonce'] ) ||
	     ! wp_verify_nonce( $_POST['ibt_venue_meta_nonce'], 'ibt_venue_meta_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( wp_is_post_revision( $post_id ) ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

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
