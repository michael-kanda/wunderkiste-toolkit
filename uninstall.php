<?php
/**
 * Wunderkiste Toolkit Uninstall
 *
 * Runs when the plugin is deleted from the WordPress admin.
 * Removes all plugin options and post meta from the database.
 *
 * @package Wunderkiste_Toolkit
 * @since 2.8
 */

// Security check: only run when called by WordPress
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/* ------------------------------------------------------------------------- *
 * CLEAN UP PER SITE
 * ------------------------------------------------------------------------- */

/**
 * Removes every option, meta value and transient the plugin created.
 *
 * @return void
 */
function seowk_uninstall_cleanup_site() {
    delete_option( 'seowk_settings' );
    delete_transient( 'seowk_activation_notice' );

    $meta_keys = array(
        // SEO Meta Settings.
        '_seowk_meta_title',
        '_seowk_meta_description',
        '_seowk_meta_robots',
        '_seowk_og_title',
        '_seowk_og_description',
        '_seowk_og_image',

        // Schema.
        '_seowk_schema_value',

        // NoIndex.
        '_seowk_noindex',

        // Conversion Tracker.
        '_seowk_ga4_conversion_enabled',
        '_seowk_ga4_conversion_event',
        '_seowk_ga4_conversion_value',
        '_seowk_ads_conversion_enabled',
        '_seowk_ads_conversion_id',
        '_seowk_ads_conversion_label',
        '_seowk_ads_conversion_value',

        // Decent Lightbox.
        'decent_lightbox_enabled',
    );

    foreach ( $meta_keys as $meta_key ) {
        delete_post_meta_by_key( $meta_key );
    }

    $user_meta_keys = array(
        'seowk_svg_notice_dismissed',
    );

    // delete_all = true removes the key for every user and keeps the meta cache consistent.
    foreach ( $user_meta_keys as $user_meta_key ) {
        delete_metadata( 'user', 0, $user_meta_key, '', true );
    }
}

/* ------------------------------------------------------------------------- *
 * RUN - SINGLE SITE OR MULTISITE
 * ------------------------------------------------------------------------- */

/*
 * The previous version only looped over sites to delete the option; post meta
 * was removed on the current site only, so every other site in a network kept
 * its rows.
 */
if ( is_multisite() ) {
    $seowk_site_ids = get_sites(
        array(
            'fields' => 'ids',
            'number' => 0,
        )
    );

    foreach ( $seowk_site_ids as $seowk_site_id ) {
        switch_to_blog( (int) $seowk_site_id );
        seowk_uninstall_cleanup_site();
        restore_current_blog();
    }
} else {
    seowk_uninstall_cleanup_site();
}
