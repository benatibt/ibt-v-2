<?php
// Handles shortcode output for single event fields and for [ibt_events_list] listings.


// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


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
                        
                        <?php
                        $venue_id = ibt_events_get_field( $post_id, 'ibt_event_venue_id' );
                        $venue    = $venue_id ? get_the_title( $venue_id ) : '';
                        $remote   = ibt_events_get_field( $post_id, 'ibt_event_remote' );

                        if ( $venue ) :
                            ?>
                            <p class="ibt-event-venue">
                                <strong>Venue:</strong>
                                <?php
                                echo esc_html( $venue );
                                if ( $remote ) :
                                    echo ' <span class="ibt-event-online-access">+ Online Access</span>';
                                endif;
                                ?>
                            </p>
                            <?php
                        endif;


						if ( $map ) echo $map; ?>

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


