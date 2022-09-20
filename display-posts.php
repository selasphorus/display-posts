<?php
/*
Plugin Name: Birdhive Display Posts
Version: 0.1
Plugin URI: 
Description: Display posts of all types in a variety of formats using shortcodes
Author: Alison Cheeseman
Author URI: http://birdhive.com
Text Domain: display-posts
*/

/*********
Copyright (c) 2022, Alison Cheeseman/Birdhive Development & Design

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*********/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

$plugin_path = plugin_dir_path( __FILE__ );

/* +~+~+~+~+~+~+~+~+~+~+~+~+~+~+~+~+~+~+~+~+~+~+~+~+ */

// Facilitate search by str in post_title (as oppposed to built-in search by content or by post name, aka slug)
add_filter( 'posts_where', 'birdhive_posts_where', 10, 2 );
function birdhive_posts_where( $where, $wp_query ) {
    
    global $wpdb;
    
    if ( $search_term = $wp_query->get( '_search_title' ) ) {
        $search_term = $wpdb->esc_like( $search_term );
        $search_term = '\'%' . $search_term . '%\'';
        $where .= ' AND ' . $wpdb->posts . '.post_title LIKE ' . $search_term;
        //$where .= " AND " . $wpdb->posts . ".post_title LIKE '" . esc_sql( $wpdb->esc_like( $title ) ) . "%'";
    }
    
    // Get query vars
    $tax_args = isset( $wp_query->query_vars['tax_query'] ) ? $wp_query->query_vars['tax_query'] : null;
    $meta_args   = isset( $wp_query->query_vars['meta_query'] ) ? $wp_query->query_vars['meta_query'] : null;
    $meta_or_tax = isset( $wp_query->query_vars['_meta_or_tax'] ) ? wp_validate_boolean( $wp_query->query_vars['_meta_or_tax'] ) : false;

    // Construct the "tax OR meta" query
    if( $meta_or_tax && is_array( $tax_args ) && is_array( $meta_args )  ) {

        // Primary id column
        $field = 'ID';

        // Tax query
        $sql_tax  = get_tax_sql( $tax_args, $wpdb->posts, $field );

        // Meta query
        $sql_meta = get_meta_sql( $meta_args, 'post', $wpdb->posts, $field );

        // Modify the 'where' part
        if( isset( $sql_meta['where'] ) && isset( $sql_tax['where'] ) ) {
            $where  = str_replace( [ $sql_meta['where'], $sql_tax['where'] ], '', $where );
            $where .= sprintf( ' AND ( %s OR  %s ) ', substr( trim( $sql_meta['where'] ), 4 ), substr( trim( $sql_tax['where']  ), 4 ) );
        }
    }
    
    // Filter query results to enable searching per ACF repeater fields
    // See https://www.advancedcustomfields.com/resources/repeater/#faq "How is the data saved"
	// meta_keys for repeater fields are named according to the number of rows
	// ... e.g. item_0_description, item_1_description, so we need to adjust the search to use a wildcard for matching
	// Replace comparision operator "=" with "LIKE" and replace the wildcard placeholder "XYZ" with the actual wildcard character "%"
	$pattern = '/meta_key = \'([A-Za-z_]+)_XYZ/i';
	if ( preg_match("/meta_key = '[A-Za-z_]+_XYZ/i", $where) ) {
		$where = preg_replace($pattern, "meta_key LIKE '$1_%", $where); //$where = str_replace("meta_key = 'program_items_XYZ", "meta_key LIKE 'program_items_%", $where);
	}
    
    return $where;
}

function birdhive_get_posts ( $a = array() ) {
    
    global $wpdb;
    
    // Init vars
    $arr_posts_info = array();
    $info = "";
    $get_by_ids = false;
    $get_by_slugs = false;
    $category_link = null;
    
    $info .= "args as passed to birdhive_get_posts: <pre>".print_r($a,true)."</pre>";
    
    // Process the args as passed
    
    // Limit, aka posts_per_page, aka num posts to retrieve
    if ( isset($a['limit']) )       { 
        $posts_per_page = $a['limit']; 
    } else if ( isset($a['posts_per_page']) )       { 
        $posts_per_page = $a['posts_per_page']; 
    } else { 
        $posts_per_page = '-1';
    }
    
    // The search_title is a special placeholder field handled by the birdhive_posts_where fcn
    if ( isset($a['_search_title']) ){ $_search_title = $a['_search_title']; } else { $_search_title = null; }
    if ( isset($a['_meta_or_tax']) ){ $_meta_or_tax = $a['_meta_or_tax']; } else { $_meta_or_tax = null; }
    //
    if ( isset($a['post_type']) )   { $post_type = $a['post_type']; } else { $post_type = 'post'; }
    if ( isset($a['post_status']) ) { $post_status = $a['post_status']; } else { $post_status = array( 'publish', 'draft' ); }
    //
    if ( isset($a['order']) )       { $order = $a['order'];         } else { $order = null; }
    if ( isset($a['orderby']) )     { $orderby = $a['orderby'];     } else { $orderby = null; }
    //
    if ( isset($a['meta_query']) )  { $meta_query = $a['meta_query']; } else { $meta_query = array(); }
    if ( isset($a['tax_query']) )  	{ $tax_query = $a['tax_query']; } else { $tax_query = array(); }
    //
    if ( isset($a['return_fields']) )  { $return_fields = $a['return_fields']; } else { $return_fields = "all"; }
    
    // Set up basic query args
    $args = array(
		'post_type'       => $post_type,
		'post_status'     => $post_status,
		'posts_per_page'  => $posts_per_page,
        'fields'          => $return_fields,
	);
    
    // Custom parameters
    if ( $_search_title ) { $args['_search_title'] = $_search_title; }
    if ( $_meta_or_tax ) { $args['_meta_or_tax'] = $_meta_or_tax; }
    
    // Order (ASC/DESC)
    if ( $order ) { $args['order'] = $order; }
    
    // Posts by ID
    // NB: if IDs are specified, ignore most other args
    if ( isset($a['ids']) && !empty($a['ids']) ) {
        
        $info .= "Getting posts by IDs: ".$a['ids'];
        
        // Turn the list of IDs into a proper array
		$posts_in         = array_map( 'intval', birdhive_att_explode( $a['ids'] ) );
		$args['post__in'] = $posts_in;
        $args['orderby']  = 'post__in';
        $get_by_ids = true;
        
	}
    
    // Posts by slug
    // NB: if slugs are specified, ignore most other args
    if ( isset($a['slugs']) && !empty($a['slugs']) ) {
        
        $info .= "Getting posts by slugs: ".$a['slugs'];
        
        // Turn the list of slugs into a proper array
		$posts_in = birdhive_att_explode( $a['slugs'] );
		$args['post_name__in'] = $posts_in;
        $args['orderby'] = 'post_name__in';
        $get_by_slugs = true;
        
	}
    
    if ( !$get_by_ids && !$get_by_slugs ) {
        
        // TODO: simplify the setting of default values
        if ( isset($a['taxonomy']) )    { $taxonomy = $a['taxonomy'];   } else { $taxonomy = null; }
        if ( isset($a['tax_terms']) )   { $tax_terms = $a['tax_terms']; } else { $tax_terms = null; }
        if ( isset($a['category']) )    { $category = $a['category'];   } else { $category = null; }
		//
        if ( isset($a['meta_key']) )    { $meta_key = $a['meta_key'];   } else { $meta_key = null; }
        if ( isset($a['meta_value']) )  { $meta_value = $a['meta_value'];   } else { $meta_value = null; }
        
        // For Events & Sermons, if those post_types exist for the current application
        if ( isset($a['series']) )      { $series = $a['series'];       } else { $series = null; }
        
        // Deal w/ taxonomy args
        $tax_field = 'slug'; // init -- in some cases will want to use term_id
        if ( $category && empty($taxonomy) ) {
            $taxonomy = 'category';
            $tax_terms = $category;
        }
        $cat_id = null; // init

        // If not empty tax_terms and empty taxonomy, determine default taxonomy from post type
        if ( empty($taxonomy) && !empty($tax_terms) ) {
            $info .= "Using atc_get_default_taxonomy"; // tft
            $taxonomy = atc_get_default_taxonomy($post_type);
        }

        // Taxonomy operator
        if ( strpos($tax_terms,"NOT-") !== false ) {
            $tax_terms = str_replace("NOT-","",$tax_terms);
            $tax_operator = 'NOT IN';
        } else {
            $tax_operator = 'IN';
        }

        // Post default category, if applicable -- WIP
        if ( $post_type == 'post' && ( empty($taxonomy) || $taxonomy == 'category' ) && empty($tax_terms) ) {
            $category = atc_get_default_category();
            if ( !empty($category) ) {
                $tax_terms = $category;
                //$cat_id = get_cat_ID( $category );
                //$tax_terms = $cat_id;
                if ( empty($taxonomy) ) {
                    $taxonomy = 'category';
                }
            } else {
                $tax_terms = null;
            }
        }
        
        // If terms, check to see if array or string; build tax_query accordingly
        //if ( !empty($terms) ) { } // TBD
        
        // Orderby
        if ( isset($a['orderby']) ) {

            $standard_orderby_values = array( 'none', 'ID', 'author', 'title', 'name', 'type', 'date', 'modified', 'parent', 'rand', 'comment_count', 'relevance', 'menu_order', 'meta_value', 'meta_value_num', 'post__in', 'post_name__in', 'post_parent__in' );

            // determine if orderby is actually meta_value or meta_value_num with orderby $a value to be used as meta_key
            if ( !in_array( $a['orderby'], $standard_orderby_values) ) {
                // TODO: determine whether to sort meta values as numbers or as text
                if (strpos($a['orderby'], 'num') !== false) {
                    $args['orderby'] = 'meta_value_num'; // or meta_value?
                } else {
                    $args['orderby'] = 'meta_value';
                }
                $args['meta_key'] = $a['orderby'];
                
                /* //TODO: consider naming meta_query sub-clauses, as per the following example:
                $q = new WP_Query( array(
                    'meta_query' => array(
                        'relation' => 'AND',
                        'state_clause' => array(
                            'key' => 'state',
                            'value' => 'Wisconsin',
                        ),
                        'city_clause' => array(
                            'key' => 'city',
                            'compare' => 'EXISTS',
                        ), 
                    ),
                    'orderby' => array( 
                        'city_clause' => 'ASC',
                        'state_clause' => 'DESC',
                    ),
                ) );
                */
            } else {
                $args['orderby'] = $a['orderby'];
            }

        }
        
		
		if ( !empty($tax_query) ) {
			
			$args['tax_query'] = $tax_query;
			
		} else if ( is_category() ) {

            // Post category archive
            $info .= "is_category (archive)<br />";

            // Get archive cat_id
            // TODO: designate instead via CMS options?
            $archive_term = get_term_by('slug', 'website-archives', 'category');
            if ( !$archive_term ) {
            	$archive_term = get_term_by('slug', 'archives', 'category');
            }
            if ( $archive_term ) {
            	$archive_cat_id = $archive_term->term_id;
            } else {
            	$archive_cat_id = 99999; // tft
            }

            $tax_field = 'term_id';

            $args['tax_query'] = array(
                'relation' => 'AND',
                array(
                    'taxonomy' => 'category',
                    'field'    => $tax_field,
                    'terms'    => array( $tax_terms ),
                ),
                array(
                    'taxonomy' => 'category',
                    'field'    => 'term_id',
                    'terms'    => array( $archive_cat_id),
                    'operator' => 'NOT IN',
                ),
            );

        } else if ( $taxonomy && $tax_terms ) {

            $info .= "Building tax_query based on taxonomy & tax_terms.<br />";

            $args['tax_query'] = array(
                array(
                    'taxonomy'  => $taxonomy,
                    'field'     => $tax_field,
                    'terms'     => $tax_terms,
                    'operator'  => $tax_operator,
                )
            );

        }
        
        // Meta Query
		if ( empty($meta_query) ) {
			
			$meta_query_components = array();
        
			// Featured Image restrictions?
			// TODO: update this to account for custom_thumb and first_image options? Or is it no longer necessary at all?
			if ( isset($a['has_image']) && $a['has_image'] == true ) {
				$meta_query_components[] = 
					array(
						'key' => '_thumbnail_id',
						'compare' => 'EXISTS'
					);
			}

			// WIP/TODO: check to see if meta_query was set already via query args...
			//if ( !isset($a['meta_query']) )  {

			if ( ( $meta_key && $meta_value ) ) {

				$meta_query_components[] = 
					array(
						'key' => $meta_key,
						'value'   => $meta_value,
						'compare' => '=',
					);
			} else if ( ( $meta_key ) ) {

                // meta_key specified, but no value
				$meta_query_components[] = 
					array(
						'key' => $meta_key,
						//'value' => '' ,
                        'compare' => 'EXISTS',
					);
			}

			// Sermon series?
			if ( post_type_exists('sermon') && $post_type == 'sermon' && $series ) {

				$meta_query_components[] = 
					array(
						'key' => 'sermon_series',
						'value'   => $series,
						'compare' => '=',
					);
			}

			if ( count($meta_query_components) > 1 ) {
				$meta_query['relation'] = 'AND';
				foreach ( $meta_query_components AS $component ) {
					$meta_query[] = $component;
				}
			} else {
				$meta_query = $meta_query_components; //$meta_query = $meta_query_components[0];
			}
			
		}

        if ( !empty($meta_query) ) {
            $args['meta_query'] = $meta_query;
        }
        
        if ( $cat_id && ! is_category() ) { // is_archive()

            // Get the URL of this category
            $category_url = get_category_link( $cat_id );
            $category_link = 'Category Link';
            if ($category_url) { 
                $category_link = '<a href="'.$category_url.'"';
                if ($category === "Latest News") {
                    $category_link .= 'title="Latest News">All Latest News';
                } else {
                    $category_link .= 'title="'.$category.'">All '.$category.' Articles';
                }
                $category_link .= '</a>';
            }

        }
        
    } // END if ( !$get_by_ids )
    
    /*
    // TBD
    if ( isset($a['name']) ) {
		$args['name']     = $a['name'];
	}*/
    
    // -------
    // Run the query
    // -------
	$arr_posts = new WP_Query( $args );
    
    $info .= "WP_Query run as follows:";
    $info .= "<pre>args: ".print_r($args, true)."</pre>"; // tft
    //$info .= "<pre>meta_query: ".print_r($meta_query, true)."</pre>"; // tft
	//$info .= "<pre>arr_posts: ".print_r($arr_posts, true)."</pre>"; // tft
    //$info .= "<pre>".$arr_posts->request."</pre>"; // tft -- wip
    //$info .= "<!-- Last SQL-Query: ".$wpdb->last_query." -->";

    $info = '<div class="troubleshooting">'.$info.'</div>';
    
    $arr_posts_info['arr_posts'] = $arr_posts;
    $arr_posts_info['args'] = $args;
    $arr_posts_info['category_link'] = $category_link;
    $arr_posts_info['info'] = $info;
    
    return $arr_posts_info;
}


// Function for display of posts in various formats -- links, grid, &c.
// This shortcode is in use on numerous Pages, as well as via the archive.php page template
add_shortcode('display_posts', 'birdhive_display_posts');
function birdhive_display_posts ( $atts = [] ) {

    global $wpdb;
	$info = "";

	$a = shortcode_atts( array(
        
        'post_type' => 'post',
        'limit' => 5,
        'orderby' => 'title',
        'order' => 'ASC',
        'meta_key' => null,
        'meta_value' => null,
        //
        'ids' => null,
        'slugs' => null,
        'post_id' => null, // ?
        'name' => null,
        //
        'category' => null, // for posts, pages only
        'taxonomy'  => null,
        'tax_terms'  => null,
        //
        'return_format' => 'links', // or: 'excerpt' for single excerpt 'archive' for linked list as in search results/archives; OR 'grid' for "flex-grid"
        'cols' => 4,
        'spacing' => 'spaced',
        'header' => false,
        'overlay' => false,
        'has_image' => false, // set to true to ONLY return posts with features images
        'class' => null, // for additional styling
        'show_images' => false,
        
        // For post_type 'event'
        'scope' => 'upcoming',
        
        // For Events or Sermons
        'series' => false,
        
        // For table return_format
        'fields'  => null,
        'headers'  => null,
        
    ), $atts );
    
    if ( $a ) { $info .= '<div class="troubleshooting">shortcode_atts: <pre>'.print_r($a, true).'</pre></div>'; } // tft
    
    $post_type = $a['post_type'];
    $return_format = $a['return_format'];
    $class = $a['class'];
    $show_images = $a['show_images'];
    
    // For grid format:
    $num_cols = $a['cols'];
    $spacing = $a['spacing'];
    $header = $a['header'];
    $overlay = $a['overlay'];
    
    // For table format:
    $fields = $a['fields'];
    $headers = $a['headers'];
    
    // Clean up the array
    if ( $post_type !== "event" ) { unset($a["scope"]); }
    if ( $post_type !== "event" && $post_type !== "sermon" ) { unset($a["series"]); }
    if ( $return_format != "grid" ) { unset($a["cols"]); unset($a["spacing"]); unset($a["overlay"]); }
    
    // Make sure the return_format is valid
    // TODO: revive/fix "archive" option -- deal w/ get_template_part issue...
    if ( $return_format != "links" && $return_format != "grid" && $return_format != "excerpts" && $return_format != "table" ) {
        $return_format = "links"; // default
    }
    
    // Retrieve an array of posts matching the args supplied    
    if ( post_type_exists('event') && $post_type == 'event' ) {
    	// TODO: check to see if EM plugin is installed and active?
        // TODO: deal w/ taxonomy parameters -- how to translate these properly for EM?
        $posts = EM_Events::get( $a ); // Retrieves an array of EM_Event Objects
        //$info .= '<div class="troubleshooting">Posts retrieved using EM_Events::get: <pre>'.print_r($posts, true).'</pre></div>'; // tft
        $info .= '<div class="troubleshooting">Posts retrieved using EM_Events::get: <pre>';
        foreach ( $posts as $post ) {
            //$info .= "post: ".print_r($post, true)."<br />";
            $info .= "post_id: ".$post->post_id."<br />";
            //$info .= "event_attributes: ".print_r($post->event_attributes, true)."<br />";
            $info .= "event_series: ".$post->event_attributes['event_series']."<br />";
            //
        }
        //$info .= 'last_query: '.print_r( $wpdb->last_query, true); // '<pre></pre>'
        $info .= '</pre></div>'; // tft
    } else {
        $posts_info = birdhive_get_posts( $a );
        $posts = $posts_info['arr_posts']->posts; // Retrieves an array of WP_Post Objects
        $info .= $posts_info['info'];
    }
    
    //if ( $posts ) { $info .= '<div class="troubleshooting"><pre>'.print_r($posts, true).'</pre></div>'; } // tft
    
	if ( $posts ) {
        
		//if ($a['header'] == 'true') { $info .= '<h3>Latest '.$category.' Articles:</h3>'; } // WIP
		
        if ( $return_format == "links" ) {
            $info .= '<ul>';
        } else if ( $return_format == "excerpts" || $return_format == "archive" ) {
            $info .= '<div class="posts_archive">';
        } else if ( $return_format == "table" ) {
            
            $info .= '<table class="posts_archive">'; //$info .= '<table class="posts_archive '.$class.'">';
            // Make header row from field names
            if ( !empty($fields) ) {
                
                $info .= "<tr>"; // prep the header row
                
                // make array from fields string
                $arr_fields = explode(",",$fields);
                //$info .= "<td>".$fields."</td>"; // tft
                //$info .= "<td><pre>".print_r($arr_fields, true)."</pre></td>"; // tft
                
                if ( !empty($headers) ) {
                    $arr_headers = explode(",",$headers);
                    
                    foreach ( $arr_headers as $header ) {
                        $header = trim($header);
                        if ( $header == "-" ) { $header = ""; }
                        $info .= "<th>".$header."</th>";
                    }
                    
                } else {
                    
                    // If no headers were submitted, make do with the field names
                    foreach ( $arr_fields as $field_name ) {
                        $field_name = ucfirst(trim($field_name));
                        $info .= "<th>".$field_name."</th>";
                    }
                    
                }
                
                $info .= "</tr>"; // close out the header row
            }
            
        } else if ( $return_format == "grid" ) {
            $colclass = digit_to_word($num_cols)."col";
            if ( $class ) { $colclass .= " ".$class; }
            $info .= '<div class="flex-container '.$colclass.'">';
        }
        
        foreach ( $posts as $post ) {
            
            //$info .= '<pre>'.print_r($post, true).'</pre>'; // tft
            //$info .= '<div class="troubleshooting">post: <pre>'.print_r($post, true).'</pre></div>'; // tft
            
            if ( post_type_exists('event') && $post_type == 'event' ) {
                $post_id = $post->post_id;
                $info .= '<!-- Event post_id: '.$post_id." -->"; // tft
            } else {
                $post_id = $post->ID;
                $info .= '<!-- $post_type post_id: '.$post_id." -->"; // tft
            }
            
            // If a short_title is set, use it. If not, use the post_title
            $short_title = get_post_meta( $post_id, 'short_title', true );
            if ( $short_title ) { $post_title = $short_title; } else { $post_title = get_the_title($post_id); }
            
            if ( $return_format == "links" ) {
                
                $info .= '<li>';
                $info .= '<a href="'.get_the_permalink( $post_id ).'" rel="bookmark">'.$post_title.'</a>';
                $info .= '</li>';
                
            } else if ( $return_format == "excerpts" || $return_format == "archive" ) {
                
                // TODO: bring this more in alignment with theme template display? e.g. content-excerpt, content-sermon, content-event...
                $info .= '<!-- wpt/adapted: content-excerpt -->';
                $info .= '<article id="post-'.$post_id.'">'; // post_class()
                $info .= '<header class="entry-header">';
                $info .= '<h2 class="entry-title"><a href="'.get_the_permalink( $post_id ).'" rel="bookmark">'.$post_title.'</a></h2>';
                $info .= '</header><!-- .entry-header -->';
                $info .= '<div class="entry-content">';
                //$info .= birdhive_post_thumbnail($post_id);
                if ( $show_images ) {
                    $info .= birdhive_post_thumbnail($post_id,'thumbnail',false,false); // function birdhive_post_thumbnail( $post_id = null, $imgsize = "thumbnail", $use_custom_thumb = false, $echo = true )
                }
                $info .= get_the_excerpt( $post_id );
                $info .= '</div><!-- .entry-content -->';
                $info .= '<footer class="entry-footer">';
                $info .= twentysixteen_entry_meta( $post_id );
                $info .= '</footer><!-- .entry-footer -->';
                $info .= '</article><!-- #post-'.$post_id.' -->';

                //$info .= get_template_part( 'template-parts/content', 'excerpt', array('post_id' => $post_id ) ); // 
                //$post_type_for_template = atc_get_type_for_template();
                //get_template_part( 'template-parts/content', $post_type_for_template );
                //$info .= get_template_part( 'template-parts/content', $post_type );
                
            } else if ( $return_format == "table" ) {
                
                $info .= '<tr>';
                
                if ( !empty($arr_fields) ) { 
                    
                    foreach ( $arr_fields as $field_name ) {
                        $field_name = trim($field_name);
                        if ( !empty($field_name) ) {
                            
                            $info .= '<td>';
                            if ( $field_name == "title" ) {
                                $field_value = '<a href="'.get_the_permalink( $post_id ).'" rel="bookmark">'.$post_title.'</a>';
                            } else {
                                $field_value = get_post_meta( $post_id, $field_name, true );
                                //$info .= "[".$field_name."] "; // tft
                            }
                            
                            if ( is_array($field_value) ) {
                                
                                if ( count($field_value) == 1 ) { // If t
                                    if ( is_numeric($field_value[0]) ) {
                                        // Get post_title
                                        $field_value = get_the_title($field_value[0]);
                                        $info .= $field_value;
                                    } else {
                                        $info .= "Not is_numeric: ".$field_value[0];
                                    }
                                    
                                } else {
                                    $info .= count($field_value).": <pre>".print_r($field_value, true)."</pre>";
                                }
                                
                            } else {
                                $info .= $field_value;
                            }
                            
                            $info .= '</td>';
                        }
                    }
                    
                }
                
                $info .= '</tr>';
                
            } else if ( $return_format == "grid" ) {
                
                $post_info = "";
                $grid_img = "";
                $featured_img_url = "/wp-content/uploads/woocommerce-placeholder-250x250.png"; // Default/placeholder
                
                // Get a featured image for display in the grid
                
                // First, check to see if the post has a Custom Thumbnail
                $custom_thumb_id = get_post_meta( $post_id, 'custom_thumb', true );
                
                if ( $custom_thumb_id ) {
                    
                    $featured_img_url = wp_get_attachment_image_url( $custom_thumb_id, 'medium' ); 
                    //$grid_img = wp_get_attachment_image( $custom_thumb_id, 'medium', false, array( "class" => "custom_thumb" ) );
                    //$post_info .= "custom_thumb_id: $custom_thumb_id<br />"; // tft
                    
                } else {
                    
                    // No custom_thumb? Then retrieve the url for the full size featured image, if any
                    if ( has_post_thumbnail( $post_id ) ) {
                        
                        $featured_img_url = get_the_post_thumbnail_url( $post_id, 'medium');
                        
                    } else { 
                        
                        // If there's no featured image, look for an image in the post content
                        
                        $first_image = get_first_image_from_post_content( $post_id );
                        if ( $first_image && !empty($first_image['id']) ) {
                            
                            $first_img_src = wp_get_attachment_image_src( $first_image['id'], 'full' );
                            
                            // If the image found is large enough, display it in the grid
                            if ( $first_img_src[1] > 300 && $first_img_src[2] > 300 ) {
                                $featured_img_url = wp_get_attachment_image_url( $first_image['id'], 'medium' );
                            }
                        }
                        
                    }
                    
                }
                
                $grid_img = '<img src="'.$featured_img_url.'" alt="'.get_the_title($post_id).'" width="100%" height="100%" />';
                
                $post_info .= '<a href="'.get_the_permalink($post_id).'" rel="bookmark">';
                $post_info .= '<span class="post_title">'.$post_title.'</span>';
                // For events, also display the date/time
                if ( post_type_exists('event') && 'event' ) { 
                    $event_start_datetime = get_post_meta( $post_id, '_event_start_local', true );
                    //$event_start_time = get_post_meta( $post_id, '_event_start_date', true );
                    if ( $event_start_datetime ) {
                        //$post_info .= "[".$event_start_datetime."]"; // tft
                        $date_str = date_i18n( "l, F d, Y \@ g:i a", strtotime($event_start_datetime) );
                        $post_info .= "<br />".$date_str;
                    }
                }
                //
                $post_info .= '</a>';
                
                $info .= '<div class="flex-box '.$spacing.'">';
                //$info .= 'test: '.$featured_img_url; // tft
                $info .= '<div class="flex-img">';
                $info .= '<a href="'.get_the_permalink($post_id).'" rel="bookmark">';
                $info .= $grid_img;
                $info .= '</a>';
                $info .= '</div>';
                if ( $overlay == true ) {
                    $info .= '<div class="overlay">'.$post_info.'</div>';
                } else {
                    $info .= '<div class="post_info">'.$post_info.'</div>';
                }
                $info .= '</div>';
                
            } else {
                
                $the_content = apply_filters('the_content', get_the_content($post_id));
                $info .= $the_content;
                //$info .= the_content();
                
            }
            
        }
        
        if ( $return_format == "links" ) {
            //if ( ! is_archive() && ! is_category() ) { $info .= '<li>'.$category_link.'</li>'; }
            $info .= '</ul>';
        } else if ( $return_format == "excerpts" || $return_format == "archive" ) {
            $info .= '</div>';
        } else if ( $return_format == "table" ) {
            $info .= '</table>';
        } else if ( $return_format == "grid" ) {
            $info .= '</div>';
        }
		
        wp_reset_postdata();
    
    } // END if posts
    
    return $info;
    
}

function birdhive_post_thumbnail( $imgsize = "thumbnail", $use_custom_thumb = false ) {
    
    $post_id = get_the_ID();
    $thumbnail_id = null; // init
    
    if ( is_singular() ) {
        $imgsize = "full";
    }
    
    $troubleshooting_info = "";
    $troubleshooting_info .= "post_id: $post_id<br />";
    $troubleshooting_info .= "imgsize: $imgsize<br />";
    
    // Are we using the custom image, if any is set?
    if ( $use_custom_thumb == true ) {    
        // First, check to see if the post has a Custom Thumbnail
        $custom_thumb_id = get_post_meta( $post_id, 'custom_thumb', true );
        
        if ( $custom_thumb_id ) {
            $thumbnail_id = $custom_thumb_id;
        }
    }

    // If we're not using the custom thumb, or if none was found, then proceed to look for other image options for the post
    if ( !$thumbnail_id ) {
        
        // Check to see if the given post has a featured image
        if ( has_post_thumbnail() ) {

            $thumbnail_id = get_post_thumbnail_id();
            $troubleshooting_info .= "post has a featured image.<br />";

        } else {

            $troubleshooting_info .= "post has NO featured image.<br />";

            // If there's no featured image, see if there are any other images that we can use instead
            $image_info = get_first_image_from_post_content( $post_id );
            if ( $image_info ) {
                $thumbnail_id = $image_info['id'];
            } else {
                $thumbnail_id = "test"; // tft
            }

            if ( empty($thumbnail_id) ) {

                // The following approach would be a good default except that images only seem to count as 'attached' if they were directly UPLOADED to the post
                // Also, images uploaded to a post remain "attached" according to the Media Library even after they're deleted from the post.
                $images = get_attached_media( 'image', $post_id );
                //$images = get_children( "post_parent=".$post_id."&post_type=attachment&post_mime_type=image&numberposts=1" );
                if ($images) {
                    //$thumbnail_id = $images[0];
                    foreach ($images as $attachment_id => $attachment) {
                        $thumbnail_id = $attachment_id;
                    }
                }

            }

            // If there's STILL no image, use a placeholder
            // TODO: make it possible to designate placeholder image(s) for archives via CMS and retrieve it using new version of get_placeholder_img fcn
            // TODO: designate placeholders *per category*?? via category/taxonomy ui?
            if ( empty($thumbnail_id) ) {
                //$thumbnail_id = null;
            }
        }
    }
    
    // Make sure this is a proper context for display of the featured image
    
    if ( post_password_required() || is_attachment() ) {
        
        return;
        
    } else if ( has_term( 'video-webcasts', 'event-categories' ) && is_singular('event') ) {
        
        // featured images for events are handled via Events > Settings > Formatting AND via calendar.php (#_EVENTIMAGE)
        return;
        
    } else if ( has_term( 'video-webcasts', 'category' ) ) {
        
        $player_status = get_media_player( $post_id, true ); // get_media_player ( $post_id = null, $status_only = false, $url = null )
        if ( $player_status == "ready" ) {
            return;
        }
        
    } else if ( is_page_template('page-centered.php') ) {
        
		return;
        
	} else if ( is_singular() && in_array( get_field('featured_image_display'), array( "background", "thumbnail", "banner" ) ) ) {
        
        return;
        
    }

    $troubleshooting_info .= "Ok to display the image!<br />";
    
    // Ok to display the image! Set up classes for styling
    $classes = "post-thumbnail dp";
    
    // Retrieve the caption (if any) and return it for display
    $caption = get_post( $thumbnail_id  )->post_excerpt;
    if ( !empty($caption) ) {
        $classes .= " has_caption";
    }
    
    if ( is_singular() ) {
        
        if ( has_post_thumbnail() ) {
            
            if ( is_singular('person') ) {
                $imgsize = "medium"; // portrait
                $classes .= " float-left";
            }
            
            $classes .= " is_singular";
            ?>

            <div class="<?php echo $classes; ?>">
                <?php the_post_thumbnail( $imgsize ); ?>
            </div><!-- .post-thumbnail -->

        <?php 
        }
        
    } else { 
        
        // NOT singular -- aka archives, search results, &c.
        
        $classes .= " float-left";
        //$classes .= " NOT_is_singular"; // tft
        ?>

        <a class="<?php echo $classes; ?>" href="<?php the_permalink(); ?>" aria-hidden="true">
        <?php 
        if ( $thumbnail_id ) {
            
            // display attachment via thumbnail_id
            echo wp_get_attachment_image( $thumbnail_id, $imgsize, false, array( "class" => "featured_attachment" ) );
            
            $troubleshooting_info .= 'post_id: '.$post_id.'; thumbnail_id: '.$thumbnail_id;
            if ( isset($images)) { $troubleshooting_info .= '<pre>'.print_r($images,true).'</pre>'; }
            
        } /*else if ( has_post_thumbnail() ) {
            
            the_post_thumbnail( $imgsize, array( 'alt' => the_title_attribute( 'echo=0' ) ) );
            
        } */else {
            
            $troubleshooting_info .= 'Use placeholder img';
            
            if ( function_exists( 'get_placeholder_img' ) ) { echo get_placeholder_img(); }
        }
        ?>
        </a>

        <?php
    } // End if is_singular()
    
    echo '<div class="troubleshooting">'.$troubleshooting_info.'</div>'; // tft

}

?>