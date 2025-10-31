<?php
/**
 * IBT Events Archive Block
 *
 * Registers the dynamic server-rendered block that outputs
 * the IBT Events archive listing.
 *
 * @package IBT_Customisation
 */

defined( 'ABSPATH' ) || exit;

error_log( 'IBT: events-archive index.php included and init hook running.' );


// Register the block on init.
add_action( 'init', function() {
	$block_dir = __DIR__;

	// Safety: only register if block.json exists.
	if ( file_exists( $block_dir . '/block.json' ) ) {
		register_block_type_from_metadata( $block_dir );
	}
} );

