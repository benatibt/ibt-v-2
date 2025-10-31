<?php
/**
 * Register the blue-box version of the IBT Events Archive block.
 * Pure PHP registration — no block.json or build step required.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', function() {
    register_block_type( 'ibt/events-archive-php', array(
        'render_callback' => function( $attributes, $content, $block ) {
            ob_start();
            include __DIR__ . '/render.php';
            return ob_get_clean();
        },
    ) );
});
