<?php
/**
 * IBT Customisation — Register Taxonomy & Types
 * Purpose:
 *   Registers the shared "Topic" taxonomy and "Library" CPT.
 *
 *   RTT1 - Topic taxonomy
 *   RTT2 - Library CPT
 *   RTT3 - Hook registration
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ========================================================================
 * RTT1 - Register Taxonomy: topic (non-hierarchical)
 * Shared between Library (CPT) and Woo Products.
 * ===================================================================== */
add_action( 'init', ibt_safe( 'register-taxonomy-topic', function() {

	$labels = array(
		'name'                       => __( 'Topics', 'ibt' ),
		'singular_name'              => __( 'Topic', 'ibt' ),
		'search_items'               => __( 'Search Topics', 'ibt' ),
		'popular_items'              => __( 'Popular Topics', 'ibt' ),
		'all_items'                  => __( 'All Topics', 'ibt' ),
		'edit_item'                  => __( 'Edit Topic', 'ibt' ),
		'update_item'                => __( 'Update Topic', 'ibt' ),
		'add_new_item'               => __( 'Add New Topic', 'ibt' ),
		'new_item_name'              => __( 'New Topic Name', 'ibt' ),
		'separate_items_with_commas' => __( 'Separate topics with commas', 'ibt' ),
		'add_or_remove_items'        => __( 'Add or remove topics', 'ibt' ),
		'choose_from_most_used'      => __( 'Choose from the most used topics', 'ibt' ),
		'menu_name'                  => __( 'Topics', 'ibt' ),
	);

	$args = array(
		'labels'            => $labels,
		'public'            => true,
		'show_ui'           => true,
		'show_in_menu'      => true,
		'show_in_nav_menus' => true,
		'show_admin_column' => true,
		'show_in_rest'      => true,
		'hierarchical'      => false,
		'query_var'         => true,
		'rewrite'           => array( 'slug' => 'topic' ),
	);

	register_taxonomy( 'topic', array( 'library', 'product' ), $args );

}, 9 ) );

/* ========================================================================
 * RTT2 - Register CPT: library
 * Plural label "Library", singular "Library Article".
 * Note – can be made hierarchical later ('hierarchical' => true)
 *        with no structural changes.
 * ===================================================================== */
add_action( 'init', ibt_safe( 'register-cpt-library', function() {

	$labels = array(
		'name'               => __( 'Library', 'ibt' ),
		'singular_name'      => __( 'Library Article', 'ibt' ),
		'menu_name'          => __( 'Library', 'ibt' ),
		'name_admin_bar'     => __( 'Library Article', 'ibt' ),
		'add_new'            => __( 'Add New', 'ibt' ),
		'add_new_item'       => __( 'Add New Library Article', 'ibt' ),
		'new_item'           => __( 'New Library Article', 'ibt' ),
		'edit_item'          => __( 'Edit Library Article', 'ibt' ),
		'view_item'          => __( 'View Library Article', 'ibt' ),
		'all_items'          => __( 'All Library', 'ibt' ),
		'search_items'       => __( 'Search Library', 'ibt' ),
		'parent_item_colon'  => __( 'Parent Library:', 'ibt' ),
		'not_found'          => __( 'No Library items found.', 'ibt' ),
		'not_found_in_trash' => __( 'No Library items found in Trash.', 'ibt' ),
	);

	$args = array(
		'labels'             => $labels,
		'description'        => __( 'IBT archival and research content.', 'ibt' ),
		'public'             => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'show_in_nav_menus'  => true,
		'show_in_rest'       => true,
		'exclude_from_search'=> false,
		'publicly_queryable' => true,
		'has_archive'        => true,
		'rewrite'            => array(
			'slug'       => 'library',
			'with_front' => false,
		),
		'menu_position'      => 20,
		'supports'           => array(
			'title',
			'editor',
			'excerpt',
			'thumbnail',
			'revisions',
			'author',
		),
		'taxonomies'         => array( 'topic' ),
		'show_in_admin_bar'  => true,
		'capability_type'    => 'post',
	);

	register_post_type( 'library', $args );

}, 10 ) );

/* ========================================================================
 * RTT3 - (static) No runtime code here beyond action hooks.
 * This file is loaded unconditionally from the main plugin.
 * ===================================================================== */
