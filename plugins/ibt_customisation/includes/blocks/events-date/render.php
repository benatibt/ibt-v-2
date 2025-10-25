<?php
/**
 * Server-side render callback for ibt/event-date
 */
$post_id = get_the_ID();
$start   = get_post_meta( $post_id, 'ibt_event_start', true );
$end     = get_post_meta( $post_id, 'ibt_event_end', true );

if ( empty( $start ) ) {
	return;
}

$start_fmt = date_i18n( 'j M Y, H:i', strtotime( $start ) );
$end_fmt   = $end ? date_i18n( 'j M Y, H:i', strtotime( $end ) ) : '';

echo '<p class="ibt-event-date">';
echo '<strong>' . esc_html__( 'Starts:', 'ibt-events' ) . '</strong> ' . esc_html( $start_fmt );
if ( ! empty( $end_fmt ) ) {
	echo '<br><strong>' . esc_html__( 'Ends:', 'ibt-events' ) . '</strong> ' . esc_html( $end_fmt );
}
echo '</p>';
