<?php
/**
 * Plugin Name: Wunderkiste Toolkit
 * Plugin URI: https://designare.at/wunderkiste-toolkit
 * Description: Modular toolkit: SEO meta and schema, image resizing, SVG uploads, login protection, lightbox and more. Enable only what you need.
 * Version: 2.12
 * Author: Michael Kanda
 * Author URI: https://designare.at
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: wunderkiste-toolkit
 * Domain Path: /languages
 * Requires at least: 6.3
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ------------------------------------------------------------------------- *
 * PLUGIN CONSTANTS
 * ------------------------------------------------------------------------- */

define( 'SEOWK_VERSION', '2.12' );
define( 'SEOWK_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SEOWK_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SEOWK_PLUGIN_FILE', __FILE__ );

/* ------------------------------------------------------------------------- *
 * TRANSLATIONS
 * ------------------------------------------------------------------------- */

/**
 * Loads the bundled translations (German) from /languages.
 *
 * Language packs from translate.wordpress.org in wp-content/languages/plugins
 * are checked first and take precedence; the bundled files are only the
 * fallback until a language pack exists.
 */
function seowk_load_textdomain() {
    // phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound -- ships its own de_DE/de_AT files as a fallback.
    load_plugin_textdomain( 'wunderkiste-toolkit', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'seowk_load_textdomain' );

/* ------------------------------------------------------------------------- *
 * HELPER FUNCTION: get options (wraps the former global variable)
 * ------------------------------------------------------------------------- */

function seowk_get_options() {
    static $options = null;
    
    if ( $options === null ) {
        $options = get_option( 'seowk_settings', array() );
    }
    
    return $options;
}

function seowk_is_module_active( $module_key ) {
    $options = seowk_get_options();
    return ! empty( $options[ $module_key ] );
}

/* ------------------------------------------------------------------------- *
 * LOAD ADMIN SETTINGS
 * ------------------------------------------------------------------------- */

require_once SEOWK_PLUGIN_DIR . 'includes/admin-settings.php';

/* ------------------------------------------------------------------------- *
 * LOAD ACTIVE MODULES
 * ------------------------------------------------------------------------- */

// SEO & CONTENT MODULES
if ( seowk_is_module_active( 'seowk_enable_meta_settings' ) ) {
    require_once SEOWK_PLUGIN_DIR . 'includes/module-meta-settings.php';
}

if ( seowk_is_module_active( 'seowk_enable_schema' ) ) {
    require_once SEOWK_PLUGIN_DIR . 'includes/module-schema.php';
}

if ( seowk_is_module_active( 'seowk_enable_bulk_noindex' ) ) {
    require_once SEOWK_PLUGIN_DIR . 'includes/module-bulk-noindex.php';
}

if ( seowk_is_module_active( 'seowk_enable_seo_redirects' ) ) {
    require_once SEOWK_PLUGIN_DIR . 'includes/module-seo-redirects.php';
}

if ( seowk_is_module_active( 'seowk_enable_conversion_tracker' ) ) {
    require_once SEOWK_PLUGIN_DIR . 'includes/module-conversion-tracker.php';
}

// IMAGE & MEDIA MODULES
if ( seowk_is_module_active( 'seowk_enable_resizer' ) ) {
    require_once SEOWK_PLUGIN_DIR . 'includes/module-resizer.php';
}

if ( seowk_is_module_active( 'seowk_enable_cleaner' ) ) {
    require_once SEOWK_PLUGIN_DIR . 'includes/module-cleaner.php';
}

if ( seowk_is_module_active( 'seowk_enable_image_seo' ) ) {
    require_once SEOWK_PLUGIN_DIR . 'includes/module-image-seo.php';
}

if ( seowk_is_module_active( 'seowk_enable_media_columns' ) ) {
    require_once SEOWK_PLUGIN_DIR . 'includes/module-media-columns.php';
}

if ( seowk_is_module_active( 'seowk_enable_svg' ) ) {
    require_once SEOWK_PLUGIN_DIR . 'includes/module-svg.php';
}

if ( seowk_is_module_active( 'seowk_enable_lightbox' ) ) {
    require_once SEOWK_PLUGIN_DIR . 'includes/module-lightbox.php';
}

// PERFORMANCE MODULES
if ( seowk_is_module_active( 'seowk_disable_emojis' ) ) {
    require_once SEOWK_PLUGIN_DIR . 'includes/module-disable-emojis.php';
}

// SECURITY & ADMIN MODULES
if ( seowk_is_module_active( 'seowk_disable_xmlrpc' ) ) {
    require_once SEOWK_PLUGIN_DIR . 'includes/module-disable-xmlrpc.php';
}

if ( seowk_is_module_active( 'seowk_enable_login_protection' ) ) {
    require_once SEOWK_PLUGIN_DIR . 'includes/module-login-protection.php';
}

if ( seowk_is_module_active( 'seowk_enable_comment_blocker' ) ) {
    require_once SEOWK_PLUGIN_DIR . 'includes/module-comment-blocker.php';
}

// ADMIN TOOLS MODULES
if ( seowk_is_module_active( 'seowk_enable_id_column' ) ) {
    require_once SEOWK_PLUGIN_DIR . 'includes/module-id-column.php';
}

// CONTENT TOOLS MODULES
if ( seowk_is_module_active( 'seowk_enable_date_shortcode' ) ) {
    require_once SEOWK_PLUGIN_DIR . 'includes/module-date-shortcode.php';
}

if ( seowk_is_module_active( 'seowk_enable_semantic_blocks' ) ) {
    require_once SEOWK_PLUGIN_DIR . 'includes/module-semantic-blocks.php';
}

/* ------------------------------------------------------------------------- *
 * PLUGIN ACTIVATION & DEACTIVATION HOOKS
 * ------------------------------------------------------------------------- */

function seowk_plugin_activate() {
    if ( ! get_option( 'seowk_settings' ) ) {
        $default_options = array(
            // No default login key on purpose: a secret printed in the plugin
            // source, the readme and the settings placeholder protects nothing.
            'seowk_login_protection_key' => '',
            'seowk_conversion_currency'  => 'EUR',
        );
        add_option( 'seowk_settings', $default_options );
    }
    
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'seowk_plugin_activate' );

function seowk_plugin_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'seowk_plugin_deactivate' );

/* ------------------------------------------------------------------------- *
 * LINKS ON THE PLUGINS SCREEN
 * ------------------------------------------------------------------------- */

function seowk_add_settings_link( $links ) {
    $settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=wunderkiste-toolkit' ) ) . '">' . esc_html__( 'Settings', 'wunderkiste-toolkit' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'seowk_add_settings_link' );

function seowk_add_plugin_meta_links( $links, $file ) {
    if ( strpos( $file, basename( __FILE__ ) ) !== false ) {
        $new_links = array(
            '<a href="https://designare.at/wunderkiste-toolkit" style="color: #d63638; font-weight: 600;">' . esc_html__( 'Documentation', 'wunderkiste-toolkit' ) . '</a>',
            '<a href="https://designare.at/wunderkiste-toolkit" style="color: #2271b1;">' . esc_html__( 'Support', 'wunderkiste-toolkit' ) . '</a>'
        );
        $links = array_merge( $links, $new_links );
    }
    return $links;
}
add_filter( 'plugin_row_meta', 'seowk_add_plugin_meta_links', 10, 2 );

/* ------------------------------------------------------------------------- *
 * ADMIN NOTICES
 * ------------------------------------------------------------------------- */

function seowk_admin_notice_after_activation() {
    if ( get_transient( 'seowk_activation_notice' ) ) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <strong>🎉 <?php esc_html_e( 'Wunderkiste Toolkit activated!', 'wunderkiste-toolkit' ); ?></strong> 
                <?php 
                printf( 
                    /* translators: %s: settings page URL */
                    esc_html__( 'Go to %s to enable the modules you want.', 'wunderkiste-toolkit' ),
                    '<a href="' . esc_url( admin_url( 'options-general.php?page=wunderkiste-toolkit' ) ) . '">' . esc_html__( 'Settings → Wunderkiste Toolkit', 'wunderkiste-toolkit' ) . '</a>'
                );
                ?>
            </p>
        </div>
        <?php
        delete_transient( 'seowk_activation_notice' );
    }
}
add_action( 'admin_notices', 'seowk_admin_notice_after_activation' );

function seowk_set_activation_transient() {
    set_transient( 'seowk_activation_notice', true, 5 );
}
register_activation_hook( __FILE__, 'seowk_set_activation_transient' );

/* ------------------------------------------------------------------------- *
 * DEBUG INFO (admins only)
 * ------------------------------------------------------------------------- */

function seowk_admin_footer_debug() {
    // Only in debug mode - client installs should not get console noise
    // injected into every single admin screen.
    if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $seowk_opts = seowk_get_options();
    $active_modules = array_filter( $seowk_opts, function( $value, $key ) {
        return strpos( $key, 'seowk_enable_' ) === 0 || strpos( $key, 'seowk_disable_' ) === 0;
    }, ARRAY_FILTER_USE_BOTH );
    $active_modules = array_filter( $active_modules );
    $module_count = count( $active_modules );

    ?>
    <script>
    console.log('%c🎯 Wunderkiste Toolkit v<?php echo esc_js( SEOWK_VERSION ); ?>', 'background: #2271b1; color: white; padding: 5px 10px; border-radius: 3px;');
    console.log('<?php echo esc_js( __( 'Active modules:', 'wunderkiste-toolkit' ) ); ?> <?php echo esc_js( (string) $module_count ); ?>');
    <?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
    console.log('Module:', <?php echo wp_json_encode( array_keys( $active_modules ) ); ?>);
    <?php endif; ?>
    </script>
    <?php
}
add_action( 'admin_footer', 'seowk_admin_footer_debug' );

/* ------------------------------------------------------------------------- *
 * MODULE OVERVIEW FOR DEVELOPERS
 * ------------------------------------------------------------------------- */

function seowk_get_available_modules() {
    return array(
        'seo_content' => array(
            'seowk_enable_meta_settings'     => __( 'SEO Meta Settings', 'wunderkiste-toolkit' ),
            'seowk_enable_schema'            => __( 'SEO Schema (JSON-LD)', 'wunderkiste-toolkit' ),
            'seowk_enable_bulk_noindex'      => __( 'Bulk NoIndex Manager', 'wunderkiste-toolkit' ),
            'seowk_enable_seo_redirects'     => __( 'SEO Zombie Killer', 'wunderkiste-toolkit' ),
            'seowk_enable_conversion_tracker'=> __( 'Conversion Tracker', 'wunderkiste-toolkit' ),
        ),
        'media' => array(
            'seowk_enable_resizer'       => __( 'Image Resizer (800px/1200px)', 'wunderkiste-toolkit' ),
            'seowk_enable_cleaner'       => __( 'Upload Cleaner', 'wunderkiste-toolkit' ),
            'seowk_enable_image_seo'     => __( 'Zero-Click Image SEO', 'wunderkiste-toolkit' ),
            'seowk_enable_media_columns' => __( 'Media Inspector', 'wunderkiste-toolkit' ),
            'seowk_enable_svg'           => __( 'SVG Upload Support', 'wunderkiste-toolkit' ),
            'seowk_enable_lightbox'      => __( 'Decent Lightbox', 'wunderkiste-toolkit' ),
        ),
        'performance' => array(
            'seowk_disable_emojis' => __( 'Emoji Bloat Remover', 'wunderkiste-toolkit' ),
        ),
        'security_admin' => array(
            'seowk_disable_xmlrpc'        => __( 'XML-RPC Blocker', 'wunderkiste-toolkit' ),
            'seowk_enable_login_protection'=> __( 'Login Guard', 'wunderkiste-toolkit' ),
            'seowk_enable_comment_blocker' => __( 'Comment Blocker', 'wunderkiste-toolkit' ),
            'seowk_enable_id_column'       => __( 'ID Column Display', 'wunderkiste-toolkit' ),
        ),
        'content_tools' => array(
            'seowk_enable_date_shortcode'   => __( 'Date Shortcode', 'wunderkiste-toolkit' ),
            'seowk_enable_semantic_blocks'  => __( 'Semantic Blocks', 'wunderkiste-toolkit' ),
        ),
    );
}

/* ------------------------------------------------------------------------- *
 * HELPER: currency for conversion tracking
 * ------------------------------------------------------------------------- */

function seowk_get_conversion_currency() {
    $options = seowk_get_options();
    $currency = isset( $options['seowk_conversion_currency'] ) ? $options['seowk_conversion_currency'] : 'EUR';
    
    /**
     * Filter: seowk_conversion_currency
     * Allows changing the currency used for conversion tracking
     *
     * @param string $currency Currency code (e.g. 'EUR', 'USD', 'CHF')
     */
    return apply_filters( 'seowk_conversion_currency', $currency );
}
