<?php
/**
 * Media folder taxonomy
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WPMN_Helper' ) ) :

	class WPMN_Helper {

		public static function create_folder( $name, $parent ) {

			$result = wp_insert_term( $name, 'wpmn_media_folder', array( 'parent' => $parent ) );
			if ( is_wp_error( $result ) && $result->get_error_code() === 'term_exists' ) {

				$counter = 1;
				while ( $counter <= 100 ) :
					$new_name = $name . ' (' . $counter . ')';
					if ( ! term_exists( $new_name, 'wpmn_media_folder', $parent ) ) :

						return wp_insert_term( 
							$new_name, 
							'wpmn_media_folder',
							array( 'parent' => $parent )
						);
					endif;
					$counter++;
				endwhile;
			}
			return $result;
		}

		public static function create_folder_request() {
			$name   = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
			$parent = isset($_POST['parent']) ? absint($_POST['parent']) : 0;

			if (!$name) :
				wp_send_json_error(array('message' => 'Folder name is required.'));
			endif;

			$result = self::create_folder($name, $parent);
			self::send_response($result);
		}

		public static function rename_folder_request() {
			$id   = isset($_POST['folder_id']) ? absint($_POST['folder_id']) : 0;
			$name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';

			if (!$id || !$name) :
				wp_send_json_error(['message' => 'Invalid folder data.']);
			endif;

			$result = wp_update_term($id, 'wpmn_media_folder', array(
				'name' => $name,
				'slug' => sanitize_title($name)
			) );

			self::send_response($result);
		}

		public static function delete_folder_request() {
			$id = isset($_POST['folder_id']) ? absint($_POST['folder_id']) : 0;

			if (!$id) :
				wp_send_json_error(['message' => 'Invalid folder.']);
			endif;

			$result = wp_delete_term($id, 'wpmn_media_folder');
			self::send_response($result);
		}

		public static function delete_folders_bulk_request() {
			$ids = isset($_POST['folder_ids']) ? array_map('absint', (array) $_POST['folder_ids']) : [];

			if (empty($ids)) :
				wp_send_json_error(['message' => 'No folders selected.']);
			endif;

			foreach ($ids as $id) :
				wp_delete_term($id, 'wpmn_media_folder');
			endforeach;
			wp_send_json_success(WPMN_Media_Folders::payload());
		}

		public static function assign_media_request() {
			global $wpdb;
			
			$folder_id = isset($_POST['folder_id']) ? absint($_POST['folder_id']) : 0;
			$items     = isset($_POST['attachment_ids']) ? array_map('absint', (array) $_POST['attachment_ids']) : [];

			if (empty($items)) :
				wp_send_json_error(['message' => 'No media selected.']);
			endif;

			foreach ($items as $attachment_id) :
				$wpdb->query( $wpdb->prepare(
					"DELETE tr FROM {$wpdb->term_relationships} tr
					INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
					WHERE tr.object_id = %d AND tt.taxonomy = %s",
					$attachment_id, 'wpmn_media_folder'
				));
				
				if ( $folder_id > 0 ) :
					// Verify term exists and get taxonomy_id
					$tt_id = $wpdb->get_var( $wpdb->prepare(
						"SELECT tt.term_taxonomy_id FROM {$wpdb->term_taxonomy} tt
						WHERE tt.term_id = %d AND tt.taxonomy = %s",
						$folder_id, 'wpmn_media_folder'
					));
					
					if ( $tt_id ) :
						$wpdb->insert( $wpdb->term_relationships, array(
							'object_id' => $attachment_id,
							'term_taxonomy_id' => $tt_id,
							'term_order' => 0
						), array( '%d', '%d', '%d' ));
					endif;
				endif;
				
				clean_object_term_cache( $attachment_id, 'attachment' );
			endforeach;
			
			if ( $folder_id > 0 ) :
				wp_update_term_count_now( array( $folder_id ), 'wpmn_media_folder' );
			endif;
			wp_send_json_success(WPMN_Media_Folders::payload());
		}

		public static function clear_all_data_request() {

			$terms = get_terms(array(
				'taxonomy'   => 'wpmn_media_folder',
				'hide_empty' => false,
			) );

			if ( ! is_wp_error( $terms ) ) :
				foreach ($terms as $term) :
					wp_delete_term($term->term_id, 'wpmn_media_folder');
				endforeach;
			endif;

			delete_option('wpmn_settings');
			wp_send_json_success(['message' => esc_html__('All data cleared.', 'medianest')]);
		}

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
			foreach ($terms as $t) :
				$grouped[$t->parent][] = $t;
			endforeach;

			foreach ($terms as $term) :
				$created_by  = get_term_meta($term->term_id, 'wpmn_folder_owner', true) ?: 1;
				$siblings    = isset($grouped[$term->parent]) ? $grouped[$term->parent] : [];
				$ord         = array_search($term, $siblings) ?: 0;

				// Get attachments inside this folder
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

			$output = fopen('php://output', 'w');
			fwrite($output, "\xEF\xBB\xBF");
			foreach ($csv_data as $row) :
				fputcsv($output, $row);
			endforeach;

			fclose($output);
			exit;
		}

		public static function move_folder_request() {
			$folder_id  = absint($_POST['folder_id'] ?? 0);
			$new_parent = absint($_POST['new_parent'] ?? 0);

			if (!$folder_id) :
				wp_send_json_error(['message' => 'Invalid folder.']);
			endif;

			if (is_wp_error(get_term($folder_id, 'wpmn_media_folder'))) :
				wp_send_json_error(['message' => 'Folder not found.']);
			endif;

			$result = wp_update_term($folder_id, 'wpmn_media_folder', array(
				'parent' => $new_parent
			) );

			self::send_response($result);
		}

		public static function send_response($result) {
			if (is_wp_error($result)) :
				wp_send_json_error(['message' => $result->get_error_message()]);
			endif;
			wp_send_json_success(WPMN_Media_Folders::payload());
		}

        public static function generate_attachment_size_request() {
            $attachments = get_posts( array(
                'post_type'      => 'attachment',
                'post_status'    => 'inherit',
                'posts_per_page' => -1,
                'fields'         => 'ids',
            ) );

            $count = 0;
            foreach ( $attachments as $id ) :	
                $path = get_attached_file( $id );
                if ( $path && file_exists( $path ) ) :
                    $size = filesize( $path );
                    update_post_meta( $id, 'wpmn_filesize', $size );
                    $count++;
                endif;
            endforeach;

            wp_send_json_success( array(
                'message' => sprintf( esc_html__( 'Generated sizes for %d attachments.', 'medianest' ), $count )
            ));
        }
	}

	new WPMN_Helper();

endif;
