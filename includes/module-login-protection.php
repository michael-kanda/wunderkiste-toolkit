<?php
/**
 * MODULE: Login Guard (Login Türsteher)
 *
 * Hides wp-login.php behind a secret query parameter.
 *
 * Rewritten in 2.11. The previous version redirected every unauthenticated
 * request whose URI contained "wp-login.php" - including the POST that the
 * login form itself sends, which carries no query string. That locked out
 * legitimate users as well as bots. It also shipped a published default key.
 *
 * @package Wunderkiste_Toolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Name of the pass cookie.
 */
const SEOWK_LOGIN_GATE_COOKIE = 'seowk_login_gate';

/**
 * The configured secret key, or an empty string when none is set.
 *
 * There is deliberately no fallback default: a default that is printed in the
 * plugin source, the readme and the settings placeholder protects nothing.
 *
 * @return string
 */
function seowk_login_protection_key() {
    $options = get_option( 'seowk_settings', array() );
    $key     = isset( $options['seowk_login_protection_key'] ) ? trim( (string) $options['seowk_login_protection_key'] ) : '';

    return $key;
}

/**
 * Value stored in the pass cookie for the current key.
 *
 * @param string $key Secret key.
 * @return string
 */
function seowk_login_gate_token( $key ) {
    return wp_hash( 'seowk-login-gate|' . $key );
}

/**
 * Login screen actions that must stay reachable without the secret.
 *
 * Blocking these would break logout, password resets, protected-post
 * passwords, admin e-mail confirmation and recovery mode.
 *
 * @return string[]
 */
function seowk_login_protection_allowed_actions() {
    return (array) apply_filters(
        'seowk_login_allowed_actions',
        array(
            'logout',
            'postpass',
            'rp',
            'resetpass',
            'lostpassword',
            'confirmaction',
            'confirm_admin_email',
            'entered_recovery_mode',
        )
    );
}

/**
 * Gatekeeper.
 *
 * @return void
 */
function seowk_secret_login_parameter() {
    global $pagenow;

    if ( 'wp-login.php' !== $pagenow ) {
        return;
    }

    if ( is_user_logged_in() ) {
        return;
    }

    $key = seowk_login_protection_key();

    // No key configured: the module stays inert rather than using a guessable
    // default. The settings screen warns about this.
    if ( '' === $key ) {
        return;
    }

    if ( wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
        return;
    }

    $action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : 'login'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

    if ( in_array( $action, seowk_login_protection_allowed_actions(), true ) ) {
        return;
    }

    $token = seowk_login_gate_token( $key );

    // Already passed the gate in this browser: let everything through,
    // including the POST that the login form sends.
    if ( isset( $_COOKIE[ SEOWK_LOGIN_GATE_COOKIE ] )
        && hash_equals( $token, (string) wp_unslash( $_COOKIE[ SEOWK_LOGIN_GATE_COOKIE ] ) ) ) {
        return;
    }

    // Correct secret in the URL: issue the pass cookie and continue.
    if ( isset( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        /**
         * Filter how long the login gate stays open for a visitor.
         *
         * @param int $lifetime Lifetime in seconds. Default one hour.
         */
        $lifetime = (int) apply_filters( 'seowk_login_gate_lifetime', HOUR_IN_SECONDS );

        if ( ! headers_sent() ) {
            setcookie(
                SEOWK_LOGIN_GATE_COOKIE,
                $token,
                array(
                    'expires'  => time() + $lifetime,
                    'path'     => defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/',
                    'domain'   => defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '',
                    'secure'   => is_ssl(),
                    'httponly' => true,
                    'samesite' => 'Lax',
                )
            );
        }

        return;
    }

    wp_safe_redirect( home_url( '/' ) );
    exit;
}
add_action( 'init', 'seowk_secret_login_parameter' );

/**
 * Clear the pass cookie on logout.
 *
 * @return void
 */
function seowk_login_gate_clear_cookie() {
    if ( headers_sent() ) {
        return;
    }

    setcookie(
        SEOWK_LOGIN_GATE_COOKIE,
        '',
        array(
            'expires'  => time() - YEAR_IN_SECONDS,
            'path'     => defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/',
            'domain'   => defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '',
            'secure'   => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        )
    );
}
add_action( 'wp_logout', 'seowk_login_gate_clear_cookie' );

/**
 * Warn when the module is on but no key has been set.
 *
 * @return void
 */
function seowk_login_protection_notice() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( '' !== seowk_login_protection_key() ) {
        return;
    }

    $url = admin_url( 'options-general.php?page=wunderkiste-toolkit' );
    ?>
    <div class="notice notice-warning">
        <p>
            <strong><?php esc_html_e( 'Wunderkiste Toolkit:', 'wunderkiste-toolkit' ); ?></strong>
            <?php
            printf(
                /* translators: %s: settings page URL */
                esc_html__( 'The Login Guard is active, but no key is set. Until you set your own key, the login page stays publicly reachable. %s', 'wunderkiste-toolkit' ),
                '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Set it now', 'wunderkiste-toolkit' ) . '</a>'
            );
            ?>
        </p>
    </div>
    <?php
}
add_action( 'admin_notices', 'seowk_login_protection_notice' );
