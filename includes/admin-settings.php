<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ------------------------------------------------------------------------- *
 * ADMIN SETTINGS PAGE - WUNDERKISTE TOOLKIT v2.8
 * ------------------------------------------------------------------------- */

function seowk_add_admin_menu() {
    add_options_page(
        __( 'Wunderkiste Toolkit Settings', 'wunderkiste-toolkit' ),
        __( 'Wunderkiste Toolkit', 'wunderkiste-toolkit' ),
        'manage_options',
        'wunderkiste-toolkit',
        'seowk_options_page_html'
    );
}
add_action( 'admin_menu', 'seowk_add_admin_menu' );

function seowk_settings_init() {
    register_setting( 
        'seowk_plugin_group', 
        'seowk_settings',
        array(
            'sanitize_callback' => 'seowk_sanitize_settings',
        )
    );

    add_settings_section(
        'seowk_plugin_section',
        __( 'Active modules', 'wunderkiste-toolkit' ),
        'seowk_section_callback',
        'wunderkiste-toolkit'
    );

    // SEO & CONTENT MODULES
    seowk_add_module_field( 'seowk_enable_meta_settings', __( 'SEO Meta Settings', 'wunderkiste-toolkit' ), __( 'Extended meta tags per page: title, description, Open Graph, Twitter Cards.', 'wunderkiste-toolkit' ) );
    seowk_add_module_field( 'seowk_enable_schema', __( 'SEO Schema (JSON-LD)', 'wunderkiste-toolkit' ), __( 'Adds an input field for structured data.', 'wunderkiste-toolkit' ) );
    seowk_add_module_field( 'seowk_enable_bulk_noindex', __( 'Bulk NoIndex Manager', 'wunderkiste-toolkit' ), __( 'Set or remove NoIndex for many posts at once.', 'wunderkiste-toolkit' ) );
    seowk_add_module_field( 'seowk_enable_seo_redirects', __( 'SEO Zombie Killer', 'wunderkiste-toolkit' ), __( 'Redirects empty attachment pages to their posts (301).', 'wunderkiste-toolkit' ) );
    seowk_add_module_field( 'seowk_enable_conversion_tracker', __( 'Conversion Tracker', 'wunderkiste-toolkit' ), __( 'Enables GA4 and Google Ads conversion tracking.', 'wunderkiste-toolkit' ) );
    
    // IMAGE & MEDIA MODULES
    seowk_add_module_field( 'seowk_enable_resizer', __( 'Image Resizer (800px/1200px)', 'wunderkiste-toolkit' ), __( 'Button in the media details to scale images (92% quality).', 'wunderkiste-toolkit' ) );
    seowk_add_module_field( 'seowk_enable_cleaner', __( 'Upload Cleaner', 'wunderkiste-toolkit' ), __( 'Automatically clean file names on upload.', 'wunderkiste-toolkit' ) );
    seowk_add_module_field( 'seowk_enable_image_seo', __( 'Zero-Click Image SEO', 'wunderkiste-toolkit' ), __( 'Generate title and alt text from the file name.', 'wunderkiste-toolkit' ) );
    seowk_add_module_field( 'seowk_enable_media_columns', __( 'Media Inspector', 'wunderkiste-toolkit' ), __( 'Shows file size and pixel dimensions in the media library.', 'wunderkiste-toolkit' ) );
    seowk_add_module_field( 'seowk_enable_svg', __( 'SVG Upload Support', 'wunderkiste-toolkit' ), __( 'Allows SVG uploads with security sanitization.', 'wunderkiste-toolkit' ) );
    seowk_add_module_field( 'seowk_enable_lightbox', __( 'Decent Lightbox', 'wunderkiste-toolkit' ), __( 'Lightweight image lightbox, enabled per image in the media library (vanilla JS, no dependencies).', 'wunderkiste-toolkit' ) );
    
    // PERFORMANCE MODULES
    seowk_add_module_field( 'seowk_disable_emojis', __( 'Emoji Bloat Remover', 'wunderkiste-toolkit' ), __( 'Removes the WordPress emoji scripts for faster page loads.', 'wunderkiste-toolkit' ) );
    
    // SECURITY & ADMIN MODULES
    seowk_add_module_field( 'seowk_disable_xmlrpc', __( 'XML-RPC Blocker', 'wunderkiste-toolkit' ), __( 'Closes the XML-RPC interface.', 'wunderkiste-toolkit' ) );
    seowk_add_module_field( 'seowk_enable_login_protection', __( 'Login Guard', 'wunderkiste-toolkit' ), __( 'Hides the login page behind a secret parameter.', 'wunderkiste-toolkit' ) );
    
    add_settings_field(
        'seowk_login_protection_key',
        __( 'Login Guard key', 'wunderkiste-toolkit' ),
        'seowk_text_render',
        'wunderkiste-toolkit',
        'seowk_plugin_section',
        array(
            'label_for' => 'seowk_login_protection_key',
            'description' => __( 'Your secret word. Afterwards you can only log in via <code>wp-login.php?YOURWORD</code>. Without a key the module does nothing – there is deliberately no default. Logout, password reset and protected posts keep working without the key.', 'wunderkiste-toolkit' )
        )
    );
    
    seowk_add_module_field( 'seowk_enable_comment_blocker', __( 'Comment Blocker', 'wunderkiste-toolkit' ), __( 'Disables comments site-wide.', 'wunderkiste-toolkit' ) );
    seowk_add_module_field( 'seowk_enable_id_column', __( 'ID Column Display', 'wunderkiste-toolkit' ), __( 'Shows the post/page/media ID in all list tables.', 'wunderkiste-toolkit' ) );
    
    // CONTENT TOOLS MODULES
    seowk_add_module_field( 'seowk_enable_date_shortcode', __( 'Date Shortcode', 'wunderkiste-toolkit' ), __( 'Inserts the current date via shortcode.', 'wunderkiste-toolkit' ) );
    seowk_add_module_field( 'seowk_enable_semantic_blocks', __( 'Semantic Blocks', 'wunderkiste-toolkit' ), __( 'HTML5 wrapper blocks for better structure and SEO.', 'wunderkiste-toolkit' ) );
    
    // ADDITIONAL SETTINGS SECTION
    add_settings_section(
        'seowk_additional_section',
        __( 'Additional settings', 'wunderkiste-toolkit' ),
        'seowk_additional_section_callback',
        'wunderkiste-toolkit'
    );
    
    // Currency for conversion tracking
    add_settings_field(
        'seowk_conversion_currency',
        __( 'Conversion currency', 'wunderkiste-toolkit' ),
        'seowk_currency_render',
        'wunderkiste-toolkit',
        'seowk_additional_section',
        array(
            'label_for' => 'seowk_conversion_currency',
            'description' => __( 'Currency code for GA4 and Google Ads conversion tracking.', 'wunderkiste-toolkit' )
        )
    );

    add_settings_field(
        'seowk_date_legacy_shortcodes',
        __( 'Legacy date shortcodes', 'wunderkiste-toolkit' ),
        'seowk_checkbox_render',
        'wunderkiste-toolkit',
        'seowk_additional_section',
        array(
            'label_for'   => 'seowk_date_legacy_shortcodes',
            // Pre-2.12 settings lack the key; show what is actually in effect.
            'default'     => seowk_is_module_active( 'seowk_enable_date_shortcode' ) ? 1 : 0,
            'description' => __( 'Also register [datum], [jahr] and [monat] from earlier versions (Date Shortcode module). New content should use [seowk_date], [seowk_year] and [seowk_month].', 'wunderkiste-toolkit' ),
        )
    );
}
add_action( 'admin_init', 'seowk_settings_init' );

function seowk_add_module_field( $id, $title, $description ) {
    add_settings_field(
        $id,
        $title,
        'seowk_checkbox_render',
        'wunderkiste-toolkit',
        'seowk_plugin_section',
        array(
            'label_for' => $id,
            'description' => $description
        )
    );
}

function seowk_sanitize_settings( $input ) {
    $sanitized = array();
    
    $checkbox_fields = array(
        'seowk_enable_meta_settings', 'seowk_enable_schema', 'seowk_enable_bulk_noindex',
        'seowk_enable_seo_redirects', 'seowk_enable_conversion_tracker', 'seowk_enable_resizer',
        'seowk_enable_cleaner', 'seowk_enable_image_seo', 'seowk_enable_media_columns',
        'seowk_enable_svg', 'seowk_enable_lightbox', 'seowk_disable_emojis', 'seowk_disable_xmlrpc',
        'seowk_enable_login_protection', 'seowk_enable_comment_blocker', 'seowk_enable_id_column',
        'seowk_enable_date_shortcode', 'seowk_enable_semantic_blocks',
        'seowk_date_legacy_shortcodes',
    );
    
    foreach ( $checkbox_fields as $field ) {
        $sanitized[ $field ] = ! empty( $input[ $field ] ) ? 1 : 0;
    }
    
    if ( isset( $input['seowk_login_protection_key'] ) ) {
        // Restrict to characters that survive as a URL query key unchanged.
        $key = sanitize_text_field( wp_unslash( $input['seowk_login_protection_key'] ) );
        $key = (string) preg_replace( '/[^A-Za-z0-9_-]/', '', $key );

        $sanitized['seowk_login_protection_key'] = $key;
    }
    
    // Validate the currency (3-letter code)
    if ( isset( $input['seowk_conversion_currency'] ) ) {
        $currency = strtoupper( sanitize_text_field( $input['seowk_conversion_currency'] ) );
        $currency = preg_replace( '/[^A-Z]/', '', $currency );
        $sanitized['seowk_conversion_currency'] = substr( $currency, 0, 3 );
    }
    
    return $sanitized;
}

function seowk_section_callback() {
    echo '<p style="font-size: 14px; color: #666;">' . esc_html__( 'Choose the tools you want to enable.', 'wunderkiste-toolkit' ) . '</p>';
}

function seowk_additional_section_callback() {
    echo '<p style="font-size: 14px; color: #666;">' . esc_html__( 'Further options for active modules.', 'wunderkiste-toolkit' ) . '</p>';
}

function seowk_checkbox_render( $args ) {
    $options = get_option( 'seowk_settings' );
    $field   = $args['label_for'];
    $default = isset( $args['default'] ) ? $args['default'] : false;
    $checked = isset( $options[ $field ] ) ? $options[ $field ] : $default;
    $desc    = isset( $args['description'] ) ? $args['description'] : '';
    ?>
    <label style="display: flex; align-items: center;">
        <input type="checkbox" id="<?php echo esc_attr( $field ); ?>" name="seowk_settings[<?php echo esc_attr( $field ); ?>]" value="1" <?php checked( 1, $checked ); ?>>
        <?php if ( ! empty( $desc ) ) : ?>
            <span style="margin-left: 8px; color: #666;"><?php echo esc_html( $desc ); ?></span>
        <?php endif; ?>
    </label>
    <?php
}

function seowk_text_render( $args ) {
    $options = get_option( 'seowk_settings' );
    $field   = $args['label_for'];
    $value   = isset( $options[ $field ] ) ? $options[ $field ] : '';
    $desc    = isset( $args['description'] ) ? $args['description'] : '';
    ?>
    <input type="text" id="<?php echo esc_attr( $field ); ?>" name="seowk_settings[<?php echo esc_attr( $field ); ?>]" value="<?php echo esc_attr( $value ); ?>" class="regular-text" autocomplete="off" placeholder="<?php esc_attr_e( 'long, random word', 'wunderkiste-toolkit' ); ?>">
    <?php if ( ! empty( $desc ) ) : ?>
        <p class="description"><?php echo wp_kses( $desc, array( 'code' => array() ) ); ?></p>
    <?php endif; ?>
    <?php
}

function seowk_currency_render( $args ) {
    $options = get_option( 'seowk_settings' );
    $field   = $args['label_for'];
    $value   = isset( $options[ $field ] ) ? $options[ $field ] : 'EUR';
    $desc    = isset( $args['description'] ) ? $args['description'] : '';
    
    $currencies = array(
        'EUR' => 'EUR - Euro',
        'USD' => 'USD - US Dollar',
        'GBP' => 'GBP - British Pound',
        'CHF' => 'CHF - Swiss Franc',
        'AUD' => 'AUD - Australian Dollar',
        'CAD' => 'CAD - Canadian Dollar',
        'JPY' => 'JPY - Japanese Yen',
        'CNY' => 'CNY - Chinese Yuan',
        'INR' => 'INR - Indian Rupee',
        'BRL' => 'BRL - Brazilian Real',
        'MXN' => 'MXN - Mexican Peso',
        'PLN' => 'PLN - Polish Zloty',
        'SEK' => 'SEK - Swedish Krona',
        'NOK' => 'NOK - Norwegian Krone',
        'DKK' => 'DKK - Danish Krone',
        'CZK' => 'CZK - Czech Koruna',
        'HUF' => 'HUF - Hungarian Forint',
        'RUB' => 'RUB - Russian Ruble',
        'TRY' => 'TRY - Turkish Lira',
        'ZAR' => 'ZAR - South African Rand',
    );
    ?>
    <select id="<?php echo esc_attr( $field ); ?>" name="seowk_settings[<?php echo esc_attr( $field ); ?>]">
        <?php foreach ( $currencies as $code => $label ) : ?>
            <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $value, $code ); ?>>
                <?php echo esc_html( $label ); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php if ( ! empty( $desc ) ) : ?>
        <p class="description"><?php echo esc_html( $desc ); ?></p>
    <?php endif; ?>
    <p class="description">
        <code><?php esc_html_e( 'Filter:', 'wunderkiste-toolkit' ); ?> seowk_conversion_currency</code>
    </p>
    <?php
}

function seowk_options_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    settings_errors( 'seowk_messages' );
    ?>
    <div class="wrap">
        <h1 style="display: flex; align-items: center; gap: 10px;">
            <span>📦</span>
            <span><?php esc_html_e( 'Wunderkiste Toolkit', 'wunderkiste-toolkit' ); ?></span>
            <span style="font-size: 14px; background: #2271b1; color: white; padding: 4px 12px; border-radius: 3px;">v<?php echo esc_html( SEOWK_VERSION ); ?></span>
        </h1>
        
        <p style="font-size: 16px; margin: 20px 0;">
            <?php esc_html_e( 'Your modular all-in-one toolkit for SEO, performance and administration.', 'wunderkiste-toolkit' ); ?>
        </p>

        <div style="background: #f0f6fc; border-left: 4px solid #2271b1; padding: 15px; margin: 20px 0;">
            <h3 style="margin-top: 0;">💡 <?php esc_html_e( 'How it works:', 'wunderkiste-toolkit' ); ?></h3>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>✅ <?php esc_html_e( 'Enable only the modules you really need', 'wunderkiste-toolkit' ); ?></li>
                <li>🚀 <?php esc_html_e( 'Every module works independently and efficiently', 'wunderkiste-toolkit' ); ?></li>
                <li>🔒 <?php esc_html_e( 'All modules are disabled by default', 'wunderkiste-toolkit' ); ?></li>
            </ul>
        </div>

        <form action="options.php" method="post" style="background: white; border: 1px solid #ccd0d4; padding: 20px; border-radius: 4px;">
            <?php
            settings_fields( 'seowk_plugin_group' );
            do_settings_sections( 'wunderkiste-toolkit' );
            submit_button( __( 'Save settings', 'wunderkiste-toolkit' ), 'primary large' );
            ?>
        </form>

        <div style="margin: 30px 0; padding: 15px; background: #f9f9f9; border-radius: 4px; text-align: center; color: #666;">
            <p style="margin: 0;">
                <?php 
                printf( 
                    /* translators: %s: author name */
                    esc_html__( 'Made with ❤️ by %s', 'wunderkiste-toolkit' ), 
                    '<strong>Michael Kanda</strong>' 
                ); 
                ?>
            </p>
        </div>
    </div>
    <?php
}

function seowk_settings_page_css( $hook_suffix ) {
    if ( 'settings_page_wunderkiste-toolkit' !== $hook_suffix ) {
        return;
    }
    seowk_add_inline_admin_css(
        '.form-table th { width: 250px; font-weight: 600; }
        .form-table td { padding: 15px 10px; }'
    );
}
add_action( 'admin_enqueue_scripts', 'seowk_settings_page_css' );
