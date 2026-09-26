<?php
/**
 * Frontend: asset enqueue and content filtering.
 *
 * @package DecentLightbox
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SEOWK_Lightbox_Frontend
 *
 * Enqueues the lightbox stylesheet and script and decorates rendered images
 * with the markup the script needs to wire up the lightbox.
 */
final class SEOWK_Lightbox_Frontend {

	/**
	 * Whether at least one lightbox-enabled image was rendered on the page.
	 *
	 * Used to decide whether the JS/CSS actually need to load.
	 *
	 * @var bool
	 */
	private bool $needs_assets = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_action( 'wp_footer', array( $this, 'maybe_enqueue_assets' ) );

		// Filter image blocks (Gutenberg).
		add_filter( 'render_block', array( $this, 'filter_image_block' ), 10, 2 );

		// Fallback for classic editor / shortcodes.
		add_filter( 'the_content', array( $this, 'filter_content_images' ), 20 );
	}

	/**
	 * Registers (but does not yet enqueue) the assets.
	 *
	 * @return void
	 */
	public function register_assets(): void {
		wp_register_style(
			'seowk-lightbox',
			SEOWK_LIGHTBOX_URL . 'assets/css/lightbox.css',
			array(),
			SEOWK_LIGHTBOX_VERSION
		);

		wp_register_script(
			'seowk-lightbox',
			SEOWK_LIGHTBOX_URL . 'assets/js/lightbox.js',
			array(),
			SEOWK_LIGHTBOX_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		// No wp_set_script_translations(): the script gets its strings from PHP
		// (wp_localize_script() below) and would otherwise pull in wp-i18n.
	}

	/**
	 * Enqueues the registered assets if the page contains any lightbox image.
	 *
	 * @return void
	 */
	public function maybe_enqueue_assets(): void {
		if ( ! $this->needs_assets ) {
			return;
		}

		wp_enqueue_style( 'seowk-lightbox' );
		wp_enqueue_script( 'seowk-lightbox' );

		// Pass localized strings for accessibility / screen readers.
		wp_localize_script(
			'seowk-lightbox',
			'decentLightboxL10n',
			array(
				'close'    => __( 'Close lightbox', 'wunderkiste-toolkit' ),
				'previous' => __( 'Previous image', 'wunderkiste-toolkit' ),
				'next'     => __( 'Next image', 'wunderkiste-toolkit' ),
				'loading'  => __( 'Loading image…', 'wunderkiste-toolkit' ),
			)
		);
	}

	/**
	 * Filters core/image blocks and adds the lightbox attributes when enabled.
	 *
	 * @param string              $block_content Rendered block HTML.
	 * @param array<string,mixed> $block         Parsed block array.
	 * @return string
	 */
	public function filter_image_block( $block_content, $block ): string {
		$block_content = (string) $block_content;
		$block         = (array) $block;

		if ( ! isset( $block['blockName'] ) || 'core/image' !== $block['blockName'] ) {
			return $block_content;
		}

		$attachment_id = isset( $block['attrs']['id'] ) ? (int) $block['attrs']['id'] : 0;

		if ( ! SEOWK_Lightbox::is_enabled_for_attachment( $attachment_id ) ) {
			return $block_content;
		}

		$full_src = $this->get_full_image_url( $attachment_id );

		if ( '' === $full_src ) {
			return $block_content;
		}

		$decorated = $this->decorate_image_html( $block_content, $attachment_id, $full_src );

		if ( $decorated !== $block_content ) {
			$this->needs_assets = true;
		}

		return $decorated;
	}

	/**
	 * Falls back to scanning post content for `wp-image-{id}` images.
	 *
	 * Useful for classic editor content or HTML produced by shortcodes that
	 * does not pass through the block renderer.
	 *
	 * @param string $content Post content HTML.
	 * @return string
	 */
	public function filter_content_images( $content ): string {
		// Not typed as string: with strict_types a filter that passes null
		// (some plugins do) would throw a TypeError and take the page down.
		$content = (string) $content;

		if ( '' === $content || false === stripos( $content, 'wp-image-' ) ) {
			return $content;
		}

		$processor = new WP_HTML_Tag_Processor( $content );

		while ( $processor->next_tag( array( 'tag_name' => 'img' ) ) ) {
			// Skip if we already decorated this image (block path).
			if ( null !== $processor->get_attribute( 'data-decent-lightbox' ) ) {
				continue;
			}

			$class = $processor->get_attribute( 'class' );

			if ( ! is_string( $class ) || ! preg_match( '/wp-image-(\d+)/', $class, $matches ) ) {
				continue;
			}

			$attachment_id = (int) $matches[1];

			if ( ! SEOWK_Lightbox::is_enabled_for_attachment( $attachment_id ) ) {
				continue;
			}

			$full_src = $this->get_full_image_url( $attachment_id );

			if ( '' === $full_src ) {
				continue;
			}

			$processor->set_attribute( 'data-decent-lightbox', '1' );
			$processor->set_attribute( 'data-decent-lightbox-full', $full_src );

			$this->needs_assets = true;
		}

		return $processor->get_updated_html();
	}

	/**
	 * Returns the full-size URL for an attachment.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return string
	 */
	private function get_full_image_url( int $attachment_id ): string {
		$src = wp_get_attachment_image_url( $attachment_id, 'full' );

		return is_string( $src ) ? $src : '';
	}

	/**
	 * Decorates an `<img>` element inside rendered HTML with lightbox attributes.
	 *
	 * @param string $html          The HTML containing the image.
	 * @param int    $attachment_id Attachment ID.
	 * @param string $full_src      URL of the full-size image.
	 * @return string
	 */
	private function decorate_image_html( string $html, int $attachment_id, string $full_src ): string {
		$processor = new WP_HTML_Tag_Processor( $html );

		if ( ! $processor->next_tag( array( 'tag_name' => 'img' ) ) ) {
			return $html;
		}

		$processor->set_attribute( 'data-decent-lightbox', '1' );
		$processor->set_attribute( 'data-decent-lightbox-full', $full_src );
		$processor->set_attribute( 'data-decent-lightbox-id', (string) $attachment_id );

		return $processor->get_updated_html();
	}
}
