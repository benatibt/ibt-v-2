<?php
defined( 'ABSPATH' ) || exit;

/**
 * Render callback for IBT Events Archive block
 * Behaviour:
 *  • Default – upcoming events only (soonest first)
 *  • ?past=1 – include past events (latest first)
 *  • Paginated (10 per page)
 */

// Determine mode
$show_past = isset( $_GET['past'] ) && $_GET['past'] == 1;
$now       = ( new DateTime( 'now', new DateTimeZone( 'Europe/London' ) ) )->format( 'Y-m-d H:i' );
$paged     = max( 1, get_query_var( 'paged' ) );

// Build query
$args = [
	'post_type'      => 'ibt_event',
	'post_status'    => 'publish',
	'posts_per_page' => 10,
	'paged'          => $paged,
	'meta_key'       => 'ibt_event_start',
	'orderby'        => 'meta_value',
];

if ( $show_past ) {
	$args['order'] = 'DESC';
} else {
	$args['order'] = 'ASC';
	$args['meta_query'] = [
		[
			'key'     => 'ibt_event_end',
			'value'   => $now,
			'compare' => '>=',
			'type'    => 'DATETIME',
		],
	];
}

$q = new WP_Query( $args );

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

	// Pagination
	echo '<div class="ibt-pagination">';
	the_posts_pagination( [
		'mid_size'  => 2,
		'prev_text' => __( '← Previous', 'ibt' ),
		'next_text' => __( 'Next →', 'ibt' ),
	] );
	echo '</div>';

	wp_reset_postdata();
} else {
	echo '<p>' . esc_html__( 'No events found.', 'ibt' ) . '</p>';
}

echo '</div>'; // .ibt-events-archive
return '';
