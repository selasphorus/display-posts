<?php

defined( 'ABSPATH' ) or die( 'Nope!' );

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}


/*** CONTENT COLLECTIONS ***/

// Content Collections -- or: Group(ing)s? Galleries? Sets? Arrays? Assortments? Selections?
function register_post_type_collection() {

	$labels = array(
		'name' => __( 'Content Collections', 'sdg' ),
		'singular_name' => __( 'Collection', 'sdg' ),
		'add_new' => __( 'New Collection', 'sdg' ),
		'add_new_item' => __( 'Add New Collection', 'sdg' ),
		'edit_item' => __( 'Edit Collection', 'sdg' ),
		'new_item' => __( 'New Collection', 'sdg' ),
		'view_item' => __( 'View Collections', 'sdg' ),
		'search_items' => __( 'Search Collections', 'sdg' ),
		'not_found' =>  __( 'No Content Collections Found', 'sdg' ),
		'not_found_in_trash' => __( 'No Content Collections found in Trash', 'sdg' ),
	);

	$args = array(
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'collection' ),
		//'capability_type' => array('collection', 'collections'),
		'map_meta_cap'       => true,
		'has_archive'        => true,
		'hierarchical'       => false,
		//'menu_icon'          => 'dashicons-welcome-write-blog',
		'menu_position'      => null,
		'supports'           => array( 'title', 'author', 'thumbnail', 'custom-fields', 'revisions', 'page-attributes' ), //, 'editor', 'excerpt'
		'taxonomies' => array( 'admin_tag' ), //'people_category', 'people_tag', 
		'show_in_rest' => false, // i.e. false = use classic, not block editor
	);

	register_post_type( 'collection', $args );

}
add_action( 'init', 'register_post_type_collection' );


?>