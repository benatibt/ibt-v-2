<?php
// Helper functions for Events and Venues (formatting, sanitising, and display logic).


if ( ! defined( 'ABSPATH' ) ) exit;


// Combines separate date and time strings into a single 'Y-m-d H:i:s' format.

function ibt_events_combine_datetime( $date, $time ) {
	if ( empty( $date ) ) {
		return '';
	}
	$time = $time ?: '00:00';
	$ts = strtotime( "$date $time" );
	return $ts ? date( 'Y-m-d H:i:s', $ts ) : '';
}


// Sanitises a price string: strips all but digits and decimal point, limited to 10 characters.

function ibt_events_sanitize_price( $val ) {
	$val = preg_replace( '/[^0-9.]/', '', (string) $val );
	return substr( $val, 0, 10 ); // prevent absurdly long input
}


// Converts a MySQL datetime string into a human-friendly, UK-style format.
// Example: '2025-10-18 12:15:00' → '12:15 pm on 18 October 25'

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


// Formats an event end time intelligently, showing only the time if on the same day.
// Example: '3:45 pm' (same day) or '3:45 pm on 19 October 25' (different day)

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



// Retrieves and formats a given event meta field for display (dates, venue, map, prices, etc.).
// Returns a ready-to-render string or small HTML fragment depending on field type.

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

			case 'ibt_event_remote':
				$is_remote = (int) get_post_meta( $post_id, 'ibt_event_remote', true );
				return $is_remote ? '1' : '';

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
