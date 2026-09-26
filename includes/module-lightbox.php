<?php
/**
 * Module: Decent Lightbox
 *
 * Lightweight vanilla JS lightbox for media library images.
 * Enabled per image in the media library (meta checkbox).
 *
 * Originally developed as the standalone plugin "Decent Lightbox",
 * merged into Wunderkiste Toolkit in version 2.10.
 *
 * @package Wunderkiste_Toolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'SEOWK_LIGHTBOX_VERSION' ) ) {
    define( 'SEOWK_LIGHTBOX_VERSION', SEOWK_VERSION );
    define( 'SEOWK_LIGHTBOX_FILE', __FILE__ );
    define( 'SEOWK_LIGHTBOX_PATH', SEOWK_PLUGIN_DIR . 'includes/lightbox/' );
    define( 'SEOWK_LIGHTBOX_URL',  SEOWK_PLUGIN_URL  . 'includes/lightbox/' );
    define( 'SEOWK_LIGHTBOX_META_KEY', 'decent_lightbox_enabled' );

    require_once SEOWK_LIGHTBOX_PATH . 'class-seowk-lightbox.php';
    require_once SEOWK_LIGHTBOX_PATH . 'class-seowk-lightbox-admin.php';
    require_once SEOWK_LIGHTBOX_PATH . 'class-seowk-lightbox-frontend.php';

    /**
     * Boots the lightbox class inside Wunderkiste Toolkit.
     */
    function seowk_decent_lightbox() {
        return SEOWK_Lightbox::instance();
    }

    seowk_decent_lightbox();
}
