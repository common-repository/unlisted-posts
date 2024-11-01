<?php
/**
     * Plugin Name: Unlisted Posts
     * Plugin URI: https://smartwp.co/unlisted-posts
     * Description: Simple plugin that adds an 'unlisted' option to posts.
     * Version: 1.1.0
     * Text Domain: unlisted_posts
     * Author: Andy Feliciotti
     * Author URI: https://smartwp.co
*/

function render_unlisted_posts() {
    $post_id = get_the_ID();
    if( get_post_type($post_id) != 'post' && get_post_type($post_id) != 'page' ) {
        return;
    }
    
    $unlisted_posts = get_option( 'unlisted_posts', array() );
    $unlisted_value = false;
    if( in_array($post_id, $unlisted_posts) ) {
        $unlisted_value = true;
    }
    
    wp_nonce_field( 'unlisted_posts_nonce_'.$post_id, 'unlisted_posts_nonce' );
    
    $html = '<div class="misc-pub-section misc-pub-section-last">
    <label><input type="checkbox" value="1"'.checked($unlisted_value, true, false).' name="_unlisted_post">'.__('Unlisted?', 'unlisted_posts').'</label>
    </div>';
    echo $html;
}

function unlisted_post_meta_box() {
    add_meta_box( 
        'unlisted-post-meta-box',
        __( 'Unlisted Post' ),
        'render_unlisted_posts',
        'post',
        'side'
    );
}
add_action( 'add_meta_boxes', 'unlisted_post_meta_box' );

function save_unlisted_post_data($post_id) {
    if( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
        return;
    }
    if( !isset($_POST['unlisted_posts_nonce']) ||
        !wp_verify_nonce( $_POST['unlisted_posts_nonce'], 'unlisted_posts_nonce_'.$post_id ) ) {
        return;
    }
    if( !current_user_can('edit_post', $post_id) ) {
        return;
    }
    
    $unlisted_posts = get_option( 'unlisted_posts', array() );
    
    if ( empty($unlisted_posts) && !is_array($unlisted_posts) ) {
        $unlisted_posts = array();
    }

    if( isset( $_POST['_unlisted_post'] ) ) {
        $unlisted_posts[] = $post_id;
    }elseif( in_array( $post_id, $unlisted_posts ) ) {
        $key = array_search( $post_id, $unlisted_posts );
        unset( $unlisted_posts[$key] );
    }
    
    $unlisted_posts = array_unique( $unlisted_posts );

    update_option( 'unlisted_posts', $unlisted_posts );
}
add_action( 'save_post', 'save_unlisted_post_data' );

//Hides the post from queries
function unlisted_posts_filter($query) {
    $unlisted_posts = get_option( 'unlisted_posts', array() );

    if( ( is_admin() && ! wp_doing_ajax() ) || $query->is_singular || empty( $unlisted_posts ) ) {
        return $query;
    }
    $query->set( 'post__not_in', $unlisted_posts );
    
    return $query;
}
add_filter( 'pre_get_posts', 'unlisted_posts_filter' );

//Hides the post from search engines (noindex)
function unlisted_posts_noindex($query) {
    $unlisted_posts = get_option( 'unlisted_posts', array() );

    if( !empty($unlisted_posts) && in_array(get_the_ID(), $unlisted_posts) ) {
        wp_no_robots();
    }
}
add_action( 'wp_head', 'unlisted_posts_noindex' );