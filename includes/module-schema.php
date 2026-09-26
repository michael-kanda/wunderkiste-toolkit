<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ------------------------------------------------------------------------- *
 * MODULE: Custom Schema Meta Box (JSON-LD)
 * Version: 2.9 - with improved validation
 * ------------------------------------------------------------------------- */

function seowk_schema_add_meta_box() {
    foreach ( array( 'post', 'page' ) as $screen ) {
        add_meta_box( 'seowk_schema_box_id', __( 'Structured data (JSON-LD)', 'wunderkiste-toolkit' ), 'seowk_schema_render_meta_box', $screen, 'normal', 'high' );
    }
}
add_action( 'add_meta_boxes', 'seowk_schema_add_meta_box' );

function seowk_schema_render_meta_box( $post ) {
    $value = get_post_meta( $post->ID, '_seowk_schema_value', true );
    wp_nonce_field( 'seowk_schema_save_data', 'seowk_schema_nonce' );
    echo '<p><label for="seowk_schema_field">' . esc_html__( 'Paste your JSON-LD object here (without script tags):', 'wunderkiste-toolkit' ) . '</label></p>';
    echo '<textarea id="seowk_schema_field" name="seowk_schema_field" rows="10" style="width:100%; font-family:monospace;">' . esc_textarea( $value ) . '</textarea>';
    echo '<p class="description">' . esc_html__( 'Example: { "@context": "https://schema.org", "@type": "Article", ... }', 'wunderkiste-toolkit' ) . '</p>';
    
    // Validation notice
    if ( ! empty( $value ) && json_decode( $value ) === null ) {
        echo '<p style="color: #d63638; margin-top: 10px;"><strong>⚠️ ' . esc_html__( 'Warning: invalid JSON format!', 'wunderkiste-toolkit' ) . '</strong></p>';
    }
}

function seowk_schema_save_postdata( $post_id ) {
    // Verify the nonce
    if ( ! isset( $_POST['seowk_schema_nonce'] ) ) { 
        return; 
    }
    
    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['seowk_schema_nonce'] ) ), 'seowk_schema_save_data' ) ) { 
        return; 
    }
    
    // Skip autosaves
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { 
        return; 
    }
    
    // Check permissions
    if ( ! current_user_can( 'edit_post', $post_id ) ) { 
        return; 
    }
    
    // Save the schema
    if ( isset( $_POST['seowk_schema_field'] ) ) {
        /*
         * Raw JSON on purpose: sanitize_text_field() and friends would mangle
         * quotes and line breaks. The value is only ever printed after being
         * decoded and re-encoded with wp_json_encode() (see
         * seowk_schema_output_head()) and via esc_textarea() in the meta box.
         */
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON, re-encoded on output.
        $schema_input = trim( wp_unslash( $_POST['seowk_schema_field'] ) );
        
        // Empty field = delete the meta
        if ( '' === $schema_input ) {
            delete_post_meta( $post_id, '_seowk_schema_value' );
            return;
        }
        
        /*
         * Invalid JSON is stored as well so the user can fix it; the meta box
         * shows a warning and nothing is printed on the frontend.
         * update_post_meta() unslashes its value, so it has to be slashed
         * again - otherwise escaped quotes (\") inside the JSON get lost.
         */
        update_post_meta( $post_id, '_seowk_schema_value', wp_slash( $schema_input ) );
    }
}
add_action( 'save_post', 'seowk_schema_save_postdata' );

function seowk_schema_output_head() {
    if ( is_singular() ) {
        $schema_json = get_post_meta( get_the_ID(), '_seowk_schema_value', true );
        
        // Only print valid JSON
        if ( ! empty( $schema_json ) ) {
            $decoded = json_decode( $schema_json );
            if ( $decoded !== null ) {
                /*
                 * JSON_HEX_TAG escapes < and > as \u003C / \u003E. Without it a
                 * "</script>" inside any string value closes the ld+json block
                 * early and everything after it is parsed as executable HTML -
                 * a stored XSS for anyone who may edit a post.
                 * JSON_UNESCAPED_SLASHES is deliberately NOT used for the same
                 * reason.
                 */
                $safe_json = wp_json_encode(
                    $decoded,
                    JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
                    | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
                );

                if ( false !== $safe_json ) {
                    wp_print_inline_script_tag( $safe_json, array( 'type' => 'application/ld+json' ) );
                }
            }
        }
    }
}
add_action( 'wp_head', 'seowk_schema_output_head' );
