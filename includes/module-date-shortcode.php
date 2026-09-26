<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ------------------------------------------------------------------------- *
 * MODULE: DATE SHORTCODE - Wunderkiste Toolkit
 * ------------------------------------------------------------------------- */

function seowk_get_german_months() {
    return array( 1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April', 5 => 'Mai', 6 => 'Juni', 
                  7 => 'Juli', 8 => 'August', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember' );
}

function seowk_get_german_weekdays() {
    return array( 'Monday' => 'Montag', 'Tuesday' => 'Dienstag', 'Wednesday' => 'Mittwoch',
        'Thursday' => 'Donnerstag', 'Friday' => 'Freitag', 'Saturday' => 'Samstag', 'Sunday' => 'Sonntag' );
}

function seowk_get_date_presets() {
    return array( 'numeric' => 'd.m.Y', 'numeric_short' => 'j.n.Y', 'full' => 'j. F Y', 'full_day' => 'l, j. F Y',
        'month_year' => 'F Y', 'year' => 'Y', 'month' => 'F', 'day' => 'l', 'iso' => 'Y-m-d', 'us' => 'm/d/Y',
        'time' => 'H:i', 'datetime' => 'd.m.Y H:i' );
}

function seowk_generate_date_output( $atts ) {
    $atts = wp_parse_args( $atts, array( 'format' => 'numeric', 'timezone' => '', 'prefix' => '', 'suffix' => '', 'wrapper' => '', 'class' => '', 'lang' => '' ) );
    
    if ( ! empty( $atts['timezone'] ) ) {
        try { $tz = new DateTimeZone( $atts['timezone'] ); $datetime = new DateTime( 'now', $tz ); }
        catch ( Exception $e ) { $datetime = new DateTime( 'now', wp_timezone() ); }
    } else { $datetime = new DateTime( 'now', wp_timezone() ); }

    $presets = seowk_get_date_presets();
    $format = isset( $presets[ $atts['format'] ] ) ? $presets[ $atts['format'] ] : $atts['format'];
    $lang = strtolower( (string) $atts['lang'] );

    if ( 'de' === $lang || 'en' === $lang ) {
        // Forced language: PHP gives English names, mapped to German if asked for.
        $raw = $datetime->format( $format );
        if ( 'de' === $lang ) {
            $english_months = array( 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' );
            $raw = str_replace( $english_months, array_values( seowk_get_german_months() ), $raw );
            $weekdays = seowk_get_german_weekdays();
            $raw = str_replace( array_keys( $weekdays ), array_values( $weekdays ), $raw );
        }
    } else {
        // Default: month and day names follow the site language.
        $raw = wp_date( $format, $datetime->getTimestamp(), $datetime->getTimezone() );
    }

    /*
     * Escaped right here: format() passes unknown characters through verbatim,
     * so a shortcode attribute like format="<\s\c\r\i\p\t>" would otherwise
     * emit raw markup and bypass kses for users without unfiltered_html.
     */
    $date_output = esc_html( (string) $raw );

    $date_output = esc_html( $atts['prefix'] ) . $date_output . esc_html( $atts['suffix'] );

    if ( ! empty( $atts['wrapper'] ) ) {
        $allowed_tags = array( 'span', 'time', 'div', 'p', 'strong', 'em' );
        $tag = in_array( strtolower( $atts['wrapper'] ), $allowed_tags, true ) ? strtolower( $atts['wrapper'] ) : 'span';
        $attributes = ! empty( $atts['class'] ) ? ' class="' . esc_attr( $atts['class'] ) . '"' : '';
        if ( $tag === 'time' ) { $attributes .= ' datetime="' . esc_attr( $datetime->format( 'c' ) ) . '"'; }
        $date_output = '<' . $tag . $attributes . '>' . $date_output . '</' . $tag . '>';
    }
    return $date_output;
}

function seowk_date_shortcode( $atts ) {
    $atts = shortcode_atts( array( 'format' => 'numeric', 'timezone' => '', 'prefix' => '', 'suffix' => '', 'wrapper' => '', 'class' => '', 'lang' => '' ), $atts, 'seowk_date' );
    return seowk_generate_date_output( $atts );
}
add_shortcode( 'seowk_date', 'seowk_date_shortcode' );

function seowk_year_shortcode( $atts ) {
    $atts = is_array( $atts ) ? $atts : array();
    $atts['format'] = 'year';
    return seowk_date_shortcode( $atts );
}
add_shortcode( 'seowk_year', 'seowk_year_shortcode' );

function seowk_month_shortcode( $atts ) {
    $atts = is_array( $atts ) ? $atts : array();
    $atts['format'] = 'month';
    return seowk_date_shortcode( $atts );
}
add_shortcode( 'seowk_month', 'seowk_month_shortcode' );

/*
 * Legacy aliases [datum], [jahr] and [monat] from earlier versions. The names
 * are generic enough to clash with other plugins, so they are opt-in and never
 * replace a shortcode another plugin already registered.
 */
function seowk_legacy_date_shortcodes_enabled() {
    $options = seowk_get_options();
    // Settings saved before 2.12 do not have this key yet: those sites already
    // used the old names, so they keep working until the setting is saved.
    if ( ! array_key_exists( 'seowk_date_legacy_shortcodes', $options ) ) {
        return true;
    }
    return ! empty( $options['seowk_date_legacy_shortcodes'] );
}

function seowk_register_legacy_date_shortcodes() {
    if ( ! seowk_legacy_date_shortcodes_enabled() ) {
        return;
    }
    $legacy = array(
        'datum' => 'seowk_date_shortcode',
        'jahr'  => 'seowk_year_shortcode',
        'monat' => 'seowk_month_shortcode',
    );
    foreach ( $legacy as $tag => $callback ) {
        if ( ! shortcode_exists( $tag ) ) {
            add_shortcode( $tag, $callback );
        }
    }
}
add_action( 'init', 'seowk_register_legacy_date_shortcodes', 20 );
