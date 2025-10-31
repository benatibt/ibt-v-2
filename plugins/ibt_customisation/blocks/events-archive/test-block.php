<?php
/**
 * Minimal test block (no metadata)
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', function() {
    register_block_type( 'ibt/test-block', array(
        'render_callback' => function() {
            error_log('IBT: bare-bones block render running');
            return '<div style="border:3px solid blue;padding:1em;">Bare-bones dynamic block works.</div>';
        },
    ));
});
