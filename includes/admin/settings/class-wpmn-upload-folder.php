<?php
/**
 * Media Upload Folder Selector
 *
 * Adds a folder selector to the WordPress media upload form
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WPMN_Upload_Folder' ) ) :

	class WPMN_Upload_Folder {

		/**
		 * Constructor
		 */
		public function __construct() {
			add_action( 'pre-upload-ui', [ $this, 'render_folder_selector' ] );
			add_action( 'wp_ajax_wpmn_get_folders_for_upload', [ $this, 'ajax_get_folders' ] );
			add_action( 'wp_ajax_wpmn_assign_uploaded_media', [ $this, 'ajax_assign_media' ] );
		}

		/**
		 * Render the folder selector dropdown in the media upload section
		 */
		public function render_folder_selector() {
			wpmn_get_template(
				'media/upload-folder.php',
				array(),
			);
		}		
		
		/**
		 * AJAX: Get folders for the upload dropdown
		 */
		public function ajax_get_folders() {
			check_ajax_referer( 'wpmn_upload_nonce', 'nonce' );

			// Get folders using the same logic as the sidebar
			$folders = $this->get_folder_tree();
			wp_send_json_success( [ 'folders' => $folders ] );
		}

		/**
		 * Build folder tree
		 */
		private function get_folder_tree() {
			$terms = get_terms( [
				'taxonomy'   => 'wpmn_media_folder',
				'hide_empty' => false,
				'orderby'    => 'term_id',
				'order'      => 'ASC',
			] );

			if ( is_wp_error( $terms ) ) {
				return [];
			}

			$group = [];
			foreach ( $terms as $term ) {
				$group[ $term->parent ][] = $term;
			}

			return $this->build_tree( 0, $group );
		}

		/**
		 * Recursively build tree structure
		 */
		private function build_tree( $parent, $group ) {
			if ( empty( $group[ $parent ] ) ) {
				return [];
			}

			$list = [];
			foreach ( $group[ $parent ] as $term ) {
				$children = $this->build_tree( $term->term_id, $group );
				$list[] = [
					'id'       => $term->term_id,
					'name'     => $term->name,
					'children' => $children,
				];
			}

			return $list;
		}

		/**
		 * AJAX: Assign uploaded media to selected folder
		 */
		public function ajax_assign_media() {
			check_ajax_referer( 'wpmn_upload_nonce', 'nonce' );

			$attachment_id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;
			$folder_id     = isset( $_POST['folder_id'] ) ? sanitize_text_field( $_POST['folder_id'] ) : '';

			if ( ! $attachment_id || ! $folder_id ) {
				wp_send_json_error( [ 'message' => 'Invalid data' ] );
			}

			// Parse folder ID (could be "term-123" or "uncategorized" or "all")
			$term_id = 0;
			if ( strpos( $folder_id, 'term-' ) === 0 ) {
				$term_id = absint( str_replace( 'term-', '', $folder_id ) );
			}

			// Assign media to folder
			if ( $term_id > 0 ) {
				wp_set_object_terms( $attachment_id, [ $term_id ], 'wpmn_media_folder' );
			}

			wp_send_json_success( [ 'message' => 'Media assigned to folder' ] );
		}
	}

	new WPMN_Upload_Folder();

endif;
