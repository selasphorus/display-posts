<?php
/**
 * @package Display_Posts
 * @version 0.1
 */

/*
Plugin Name: Birdhive Display Posts
Version: 0.1
Plugin URI: 
Description: Display posts of all types in a variety of formats using shortcodes.
Author: Alison C.
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

// Enqueue scripts and styles -- WIP
add_action( 'wp_enqueue_scripts', 'dp_scripts_method' );
function dp_scripts_method() {
    
    $ver = "0.1";
    wp_enqueue_style( 'dp-style', plugin_dir_url( __FILE__ ) . 'dp.css', NULL, $ver );
    
    wp_register_script('dp-js', plugin_dir_url( __FILE__ ) . 'js/dp.js', array( 'jquery' ) );
	wp_enqueue_script('dp-js');	

}

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


/*** MISC ***/

/**
 * Explode list using "," and ", ".
 *
 * @param string $string String to split up.
 * @return array Array of string parts.
 */
function birdhive_att_explode( $string = '' ) {
	$string = str_replace( ', ', ',', $string );
	return explode( ',', $string );
}

function digit_to_word( $number ){
    switch($number){
        case 0:$word = "zero";break;
        case 1:$word = "one";break;
        case 2:$word = "two";break;
        case 3:$word = "three";break;
        case 4:$word = "four";break;
        case 5:$word = "five";break;
        case 6:$word = "six";break;
        case 7:$word = "seven";break;
        case 8:$word = "eight";break;
        case 9:$word = "nine";break;
    }
    return $word;
}

// Hide everything within and including the square brackets
// e.g. for titles matching the pattern "{Whatever} [xxx]" or "[xxx] {Whatever}"
/*if ( !function_exists( 'remove_bracketed_info' ) ) {
    function remove_bracketed_info ( $str ) {

        if (strpos($str, '[') !== false) { 
            $str = preg_replace('/\[[^\]]*\]([^\]]*)/', trim('$1'), $str);
            $str = preg_replace('/([^\]]*)\[[^\]]*\]/', trim('$1'), $str);
        }

        return $str;
    }
}*/

/*** IMAGE FUNCTIONS ***/

// Extract first image from post content
function get_first_image_from_post_content( $post_id ) {
    
    $post = get_post( $post_id );
    
    // init
    $info = array();
    $first_image = null;
    $first_image_id = null;
    $first_image_url = null;
    
    //ob_start();
    //ob_end_clean();
    
    $output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $post->post_content, $matches);
    //$output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $post->post_content, $matches);
    //echo "Matches for post_id: $post_id => <pre>".print_r($matches, true)."</pre>"; // tft
    
    //if ( !empty($matches) ) { 
    if ( isset($matches[1][0]) ) {
        $first_image = $matches[0][0];
        $first_image_url = $matches[1][0];
    } else {
        //echo "Matches for post_id: $post_id => <pre>".print_r($matches, true)."</pre>"; // tft
    }

    /*if ( empty($first_image) ){ // Defines a default placeholder image
        // Set the default image if there are no image available inside post content
        if ( function_exists( 'get_placeholder_img' ) ) { $first_image = get_placeholder_img(); }
        //$first_image = "/img/default.jpg";
    }*/
    
    //return $first_image;
    
    if ( !empty($first_image) ) {
        
        if ( preg_match('/(wp-image-)([0-9]+)/', $first_image, $matches) ) {
            //echo print_r($matches, true); // tft
            $first_image_id = $matches[2];
        }
    }
    
    $info['img'] = $first_image;
    $info['id'] = $first_image_id;
    $info['url'] = $first_image_url;
    
    //return $first_image_id;
    return $info;
    
}

function birdhive_post_thumbnail( $post_id = null, $imgsize = "thumbnail", $use_custom_thumb = false, $echo = true ) {
    
    $info = ""; // init
    
    /*
    // Defaults
	$defaults = array(
		'post_id'         => null,
		'preview_length'  => 55,
		'readmore'        => false,
	);
	
    // Parse args
	$args = wp_parse_args( $args, $defaults );

	// Extract
	extract( $args );
	*/
    
    if ( $post_id === null ) {
        $post_id = get_the_ID();
    }
    $thumbnail_id = null; // init
    
    if ( is_singular($post_id) ) {
        $imgsize = "full";
    }
    
    $troubleshooting = "";
    $troubleshooting .= "post_id: $post_id<br />";
    $troubleshooting .= "imgsize: $imgsize<br />";
    
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
        if ( has_post_thumbnail( $post_id ) ) {

            $thumbnail_id = get_post_thumbnail_id( $post_id );
            $troubleshooting .= "post has a featured image.<br />";

        } else {

            $troubleshooting .= "post has NO featured image.<br />";

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
    
    if ( post_password_required($post_id) || is_attachment($post_id) ) {
        
        return;
        
    } else if ( has_term( 'video-webcasts', 'event-categories' ) && is_singular('event') ) {
        
        // featured images for events are handled via Events > Settings > Formatting AND via events.php (?) (#_EVENTIMAGE)
        return;
        
    } else if ( has_term( 'video-webcasts', 'category' ) ) {
        
        $player_status = get_media_player( $post_id, true ); // get_media_player ( $post_id = null, $status_only = false, $url = null )
        if ( $player_status == "ready" ) {
            return;
        }
        
    } else if ( is_page_template('page-centered.php') ) {
        
		return;
        
	} else if ( is_singular() && in_array( get_field('featured_image_display'), array( "background", "thumbnail", "banner" ) ) ) {
        
        //return; // wip
        
    }

    $troubleshooting .= "Ok to display the image!<br />";
    
    // Ok to display the image! Set up classes for styling
    $classes = "post-thumbnail dp";
    
    // Retrieve the caption (if any) and return it for display
    if ( get_post( $thumbnail_id  ) ) {
        $caption = get_post( $thumbnail_id  )->post_excerpt;
        if ( !empty($caption) ) {
            $classes .= " has_caption";
        }
    }
    
    if ( is_singular($post_id) ) {
        
        if ( has_post_thumbnail($post_id) ) {
            
            if ( is_singular('person') ) {
                $imgsize = "medium"; // portrait
                $classes .= " float-left";
            }
            
            $classes .= " is_singular";
            
            $info .= '<div class="'.$classes.'">';
            $info .= get_the_post_thumbnail( $imgsize ); //the_post_thumbnail( $imgsize );
            $info .= '</div><!-- .post-thumbnail -->';
            
        }
        
    } else { 
        
        // NOT singular -- aka archives, search results, &c.
        
        $classes .= " float-left";
        //$classes .= " NOT_is_singular"; // tft
        
        if ( $thumbnail_id ) {
        
            $info .= '<a class="'.$classes.'" href="'.get_the_permalink($post_id).'" aria-hidden="true">';
            
            // display attachment via thumbnail_id
            $info .= wp_get_attachment_image( $thumbnail_id, $imgsize, false, array( "class" => "featured_attachment" ) );
            
            $troubleshooting .= 'post_id: '.$post_id.'; thumbnail_id: '.$thumbnail_id;
            if ( isset($images)) { $troubleshooting .= '<pre>'.print_r($images,true).'</pre>'; }
        
            $info .= '</a>';
            
        } else {
            
            $troubleshooting .= 'Use placeholder img';
            
            if ( function_exists( 'get_placeholder_img' ) ) { 
                $img = get_placeholder_img();
                if ( $img ) {
                    $info .= '<a class="'.$classes.'" href="'.get_the_permalink($post_id).'" aria-hidden="true">';
                    $info .= $img;
                    $info .= '</a>';
                }
            }
        }
        
    } // End if is_singular()
    
    $info .= '<div class="troubleshooting">'.$troubleshooting.'</div>';
    if ( $echo === true ) {
        echo $info;
        return true;
    } else {
        return $info;
    }    

}


/*** EXCERPTS AND ENTRY META ***/


// Allow select HTML tags in excerpts
// https://wordpress.stackexchange.com/questions/141125/allow-html-in-excerpt
function dp_allowedtags() {
    return '<style>,<br>,<em>,<strong>'; 
}

if ( ! function_exists( 'dp_custom_wp_trim_excerpt' ) ) : 

    function dp_custom_wp_trim_excerpt($excerpt) {
        
        global $post;
        
        $raw_excerpt = $excerpt;
        if ( '' == $excerpt ) {

            $excerpt = get_the_content('');
            $excerpt = strip_shortcodes( $excerpt );
            $excerpt = apply_filters('the_content', $excerpt);
            $excerpt = str_replace(']]>', ']]&gt;', $excerpt);
            $excerpt = strip_tags($excerpt, dp_allowedtags()); // IF you need to allow just certain tags. Delete if all tags are allowed

            //Set the excerpt word count and only break after sentence is complete.
            $excerpt_word_count = 75;
            $excerpt_length = apply_filters('excerpt_length', $excerpt_word_count); 
            $tokens = array();
            $excerptOutput = '';
            $count = 0;

            // Divide the string into tokens; HTML tags, or words, followed by any whitespace
            preg_match_all('/(<[^>]+>|[^<>\s]+)\s*/u', $excerpt, $tokens);

            foreach ($tokens[0] as $token) { 

                if ($count >= $excerpt_length && preg_match('/[\,\;\?\.\!]\s*$/uS', $token)) { 
                // Limit reached, continue until , ; ? . or ! occur at the end
                    $excerptOutput .= trim($token);
                    break;
                }

                // Add words to complete sentence
                $count++;

                // Append what's left of the token
                $excerptOutput .= $token;
            }

            $excerpt = trim(force_balance_tags($excerptOutput));
            
            // After the content
            $excerpt .= atc_excerpt_more( '' );

            return $excerpt;   

        } else if ( has_excerpt( $post->ID ) ) {
            //$excerpt .= atc_excerpt_more( '' );
            //$excerpt .= "***";
        }
        return apply_filters('dp_custom_wp_trim_excerpt', $dp_excerpt, $raw_excerpt);
    }

endif; 

// Replace trim_excerpt function -- temp disabled for troubleshooting
//remove_filter('get_the_excerpt', 'wp_trim_excerpt');
//add_filter('get_the_excerpt', 'dp_custom_wp_trim_excerpt'); 

/* Function to allow for multiple different excerpt lengths as needed
 * Call as follows:
 * Adapted from https://www.wpexplorer.com/custom-excerpt-lengths-wordpress/
 *
 */

//if ( function_exists('is_dev_site') && is_dev_site() ) {
function dp_get_excerpt( $args = array() ) {
	
	$info = ""; // init
	$text = "";
	
	//$info .= "args: <pre>".print_r($args, true)."</pre>";
	
	// Defaults
	$defaults = array(
		'post'            => '',
		'post_id'         => null,
		'preview_length'  => 55, // num words to display as preview text
		'readmore'        => false,
		'readmore_text'   => esc_html__( 'Read more...', 'dp' ),
		'readmore_after'  => '',
		'custom_excerpts' => true,
		'disable_more'    => false,
		'expandable'    => false,
		'text_length'    => 'excerpt',
	);

	// Apply filters
	//$defaults = apply_filters( 'dp_get_excerpt_defaults', $defaults );

	// Parse args
	$args = wp_parse_args( $args, $defaults );
	
	//$info .= "args: <pre>".print_r($args, true)."</pre>";

	// Apply filters to args
	//$args = apply_filters( 'dp_get_excerpt_args', $defaults );

	// Extract
	extract( $args );

	if ( $post_id ) {	
		$post = get_post( $post_id );		
	} else {	
		// Get global post data
		if ( ! $post ) {
			global $post;
		}
		// Get post ID
		$post_id = $post->ID;		
	}
	
	// Set up the "Read more" link
	$readmore_link = '&nbsp;<a href="' . get_permalink( $post_id ) . '" class="readmore"><em>' . $readmore_text . $readmore_after . '</em></a>'; // todo -- get rid of em, use css
	 
	// Get the text, based on args
	if ( $text_length == "full" ) {
		// Full post content
		$text = $post->post_content;
	} else if ( $custom_excerpts && has_excerpt( $post_id ) ) {
		// Check for custom excerpt
		$text = $post->post_excerpt;
	} else if ( ! $disable_more && strpos( $post->post_content, '<!--more-->' ) ) {
		// Check for "more" tag and return content, if it exists
		$text = apply_filters( 'the_content', get_the_content( $readmore_text . $readmore_after ) );
	} else {	
		// No "more" tag defined, so generate excerpt using wp_trim_words
		$text = wp_trim_words( strip_shortcodes( $post->post_content ), $preview_length );
	}
	
	if ( $expandable ) {
		$info .= expandable_text( $text, $post_id, $text_length, $preview_length );
	} else {
		$info .= $text;
	}		
	
	// Add readmore to excerpt if enabled
	if ( $readmore ) {
		$info .= apply_filters( 'dp_readmore_link', $readmore_link );
	}

	// Apply filters and echo
	//return apply_filters( 'dp_get_excerpt', $info );
	return $info;

}

// WIP
// see https://developer.wordpress.org/reference/functions/get_the_excerpt/
// TODO: pare down number of args -- simplify
//function expandable_text( $args = array() ) {
function expandable_text( $post_id = null, $text_length = "excerpt", $preview_length = 55 ) {
//function expandable_text( $text = null, $post_id = null, $text_length = "excerpt", $preview_length = 55 ) { //function expandable_excerpt($excerpt) // $args = array() 
	
	/*
    // Defaults
	$defaults = array(
		'post_id'         => null,
		'preview_length'  => 55,
		'readmore'        => false,
	);
	
    // Parse args
	$args = wp_parse_args( $args, $defaults );

	// Extract
	extract( $args );
	*/
	
	$output = "";
	
	//if ( empty($text) ) {
		if ( empty($post_id) ) { 
			return false;
		} else {
			$post = get_post( $post_id );
			if ( has_excerpt( $post_id ) ) { 
				$preview_text = $post->post_excerpt; // ??
			} else {
				$preview_text = get_the_excerpt($post_id);
			}
			$full_text = $post->post_content;
		}
	//}
	
	// TODO fix the following in terms of handling html tags within the text
	//$stripped_text = wp_strip_all_tags($text);
	$split = explode(" ", $preview_text); // convert string to array
	$len = count($split); // get number of words in text
	
	//$output .= "<pre>".print_r($split, true)."</pre>";
	
	if ( $len > $preview_length ) { // Is the excerpt-as-preview_text longer than the set preview length?

		$firsthalf = array_slice($split, 0, $preview_length);
		$secondhalf = array_slice($split, $preview_length, $len - 1);
		
		$output .= '<p class="expandable-text" >';
		$output .= implode(' ', $firsthalf) . '<span class="extxt spacer">&nbsp;</span><span class="extxt more-text readmore">more</span>';
		$output .= '<span class="extxt text-full hide">';
		$output .= ' ' . implode(' ', $secondhalf);
		$output .= '</span>';
		$output .= '<span class="extxt spacer hide">&nbsp;</span><span class="extxt less-text readmore hide">less</span>';
		$output .= '</p>';
		
	} else {
	
		//$output = '<p class="extxt expandable-text">'.$text.'</p>';
		$output .= '<p class="expandable-text" >';
		$output .= '<span class="extxt text-preview" >';
		$output .= $preview_text;
		$output .= '</span>';
		$output .= '<span class="extxt spacer">&nbsp;</span><span class="extxt more-text readmore">more</span>';
		$output .= '<span class="extxt text-full hide">';
		$output .= $full_text;
		$output .= '</span>';
		$output .= '<span class="extxt spacer hide">&nbsp;</span><span class="extxt less-text readmore hide">less</span>';
		$output .= '</p>';
	
	}
	
	return $output;
}

//}

/**
 * Prints HTML with meta information for the categories, tags.
 * This function is a version of twentysixteen_entry_meta
 */
function birdhive_entry_meta() {
	
    $format = get_post_format();
    if ( current_theme_supports( 'post-formats', $format ) ) {
        printf(
            '<span class="entry-format">%1$s<a href="%2$s">%3$s</a></span>',
            sprintf( '<span class="screen-reader-text">%s </span>', _x( 'Format', 'Used before post format.', 'twentysixteen' ) ),
            esc_url( get_post_format_link( $format ) ),
            get_post_format_string( $format )
        );
    }

    if ( 'post' === get_post_type() ) {
        //birdhive_entry_taxonomies(); // tmp disabled until fcn has been created based on twentysixteen version
    }

}


/*** TAXONOMY-RELATED FUNCTIONS ***/

// Function to determine default taxonomy for a given post_type, for use with display_posts shortcode, &c.
function birdhive_get_default_taxonomy ( $post_type = null ) {
    switch ($post_type) {
        case "post":
            return "category";
        case "page":
            return "page_tag"; // ??
        case "event":
            return "event-categories";
        case "product":
            return "product_cat";
        case "repertoire":
            return "repertoire_category";
        case "person":
            return "people_category";
        case "sermon":
            return "sermon_topic";
        default:
            return "category"; // default -- applies to type 'post'
    }
}

// Function to determine default category for given page, for purposes of Recent Posts &c.
function birdhive_get_default_category () {
	
	$default_cat = "";
	
    if ( is_category() ) {
        $category = get_queried_object();
        $default_cat = $category->name;
    } else if ( is_single() ) {
        $categories = get_the_category();
        $post_id = get_the_ID();
        $parent_id = wp_get_post_parent_id( $post_id );
        //$parent = $post->post_parent;
    }
	
	if ( ! empty( $categories ) ) {
		//echo esc_html( $categories[0]->name );		 
	} else if ( empty($default_cat) ) {
        
		// TODO: make this more efficient by simply checking to see if name of Page is same as name of any Category
		//echo "No categories.<br />";
		if ( is_page('Families') ) {
			$default_cat = "Families";
		} else if (is_page('Giving')) {
			$default_cat = "Giving";
		} else if (is_page('Music')) {
			$default_cat = "Music";
		} else if (is_page('Outreach')) {
			$default_cat = "Outreach";
		} else if (is_page('Parish Life')) {
			$default_cat = "Parish Life";
		} else if (is_page('Rector')) {
			$default_cat = "Rector";
		} else if (is_page('Theology')) {
			$default_cat = "Theology";
		} else if (is_page('Worship')) {
			$default_cat = "Worship";
		} else if (is_page('Youth')) {
			$default_cat = "Youth";
		} else {
			$default_cat = "Latest News";
		}
	}
	//$info .= "default_cat: $default_cat<br />";
	//echo "default_cat: $default_cat<br />";
	
	return $default_cat;
}


/*** RETRIEVE & DISPLAY POSTS with complex queries &c. ***/

function birdhive_get_posts ( $a = array() ) {
//function birdhive_get_posts ( $args = array() ) {
    
    global $wpdb;
    
    /*
    // Defaults
	$defaults = array(
		'post_id'         => null,
		'preview_length'  => 55,
		'readmore'        => false,
	);
	
    // Parse args
	$args = wp_parse_args( $args, $defaults );

	// Extract
	extract( $args );
	*/
    
    // Init vars
    $arr_posts_info = array();
    $info = "";
    $troubleshooting = "";
    $get_by_ids = false;
    $get_by_slugs = false;
    $category_link = null;
    
    $troubleshooting .= "args as passed to birdhive_get_posts: <pre>".print_r($a,true)."</pre>";
    
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
    if ( isset($a['post_status']) ) { $post_status = $a['post_status']; } else { $post_status = array( 'publish' ); } // , 'draft'
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
        
        $troubleshooting .= "Getting posts by IDs: ".$a['ids'];
        
        // Turn the list of IDs into a proper array
		$posts_in         = array_map( 'intval', birdhive_att_explode( $a['ids'] ) );
		$args['post__in'] = $posts_in;
        $args['orderby']  = 'post__in';
        $get_by_ids = true;
        
	}
    
    // Posts by slug
    // NB: if slugs are specified, ignore most other args
    if ( isset($a['slugs']) && !empty($a['slugs']) ) {
        
        $troubleshooting .= "Getting posts by slugs: ".$a['slugs'];
        
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
        if ( isset($a['series']) )      { $series_id = $a['series'];    } else { $series_id = null; }
        
        // Deal w/ taxonomy args
        $tax_field = 'slug'; // init -- in some cases will want to use term_id
        if ( $category && empty($taxonomy) ) {
            $taxonomy = 'category';
            $tax_terms = $category;
        }
        $cat_id = null; // init

        // If not empty tax_terms and empty taxonomy, determine default taxonomy from post type
        if ( empty($taxonomy) && !empty($tax_terms) ) {
            $troubleshooting .= "Using birdhive_get_default_taxonomy"; // tft
            $taxonomy = birdhive_get_default_taxonomy($post_type);
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
            $category = birdhive_get_default_category();
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
            $troubleshooting .= "is_category (archive)<br />";

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

            $troubleshooting .= "Building tax_query based on taxonomy & tax_terms.<br />";

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
			if ( post_type_exists('sermon') && $post_type == 'sermon' && $series_id ) {

				$meta_query_components[] = 
					array(
						'key' => 'sermons_series',
                        'value' => '"' . $series_id . '"', // matches exactly "123", not just 123. This prevents a match for "1234"
                        'compare' => 'LIKE'	
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
    
    $troubleshooting .= "WP_Query run as follows:";
    $troubleshooting .= "<pre>args: ".print_r($args, true)."</pre>"; // tft
    //$troubleshooting .= "<pre>meta_query: ".print_r($meta_query, true)."</pre>"; // tft
	//$troubleshooting .= "<pre>arr_posts: ".print_r($arr_posts, true)."</pre>"; // tft

    $troubleshooting .= "birdhive_get_posts arr_posts->request<pre>".$arr_posts->request."</pre>"; // tft -- wip
    $troubleshooting .= "birdhive_get_posts last_query:<pre>".$wpdb->last_query."</pre>"; // tft
    
    //$info = '<div class="troubleshooting">'.$info.'</div>';
    
    $arr_posts_info['arr_posts'] = $arr_posts;
    $arr_posts_info['args'] = $args;
    $arr_posts_info['category_link'] = $category_link;
    $arr_posts_info['info'] = $info;
    $arr_posts_info['troubleshooting'] = $troubleshooting;
    
    return $arr_posts_info;
}

// Function for display of posts in various formats -- links, grid, &c.
// This shortcode is in use on numerous Pages, as well as via the archive.php page template
add_shortcode('display_posts', 'birdhive_display_posts');
function birdhive_display_posts ( $atts = [] ) {
//function birdhive_display_posts ( $args = array() ) {
    global $wpdb;
	$info = "";
	$troubleshooting = "";
	
	/*
    // Defaults
	$defaults = array(
		'post_id'         => null,
		'preview_length'  => 55,
		'readmore'        => false,
	);
	
    // Parse args
	$args = wp_parse_args( $args, $defaults );

	// Extract
	extract( $args );
	*/

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
        'return_format' => 'links', // other options: excerpts; archive (full post content); grid; table
        'cols' => 4,
        'spacing' => 'spaced',
        'header' => false,
        'overlay' => false,
        'has_image' => false, // set to true to ONLY return posts with features images
        'class' => null, // for additional styling
        'show_images' => false,
        'expandable' => false, // for excerpts
        'text_length' => 'excerpt', // excerpt or full length
        'preview_length' => '55',
        
        // For post_type 'event'
        'scope' => 'upcoming',
        
        // For Events or Sermons
        'series' => false,
        
        // For table return_format
        'fields'  => null,
        'headers'  => null,
        
    ), $atts );
    
    if ( $a ) { $troubleshooting .= 'shortcode_atts: <pre>'.print_r($a, true).'</pre>'; } // tft
    
    $post_type = $a['post_type'];
    $return_format = $a['return_format'];
    $class = $a['class'];
    $show_images = $a['show_images'];
    $expandable = $a['expandable'];
    $text_length = $a['text_length'];
    $preview_length = $a['preview_length'];
    
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
    if ( $return_format != "links" && $return_format != "table" && $return_format != "grid" && $return_format != "excerpts" && $return_format != "archive" ) {
        $return_format = "links"; // default
    }
    
    //$return_format = "links"; // tft
    
    // Retrieve an array of posts matching the args supplied    
    if ( post_type_exists('event') && $post_type == 'event' ) {
    	// TODO: check to see if EM plugin is installed and active?
        // TODO: deal w/ taxonomy parameters -- how to translate these properly for EM?
        $posts = EM_Events::get( $a ); // Retrieves an array of EM_Event Objects
        
        $troubleshooting .= 'Posts retrieved using EM_Events::get: <pre>';
        
        foreach ( $posts as $post ) {
            //$troubleshooting .= "post: ".print_r($post, true)."<br />";
            $troubleshooting .= "post_id: ".$post->post_id."<br />";
            //$troubleshooting .= "event_attributes: ".print_r($post->event_attributes, true)."<br />";
            if ( isset($post->event_attributes['event_series']) ) { $troubleshooting .= "event_series: ".$post->event_attributes['event_series']."<br />"; }
        }
        //$troubleshooting .= 'last_query: '.print_r( $wpdb->last_query, true); // '<pre></pre>'
        $troubleshooting .= '</pre>'; // tft
    } else {
        $posts_info = birdhive_get_posts( $a );
        $posts = $posts_info['arr_posts']->posts; // Retrieves an array of WP_Post Objects
        $info .= $posts_info['info'];
        $troubleshooting .= $posts_info['troubleshooting'];
    }
    
    if ( $posts ) {
        
        $troubleshooting .= '<pre>'.print_r($posts, true).'</pre>'; // tft
        
		//if ($a['header'] == 'true') { $info .= '<h3>Latest '.$category.' Articles:</h3>'; } // WIP
		$info .= '<div class="dp-posts">';
        
        if ( $return_format == "links" ) {
            $info .= '<ul>';
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
        } else if ( $return_format == "excerpts" || $return_format == "archive" ) {
            $info .= '<div class="posts_archive">';
        }
        
        foreach ( $posts as $post ) {
            
            //$troubleshooting .= '<pre>'.print_r($post, true).'</pre>'; // tft
            //$troubleshooting .= 'post: <pre>'.print_r($post, true).'</pre>'; // tft
            
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
                
            } else if ( $return_format == "excerpts" || $return_format == "archive" ) {
                
                // TODO: bring this more in alignment with theme template display? e.g. content-excerpt, content-sermon, content-event...
                $info .= '<!-- '.$return_format.' -->';
                $info .= '<article id="post-'.$post_id.'">'; // post_class()
                $info .= '<header class="entry-header">';
                $info .= '<h2 class="entry-title"><a href="'.get_the_permalink( $post_id ).'" rel="bookmark">'.$post_title.'</a></h2>';
                $info .= '</header><!-- .entry-header -->';
                $info .= '<div class="entry-content">';
                //$info .= birdhive_post_thumbnail($post_id);
                if ( $show_images ) {
                    $info .= birdhive_post_thumbnail($post_id,'thumbnail',false,false); // function birdhive_post_thumbnail( $post_id = null, $imgsize = "thumbnail", $use_custom_thumb = false, $echo = true )
                }
                if ( $return_format == "excerpts" ) {
                	
                	if ( function_exists('is_dev_site') && is_dev_site() ) {
                		$info .= expandable_text( array('post_id' => $post_id, 'text_length' => $text_length, 'preview_length' => $preview_length ) );
                		//$info .= dp_get_excerpt( array('post_id' => $post_id, 'expandable' => $expandable, 'text_length' => $text_length, 'preview_length' => $preview_length ) );
                		//$info .= $post->post_excerpt;
                	} else {
                		$info .= get_the_excerpt( $post_id );
                	}
                	
                } else {
                    $info .= $post->post_content;
                }
                
                $info .= '</div><!-- .entry-content -->';
                $info .= '<footer class="entry-footer">';
                $info .= birdhive_entry_meta( $post_id );
                $info .= '</footer><!-- .entry-footer -->';
                $info .= '</article><!-- #post-'.$post_id.' -->';

                // WIP is it possible to use template parts in this context?
                //$info .= get_template_part( 'template-parts/content', 'excerpt', array('post_id' => $post_id ) ); // 
                //$post_type_for_template = birdhive_get_type_for_template();
                //get_template_part( 'template-parts/content', $post_type_for_template );
                //$info .= get_template_part( 'template-parts/content', $post_type );
                
            } else {
                
                $the_content = apply_filters('the_content', get_the_content($post_id));
                $info .= $the_content;
                //$info .= the_content();
                
            }
            
        }
        
        if ( $return_format == "links" ) {
            //if ( ! is_archive() && ! is_category() ) { $info .= '<li>'.$category_link.'</li>'; }
            $info .= '</ul>';
        } else if ( $return_format == "table" ) {
            $info .= '</table>';
        } else if ( $return_format == "grid" ) {
            $info .= '</div>';
        } else if ( $return_format == "excerpts" || $return_format == "archive" ) {
            $info .= '</div>';
        }
		
        $info .= '</div>'; // end div class="dp-posts" (wrapper)
        
        wp_reset_postdata();
    
    }  else {
        
        $troubleshooting .= "No posts found!";
        
    } // END if posts
    
    $info .= '<div class="troubleshooting">'.$troubleshooting.'</div>';
    
    return $info;
    
}


// ACF field groups...
function match_group_field ( $field_groups, $field_name ) {
    
    $field = null;
    
    // Loop through the field_groups and their fields to look for a match (by field name)
    foreach ( $field_groups as $group ) {

        $group_key = $group['key'];
        //$info .= "group: <pre>".print_r($group,true)."</pre>"; // tft
        $group_title = $group['title'];
        $group_fields = acf_get_fields($group_key); // Get all fields associated with the group
        //$field_info .= "<hr /><strong>".$group_title."/".$group_key."] ".count($group_fields)." group_fields</strong><br />"; // tft

        $i = 0;
        foreach ( $group_fields as $group_field ) {

            $i++;

            if ( $group_field['name'] == $field_name ) {

                // field exists, i.e. the post_type is associated with a field matching the $field_name
                $field = $group_field;
                // field_object parameters include: key, label, name, type, id -- also potentially: 'post_type' for relationship fields, 'sub_fields' for repeater fields, 'choices' for select fields, and so on

                //$field_info .= "Matching field found for field_name $field_name!<br />"; // tft
                //$field_info .= "<pre>".print_r($group_field,true)."</pre>"; // tft

                /*
                $field_info .= "[$i] group_field: <pre>".print_r($group_field,true)."</pre>"; // tft
                $field_info .= "[$i] group_field: ".$group_field['key']."<br />";
                $field_info .= "label: ".$group_field['label']."<br />";
                $field_info .= "name: ".$group_field['name']."<br />";
                $field_info .= "type: ".$group_field['type']."<br />";
                if ( $group_field['type'] == "relationship" ) { $field_info .= "post_type: ".print_r($group_field['post_type'],true)."<br />"; }
                if ( $group_field['type'] == "select" ) { $field_info .= "choices: ".print_r($group_field['choices'],true)."<br />"; }
                $field_info .= "<br />";
                //$field_info .= "[$i] group_field: ".$group_field['key']."/".$group_field['label']."/".$group_field['name']."/".$group_field['type']."/".$group_field['post_type']."<br />";
                */

                break;
            }

        }

        if ( $field ) { 
            //$field_info .= "break.<br />";
            break;  // Once the field has been matched to a post_type field, there's no need to continue looping
        }

    } // END foreach ( $field_groups as $group )
    
    return $field;
}


/***  SEARCH FORM ***/

// TODO: generalize the following to make this functionality not so repertoire-specific
// https://www.advancedcustomfields.com/resources/creating-wp-archive-custom-field-filter/
add_shortcode('birdhive_search_form', 'birdhive_search_form');
function birdhive_search_form ($atts = [], $content = null, $tag = '') {
//function birdhive_search_form ( $args = array() ) {
	/*
    // Defaults
	$defaults = array(
		'post_id'         => null,
		'preview_length'  => 55,
		'readmore'        => false,
	);
	
    // Parse args
	$args = wp_parse_args( $args, $defaults );

	// Extract
	extract( $args );
	*/
	
	$info = "";
    $troubleshooting = "";
    //$search_values = false; // var to track whether any search values have been submitted on which to base the search
    $search_values = array(); // var to track whether any search values have been submitted and to which post_types they apply
    
    $troubleshooting .= '_GET: <pre>'.print_r($_GET,true).'</pre>'; // tft
    //$troubleshooting .= '_REQUEST: <pre>'.print_r($_REQUEST,true).'</pre>'; // tft
        
	$a = shortcode_atts( array(
		'post_type'    => 'post',
		'fields'       => null,
        'form_type'    => 'simple_search',
        'limit'        => '-1'
    ), $atts );
    
    $post_type = $a['post_type'];
    $form_type = $a['form_type'];
    $limit = $a['limit'];
    
    //$info .= "form_type: $form_type<br />"; // tft

    // After building the form, assuming any search terms have been submitted, we're going to call the function birdhive_get_posts
    // In prep for that search call, initialize some vars to be used in the args array
    // Set up basic query args
    $args = array(
		'post_type'       => array( $post_type ), // Single item array, for now. May add other related_post_types -- e.g. repertoire; edition
		'post_status'     => 'publish',
		'posts_per_page'  => $limit, //-1, //$posts_per_page,
        'orderby'         => 'title',
        'order'           => 'ASC',
        'return_fields'   => 'ids',
	);
    
    // WIP / TODO: fine-tune ordering -- 1) rep with editions, sorted by title_clean 2) rep without editions, sorted by title_clean
    /*
    'orderby'	=> 'meta_value',
    'meta_key' 	=> '_event_start_date',
    'order'     => 'DESC',
    */
    
    //
    $meta_query = array();
    $meta_query_related = array();
    $tax_query = array();
    $tax_query_related = array();
    //$options_posts = array();
    //
    $mq_components_primary = array(); // meta_query components
    $tq_components_primary = array(); // tax_query components
    $mq_components_related = array(); // meta_query components
    $tq_components_related = array(); // tax_query components
    //$mq_components = array(); // meta_query components
    //$tq_components = array(); // tax_query components
    
    // Get related post type(s), if any
    if ( $post_type == "repertoire" ) {
        $related_post_type = 'edition';
    } else {
        $related_post_type = null;
    }
    
    // init -- determines whether or not to *search* multiple post types -- depends on kinds of search values submitted
    $search_primary_post_type = false;
    $search_related_post_type = false;
    $query_assignment = "primary"; // init -- each field pertains to either primary or related query
    
    // Check to see if any fields have been designated via the shortcode attributes
    if ( $a['fields'] ) {
        
        // Turn the fields list into an array
        $arr_fields = birdhive_att_explode( $a['fields'] ); //if ( function_exists('sdg_att_explode') ) { }
        //$info .= print_r($arr_fields, true); // tft
        
        // e.g. http://stthomas.choirplanner.com/library/search.php?workQuery=Easter&composerQuery=Williams
        
        $info .= '<form class="birdhive_search_form '.$form_type.'">';
        //$info .= '<form action="'.htmlspecialchars($_SERVER['PHP_SELF']).'" class="birdhive_search_form '.$form_type.'">';
        
        // Get all ACF field groups associated with the primary post_type
        $field_groups = acf_get_field_groups( array( 'post_type' => $post_type ) );
        
        // Get all taxonomies associated with the primary post_type
        $taxonomies = get_object_taxonomies( $post_type );
        //$info .= "taxonomies for post_type '$post_type': <pre>".print_r($taxonomies,true)."</pre>"; // tft
        
        ///
        $search_operator = "and"; // init
        
        // Loop through the field names and create the actual form fields
        foreach ( $arr_fields as $arr_field ) {
            
            $field_info = ""; // init
            $field_name = $arr_field; // may be overrriden below
            $alt_field_name = null; // for WIP fields/transition incomplete, e.g. repertoire_litdates replacing related_liturgical_dates
                    
            // Fine tune the field name
            if ( $field_name == "title" ) {
                $placeholder = "title"; // for input field
                if ( $post_type == "repertoire" ) { // || $post_type == "edition"
                    $field_name = "title_clean"; // todo -- address problem that editions don't have this field
                    //$field_name = "post_title";
                } else {
                    $field_name = "post_title";
                    //$field_name = "s";
                }
            } else {
                $placeholder = $field_name; // for input field
            }
            
            if ( $form_type == "advanced_search" ) {
                $field_label = str_replace("_", " ",ucfirst($placeholder));
                if ( $field_label == "Repertoire category" ) { 
                    $field_label = "Category";
                } else if ( $field_name == "liturgical_date" || $field_label == "Related liturgical dates" ) { 
                    $field_label = "Liturgical Dates";
                    $field_name = "repertoire_litdates";
                    $alt_field_name = "related_liturgical_dates";
                }/* else if ( $field_name == "edition_publisher" ) {
                    $field_label = "Publisher";
                }*/
            }
            
            // Check to see if the field_name is an actual field, separator, or search operator
            if ( str_starts_with($field_name, '&') ) { 
                
                // This "field" is a separator/text between fields                
                $info .= substr($field_name,1).'&nbsp;';
                
            } else if ( $field_name == 'search_operator' ) {
                
                // This "field" is a search operator, i.e. search type
                
                if ( !isset($_GET[$field_name]) || empty($_GET[$field_name]) ) { $search_operator = 'and'; } else { $search_operator = $_GET[$field_name]; } // default to "and"

                $info .= 'Search Type: ';
                $info .= '<input type="radio" id="and" name="search_operator" value="and"';
                if ( $search_operator == 'and' ) { $info .= ' checked="checked"'; }
                $info .= '>';
                $info .= '<label for="and">AND <span class="tip">(match all criteria)</span></label>&nbsp;';
                $info .= '<input type="radio" id="or" name="search_operator" value="or"';
                if ( $search_operator == 'or' ) { $info .= ' checked="checked"'; }
                $info .= '>';
                $info .= '<label for="or">OR <span class="tip">(match any)</span></label>';
                $info .= '<br />';
                        
            } else if ( $field_name == 'devmode' ) {
                
                // This "field" is for testing/dev purposes only
                
                if ( !isset($_GET[$field_name]) || empty($_GET[$field_name]) ) { $devmode = 'true'; } else { $devmode = $_GET[$field_name]; } // default to "true"

                $info .= 'Dev Mode?: ';
                $info .= '<input type="radio" id="devmode" name="devmode" value="true"';
                if ( $devmode == 'true' ) { $info .= ' checked="checked"'; }
                $info .= '>';
                $info .= '<label for="true">True</label>&nbsp;';
                $info .= '<input type="radio" id="false" name="devmode" value="false"';
                if ( $devmode !== 'true' ) { $info .= ' checked="checked"'; }
                $info .= '>';
                $info .= '<label for="false">False</label>';
                $info .= '<br />';
                        
            } else {
                
                // This is an actual search field
                
                // init/defaults
                $field_type = null; // used to default to "text"
                $pick_object = null; // ?pods?
                $pick_custom = null; // ?pods?
                $field = null;
                $field_value = null;
                
                // First, deal w/ title field -- special case
                if ( $field_name == "post_title" ) {
                    $field = array( 'type' => 'text', 'name' => $field_name );
                }
                //if ( $field_name == "edition_publisher"
                
                // Check to see if a field by this name is associated with the designated post_type -- for now, only in use for repertoire(?)
                $field = match_group_field( $field_groups, $field_name );
                
                if ( $field ) {
                    
                    // if field_name is same as post_type, must alter it to prevent automatic redirect when search is submitted -- e.g. "???"
                    if ( post_type_exists( $arr_field ) ) {
                        $field_name = $post_type."_".$arr_field;
                    }
                    
                    $query_assignment = "primary";
                    
                } else {
                    
                    //$field_info .= "field_name: $field_name -- not found for $post_type >> look for related field.<br />"; // tft
                    
                    // If no matching field was found in the primary post_type, then
                    // ... get all ACF field groups associated with the related_post_type(s)                    
                    $related_field_groups = acf_get_field_groups( array( 'post_type' => $related_post_type ) );
                    $field = match_group_field( $related_field_groups, $field_name );
                                
                    if ( $field ) {
                        
                        // if field_name is same as post_type, must alter it to prevent automatic redirect when search is submitted -- e.g. "publisher" => "edition_publisher"
                        if ( post_type_exists( $arr_field ) ) {
                            $field_name = $related_post_type."_".$arr_field;
                        }
                        $query_assignment = "related";
                        $field_info .= "field_name: $field_name found for related_post_type: $related_post_type.<br />"; // tft    
                        
                    } else {
                        
                        // Still no field found? Check taxonomies 
                        //$field_info .= "field_name: $field_name -- not found for $related_post_type either >> look for taxonomy.<br />"; // tft
                        
                        // For field_names matching taxonomies, check for match in $taxonomies array
                        if ( taxonomy_exists( $field_name ) ) {
                            
                            $field_info .= "$field_name taxonomy exists.<br />";
                                
                            if ( in_array($field_name, $taxonomies) ) {

                                $query_assignment = "primary";                                    
                                $field_info .= "field_name $field_name found in primary taxonomies array<br />";

                            } else {

                                // Get all taxonomies associated with the related_post_type
                                $related_taxonomies = get_object_taxonomies( $related_post_type );

                                if ( in_array($field_name, $related_taxonomies) ) {

                                    $query_assignment = "related";
                                    $field_info .= "field_name $field_name found in related taxonomies array<br />";                                        

                                } else {
                                    $field_info .= "field_name $field_name NOT found in related taxonomies array<br />";
                                }
                                //$info .= "taxonomies for post_type '$related_post_type': <pre>".print_r($related_taxonomies,true)."</pre>"; // tft

                                $field_info .= "field_name $field_name NOT found in primary taxonomies array<br />";
                            }
                            
                            $field = array( 'type' => 'taxonomy', 'name' => $field_name );
                            
                        } else {
                            $field_info .= "Could not determine field_type!<br />";
                        }
                    }
                }                
                
                if ( $field ) {
                    
                    //$field_info .= "field: <pre>".print_r($field,true)."</pre>"; // tft
                    
                    if ( isset($field['post_type']) ) { $field_post_type = $field['post_type']; } else { $field_post_type = null; } // ??
                    
                    // Check to see if a custom post type or taxonomy exists with same name as $field_name
                    // In the case of the choirplanner search form, this will be relevant for post types such as "Publisher" and taxonomies such as "Voicing"
                    if ( post_type_exists( $arr_field ) || taxonomy_exists( $arr_field ) ) {
                        $field_cptt_name = $arr_field;
                        //$field_info .= "field_cptt_name: $field_cptt_name same as arr_field: $arr_field<br />"; // tft
                    } else {
                        $field_cptt_name = null;
                    }

                    //
                    $field_info .= "field_name: $field_name<br />"; // tft
                    if ( $alt_field_name ) { $field_info .= "alt_field_name: $alt_field_name<br />"; }                    
                    $field_info .= "query_assignment: $query_assignment<br />";

                    // Check to see if a value was submitted for this field
                    if ( isset($_GET[$field_name]) ) { // if ( isset($_REQUEST[$field_name]) ) {
                        
                        $field_value = $_GET[$field_name]; // $field_value = $_REQUEST[$field_name];
                        
                        // If field value is not empty...
                        if ( !empty($field_value) && $field_name != 'search_operator' && $field_name != 'devmode' ) {
                            //$search_values = true; // actual non-empty search values have been found in the _GET/_REQUEST array
                            // instead of boolean, create a search_values array? and track which post_type they relate to?
                            $search_values[] = array( 'field_post_type' => $field_post_type, 'arr_field' => $arr_field, 'field_name' => $field_name, 'field_value' => $field_value );
                            //$field_info .= "field value: $field_value<br />"; 
                            //$troubleshooting .= "query_assignment for field_name $field_name is *$query_assignment* >> search value: '$field_value'<br />";
                            
                            if ( $query_assignment == "primary" ) {
                                $search_primary_post_type = true;
                                $troubleshooting .= ">> Setting search_primary_post_type var to TRUE based on field $field_name searching value $field_value<br />";
                            } else {
                                $search_related_post_type = true;
                                $troubleshooting .= ">> Setting search_related_post_type var to TRUE based on field $field_name searching value $field_value<br />";
                            }
                            
                        }
                        
                        $field_info .= "field value: $field_value<br />";
                        
                    } else {
                        //$field_info .= "field value: [none]<br />";
                        $field_value = null;
                    }

                    
                    // Get 'type' field option
                    $field_type = $field['type'];
                    $field_info .= "field_type: $field_type<br />"; // tft
                    
                    if ( !empty($field_value) ) {
                        if ( function_exists('sdg_sanitize')) { $field_value = sdg_sanitize($field_value); }
                    }
                    
                    //$field_info .= "field_name: $field_name<br />";                    
                    //$field_info .= "value: $field_value<br />";
                    
                    if ( $field_type !== "text" && $field_type !== "taxonomy" ) {
                        //$field_info .= "field: <pre>".print_r($field,true)."</pre>"; // tft
                        //$field_info .= "field key: ".$field['key']."<br />";
                        //$field_info .= "field return_format: ".$field['return_format']."<br />";
                    }                    
                    
                    //if ( ( $field_name == "post_title" || $field_name == "title_clean" ) && !empty($field_value) ) {
                    
                    if ( $field_name == "post_title" && !empty($field_value) ) {
                        
                        //$args['s'] = $field_value;
                        $args['_search_title'] = $field_value; // custom parameter -- see posts_where filter fcn

                    } else if ( $field_type == "text" && !empty($field_value) ) { 
                        
                        // TODO: figure out how to determine whether to match exact or not for particular fields
                        // -- e.g. box_num should be exact, but not necessarily for title_clean?
                        // For now, set it explicitly per field_name
                        /*if ( $field_name == "box_num" ) {
                            $match_value = '"' . $field_value . '"'; // matches exactly "123", not just 123. This prevents a match for "1234"
                        } else {
                            $match_value = $field_value;
                        }*/
                        $match_value = $field_value;
                        //$mq_components[] =  array(
                        $query_component = array(
                            'key'   => $field_name,
                            'value' => $match_value,
                            'compare'=> 'LIKE'
                        );
                        
                        // Add query component to the appropriate components array
                        if ( $query_assignment == "primary" ) {
                            $mq_components_primary[] = $query_component;
                        } else {
                            $mq_components_related[] = $query_component;
                        }
                        
                        $field_info .= ">> Added $query_assignment meta_query_component for key: $field_name, value: $match_value<br/>";

                    } else if ( $field_type == "select" && !empty($field_value) ) { 
                        
                        // If field allows multiple values, then values will return as array and we must use LIKE comparison
                        if ( $field['multiple'] == 1 ) {
                            $compare = 'LIKE';
                        } else {
                            $compare = '=';
                        }
                        
                        $match_value = $field_value;
                        $query_component = array(
                            'key'   => $field_name,
                            'value' => $match_value,
                            'compare'=> $compare
                        );
                        
                        // Add query component to the appropriate components array
                        if ( $query_assignment == "primary" ) {
                            $mq_components_primary[] = $query_component;
                        } else {
                            $mq_components_related[] = $query_component;
                        }                        
                        
                        $field_info .= ">> Added $query_assignment meta_query_component for key: $field_name, value: $match_value<br/>";

                    } else if ( $field_type == "relationship" ) { // && !empty($field_value) 

                        $field_post_type = $field['post_type'];                        
                        // Check to see if more than one element in array. If not, use $field['post_type'][0]...
                        if ( count($field_post_type) == 1) {
                            $field_post_type = $field['post_type'][0];
                        } else {
                            // ???
                        }
                        
                        $field_info .= "field_post_type: ".print_r($field_post_type,true)."<br />";
                        
                        if ( !empty($field_value) ) {
                            
                            $field_value_converted = ""; // init var for storing ids of posts matching field_value
                            
                            // If $options,
                            if ( !empty($options) ) {
                                
                                if ( $arr_field == "publisher" ) {
                                    $key = $arr_field; // can't use field_name because of redirect issue
                                } else {
                                    $key = $field_name;
                                }
                                $query_component = array(
                                    'key'   => $key, 
                                    //'value' => $match_value,
                                    // TODO: FIX -- value as follows doesn't work w/ liturgical dates because it's trying to match string, not id... need to get id!
                                    'value' => '"' . $field_value . '"', // matches exactly "123", not just 123. This prevents a match for "1234"
                                    'compare'=> 'LIKE', 
                                );

                                // Add query component to the appropriate components array
                                if ( $query_assignment == "primary" ) {
                                    $mq_components_primary[] = $query_component;
                                } else {
                                    $mq_components_related[] = $query_component;
                                }
                                
                                if ( $alt_field_name ) {
                                    
                                    $meta_query['relation'] = 'OR';
                                    
                                    $query_component = array(
                                        'key'   => $alt_field_name,
                                        //'value' => $field_value,
                                        // TODO: FIX -- value as follows doesn't work w/ liturgical dates because it's trying to match string, not id... need to get id!
                                        'value' => '"' . $field_value . '"',
                                        'compare'=> 'LIKE'
                                    );
                                    
                                    // Add query component to the appropriate components array
                                    if ( $query_assignment == "primary" ) {
                                        $mq_components_primary[] = $query_component;
                                    } else {
                                        $mq_components_related[] = $query_component;
                                    }
                                    
                                }
                                
                            } else {
                                
                                // If no $options, match search terms
                                $field_info .= "options array is empty.<br />";
                                
                                // Get id(s) of any matching $field_post_type records with post_title like $field_value
                                $field_value_args = array('post_type' => $field_post_type, 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', '_search_title' => $field_value, 'suppress_filters' => FALSE );
                                $field_value_posts = get_posts( $field_value_args );
                                if ( count($field_value_posts) > 0 ) {

                                    $field_info .= count($field_value_posts)." field_value_posts found<br />";
                                    //$field_info .= "field_value_args: <pre>".print_r($field_value_args, true)."</pre><br />";

                                    // The problem here is that, because ACF stores multiple values as a single meta_value array, 
                                    // ... it's not possible to search efficiently for an array of values
                                    // TODO: figure out if there's some way to for ACF to store the meta_values in separate rows?
                                    
                                    $sub_query = array();
                                    
                                    if ( count($field_value_posts) > 1 ) {
                                        $sub_query['relation'] = 'OR';
                                    }
                                    
                                    // TODO: make this a subquery to better control relation
                                    foreach ( $field_value_posts as $fvp_id ) {
                                        $sub_query[] = [
                                            'key'   => $arr_field, // can't use field_name because of "publisher" issue
                                            //'key'   => $field_name,
                                            'value' => '"' . $fvp_id . '"',
                                            'compare' => 'LIKE',
                                        ];
                                    }
                                    
                                    // Add query component to the appropriate components array
                                    if ( $query_assignment == "primary" ) {
                                        $mq_components_primary[] = $sub_query;
                                    } else {
                                        $mq_components_related[] = $sub_query;
                                    }
                                    //$mq_components_primary[] = $sub_query;
                                }
                                
                            }
                            
                            //$field_info .= ">> WIP: set meta_query component for: $field_name = $field_value<br/>";
                            $field_info .= "Added meta_query_component for key: $field_name, value: $field_value<br/>";
                            
                        }
                        
                        // For text fields, may need to get ID matching value -- e.g. person id for name mousezart (220824), if composer field were not set up as combobox -- maybe faster?
                        
                                                
                        /* ACF
                        create_field( $field_name ); // new ACF fcn to generate HTML for field 
                        // see https://www.advancedcustomfields.com/resources/creating-wp-archive-custom-field-filter/ and https://www.advancedcustomfields.com/resources/upgrade-guide-version-4/
                        
                        
                        // Old ACF -- see ca. 08:25 in video tutorial:
                        $field_obj = get_field_object($field_name);
                        foreach ( $field_obj['choices'] as $choice_value => $choice_label ) {
                            // checkbox code or whatever
                        }                        
                        */

                    } else if ( $field_type == "taxonomy" && !empty($field_value) ) {

                        $query_component = array (
                            'taxonomy' => $field_name,
                            //'field'    => 'slug',
                            'terms'    => $field_value,
                        );
                        
                        // Add query component to the appropriate components array
                        if ( $query_assignment == "primary" ) {
                            $tq_components_primary[] = $query_component;
                        } else {
                            $tq_components_related[] = $query_component;
                        }

                        if ( $post_type == "repertoire" ) {

                            // Since rep & editions share numerous taxonomies in common, check both
                            
                            $related_field_name = 'repertoire_editions'; //$related_field_name = 'related_editions';
                            
                            $field_info .= ">> WIP: field_type: taxonomy; field_name: $field_name; post_type: $post_type; terms: $field_value<br />"; // tft
                            
                            // Add a tax query somehow to search for related_post_type posts with matching taxonomy value                            
                            // Create a secondary query for related_post_type?
                            // PROBLEM WIP -- tax_query doesn't seem to work with two post_types if tax only applies to one of them?
                            
                            /*
                            $tq_components_primary[] = array(
                                'taxonomy' => $field_name,
                                //'field'    => 'slug',
                                'terms'    => $field_value,
                            );
                            
                            // Add query component to the appropriate components array
                            if ( $query_assignment == "primary" ) {
                                $tq_components_primary[] = $query_component;
                            } else {
                                $tq_components_related[] = $query_component;
                            }
                            */

                        }

                    }
                    
                    //$field_info .= "-----<br />";
                    
                } // END if ( $field )
                
                
                // Set up the form fields
                // ----------------------
                if ( $form_type == "advanced_search" ) {
                   
                    //$field_info .= "CONFIRM field_type: $field_type<br />"; // tft
                    
                    $input_class = "advanced_search";
                    $input_html = "";
                    $options = array();
                    
                    if ( in_array($field_name, $taxonomies) ) {
                        $input_class .= " primary_post_type";
                        $field_label .= "*";
                    }                    
                    
                    $info .= '<label for="'.$field_name.'" class="'.$input_class.'">'.$field_label.':</label>';
                    
                    if ( $field_type == "text" ) {
                        
                        $input_html = '<input type="text" id="'.$field_name.'" name="'.$field_name.'" value="'.$field_value.'" class="'.$input_class.'" />';                                            
                    
                    } else if ( $field_type == "select" ) {
                        
                        if ( isset($field['choices']) ) {
                            $options = $field['choices'];
                            //$field_info .= "field: <pre>".print_r($field, true)."</pre>";
                            //$field_info .= "field choices: <pre>".print_r($field['choices'],true)."</pre>"; // tft
                        } else {
                            $options = null; // init
                            $field_info .= "No field choices found. About to go looking for values to set as options...<br />";
                            $field_info .= "field: <pre>".print_r($field, true)."</pre>";
                        }
                        
                    } else if ( $field_type == "relationship" ) {
                        
                        if ( $field_cptt_name ) { $field_info .= "field_cptt_name: $field_cptt_name<br />"; } // tft 
                        if ( $arr_field ) { $field_info .= "arr_field: $arr_field<br />"; } // tft 
                        
                        // repertoire_litdates
                        // related_liturgical_dates
                        
                        if ( $field_cptt_name != $arr_field ) {
                        //if ( $field_cptt_name != $field_name ) {
                            
                            $field_info .= "field_cptt_name NE arr_field<br />"; // tft
                            //$field_info .= "field_cptt_name NE field_name<br />"; // tft
                            
                            // TODO: 
                            if ( $field_post_type && $field_post_type != "person" && $field_post_type != "publisher" ) { // TMP disable options for person fields so as to allow for free autocomplete
                                
                                // TODO: consider when to present options as combo box and when to go for autocomplete text
                                // For instance, what if the user can't remember which Bach wrote a piece? Should be able to search for all...
                                
                                // e.g. field_post_type = person, field_name = composer 
                                // ==> find all people in Composers people_category -- PROBLEM: people might not be correctly categorized -- this depends on good data entry
                                // -- alt: get list of composers who are represented in the music library -- get unique meta_values for meta_key="composer"

                                // TODO: figure out how to filter for only composers related to editions? or lit dates related to rep... &c.
                                // TODO: find a way to do this more efficiently, perhaps with a direct wpdb query to get all unique meta_values for relevant keys
                                
                                //
                                // set up WP_query
                                $options_args = array(
                                    'post_type' => $post_type, //'post_type' => $field_post_type,
                                    'post_status' => 'publish',
                                    'fields' => 'ids',
                                    'posts_per_page' => -1, // get them all
                                    'meta_query' => array(
                                        'relation' => 'OR',
                                        array(
                                            'key'     => $field_name,
                                            'compare' => 'EXISTS'
                                        ),
                                        array(
                                            'key'     => $alt_field_name,
                                            'compare' => 'EXISTS'
                                        ),
                                    ),
                                );
                                
                                $options_arr_posts = new WP_Query( $options_args );
                                $options_posts = $options_arr_posts->posts;

                                //$field_info .= "options_args: <pre>".print_r($options_args,true)."</pre>"; // tft
                                $field_info .= count($options_posts)." options_posts found <br />"; // tft
                                //$field_info .= "options_posts: <pre>".print_r($options_posts,true)."</pre>"; // tft

                                $arr_ids = array(); // init

                                foreach ( $options_posts as $options_post_id ) {

                                    // see also get composer_ids
                                    $meta_values = get_field($field_name, $options_post_id, false);
                                    $alt_meta_values = get_field($alt_field_name, $options_post_id, false);
                                    if ( !empty($meta_values) ) {
                                        //$field_info .= count($meta_values)." meta_value(s) found for field_name: $field_name and post_id: $options_post_id.<br />";
                                        foreach ($meta_values AS $meta_value) {
                                            $arr_ids[] = $meta_value;
                                        }
                                    }
                                    if ( !empty($alt_meta_values) ) {
                                        //$field_info .= count($alt_meta_values)." meta_value(s) found for alt_field_name: $alt_field_name and post_id: $options_post_id.<br />";
                                        foreach ($alt_meta_values AS $meta_value) {
                                            $arr_ids[] = $meta_value;
                                        }
                                    }

                                }

                                $arr_ids = array_unique($arr_ids);

                                // Build the options array from the ids
                                foreach ( $arr_ids as $id ) {
                                    if ( $field_post_type == "person" ) {
                                        $last_name = get_post_meta( $id, 'last_name', true );
                                        $first_name = get_post_meta( $id, 'first_name', true );
                                        $middle_name = get_post_meta( $id, 'middle_name', true );
                                        //
                                        $option_name = $last_name;
                                        if ( !empty($first_name) ) {
                                            $option_name .= ", ".$first_name;
                                        }
                                        if ( !empty($middle_name) ) {
                                            $option_name .= " ".$middle_name;
                                        }
                                        //$option_name = $last_name.", ".$first_name;
                                        $options[$id] = $option_name;
                                        // TODO: deal w/ possibility that last_name, first_name fields are empty
                                    } else {
                                        $options[$id] = get_the_title($id);
                                    }
                                }

                            }

                            asort($options);

                        } else {
                        	
                        	$input_html = '<input type="text" id="'.$field_name.'" name="'.$field_name.'" value="'.$field_value.'" class="'.$input_class.'" />';                                            
                    
                    		//$input_html = "LE TSET"; // tft
                        	//$input_html = '<input type="text" id="'.$field_name.'" name="'.$field_name.'" value="'.$field_value.'" class="autocomplete '.$input_class.' relationship" />';
                        }
                        
                    } else if ( $field_type == "taxonomy" ) {
                        
                        // Get options, i.e. taxonomy terms
                        $obj_options = get_terms ( $field_name );
                        //$info .= "options for taxonomy $field_name: <pre>".print_r($options, true)."</pre>"; // tft
                        
                        // Convert objects into array for use in building select menu
                        foreach ( $obj_options as $obj_option ) { // $option_value => $option_name
                            
                            $option_value = $obj_option->term_id;
                            $option_name = $obj_option->name;
                            //$option_slug = $obj_option->slug;
                            $options[$option_value] = $option_name;
                        }
                        
                    } else {
                        
                        $field_info .= "field_type could not be determined.";
                    }
                    
                    if ( !empty($options) ) { // WIP // && strpos($input_class, "combobox")

                        //if ( !empty($field_value) ) { $troubleshooting .= "options: <pre>".print_r($options, true)."</pre>"; } // tft

                        $input_class .= " combobox"; // tft
                                                
                        $input_html = '<select name="'.$field_name.'" id="'.$field_name.'" class="'.$input_class.'">'; 
                        $input_html .= '<option value>-- Select One --</option>'; // default empty value // class="'.$input_class.'"
                        
                        // Loop through the options to build the select menu
                        foreach ( $options as $option_value => $option_name ) {
                            $input_html .= '<option value="'.$option_value.'"';
                            if ( $option_value == $field_value ) { $input_html .= ' selected="selected"'; }
                            //if ( $option_name == "Men-s Voices" ) { $option_name = "Men's Voices"; }
                            $input_html .= '>'.$option_name.'</option>'; //  class="'.$input_class.'"
                        }
                        $input_html .= '</select>';

                    } else if ( $options && strpos($input_class, "multiselect") !== false ) {
                        // TODO: implement multiple select w/ remote source option in addition to combobox (which is for single-select inputs) -- see choirplanner.js WIP
                    } else if ( empty($input_html) ) {
                        $input_html = '<input type="text" id="'.$field_name.'" name="'.$field_name.'" value="'.$field_value.'" class="autocomplete '.$input_class.'" />'; // tft
                    }
                    
                    $info .= $input_html;
                    
                } else {
                    $input_class = "simple_search";
                    $info .= '<input type="text" id="'.$field_name.'" name="'.$field_name.'" placeholder="'.$placeholder.'" value="'.$field_value.'" class="'.$input_class.'" />';
                }
                
                if ( $form_type == "advanced_search" ) {
                    $info .= '<br />';
                    /*$info .= '<div class="dev-view">';
                    $info .= '<span class="troubleshooting smaller">'.$field_info.'</span>\n'; // tft
                    $info .= '</div>';*/
                    //$info .= '<!-- '."\n".$field_info."\n".' -->';
                }
                
                //$troubleshooting .= "+++++<br />FIELD INFO<br/>+++++<br />".$field_info."<br />";
                //if ( strpos($field_name, "publisher") || strpos($field_name, "devmode") || strpos($arr_field, "devmode") || $field_name == "devmode" ) {
                if ( (!empty($field_value) && $field_name != 'search_operator' && $field_name != 'devmode' ) ||
                   ( !empty($options_posts) && count($options_posts) > 0 ) ||
                   strpos($field_name, "liturgical") ) {
                    $troubleshooting .= "+++++<br />FIELD INFO<br/>+++++<br />".$field_info."<br />";
                }
                //$field_name == "liturgical_date" || $field_name == "repertoire_litdates" || 
                //if ( !empty($field_value) ) { $troubleshooting .= "+++++<br />FIELD INFO<br/>+++++<br />".$field_info."<br />"; }
                
            } // End conditional for actual search fields
            
        } // end foreach ( $arr_fields as $field_name )
        
        $info .= '<input type="submit" value="Search Library">';
        $info .= '<a href="#!" id="form_reset">Clear Form</a>';
        $info .= '</form>';
        
        
        // 
        $args_related = null; // init
        $mq_components = array();
        $tq_components = array();
        //$troubleshooting .= "mq_components_primary: <pre>".print_r($mq_components_primary,true)."</pre>"; // tft
        //$troubleshooting .= "tq_components_primary: <pre>".print_r($tq_components_primary,true)."</pre>"; // tft
        //$troubleshooting .= "mq_components_related: <pre>".print_r($mq_components_related,true)."</pre>"; // tft
        //$troubleshooting .= "tq_components_related: <pre>".print_r($tq_components_related,true)."</pre>"; // tft
        
        // If field values were found related to both post types,
        // AND if we're searching for posts that match ALL terms (search_operator: "and"),
        // then set up a second set of args/birdhive_get_posts
        if ( $search_primary_post_type == true && $search_related_post_type == true && $search_operator == "and" ) { 
            $troubleshooting .= "Querying both primary and related post_types (two sets of args)<br />";
            $args_related = $args;
            $args_related['post_type'] = $related_post_type; // reset post_type            
        } else if ( $search_primary_post_type == true && $search_related_post_type == true && $search_operator == "or" ) { 
            // WIP -- in this case
            $troubleshooting .= "Querying both primary and related post_types (two sets of args) but with OR operator... WIP<br />";
            //$args_related = $args;
            //$args_related['post_type'] = $related_post_type; // reset post_type            
        } else {
            if ( $search_primary_post_type == true ) {
                // Searching primary post_type only
                $troubleshooting .= "Searching primary post_type only<br />";
                $args['post_type'] = $post_type;
                $mq_components = $mq_components_primary;
                $tq_components = $tq_components_primary;
            } else if ( $search_related_post_type == true ) {
                // Searching related post_type only
                $troubleshooting .= "Searching related post_type only<br />";
                $args['post_type'] = $related_post_type;
                $mq_components = $mq_components_related;
                $tq_components = $tq_components_related;
            }
        }
        
        // Finalize meta_query or queries
        // ==============================
        /* 
        WIP if meta_key = title_clean and related_post_type is true then incorporate also, using title_clean meta_value:
        $args['_search_title'] = $field_value; // custom parameter -- see posts_where filter fcn
        */
        
        if ( empty($args_related) ) {
            
            if ( count($mq_components) > 1 && empty($meta_query['relation']) ) {
                $meta_query['relation'] = $search_operator;
            }
            if ( count($mq_components) == 1) {
                //$troubleshooting .= "Single mq_component.<br />";
                $meta_query = $mq_components; //$meta_query = $mq_components[0];
            } else {
                foreach ( $mq_components AS $component ) {
                    $meta_query[] = $component;
                }
            }
            
            if ( !empty($meta_query) ) { $args['meta_query'] = $meta_query; }
            
        } else {
            
            // TODO: eliminate redundancy!
            if ( count($mq_components_primary) > 1 && empty($meta_query['relation']) ) {
                $meta_query['relation'] = $search_operator;
            }
            if ( count($mq_components_primary) == 1) {                
                $meta_query = $mq_components_primary; //$meta_query = $mq_components_primary[0];
            } else {
                foreach ( $mq_components_primary AS $component ) {
                    $meta_query[] = $component;
                }
            }
            /*foreach ( $mq_components_primary AS $component ) {
                $meta_query[] = $component;
            }*/
            if ( !empty($meta_query) ) { $args['meta_query'] = $meta_query; }
            
            // related query
            if ( count($mq_components_related) > 1 && empty($meta_query_related['relation']) ) {
                $meta_query_related['relation'] = $search_operator;
            }
            if ( count($mq_components_related) == 1) {
                $meta_query_related = $mq_components_related; //$meta_query_related = $mq_components_related[0];
            } else {
                foreach ( $mq_components_related AS $component ) {
                    $meta_query_related[] = $component;
                }
            }
            /*foreach ( $mq_components_related AS $component ) {
                $meta_query_related[] = $component;
            }*/
            if ( !empty($meta_query_related) ) { $args_related['meta_query'] = $meta_query_related; }
            
        }
        
        
        // Finalize tax_query or queries
        // =============================
        if ( empty($args_related) ) {
            
            if ( count($tq_components) > 1 && empty($tax_query['relation']) ) {
                $tax_query['relation'] = $search_operator;
            }
            foreach ( $tq_components AS $component ) {
                $tax_query[] = $component;
            }
            if ( !empty($tax_query) ) { $args['tax_query'] = $tax_query; }
            
        } else {
            
            // TODO: eliminate redundancy!
            if ( count($tq_components_primary) > 1 && empty($tax_query['relation']) ) {
                $tax_query['relation'] = $search_operator;
            }
            foreach ( $tq_components_primary AS $component ) {
                $tax_query[] = $component;
            }
            if ( !empty($tax_query) ) { $args['tax_query'] = $tax_query; }
            
            // related query
            if ( count($tq_components_related) > 1 && empty($tax_query_related['relation']) ) {
                $tax_query_related['relation'] = $search_operator;
            }
            foreach ( $tq_components_related AS $component ) {
                $tax_query_related[] = $component;
            }
            if ( !empty($tax_query_related) ) { $args_related['tax_query'] = $tax_query_related; }
            
        }

        ///// WIP
        if ( $related_post_type ) {
            
            // If we're dealing with multiple post types, then the and/or is extra-complicated, because not all taxonomies apply to all post_types
            // Must be able to find, e.g., repertoire with composer: Mousezart as well as ("OR") all editions/rep with instrument: Bells
            
            if ( $search_operator == "or" ) {
                if ( !empty($tax_query) && !empty($meta_query) ) {
                    $args['_meta_or_tax'] = true; // custom parameter -- see posts_where filters
                }
            }
        }
        /////
        
        // If search values have been submitted, then run the search query
        if ( count($search_values) > 0 ) {
            
            $troubleshooting .= "About to pass args to birdhive_get_posts: <pre>".print_r($args,true)."</pre>"; // tft
            
            // Get posts matching the assembled args
            /* ===================================== */
            if ( $form_type == "advanced_search" ) {
                //$troubleshooting .= "<strong>NB: search temporarily disabled for troubleshooting.</strong><br />"; $posts_info = array(); // tft
                $posts_info = birdhive_get_posts( $args );
            } else {
                $posts_info = birdhive_get_posts( $args );
            }
            
            if ( isset($posts_info['arr_posts']) ) {
                
                $arr_post_ids = $posts_info['arr_posts']->posts; // Retrieves an array of IDs (based on return_fields: 'ids')
                $troubleshooting .= "Num arr_post_ids: [".count($arr_post_ids)."]<br />";
                //$troubleshooting .= "arr_post_ids: <pre>".print_r($arr_post_ids,true)."</pre>"; // tft
                
                $info .= '<div class="troubleshooting">'.$posts_info['info'].'</div>';
                //$troubleshooting .= $posts_info['info']."<hr />";
                //$info .= $posts_info['info']."<hr />"; //$info .= "birdhive_get_posts/posts_info: ".$posts_info['info']."<hr />";
                
                // Print last SQL query string
                global $wpdb;
                $info .= '<div class="troubleshooting">'."last_query:<pre>".$wpdb->last_query."</pre>".'</div>'; // tft
                //$troubleshooting .= "<p>last_query:</p><pre>".$wpdb->last_query."</pre>"; // tft
                
            }
            
            if ( $args_related ) {
                
                $troubleshooting .= "About to pass args_related to birdhive_get_posts: <pre>".print_r($args_related,true)."</pre>"; // tft
                
                $troubleshooting .= "<strong>NB: search temporarily disabled for troubleshooting.</strong><br />"; $related_posts_info = array(); // tft
                //$related_posts_info = birdhive_get_posts( $args_related );
                
                if ( isset($related_posts_info['arr_posts']) ) {
                
                    $arr_related_post_ids = $related_posts_info['arr_posts']->posts;
                    $troubleshooting .= "Num arr_related_post_ids: [".count($arr_related_post_ids)."]<br />";
                    //$troubleshooting .= "arr_related_post_ids: <pre>".print_r($arr_related_post_ids,true)."</pre>"; // tft

                    $troubleshooting .= $related_posts_info['info'];

                    // Print last SQL query string
                    global $wpdb;
                    $troubleshooting .= "last_query: <pre>".$wpdb->last_query."</pre>"; // tft
                    
                    // WIP -- we're running an "and" so we need to find the OVERLAP between the two sets of ids... one set of repertoire ids, one of editions... hmm...
                    if ( !empty($arr_post_ids) ) {
                        
                        $related_post_field_name = "repertoire_editions"; // TODO: generalize!
                        
                        $full_match_ids = array(); // init
                        
                        // Search through the smaller of the two data sets and find posts that overlap both sets; return only those
                        // TODO: eliminate redundancy
                        if ( count($arr_post_ids) > count($arr_related_post_ids) ) {
                            // more rep than edition records
                            $troubleshooting .= "more rep than edition records >> loop through arr_related_post_ids<br />";
                            foreach ( $arr_related_post_ids as $tmp_id ) {
                                $troubleshooting .= "tmp_id: $tmp_id<br />";
                                $tmp_posts = get_field($related_post_field_name, $tmp_id); // repertoire_editions
                                if ( empty($tmp_posts) ) { $tmp_posts = get_field('musical_work', $tmp_id); } // WIP/tmp
                                if ( $tmp_posts ) {
                                    foreach ( $tmp_posts as $tmp_match ) {
                                        // Get the ID
                                        if ( is_object($tmp_match) ) {
                                            $tmp_match_id = $tmp_match->ID;
                                        } else {
                                            $tmp_match_id = $tmp_match;
                                        }
                                        // Look
                                        if ( in_array($tmp_match_id, $arr_post_ids) ) {
                                            // it's a full match -- keep it
                                            $full_match_ids[] = $tmp_match_id;
                                            $troubleshooting .= "$related_post_field_name tmp_match_id: $tmp_match_id -- FOUND in arr_post_ids<br />";
                                        } else {
                                            $troubleshooting .= "$related_post_field_name tmp_match_id: $tmp_match_id -- NOT found in arr_post_ids<br />";
                                        }
                                    }
                                } else {
                                    $troubleshooting .= "No $related_post_field_name records found matching related_post_id $tmp_id<br />";
                                }
                            }
                        } else {
                            // more editions than rep records
                            $troubleshooting .= "more editions than rep records >> loop through arr_post_ids<br />";
                            foreach ( $arr_post_ids as $tmp_id ) {
                                $tmp_posts = get_field($related_post_field_name, $tmp_id); // repertoire_editions
                                if ( empty($tmp_posts) ) { $tmp_posts = get_field('related_editions', $tmp_id); } // WIP/tmp
                                if ( $tmp_posts ) {
                                    foreach ( $tmp_posts as $tmp_match ) {
                                        // Get the ID
                                        if ( is_object($tmp_match) ) {
                                            $tmp_match_id = $tmp_match->ID;
                                        } else {
                                            $tmp_match_id = $tmp_match;
                                        }
                                        // Look for a match in arr_post_ids
                                        if ( in_array($tmp_match_id, $arr_related_post_ids) ) {
                                            // it's a full match -- keep it
                                            $full_match_ids[] = $tmp_match_id;
                                        } else {
                                            $troubleshooting .= "$related_post_field_name tmp_match_id: $tmp_match_id -- NOT in arr_related_post_ids<br />";
                                        }
                                    }
                                }
                            }
                        }
                        //$arr_post_ids = array_merge($arr_post_ids, $arr_related_post_ids); // Merge $arr_related_posts into arr_post_ids -- nope, too simple
                        $arr_post_ids = $full_match_ids;
                        $troubleshooting .= "Num full_match_ids: [".count($full_match_ids)."]".'</div>';
                        
                    } else {
                        $arr_post_ids = $arr_related_post_ids;
                    }

                }
            }
            
            // 
            
            if ( !empty($arr_post_ids) ) {
                    
                $troubleshooting .= "Num matching posts found (raw results): [".count($arr_post_ids)."]"; // tft -- if there are both rep and editions, it will likely be an overcount
                $info .= format_search_results($arr_post_ids);

            } else {
                
                $info .= "No matching items found.<br />";
                
            } // END if ( !empty($arr_post_ids) )
            
            
            /*if ( isset($posts_info['arr_posts']) ) {
                
                $arr_posts = $posts_info['arr_posts'];//$posts_info['arr_posts']->posts; // Retrieves an array of WP_Post Objects
                
                $troubleshooting .= $posts_info['info']."<hr />";
                //$info .= $posts_info['info']."<hr />"; //$info .= "birdhive_get_posts/posts_info: ".$posts_info['info']."<hr />";
                
                if ( !empty($arr_posts) ) {
                    
                    $troubleshooting .= "Num matching posts found (raw results): [".count($arr_posts->posts)."]"; 
                    //$info .= '<div class="troubleshooting">'."Num matching posts found (raw results): [".count($arr_posts->posts)."]".'</div>'; // tft -- if there are both rep and editions, it will likely be an overcount
               
                    if ( count($arr_posts->posts) == 0 ) { // || $form_type == "advanced_search"
                        //$troubleshooting .= "args: <pre>".print_r($args,true)."</pre>"; // tft
                    }
                    
                    // Print last SQL query string
                    global $wpdb;
                    $troubleshooting .= "<p>last_query:</p><pre>".$wpdb->last_query."</pre>"; // tft

                    $info .= format_search_results($arr_posts);
                    
                } // END if ( !empty($arr_posts) )
                
            } else {
                $troubleshooting .= "No arr_posts retrieved.<br />";
            }*/
            
        } else {
            
            $troubleshooting .= "No search values submitted.<br />";
            
        }
        
        
    } // END if ( $a['fields'] )

    $info .= '<div class="troubleshooting">';
    $info .= $troubleshooting;
    $info .= '</div>';
    
    return $info;
    
}


?>