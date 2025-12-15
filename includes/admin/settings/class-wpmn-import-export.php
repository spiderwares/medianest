<?php
/**
 * Media folder taxonomy
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WPMN_Import_Export' ) ) :

	class WPMN_Import_Export {

        public static function export_folders_request() {

			global $wpdb;

			$terms = get_terms(array(
				'taxonomy'   => 'wpmn_media_folder',
				'hide_empty' => false,
				'orderby'    => 'term_id',
				'order'      => 'ASC',
			) );

			if (is_wp_error($terms)) :
				wp_send_json_error(['message' => 'Failed to fetch folders.']);
			endif;

			$csv_data   = [['id','name','parent','type','ord','created_by','attachment_ids']];
			$grouped    = [];

			// Group folders by parent
			foreach ($terms as $term) :
				$grouped[$term->parent][] = $term;
			endforeach;

			foreach ($terms as $term) :
				$created_by  = get_term_meta($term->term_id, 'wpmn_folder_owner', true) ?: 1;
				$siblings    = isset($grouped[$term->parent]) ? $grouped[$term->parent] : [];
				$ord         = array_search($term, $siblings) ?: 0;

				// Get attachments inside this folder
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$attachment_ids = $wpdb->get_col($wpdb->prepare(
					"SELECT DISTINCT tr.object_id
					FROM {$wpdb->term_relationships} tr
					INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
					INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
					WHERE tt.taxonomy = %s AND tt.term_id = %d
					AND p.post_type = 'attachment' AND p.post_status != 'trash'
					ORDER BY tr.object_id ASC",
					'wpmn_media_folder', $term->term_id
				));

				$csv_data[] = array(	
					$term->term_id,
					$term->name,
					$term->parent,
					0,
					$ord,
					$created_by,
					implode('|', $attachment_ids ?: [])
				);
			endforeach;

			// Output CSV
			$filename = 'medianest.csv';
			header('Content-Type: text/csv; charset=utf-8');
			header("Content-Disposition: attachment; filename=$filename");

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
			$output = fopen('php://output', 'w');
			foreach ($csv_data as $row) :
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputcsv
				fputcsv($output, $row);
			endforeach;

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			fclose($output);
			exit;
		}

		public static function import_folders_request() {

			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wpmn_media_nonce' ) ) :
                wp_die( esc_html__( 'Security check failed.', 'medianest' ) );
            endif;

			if ( empty($_FILES['csv_file']['tmp_name']) ) :
				wp_send_json_error(['message' => 'No file uploaded.']);
			endif;

			$file   = sanitize_text_field( $_FILES['csv_file']['tmp_name'] );

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
			$handle = fopen($file, "r");
			if (!$handle) :
				wp_send_json_error(['message' => 'Cannot read file.']);
			endif;

			// Read header (remove BOM)
			
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fgetcsv
			$header = fgetcsv($handle);
			if ($header && isset($header[0])) :
				$header[0] = preg_replace("/^\xEF\xBB\xBF/", '', $header[0]);
			endif;
			if (!$header || !in_array('name', $header)) :
				wp_send_json_error(['message' => 'Invalid CSV format.']);
			endif;

			$rows = [];

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fgetcsv
			while (($row = fgetcsv($handle)) !== false) :
				if (count($row) == count($header)) :
					$rows[] = array_combine($header, $row);
				endif;
			endwhile;

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			fclose($handle);
			$id_map = [];

			// STEP 1 — Create all folders
			foreach ($rows as $r) :
				$name = sanitize_text_field($r['name']);
				if (!$name) :
					continue;
				endif;

				$result = wp_insert_term($name, 'wpmn_media_folder', array(
					'slug' => sanitize_title($name)
				) );

				$new_id = is_wp_error($result) ? (int) $result->get_error_data() : $result['term_id'];
				if (isset($r['id'])) :
					$id_map[$r['id']] = $new_id;
				endif;

				if (!empty($r['created_by'])) :
					update_term_meta($new_id, 'wpmn_folder_owner', absint($r['created_by']));
				endif;
			endforeach;

			// STEP 2 — Set parents + assign media
			foreach ($rows as $r) :
				if (empty($id_map[$r['id']])) :
					continue;
				endif;

				$new_id = $id_map[$r['id']];
				if (!empty($r['parent']) && !empty($id_map[$r['parent']])) :
					wp_update_term($new_id, 'wpmn_media_folder', array(
						'parent' => $id_map[$r['parent']]
					) );
				endif;

				// Attach media
				if (!empty($r['attachment_ids'])) :
					$att_ids = array_filter(array_map('absint', explode('|', $r['attachment_ids'])));
					foreach ($att_ids as $att_id) :
						wp_set_object_terms($att_id, [$new_id], 'wpmn_media_folder', false);
					endforeach;
				endif;
			endforeach;

			wp_send_json_success(WPMN_Media_Folders::payload());
		}
	}

	new WPMN_Import_Export();

endif;
