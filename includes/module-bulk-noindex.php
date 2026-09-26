<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ------------------------------------------------------------------------- *
 * MODULE: Bulk NoIndex Manager
 * Version: 2.9 - with Quick Edit support
 * ------------------------------------------------------------------------- */

// Add the bulk actions
function seowk_add_bulk_noindex_actions( $bulk_actions ) {
    $bulk_actions['seowk_set_noindex'] = __( 'Set NoIndex (SEO)', 'wunderkiste-toolkit' );
    $bulk_actions['seowk_remove_noindex'] = __( 'Remove NoIndex (SEO)', 'wunderkiste-toolkit' );
    return $bulk_actions;
}
add_filter( 'bulk_actions-edit-post', 'seowk_add_bulk_noindex_actions' );
add_filter( 'bulk_actions-edit-page', 'seowk_add_bulk_noindex_actions' );

// Handle the bulk actions
function seowk_handle_bulk_noindex( $redirect_to, $action, $post_ids ) {
    if ( 'seowk_set_noindex' !== $action && 'seowk_remove_noindex' !== $action ) {
        return $redirect_to;
    }

    $changed = 0;

    foreach ( (array) $post_ids as $post_id ) {
        $post_id = (int) $post_id;

        /*
         * WordPress only verifies the bulk nonce for custom bulk actions - it
         * does not check per-object rights. Without this, any user who can
         * reach edit.php could submit arbitrary IDs and de-index the whole
         * site.
         */
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            continue;
        }

        if ( 'seowk_set_noindex' === $action ) {
            update_post_meta( $post_id, '_seowk_noindex', '1' );
        } else {
            delete_post_meta( $post_id, '_seowk_noindex' );
        }

        $changed++;
    }

    $arg = ( 'seowk_set_noindex' === $action ) ? 'seowk_noindex_set' : 'seowk_noindex_removed';

    // Drop the counter of a previous run so both notices never show at once.
    $redirect_to = remove_query_arg( array( 'seowk_noindex_set', 'seowk_noindex_removed' ), $redirect_to );

    return add_query_arg( $arg, $changed, $redirect_to );
}
add_filter( 'handle_bulk_actions-edit-post', 'seowk_handle_bulk_noindex', 10, 3 );
add_filter( 'handle_bulk_actions-edit-page', 'seowk_handle_bulk_noindex', 10, 3 );

// Remove the counter from the address bar so a reload does not repeat the notice.
function seowk_bulk_noindex_removable_args( $args ) {
    $args[] = 'seowk_noindex_set';
    $args[] = 'seowk_noindex_removed';
    return $args;
}
add_filter( 'removable_query_args', 'seowk_bulk_noindex_removable_args' );

// Admin notice for the bulk actions
function seowk_bulk_noindex_admin_notice() {
    /*
     * Read-only display of the counter that seowk_handle_bulk_noindex() put
     * into the redirect URL. The bulk action itself is nonce-checked by core;
     * the worst a forged link can do here is show a wrong number.
     */
    // phpcs:disable WordPress.Security.NonceVerification.Recommended
    if ( ! empty( $_GET['seowk_noindex_set'] ) ) {
        $count = absint( $_GET['seowk_noindex_set'] );
        printf(
            '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
            esc_html(
                sprintf(
                    /* translators: %d: number of posts */
                    _n( 'NoIndex set for %d item.', 'NoIndex set for %d items.', $count, 'wunderkiste-toolkit' ),
                    $count
                )
            )
        );
    }
    if ( ! empty( $_GET['seowk_noindex_removed'] ) ) {
        $count = absint( $_GET['seowk_noindex_removed'] );
        printf(
            '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
            esc_html(
                sprintf(
                    /* translators: %d: number of posts */
                    _n( 'NoIndex removed for %d item.', 'NoIndex removed for %d items.', $count, 'wunderkiste-toolkit' ),
                    $count
                )
            )
        );
    }
    // phpcs:enable WordPress.Security.NonceVerification.Recommended
}
add_action( 'admin_notices', 'seowk_bulk_noindex_admin_notice' );

// Add the column
function seowk_add_noindex_column( $columns ) {
    $columns['seowk_noindex_status'] = '<span title="NoIndex Status">🔍 NoIndex</span>';
    return $columns;
}
add_filter( 'manage_posts_columns', 'seowk_add_noindex_column' );
add_filter( 'manage_pages_columns', 'seowk_add_noindex_column' );

// Fill the column
function seowk_fill_noindex_column( $column_name, $post_id ) {
    if ( 'seowk_noindex_status' === $column_name ) {
        $is_noindex = get_post_meta( $post_id, '_seowk_noindex', true );
        if ( $is_noindex ) {
            echo '<span style="color: #d63638; font-weight: bold;" title="' . esc_attr__( 'Not indexed by search engines', 'wunderkiste-toolkit' ) . '">✗ NoIndex</span>';
        } else {
            echo '<span style="color: #00a32a;" title="' . esc_attr__( 'Indexed by search engines', 'wunderkiste-toolkit' ) . '">✓ Index</span>';
        }
    }
}
add_action( 'manage_posts_custom_column', 'seowk_fill_noindex_column', 10, 2 );
add_action( 'manage_pages_custom_column', 'seowk_fill_noindex_column', 10, 2 );

/*
 * Robots directives go through the core wp_robots filter so the document
 * contains a single robots meta tag. Priority 20 runs after the Meta
 * Settings module, so a NoIndex flag always wins.
 */
function seowk_noindex_wp_robots( $robots ) {
    if ( ! is_singular() || ! get_post_meta( get_the_ID(), '_seowk_noindex', true ) ) {
        return $robots;
    }

    unset( $robots['index'], $robots['follow'], $robots['max-image-preview'], $robots['max-snippet'], $robots['max-video-preview'] );
    $robots['noindex']  = true;
    $robots['nofollow'] = true;

    return $robots;
}
add_filter( 'wp_robots', 'seowk_noindex_wp_robots', 20 );

// Column CSS and Quick Edit script
function seowk_noindex_enqueue_assets( $hook_suffix ) {
    if ( 'edit.php' !== $hook_suffix ) {
        return;
    }
    $screen = get_current_screen();
    if ( ! $screen || ! in_array( $screen->post_type, array( 'post', 'page' ), true ) ) {
        return;
    }

    seowk_add_inline_admin_css( '.column-seowk_noindex_status { width: 100px; text-align: center; }' );
    wp_enqueue_script( 'seowk-noindex-quick-edit', SEOWK_PLUGIN_URL . 'assets/js/noindex-quick-edit.js', array( 'jquery', 'inline-edit-post' ), SEOWK_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'seowk_noindex_enqueue_assets' );

/* ------------------------------------------------------------------------- *
 * QUICK EDIT SUPPORT
 * ------------------------------------------------------------------------- */

// Add the Quick Edit field
function seowk_add_quick_edit_noindex( $column_name, $post_type ) {
    if ( $column_name !== 'seowk_noindex_status' ) {
        return;
    }
    if ( ! in_array( $post_type, array( 'post', 'page' ), true ) ) {
        return;
    }
    ?>
    <fieldset class="inline-edit-col-right">
        <div class="inline-edit-col">
            <label class="inline-edit-seowk-noindex">
                <input type="checkbox" name="seowk_noindex" value="1">
                <span class="checkbox-title"><?php esc_html_e( 'NoIndex (do not index)', 'wunderkiste-toolkit' ); ?></span>
            </label>
        </div>
    </fieldset>
    <?php
}
add_action( 'quick_edit_custom_box', 'seowk_add_quick_edit_noindex', 10, 2 );

// Save Quick Edit
function seowk_save_quick_edit_noindex( $post_id ) {
    // Skip autosaves
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    
    // Check permissions
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    
    // Quick Edit only (not on a regular save)
    if ( ! isset( $_POST['_inline_edit'] ) ) {
        return;
    }
    
    // Verify the nonce
    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_inline_edit'] ) ), 'inlineeditnonce' ) ) {
        return;
    }
    
    // Save or delete NoIndex
    if ( isset( $_POST['seowk_noindex'] ) && $_POST['seowk_noindex'] === '1' ) {
        update_post_meta( $post_id, '_seowk_noindex', '1' );
    } else {
        delete_post_meta( $post_id, '_seowk_noindex' );
    }
}
add_action( 'save_post', 'seowk_save_quick_edit_noindex' );

// Hidden field for JavaScript access
function seowk_add_noindex_inline_data( $column_name, $post_id ) {
    if ( $column_name !== 'seowk_noindex_status' ) {
        return;
    }
    $is_noindex = get_post_meta( $post_id, '_seowk_noindex', true ) ? '1' : '0';
    echo '<div class="seowk-noindex-data hidden" data-noindex="' . esc_attr( $is_noindex ) . '"></div>';
}
add_action( 'manage_posts_custom_column', 'seowk_add_noindex_inline_data', 11, 2 );
add_action( 'manage_pages_custom_column', 'seowk_add_noindex_inline_data', 11, 2 );
