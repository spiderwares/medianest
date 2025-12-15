<?php
/**
 * Media folder taxonomy
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WPMN_Media_Folders' ) ) :

	/**
     * Main WPMN_Media_Folders Class
     *
     * @class WPMN_Media_Folders
     * @version 1.0.0
     */
	class WPMN_Media_Folders {

		/**
         * Constructor for the class.
         */
		public function __construct() {
			$this->events_handler();
		}

		/**
         * Initialize hooks and filters.
         */
		public function events_handler() {
			add_action( 'init', [ $this, 'register_taxonomy' ] );
			add_action( 'wp_ajax_wpmn_ajax', [ $this, 'handle_request' ] );
		}

		public function register_taxonomy() {

			$labels = array(
				'name'          => esc_html__('Media Folders', 'medianest'),
				'singular_name' => esc_html__('Media Folder', 'medianest'),
			);

			register_taxonomy(
				'wpmn_media_folder',
				'attachment',
				array(
					'hierarchical' => true,
					'labels'       => $labels,
					'show_ui'      => false,
					'show_in_rest' => false,
					'rewrite'      => false,
					'public'       => false,
				)
			);
		}

		public function handle_request() {

			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wpmn_media_nonce' ) ) :
                wp_die( esc_html__( 'Security check failed.', 'medianest' ) );
            endif;

			$type = isset($_POST['request_type']) ? sanitize_text_field( wp_unslash( $_POST['request_type'] ) ) : '';

			switch ($type) :
				case 'get_folders':
					wp_send_json_success(self::payload());
					break;

				case 'create_folder':
					WPMN_Helper::create_folder_request();
					break;

				case 'rename_folder':
					WPMN_Helper::rename_folder_request();
					break;

				case 'delete_folder':
					WPMN_Helper::delete_folder_request();
					break;

				case 'delete_folders_bulk':
					WPMN_Helper::delete_folders_bulk_request();
					break;

				case 'assign_media':
					WPMN_Helper::assign_media_request();
					break;

				case 'wpmn_clear_all_data':
					WPMN_Helper::clear_all_data_request();
					break;

				case 'wpmn_export_folders':
					WPMN_Import_Export::export_folders_request();
					break;

				case 'wpmn_import_folders':
					WPMN_Import_Export::import_folders_request();
					break;

				case 'move_folder':
					WPMN_Helper::move_folder_request();
					break;

                case 'wpmn_generate_attachment_size':
                    WPMN_Helper::generate_attachment_size_request();
                    break;
                
                case 'wpmn_generate_api_key':
                    WPMN_Helper::generate_api_key_request();
                    break;
			endswitch;
		}

		public static function payload() {
			return array(
				'folders' => self::folder_tree(),
				'counts'  => self::special_counts(),
			);
		}

		public static function folder_tree() {

			$terms = get_terms(array(
				'taxonomy'   => 'wpmn_media_folder',
				'hide_empty' => false,
				'orderby'    => 'term_id',
				'order'      => 'ASC',
			) );

			if (is_wp_error($terms)) return [];
			$group = [];

			foreach ($terms as $t) :
				$t->count_with_children = self::folder_count($t->term_id);
				$group[$t->parent][] = $t;
			endforeach;

			return self::build_tree(0, $group);
		}

		public static function build_tree($parent, $group) {

			if (empty($group[$parent])) return [];
			$list = [];

			foreach ($group[$parent] as $term) :
				$children = self::build_tree($term->term_id, $group);
				$total = $term->count_with_children;
				foreach ($children as $c) :
					$total += $c['total'];
				endforeach;

				$list[] = array(	
					'id'       => $term->term_id,
					'name'     => $term->name,
					'count'    => $term->count_with_children,
					'total'    => $total,
					'children' => $children,
				);
			endforeach;
			return $list;
		}

		public static function folder_count($id) {
			global $wpdb;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			return (int) $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(DISTINCT tr.object_id)
				FROM {$wpdb->term_relationships} tr
				INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
				INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
				WHERE tt.taxonomy = %s
				AND tt.term_id = %d
				AND p.post_type = %s
				AND p.post_status != %s",
				'wpmn_media_folder', $id, 'attachment', 'trash'
			) );
		}

		public static function special_counts() {
			global $wpdb;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$total = (int) $wpdb->get_var(
				"SELECT COUNT(ID) FROM {$wpdb->posts}
				WHERE post_type='attachment' AND post_status!='trash'"
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$uncat = (int) $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(p.ID)
				FROM {$wpdb->posts} p
				LEFT JOIN {$wpdb->term_relationships} tr ON p.ID=tr.object_id
				LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id=tt.term_taxonomy_id 
					AND tt.taxonomy=%s
				WHERE p.post_type='attachment'
				AND p.post_status!='trash'
				AND tt.term_taxonomy_id IS NULL",
				'wpmn_media_folder'
			) );

			return array(
				'all'           => $total,
				'uncategorized' => $uncat,
			);
		}
	}

	new WPMN_Media_Folders();

endif;
