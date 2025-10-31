( function ( wp ) {
	const { registerBlockType } = wp.blocks;
	const { createElement: el } = wp.element;

	registerBlockType( 'ibt/events-archive', {
		edit: () => el( 'p', {}, 'IBT Events Archive (server-rendered preview)' ),
		save: () => null
	} );
} )( window.wp );
