<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ------------------------------------------------------------------------- *
 * MODULE: Comment Blocker (site-wide comment shutdown)
 * ------------------------------------------------------------------------- */

/**
 * Post types the comment blocker must leave alone.
 *
 * WooCommerce product reviews are stored as comments. Switching comments off
 * globally silently removes the review tab, the star rating and every existing
 * review from a shop, so products are exempt by default.
 *
 * @return string[]
 */
function seowk_comment_blocker_exempt_post_types() {
    $exempt = array();

    if ( class_exists( 'WooCommerce' ) || post_type_exists( 'product' ) ) {
        $exempt[] = 'product';
    }

    /**
     * Filter the post types excluded from the comment blocker.
     *
     * @param string[] $exempt Post type slugs.
     */
    return (array) apply_filters( 'seowk_comment_blocker_exempt_post_types', $exempt );
}

/**
 * Whether comments for this post should be left untouched.
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function seowk_comment_blocker_is_exempt( $post_id ) {
    return in_array( get_post_type( $post_id ), seowk_comment_blocker_exempt_post_types(), true );
}

function seowk_disable_comments_post_types_support() {
    $exempt     = seowk_comment_blocker_exempt_post_types();
    $post_types = get_post_types( array( 'public' => true ), 'names' );

    foreach ( $post_types as $post_type ) {
        if ( in_array( $post_type, $exempt, true ) ) {
            continue;
        }
        if ( post_type_supports( $post_type, 'comments' ) ) {
            remove_post_type_support( $post_type, 'comments' );
            remove_post_type_support( $post_type, 'trackbacks' );
        }
    }
}
add_action( 'init', 'seowk_disable_comments_post_types_support' );

function seowk_disable_comments_admin_bar() {
    global $wp_admin_bar;
    if ( is_object( $wp_admin_bar ) && method_exists( $wp_admin_bar, 'remove_menu' ) ) {
        $wp_admin_bar->remove_menu( 'comments' );
    }
}
add_action( 'wp_before_admin_bar_render', 'seowk_disable_comments_admin_bar' );

function seowk_disable_comments_admin_menu() {
    remove_menu_page( 'edit-comments.php' );
}
add_action( 'admin_menu', 'seowk_disable_comments_admin_menu' );

function seowk_disable_comments_dashboard() {
    remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
}
add_action( 'admin_init', 'seowk_disable_comments_dashboard' );

function seowk_disable_comments_edit_screen() {
    $exempt     = seowk_comment_blocker_exempt_post_types();
    $post_types = get_post_types( array( 'public' => true ), 'names' );
    foreach ( $post_types as $post_type ) {
        if ( in_array( $post_type, $exempt, true ) ) {
            continue;
        }
        remove_meta_box( 'commentstatusdiv', $post_type, 'normal' );
        remove_meta_box( 'commentsdiv', $post_type, 'normal' );
        remove_meta_box( 'trackbacksdiv', $post_type, 'normal' );
    }
}
add_action( 'admin_menu', 'seowk_disable_comments_edit_screen' );

function seowk_disable_existing_comments( $open, $post_id ) {
    if ( seowk_comment_blocker_is_exempt( $post_id ) ) {
        return $open;
    }
    return false;
}
add_filter( 'comments_open', 'seowk_disable_existing_comments', 20, 2 );
add_filter( 'pings_open', 'seowk_disable_existing_comments', 20, 2 );

function seowk_disable_comments_feed() {
    if ( is_comment_feed() ) {
        wp_die( esc_html__( 'Comments are disabled on this site.', 'wunderkiste-toolkit' ), '', array( 'response' => 403 ) );
    }
}
add_action( 'do_feed_rss2_comments', 'seowk_disable_comments_feed', 1 );
add_action( 'do_feed_atom_comments', 'seowk_disable_comments_feed', 1 );

/*
 * feed_links_extra() also emits category, tag and author feed links, so
 * removing it wholesale stripped far more than the comment feed. The comment
 * feed link is suppressed on its own instead.
 */
add_filter( 'feed_links_show_comments_feed', '__return_false' );

function seowk_disable_comments_widget() {
    unregister_widget( 'WP_Widget_Recent_Comments' );
}
add_action( 'widgets_init', 'seowk_disable_comments_widget' );

function seowk_hide_comments_admin_css() {
    seowk_add_inline_admin_css( '.column-comments { display: none !important; }' );
}
add_action( 'admin_enqueue_scripts', 'seowk_hide_comments_admin_css' );
