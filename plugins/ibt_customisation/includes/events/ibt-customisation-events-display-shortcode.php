<?php
// Handles shortcode output for single event fields and for [ibt_events_list] listings.


if ( ! defined( 'ABSPATH' ) ) exit;


// Provides [ibt_event_field key="meta_key"] for safe public fields only.
// Handles date formatting, venue details, and optional Google Maps button.
// Outputs a single field per shortcode instance.
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
		$post_id = isset( $q->ID ) ? (int) $q->ID : 0;
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
		'ibt_event_presenter',
		'ibt_event_online',
		'ibt_event_venue',
		'ibt_event_venue_name',
		'ibt_event_venue_address',
		'ibt_event_excerpt',
		'ibt_event_map_button',
	);
	if ( ! in_array( $key, $allowed_keys, true ) ) {
		return '';
	}

	// ---- Get value via helpers (single source of truth) ----
	$value = ibt_events_get_field( $post_id, $key );

	// Nothing to render
	if ( $value === '' ) {
		return '';
	}

	// ---- Build a standard wrapper for CSS targeting ----
	$class = 'ibt-event-field ibt-event-field--' . sanitize_html_class( str_replace( '_', '-', $key ) );

	// ---- Handle HTML vs plain text ----
	$is_html = ( strip_tags( $value ) !== $value );

	// If it's HTML (like the map button), inject the wrapper class into the first tag
	if ( $is_html ) {
		// Already has class attr?
		if ( preg_match( '/^<([a-z0-9]+)\s+[^>]*class=("|\')(.*?)\2/i', $value ) ) {
			$value = preg_replace(
				'/^<([a-z0-9]+)\s+([^>]*?)class=("|\')(.*?)\3/i',
				'<$1 $2class=$3$4 ' . esc_attr( $class ) . '$3',
				$value,
				1
			);
		} else {
			// No class attribute yet — inject one
			$value = preg_replace(
				'/^<([a-z0-9]+)(\s+[^>]*)?>/i',
				'<$1 class="' . esc_attr( $class ) . '"$2>',
				$value,
				1
			);
		}
		return $value;
	}

	// Plain text → wrap in span
	return '<span class="' . esc_attr( $class ) . '">' . esc_html( $value ) . '</span>';
});




// Allows [ibt_events_list limit="n"] as a list of n = 1 to 5 events on a page.

add_shortcode( 'ibt_events_list', function( $atts = array() ) {
	return ibt_events_render_list( shortcode_atts( array(
		'limit'     => 5,
		'show_past' => false,
	), $atts ) );
});



// Query & output a list of events using direct PHP field calls.
// Used internally by [ibt_events_list] shortcode. 
// Could be be called directly from templates - Move to helpers if this happens.

function ibt_events_render_list( $atts = array() ) {
	$atts = shortcode_atts(
		array(
			'n' => 3, // number of events to show
		),
		$atts,
		'ibt_events_list'
	);

	// Set to fixed UK time to avoid issues with server or WP timezone setup.
	// NOTE: Only suitable for UK deployments (Europe/London DST aware).
	$now = ( new DateTime( 'now', new DateTimeZone( 'Europe/London' ) ) )->format( 'Y-m-d H:i' );

	$query = new WP_Query( array(
		'post_type'      => 'ibt_event',
		'posts_per_page' => (int) $atts['n'],
		'post_status'    => 'publish',
		'meta_key'       => 'ibt_event_start',
		'orderby'        => 'meta_value',
		'order'          => 'ASC',
		'meta_query'     => array(
			'relation' => 'AND',
			// Event must not be past (now <= end OR no end set)
			array(
				'relation' => 'OR',
				array(
					'key'     => 'ibt_event_end',
					'value'   => $now,
					'compare' => '>=',
					'type'    => 'DATETIME',
				),
				array(
					'key'     => 'ibt_event_end',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => 'ibt_event_end',
					'value'   => '',
					'compare' => '=',
				),
			),
		),
	) );

	// --- Optional featured substitution for lists of 3+ ---
	$posts = $query->posts;

	if ( count( $posts ) >= (int) $atts['n'] ) {

		// Check whether any of the upcoming events are already featured
		$has_featured = false;
		foreach ( $posts as $p ) {
			if ( get_post_meta( $p->ID, 'ibt_event_featured', true ) ) {
				$has_featured = true;
				break;
			}
		}

		// If none are featured, fetch the next future featured event
		if ( ! $has_featured ) {
			$featured = new WP_Query( array(
				'post_type'      => 'ibt_event',
				'posts_per_page' => 1,
				'post_status'    => 'publish',
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'relation' => 'OR',
						array(
							'key'     => 'ibt_event_end',
							'value'   => $now,
							'compare' => '>=',
							'type'    => 'DATETIME',
						),
						array(
							'key'     => 'ibt_event_end',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => 'ibt_event_end',
							'value'   => '',
							'compare' => '=',
						),
					),
					array(
						'key'     => 'ibt_event_featured',
						'value'   => '1',
						'compare' => '=',
					),
				),
				'meta_key'  => 'ibt_event_start',
				'orderby'   => 'meta_value',
				'order'     => 'ASC',
			) );

			if ( $featured->have_posts() ) {
				$featured_post = $featured->posts[0];

				// Replace the last event with the featured one
				array_pop( $posts );
				$posts[] = $featured_post;

				// Resort chronologically
				usort( $posts, function ( $a, $b ) {
					$a_start = get_post_meta( $a->ID, 'ibt_event_start', true );
					$b_start = get_post_meta( $b->ID, 'ibt_event_start', true );
					return strcmp( $a_start, $b_start );
				} );

				// Replace query posts so the existing loop uses updated list
				$query->posts = $posts;
				$query->post_count = count( $posts );
			}
		}
	}


	$out = '<div class="ibt-event-list">';

	while ( $query->have_posts() ) {
		$query->the_post();
		$post_id = get_the_ID();

		$out .= '<article class="ibt-event-list-item">';
		$out .= '<h3 class="ibt-event-title"><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h3>';

		// Excerpt
		$excerpt = trim( do_shortcode( '[ibt_event_field key="ibt_event_excerpt"]' ) );
		if ( $excerpt !== '' ) {
			$out .= $excerpt;
		}

		// Presenter
		$presenter = do_shortcode( '[ibt_event_field key="ibt_event_presenter"]' );
		if ( ! empty( trim( $presenter ) ) ) {
			$out .= '<p><strong>Presenter:</strong> ' . $presenter . '</p>';
		}

		// Starts
		$start = do_shortcode( '[ibt_event_field key="ibt_event_start"]' );
		if ( ! empty( trim( $start ) ) ) {
			$out .= '<p><strong>Starts:</strong> ' . $start . '</p>';
		}

		// Venue
		$venue = do_shortcode( '[ibt_event_field key="ibt_event_venue"]' );
		if ( ! empty( trim( $venue ) ) ) {
			$out .= '<p><strong>Venue:</strong> ' . $venue . '</p>';
		}

		// Online flag (collapses automatically when empty)
		$online = do_shortcode( '[ibt_event_field key="ibt_event_online"]' );
		if ( ! empty( trim( $online ) ) ) {
			$out .= $online;
		}

		$out .= '</article>';
	}

	wp_reset_postdata();

	$out .= '</div>';

	return $out;
}