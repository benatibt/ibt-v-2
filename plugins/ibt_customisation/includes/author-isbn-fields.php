<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Helpers
 */
function ibt_get_books_term() {
	static $term = null;
	if ( $term === null ) {
		$term = get_term_by( 'slug', IBT_BOOKS_CATEGORY_SLUG, 'product_cat' );
	}
	return ( $term instanceof WP_Term ) ? $term : null;
}

function ibt_get_books_and_descendant_ids() {
	static $cache = null;
	if ( $cache !== null ) {
		return $cache;
	}

	$root = ibt_get_books_term();
	if ( ! $root ) return $cache = array();

	$ids  = array( (int) $root->term_id );
	$desc = get_terms( array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
		'child_of'   => (int) $root->term_id,
		'fields'     => 'ids',
	) );

	if ( is_array( $desc ) ) {
		$ids = array_map( 'intval', array_unique( array_merge( $ids, $desc ) ) );
	}

	return $cache = $ids;
}

/**
 * ADMIN: Add custom fields to Product Data → General (guarded)
 */
add_action( 'woocommerce_product_options_general_product_data', ibt_safe('AIF1-admin-add-fields', function() {
	if ( ! current_user_can( 'edit_products' ) ) return;

	echo '<div class="options_group ibt-book-only-fields" style="display:none">';

	woocommerce_wp_text_input( array(
		'id'          => '_ibt_subtitle',
		'label'       => __( 'Author', 'ibt' ),
		'placeholder' => __( 'e.g. John MacLeod', 'ibt' ),
		'desc_tip'    => true,
		'description' => __( 'Appears in product listings and on the single product page.', 'ibt' ),
	) );

	woocommerce_wp_text_input( array(
		'id'          => '_ibt_isbn',
		'label'       => __( 'ISBN', 'ibt' ),
		'placeholder' => __( '978-…', 'ibt' ),
		'desc_tip'    => true,
		'description' => __( 'Optional. Shown in Additional information.', 'ibt' ),
		'type'        => 'text',
	) );

	woocommerce_wp_text_input( array(
		'id'          => '_ibt_pages',
		'label'       => __( 'Pages', 'ibt' ),
		'placeholder' => __( 'e.g. 224', 'ibt' ),
		'desc_tip'    => true,
		'description' => __( 'Optional. Total number of pages.', 'ibt' ),
		'type'        => 'number',
		'custom_attributes' => array(
			'min' => '1',
			'step' => '1',
		),
	) );

	woocommerce_wp_text_input( array(
		'id'          => '_ibt_first_published',
		'label'       => __( 'First Published', 'ibt' ),
		'placeholder' => __( 'e.g. 1962', 'ibt' ),
		'desc_tip'    => true,
		'description' => __( 'Optional. Four-digit year.', 'ibt' ),
		'type'        => 'number',
		'custom_attributes' => array(
			'min'  => '1',
			'step' => '1',
		),
	) );

	echo '</div>';
} ) );

/**
 * ADMIN: Save fields (guarded)
 */
add_action( 'woocommerce_process_product_meta', ibt_safe('AIF2-admin-save-fields', function( $post_id ) {
	if ( ! current_user_can( 'edit_products' ) ) return;

	if ( isset( $_POST['_ibt_subtitle'] ) ) {
		update_post_meta(
			$post_id,
			'_ibt_subtitle',
			sanitize_text_field( wp_unslash( $_POST['_ibt_subtitle'] ) )
		);
	}

	if ( isset( $_POST['_ibt_isbn'] ) ) {
		update_post_meta(
			$post_id,
			'_ibt_isbn',
			sanitize_text_field( wp_unslash( $_POST['_ibt_isbn'] ) )
		);
	}

	if ( isset( $_POST['_ibt_pages'] ) ) {
		$pages = absint( $_POST['_ibt_pages'] );
		update_post_meta( $post_id, '_ibt_pages', $pages );
	}

	if ( isset( $_POST['_ibt_first_published'] ) ) {
		$year_raw = sanitize_text_field( wp_unslash( $_POST['_ibt_first_published'] ) );
		$year     = preg_match( '/^\d{4}$/', $year_raw ) ? $year_raw : '';
		update_post_meta( $post_id, '_ibt_first_published', $year );
	}



} ) );

/**
 * ADMIN: Toggle field visibility when category changes
 */
add_action( 'admin_enqueue_scripts', ibt_safe('AIF3-admin-books-toggle', function( $hook_suffix = '' ) {
	if ( ! current_user_can( 'edit_products' ) ) return;

	$screen = get_current_screen();
	if ( ! $screen || $screen->post_type !== 'product' ) return;

	$books_ids = ibt_get_books_and_descendant_ids();
	if ( empty( $books_ids ) ) return;

	wp_register_script( 'ibt-admin-books-toggle', '', array( 'jquery' ), '1.0.3', true );
	$inline = <<<JS
	(function($){
		function ids(){return new Set(JSON.parse($('#ibtBooksIds').data('ids')||'[]'));}
		function isBook(){
			var set=ids(),found=false;
			$('#product_catchecklist input:checked, [data-taxonomy="product_cat"] input:checked').each(function(){
				if(set.has(parseInt(this.value,10))){found=true;return false;}
			});
			return found;
		}
		function toggle(){ isBook()?$('.ibt-book-only-fields').slideDown(150):$('.ibt-book-only-fields').slideUp(150); }
		$(document).on('change','#product_catchecklist input, [data-taxonomy="product_cat"] input',toggle);
		$(function(){ $('body').append('<div id="ibtBooksIds" data-ids="[]"></div>')
			.find('#ibtBooksIds').data('ids',JSON.stringify(IBT_BOOKS_IDS||[]));
			toggle();
		});
	})(jQuery);
	JS;
	wp_enqueue_script( 'ibt-admin-books-toggle' );
	wp_add_inline_script( 'ibt-admin-books-toggle', 'var IBT_BOOKS_IDS = ' . wp_json_encode( $books_ids ) . ';', 'before' );
	wp_add_inline_script( 'ibt-admin-books-toggle', $inline, 'after' );
} ) );

/**
 * FRONT: Core rendering function (used by shortcode and hooks)
 * Outputs Author field; shortcode uses <h2>, loop hook overrides to <h3>.
 */
function ibt_render_author( $atts = array(), $content = null ) {
	global $product;
	if ( ! ( $product instanceof WC_Product ) ) return '';

	// Determine heading level (default h2)
	$atts = shortcode_atts(
		array( 'level' => 'h2' ),
		$atts,
		'ibt_author'
	);

	$level = in_array( strtolower( $atts['level'] ), array( 'h2', 'h3', 'p' ), true )
		? strtolower( $atts['level'] )
		: 'h2';

	$books_ids = ibt_get_books_and_descendant_ids();
	if ( empty( $books_ids ) ) return '';

	$terms = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'ids' ) );
	if ( is_wp_error( $terms ) || ! array_intersect( $books_ids, $terms ) ) return '';

	$subtitle = get_post_meta( $product->get_id(), '_ibt_subtitle', true );
	if ( ! $subtitle ) return '';

	return sprintf(
		'<%1$s class="ibt-author-parastyle">Author: %2$s</%1$s>',
		esc_html( $level ),
		esc_html( $subtitle )
	);
}


/**
 * FRONT: Show author in product loops (shop, category, related, etc.)
 * Shortcode has issues resolving product so old school approach.
 */
add_action( 'woocommerce_after_shop_loop_item_title', function() {
	global $product;
	if ( ! ( $product instanceof WC_Product ) ) {
		return;
	}

	$author_html = ibt_render_author( array( 'level' => 'h3' ) );
	if ( $author_html ) {
		echo $author_html;
	}
}, 6 );


/**
 * FRONT: Book meta (ISBN, Pages, First Published) in Additional Information table
 */
add_filter( 'woocommerce_display_product_attributes', ibt_safe( 'AIF4-front-bookmeta-filter', function( $attrs, $product = null ) {

	// Woo sometimes omits $product; recover it safely.
	if ( ! ( $product instanceof WC_Product ) ) {
		global $product;
		$product = $product instanceof WC_Product ? $product : wc_get_product( get_the_ID() );
	}
	if ( ! ( $product instanceof WC_Product ) ) {
		return $attrs;
	}

	// Only show for books or descendant categories
	$books_ids = ibt_get_books_and_descendant_ids();
	if ( empty( $books_ids ) ) return $attrs;

	$terms = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'ids' ) );
	if ( is_wp_error( $terms ) || ! array_intersect( $books_ids, $terms ) ) return $attrs;

	// --- ISBN (optional) ---
	$isbn = get_post_meta( $product->get_id(), '_ibt_isbn', true );
	if ( $isbn !== '' ) {
		$row = array( 'label' => __( 'ISBN', 'ibt' ), 'value' => esc_html( $isbn ) );
		if ( is_array( $attrs ) && array_keys( $attrs ) !== range( 0, count( $attrs ) - 1 ) ) {
			$attrs = array( 'ibt_isbn' => $row ) + $attrs;
		} else {
			array_unshift( $attrs, $row );
		}
	}

	// --- Pages ---
	$pages = get_post_meta( $product->get_id(), '_ibt_pages', true );
	if ( $pages ) {
		$attrs[] = array(
			'label' => __( 'Pages', 'ibt' ),
			'value' => esc_html( $pages ),
		);
	}

	// --- First Published ---
	$year = get_post_meta( $product->get_id(), '_ibt_first_published', true );
	if ( $year ) {
		$attrs[] = array(
			'label' => __( 'First published', 'ibt' ),
			'value' => esc_html( $year ),
		);
	}

	return $attrs;

}), 10, 2 );




/**
 * SHORTCODE: [ibt_author]
 */
add_shortcode( 'ibt_author', 'ibt_render_author' );
