<?php
/**
 * Plugin Name:  Get Posts API
 * Description:  Gets the latest 5 posts from a blog via the REST API. Inserting new posts, users, categories, attachments.
 * Plugin URI:   https://github.com/evgenijab/get-post-api
 * Author:       Evgenija Butleska
 * Author URI: 	 https://evgenijab.github.io/
 * Version:      1.0
 * License:      GPL v2 or later
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @package getpostsviarestapi
 */

// Disable direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
register_activation_hook(__FILE__, 'get_posts_via_rest');
function get_posts_via_rest() {

	//Get the data from json
	$response = wp_remote_get( 'https://protonmail.com/blog/wp-json/wp/v2/posts?_embed&per_page=5' );	

	// Exit if error.
	if ( is_wp_error( $response ) ) {
		return;
	}

	// Get the body.
	$posts = json_decode( wp_remote_retrieve_body( $response ) );
	
	// Exit if nothing is returned.
	if ( empty( $posts ) ) {
		return;
	}

	// If there are posts.
	if ( ! empty( $posts ) ) {
				
		// For each post.
		foreach ( $posts as $post ) {

			$get_page = get_page_by_title( $post->title->rendered, OBJECT, 'post');
			$new_cat_id = $post->_embedded->{'wp:term'}[0][0]->id;
			$post_id = $post->id;

        	if ($get_page->post_title == NULL){ //check if the post already exists

/////////////////////////////////////////////////////////////////////////
///INSERT NEW USER/AUTHOR ////
        	$new_user=$post->_embedded->author[0]->name; // author name for the post
			$user_id = (get_user_by('login', $new_user)->ID); // need the id of current user
			if($user_id == 0) {
			    $userdata = array(
			    	'user_login' => $new_user,
			    	'role' => 'author',
			    	'user_nicename' => $post->_embedded->author[0]->slug,
			    	'description' => $post->_embedded->author[0]->description
			    	);
			   wp_insert_user( $userdata ); // insert new user
			}
			//Get ID of the user again incase a new one has been created
			$new_user_ID = get_user_by('login', $new_user)->ID;

 ////////////////////////////////////////////////////////////////////////////////////////////////////////////////       	
////INSERT NEW CATEGORY////
			$terms = $post->_embedded->{'wp:term'}[0];
			foreach ($terms as $term) {
				$term_name=$term->name;
				$category=$term_name; // category name for the post
			$cat_ID = get_cat_ID( $category ); // need the id of the category
			if($cat_ID == 0) {
			    $cat_name = array('cat_name' => $category);
			    wp_insert_category($cat_name); // insert new category
			}
			//Get ID of category again incase a new one has been created
			$new_cat_ID = get_cat_ID($category);
			}
////////////////////////////////////////////////////////////////////////////////////////////////////////////////       	
////INSERT NEW POST////        	
            $new_post = array(
            	//'ID' => $post_id,
                'post_title' => $post->title->rendered,
                'post_content' => $post->content->rendered,
                'post_name' => $post->slug,
                'post_date' => $post->date,
                'post_modified' => $post->modified,
                'comment_status' => $post->comment_status,
                'post_status' => 'draft',
                'post_author' => $new_user_ID,
                'post_category' => array($new_cat_ID),
                'post_type' => 'post'
            );
            // Insert post
           $post_id = wp_insert_post($new_post);
//////////////////////////////////////////////////////////////////////////////////////////////////////////////// 
////INSERT ATTACHMENTS (FEATURED IMAGES) /////
			$image_url        = $post->_embedded->{'wp:featuredmedia'}[0]->source_url; // Define the image URL here
			$image_name       = $post->_embedded->{'wp:featuredmedia'}[0]->media_details->file;
			$upload_dir       = wp_upload_dir(); // Set upload folder
			$image_data       = file_get_contents($image_url); // Get image data

			$unique_file_name = wp_unique_filename( $upload_dir['path'], $image_name ); // Generate unique name
			$filename         = basename( $unique_file_name ); // Create image file name

			// Check folder permission and define file location
			if( wp_mkdir_p( $upload_dir['path'] ) ) {
			    $file = $upload_dir['path'] . '/' . $filename;
			} else {
			    $file = $upload_dir['basedir'] . '/' . $filename;
			}

			// Create the image  file on the server
			file_put_contents( $file, $image_data );

			// Check image file type
			$wp_filetype = wp_check_filetype( $filename, null );
			
				
			
			// Set attachment data
			$attachment = array(
			    'post_mime_type' => $post->_embedded->{'wp:featuredmedia'}[0]->mime_type,
			    'post_title'     => $post->_embedded->{'wp:featuredmedia'}[0]->title->rendered,
			    //'post_content'   => '',
			    'post_status'    => 'inherit'
			);

			// Create the attachment
			$attach_id = wp_insert_attachment( $attachment, $file, $post_id );
			
			// Include image.php
			require_once(ABSPATH . 'wp-admin/includes/image.php');

			// Define attachment metadata
			$attach_data = wp_generate_attachment_metadata( $attach_id, $file );

			// Assign metadata to attachment
			wp_update_attachment_metadata( $attach_id, $attach_data );

			// Assign featured image to post
			set_post_thumbnail( $post_id, $attach_id );
            
            add_post_meta( $post_id, 'meta_key', $post->id );  
           // wp_set_object_terms( $post_id, $new_cat_ID, 'category');

        }
    
		else{
	        // Update meta value
	        update_post_meta($post_id, 'my_key', $post->id);
        }
			
		}

	}

}
add_action( 'admin_init', 'get_posts_via_rest' );