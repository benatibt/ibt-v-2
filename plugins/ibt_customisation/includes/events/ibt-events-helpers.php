<?php
/* -------------------------------------------------------------------------
   IBT EVENTS HELPERS
   -------------------------------------------------------------------------
   Purpose:
   - Provide reusable formatting and sanitisation helpers for the Events system.
   - No HTML layout or WP_Query logic here — these functions are used by
     display-single.php, display-list.php, and shortcodes.

   Timezone policy:
   - Datetimes stored and displayed in site local time (Europe/London).
   - Exports and APIs convert to UTC as a single normalisation step.

   Note:
   - All functions return strings, never echo.
   - Escaping is applied where required for direct template output.
------------------------------------------------------------------------- */

if ( ! defined( 'ABSPATH' ) ) exit;


// Combine separate date and time inputs into a single MySQL-style 'Y-m-d H:i:s' string.
// Used when saving event meta to ensure consistent format.

function ibt_events_combine_datetime( $date, $time ) {
	if ( empty( $date ) ) {
		return '';
	}
	$time = $time ?: '00:00';
	$ts = strtotime( "$date $time" );
	return $ts ? date( 'Y-m-d H:i:s', $ts ) : '';
}


// Strip all non-numeric/decimal characters from a price string.
// Prevents unsafe input before saving to meta.

function ibt_events_sanitize_price( $val ) {
	$val = preg_replace( '/[^0-9.]/', '', (string) $val );
	return substr( $val, 0, 10 ); // prevent absurdly long input
}


// Convert a MySQL datetime string to a short UK format (site local time).
// Example: '2025-10-18 12:15:00' → '18th Oct 25 at 12:15 pm'
// Uses ordinal day and abbreviated month for compact mobile display.

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

		return date_i18n( 'jS M y \a\t g:ia', $ts ); // e.g. 18th Oct 25 at 12:15 pm
	}
}


// Format event end time intelligently.
// Shows only time if same day, otherwise full date and time.
// Example: '3:45 pm' (same day) or '19th Oct 25 at 3:45 pm'

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
			? date_i18n( 'g:ia', $end_ts )                 // e.g. 3:45 pm
			: date_i18n( 'jS M y \a\t g:ia', $end_ts );   // e.g. 19th Oct 25 at 3:45 pm
	}
}


// Retrieve and format an event meta field for display.
// Handles special cases (dates, venues, online flag, map button, etc.).
// Returns a ready-to-render string or url for map location.
// Called by shortcodes and display templates.

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

				$venue_name = get_the_title( $venue_id );
				$island     = get_post_meta( $venue_id, 'ibt_venue_island', true );

				if ( $venue_name && $island ) {
					return sprintf( '%s, %s', $venue_name, $island );
				} elseif ( $venue_name ) {
					return $venue_name;
				} elseif ( $island ) {
					return $island;
				}
				return '';

			case 'ibt_event_venue_name':
				$venue_id = (int) get_post_meta( $post_id, 'ibt_event_venue_id', true );
				if ( ! $venue_id ) {
					return '';
				}
				$venue_name = get_the_title( $venue_id );
				return $venue_name ? esc_html( $venue_name ) : '';

			case 'ibt_event_venue_address':
				$venue_id = (int) get_post_meta( $post_id, 'ibt_event_venue_id', true );
				if ( ! $venue_id ) {
					return '';
				}

				$address = get_post_meta( $venue_id, 'ibt_venue_address', true );
				if ( empty( $address ) ) {
					return '';
				}

				// Convert newlines to <br> for display, escape each line and wrap in <p>
				// for cleaner formatting and consistent block spacing
				return '<p>' . nl2br( esc_html( $address ) ) . '</p>';


			case 'ibt_event_remote':
				$is_remote = (int) get_post_meta( $post_id, 'ibt_event_remote', true );
				return $is_remote ? '1' : '';

			// ----- Prices (formatted with £ symbol) -----
			case 'ibt_event_price_public':
			case 'ibt_event_price_member':
				$value = get_post_meta( $post_id, $key, true );
				if ( $value === '' ) {
					return '';
				}
				return '£' . number_format( (float) $value, 2 );

			// ----- Presenter -----
			case 'ibt_event_presenter':
				$presenter = get_post_meta( $post_id, 'ibt_event_presenter', true );
				return $presenter ? esc_html( $presenter ) : '';

			// ----- Online / remote flag (block-friendly inline) -----
			case 'ibt_event_online':
				$remote = get_post_meta( $post_id, 'ibt_event_remote', true );

				if ( $remote && ( $remote === '1' || $remote === 1 || $remote === true ) ) {
					return 'Online - Remote Accessible';
				} else {
					return ''; // explicit, not null or false
				}
			
			// ----- Event excerpt (short version for listings) -----
			case 'ibt_event_excerpt':
				$excerpt = get_the_excerpt( $post_id );
				if ( empty( $excerpt ) ) {
					return '';
				}

				// Trim to ~25 words, add ellipsis
				$short = wp_trim_words( $excerpt, 25, '…' );
				return esc_html( $short );


			case 'ibt_event_map_button':
				// Return a Google Maps search URL for the venue's stored map location.
				$venue_id = get_post_meta( $post_id, 'ibt_event_venue_id', true );
				if ( ! $venue_id ) {
					return '';
				}

				$maploc = get_post_meta( $venue_id, 'ibt_venue_maplocation', true );
				if ( empty( $maploc ) ) {
					return '';
				}

				return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $maploc );

    
			// Fallback – return raw meta value safely escaped for other keys.
			default:
				return esc_html( $value );
		}
	}
}
