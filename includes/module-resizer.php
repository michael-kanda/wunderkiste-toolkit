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

function seowk_resizer_admin_footer_script() {
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
    ?>
    <script>
    jQuery(document).ready(function($) {
        var cfg = <?php echo wp_json_encode( $config ); ?>;
        var t = cfg.i18n;

        // Single image resize
        $(document).on('click', '.seowk-resizer-trigger', function(e) {
            e.preventDefault();
            var btn = $(this),
                container = btn.closest('td, .setting, .compat-field-seowk_resizer_resize'),
                status = container.find('.seowk-resizer-status'),
                sizeDisplay = container.find('.seowk-resizer-current-size');
            var id = btn.data('id'), size = btn.data('size') || 800, nonce = btn.data('nonce');

            if (!confirm(t.confirmSingle.replace('%d', size))) return;

            container.find('.seowk-resizer-trigger').prop('disabled', true);
            status.text(t.working).css('color', '#2271b1').show();

            $.post(ajaxurl, {
                action: 'seowk_resizer_resize_image',
                attachment_id: id,
                target_size: size,
                security: nonce
            }, function(r) {
                if (r.success) {
                    status.text(t.done + ' ' + r.data.dimensions).css('color', 'green');
                    // Update the size label
                    if (sizeDisplay.length) {
                        sizeDisplay.empty().append(document.createTextNode(t.current + ' '), $('<strong>').text(r.data.dimensions));
                    }
                    // Re-enable the buttons after 3 seconds
                    setTimeout(function() {
                        container.find('.seowk-resizer-trigger').prop('disabled', false);
                        status.fadeOut();
                    }, 3000);
                } else {
                    status.text('✗ ' + (r.data || t.error)).css('color', 'red');
                    container.find('.seowk-resizer-trigger').prop('disabled', false);
                }
            }).fail(function() {
                status.text('✗ ' + t.connection).css('color', 'red');
                container.find('.seowk-resizer-trigger').prop('disabled', false);
            });
        });

        // Bulk Action Handler
        $(document).on('click', '#doaction, #doaction2', function(e) {
            var action = $(this).prev('select').val();
            if (action !== 'seowk_resizer_bulk_800' && action !== 'seowk_resizer_bulk_1200') return;

            e.preventDefault();
            var size = action === 'seowk_resizer_bulk_800' ? 800 : 1200;
            var checked = $('input[name="media[]"]:checked');

            if (checked.length === 0) {
                alert(t.selectOne);
                return;
            }

            if (!confirm(t.confirmBulk.replace('%d', size))) {
                return;
            }

            var ids = [];
            checked.each(function() { ids.push($(this).val()); });

            // Build the progress modal
            var modal = $('<div id="seowk-resizer-bulk-modal" style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.7);z-index:100000;display:flex;align-items:center;justify-content:center;">' +
                '<div style="background:#fff;padding:30px;border-radius:8px;max-width:500px;width:90%;box-shadow:0 4px 20px rgba(0,0,0,0.3);">' +
                '<h2 style="margin:0 0 20px;color:#1d2327;"></h2>' +
                '<div class="seowk-resizer-progress-bar" style="background:#ddd;height:24px;border-radius:12px;overflow:hidden;margin-bottom:15px;">' +
                '<div class="seowk-resizer-progress-fill" style="background:linear-gradient(90deg,#2271b1,#135e96);height:100%;width:0%;transition:width 0.3s;"></div></div>' +
                '<div class="seowk-resizer-progress-text" style="text-align:center;font-size:14px;color:#50575e;"></div>' +
                '<div class="seowk-resizer-progress-log" style="max-height:200px;overflow-y:auto;margin-top:15px;font-size:12px;background:#f6f7f7;padding:10px;border-radius:4px;"></div>' +
                '</div></div>');
            modal.find('h2').text('🖼️ ' + t.scaling);
            $('body').append(modal);

            var processed = 0, success = 0, errors = 0;
            var progressFill = modal.find('.seowk-resizer-progress-fill');
            var progressText = modal.find('.seowk-resizer-progress-text').text('0 / ' + ids.length);
            var progressLog = modal.find('.seowk-resizer-progress-log');

            // Titles and server messages are inserted as text, never as HTML.
            function logLine(color, text) {
                progressLog.prepend($('<div>').css({ color: color, marginBottom: '3px' }).text(text));
            }

            function processNext(index) {
                if (index >= ids.length) {
                    // Done
                    progressText.empty().append(
                        $('<strong>').css('color', '#00a32a').text('✓ ' + t.finished),
                        document.createTextNode(' ' + success + ' ' + t.successful + ', ' + errors + ' ' + t.errors)
                    );
                    setTimeout(function() {
                        modal.fadeOut(300, function() {
                            $(this).remove();
                            location.reload();
                        });
                    }, 2000);
                    return;
                }

                var id = ids[index];
                var row = $('input[name="media[]"][value="' + parseInt(id, 10) + '"]').closest('tr');
                var title = row.find('.title a').text() || row.find('.column-title strong').text() || 'ID: ' + id;

                $.post(ajaxurl, {
                    action: 'seowk_resizer_resize_image',
                    attachment_id: id,
                    target_size: size,
                    security: cfg.bulkNonce
                }, function(r) {
                    processed++;
                    var percent = Math.round((processed / ids.length) * 100);
                    progressFill.css('width', percent + '%');
                    progressText.text(processed + ' / ' + ids.length);

                    if (r.success) {
                        success++;
                        logLine('#00a32a', '✓ ' + title + ' → ' + r.data.dimensions);
                    } else {
                        errors++;
                        logLine('#d63638', '✗ ' + title + ': ' + (r.data || t.error));
                    }

                    // Next image after a short pause
                    setTimeout(function() { processNext(index + 1); }, 200);
                }).fail(function() {
                    processed++;
                    errors++;
                    logLine('#d63638', '✗ ' + title + ': ' + t.connection);
                    setTimeout(function() { processNext(index + 1); }, 200);
                });
            }

            processNext(0);
        });
    });
    </script>
    <?php
}
add_action( 'admin_footer', 'seowk_resizer_admin_footer_script' );

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

function seowk_resizer_list_css() {
    $screen = get_current_screen();
    if ( ! $screen || 'upload' !== $screen->base ) {
        return;
    }
    echo '<style>
        .column-seowk_resizer_action { width: 120px; }
        .seowk-resizer-container .button { min-width: 45px; padding: 0 8px; }
        .seowk-resizer-current-size strong { color: #1d2327; }
    </style>';
}
add_action( 'admin_head', 'seowk_resizer_list_css' );
