<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ------------------------------------------------------------------------- *
 * MODULE: SEO Meta Settings
 * ------------------------------------------------------------------------- */

function seowk_meta_add_meta_box() {
    foreach ( array( 'post', 'page' ) as $screen ) {
        add_meta_box( 'seowk_meta_box', __( '🔍 SEO Meta Settings', 'wunderkiste-toolkit' ), 'seowk_meta_render_meta_box', $screen, 'normal', 'high' );
    }
}
add_action( 'add_meta_boxes', 'seowk_meta_add_meta_box' );

function seowk_meta_render_meta_box( $post ) {
    wp_nonce_field( 'seowk_meta_save', 'seowk_meta_nonce' );
    $meta_title = get_post_meta( $post->ID, '_seowk_meta_title', true );
    $meta_description = get_post_meta( $post->ID, '_seowk_meta_description', true );
    $meta_robots = get_post_meta( $post->ID, '_seowk_meta_robots', true );
    $og_title = get_post_meta( $post->ID, '_seowk_og_title', true );
    $og_description = get_post_meta( $post->ID, '_seowk_og_description', true );
    $og_image = get_post_meta( $post->ID, '_seowk_og_image', true );
    if ( empty( $meta_robots ) ) { $meta_robots = 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1'; }
    ?>
    <div class="seowk-meta-box" style="padding: 15px 0;">
        <h4 style="margin-top: 0;"><?php esc_html_e( 'Basic SEO', 'wunderkiste-toolkit' ); ?></h4>
        <p><label style="font-weight: 600; display: block; margin-bottom: 5px;"><?php esc_html_e( 'SEO title (max. 60 characters)', 'wunderkiste-toolkit' ); ?></label>
        <input type="text" name="seowk_meta_title" value="<?php echo esc_attr( $meta_title ); ?>" class="large-text" maxlength="70" /></p>
        <p><label style="font-weight: 600; display: block; margin-bottom: 5px;"><?php esc_html_e( 'Meta description (max. 160 characters)', 'wunderkiste-toolkit' ); ?></label>
        <textarea name="seowk_meta_description" rows="3" class="large-text" maxlength="170"><?php echo esc_textarea( $meta_description ); ?></textarea></p>
        <p><label style="font-weight: 600; display: block; margin-bottom: 5px;"><?php esc_html_e( 'Robots meta tag', 'wunderkiste-toolkit' ); ?></label>
        <input type="text" name="seowk_meta_robots" value="<?php echo esc_attr( $meta_robots ); ?>" class="large-text" /></p>
        <h4><?php esc_html_e( 'Open Graph (social media)', 'wunderkiste-toolkit' ); ?></h4>
        <p><label style="font-weight: 600; display: block; margin-bottom: 5px;"><?php esc_html_e( 'OG title', 'wunderkiste-toolkit' ); ?></label>
        <input type="text" name="seowk_og_title" value="<?php echo esc_attr( $og_title ); ?>" class="large-text" /></p>
        <p><label style="font-weight: 600; display: block; margin-bottom: 5px;"><?php esc_html_e( 'OG description', 'wunderkiste-toolkit' ); ?></label>
        <textarea name="seowk_og_description" rows="2" class="large-text"><?php echo esc_textarea( $og_description ); ?></textarea></p>
        <p><label style="font-weight: 600; display: block; margin-bottom: 5px;"><?php esc_html_e( 'OG image URL', 'wunderkiste-toolkit' ); ?></label>
        <input type="url" name="seowk_og_image" value="<?php echo esc_attr( $og_image ); ?>" class="large-text" /></p>
    </div>
    <?php
}

function seowk_meta_save_data( $post_id ) {
    if ( ! isset( $_POST['seowk_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['seowk_meta_nonce'] ) ), 'seowk_meta_save' ) ) { return; }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
    if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }
    $fields = array( 'seowk_meta_title', 'seowk_meta_description', 'seowk_meta_robots', 'seowk_og_title', 'seowk_og_description', 'seowk_og_image' );
    foreach ( $fields as $field ) {
        if ( isset( $_POST[ $field ] ) ) {
            $value = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
            if ( ! empty( $value ) ) { update_post_meta( $post_id, '_' . $field, $value ); }
            else { delete_post_meta( $post_id, '_' . $field ); }
        }
    }
}
add_action( 'save_post', 'seowk_meta_save_data' );

function seowk_meta_output_head() {
    if ( ! is_singular() ) { return; }
    $post_id = get_the_ID();
    $meta_title = get_post_meta( $post_id, '_seowk_meta_title', true );
    $meta_description = get_post_meta( $post_id, '_seowk_meta_description', true );
    $meta_robots = get_post_meta( $post_id, '_seowk_meta_robots', true );
    $og_title = get_post_meta( $post_id, '_seowk_og_title', true );
    $og_description = get_post_meta( $post_id, '_seowk_og_description', true );
    $og_image = get_post_meta( $post_id, '_seowk_og_image', true );
    $site_name = get_bloginfo( 'name' );
    $current_url = get_permalink( $post_id );
    $page_title = get_the_title( $post_id );
    if ( empty( $og_image ) && has_post_thumbnail( $post_id ) ) { $og_image = get_the_post_thumbnail_url( $post_id, 'large' ); }
    echo "\n<!-- Wunderkiste Toolkit -->\n";
    if ( ! empty( $meta_description ) ) { echo '<meta name="description" content="' . esc_attr( $meta_description ) . '">' . "\n"; }
    /*
     * robots goes through the core wp_robots filter (seowk_meta_wp_robots())
     * and the canonical link comes from core's rel_canonical(). Printing them
     * here as well put two robots tags and two canonicals into every page.
     */
    echo '<meta property="og:locale" content="' . esc_attr( str_replace( '-', '_', get_bloginfo( 'language' ) ) ) . '">' . "\n";
    echo '<meta property="og:type" content="website">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr( $og_title ?: $meta_title ?: $page_title ) . '">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr( $og_description ?: $meta_description ) . '">' . "\n";
    echo '<meta property="og:url" content="' . esc_url( $current_url ) . '">' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr( $site_name ) . '">' . "\n";
    if ( ! empty( $og_image ) ) { echo '<meta property="og:image" content="' . esc_url( $og_image ) . '">' . "\n"; }
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr( $og_title ?: $meta_title ?: $page_title ) . '">' . "\n";
    echo '<meta name="twitter:description" content="' . esc_attr( $og_description ?: $meta_description ) . '">' . "\n";
    if ( ! empty( $og_image ) ) { echo '<meta name="twitter:image" content="' . esc_url( $og_image ) . '">' . "\n"; }
    echo "<!-- /Wunderkiste Toolkit -->\n\n";
}
add_action( 'wp_head', 'seowk_meta_output_head', 1 );

/**
 * Merges the per-post robots field into core's robots meta tag.
 *
 * @param array $robots Directives collected by core.
 * @return array
 */
function seowk_meta_wp_robots( $robots ) {
    if ( ! is_singular() ) { return $robots; }
    $meta_robots = (string) get_post_meta( get_the_ID(), '_seowk_meta_robots', true );
    if ( '' === trim( $meta_robots ) ) { return $robots; }

    foreach ( explode( ',', $meta_robots ) as $directive ) {
        $parts = array_map( 'trim', explode( ':', $directive, 2 ) );
        $name  = strtolower( sanitize_key( $parts[0] ) );
        if ( '' === $name ) { continue; }
        $robots[ $name ] = isset( $parts[1] ) ? sanitize_text_field( $parts[1] ) : true;
    }

    // Contradicting pairs: the negative directive wins.
    if ( ! empty( $robots['noindex'] ) ) { unset( $robots['index'] ); }
    if ( ! empty( $robots['nofollow'] ) ) { unset( $robots['follow'] ); }

    return $robots;
}
add_filter( 'wp_robots', 'seowk_meta_wp_robots' );

function seowk_meta_document_title( $title ) {
    if ( ! is_singular() ) { return $title; }
    $meta_title = get_post_meta( get_the_ID(), '_seowk_meta_title', true );
    return ! empty( $meta_title ) ? $meta_title : $title;
}
add_filter( 'pre_get_document_title', 'seowk_meta_document_title', 999 );
