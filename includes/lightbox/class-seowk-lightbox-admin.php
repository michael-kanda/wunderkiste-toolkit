<?php
/**
 * Admin: Media Library integration.
 *
 * @package DecentLightbox
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SEOWK_Lightbox_Admin
 *
 * Adds a checkbox to the attachment details (Media Library modal and the
 * full attachment edit screen) that lets users enable or disable the
 * lightbox for individual images.
 */
final class SEOWK_Lightbox_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'attachment_fields_to_edit', array( $this, 'add_attachment_field' ), 10, 2 );
		add_filter( 'attachment_fields_to_save', array( $this, 'save_attachment_field' ), 10, 2 );
		add_filter( 'manage_media_columns', array( $this, 'add_media_column' ) );
		add_action( 'manage_media_custom_column', array( $this, 'render_media_column' ), 10, 2 );
	}

	/**
	 * Adds the "Open in lightbox" checkbox to the attachment edit form.
	 *
	 * @param array<string,mixed> $form_fields Existing form fields.
	 * @param WP_Post             $post        Attachment post object.
	 * @return array<string,mixed>
	 */
	public function add_attachment_field( array $form_fields, WP_Post $post ): array {
		// Only show for image attachments.
		if ( ! wp_attachment_is_image( $post->ID ) ) {
			return $form_fields;
		}

		$enabled = SEOWK_Lightbox::is_enabled_for_attachment( $post->ID );

		$checkbox  = '<input type="checkbox" id="attachments-' . esc_attr( (string) $post->ID ) . '-decent_lightbox" ';
		$checkbox .= 'name="attachments[' . esc_attr( (string) $post->ID ) . '][decent_lightbox]" value="1" ';
		$checkbox .= checked( $enabled, true, false ) . ' /> ';
		$checkbox .= '<label for="attachments-' . esc_attr( (string) $post->ID ) . '-decent_lightbox">';
		$checkbox .= esc_html__( 'Open this image in the lightbox on click', 'wunderkiste-toolkit' );
		$checkbox .= '</label>';

		$form_fields[ SEOWK_LIGHTBOX_META_KEY ] = array(
			'label' => __( 'Lightbox', 'wunderkiste-toolkit' ),
			'input' => 'html',
			'html'  => $checkbox,
			'helps' => __( 'When enabled, clicking the image on the front end opens it in a lightweight overlay.', 'wunderkiste-toolkit' ),
		);

		return $form_fields;
	}

	/**
	 * Saves the lightbox flag from the attachment edit form.
	 *
	 * @param array<string,mixed> $post       Sanitized post data.
	 * @param array<string,mixed> $attachment Raw form data for this attachment.
	 * @return array<string,mixed>
	 */
	public function save_attachment_field( array $post, array $attachment ): array {
		$post_id = isset( $post['ID'] ) ? (int) $post['ID'] : 0;

		if ( $post_id <= 0 || ! current_user_can( 'edit_post', $post_id ) ) {
			return $post;
		}

		$enabled = ! empty( $attachment['decent_lightbox'] );

		if ( $enabled ) {
			update_post_meta( $post_id, SEOWK_LIGHTBOX_META_KEY, true );
		} else {
			delete_post_meta( $post_id, SEOWK_LIGHTBOX_META_KEY );
		}

		return $post;
	}

	/**
	 * Adds a "Lightbox" column to the Media list view.
	 *
	 * @param array<string,string> $columns Existing columns.
	 * @return array<string,string>
	 */
	public function add_media_column( array $columns ): array {
		$columns['decent_lightbox'] = __( 'Lightbox', 'wunderkiste-toolkit' );
		return $columns;
	}

	/**
	 * Renders the "Lightbox" column content.
	 *
	 * @param string $column_name Column slug.
	 * @param int    $post_id     Attachment ID.
	 * @return void
	 */
	public function render_media_column( string $column_name, int $post_id ): void {
		if ( 'decent_lightbox' !== $column_name ) {
			return;
		}

		if ( ! wp_attachment_is_image( $post_id ) ) {
			echo '<span aria-hidden="true">—</span>';
			return;
		}

		if ( SEOWK_Lightbox::is_enabled_for_attachment( $post_id ) ) {
			echo '<span class="dashicons dashicons-yes" aria-hidden="true"></span>';
			echo '<span class="screen-reader-text">' . esc_html__( 'Lightbox enabled', 'wunderkiste-toolkit' ) . '</span>';
		} else {
			echo '<span aria-hidden="true">—</span>';
			echo '<span class="screen-reader-text">' . esc_html__( 'Lightbox disabled', 'wunderkiste-toolkit' ) . '</span>';
		}
	}
}
