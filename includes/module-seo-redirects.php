<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ------------------------------------------------------------------------- *
 * MODULE: SEO Zombie Killer (Attachment Redirects)
 * ------------------------------------------------------------------------- */

function seowk_redirect_attachment_pages() {
    if ( is_attachment() ) {
        global $post;
        if ( $post && $post->post_parent ) {
            wp_safe_redirect( get_permalink( $post->post_parent ), 301 );
            exit;
        } else {
            wp_safe_redirect( home_url( '/' ), 302 );
            exit;
        }
    }
}
add_action( 'template_redirect', 'seowk_redirect_attachment_pages' );
