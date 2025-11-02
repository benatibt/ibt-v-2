<?php
/* -------------------------------------------------------------------------
   IBT EVENTS ARCHIVE – RENDER CALLBACK
   -------------------------------------------------------------------------
   Purpose:
   - Display paginated lists of Events for the /events/ archive block.
   - Supports toggle between upcoming and past events using ?past=1.
   - Outputs full markup including headings, buttons, and pagination.

   Notes:
   - Default mode shows only future events (ordered soonest first).
   - ?past=1 includes all events (ordered newest first).
   - Pagination handled via local WP_Query ($q) with temporary $wp_query swap.
   - Fully compatible with WP 6.8+ block themes; no JavaScript build required.
------------------------------------------------------------------------- */

defined( 'ABSPATH' ) || exit;


// Determine mode
$show_past = isset( $_GET['past'] ) && $_GET['past'] == 1;
$now       = ( new DateTime( 'now', new DateTimeZone( 'Europe/London' ) ) )->format( 'Y-m-d H:i' );
$paged     = max( 1, get_query_var( 'paged' ) );

// Setup pagination
$per_page = 10;
$paged    = max( 1, get_query_var( 'paged' ) );

if ( $show_past ) {
	// Show past selected. Show all events. Sort descending.
	$order       = 'DESC';
	$meta_query  = [];
} else {
	// Show future selected. Filter by event_end < now. Sort ascending.
	$order       = 'ASC';
	$meta_query  = [[
		'key'     => 'ibt_event_end',
		'value'   => $now,
		'compare' => '>=',
		'type'    => 'DATETIME',
	]];
}

// --- Run query ---
$q = new WP_Query([
	'post_type'      => 'ibt_event',
	'post_status'    => 'publish',
	'posts_per_page' => $per_page,
	'paged'          => $paged,
	'meta_key'       => 'ibt_event_start',
	'orderby'        => 'meta_value',
	'order'          => $order,
	'meta_query'     => $meta_query,
]);


// Wrapper
echo '<div class="ibt-events-archive alignwide">';

// --- Heading row with toggle button ---
$title_text   = $show_past ? __( 'All Events', 'ibt' ) : __( 'Upcoming Events', 'ibt' );
$toggle_url   = $show_past ? get_post_type_archive_link( 'ibt_event' ) : add_query_arg( 'past', 1, get_post_type_archive_link( 'ibt_event' ) );
$toggle_label = $show_past ? __( 'Show upcoming events', 'ibt' ) : __( 'Include History', 'ibt' );

echo '<div class="ibt-archive-header">';
	echo '<h1 class="wp-block-heading ibt-archive-title">' . esc_html( $title_text ) . '</h1>';

	echo do_blocks( '
		<!-- wp:buttons {"className":"is-content-justification-center"} -->
		<div class="wp-block-buttons is-content-justification-center">
			<!-- wp:button {"className":"is-style-primary-solid ibt-event-toggle-btn"} -->
			<div class="wp-block-button is-style-primary-solid ibt-event-toggle-btn">
				<a class="wp-block-button__link wp-element-button" href="' . esc_url( $toggle_url ) . '" style="padding-top:0;padding-bottom:0">'
					. esc_html( $toggle_label ) .
				'</a>
			</div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	' );


echo '</div>'; // .ibt-archive-header
echo '<hr class="ibt-event-divider-top" />';

// --- Event list ---
if ( $q->have_posts() ) {
	echo '<div class="ibt-event-list">';

	while ( $q->have_posts() ) {
		$q->the_post();

		$presenter = do_shortcode( '[ibt_event_field key="ibt_event_presenter"]' );
		$start     = do_shortcode( '[ibt_event_field key="ibt_event_start"]' );
		$venue     = do_shortcode( '[ibt_event_field key="ibt_event_venue"]' );
		$online    = do_shortcode( '[ibt_event_field key="ibt_event_online"]' );

		echo '<article class="ibt-event-list-item">';
		echo '<h2 class="ibt-event-title"><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h2>';

		// Meta info — native block column layout
		echo '<div class="wp-block-columns ibt-event-meta" style="align-items:flex-start">';
			echo '<div class="wp-block-column">';
				if ( $presenter ) echo '<p><strong>Presenter:</strong> ' . wp_kses_post( $presenter ) . '</p>';
				if ( $start )     echo '<p><strong>When:</strong> ' . wp_kses_post( $start ) . '</p>';
			echo '</div>';

			echo '<div class="wp-block-column">';
				if ( $venue )  echo '<p><strong>Venue:</strong> ' . wp_kses_post( $venue ) . '</p>';
				if ( $online ) echo $online;
			echo '</div>';
		echo '</div>'; // .wp-block-columns

		// Excerpt now below the meta block
		the_excerpt();

		echo '<hr class="ibt-event-divider" />';
		echo '</article>';
	}

	echo '</div>'; // .ibt-event-list

	// --- Pagination ---
	// Temporarily replace global $wp_query with local query $q during pagination
	// render so it is based off on filtered query rather than WP default query.
	echo '<div class="ibt-pagination">';
	global $wp_query;
	$backup_wp_query = $wp_query;
	$wp_query = $q;

	the_posts_pagination( [
		'mid_size'  => 2,
		'prev_text' => __( '← Previous', 'ibt' ),
		'next_text' => __( 'Next →', 'ibt' ),
	] );

	$wp_query = $backup_wp_query;

	// Render rest of page
	echo '</div>';


	wp_reset_postdata();
} else {
	echo '<p>' . esc_html__( 'No events found.', 'ibt' ) . '</p>';
}

echo '</div>'; // .ibt-events-archive
return '';
