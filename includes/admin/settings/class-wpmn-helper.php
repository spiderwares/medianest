<?php
/**
 * Media folder taxonomy + AJAX handling
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WPMN_Helper' ) ) :

	class WPMN_Helper {

		public static function create_folder($name, $parent) {

			$result = wp_insert_term($name, 'wpmn_media_folder', ['parent' => $parent]);
			if (is_wp_error($result) && $result->get_error_code() === 'term_exists') {
				$zws = "\xE2\x80\x8B";
				for ($i = 1; $i <= 20; $i++) {
					$new = $name . str_repeat($zws, $i);

					if (!term_exists($new, 'wpmn_media_folder', $parent)) {
						return wp_insert_term($new, 'wpmn_media_folder', ['parent' => $parent]);
					}
				}
			}

			return $result;
		}

		public static function create_folder_request() {
			$name   = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
			$parent = isset($_POST['parent']) ? absint($_POST['parent']) : 0;

			if (!$name) {
				wp_send_json_error(['message' => 'Folder name is required.']);
			}

			$result = self::create_folder($name, $parent);
			self::send_response($result);
		}

		public static function rename_folder_request() {
			$id   = isset($_POST['folder_id']) ? absint($_POST['folder_id']) : 0;
			$name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';

			if (!$id || !$name) {
				wp_send_json_error(['message' => 'Invalid folder data.']);
			}

			$result = wp_update_term($id, 'wpmn_media_folder', [
				'name' => $name,
				'slug' => sanitize_title($name)
			]);

			self::send_response($result);
		}

		public static function delete_folder_request() {
			$id = isset($_POST['folder_id']) ? absint($_POST['folder_id']) : 0;

			if (!$id) {
				wp_send_json_error(['message' => 'Invalid folder.']);
			}

			$result = wp_delete_term($id, 'wpmn_media_folder');
			self::send_response($result);
		}

		public static function assign_media_request() {
			$folder_id = isset($_POST['folder_id']) ? absint($_POST['folder_id']) : 0;
			$items     = isset($_POST['attachment_ids']) ? array_map('absint', (array) $_POST['attachment_ids']) : [];

			if (empty($items)) {
				wp_send_json_error(['message' => 'No media selected.']);
			}

			foreach ($items as $id) {
				wp_set_object_terms($id, $folder_id ? [$folder_id] : [], 'wpmn_media_folder');
			}

			wp_send_json_success(WPMN_Media_Folders::payload());
		}

		public static function clear_all_data_request() {

			$terms = get_terms([
				'taxonomy'   => 'wpmn_media_folder',
				'hide_empty' => false,
			]);

			if ( ! is_wp_error( $terms ) ) {
				foreach ($terms as $term) {
					wp_delete_term($term->term_id, 'wpmn_media_folder');
				}
			}

			delete_option('wpmn_settings');
			wp_send_json_success(['message' => esc_html__('All data cleared.', 'medianest')]);
		}

		public static function send_response($result) {
			if (is_wp_error($result)) {
				wp_send_json_error(['message' => $result->get_error_message()]);
			}
			wp_send_json_success(WPMN_Media_Folders::payload());
		}
	}

	new WPMN_Helper();

endif;
