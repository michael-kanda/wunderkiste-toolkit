<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ------------------------------------------------------------------------- *
 * MODULE: Image Resizer 800px / 1200px with bulk action
 * Version: 2.9.1 - with live update and bulk editing
 * ------------------------------------------------------------------------- */

/**
 * Whether the current user may resize this specific attachment.
 *
 * @param int $attachment_id Attachment ID.
 * @return bool
 */
function seowk_resizer_user_may_resize( $attachment_id ) {
    $attachment_id = (int) $attachment_id;

    if ( $attachment_id <= 0 || ! current_user_can( 'upload_files' ) ) {
        return false;
    }

    return current_user_can( 'edit_post', $attachment_id );
}

/**
 * Size label plus the two resize buttons.
 *
 * @param int    $post_id Attachment ID.
 * @param string $context 'details' (attachment modal/edit screen) or 'list' (media list column).
 * @return string Escaped HTML.
 */
function seowk_resizer_buttons_html( $post_id, $context ) {
    $post_id  = (int) $post_id;
    $nonce    = wp_create_nonce( 'seowk_resizer_resize_' . $post_id );
    $metadata = wp_get_attachment_metadata( $post_id );
    $is_list  = ( 'list' === $context );

    $html = '';
    if ( $metadata && isset( $metadata['width'], $metadata['height'] ) ) {
        $dimensions = sprintf( '%d × %d px', (int) $metadata['width'], (int) $metadata['height'] );
        if ( $is_list ) {
            $html .= '<span class="seowk-resizer-current-size" style="font-size:11px;color:#666;display:block;margin-bottom:4px;">' . esc_html( $dimensions ) . '</span>';
        } else {
            $html .= '<span class="seowk-resizer-current-size" style="color:#666; font-size:12px;">'
                . esc_html__( 'Current:', 'wunderkiste-toolkit' ) . ' <strong>' . esc_html( $dimensions ) . '</strong></span><br>';
        }
    }

    $html .= '<div class="seowk-resizer-container" style="display: flex; gap: ' . ( $is_list ? '4px' : '8px; margin-bottom: 8px' ) . ';">';
    foreach ( array( 800, 1200 ) as $size ) {
        $html .= sprintf(
            '<button type="button" class="button button-small seowk-resizer-trigger" data-id="%1$d" data-size="%2$d" data-nonce="%3$s">%4$s</button>',
            $post_id,
            $size,
            esc_attr( $nonce ),
            esc_html( $is_list ? (string) $size : $size . 'px' )
        );
    }
    $html .= '</div>';

    if ( ! $is_list ) {
        $html .= '<p class="description">' . esc_html__( 'Overwrites the original image (92% quality).', 'wunderkiste-toolkit' ) . '</p>';
    }
    $html .= '<span class="seowk-resizer-status" style="display:none; font-size:' . ( $is_list ? '11px' : '13px' ) . '; margin-top:2px; font-weight:bold;"></span>';

    return $html;
}

function seowk_resizer_add_resize_button( $form_fields, $post ) {
    if ( ! wp_attachment_is_image( $post->ID ) || ! seowk_resizer_user_may_resize( $post->ID ) ) { return $form_fields; }
    $form_fields['seowk_resizer_resize'] = array(
        'label' => __( 'Scaling', 'wunderkiste-toolkit' ),
        'input' => 'html',
        'html'  => seowk_resizer_buttons_html( $post->ID, 'details' ),
    );
    return $form_fields;
}
add_filter( 'attachment_fields_to_edit', 'seowk_resizer_add_resize_button', 10, 2 );

function seowk_resizer_enqueue_assets() {
    $screen = get_current_screen();
    if ( ! $screen || ( $screen->base !== 'upload' && $screen->base !== 'post' && $screen->id !== 'attachment' ) ) {
        return;
    }

    $config = array(
        'bulkNonce' => wp_create_nonce( 'seowk_resizer_bulk_resize' ),
        'i18n'      => array(
            /* translators: %d: target width/height in pixels */
            'confirmSingle' => __( 'Scale image down to %dpx? The original will be overwritten.', 'wunderkiste-toolkit' ),
            /* translators: %d: target width/height in pixels */
            'confirmBulk'   => __( 'Scale selected images down to %dpx? The originals will be overwritten!', 'wunderkiste-toolkit' ),
            'working'       => __( '⏳ Working...', 'wunderkiste-toolkit' ),
            'done'          => __( '✓ Done:', 'wunderkiste-toolkit' ),
            'current'       => __( 'Current:', 'wunderkiste-toolkit' ),
            'error'         => __( 'Error', 'wunderkiste-toolkit' ),
            'connection'    => __( 'Connection error', 'wunderkiste-toolkit' ),
            'selectOne'     => __( 'Please select at least one image.', 'wunderkiste-toolkit' ),
            'scaling'       => __( 'Scaling images...', 'wunderkiste-toolkit' ),
            'finished'      => __( 'Done!', 'wunderkiste-toolkit' ),
            'successful'    => __( 'successful', 'wunderkiste-toolkit' ),
            'errors'        => __( 'Error', 'wunderkiste-toolkit' ),
        ),
    );

    wp_enqueue_script( 'seowk-resizer', SEOWK_PLUGIN_URL . 'assets/js/resizer.js', array( 'jquery' ), SEOWK_VERSION, true );
    wp_add_inline_script( 'seowk-resizer', 'window.seowkResizer = ' . wp_json_encode( $config ) . ';', 'before' );

    if ( 'upload' === $screen->base ) {
        seowk_add_inline_admin_css(
            '.column-seowk_resizer_action { width: 120px; }
            .seowk-resizer-container .button { min-width: 45px; padding: 0 8px; }
            .seowk-resizer-current-size strong { color: #1d2327; }'
        );
    }
}
add_action( 'admin_enqueue_scripts', 'seowk_resizer_enqueue_assets' );

// Add bulk actions to the media library
function seowk_resizer_add_bulk_actions( $bulk_actions ) {
    $bulk_actions['seowk_resizer_bulk_800'] = __( '🖼️ Scale to 800px', 'wunderkiste-toolkit' );
    $bulk_actions['seowk_resizer_bulk_1200'] = __( '🖼️ Scale to 1200px', 'wunderkiste-toolkit' );
    return $bulk_actions;
}
add_filter( 'bulk_actions-upload', 'seowk_resizer_add_bulk_actions' );

function seowk_resizer_ajax_resize_image() {
    // Validation
    if ( ! isset( $_POST['attachment_id'] ) || ! isset( $_POST['security'] ) ) {
        wp_send_json_error( __( 'Invalid request.', 'wunderkiste-toolkit' ), 400 );
    }

    $attachment_id = absint( wp_unslash( $_POST['attachment_id'] ) );
    $target_size = isset( $_POST['target_size'] ) ? absint( wp_unslash( $_POST['target_size'] ) ) : 800;
    $nonce = sanitize_text_field( wp_unslash( $_POST['security'] ) );

    if ( ! in_array( $target_size, array( 800, 1200 ), true ) ) { $target_size = 800; }

    /*
     * Both nonces are accepted, but which one is checked is not decided by a
     * request parameter the caller controls. Neither nonce authorises
     * anything on its own - the capability check below does.
     */
    $nonce_valid = wp_verify_nonce( $nonce, 'seowk_resizer_resize_' . $attachment_id )
        || wp_verify_nonce( $nonce, 'seowk_resizer_bulk_resize' );

    if ( ! $nonce_valid ) {
        wp_send_json_error( __( 'Security check failed. Please reload the page.', 'wunderkiste-toolkit' ), 403 );
    }

    /*
     * upload_files alone is not enough: Authors have it, and the resize
     * overwrites the original file irreversibly. Edit rights on this specific
     * attachment are required.
     */
    if ( ! seowk_resizer_user_may_resize( $attachment_id ) ) {
        wp_send_json_error( __( 'You are not allowed to edit this image.', 'wunderkiste-toolkit' ), 403 );
    }

    // Make sure it is an image
    if ( ! wp_attachment_is_image( $attachment_id ) ) {
        wp_send_json_error( __( 'Not an image.', 'wunderkiste-toolkit' ) );
    }

    $path = get_attached_file( $attachment_id );
    if ( ! $path || ! file_exists( $path ) ) {
        wp_send_json_error( __( 'File not found.', 'wunderkiste-toolkit' ) );
    }

    $editor = wp_get_image_editor( $path );
    if ( is_wp_error( $editor ) ) {
        wp_send_json_error( __( 'Image error.', 'wunderkiste-toolkit' ) );
    }

    $editor->set_quality( 92 );
    $size = $editor->get_size();

    if ( $size['width'] <= $target_size && $size['height'] <= $target_size ) {
        wp_send_json_error( sprintf(
            /* translators: %d: target width/height in pixels */
            __( 'Already %dpx or smaller.', 'wunderkiste-toolkit' ),
            $target_size
        ) );
    }

    $resized = $editor->resize( $target_size, $target_size, false );
    if ( is_wp_error( $resized ) ) {
        wp_send_json_error( __( 'Resize error.', 'wunderkiste-toolkit' ) );
    }

    $saved = $editor->save( $path );
    if ( is_wp_error( $saved ) ) {
        wp_send_json_error( __( 'Save error.', 'wunderkiste-toolkit' ) );
    }

    // Update the metadata
    $metadata = wp_generate_attachment_metadata( $attachment_id, $path );
    wp_update_attachment_metadata( $attachment_id, $metadata );

    $new_size = $editor->get_size();
    wp_send_json_success( array(
        'dimensions' => $new_size['width'] . ' × ' . $new_size['height'] . ' px',
        'width'      => $new_size['width'],
        'height'     => $new_size['height'],
    ) );
}
add_action( 'wp_ajax_seowk_resizer_resize_image', 'seowk_resizer_ajax_resize_image' );

function seowk_resizer_add_list_column( $columns ) {
    $columns['seowk_resizer_action'] = __( 'Resizer', 'wunderkiste-toolkit' );
    return $columns;
}
add_filter( 'manage_upload_columns', 'seowk_resizer_add_list_column' );

function seowk_resizer_fill_list_column( $column_name, $post_id ) {
    if ( 'seowk_resizer_action' !== $column_name ) return;
    if ( wp_attachment_is_image( $post_id ) && seowk_resizer_user_may_resize( $post_id ) ) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- every part is escaped in seowk_resizer_buttons_html().
        echo seowk_resizer_buttons_html( $post_id, 'list' );
    } else {
        echo '<span style="color:#999;">—</span>';
    }
}
add_action( 'manage_media_custom_column', 'seowk_resizer_fill_list_column', 10, 2 );

