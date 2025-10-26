<?php
/**
 * IBT Events – Render Functions
 * Provides reusable PHP output for event listings (no shortcodes).
 */

// === 1. Helper: get formatted event field ===============================
// Returns formatted string or HTML for a given meta key.
if ( ! function_exists( 'ibt_events_get_field' ) ) {
	function ibt_events_get_field( $post_id, $key ) {
		$value = get_post_meta( $post_id, $key, true );

		switch ( $key ) {

			case 'ibt_event_start':
				return $value ? ibt_events_format_datetime( $value ) : '';

			case 'ibt_event_end':
				$start = get_post_meta( $post_id, 'ibt_event_start', true );
				return $value ? ibt_events_format_end( $start, $value ) : '';

            case 'ibt_event_venue':
                $venue_id = (int) get_post_meta( $post_id, 'ibt_event_venue_id', true );
                if ( ! $venue_id ) {
                    return '';
                }

                // Fetch the venue post (uses post_title for venue name)
                $venue_post = get_post( $venue_id );
                if ( ! $venue_post ) {
                    return '';
                }

                $venue_name    = $venue_post->post_title;
                $venue_address = get_post_meta( $venue_id, 'ibt_venue_address', true );

                // Build output
                $out  = '<span class="ibt-event-venue-name">' . esc_html( $venue_name ) . '</span>';
                if ( $venue_address ) {
                    $out .= '<br><span class="ibt-event-venue-address">' .
                        nl2br( esc_html( $venue_address ) ) . '</span>';
                }

                return $out;


			case 'ibt_event_map_button':
				$venue_id = get_post_meta( $post_id, 'ibt_event_venue_id', true );
				if ( ! $venue_id ) return '';

				$maploc = get_post_meta( $venue_id, 'ibt_venue_maplocation', true );
				if ( ! $maploc ) return '';

				$url = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $maploc );

				return sprintf(
					'<a class="wp-block-button__link ibt-event-map-btn" href="%s" target="_blank" rel="noopener">View on Google Maps</a>',
					esc_url( $url )
				);

			case 'ibt_event_price_public':
				return $value !== '' ? '£' . number_format( (float) $value, 2 ) : '';

			case 'ibt_event_price_member':
				return $value !== '' ? '£' . number_format( (float) $value, 2 ) : '';

			default:
				return esc_html( $value );
		}
	}
}


// === 2. Render function: ibt_events_render_list() =======================
// Query & output a list of events using direct PHP field calls.

if ( ! function_exists( 'ibt_events_render_list' ) ) {
	function ibt_events_render_list( $args = array() ) {

		$defaults = array(
			'limit'     => 5,
			'show_past' => false,
		);
		$args = wp_parse_args( $args, $defaults );

		$now = current_time( 'mysql' );

		$query_args = array(
			'post_type'      => 'ibt_event',
			'posts_per_page' => (int) $args['limit'],
			'meta_key'       => 'ibt_event_start',
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
		);

		if ( ! $args['show_past'] ) {
			$query_args['meta_query'] = array(
				array(
					'key'     => 'ibt_event_start',
					'value'   => $now,
					'compare' => '>=',
					'type'    => 'DATETIME',
				),
			);
		}

		$q = new WP_Query( $query_args );

		ob_start();

		if ( $q->have_posts() ) : ?>
			<div class="ibt-events-list">
				<?php while ( $q->have_posts() ) :
					$q->the_post();
					$post_id = get_the_ID(); ?>

					<article <?php post_class( 'ibt-event-list-item' ); ?>>
						<h3 class="ibt-event-title">
							<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						</h3>

						<?php
						$start = ibt_events_get_field( $post_id, 'ibt_event_start' );
						$end   = ibt_events_get_field( $post_id, 'ibt_event_end' );
						$venue = ibt_events_get_field( $post_id, 'ibt_event_venue' );
						$map   = ibt_events_get_field( $post_id, 'ibt_event_map_button' );
						?>

						<?php if ( $start ) : ?>
							<p class="ibt-event-date"><strong>Starts:</strong> <?php echo esc_html( $start ); ?></p>
						<?php endif; ?>

						<?php if ( $end ) : ?>
							<p class="ibt-event-end"><strong>Ends:</strong> <?php echo esc_html( $end ); ?></p>
						<?php endif; ?>

						<?php if ( $venue ) : ?>
							<p class="ibt-event-venue"><strong>Venue:</strong><br><?php echo $venue; ?></p>
						<?php endif; ?>

						<?php if ( $map ) : ?>
							<div class="wp-block-buttons"><?php echo $map; ?></div>
						<?php endif; ?>
					</article>

				<?php endwhile; ?>
			</div>
		<?php else : ?>
			<p>No upcoming events found.</p>
		<?php endif;

		wp_reset_postdata();
		return ob_get_clean();
	}
}


// === 3. Optional Shortcode wrapper =====================================
// Allows [ibt_events_list limit="3"] in editor if ever desired.

add_shortcode( 'ibt_events_list', function( $atts = array() ) {
	return ibt_events_render_list( shortcode_atts( array(
		'limit'     => 5,
		'show_past' => false,
	), $atts ) );
});
