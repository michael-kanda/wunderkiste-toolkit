<?php
/**
 * Main plugin class.
 *
 * @package DecentLightbox
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SEOWK_Lightbox
 *
 * Bootstraps the plugin and registers the meta field that drives the
 * per-image lightbox toggle.
 */
final class SEOWK_Lightbox {

	/**
	 * Singleton instance.
	 *
	 * @var SEOWK_Lightbox|null
	 */
	private static ?SEOWK_Lightbox $instance = null;

	/**
	 * Returns the singleton instance.
	 *
	 * @return SEOWK_Lightbox
	 */
	public static function instance(): SEOWK_Lightbox {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'register_meta' ) );

		// Boot subsystems.
		new SEOWK_Lightbox_Admin();
		new SEOWK_Lightbox_Frontend();
	}

	/**
	 * Registers the post meta that stores the per-image lightbox flag.
	 *
	 * Registered with `show_in_rest` so the block editor and REST clients
	 * can read and write the value too.
	 *
	 * @return void
	 */
	public function register_meta(): void {
		register_post_meta(
			'attachment',
			SEOWK_LIGHTBOX_META_KEY,
			array(
				'type'              => 'boolean',
				'description'       => __( 'Whether to open this image in the Decent Lightbox.', 'wunderkiste-toolkit' ),
				'single'            => true,
				'default'           => false,
				'show_in_rest'      => true,
				'sanitize_callback' => array( $this, 'sanitize_bool_meta' ),
				'auth_callback'     => static function ( bool $allowed, string $meta_key, int $object_id ): bool {
					return current_user_can( 'edit_post', $object_id );
				},
			)
		);
	}

	/**
	 * Sanitizes a boolean meta value.
	 *
	 * @param mixed $value Raw meta value.
	 * @return bool
	 */
	public function sanitize_bool_meta( $value ): bool {
		return (bool) rest_sanitize_boolean( $value );
	}

	/**
	 * Returns whether lightbox is enabled for the given attachment.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	public static function is_enabled_for_attachment( int $attachment_id ): bool {
		if ( $attachment_id <= 0 ) {
			return false;
		}

		return (bool) get_post_meta( $attachment_id, SEOWK_LIGHTBOX_META_KEY, true );
	}
}
