<?php
/**
 * Register the IBT Post Type Label.
 * Pure PHP registration — no block.json or build step required.
 */

add_action( 'init', function() {
    register_block_type(
        'ibt/post-type-label',
        array(
            'render_callback' => function( $attributes, $content, $block ) {
                ob_start();
                include __DIR__ . '/render.php';
                return ob_get_clean();
            },
        )
    );
});
