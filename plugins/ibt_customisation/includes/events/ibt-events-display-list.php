<?php
/* -------------------------------------------------------------------------
   IBT EVENTS DISPLAY – LIST
   -------------------------------------------------------------------------
   Purpose:
   - Render lists of events for use in shortcode.
   - Handles featured-event substitution and list layout markup.

   Notes:
   - Uses ibt_events_get_field() for data access and formatting.
   - Returns complete HTML strings; never echoes.
   - No block or query registration — purely display logic.
------------------------------------------------------------------------- */

if ( ! defined( 'ABSPATH' ) ) exit;


// Query & output for 'ibt_events_list' to return a list of the next n events with featured event logic
// using direct PHP query and indicidual shortcodes to build the fields.

function ibt_events_render_list( $atts = array() ) {

	$atts = shortcode_atts(
		array(
			'n' => 3, // number of events to show
		),
		$atts,
		'ibt_events_list'
	);

	// --- Safety clamp for n (1–10) ---
	$n = max( 1, min( (int) $atts['n'], 10 ) );

	// Set to fixed UK time to avoid issues with server or WP timezone setup.
	// NOTE: Only suitable for UK deployments (Europe/London DST aware).
	$now = ( new DateTime( 'now', new DateTimeZone( 'Europe/London' ) ) )->format( 'Y-m-d H:i' );

	// --- Step 1: build $events_main (Next n future events) ---
	$args_main = array(
		'post_type'      => 'ibt_event',
		'posts_per_page' => $n,
		'post_status'    => 'publish',
		'meta_key'       => 'ibt_event_start',
		'orderby'        => 'meta_value',
		'order'          => 'ASC',
		'meta_query'     => array(
			array(
				'key'     => 'ibt_event_end',
				'value'   => $now,
				'compare' => '>=',
				'type'    => 'DATETIME',
			),
		),
	);


	$query_main   = new WP_Query( $args_main );
	$events_main  = $query_main->posts;
	$events_final = array();

	// --- Step 2: n ≤ 2 → output main list immediately, no featured event logic required ---
	if ( $n <= 2 ) {
		$events_final = $events_main;
		goto render_output;
	}

	// --- Step 3: check if there is an existing featured event in the query. If yes no feautured event logic required. ---
	$has_featured = false;
	foreach ( $events_main as $em ) {
		if ( get_post_meta( $em->ID, 'ibt_event_featured', true ) ) {
			$has_featured = true;
			break;
		}
	}
	if ( $has_featured ) {
		$events_final = $events_main;
		goto render_output;
	}

	// --- Step 4: fetch next future featured event for use in step 5 & 6 ---
	$args_featured = array(
		'post_type'      => 'ibt_event',
		'posts_per_page' => 1,
		'post_status'    => 'publish',
		'meta_key'       => 'ibt_event_start',
		'orderby'        => 'meta_value',
		'order'          => 'ASC',
		'meta_query'     => array(
			array(
				'key'     => 'ibt_event_end',
				'value'   => $now,
				'compare' => '>=',
				'type'    => 'DATETIME',
			),
			array(
				'key'     => 'ibt_event_featured',
				'value'   => '1',
				'compare' => '=',
			),
		),
	);
	$query_featured = new WP_Query( $args_featured );

	if ( ! $query_featured->have_posts() ) {
		// Step 5: no future featured events so output main list
		$events_final = $events_main;
		goto render_output;
	}

	// --- Step 6: build $events_final with last event in list substituted for first featured event ---
	$featured_post = $query_featured->posts[0];
	$events_final  = $events_main;

	// Replace last event with featured one
	array_pop( $events_final );
	$events_final[] = $featured_post;

	// Resort chronologically by start date
	usort( $events_final, function ( $a, $b ) {
		return strcmp(
			get_post_meta( $a->ID, 'ibt_event_start', true ),
			get_post_meta( $b->ID, 'ibt_event_start', true )
		);
	});

	/**
	 * ------------------------------------------------------------------------
	 * render_output:
	 * Unified render exit point for all query paths.
	 *
	 * The logic above selects which set of events to render:
	 *   2 → $events_main (n ≤ 2, skip featured logic)
	 *   3 → $events_main (already includes a featured event)
	 *   5 → $events_main (no featured found)
	 *   6 → $events_final (featured substituted in)
	 *
	 * Each path defines $events_final before jumping here.
	 * We then inject that array back into $query_main->posts so the
	 * existing render loop below can run unchanged.
	 *
	 * Equivalent to: $query = new WP_Query(); $query->posts = $events_final;
	 * Using goto avoids repeating the 40-line render loop four times.
	 * ------------------------------------------------------------------------
	 */

render_output:

    // Add div encapsulation and section heading
    $out = '<div class="ibt-event-list">';
    $out .= '<h2 class="ibt-events-list-heading">' . esc_html__( 'Upcoming Events', 'ibt-events' ) . '</h2>';

    // Loop through query adding html formatted fields to list
    foreach ( $events_final as $event_post ) {
        // Prepare global $post so template tags work (get_the_title(), etc.)
        setup_postdata( $event_post );

        $post_id = $event_post->ID;

        $out .= '<article class="ibt-event-list-item">';
        $out .= '<h3 class="ibt-event-title"><a href="' . esc_url( get_permalink( $post_id ) ) . '">' . esc_html( get_the_title( $post_id ) ) . '</a></h3>';

        // Pull pre-formatted field fragments
        $start     = ibt_events_render_field( $post_id, 'ibt_event_start' );
        $venue     = ibt_events_render_field( $post_id, 'ibt_event_venue' );
        $online    = ibt_events_render_field( $post_id, 'ibt_event_online' );

        // Append each field to $out
        if ( $start !== '' )     { $out .= '<p><strong>When:</strong> ' . $start . '</p>'; }
        if ( $online !== '' )    { $out .= $online; }
        if ( $venue !== '' )     { $out .= '<p><strong>Where:</strong> ' . $venue . '</p>'; }
        
        $out .= '</article>';
    }

    // Clean up global $post after setup_postdata()
    wp_reset_postdata();

    $out .= '</div>';

    // Return the buffer
    return $out;

}