/**
 * IBT Theme – Gutenberg editor enhancements
 * ---------------------------------------------------------------------
 * Purpose:
 *   • Remove WP default "Fill" and "Outline" button styles. They don't want to go any other way!!!
 *   • Provide a safe home for future editor-only tweaks.
 */

( function( wp ) {
	if ( ! wp || ! wp.blocks ) return;

	wp.domReady( function() {
		wp.blocks.unregisterBlockStyle( 'core/button', 'fill' );
		wp.blocks.unregisterBlockStyle( 'core/button', 'outline' );
		console.log( 'IBT: removed default core/button styles' );
	} );

} )( window.wp );
