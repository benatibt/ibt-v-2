<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ---------------------------------------------------------------------
 * Basic error-handling wrapper.
 * Catches any Throwable, logs message, continues execution.
 * ---------------------------------------------------------------------
 */
if ( ! function_exists( 'ibt_safe' ) ) {
	function ibt_safe( callable $fn ): callable {
		return function ( ...$args ) use ( $fn ) {
			try {
				return $fn( ...$args );
			} catch ( \Throwable $e ) {
				error_log( '[IBT Customisation] Handler error: ' . $e->getMessage() );
				return null;
			}
		};
	}
}

/**
 * ---------------------------------------------------------------------
 * Helpers
 * ---------------------------------------------------------------------
 */
function ibt_get_books_term() {
	static $term = null;
	if ( $term === null ) {
		$term = get_term_by( 'slug', IBT_BOOKS_CATEGORY_SLUG, 'product_cat' );
	}
	return ( $term instanceof WP_Term ) ? $term : null;
}

function ibt_get_books_and_descendant_ids() {
	$root = ibt_get_books_term();
	if ( ! $root ) {
		return array();
	}
	$ids  = array( (int) $root->term_id );
	$desc = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'child_of'   => (int) $root->term_id,
			'fields'     => 'ids',
		)
	);
	if ( is_array( $desc ) ) {
		$ids = array_map( 'intval', array_unique( array_merge( $ids, $desc ) ) );
	}
	return $ids;
}

/**
 * ---------------------------------------------------------------------
 * ADMIN: Add custom fields to Product Data → General
 * (guarded by capability)
 * ---------------------------------------------------------------------
 */
add_action(
	'woocommerce_product_options_general_product_data',
	ibt_safe(
		function() {

			if ( ! current_user_can( 'edit_products' ) ) {
				return;
			}

			echo '<div class="options_group ibt-book-only-fields" style="display:none">';

			// Author
			woocommerce_wp_text_input(
				array(
					'id'          => '_ibt_subtitle',
					'label'       => __( 'Author', 'ibt' ),
					'placeholder' => __( 'e.g. John MacLeod', 'ibt' ),
					'desc_tip'    => true,
					'description' => __( 'Appears in product listings and on the single product page.', 'ibt' ),
				)
			);

			// ISBN (free text as per spec)
			woocommerce_wp_text_input(
				array(
					'id'          => '_ibt_isbn',
					'label'       => __( 'ISBN', 'ibt' ),
					'placeholder' => __( '978-…', 'ibt' ),
					'desc_tip'    => true,
					'description' => __( 'Optional. Shown in Additional information.', 'ibt' ),
					'type'        => 'text',
				)
			);

			echo '</div>';
		}
	)
);

/**
 * ---------------------------------------------------------------------
 * ADMIN: Save fields (guarded by capability)
 * (free-text ISBN; sanitise only)
 * ---------------------------------------------------------------------
 */
add_action(
	'woocommerce_process_product_meta',
	ibt_safe(
		function( $post_id ) {

			if ( ! current_user_can( 'edit_products' ) ) {
				return;
			}

			if ( isset( $_POST['_ibt_subtitle'] ) ) {
				update_post_meta(
					$post_id,
					'_ibt_subtitle',
					sanitize_text_field( wp_unslash( $_POST['_ibt_subtitle'] ) )
				);
			}

			if ( isset( $_POST['_ibt_isbn'] ) ) {
				$isbn_raw = sanitize_text_field( wp_unslash( $_POST['_ibt_isbn'] ) );
				update_post_meta( $post_id, '_ibt_isbn', $isbn_raw );
			}
		}
	)
);

/**
 * ---------------------------------------------------------------------
 * ADMIN: Toggle field visibility when category changes
 * ---------------------------------------------------------------------
 */
add_action(
	'admin_enqueue_scripts',
	ibt_safe(
		function() {
			$screen = get_current_screen();
			if ( ! $screen || $screen->post_type !== 'product' ) {
				return;
			}
			$books_ids = ibt_get_books_and_descendant_ids();
			if ( empty( $books_ids ) ) {
				return;
			}

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
		}
	)
);

/**
 * ---------------------------------------------------------------------
 * FRONT: Core rendering function (used by block + shortcode)
 * (No PHP injection hooks; display is block-only now)
 * ---------------------------------------------------------------------
 */
function ibt_render_author() {
	global $product;
	if ( ! ( $product instanceof WC_Product ) ) {
		return '';
	}

	$books_ids = ibt_get_books_and_descendant_ids();
	if ( empty( $books_ids ) ) {
		return '';
	}

	$terms = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'ids' ) );
	if ( is_wp_error( $terms ) || ! array_intersect( $books_ids, $terms ) ) {
		return '';
	}

	$subtitle = get_post_meta( $product->get_id(), '_ibt_subtitle', true );
	if ( ! $subtitle ) {
		return '';
	}

	return '<p class="ibt-author-parastyle">Author: ' . esc_html( $subtitle ) . '</p>';
}

/**
 * ---------------------------------------------------------------------
 * FRONT: ISBN in Additional Information table (keep as PHP filter)
 * ---------------------------------------------------------------------
 */
add_filter(
	'woocommerce_display_product_attributes',
	ibt_safe(
		function( $attrs, $product ) {
			$isbn = get_post_meta( $product->get_id(), '_ibt_isbn', true );
			if ( $isbn === '' ) {
				return $attrs;
			}
			$books_ids = ibt_get_books_and_descendant_ids();
			if ( empty( $books_ids ) ) {
				return $attrs;
			}
			$terms = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'ids' ) );
			if ( is_wp_error( $terms ) || ! array_intersect( $books_ids, $terms ) ) {
				return $attrs;
			}

			$row = array( 'label' => __( 'ISBN', 'ibt' ), 'value' => esc_html( $isbn ) );
			if ( is_array( $attrs ) && array_keys( $attrs ) !== range( 0, count( $attrs ) - 1 ) ) {
				$attrs = array( 'ibt_isbn' => $row ) + $attrs;
			} else {
				array_unshift( $attrs, $row );
			}
			return $attrs;
		}
	),
	10,
	2
);

/**
 * ---------------------------------------------------------------------
 * BLOCK: Register "Book Author" dynamic block via block.json
 * ---------------------------------------------------------------------
 */
add_action(
	'init',
	ibt_safe(
		function() {
			// Use block.json for modern editor visibility.
			$block_dir = dirname( __DIR__ ) . '/blocks/book-author';
			if ( file_exists( $block_dir . '/block.json' ) ) {
				register_block_type( $block_dir );
			} else {
				// Fallback: direct registration if block.json missing (won't show in inserter)
				register_block_type(
					'ibt/book-author',
					array(
						'api_version'     => 2,
						'title'           => __( 'Book Author', 'ibt' ),
						'description'     => __( 'Displays the Author field for Books products.', 'ibt' ),
						'category'        => 'woocommerce',
						'icon'            => 'id',
						'render_callback' => 'ibt_render_author',
						'supports'        => array( 'html' => false ),
					)
				);
			}
		}
	)
);

/**
 * ---------------------------------------------------------------------
 * SHORTCODE (optional, for legacy use)
 * ---------------------------------------------------------------------
 */
add_shortcode( 'ibt_author', 'ibt_render_author' );
