<?php
/**
 * Archive template for Events (ibt_event)
 * 
 * Based on page.html layout for consistent design.
 * Default: future events only (soonest first)
 * ?past=1 → all events (latest first)
 */

get_header();

// --- Determine mode ---
$show_past = isset( $_GET['past'] ) && $_GET['past'] == 1;

// Fixed UK time
$now = ( new DateTime( 'now', new DateTimeZone( 'Europe/London' ) ) )->format( 'Y-m-d H:i' );

// Pagination
$paged = max( 1, get_query_var( 'paged' ) );

// Build query
$args = array(
	'post_type'      => 'ibt_event',
	'post_status'    => 'publish',
	'posts_per_page' => 10,
	'paged'          => $paged,
	'meta_key'       => 'ibt_event_start',
	'orderby'        => 'meta_value',
);

if ( $show_past ) {
	$args['order'] = 'DESC'; // all events, latest first
} else {
	$args['order'] = 'ASC'; // future events, soonest first
	$args['meta_query'] = array(
		array(
			'key'     => 'ibt_event_end',
			'value'   => $now,
			'compare' => '>=',
			'type'    => 'DATETIME',
		),
	);
}

$query = new WP_Query( $args );
?>

<!-- wp:template-part {"slug":"header","theme":"ibt"} /-->

<!-- wp:group {"tagName":"main","className":"ibt-rootp-apply","layout":{"type":"constrained"}} -->
<main class="wp-block-group ibt-rootp-apply">
	<!-- wp:template-part {"slug":"meta-schema","theme":"ibt"} /-->

	<!-- wp:group {"align":"wide","layout":{"type":"default"}} -->
	<div class="wp-block-group alignwide">

		<h1 class="wp-block-heading ibt-archive-title"><?php post_type_archive_title(); ?></h1>

		<p class="ibt-event-toggle">
			<?php if ( $show_past ) : ?>
				<a class="wp-block-button__link ibt-button-small" href="<?php echo esc_url( get_post_type_archive_link( 'ibt_event' ) ); ?>">Show upcoming events</a>
			<?php else : ?>
				<a class="wp-block-button__link ibt-button-small" href="<?php echo esc_url( add_query_arg( 'past', 1, get_post_type_archive_link( 'ibt_event' ) ) ); ?>">Show past events</a>
			<?php endif; ?>
		</p>

		<?php if ( $query->have_posts() ) : ?>
			<div class="ibt-event-list">
				<?php while ( $query->have_posts() ) : $query->the_post(); ?>
					<article <?php post_class( 'ibt-event-list-item' ); ?>>
						<h3 class="ibt-event-title">
							<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						</h3>

						<?php the_excerpt(); ?>

						<p><strong>Presenter:</strong> <?php echo do_shortcode( '[ibt_event_field key="ibt_event_presenter"]' ); ?></p>
						<p><strong>Starts:</strong> <?php echo do_shortcode( '[ibt_event_field key="ibt_event_start"]' ); ?></p>
						<p><strong>Venue:</strong> <?php echo do_shortcode( '[ibt_event_field key="ibt_event_venue"]' ); ?></p>
						<?php echo do_shortcode( '[ibt_event_field key="ibt_event_online"]' ); ?>
					</article>
				<?php endwhile; ?>
			</div>

			<div class="ibt-pagination">
				<?php the_posts_pagination( array(
					'mid_size'  => 2,
					'prev_text' => __( '← Previous', 'ibt' ),
					'next_text' => __( 'Next →', 'ibt' ),
				) ); ?>
			</div>
		<?php else : ?>
			<p>No events found.</p>
		<?php endif; ?>

		<?php wp_reset_postdata(); ?>

		<!-- wp:spacer {"height":"100px"} -->
		<div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
		<!-- /wp:spacer -->
	</div>
	<!-- /wp:group -->
</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer","theme":"ibt"} /-->
