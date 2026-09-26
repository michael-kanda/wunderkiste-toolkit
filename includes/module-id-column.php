<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ------------------------------------------------------------------------- *
 * MODULE: ID Column Display
 * ------------------------------------------------------------------------- */

function seowk_add_id_column( $columns ) {
    $new_columns = array();
    foreach ( $columns as $key => $value ) {
        $new_columns[ $key ] = $value;
        if ( $key === 'cb' ) { $new_columns['seowk_id'] = 'ID'; }
    }
    return $new_columns;
}
add_filter( 'manage_posts_columns', 'seowk_add_id_column' );
add_filter( 'manage_pages_columns', 'seowk_add_id_column' );
add_filter( 'manage_media_columns', 'seowk_add_id_column' );

function seowk_add_id_column_to_cpts() {
    $post_types = get_post_types( array( 'public' => true, '_builtin' => false ), 'names' );
    foreach ( $post_types as $post_type ) {
        add_filter( "manage_{$post_type}_posts_columns", 'seowk_add_id_column' );
    }
}
add_action( 'admin_init', 'seowk_add_id_column_to_cpts' );

function seowk_fill_id_column( $column_name, $post_id ) {
    if ( 'seowk_id' === $column_name ) {
        echo '<strong style="color: #2271b1;">' . esc_html( $post_id ) . '</strong>';
    }
}
add_action( 'manage_posts_custom_column', 'seowk_fill_id_column', 10, 2 );
add_action( 'manage_pages_custom_column', 'seowk_fill_id_column', 10, 2 );
add_action( 'manage_media_custom_column', 'seowk_fill_id_column', 10, 2 );

function seowk_make_id_column_sortable( $columns ) {
    $columns['seowk_id'] = 'ID';
    return $columns;
}
add_filter( 'manage_edit-post_sortable_columns', 'seowk_make_id_column_sortable' );
add_filter( 'manage_edit-page_sortable_columns', 'seowk_make_id_column_sortable' );
add_filter( 'manage_upload_sortable_columns', 'seowk_make_id_column_sortable' );

function seowk_id_column_enqueue_assets( $hook_suffix ) {
    if ( 'edit.php' !== $hook_suffix && 'upload.php' !== $hook_suffix ) {
        return;
    }

    seowk_add_inline_admin_css(
        '.column-seowk_id { width: 60px !important; text-align: center; }
        @media screen and (max-width: 782px) { .column-seowk_id { display: none; } }
        .column-seowk_id strong { cursor: pointer; }
        .column-seowk_id strong:hover { color: #135e96; }'
    );

    wp_enqueue_script( 'seowk-id-column', SEOWK_PLUGIN_URL . 'assets/js/id-column.js', array( 'jquery' ), SEOWK_VERSION, true );
    wp_add_inline_script(
        'seowk-id-column',
        'window.seowkIdColumn = ' . wp_json_encode(
            array(
                /* translators: %s: post ID */
                'copyTitle' => __( 'Click to copy: %s', 'wunderkiste-toolkit' ),
            )
        ) . ';',
        'before'
    );
}
add_action( 'admin_enqueue_scripts', 'seowk_id_column_enqueue_assets' );
