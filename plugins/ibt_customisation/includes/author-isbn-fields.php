<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Safe wrapper for Woo callbacks:
 * shows at most two unique error messages per page load,
 * plus one “additional errors suppressed” notice if needed.
 */
function ibt_safe( callable $fn ): callable {
    return function( ...$args ) use ( $fn ) {
        static $seen = array();
        static $cap  = 2;
        try {
            return $fn( ...$args );
        } catch ( \Throwable $e ) {
            $msg = $e->getMessage();
            if ( count( $seen ) < $cap && ! in_array( $msg, $seen, true ) ) {
                $seen[] = $msg;
                add_action( 'admin_notices', function() use ( $msg ) {
                    if ( current_user_can( 'edit_products' ) ) {
                        echo '<div class="notice notice-error"><p><strong>IBT Customisation error:</strong> '
                             . esc_html( $msg ) . '</p></div>';
                    }
                } );
            } elseif ( count( $seen ) === $cap ) {
                $seen[] = '__ibt_suppressed__';
                add_action( 'admin_notices', function() {
                    if ( current_user_can( 'edit_products' ) ) {
                        echo '<div class="notice notice-warning"><p><strong>'
                             . esc_html__( 'Additional IBT Customisation errors were suppressed. Check site debugging for details.', 'ibt' )
                             . '</strong></p></div>';
                    }
                } );
            }
            return null;
        }
    };
}

/** Helpers */
function ibt_get_books_term() {
    static $term = null;
    if ( $term === null ) {
        $term = get_term_by( 'slug', IBT_BOOKS_CATEGORY_SLUG, 'product_cat' );
    }
    return $term instanceof WP_Term ? $term : null;
}

function ibt_get_books_and_descendant_ids() {
    $root = ibt_get_books_term();
    if ( ! $root ) return array();
    $ids = array( (int) $root->term_id );
    $desc = get_terms( array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
        'child_of'   => (int) $root->term_id,
        'fields'     => 'ids',
    ) );
    if ( is_array( $desc ) ) {
        $ids = array_map( 'intval', array_unique( array_merge( $ids, $desc ) ) );
    }
    return $ids;
}

/** ADMIN: Add custom fields to Product Data → General */
add_action( 'woocommerce_product_options_general_product_data', ibt_safe( function() {
    if ( ! current_user_can( 'edit_products' ) ) return;

    echo '<div class="options_group ibt-book-only-fields" style="display:none">';

    // Nonce for CSRF protection
    wp_nonce_field( 'ibt_save_product_meta', 'ibt_product_meta_nonce' );

    // Author
    woocommerce_wp_text_input( array(
        'id'          => '_ibt_subtitle',
        'label'       => 'Author',
        'placeholder' => 'e.g. John MacLeod',
        'desc_tip'    => true,
        'description' => 'Appears in product listings and on the single product page.',
    ) );

    // ISBN (no format validation)
    woocommerce_wp_text_input( array(
        'id'          => '_ibt_isbn',
        'label'       => 'ISBN',
        'placeholder' => '978…',
        'desc_tip'    => true,
        'description' => 'Optional. Shown in Additional information.',
        'type'        => 'text',
    ) );

    echo '</div>';
} ) );

/** ADMIN: Save fields */
add_action( 'woocommerce_process_product_meta', ibt_safe( function( $post_id ) {
    if ( ! current_user_can( 'edit_products' ) ) return;
    if (
        ! isset( $_POST['ibt_product_meta_nonce'] )
        || ! wp_verify_nonce( wp_unslash( $_POST['ibt_product_meta_nonce'] ), 'ibt_save_product_meta' )
    ) {
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
} ) );

/** ADMIN: Toggle field visibility when category changes */
add_action( 'admin_enqueue_scripts', ibt_safe( function() {
    if ( ! current_user_can( 'edit_products' ) ) return;

    $screen = get_current_screen();
    if ( ! $screen || $screen->post_type !== 'product' ) return;

    $books_ids = ibt_get_books_and_descendant_ids();
    if ( empty( $books_ids ) ) return;

    wp_register_script( 'ibt-admin-books-toggle', '', array( 'jquery' ), '1.0.1', true );
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

/** FRONT: Core rendering function used by shortcode and block */
function ibt_render_author() {
    try {
        global $product;
        if ( ! ( $product instanceof WC_Product ) ) return '';
        $books_ids = ibt_get_books_and_descendant_ids();
        if ( empty( $books_ids ) ) return '';
        $terms = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'ids' ) );
        if ( is_wp_error( $terms ) || ! array_intersect( $books_ids, $terms ) ) return '';

        $subtitle = get_post_meta( $product->get_id(), '_ibt_subtitle', true );
        if ( ! $subtitle ) return '';
        return '<p class="ibt-author-parastyle">Author: ' . esc_html( $subtitle ) . '</p>';
    } catch ( \Throwable $e ) {
        // Error will be caught by ibt_safe in outer hooks if needed.
        return '';
    }
}

/** FRONT: Shortcode */
add_shortcode( 'ibt_author', 'ibt_render_author' );

/** FRONT: Classic loop (shop/category/search/related) */
add_action( 'woocommerce_after_shop_loop_item_title', ibt_safe( function() {
    global $product;
    if ( ! ( $product instanceof WC_Product ) ) return;
    echo ibt_render_author();
} ), 6 );

/** FRONT: ISBN in Additional Information table (top row) */
add_filter( 'woocommerce_display_product_attributes', ibt_safe( function( $attrs, $product ) {
    $isbn = get_post_meta( $product->get_id(), '_ibt_isbn', true );
    if ( $isbn === '' ) return $attrs;

    $books_ids = ibt_get_books_and_descendant_ids();
    if ( empty( $books_ids ) ) return $attrs;
    $terms = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'ids' ) );
    if ( is_wp_error( $terms ) || ! array_intersect( $books_ids, $terms ) ) return $attrs;

    $row = array( 'label' => 'ISBN', 'value' => esc_html( $isbn ) );

    if ( is_array( $attrs ) && array_keys( $attrs ) !== range(0, count($attrs) - 1) ) {
        $attrs = array( 'ibt_isbn' => $row ) + $attrs;
    } else {
        array_unshift( $attrs, $row );
    }
    return $attrs;
} ), 10, 2 );

/** FRONT: Minimal CSS */
add_action( 'wp_enqueue_scripts', ibt_safe( function() {
    if ( ! ( is_product() || is_shop() || is_product_category() ) ) return;
    $css = '
    .ibt-author-parastyle {
        margin: 0 0 0.5rem 0;
        font: inherit;
        font-weight: 400;
        color: var(--wp--preset--color--text,#000);
        font-family: var(--wp--preset--font-family--body-font,inherit);
        font-size: var(--wp--preset--font-size--m,1rem);
    }';
    wp_register_style( 'ibt-front-inline', false, array(), '1.4.1' );
    wp_enqueue_style( 'ibt-front-inline' );
    wp_add_inline_style( 'ibt-front-inline', $css );
} ) );

/** BLOCK: Register "Book Author" dynamic block */
add_action( 'init', ibt_safe( function() {
    register_block_type( 'ibt/book-author', array(
        'api_version'     => 2,
        'title'           => 'Book Author',
        'description'     => 'Displays the Author field for Books products.',
        'category'        => 'widgets',
        'icon'            => 'id',
        'render_callback' => 'ibt_render_author',
        'supports'        => array( 'html' => false ),
    ) );
} ) );
