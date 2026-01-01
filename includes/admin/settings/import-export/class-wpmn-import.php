<?php
/**
 * Media folder taxonomy
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WPMN_Import' ) ) :
	
	/**
     * Main WPMN_Import Class
     *
     * @class WPMN_Import
     * @version 1.0.0
     */
	class WPMN_Import {

		public static function import_folders_request() {

			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wpmn_media_nonce' ) ) :
                wp_die( esc_html__( 'Security check failed.', 'medianest' ) );
            endif;

			if ( empty($_FILES['csv_file']['tmp_name']) ) :
				wp_send_json_error(['message' => 'No file uploaded.']);
			endif;

			$file = sanitize_text_field( $_FILES['csv_file']['tmp_name'] );

			// Use WP_Filesystem instead of direct fopen
			require_once ABSPATH . 'wp-admin/includes/file.php';
			if ( ! function_exists( 'WP_Filesystem' ) ) :
				require_once ABSPATH . 'wp-admin/includes/file.php';
			endif;
			
			if ( ! WP_Filesystem() ) :
				wp_send_json_error( [ 'message' => 'Failed to initialize filesystem.' ] );
			endif;
			
			global $wp_filesystem;
			$csv_content = $wp_filesystem->get_contents( $file );
			
			if ( ! $csv_content ) :
				wp_send_json_error( [ 'message' => 'Cannot read file.' ] );
			endif;

			// Split content into lines and parse
			$lines  = explode( "\n", str_replace( "\r", "", $csv_content ) );
			$header = array();
			$rows   = array();

			foreach ( $lines as $i => $line ) :
				if ( empty( trim( $line ) ) ) continue;
				$data = str_getcsv( $line );

				if ( 0 === $i ) :
					$header = $data;
					if ( $header && isset( $header[0] ) ) :
						$header[0] = preg_replace( "/^\xEF\xBB\xBF/", '', $header[0] );
					endif;
					
					if ( ! $header || ! in_array( 'name', $header ) ) :
						wp_send_json_error( [ 'message' => 'Invalid CSV format.' ] );
					endif;
				else :
					if ( count( $data ) == count( $header ) ) :
						$rows[] = array_combine( $header, $data );
					endif;
				endif;
			endforeach;
			$id_map = [];

			// STEP 1 — Create all folders
			foreach ($rows as $row) :
				$name = sanitize_text_field($row['name']);
				if (!$name) :
					continue;
				endif;

				$result = wp_insert_term($name, 'wpmn_media_folder', array(
					'slug' => sanitize_title($name)
				) );

				$new_id = is_wp_error($result) ? (int) $result->get_error_data() : $result['term_id'];
				if (isset($row['id'])) :
					$id_map[$row['id']] = $new_id;
				endif;

				if (!empty($row['created_by'])) :
					update_term_meta($new_id, 'wpmn_folder_owner', absint($row['created_by']));
				endif;

				if (!empty($row['post_type'])) :
					update_term_meta($new_id, 'wpmn_post_type', sanitize_text_field($row['post_type']));
				endif;
			endforeach;

			// STEP 2 — Set parents + assign media
			foreach ($rows as $row) :
				if (empty($id_map[$row['id']])) :
					continue;
				endif;

				$new_id = $id_map[$row['id']];
				if (!empty($row['parent']) && !empty($id_map[$row['parent']])) :
					wp_update_term($new_id, 'wpmn_media_folder', array(
						'parent' => $id_map[$row['parent']]
					) );
				endif;

				// Attach media
				if (!empty($row['attachment_ids'])) :
					$att_ids = array_filter(array_map('absint', explode('|', $row['attachment_ids'])));
					foreach ($att_ids as $att_id) :
						wp_set_object_terms($att_id, [$new_id], 'wpmn_media_folder', false);
					endforeach;
				endif;
			endforeach;

			wp_send_json_success(WPMN_Media_Folders::payload());
		}
	}

	new WPMN_Import();

endif;
