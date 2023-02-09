<?php

defined( 'ABSPATH' ) or die( 'Nope!' );

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

// Get plugin options to determine which modules are active
$options = get_option( 'sdg_settings' );
if ( isset($options['sdg_modules']) ) { $sdg_modules = $options['sdg_modules']; } else { $sdg_modules = array(); }

// TODO: review and revise capabilities to make sure they'll be compatible for sites with and without sophisticated permissions management (e.g. Members plugin)


/*** GENERAL/ADMIN POST TYPES ***/

if ( in_array('admin_notes', $sdg_modules ) ) {

	// Admin Note
	function register_post_type_admin_note() {

		$labels = array(
			'name' => __( 'Admin Notes', 'sdg' ),
			'singular_name' => __( 'Admin Note', 'sdg' ),
			'add_new' => __( 'New Admin Note', 'sdg' ),
			'add_new_item' => __( 'Add New Admin Note', 'sdg' ),
			'edit_item' => __( 'Edit Admin Note', 'sdg' ),
			'new_item' => __( 'New Admin Note', 'sdg' ),
			'view_item' => __( 'View Admin Notes', 'sdg' ),
			'search_items' => __( 'Search Admin Notes', 'sdg' ),
			'not_found' =>  __( 'No Admin Notes Found', 'sdg' ),
			'not_found_in_trash' => __( 'No Admin Notes found in Trash', 'sdg' ),
		);
	
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'admin_note' ),
			'capability_type' => array('admin_note', 'admin_notes'),
			'map_meta_cap'       => true,
			'has_archive'        => true,
			'hierarchical'       => false,
			//'menu_icon'          => 'dashicons-groups',
			'menu_position'      => null,
			'supports'           => array( 'title', 'author', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ), // 
			'taxonomies' => array( 'adminnote_category', 'admin_tag', 'data_table', 'query_tag', 'admin_tag' ),
			'show_in_rest' => true,    
		);

		register_post_type( 'admin_note', $args );
	
	}
	add_action( 'init', 'register_post_type_admin_note' );
}

// DB Query -- deprecated -- merged with Admin Notes
function register_post_type_db_query() {

	$labels = array(
		'name' => __( 'DB Queries', 'sdg' ),
		'singular_name' => __( 'DB Query', 'sdg' ),
		'add_new' => __( 'New DB Query', 'sdg' ),
		'add_new_item' => __( 'Add New DB Query', 'sdg' ),
		'edit_item' => __( 'Edit DB Query', 'sdg' ),
		'new_item' => __( 'New DB Query', 'sdg' ),
		'view_item' => __( 'View DB Queries', 'sdg' ),
		'search_items' => __( 'Search DB Queries', 'sdg' ),
		'not_found' =>  __( 'No DB Queries Found', 'sdg' ),
		'not_found_in_trash' => __( 'No DB Queries found in Trash', 'sdg' ),
	);
	
	$args = array(
		'labels' => $labels,
	 	'public' => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => 'edit.php?post_type=admin_note',
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'db_query' ),
        'capability_type' => array('admin_note', 'admin_notes'),
        'map_meta_cap'       => true,
        'has_archive'        => true,
        'hierarchical'       => false,
	 	//'menu_icon'          => 'dashicons-welcome-write-blog',
        'menu_position'      => null,
        'supports'           => array( 'title', 'author', 'thumbnail', 'editor', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ), //
		'taxonomies' => array( 'data_table', 'query_tag', 'admin_tag' ),
		'show_in_rest' => true,    
	);

	register_post_type( 'db_query', $args );
	
}
//add_action( 'init', 'register_post_type_db_query' );

if ( in_array('data_tables', $sdg_modules ) ) {
	// Data Table
	function register_post_type_data_table() {

		$labels = array(
			'name' => __( 'Data Tables', 'sdg' ),
			'singular_name' => __( 'Data Table', 'sdg' ),
			'add_new' => __( 'New Data Table', 'sdg' ),
			'add_new_item' => __( 'Add New Data Table', 'sdg' ),
			'edit_item' => __( 'Edit Data Table', 'sdg' ),
			'new_item' => __( 'New Data Table', 'sdg' ),
			'view_item' => __( 'View Data Tables', 'sdg' ),
			'search_items' => __( 'Search Data Tables', 'sdg' ),
			'not_found' =>  __( 'No Data Tables Found', 'sdg' ),
			'not_found_in_trash' => __( 'No Data Tables found in Trash', 'sdg' ),
		);
	
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => 'edit.php?post_type=admin_note',
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'data_table' ),
			'capability_type' => array('admin_note', 'admin_notes'),
			'map_meta_cap'       => true,
			'has_archive'        => true,
			'hierarchical'       => false,
			//'menu_icon'          => 'dashicons-welcome-write-blog',
			'menu_position'      => null,
			'supports'           => array( 'title', 'author', 'thumbnail', 'custom-fields', 'revisions', 'page-attributes' ),
			'taxonomies' => array( 'admin_tag' ),
			'show_in_rest' => true,    
		);

		register_post_type( 'data_table', $args );
	
	}
	add_action( 'init', 'register_post_type_data_table' );
}

/*** PEOPLE & ENSEMBLES ***/

// TODO: change "person" to "individual", to better include plants and animals? w/ ACF field groups based on category/species
if ( in_array('people', $sdg_modules ) ) {
	// Person
	function register_post_type_person() {

		$labels = array(
			'name' => __( 'People', 'sdg' ),
			'singular_name' => __( 'Person', 'sdg' ),
			'add_new' => __( 'New Person', 'sdg' ),
			'add_new_item' => __( 'Add New Person', 'sdg' ),
			'edit_item' => __( 'Edit Person', 'sdg' ),
			'new_item' => __( 'New Person', 'sdg' ),
			'view_item' => __( 'View People', 'sdg' ),
			'search_items' => __( 'Search People', 'sdg' ),
			'not_found' =>  __( 'No People Found', 'sdg' ),
			'not_found_in_trash' => __( 'No People found in Trash', 'sdg' ),
		);
	
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'person' ),
			'capability_type' => array('person', 'people'),
			'map_meta_cap'       => true,
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_icon'          => 'dashicons-groups',
			'menu_position'      => null,
			'supports'           => array( 'title', 'author', 'thumbnail', 'editor', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ),
			'taxonomies' => array( 'people_category', 'people_tag', 'admin_tag' ),
			'show_in_rest' => true,    
		);

		register_post_type( 'person', $args );
	
	}
	add_action( 'init', 'register_post_type_person' );
}

if ( in_array('ensembles', $sdg_modules ) ) {
	// Ensemble
	function register_post_type_ensemble() {

		$labels = array(
			'name' => __( 'Ensembles', 'sdg' ),
			'singular_name' => __( 'Ensemble', 'sdg' ),
			'add_new' => __( 'New Ensemble', 'sdg' ),
			'add_new_item' => __( 'Add New Ensemble', 'sdg' ),
			'edit_item' => __( 'Edit Ensemble', 'sdg' ),
			'new_item' => __( 'New Ensemble', 'sdg' ),
			'view_item' => __( 'View Ensembles', 'sdg' ),
			'search_items' => __( 'Search Ensembles', 'sdg' ),
			'not_found' =>  __( 'No Ensembles Found', 'sdg' ),
			'not_found_in_trash' => __( 'No Ensemble found in Trash', 'sdg' ),
		);
	
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'ensemble' ),
			'capability_type' => array('ensemble', 'ensembles'),
			'map_meta_cap'       => true,
			'has_archive'        => true,
			'hierarchical'       => false,
			//'menu_icon'          => 'dashicons-groups',
			'menu_position'      => null,
			'supports'           => array( 'title', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ), //'editor', 
			'taxonomies' => array( 'admin_tag' ),
			'show_in_rest' => true,    
		);

		register_post_type( 'ensemble', $args );
	
	}
	add_action( 'init', 'register_post_type_ensemble' );
}

if ( in_array('organizations', $sdg_modules ) ) {
	// Organization
	function register_post_type_organization() {

		$labels = array(
			'name' => __( 'Organizations', 'sdg' ),
			'singular_name' => __( 'Organization', 'sdg' ),
			'add_new' => __( 'New Organization', 'sdg' ),
			'add_new_item' => __( 'Add New Organization', 'sdg' ),
			'edit_item' => __( 'Edit Organization', 'sdg' ),
			'new_item' => __( 'New Organization', 'sdg' ),
			'view_item' => __( 'View Organizations', 'sdg' ),
			'search_items' => __( 'Search Organizations', 'sdg' ),
			'not_found' =>  __( 'No Organizations Found', 'sdg' ),
			'not_found_in_trash' => __( 'No Organizations found in Trash', 'sdg' ),
		);
	
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'organization' ),
			//'capability_type' => array('organization', 'organizations'),
			'map_meta_cap'       => true,
			'has_archive'        => true,
			'hierarchical'       => false,
			//'menu_icon'          => 'dashicons-groups',
			'menu_position'      => null,
			'supports'           => array( 'title', 'author', 'thumbnail', 'editor', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ), 
			'taxonomies' => array( 'admin_tag' ),
			'show_in_rest' => true,    
		);

		register_post_type( 'organization', $args );
	
	}
	add_action( 'init', 'register_post_type_organization' );
}

/*** PROJECTS ***/

if ( in_array('projects', $sdg_modules ) ) {

	// Project
	function register_post_type_project() {

		$labels = array(
			'name' => __( 'Projects', 'sdg' ),
			'singular_name' => __( 'Project', 'sdg' ),
			'add_new' => __( 'New Project', 'sdg' ),
			'add_new_item' => __( 'Add New Project', 'sdg' ),
			'edit_item' => __( 'Edit Project', 'sdg' ),
			'new_item' => __( 'New Project', 'sdg' ),
			'view_item' => __( 'View Projects', 'sdg' ),
			'search_items' => __( 'Search Projects', 'sdg' ),
			'not_found' =>  __( 'No Projects Found', 'sdg' ),
			'not_found_in_trash' => __( 'No Projects found in Trash', 'sdg' ),
		);
	
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'project' ),
			'capability_type' => array('project', 'projects'),
			'map_meta_cap'       => true,
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_icon'          => 'dashicons-welcome-write-blog',
			'menu_position'      => null,
			'supports'           => array( 'title', 'author', 'thumbnail', 'editor', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ),
			'taxonomies' => array( 'admin_tag', 'project_category' ), //'people_category', 'people_tag', 
			'show_in_rest' => true,    
		);

		register_post_type( 'project', $args );
	
	}
	add_action( 'init', 'register_post_type_project' );
	
	// Recording (Discography)
	function register_post_type_recording() {

		$labels = array(
			'name' => __( 'Recordings', 'sdg' ),
			'singular_name' => __( 'Recording', 'sdg' ),
			'add_new' => __( 'New Recording', 'sdg' ),
			'add_new_item' => __( 'Add New Recording', 'sdg' ),
			'edit_item' => __( 'Edit Recording', 'sdg' ),
			'new_item' => __( 'New Recording', 'sdg' ),
			'view_item' => __( 'View Recording', 'sdg' ),
			'search_items' => __( 'Search Recordings', 'sdg' ),
			'not_found' =>  __( 'No Recordings Found', 'sdg' ),
			'not_found_in_trash' => __( 'No Recordings found in Trash', 'sdg' ),
		);
	
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'recording' ),
			//'capability_type' => array('publication', 'publications'),
			'map_meta_cap'       => true,
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_icon'          => 'dashicons-album',
			'menu_position'      => null,
			'supports'           => array( 'title', 'author', 'thumbnail', 'editor', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ), //
			'taxonomies' => array( 'recording_category', 'admin_tag' ),
			'show_in_rest' => true,    
		);

		register_post_type( 'recording', $args );
	
	}
	//add_action( 'init', 'register_post_type_recording' );


}

/*** PRESS ***/

if ( in_array('press', $sdg_modules ) ) {
	// Press
	function sdg_register_post_type_press() {

		$labels = array(
			'name' => __( 'Press', 'sdg' ),
			'singular_name' => __( 'Press', 'sdg' ),
			'add_new' => __( 'New Press Item', 'sdg' ),
			'add_new_item' => __( 'Add New Press Item', 'sdg' ),
			'edit_item' => __( 'Edit Press Item', 'sdg' ),
			'new_item' => __( 'New Press Item', 'sdg' ),
			'view_item' => __( 'View Press', 'sdg' ),
			'search_items' => __( 'Search Press', 'sdg' ),
			'not_found' =>  __( 'No Press Found', 'sdg' ),
			'not_found_in_trash' => __( 'No Press found in Trash', 'sdg' ),
		);
	
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'press' ),
			//'capability_type' => array('press', 'press'),
			'map_meta_cap'       => true,
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_icon'          => 'dashicons-welcome-write-blog',
			'menu_position'      => null,
			'supports'           => array( 'title', 'author', 'thumbnail', 'editor', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ),
			'taxonomies' => array( 'admin_tag', 'press_category' ),
			'show_in_rest' => true,    
		);

		register_post_type( 'press', $args );
	
	}
	add_action( 'init', 'sdg_register_post_type_press' );
}

/*** NEWSLETTER ***/

if ( in_array('newsletters', $sdg_modules ) ) {
	// Newsletter
	function sdg_register_post_type_newsletter() {

		$labels = array(
			'name' => __( 'Newsletters', 'sdg' ),
			'singular_name' => __( 'Newsletter', 'sdg' ),
			'add_new' => __( 'New Newsletter', 'sdg' ),
			'add_new_item' => __( 'Add New Newsletter', 'sdg' ),
			'edit_item' => __( 'Edit Newsletter', 'sdg' ),
			'new_item' => __( 'New Newsletter', 'sdg' ),
			'view_item' => __( 'View Newsletter', 'sdg' ),
			'search_items' => __( 'Search Newsletters', 'sdg' ),
			'not_found' =>  __( 'No Newsletter Found', 'sdg' ),
			'not_found_in_trash' => __( 'No Newsletters found in Trash', 'sdg' ),
		);
	
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'newsletter' ),
			//'capability_type' => array('newsletter', 'newsletter'),
			'map_meta_cap'       => true,
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_icon'          => 'dashicons-welcome-write-blog',
			'menu_position'      => null,
			'supports'           => array( 'title', 'author', 'thumbnail', 'editor', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ),
			//'taxonomies' => array( 'admin_tag', 'press_category' ),
			'show_in_rest' => true,    
		);

		register_post_type( 'newsletter', $args );
	
	}
	add_action( 'init', 'sdg_register_post_type_newsletter' );
}

/*** MUSIC LIBRARY ***/

// TODO: generalize as "library" w/ sub-options for music?
if ( in_array('music', $sdg_modules ) ) {

	// Repertoire, aka Musical Work
	function register_post_type_repertoire() {

		$labels = array(
			'name' => __( 'Musical Works', 'sdg' ),
			'singular_name' => __( 'Musical Work', 'sdg' ),
			'add_new' => __( 'New Musical Work', 'sdg' ),
			'add_new_item' => __( 'Add New Musical Work', 'sdg' ),
			'edit_item' => __( 'Edit Musical Work', 'sdg' ),
			'new_item' => __( 'New Musical Work', 'sdg' ),
			'view_item' => __( 'View Musical Works', 'sdg' ),
			'search_items' => __( 'Search Musical Works', 'sdg' ),
			'not_found' =>  __( 'No Musical Works Found', 'sdg' ),
			'not_found_in_trash' => __( 'No Musical Works found in Trash', 'sdg' ),
		);
	
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'repertoire' ),
			'capability_type' => array('musicwork', 'repertoire'),
			'map_meta_cap'       => true,
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_icon'          => 'dashicons-book',
			'menu_position'      => null,
			'supports'           => array( 'title', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ), //'editor', 
			'taxonomies' => array( 'repertoire_category', 'occasion', 'season', 'post_tag', 'admin_tag' ),
			'show_in_rest' => true,    
		);

		register_post_type( 'repertoire', $args );
	
	}
	add_action( 'init', 'register_post_type_repertoire' );

	// Edition
	function register_post_type_edition() {

		$labels = array(
			'name' => __( 'Editions', 'sdg' ),
			'singular_name' => __( 'Edition', 'sdg' ),
			'add_new' => __( 'New Edition', 'sdg' ),
			'add_new_item' => __( 'Add New Edition', 'sdg' ),
			'edit_item' => __( 'Edit Edition', 'sdg' ),
			'new_item' => __( 'New Edition', 'sdg' ),
			'view_item' => __( 'View Edition', 'sdg' ),
			'search_items' => __( 'Search Editions', 'sdg' ),
			'not_found' =>  __( 'No Editions Found', 'sdg' ),
			'not_found_in_trash' => __( 'No Editions found in Trash', 'sdg' ),
		);
	
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => 'edit.php?post_type=repertoire',
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'edition' ),
			'capability_type' => array('edition', 'editions'),
			'map_meta_cap'       => true,
			'has_archive'        => true,
			'hierarchical'       => false,
			//'menu_icon'          => 'dashicons-book',
			'menu_position'      => null,
			'supports'           => array( 'title', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ), //'editor', 
			'taxonomies' => array( 'instrument', 'key', 'soloist', 'voicing', 'library_tag', 'admin_tag' ),
			'show_in_rest' => true,    
		);

		register_post_type( 'edition', $args );
	
	}
	add_action( 'init', 'register_post_type_edition' );

	// Publisher
	function register_post_type_publisher() {

		$labels = array(
			'name' => __( 'Publishers', 'sdg' ),
			'singular_name' => __( 'Publisher', 'sdg' ),
			'add_new' => __( 'New Publisher', 'sdg' ),
			'add_new_item' => __( 'Add New Publisher', 'sdg' ),
			'edit_item' => __( 'Edit Publisher', 'sdg' ),
			'new_item' => __( 'New Publisher', 'sdg' ),
			'view_item' => __( 'View Publishers', 'sdg' ),
			'search_items' => __( 'Search Publishers', 'sdg' ),
			'not_found' =>  __( 'No Publishers Found', 'sdg' ),
			'not_found_in_trash' => __( 'No Publishers found in Trash', 'sdg' ),
		);
	
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => 'edit.php?post_type=publication',
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'publisher' ),
			'capability_type' => array('publisher', 'publishers'),
			'map_meta_cap'       => true,
			'has_archive'        => true,
			'hierarchical'       => false,
			//'menu_icon'          => 'dashicons-book',
			'menu_position'      => null,
			'supports'           => array( 'title', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ), //'editor', 
			'taxonomies' => array( 'admin_tag' ),
			'show_in_rest' => true,    
		);

		register_post_type( 'publisher', $args );
	
	}
	add_action( 'init', 'register_post_type_publisher' );

	// Publication
	function register_post_type_publication() {

		$labels = array(
			'name' => __( 'Publications', 'sdg' ),
			'singular_name' => __( 'Publication', 'sdg' ),
			'add_new' => __( 'New Publication', 'sdg' ),
			'add_new_item' => __( 'Add New Publication', 'sdg' ),
			'edit_item' => __( 'Edit Publication', 'sdg' ),
			'new_item' => __( 'New Publication', 'sdg' ),
			'view_item' => __( 'View Publications', 'sdg' ),
			'search_items' => __( 'Search Publications', 'sdg' ),
			'not_found' =>  __( 'No Publications Found', 'sdg' ),
			'not_found_in_trash' => __( 'No Publications found in Trash', 'sdg' ),
		);
	
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'publication' ),
			'capability_type' => array('publication', 'publications'),
			'map_meta_cap'       => true,
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_icon'          => 'dashicons-book-alt',
			'menu_position'      => null,
			'supports'           => array( 'title', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ), //'editor', 
			'taxonomies' => array( 'publication_category', 'admin_tag' ),
			'show_in_rest' => true,    
		);

		register_post_type( 'publication', $args );
	
	}
	add_action( 'init', 'register_post_type_publication' );

	// Music List
	function register_post_type_music_list() {

		$labels = array(
			'name' => __( 'Music Lists', 'sdg' ),
			'singular_name' => __( 'Music List', 'sdg' ),
			'add_new' => __( 'New Music List', 'sdg' ),
			'add_new_item' => __( 'Add New Music List', 'sdg' ),
			'edit_item' => __( 'Edit Music List', 'sdg' ),
			'new_item' => __( 'New Music List', 'sdg' ),
			'view_item' => __( 'View Music Lists', 'sdg' ),
			'search_items' => __( 'Search Music Lists', 'sdg' ),
			'not_found' =>  __( 'No Music Lists Found', 'sdg' ),
			'not_found_in_trash' => __( 'No Music Lists found in Trash', 'sdg' ),
		);
	
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'music_list' ),
			'capability_type' => array('music_list', 'music_lists'),
			'map_meta_cap'       => true,
			'has_archive'        => true,
			'hierarchical'       => false,
			//'menu_icon'          => 'dashicons-book',
			'menu_position'      => null,
			'supports'           => array( 'title', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ), //'editor', 
			'taxonomies' => array( 'admin_tag' ),
			'show_in_rest' => true,    
		);

		register_post_type( 'music_list', $args );
	
	}
	//add_action( 'init', 'register_post_type_music_list' );

}

/*** INVENTORY ***/
// WIP
// item? thing? possession? object?
if ( in_array('inventory', $sdg_modules ) ) {
	// Thing
	function sdg_register_post_type_thing() {

		$labels = array(
			'name' => __( 'Things', 'sdg' ),
			'singular_name' => __( 'Thing', 'sdg' ),
			'add_new' => __( 'New Thing', 'sdg' ),
			'add_new_item' => __( 'Add New Thing', 'sdg' ),
			'edit_item' => __( 'Edit Thing', 'sdg' ),
			'new_item' => __( 'New Thing', 'sdg' ),
			'view_item' => __( 'View Thing', 'sdg' ),
			'search_items' => __( 'Search Things', 'sdg' ),
			'not_found' =>  __( 'No Things Found', 'sdg' ),
			'not_found_in_trash' => __( 'No Things found in Trash', 'sdg' ),
		);
	
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'thing' ),
			'map_meta_cap'       => true,
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_icon'          => 'dashicons-welcome-write-blog',
			'menu_position'      => null,
			'supports'           => array( 'title', 'author', 'thumbnail', 'editor', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ),
			//'taxonomies' => array( 'admin_tag', 'press_category' ),
			'show_in_rest' => true,    
		);

		register_post_type( 'thing', $args );
	
	}
	add_action( 'init', 'sdg_register_post_type_thing' );
}

/*** LOGBOOK ***/
// WIP -- consider log entries model vs calendar events -- see ATCHQ ACF field group "Logbook (Library)" >> log_entries repeater.
// Is there any need for a special post type -- or instead a Logbook/Log Entries field group applied to multiple post types? 

/*** LECTIONARY ***/

if ( in_array('lectionary', $sdg_modules ) ) {

	// Bible Book
	function register_post_type_bible_book() {

		$labels = array(
			'name' => __( 'Books of the Bible', 'sdg' ),
			'singular_name' => __( 'Bible Book', 'sdg' ),
			'add_new' => __( 'New Bible Book', 'sdg' ),
			'add_new_item' => __( 'Add New Bible Book', 'sdg' ),
			'edit_item' => __( 'Edit Bible Book', 'sdg' ),
			'new_item' => __( 'New Bible Book', 'sdg' ),
			'view_item' => __( 'View Books of the Bible', 'sdg' ),
			'search_items' => __( 'Search Books of the Bible', 'sdg' ),
			'not_found' =>  __( 'No Books of the Bible Found', 'sdg' ),
			'not_found_in_trash' => __( 'No Books of the Bible found in Trash', 'sdg' ),
		);
	
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => 'edit.php?post_type=lectionary',
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'bible_book' ),
			'capability_type' => array('bible_book', 'bible_books'),
			'map_meta_cap'       => true,
			'has_archive'        => true,
			'hierarchical'       => false,
			//'menu_icon'          => 'dashicons-book',
			'menu_position'      => null,
			'supports'           => array( 'title', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ), //'editor', 
			'taxonomies' => array( 'admin_tag' ),
			'show_in_rest' => true,    
		);

		register_post_type( 'bible_book', $args );
	
	}
	add_action( 'init', 'register_post_type_bible_book' );

	// Reading
	function register_post_type_reading() {

		$labels = array(
			'name' => __( 'Readings', 'sdg' ),
			'singular_name' => __( 'Reading', 'sdg' ),
			'add_new' => __( 'New Reading', 'sdg' ),
			'add_new_item' => __( 'Add New Reading', 'sdg' ),
			'edit_item' => __( 'Edit Reading', 'sdg' ),
			'new_item' => __( 'New Reading', 'sdg' ),
			'view_item' => __( 'View Readings', 'sdg' ),
			'search_items' => __( 'Search Readings', 'sdg' ),
			'not_found' =>  __( 'No Readings Found', 'sdg' ),
			'not_found_in_trash' => __( 'No Readings found in Trash', 'sdg' ),
		);
	
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => 'edit.php?post_type=lectionary',
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'reading' ),
			'capability_type'    => array('lectionary_item', 'lectionary'),
			//'capability_type' => array('reading', 'readings'),
			'map_meta_cap'       => true,
			'has_archive'        => true,
			'hierarchical'       => false,
			//'menu_icon'          => 'dashicons-book',
			'menu_position'      => null,
			'supports'           => array( 'title', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ), //'editor', 
			'taxonomies' => array( 'admin_tag' ),
			'show_in_rest' => true,    
		);

		register_post_type( 'reading', $args );
	
	}
	add_action( 'init', 'register_post_type_reading' );

	// Lectionary Day
	function register_post_type_lectionary() {

		$labels = array(
			'name' => __( 'Lectionary', 'sdg' ),
			'singular_name' => __( 'Lectionary Day', 'sdg' ),
			'add_new' => __( 'New Lectionary Day', 'sdg' ),
			'add_new_item' => __( 'Add New Lectionary Day', 'sdg' ),
			'edit_item' => __( 'Edit Lectionary Day', 'sdg' ),
			'new_item' => __( 'New Lectionary Day', 'sdg' ),
			'view_item' => __( 'View Lectionary', 'sdg' ),
			'search_items' => __( 'Search Lectionary', 'sdg' ),
			'not_found' =>  __( 'No Lectionary Days Found', 'sdg' ),
			'not_found_in_trash' => __( 'No Lectionary Days found in Trash', 'sdg' ),
		);
	
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'lectionary' ),
			'capability_type'    => array('lectionary_item', 'lectionary'),
			//'capability_type' => array('lectionary_day', 'lectionary_days'),
			'map_meta_cap'       => true,
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_icon'          => 'dashicons-calendar-alt',
			'menu_position'      => null,
			'supports'           => array( 'title', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ), //'editor', 
			'taxonomies' => array( 'admin_tag' ),
			'show_in_rest' => true,    
		);

		register_post_type( 'lectionary', $args );
	
	}
	add_action( 'init', 'register_post_type_lectionary' );

	// Liturgical Date
	function register_post_type_liturgical_date() {

		$labels = array(
			'name' => __( 'Liturgical Calendar', 'sdg' ),
			'singular_name' => __( 'Liturgical Date', 'sdg' ),
			'add_new' => __( 'New Liturgical Date', 'sdg' ),
			'add_new_item' => __( 'Add New Liturgical Date', 'sdg' ),
			'edit_item' => __( 'Edit Liturgical Date', 'sdg' ),
			'new_item' => __( 'New Liturgical Date', 'sdg' ),
			'view_item' => __( 'View Liturgical Dates', 'sdg' ),
			'search_items' => __( 'Search Liturgical Dates', 'sdg' ),
			'not_found' =>  __( 'No Liturgical Dates Found', 'sdg' ),
			'not_found_in_trash' => __( 'No Liturgical Dates found in Trash', 'sdg' ),
		);
	
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => 'edit.php?post_type=lectionary',
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'liturgical_date' ),
			'capability_type'    => array('lectionary_item', 'lectionary'),
			'map_meta_cap'       => true,
			'has_archive'        => true,
			'hierarchical'       => false,
			//'menu_icon'          => 'dashicons-calendar-alt',
			'menu_position'      => null,
			'supports'           => array( 'title', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ), //'editor', 
			'taxonomies' => array( 'admin_tag' ),
			'show_in_rest' => true,    
		);

		register_post_type( 'liturgical_date', $args );
	
	}
	add_action( 'init', 'register_post_type_liturgical_date' );

	// Liturgical Date Calculation
	function register_post_type_liturgical_date_calc() {

		$labels = array(
			'name' => __( 'Liturgical Date Calculations', 'sdg' ),
			'singular_name' => __( 'Liturgical Date Calculation', 'sdg' ),
			'add_new' => __( 'New Liturgical Date Calculation', 'sdg' ),
			'add_new_item' => __( 'Add New Liturgical Date Calculation', 'sdg' ),
			'edit_item' => __( 'Edit Liturgical Date Calculation', 'sdg' ),
			'new_item' => __( 'New Liturgical Date Calculation', 'sdg' ),
			'view_item' => __( 'View Liturgical Date Calculations', 'sdg' ),
			'search_items' => __( 'Search Liturgical Date Calculations', 'sdg' ),
			'not_found' =>  __( 'No Liturgical Date Calculations Found', 'sdg' ),
			'not_found_in_trash' => __( 'No Liturgical Date Calculations found in Trash', 'sdg' ),
		);
	
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => 'edit.php?post_type=lectionary',
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'liturgical_date_calc' ),
			'capability_type'    => array('lectionary_item', 'lectionary'),
			'map_meta_cap'       => true,
			'has_archive'        => true,
			'hierarchical'       => false,
			//'menu_icon'          => 'dashicons-calendar-alt',
			'menu_position'      => null,
			'supports'           => array( 'title', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ), //'editor', 
			'taxonomies' => array( 'admin_tag' ),
			'show_in_rest' => true,    
		);

		register_post_type( 'liturgical_date_calc', $args );
	
	}
	add_action( 'init', 'register_post_type_liturgical_date_calc' );

	// Collect
	function register_post_type_collect() {

		$labels = array(
			'name' => __( 'Collects', 'sdg' ),
			'singular_name' => __( 'Collect', 'sdg' ),
			'add_new' => __( 'New Collect', 'sdg' ),
			'add_new_item' => __( 'Add New Collect', 'sdg' ),
			'edit_item' => __( 'Edit Collect', 'sdg' ),
			'new_item' => __( 'New Collect', 'sdg' ),
			'view_item' => __( 'View Collects', 'sdg' ),
			'search_items' => __( 'Search Collects', 'sdg' ),
			'not_found' =>  __( 'No Collects Found', 'sdg' ),
			'not_found_in_trash' => __( 'No Collects found in Trash', 'sdg' ),
		);
	
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => 'edit.php?post_type=lectionary',
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'collect' ),
			'capability_type'    => array('lectionary_item', 'lectionary'),
			'map_meta_cap'       => true,
			'has_archive'        => true,
			'hierarchical'       => false,
			//'menu_icon'          => 'dashicons-welcome-write-blog',
			'menu_position'      => null,
			'supports'           => array( 'title', 'author', 'thumbnail', 'editor', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ), //
			'taxonomies' => array( 'season', 'admin_tag' ),
			'show_in_rest' => true,    
		);

		register_post_type( 'collect', $args );
	
	}
	add_action( 'init', 'register_post_type_collect' );

	// Psalms of the Day
	function register_post_type_psalms_of_the_day() {

		$labels = array(
			'name' => __( 'Psalms of the Day', 'sdg' ),
			'singular_name' => __( 'Psalms of the Day', 'sdg' ),
			'add_new' => __( 'New Psalms of the Day', 'sdg' ),
			'add_new_item' => __( 'Add New Psalms of the Day', 'sdg' ),
			'edit_item' => __( 'Edit Psalms of the Day', 'sdg' ),
			'new_item' => __( 'New Psalms of the Day', 'sdg' ),
			'view_item' => __( 'View Psalms of the Day', 'sdg' ),
			'search_items' => __( 'Search Psalms of the Day', 'sdg' ),
			'not_found' =>  __( 'No Psalms of the Day Found', 'sdg' ),
			'not_found_in_trash' => __( 'No Psalms of the Day found in Trash', 'sdg' ),
		);
	
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => 'edit.php?post_type=lectionary',
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'psalms_of_the_day' ),
			'capability_type'    => array('lectionary_item', 'lectionary'),
			//'capability_type' => array('lectionary', 'lectionary'),
			'map_meta_cap'       => true,
			'has_archive'        => true,
			'hierarchical'       => false,
			//'menu_icon'          => 'dashicons-welcome-write-blog',
			'menu_position'      => null,
			'supports'           => array( 'title', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ), //'editor', 
			'taxonomies' => array( 'admin_tag' ),
			'show_in_rest' => true,    
		);

		register_post_type( 'psalms_of_the_day', $args );
	
	}
	add_action( 'init', 'register_post_type_psalms_of_the_day' );

}

/*** SERMONS ***/

if ( in_array('sermons', $sdg_modules ) ) {

	// Sermon
	function register_post_type_sermon() {

		$labels = array(
			'name' => __( 'Sermons', 'sdg' ),
			'singular_name' => __( 'Sermon', 'sdg' ),
			'add_new' => __( 'New Sermon', 'sdg' ),
			'add_new_item' => __( 'Add New Sermon', 'sdg' ),
			'edit_item' => __( 'Edit Sermon', 'sdg' ),
			'new_item' => __( 'New Sermon', 'sdg' ),
			'view_item' => __( 'View Sermons', 'sdg' ),
			'search_items' => __( 'Search Sermons', 'sdg' ),
			'not_found' =>  __( 'No Sermons Found', 'sdg' ),
			'not_found_in_trash' => __( 'No Sermons found in Trash', 'sdg' ),
		);
	
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'sermon' ),
			'capability_type' => array('sermon', 'sermons'),
			'map_meta_cap'       => true,
			'has_archive'        => 'sermon-archive',
			//'has_archive'        => true,
			'hierarchical'       => false,
			'menu_icon'          => 'dashicons-welcome-write-blog',
			'menu_position'      => null,
			'supports'           => array( 'title', 'author', 'thumbnail', 'editor', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ), //
			'taxonomies' => array( 'sermon_topic', 'admin_tag' ),
			'show_in_rest' => true,    
		);

		register_post_type( 'sermon', $args );
	
	}
	add_action( 'init', 'register_post_type_sermon' );

	// Sermon Series
	function register_post_type_sermon_series() {

		$labels = array(
			'name' => __( 'Sermon Series', 'sdg' ),
			'singular_name' => __( 'Sermon Series', 'sdg' ),
			'add_new' => __( 'New Sermon Series', 'sdg' ),
			'add_new_item' => __( 'Add New Sermon Series', 'sdg' ),
			'edit_item' => __( 'Edit Sermon Series', 'sdg' ),
			'new_item' => __( 'New Sermon Series', 'sdg' ),
			'view_item' => __( 'View Sermon Series', 'sdg' ),
			'search_items' => __( 'Search Sermon Series', 'sdg' ),
			'not_found' =>  __( 'No Sermon Series Found', 'sdg' ),
			'not_found_in_trash' => __( 'No Sermon Series found in Trash', 'sdg' ),
		);
	
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => 'edit.php?post_type=sermon',
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'sermon_series' ),
			//'capability_type' => array('lectionary', 'lectionary'),
			'map_meta_cap'       => true,
			'has_archive'        => true,
			'hierarchical'       => false,
			//'menu_icon'          => 'dashicons-book',
			'menu_position'      => null,
			'supports'           => array( 'title', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ), //'editor', 
			'taxonomies' => array( 'admin_tag' ),
			'show_in_rest' => true,    
		);

		register_post_type( 'sermon_series', $args );
	
	}
	add_action( 'init', 'register_post_type_sermon_series' );

}

/*** EVENTS (extended EM) ***/

if ( in_array('events', $sdg_modules ) ) {

	// Event Series
	function register_post_type_event_series() {

		$labels = array(
			'name' => __( 'Event Series', 'sdg' ),
			'singular_name' => __( 'Event Series', 'sdg' ),
			'add_new' => __( 'New Event Series', 'sdg' ),
			'add_new_item' => __( 'Add New Event Series', 'sdg' ),
			'edit_item' => __( 'Edit Event Series', 'sdg' ),
			'new_item' => __( 'New Event Series', 'sdg' ),
			'view_item' => __( 'View Event Series', 'sdg' ),
			'search_items' => __( 'Search Event Series', 'sdg' ),
			'not_found' =>  __( 'No Event Series Found', 'sdg' ),
			'not_found_in_trash' => __( 'No Event Series found in Trash', 'sdg' ),
		);
	
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => 'edit.php?post_type=event',
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'event_series' ),
			//'capability_type' => array('lectionary', 'lectionary'),
			'map_meta_cap'       => true,
			'has_archive'        => true,
			'hierarchical'       => false,
			//'menu_icon'          => 'dashicons-book',
			'menu_position'      => null,
			'supports'           => array( 'title', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ), //'editor', 
			'taxonomies' => array( 'admin_tag' ),
			'show_in_rest' => true,    
		);

		register_post_type( 'event_series', $args );
	
	}
	add_action( 'init', 'register_post_type_event_series' );

}

/*** ORGANS ***/

// TODO: generalize as "instruments"?
if ( in_array('organs', $sdg_modules ) ) {

	// Organ
	function register_post_type_organ() {

		$labels = array(
			'name' => __( 'Organs', 'sdg' ),
			'singular_name' => __( 'Organ', 'sdg' ),
			'add_new' => __( 'New Organ', 'sdg' ),
			'add_new_item' => __( 'Add New Organ', 'sdg' ),
			'edit_item' => __( 'Edit Organ', 'sdg' ),
			'new_item' => __( 'New Organ', 'sdg' ),
			'view_item' => __( 'View Organs', 'sdg' ),
			'search_items' => __( 'Search Organs', 'sdg' ),
			'not_found' =>  __( 'No Organs Found', 'sdg' ),
			'not_found_in_trash' => __( 'No Organs found in Trash', 'sdg' ),
		);
	
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'organ' ),
			'capability_type' => array('organ', 'organs'),
			'map_meta_cap'       => true,
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_icon'          => 'dashicons-playlist-audio',
			'menu_position'      => null,
			'supports'           => array( 'title', 'author', 'thumbnail', 'editor', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ), //
			'taxonomies' => array( 'admin_tag' ), //'people_category', 'people_tag', 
			'show_in_rest' => false, // i.e. false = use classic, not block editor
		);

		register_post_type( 'organ', $args );
	
	}
	add_action( 'init', 'register_post_type_organ' );

	// Organ Builder
	function register_post_type_builder() {

		$labels = array(
			'name' => __( 'Builders', 'sdg' ),
			'singular_name' => __( 'Builder', 'sdg' ),
			'add_new' => __( 'New Builder', 'sdg' ),
			'add_new_item' => __( 'Add New Builder', 'sdg' ),
			'edit_item' => __( 'Edit Builder', 'sdg' ),
			'new_item' => __( 'New Builder', 'sdg' ),
			'view_item' => __( 'View Builders', 'sdg' ),
			'search_items' => __( 'Search Builders', 'sdg' ),
			'not_found' =>  __( 'No Builders Found', 'sdg' ),
			'not_found_in_trash' => __( 'No Builders found in Trash', 'sdg' ),
		);
	
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => 'edit.php?post_type=organ',
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'builder' ),
			'capability_type' => array('builder', 'builders'),
			'map_meta_cap'       => true,
			'has_archive'        => true,
			'hierarchical'       => false,
			//'menu_icon'          => 'dashicons-welcome-write-blog',
			'menu_position'      => null,
			'supports'           => array( 'title', 'author', 'thumbnail', 'editor', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ), //
			'taxonomies' => array( 'admin_tag' ), //'people_category', 'people_tag', 
			'show_in_rest' => false, // i.e. false = use classic, not block editor
		);

		register_post_type( 'builder', $args );
	
	}
	add_action( 'init', 'register_post_type_builder' );

	// Division
	function register_post_type_division() {

		$labels = array(
			'name' => __( 'Divisions', 'sdg' ),
			'singular_name' => __( 'Division', 'sdg' ),
			'add_new' => __( 'New Division', 'sdg' ),
			'add_new_item' => __( 'Add New Division', 'sdg' ),
			'edit_item' => __( 'Edit Division', 'sdg' ),
			'new_item' => __( 'New Division', 'sdg' ),
			'view_item' => __( 'View Divisions', 'sdg' ),
			'search_items' => __( 'Search Divisions', 'sdg' ),
			'not_found' =>  __( 'No Divisions Found', 'sdg' ),
			'not_found_in_trash' => __( 'No Divisions found in Trash', 'sdg' ),
		);
	
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => 'edit.php?post_type=organ',
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'division' ),
			'capability_type' => array('organ', 'organs'),
			'map_meta_cap'       => true,
			'has_archive'        => true,
			'hierarchical'       => false,
			//'menu_icon'          => 'dashicons-welcome-write-blog',
			'menu_position'      => null,
			'supports'           => array( 'title', 'author', 'thumbnail', 'editor', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ), //
			'taxonomies' => array( 'admin_tag' ), //'people_category', 'people_tag', 
			'show_in_rest' => false, // i.e. false = use classic, not block editor
		);

		register_post_type( 'division', $args );
	
	}
	add_action( 'init', 'register_post_type_division' );

	// Manual
	function register_post_type_manual() {

		$labels = array(
			'name' => __( 'Manuals', 'sdg' ),
			'singular_name' => __( 'Manual', 'sdg' ),
			'add_new' => __( 'New Manual', 'sdg' ),
			'add_new_item' => __( 'Add New Manual', 'sdg' ),
			'edit_item' => __( 'Edit Manual', 'sdg' ),
			'new_item' => __( 'New Manual', 'sdg' ),
			'view_item' => __( 'View Manuals', 'sdg' ),
			'search_items' => __( 'Search Manuals', 'sdg' ),
			'not_found' =>  __( 'No Manuals Found', 'sdg' ),
			'not_found_in_trash' => __( 'No Manuals found in Trash', 'sdg' ),
		);
	
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => 'edit.php?post_type=organ',
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'manual' ),
			'capability_type' => array('organ', 'organs'),
			'map_meta_cap'       => true,
			'has_archive'        => true,
			'hierarchical'       => false,
			//'menu_icon'          => 'dashicons-welcome-write-blog',
			'menu_position'      => null,
			'supports'           => array( 'title', 'author', 'thumbnail', 'editor', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ), //
			'taxonomies' => array( 'admin_tag' ), //'people_category', 'people_tag', 
			'show_in_rest' => false, // i.e. false = use classic, not block editor
		);

		register_post_type( 'manual', $args );
	
	}
	add_action( 'init', 'register_post_type_manual' );

	// Stop
	function register_post_type_stop() {

		$labels = array(
			'name' => __( 'Stops', 'sdg' ),
			'singular_name' => __( 'Stop', 'sdg' ),
			'add_new' => __( 'New Stop', 'sdg' ),
			'add_new_item' => __( 'Add New Stop', 'sdg' ),
			'edit_item' => __( 'Edit Stop', 'sdg' ),
			'new_item' => __( 'New Stop', 'sdg' ),
			'view_item' => __( 'View Stops', 'sdg' ),
			'search_items' => __( 'Search Stops', 'sdg' ),
			'not_found' =>  __( 'No Stops Found', 'sdg' ),
			'not_found_in_trash' => __( 'No Stops found in Trash', 'sdg' ),
		);
	
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => 'edit.php?post_type=organ',
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'stop' ),
			'capability_type' => array('organ', 'organs'),
			'map_meta_cap'       => true,
			'has_archive'        => true,
			'hierarchical'       => false,
			//'menu_icon'          => 'dashicons-welcome-write-blog',
			'menu_position'      => null,
			'supports'           => array( 'title', 'author', 'thumbnail', 'editor', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ), //
			'taxonomies' => array( 'admin_tag' ), //'people_category', 'people_tag', 
			'show_in_rest' => false, // i.e. false = use classic, not block editor
		);

		register_post_type( 'stop', $args );
	
	}
	add_action( 'init', 'register_post_type_stop' );

}

/*** VENUES ***/

if ( in_array('venues', $sdg_modules ) ) {

	// Venue
	function register_post_type_venue() {

		$labels = array(
			'name' => __( 'Venues', 'sdg' ),
			'singular_name' => __( 'Venue', 'sdg' ),
			'add_new' => __( 'New Venue', 'sdg' ),
			'add_new_item' => __( 'Add New Venue', 'sdg' ),
			'edit_item' => __( 'Edit Venue', 'sdg' ),
			'new_item' => __( 'New Venue', 'sdg' ),
			'view_item' => __( 'View Venues', 'sdg' ),
			'search_items' => __( 'Search Venues', 'sdg' ),
			'not_found' =>  __( 'No Venues Found', 'sdg' ),
			'not_found_in_trash' => __( 'No Venues found in Trash', 'sdg' ),
		);
	
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'venue' ),
			'capability_type' => array('venue', 'venues'),
			'map_meta_cap'       => true,
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_icon'          => 'dashicons-admin-multisite',
			'menu_position'      => null,
			'supports'           => array( 'title', 'author', 'thumbnail', 'editor', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ), //
			'taxonomies' => array( 'admin_tag', 'venue_category' ), //'venue_category', 'people_tag', 
			'show_in_rest' => false, // i.e. false = use classic, not block editor
		);

		register_post_type( 'venue', $args );
	
	}
	add_action( 'init', 'register_post_type_venue' );

	// Address
	function register_post_type_address() {

		$labels = array(
			'name' => __( 'Addresses', 'sdg' ),
			'singular_name' => __( 'Address', 'sdg' ),
			'add_new' => __( 'New Address', 'sdg' ),
			'add_new_item' => __( 'Add New Address', 'sdg' ),
			'edit_item' => __( 'Edit Address', 'sdg' ),
			'new_item' => __( 'New Address', 'sdg' ),
			'view_item' => __( 'View Addresses', 'sdg' ),
			'search_items' => __( 'Search Addresses', 'sdg' ),
			'not_found' =>  __( 'No Addresses Found', 'sdg' ),
			'not_found_in_trash' => __( 'No Addresses found in Trash', 'sdg' ),
		);
	
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => 'edit.php?post_type=venue',
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'address' ),
			'capability_type' => array('venue', 'venues'),
			'map_meta_cap'       => true,
			'has_archive'        => true,
			'hierarchical'       => false,
			//'menu_icon'          => 'dashicons-welcome-write-blog',
			'menu_position'      => null,
			'supports'           => array( 'title', 'author', 'thumbnail', 'editor', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ), //
			'taxonomies' => array( 'admin_tag' ), //'people_category', 'people_tag', 
			'show_in_rest' => false, // i.e. false = use classic, not block editor
		);

		register_post_type( 'address', $args );
	
	}
	//add_action( 'init', 'register_post_type_address' ); // disabled as redundant w/ EM locations 08/20/22
	
}

/*** SOURCES ***/

if ( in_array('sources', $sdg_modules ) ) {

	// Source
	function register_post_type_source() {

		$labels = array(
			'name' => __( 'Sources', 'sdg' ),
			'singular_name' => __( 'Source', 'sdg' ),
			'add_new' => __( 'New Source', 'sdg' ),
			'add_new_item' => __( 'Add New Source', 'sdg' ),
			'edit_item' => __( 'Edit Source', 'sdg' ),
			'new_item' => __( 'New Source', 'sdg' ),
			'view_item' => __( 'View Sources', 'sdg' ),
			'search_items' => __( 'Search Sources', 'sdg' ),
			'not_found' =>  __( 'No Sources Found', 'sdg' ),
			'not_found_in_trash' => __( 'No Sources found in Trash', 'sdg' ),
		);
	
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'source' ),
			'capability_type' => array('organ', 'organs'),
			'map_meta_cap'       => true,
			'has_archive'        => true,
			'hierarchical'       => false,
			//'menu_icon'          => 'dashicons-welcome-write-blog',
			'menu_position'      => null,
			'supports'           => array( 'title', 'author', 'thumbnail', 'editor', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ), //
			'taxonomies' => array( 'admin_tag' ), //'people_category', 'people_tag', 
			'show_in_rest' => false, // i.e. false = use classic, not block editor
		);

		register_post_type( 'source', $args );
	
	}
	add_action( 'init', 'register_post_type_source' );

}

/*** LINKS ***/

if ( in_array('links', $sdg_modules ) ) {

	// Links
	function register_post_type_link() {

		$labels = array(
			'name' => __( 'Links', 'sdg' ),
			'singular_name' => __( 'Link', 'sdg' ),
			'add_new' => __( 'New Link', 'sdg' ),
			'add_new_item' => __( 'Add New Link', 'sdg' ),
			'edit_item' => __( 'Edit Link', 'sdg' ),
			'new_item' => __( 'New Link', 'sdg' ),
			'view_item' => __( 'View Links', 'sdg' ),
			'search_items' => __( 'Search Links', 'sdg' ),
			'not_found' =>  __( 'No Links Found', 'sdg' ),
			'not_found_in_trash' => __( 'No Links found in Trash', 'sdg' ),
		);
	
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'link' ),
			//'capability_type' => array('organ', 'organs'),
			'map_meta_cap'       => true,
			'has_archive'        => true,
			'hierarchical'       => false,
			//'menu_icon'          => 'dashicons-welcome-write-blog',
			'menu_position'      => null,
			'supports'           => array( 'title', 'author', 'thumbnail', 'editor', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ), //
			'taxonomies' => array( 'admin_tag' ), //'people_category', 'people_tag', 
			'show_in_rest' => false, // i.e. false = use classic, not block editor
		);

		register_post_type( 'link', $args );
	
	}
	add_action( 'init', 'register_post_type_link' );

}

/*** HEALTH & WELLNESS ***/
//Diseases & Conditions -- condition
//Tests & Procedures -- vettest >> procedure
//Medications -- medication
//Foods -- food
//Symptoms -- symptom

/*** ***/


//
if ( in_array('music', $sdg_modules ) ) {
	add_filter('acf/update_value/name=repertoire_editions', 'bidirectional_acf_update_value', 10, 3);
	if ( is_dev_site() ) {
		//add_action('acf/save_post', 'acf_update_related_field_on_save'); // WIP
	}
}

// ACF Bi-directional fields
// WIP!
// https://www.advancedcustomfields.com/resources/bidirectional-relationships/
function bidirectional_acf_update_value( $value, $post_id, $field  ) {	
	
	// vars
	$field_name = $field['name'];
	$field_key = $field['key'];
	$global_name = 'is_updating_' . $field_name;
	
	// bail early if this filter was triggered from the update_field() function called within the loop below
	// - this prevents an infinite loop
	if( !empty($GLOBALS[ $global_name ]) ) return $value;
	
	
	// set global variable to avoid inifite loop
	// - could also remove_filter() then add_filter() again, but this is simpler
	$GLOBALS[ $global_name ] = 1;
	
	
	// loop over selected posts and add this $post_id
	if( is_array($value) ) {
	
		foreach( $value as $post_id2 ) {
			
			// load existing related posts
			$value2 = get_field($field_name, $post_id2, false);
			
			// allow for selected posts to not contain a value
			if( empty($value2) ) {
				$value2 = array();
			}
			
			// bail early if the current $post_id is already found in selected post's $value2
			if( in_array($post_id, $value2) ) continue;
			
			// append the current $post_id to the selected post's 'related_posts' value
			$value2[] = $post_id;
			
			// update the selected post's value (use field's key for performance)
			update_field($field_key, $value2, $post_id2);
			
		}
	
	}
	
	// find posts which have been removed
	$old_value = get_field($field_name, $post_id, false);
	
	if ( is_array($old_value) ) {
		
		foreach( $old_value as $post_id2 ) {
			
			// bail early if this value has not been removed
			if( is_array($value) && in_array($post_id2, $value) ) continue;
			
			// load existing related posts
			$value2 = get_field($field_name, $post_id2, false);
			
			// bail early if no value
			if( empty($value2) ) continue;
			
			// find the position of $post_id within $value2 so we can remove it
			$pos = array_search($post_id, $value2);
			
			// remove
			unset( $value2[ $pos] );
			
			// update the un-selected post's value (use field's key for performance)
			update_field($field_key, $value2, $post_id2);
			
		}
		
	}
	
	// reset global varibale to allow this filter to function as per normal
	$GLOBALS[ $global_name ] = 0;	
	
	// return
    return $value;
    
}

// WIP!
function acf_update_related_field_on_save ( $post_id ) {	
	
	// TODO: figure out how to handle repeater field sub_fields -- e.g. repertoire_events << event program_items
	
	// Get newly saved values -- all fields
    //$values = get_fields( $post_id );

    // Check the current (updated) value of a specific field.
    $rows = get_field('program_items', $post_id);
    if ( $rows ) {
        foreach( $rows as $row ) {
        	if ( isset($row['program_item'][0]) ) {
        		foreach ( $row['program_item'] as $program_item_obj_id ) {
        			$item_post_type = get_post_type( $program_item_obj_id );
        			if ( $item_post_type == 'repertoire' ) {
        				$rep_related_events = get_field('related_events', $program_item_obj_id);
        				if ( $rep_related_events ) {
        					// Check to see if post_id is already saved to rep record
        				} else {
        					// No related_events set yet, so add the post_id
        					//update_field('related_events', $post_id, $program_item_obj_id );
        				}
        			}	
        		}
        	}
        }
    }
	
}

if ( in_array('events', $sdg_modules ) ) {
	add_filter('acf/update_value/name=events_series', 'bidirectional_acf_update_value', 10, 3);
}
if ( in_array('sermons', $sdg_modules ) ) {
	add_filter('acf/update_value/name=sermons_series', 'bidirectional_acf_update_value', 10, 3);
}

//
//add_filter('acf/update_value/name=related_compositions', 'bidirectional_acf_update_value', 10, 3);
//add_filter('acf/update_value/type=relationship', array($this, 'update_relationship_field'), 11, 3);
///

// function to copy data from old ACF one-way to new ACF bidirectional relationship fields
add_shortcode('convert_bidirectional_fields', 'convert_bidirectional_fields');
function convert_bidirectional_fields ( $post_id = null, $post_type = "", $old_field_name = "", $new_field_name = "", $verbose = false ) {
    
    $info = "";
    $ts_info = "";
    $new_vals = false;
    
    $info .= ">> convert_bidirectional_fields >><br />";
    $info .= "post_id: $post_id<br />";
    $info .= "post_type: $post_type<br />";
    $info .= "old_field_name: $old_field_name<br />";
    $info .= "new_field_name: $new_field_name<br />";
    
    // Get current ACF values, if any
    $arr_acf_values = get_field( $old_field_name, $post_id );
    if( !is_array($arr_acf_values) ) {
        $arr_acf_values = array();
    }
    if( !empty($arr_acf_values) ) { $info .= "[1] arr_acf_values: <pre>".print_r($arr_acf_values, true)."</pre>"; } else { $info .= "[1] arr_acf_values is empty.<br />"; }
    
}


?>