<?php
/**
 * MODULE: SVG Upload Support
 *
 * Uploaded SVGs are cleaned with enshrined/svg-sanitize (bundled under
 * includes/vendor/), which is the library Safe SVG uses as well. The previous
 * hand-rolled DOM blacklist was ineffective: its XPath queries did not match
 * namespaced elements, so a standards-compliant SVG carrying a <script> tag
 * passed through untouched.
 *
 * @package Wunderkiste_Toolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once SEOWK_PLUGIN_DIR . 'includes/vendor/svg-sanitize-autoload.php';

/**
 * Capability a user needs before SVG uploads are offered to them.
 *
 * SVG is an executable document format. Even after sanitising, it is not a
 * format every Author should be able to place in the media library, so the
 * MIME type is only added for users who are trusted with raw markup anyway.
 *
 * @return bool
 */
function seowk_user_may_upload_svg() {
    /**
     * Filter the capability required to upload SVG files.
     *
     * @param string $capability Capability name. Default 'unfiltered_html'.
     */
    $capability = apply_filters( 'seowk_svg_upload_capability', 'unfiltered_html' );

    return current_user_can( $capability );
}

/**
 * Register the SVG MIME types for permitted users.
 *
 * @param array $mimes Allowed MIME types.
 * @return array
 */
function seowk_add_svg_mime_type( $mimes ) {
    if ( ! seowk_user_may_upload_svg() ) {
        unset( $mimes['svg'], $mimes['svgz'] );
        return $mimes;
    }

    $mimes['svg']  = 'image/svg+xml';
    $mimes['svgz'] = 'image/svg+xml';

    return $mimes;
}
add_filter( 'upload_mimes', 'seowk_add_svg_mime_type' );

/**
 * Keep WordPress from rejecting SVG on extension/MIME mismatch.
 *
 * @param array  $data     File data.
 * @param string $file     Full path to the file.
 * @param string $filename Filename.
 * @param array  $mimes    Allowed MIME types.
 * @return array
 */
function seowk_fix_svg_mime_type( $data, $file, $filename, $mimes ) {
    if ( ! seowk_user_may_upload_svg() ) {
        return $data;
    }

    $ext = isset( $data['ext'] ) ? $data['ext'] : '';

    if ( strlen( $ext ) < 1 ) {
        $ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
    }

    if ( 'svg' === $ext || 'svgz' === $ext ) {
        $data['type'] = 'image/svg+xml';
        $data['ext']  = $ext;
    }

    return $data;
}
add_filter( 'wp_check_filetype_and_ext', 'seowk_fix_svg_mime_type', 10, 4 );

/**
 * Sanitize an SVG string.
 *
 * @param string $svg_content Raw SVG markup.
 * @return string|false Cleaned markup, or false when it cannot be parsed.
 */
function seowk_sanitize_svg_content( $svg_content ) {
    if ( ! class_exists( '\enshrined\svgSanitize\Sanitizer' ) ) {
        return false;
    }

    $sanitizer = new \enshrined\svgSanitize\Sanitizer();

    // Strip references to external files (remote <use>, xlink to other hosts).
    $sanitizer->removeRemoteReferences( true );

    $clean = $sanitizer->sanitize( $svg_content );

    if ( false === $clean || '' === trim( (string) $clean ) ) {
        return false;
    }

    return seowk_strip_svg_style_imports( $clean );
}

/**
 * Remove @import rules and remote url() references from <style> blocks.
 *
 * The library keeps <style> because it is a legitimate SVG element, but it
 * does not look inside the CSS. An @import there still pulls a remote
 * stylesheet whenever the SVG is rendered, which leaks visitor IPs and hands
 * a third party control over part of the rendering.
 *
 * @param string $svg Sanitized SVG markup.
 * @return string
 */
function seowk_strip_svg_style_imports( $svg ) {
    return (string) preg_replace_callback(
        '#(<style\b[^>]*>)(.*?)(</style>)#is',
        static function ( $matches ) {
            $css = $matches[2];

            // Drop @import at-rules entirely.
            $css = preg_replace( '#@import\b[^;]*;?#i', '', $css );

            // Drop url() targets that point somewhere off-site.
            $css = preg_replace(
                '#url\(\s*[\'"]?\s*(?:https?:)?//[^)]*\)#i',
                'none',
                $css
            );

            return $matches[1] . $css . $matches[3];
        },
        $svg
    );
}

/**
 * Clean SVG uploads before WordPress moves them into the uploads folder.
 *
 * Handles both plain .svg and gzip-compressed .svgz, which the old
 * implementation always rejected because it fed the compressed bytes into the
 * XML parser.
 *
 * @param array $file Upload data.
 * @return array
 */
function seowk_sanitize_svg_upload( $file ) {
    if ( empty( $file['type'] ) || 'image/svg+xml' !== $file['type'] ) {
        return $file;
    }

    if ( ! seowk_user_may_upload_svg() ) {
        $file['error'] = __( 'You are not allowed to upload SVG files.', 'wunderkiste-toolkit' );
        return $file;
    }

    if ( empty( $file['tmp_name'] ) || ! is_readable( $file['tmp_name'] ) ) {
        return $file;
    }

    $raw = file_get_contents( $file['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

    if ( false === $raw || '' === $raw ) {
        $file['error'] = __( 'The SVG file could not be read.', 'wunderkiste-toolkit' );
        return $file;
    }

    // Detect gzip magic bytes so .svgz files can be cleaned too.
    $is_gzipped = ( 0 === strncmp( $raw, "\x1f\x8b", 2 ) );

    if ( $is_gzipped ) {
        if ( ! function_exists( 'gzdecode' ) ) {
            $file['error'] = __( 'Compressed SVGZ files cannot be checked on this server.', 'wunderkiste-toolkit' );
            return $file;
        }

        $decoded = gzdecode( $raw );

        if ( false === $decoded ) {
            $file['error'] = __( 'The SVGZ file could not be decompressed.', 'wunderkiste-toolkit' );
            return $file;
        }

        $raw = $decoded;
    }

    $clean = seowk_sanitize_svg_content( $raw );

    if ( false === $clean ) {
        $file['error'] = __( 'This SVG file could not be processed safely and was rejected.', 'wunderkiste-toolkit' );
        return $file;
    }

    $payload = $is_gzipped ? gzencode( $clean ) : $clean;

    if ( false === file_put_contents( $file['tmp_name'], $payload ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- overwriting PHP's own upload temp file; WP_Filesystem may need FTP credentials here.
        $file['error'] = __( 'The sanitized SVG file could not be saved.', 'wunderkiste-toolkit' );
        return $file;
    }

    $file['size'] = strlen( $payload );

    return $file;
}
add_filter( 'wp_handle_upload_prefilter', 'seowk_sanitize_svg_upload' );

/**
 * Record the SVG viewBox dimensions so WordPress has something to work with.
 *
 * @param array  $metadata      Attachment metadata.
 * @param int    $attachment_id Attachment ID.
 * @return array
 */
function seowk_svg_attachment_metadata( $metadata, $attachment_id ) {
    if ( 'image/svg+xml' !== get_post_mime_type( $attachment_id ) ) {
        return $metadata;
    }

    $dimensions = seowk_get_svg_dimensions( get_attached_file( $attachment_id ) );

    if ( ! $dimensions ) {
        return $metadata;
    }

    $metadata = is_array( $metadata ) ? $metadata : array();

    $metadata['width']  = $dimensions['width'];
    $metadata['height'] = $dimensions['height'];

    return $metadata;
}
add_filter( 'wp_generate_attachment_metadata', 'seowk_svg_attachment_metadata', 10, 2 );

/**
 * Read width/height from an SVG file.
 *
 * @param string|false $path Absolute file path.
 * @return array{width:int,height:int}|false
 */
function seowk_get_svg_dimensions( $path ) {
    if ( ! $path || ! is_readable( $path ) ) {
        return false;
    }

    $contents = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

    if ( false === $contents ) {
        return false;
    }

    if ( 0 === strncmp( $contents, "\x1f\x8b", 2 ) && function_exists( 'gzdecode' ) ) {
        $contents = gzdecode( $contents );
    }

    if ( ! $contents ) {
        return false;
    }

    $previous = libxml_use_internal_errors( true );
    $svg      = simplexml_load_string( $contents );
    libxml_clear_errors();
    libxml_use_internal_errors( $previous );

    if ( false === $svg ) {
        return false;
    }

    $attributes = $svg->attributes();
    $width      = isset( $attributes->width ) ? (int) round( (float) $attributes->width ) : 0;
    $height     = isset( $attributes->height ) ? (int) round( (float) $attributes->height ) : 0;

    if ( ( ! $width || ! $height ) && isset( $attributes->viewBox ) ) {
        $box = preg_split( '/[\s,]+/', trim( (string) $attributes->viewBox ) );

        if ( is_array( $box ) && 4 === count( $box ) ) {
            $width  = (int) round( (float) $box[2] );
            $height = (int) round( (float) $box[3] );
        }
    }

    if ( $width <= 0 || $height <= 0 ) {
        return false;
    }

    return array(
        'width'  => $width,
        'height' => $height,
    );
}

/**
 * Show a real preview for SVGs in the media modal.
 *
 * @param array   $response   Attachment response.
 * @param WP_Post $attachment Attachment post.
 * @param array   $meta       Attachment metadata.
 * @return array
 */
function seowk_svg_media_preview( $response, $attachment, $meta ) {
    if ( empty( $response['mime'] ) || 'image/svg+xml' !== $response['mime'] ) {
        return $response;
    }

    $svg_url = wp_get_attachment_url( $attachment->ID );

    if ( ! $svg_url ) {
        return $response;
    }

    $dimensions = seowk_get_svg_dimensions( get_attached_file( $attachment->ID ) );
    $width      = $dimensions ? $dimensions['width'] : 100;
    $height     = $dimensions ? $dimensions['height'] : 100;

    $response['image'] = array(
        'src'    => $svg_url,
        'width'  => $width,
        'height' => $height,
    );
    $response['sizes'] = array(
        'full' => array(
            'url'         => $svg_url,
            'width'       => $width,
            'height'      => $height,
            'orientation' => $height > $width ? 'portrait' : 'landscape',
        ),
    );

    return $response;
}
add_filter( 'wp_prepare_attachment_for_js', 'seowk_svg_media_preview', 10, 3 );

/**
 * Return the SVG itself for any requested image size.
 *
 * @param array|false $image         Image data.
 * @param int         $attachment_id Attachment ID.
 * @param string|int[] $size         Requested size.
 * @param bool        $icon          Whether an icon was requested.
 * @return array|false
 */
function seowk_svg_thumbnail_filter( $image, $attachment_id, $size, $icon ) {
    if ( 'image/svg+xml' !== get_post_mime_type( $attachment_id ) ) {
        return $image;
    }

    $url        = wp_get_attachment_url( $attachment_id );
    $dimensions = seowk_get_svg_dimensions( get_attached_file( $attachment_id ) );

    return array(
        $url,
        $dimensions ? $dimensions['width'] : 100,
        $dimensions ? $dimensions['height'] : 100,
        false,
    );
}
add_filter( 'wp_get_attachment_image_src', 'seowk_svg_thumbnail_filter', 10, 4 );
