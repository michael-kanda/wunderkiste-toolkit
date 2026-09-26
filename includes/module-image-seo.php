<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ------------------------------------------------------------------------- *
 * MODULE: Zero-Click Image SEO
 * ------------------------------------------------------------------------- */

function seowk_auto_image_attributes( $post_ID ) {
    if ( ! wp_attachment_is_image( $post_ID ) ) { return; }
    $file_path = get_attached_file( $post_ID );
    $filename  = pathinfo( $file_path, PATHINFO_FILENAME );
    $clean_title = str_replace( array( '-', '_' ), ' ', $filename );
    $clean_title = ucwords( $clean_title );
    wp_update_post( array( 'ID' => $post_ID, 'post_title' => $clean_title ) );
    $existing_alt = get_post_meta( $post_ID, '_wp_attachment_image_alt', true );
    if ( empty( $existing_alt ) ) {
        update_post_meta( $post_ID, '_wp_attachment_image_alt', $clean_title );
    }
}
add_action( 'add_attachment', 'seowk_auto_image_attributes' );
