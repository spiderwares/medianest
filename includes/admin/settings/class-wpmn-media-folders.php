<?php
/**
 * Media folder taxonomy
 */

// Exit if accessed directly.
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
		 * 
         */
		public function __construct() {
			$this->events_handler();
		}

		/**
         * Initialize hooks and filters.
		 * 
         */
		public function events_handler() {
			add_action( 'init', [ $this, 'register_taxonomy' ] );
			add_action( 'wp_ajax_wpmn_ajax', [ $this, 'handle_request' ] );
			add_action( 'wp_ajax_nopriv_wpmn_ajax', [ $this, 'handle_request' ] );
		}

		/**
         * Register media folder taxonomy.
		 * 
         */
		public function register_taxonomy() {

            $settings   = get_option( 'wpmn_settings', [] );
            $post_types = isset( $settings['post_types'] ) ? (array) $settings['post_types'] : [];
            $post_types[] = 'attachment';
            $post_types = array_unique( $post_types );

			$labels = array(
				'name'          => esc_html__('Media Folders', 'medianest'),
				'singular_name' => esc_html__('Media Folder', 'medianest'),
			);

			register_taxonomy(
				'wpmn_media_folder',
				$post_types,
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
					$count_mode = isset($_POST['folder_count_mode']) ? sanitize_text_field( wp_unslash( $_POST['folder_count_mode'] ) ) : null;
                    $post_type = isset($_POST['post_type']) ? sanitize_text_field( wp_unslash( $_POST['post_type'] ) ) : 'attachment';
					wp_send_json_success(self::payload($count_mode, $post_type));
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
					WPMN_Export::export_folders_request();
					break;

				case 'wpmn_import_folders':
					WPMN_Import::import_folders_request();
					break;

				case 'move_folder':
					WPMN_Helper::move_folder_request();
					break;

                case 'save_settings':
                    WPMN_Helper::save_settings_request();
                    break;

                case 'wpmn_generate_attachment_size':
                    WPMN_Helper::generate_attachment_size_request();
                    break;
                
                case 'wpmn_generate_api_key':
                    WPMN_Helper::generate_api_key_request();
                    break;

				default:
					do_action( 'wpmn_ajax_' . $type, $_POST );
					break;
			endswitch;
		}

		public static function payload($count_mode = null, $post_type = 'attachment') {
			return array(
				'folders' => self::folder_tree($count_mode, $post_type),
				'counts'  => self::special_counts($post_type),
			);
		}

		public static function folder_tree($count_mode = null, $post_type = 'attachment') {

			$args = array(
				'taxonomy'   => 'wpmn_media_folder',
				'hide_empty' => false,
				'orderby'    => 'term_id',
				'order'      => 'ASC',
                'meta_query' => array(
                    'relation' => 'OR',
                    array(
                        'key'     => 'wpmn_post_type',
                        'value'   => $post_type,
                        'compare' => '=',
                    ),
                ),
			);

            // For media (attachment), also show folders that have no post type set (legacy folders)
            if ( $post_type === 'attachment' ) :
                $args['meta_query'][] = array(
                    'key'     => 'wpmn_post_type',
                    'compare' => 'NOT EXISTS',
                );
            endif;

            $terms = get_terms( $args );

			if (is_wp_error($terms)) return [];
			$group = [];

			foreach ($terms as $term) :
				$term->count_with_children = self::folder_count($term->term_id, $post_type);
				$group[$term->parent][] = $term;
			endforeach;

            if ( empty( $count_mode ) ) :
                $settings = get_option( 'wpmn_settings' );
                $count_mode = isset( $settings['folder_count_mode'] ) ? $settings['folder_count_mode'] : 'folder_only';
            endif;

			return self::build_tree(0, $group, $count_mode);
		}

		public static function build_tree($parent, $group, $count_mode = 'folder_only') {

			if (empty($group[$parent])) return [];
			$list = [];

			foreach ($group[$parent] as $term) :
				$children = self::build_tree($term->term_id, $group, $count_mode);
				$total = $term->count_with_children;
				foreach ($children as $c) :
					$total += $c['total'];
				endforeach;

                $count = ($count_mode === 'all_files') ? $total : $term->count_with_children;

				$list[] = array(	
					'id'       => $term->term_id,
					'name'     => $term->name,
					'count'    => $count,
					'total'    => $total,
					'children' => $children,
					'color'    => get_term_meta( $term->term_id, 'wpmn_color', true ) ?: '',
				);
			endforeach;
			return $list;
		}

		public static function folder_count($id, $post_type = 'attachment') {
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
				'wpmn_media_folder', $id, $post_type, 'trash'
			) );
		}

		public static function special_counts($post_type = 'attachment') {
			global $wpdb;

            // Exclude these statuses to match WordPress main query counts
            $exclude_statuses = array( 'trash', 'auto-draft', 'revision' );
            $exclude_placeholders = implode( ',', array_fill( 0, count( $exclude_statuses ), '%s' ) );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
			$total = (int) $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(ID) FROM {$wpdb->posts}
				WHERE post_type=%s AND post_status NOT IN ($exclude_placeholders)",
                array_merge( array( $post_type ), $exclude_statuses )
			));

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
            $uncat = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(ID) FROM {$wpdb->posts} p
                WHERE p.post_type=%s
                AND p.post_status NOT IN ($exclude_placeholders)
                AND NOT EXISTS (
                    SELECT 1 FROM {$wpdb->term_relationships} tr
                    INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                    WHERE tr.object_id = p.ID
                    AND tt.taxonomy = %s
                )",
                array_merge( array( $post_type ), $exclude_statuses, array( 'wpmn_media_folder' ) )
            ) );

			return array(
				'all'           => $total,
				'uncategorized' => $uncat,
			);
		}
	}

	new WPMN_Media_Folders();

endif;
