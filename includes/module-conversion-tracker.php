<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ------------------------------------------------------------------------- *
 * MODULE: Conversion Tracker
 * Version: 2.8 - with configurable currency
 * ------------------------------------------------------------------------- */

function seowk_conversion_add_meta_box() {
    $screens = array( 'post', 'page' );
    
    foreach ( $screens as $screen ) {
        add_meta_box(
            'seowk_conversion_box',
            __( '🎯 Conversion Tracking', 'wunderkiste-toolkit' ),
            'seowk_conversion_render_meta_box',
            $screen,
            'side',
            'high'
        );
    }
}
add_action( 'add_meta_boxes', 'seowk_conversion_add_meta_box' );

function seowk_conversion_render_meta_box( $post ) {
    wp_nonce_field( 'seowk_conversion_save', 'seowk_conversion_nonce' );
    
    $ga4_enabled = get_post_meta( $post->ID, '_seowk_ga4_conversion_enabled', true );
    $ga4_event = get_post_meta( $post->ID, '_seowk_ga4_conversion_event', true );
    $ga4_value = get_post_meta( $post->ID, '_seowk_ga4_conversion_value', true );
    
    $ads_enabled = get_post_meta( $post->ID, '_seowk_ads_conversion_enabled', true );
    $ads_id = get_post_meta( $post->ID, '_seowk_ads_conversion_id', true );
    $ads_label = get_post_meta( $post->ID, '_seowk_ads_conversion_label', true );
    $ads_value = get_post_meta( $post->ID, '_seowk_ads_conversion_value', true );
    
    // Get the currency from the settings
    $currency = function_exists( 'seowk_get_conversion_currency' ) ? seowk_get_conversion_currency() : 'EUR';
    
    ?>
    <div style="padding: 10px 0;">
        <div style="margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #ddd;">
            <h4 style="margin: 0 0 10px 0; color: #4285f4;">📊 Google Analytics 4</h4>
            
            <label style="display: block; margin-bottom: 10px;">
                <input type="checkbox" name="seowk_ga4_conversion_enabled" value="1" <?php checked( 1, $ga4_enabled ); ?>>
                <strong><?php esc_html_e( 'Enable GA4 conversion', 'wunderkiste-toolkit' ); ?></strong>
            </label>
            
            <p style="margin: 10px 0 5px 0;">
                <label style="font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e( 'Event name:', 'wunderkiste-toolkit' ); ?></label>
                <input type="text" name="seowk_ga4_conversion_event" value="<?php echo esc_attr( $ga4_event ); ?>" placeholder="purchase, conversion, lead" style="width: 100%;" />
            </p>
            
            <p style="margin: 10px 0 5px 0;">
                <label style="font-weight: 600; display: block; margin-bottom: 3px;">
                    <?php 
                    printf( 
                        /* translators: %s: currency code */
                        esc_html__( 'Conversion value (%s):', 'wunderkiste-toolkit' ), 
                        esc_html( $currency ) 
                    ); 
                    ?>
                </label>
                <input type="number" step="0.01" name="seowk_ga4_conversion_value" value="<?php echo esc_attr( $ga4_value ); ?>" placeholder="49.99" style="width: 100%;" />
            </p>
        </div>
        
        <div style="margin-bottom: 10px;">
            <h4 style="margin: 0 0 10px 0; color: #34a853;">💰 Google Ads</h4>
            
            <label style="display: block; margin-bottom: 10px;">
                <input type="checkbox" name="seowk_ads_conversion_enabled" value="1" <?php checked( 1, $ads_enabled ); ?>>
                <strong><?php esc_html_e( 'Enable Google Ads conversion', 'wunderkiste-toolkit' ); ?></strong>
            </label>
            
            <p style="margin: 10px 0 5px 0;">
                <label style="font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e( 'Conversion ID:', 'wunderkiste-toolkit' ); ?></label>
                <input type="text" name="seowk_ads_conversion_id" value="<?php echo esc_attr( $ads_id ); ?>" placeholder="AW-123456789" style="width: 100%;" />
            </p>
            
            <p style="margin: 10px 0 5px 0;">
                <label style="font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e( 'Conversion label:', 'wunderkiste-toolkit' ); ?></label>
                <input type="text" name="seowk_ads_conversion_label" value="<?php echo esc_attr( $ads_label ); ?>" placeholder="abc123def456" style="width: 100%;" />
            </p>
            
            <p style="margin: 10px 0 5px 0;">
                <label style="font-weight: 600; display: block; margin-bottom: 3px;">
                    <?php 
                    printf( 
                        /* translators: %s: currency code */
                        esc_html__( 'Conversion value (%s):', 'wunderkiste-toolkit' ), 
                        esc_html( $currency ) 
                    ); 
                    ?>
                </label>
                <input type="number" step="0.01" name="seowk_ads_conversion_value" value="<?php echo esc_attr( $ads_value ); ?>" placeholder="49.99" style="width: 100%;" />
            </p>
        </div>
        
        <div style="background: #f0f6fc; border-left: 3px solid #2271b1; padding: 10px; margin-top: 15px; font-size: 12px;">
            <strong>💡 <?php esc_html_e( 'Tip:', 'wunderkiste-toolkit' ); ?></strong> <?php esc_html_e( 'Enable this on thank-you pages after a form submission.', 'wunderkiste-toolkit' ); ?>
            <br><small style="color: #666;">
                <?php 
                printf( 
                    /* translators: %s: currency code */
                    esc_html__( 'Currency: %s (can be changed in the plugin settings)', 'wunderkiste-toolkit' ), 
                    esc_html( $currency ) 
                ); 
                ?>
            </small>
        </div>
    </div>
    <?php
}

function seowk_conversion_save_meta_data( $post_id ) {
    if ( ! isset( $_POST['seowk_conversion_nonce'] ) ) {
        return;
    }
    
    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['seowk_conversion_nonce'] ) ), 'seowk_conversion_save' ) ) {
        return;
    }
    
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    
    // GA4
    if ( isset( $_POST['seowk_ga4_conversion_enabled'] ) ) {
        update_post_meta( $post_id, '_seowk_ga4_conversion_enabled', 1 );
    } else {
        delete_post_meta( $post_id, '_seowk_ga4_conversion_enabled' );
    }
    
    if ( isset( $_POST['seowk_ga4_conversion_event'] ) ) {
        update_post_meta( $post_id, '_seowk_ga4_conversion_event', sanitize_text_field( wp_unslash( $_POST['seowk_ga4_conversion_event'] ) ) );
    }
    
    if ( isset( $_POST['seowk_ga4_conversion_value'] ) ) {
        update_post_meta( $post_id, '_seowk_ga4_conversion_value', sanitize_text_field( wp_unslash( $_POST['seowk_ga4_conversion_value'] ) ) );
    }
    
    // Google Ads
    if ( isset( $_POST['seowk_ads_conversion_enabled'] ) ) {
        update_post_meta( $post_id, '_seowk_ads_conversion_enabled', 1 );
    } else {
        delete_post_meta( $post_id, '_seowk_ads_conversion_enabled' );
    }
    
    if ( isset( $_POST['seowk_ads_conversion_id'] ) ) {
        update_post_meta( $post_id, '_seowk_ads_conversion_id', sanitize_text_field( wp_unslash( $_POST['seowk_ads_conversion_id'] ) ) );
    }
    
    if ( isset( $_POST['seowk_ads_conversion_label'] ) ) {
        update_post_meta( $post_id, '_seowk_ads_conversion_label', sanitize_text_field( wp_unslash( $_POST['seowk_ads_conversion_label'] ) ) );
    }
    
    if ( isset( $_POST['seowk_ads_conversion_value'] ) ) {
        update_post_meta( $post_id, '_seowk_ads_conversion_value', sanitize_text_field( wp_unslash( $_POST['seowk_ads_conversion_value'] ) ) );
    }
}
add_action( 'save_post', 'seowk_conversion_save_meta_data' );

function seowk_conversion_output_tracking() {
    if ( ! is_singular() ) {
        return;
    }
    
    $post_id = get_the_ID();
    
    // Get the currency
    $currency = function_exists( 'seowk_get_conversion_currency' ) ? seowk_get_conversion_currency() : 'EUR';
    
    // GA4 Conversion
    $ga4_enabled = get_post_meta( $post_id, '_seowk_ga4_conversion_enabled', true );
    
    if ( $ga4_enabled ) {
        $event_name = get_post_meta( $post_id, '_seowk_ga4_conversion_event', true );
        $event_value = get_post_meta( $post_id, '_seowk_ga4_conversion_value', true );
        
        if ( ! empty( $event_name ) ) {
            ?>
            <script>
            window.addEventListener('load', function() {
                if (typeof gtag === 'function') {
                    <?php if ( ! empty( $event_value ) ) : ?>
                    gtag('event', '<?php echo esc_js( $event_name ); ?>', {
                        'value': <?php echo floatval( $event_value ); ?>,
                        'currency': '<?php echo esc_js( $currency ); ?>'
                    });
                    <?php else : ?>
                    gtag('event', '<?php echo esc_js( $event_name ); ?>');
                    <?php endif; ?>
                }
            });
            </script>
            <?php
        }
    }
    
    // Google Ads Conversion
    $ads_enabled = get_post_meta( $post_id, '_seowk_ads_conversion_enabled', true );
    
    if ( $ads_enabled ) {
        $conversion_id = get_post_meta( $post_id, '_seowk_ads_conversion_id', true );
        $conversion_label = get_post_meta( $post_id, '_seowk_ads_conversion_label', true );
        $conversion_value = get_post_meta( $post_id, '_seowk_ads_conversion_value', true );
        
        if ( ! empty( $conversion_id ) && ! empty( $conversion_label ) ) {
            ?>
            <script>
            window.addEventListener('load', function() {
                if (typeof gtag === 'function') {
                    gtag('event', 'conversion', {
                        'send_to': '<?php echo esc_js( $conversion_id ); ?>/<?php echo esc_js( $conversion_label ); ?>'<?php if ( ! empty( $conversion_value ) ) : ?>,
                        'value': <?php echo floatval( $conversion_value ); ?>,
                        'currency': '<?php echo esc_js( $currency ); ?>'<?php endif; ?>
                    });
                }
            });
            </script>
            <?php
        }
    }
}
add_action( 'wp_footer', 'seowk_conversion_output_tracking', 999 );

function seowk_conversion_add_admin_column( $columns ) {
    $new_columns = array();
    
    foreach ( $columns as $key => $value ) {
        $new_columns[ $key ] = $value;
        
        if ( $key === 'title' ) {
            $new_columns['seowk_conversion'] = '🎯 Tracking';
        }
    }
    
    return $new_columns;
}
add_filter( 'manage_posts_columns', 'seowk_conversion_add_admin_column' );
add_filter( 'manage_pages_columns', 'seowk_conversion_add_admin_column' );

function seowk_conversion_fill_admin_column( $column_name, $post_id ) {
    if ( 'seowk_conversion' !== $column_name ) {
        return;
    }
    
    $ga4_enabled = get_post_meta( $post_id, '_seowk_ga4_conversion_enabled', true );
    $ads_enabled = get_post_meta( $post_id, '_seowk_ads_conversion_enabled', true );
    
    $output = array();
    
    if ( $ga4_enabled ) {
        $output[] = '<span style="color: #4285f4; font-weight: 600;" title="' . esc_attr__( 'GA4 conversion active', 'wunderkiste-toolkit' ) . '">GA4</span>';
    }
    
    if ( $ads_enabled ) {
        $output[] = '<span style="color: #34a853; font-weight: 600;" title="' . esc_attr__( 'Google Ads conversion active', 'wunderkiste-toolkit' ) . '">Ads</span>';
    }
    
    if ( empty( $output ) ) {
        echo '<span style="color: #ccc;">—</span>';
    } else {
        echo wp_kses( implode( ' + ', $output ), array( 'span' => array( 'style' => array(), 'title' => array() ) ) );
    }
}
add_action( 'manage_posts_custom_column', 'seowk_conversion_fill_admin_column', 10, 2 );
add_action( 'manage_pages_custom_column', 'seowk_conversion_fill_admin_column', 10, 2 );

function seowk_conversion_admin_css() {
    echo '<style>.column-seowk_conversion { width: 80px; text-align: center; }</style>';
}
add_action( 'admin_head', 'seowk_conversion_admin_css' );
