<?php
defined( 'ABSPATH' ) || exit;

/**
 * Render callback for IBT Events Archive block
 *
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

// Dynamic heading
$title_text = $show_past ? __( 'All Events', 'ibt' ) : __( 'Upcoming Events', 'ibt' );
echo '<h1 class="wp-block-heading ibt-archive-title">' . esc_html( $title_text ) . '</h1>';

// Past/future toggle – as a real core Button block
if ( $show_past ) {
	echo do_blocks( '
		<!-- wp:buttons -->
		<div class="wp-block-buttons ibt-event-toggle">
			<!-- wp:button {"className":"ibt-event-toggle-btn"} -->
			<div class="wp-block-button ibt-event-toggle-btn">
				<a class="wp-block-button__link" href="' . esc_url( get_post_type_archive_link( 'ibt_event' ) ) . '">
					' . esc_html__( 'Show upcoming events', 'ibt' ) . '
				</a>
			</div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	' );
} else {
	echo do_blocks( '
		<!-- wp:buttons -->
		<div class="wp-block-buttons ibt-event-toggle">
			<!-- wp:button {"className":"ibt-event-toggle-btn"} -->
			<div class="wp-block-button ibt-event-toggle-btn">
				<a class="wp-block-button__link" href="' . esc_url( add_query_arg( 'past', 1, get_post_type_archive_link( 'ibt_event' ) ) ) . '">
					' . esc_html__( 'Show past events', 'ibt' ) . '
				</a>
			</div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	' );
}

// Event list
if ( $q->have_posts() ) {
	echo '<div class="ibt-event-list">';
	while ( $q->have_posts() ) {
		$q->the_post();
		echo '<article class="ibt-event-list-item">';
		echo '<h2 class="ibt-event-title"><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h2>';
		the_excerpt();

		$presenter = do_shortcode( '[ibt_event_field key="ibt_event_presenter"]' );
		$start     = do_shortcode( '[ibt_event_field key="ibt_event_start"]' );
		$venue     = do_shortcode( '[ibt_event_field key="ibt_event_venue"]' );
		$online    = do_shortcode( '[ibt_event_field key="ibt_event_online"]' );

		if ( $presenter ) echo '<p><strong>Presenter:</strong> ' . wp_kses_post( $presenter ) . '</p>';
		if ( $start )     echo '<p><strong>Starts:</strong> ' . wp_kses_post( $start ) . '</p>';
		if ( $venue )     echo '<p><strong>Venue:</strong> ' . wp_kses_post( $venue ) . '</p>';
		if ( $online )    echo wp_kses_post( $online );

		echo '</article>';
	}
	echo '</div>';

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
