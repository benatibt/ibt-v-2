<?php
/**
 * Template Part: Events Loop
 * Displays upcoming events ordered by start date.
 * Toggle $show_past to include past events for testing.
 */

$show_past = false; // flip to true to include past events

$now = current_time( 'mysql' );

$args = array(
	'post_type'      => 'ibt_event',
	'posts_per_page' => 10,
	'meta_key'       => 'ibt_event_start',
	'orderby'        => 'meta_value',
	'order'          => 'ASC',
);

if ( ! $show_past ) {
	$args['meta_query'] = array(
		array(
			'key'     => 'ibt_event_start',
			'value'   => $now,
			'compare' => '>=',
			'type'    => 'DATETIME',
		),
	);
}

$query = new WP_Query( $args );

if ( $query->have_posts() ) : ?>
	<div class="ibt-events-loop">

		<?php while ( $query->have_posts() ) : $query->the_post(); ?>
			<article <?php post_class( 'ibt-event-item' ); ?>>
				<h2 class="ibt-event-title">
					<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
				</h2>

				<p><strong>Starts:</strong> [ibt_event_field key="ibt_event_start"]</p>
				<p><strong>Ends:</strong> [ibt_event_field key="ibt_event_end"]</p>
				<p><strong>Public price:</strong> £[ibt_event_field key="ibt_event_price_public"]</p>
				<p><strong>Member price:</strong> £[ibt_event_field key="ibt_event_price_member"]</p>
				<p><strong>Venue:</strong><br>[ibt_event_field key="ibt_event_venue"]</p>

				<!-- Map Button -->
				<!-- wp:buttons -->
				<div class="wp-block-buttons">
					[ibt_event_field key="ibt_event_map_button"]
				</div>
				<!-- /wp:buttons -->
			</article>
			<hr>
		<?php endwhile; ?>

	</div>
<?php else : ?>
	<p>No upcoming events found.</p>
<?php endif;

wp_reset_postdata();
