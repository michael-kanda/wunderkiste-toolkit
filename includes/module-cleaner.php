<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ------------------------------------------------------------------------- *
 * MODULE: Upload Filename Cleaner
 * ------------------------------------------------------------------------- */

function seowk_sanitize_upload_filename( $filename ) {
    $info = pathinfo( $filename );
    $ext  = empty( $info['extension'] ) ? '' : '.' . $info['extension'];
    $name = basename( $filename, $ext );
    $name = strtolower( $name );
    $ext  = strtolower( $ext );
    $name = str_replace( array( 'ä', 'ö', 'ü', 'ß' ), array( 'ae', 'oe', 'ue', 'ss' ), $name );
    $name = str_replace( ' ', '-', $name );
    return $name . $ext;
}
add_filter( 'sanitize_file_name', 'seowk_sanitize_upload_filename', 10 );
