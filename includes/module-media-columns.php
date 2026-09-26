<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ------------------------------------------------------------------------- *
 * MODULE: Media Library Inspector
 * ------------------------------------------------------------------------- */

function seowk_add_media_columns( $columns ) {
    $columns['seowk_filesize']   = __( 'File size', 'wunderkiste-toolkit' );
    $columns['seowk_dimensions'] = __( 'Dimensions (px)', 'wunderkiste-toolkit' );
    return $columns;
}
add_filter( 'manage_upload_columns', 'seowk_add_media_columns' );

function seowk_fill_media_columns( $column_name, $post_id ) {
    $file_path = get_attached_file( $post_id );
    if ( 'seowk_filesize' === $column_name ) {
        if ( file_exists( $file_path ) ) {
            echo esc_html( size_format( filesize( $file_path ), 2 ) );
        } else { echo '<span style="color:#ccc;">—</span>'; }
    }
    if ( 'seowk_dimensions' === $column_name ) {
        $meta = wp_get_attachment_metadata( $post_id );
        if ( isset( $meta['width'] ) && isset( $meta['height'] ) ) {
            echo esc_html( $meta['width'] . ' x ' . $meta['height'] );
        } else { echo '<span style="color:#ccc;">—</span>'; }
    }
}
add_action( 'manage_media_custom_column', 'seowk_fill_media_columns', 10, 2 );

function seowk_media_columns_css( $hook_suffix ) {
    if ( 'upload.php' === $hook_suffix ) {
        seowk_add_inline_admin_css( '.column-seowk_filesize, .column-seowk_dimensions { width: 100px; }' );
    }
}
add_action( 'admin_enqueue_scripts', 'seowk_media_columns_css' );
