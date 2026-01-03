<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'MDDR_Media_Folders' ) ) :

	/**
     * Main MDDR_Media_Folders Class
     *
     * @class MDDR_Media_Folders
     * @version 1.0.0
     */
	class MDDR_Media_Folders {

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
			add_action( 'wp_ajax_mddr_ajax', [ $this, 'handle_request' ] );
			add_action( 'wp_ajax_nopriv_mddr_ajax', [ $this, 'handle_request' ] );
		}

		/**
         * Register media folder taxonomy.
         */
		public function register_taxonomy() {

            $settings     = get_option( 'mddr_settings', [] );
            $post_types   = isset( $settings['post_types'] ) ? (array) $settings['post_types'] : [];
            $post_types[] = 'attachment';
            $post_types   = array_unique( $post_types );

			$labels = array(
				'name'          => esc_html__('Media Folders', 'media-directory'),
				'singular_name' => esc_html__('Media Folder', 'media-directory'),
			);

			register_taxonomy(
				'mddr_media_folder',
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

			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'mddr_media_nonce' ) ) :
                wp_die( esc_html__( 'Security check failed.', 'media-directory' ) );
            endif;

			$type = isset($_POST['request_type']) ? sanitize_text_field( wp_unslash( $_POST['request_type'] ) ) : '';

			switch ($type) :
				case 'get_folders':
					$count_mode = isset($_POST['folder_count_mode']) ? sanitize_text_field( wp_unslash( $_POST['folder_count_mode'] ) ) : null;
                    $post_type  = isset($_POST['post_type']) ? sanitize_text_field( wp_unslash( $_POST['post_type'] ) ) : 'attachment';
					wp_send_json_success(apply_filters('mddr_get_folders_payload', self::payload($count_mode, $post_type), $count_mode, $post_type));
					break;

				case 'create_folder':
					MDDR_Helper::create_folder_request();
					break;

				case 'rename_folder':
					MDDR_Helper::rename_folder_request();
					break;

				case 'delete_folder':
					MDDR_Helper::delete_folder_request();
					break;

				case 'delete_folders_bulk':
					MDDR_Helper::delete_folders_bulk_request();
					break;

				case 'assign_media':
					MDDR_Helper::assign_media_request();
					break;

				case 'mddr_clear_all_data':
					MDDR_Helper::clear_all_data_request();
					break;

				case 'mddr_export_folders':
					MDDR_Export::export_folders_request();
					break;

				case 'mddr_import_folders':
					MDDR_Import::import_folders_request();
					break;

				case 'move_folder':
					MDDR_Helper::move_folder_request();
					break;

                case 'save_settings':
                    MDDR_Helper::save_settings_request();
                    break;

                case 'mddr_generate_attachment_size':
                    MDDR_Helper::generate_attachment_size_request();
                    break;
                
                case 'mddr_generate_api_key':
                    MDDR_REST_API::generate_api_key_request();
                    break;

				case 'reorder_folder':
					MDDR_Reorder::reorder_folder_request();
					break;

				default:
					do_action( 'mddr_ajax_' . $type, $_POST );
					break;
			endswitch;
		}

		public static function payload($count_mode = null, $post_type = 'attachment') {
			$user_id       = get_current_user_id();
			$user_settings = array();

			if ( $user_id ) :
				$user_settings = array(
					'default_folder' => get_user_meta( $user_id, 'mddr_default_folder', true ),
					'default_sort'   => get_user_meta( $user_id, 'mddr_default_sort', true ),
					'theme_design'   => get_user_meta( $user_id, 'mddr_theme_design', true ),
				);
			endif;

			return array(
				'folders'  => self::folder_tree($count_mode, $post_type),
				'counts'   => self::special_counts($post_type),
				'settings' => $user_settings,
			);
		}

		public static function folder_tree($count_mode = null, $post_type = 'attachment') {

			$args = array(
				'taxonomy'   => 'mddr_media_folder',
				'hide_empty' => false,
				'orderby'    => 'term_id',
				'order'      => 'ASC',
                'meta_query' => array(
                    'relation' => 'OR',
                    array(
                        'key'     => 'mddr_post_type',
                        'value'   => $post_type,
                        'compare' => '=',
                    ),
                ),
			);

            if ( $post_type === 'attachment' ) :
                $args['meta_query'][] = array(
                    'key'     => 'mddr_post_type',
                    'compare' => 'NOT EXISTS',
                );
            endif;
            
            // Check if user_separate_folders setting is enabled
            $settings  = get_option( 'mddr_settings', [] );
            $user_mode = isset($settings['user_separate_folders']) && $settings['user_separate_folders'] === 'yes';
            
            if ( $user_mode && is_user_logged_in() ) :
                $current_user_id = get_current_user_id();
                $post_type_query = $args['meta_query'];
                
                $args['meta_query'] = array(
                    'relation' => 'AND',
                    $post_type_query,
                    array(
                        'relation' => 'OR',
                        array(
                            'key'     => 'mddr_folder_author',
                            'value'   => $current_user_id,
                            'compare' => '=',
                        ),
                        array(
                            'key'     => 'mddr_folder_author',
                            'compare' => 'NOT EXISTS',
                        )
                    )
                );
            endif;

            $terms = get_terms( $args );

			if (is_wp_error($terms)) return [];

			// Sort terms by mddr_order meta
			usort($terms, function($a, $b) {
				$prioritize = apply_filters('mddr_prioritize_terms', [], $a, $b);
				if (!empty($prioritize)) :
					if (isset($prioritize['result'])) return $prioritize['result'];
				endif;

				$ord_a = (int) get_term_meta($a->term_id, 'mddr_order', true);
				$ord_b = (int) get_term_meta($b->term_id, 'mddr_order', true);
				
				if ($ord_a === $ord_b) :
					return $a->term_id <=> $b->term_id;
				endif;
				return $ord_a <=> $ord_b;
			});

			$group = [];

			foreach ($terms as $term) :
				$term->count_with_children = self::folder_count($term->term_id, $post_type);
				$group[$term->parent][] = $term;
			endforeach;

            if ( empty( $count_mode ) ) :
                // Settings loaded above
                $count_mode = isset( $settings['folder_count_mode'] ) ? $settings['folder_count_mode'] : 'folder_only';
            endif;

			return self::build_tree(0, $group, $count_mode);
		}

		public static function build_tree($parent, $group, $count_mode = 'folder_only') {

			if (empty($group[$parent])) return [];
			$list = [];

			foreach ($group[$parent] as $term) :
				$children = self::build_tree($term->term_id, $group, $count_mode);
				$total 	  = $term->count_with_children;
				foreach ($children as $c) :
					$total += $c['total'];
				endforeach;

                $count 	= ($count_mode === 'all_files') ? $total : $term->count_with_children;

				$list[] = array(	
					'id'       	=> $term->term_id,
					'name'     	=> $term->name,
					'count'    	=> $count,
					'total'    	=> $total,
					'children' 	=> $children,
					'color'     => get_term_meta( $term->term_id, 'mddr_color', true ) ?: '',
					'is_pinned' => get_term_meta( $term->term_id, 'mddr_is_pinned', true ) === '1',
				);
				$list[count($list) - 1] = apply_filters('mddr_folder_node_data', $list[count($list) - 1], $term);
			endforeach;
			return $list;
		}

		public static function folder_count($id, $post_type = 'attachment') {
			$args = array(
				'post_type'      => $post_type,
				'post_status'    => ( $post_type === 'attachment' ) ? 'inherit' : 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => false,
				'tax_query'      => array(
					array(
						'taxonomy' 		   => 'mddr_media_folder',
						'field'    		   => 'term_id',
						'terms'    		   => $id,
						'include_children' => false,
					),
				),
			);
			$query = new WP_Query( $args );
			return $query->found_posts;
		}

		public static function special_counts($post_type = 'attachment') {
			$counts = wp_count_posts( $post_type );
			$total  = ( $post_type === 'attachment' ) ? (int) $counts->inherit : (int) $counts->publish;

			$settings  = get_option( 'mddr_settings', [] );
			$user_mode = isset($settings['user_separate_folders']) && $settings['user_separate_folders'] === 'yes';

			if ( $user_mode && is_user_logged_in() ) :
				$current_user_id = get_current_user_id();
				
				// Get all folder IDs belonging to this user
				$user_folders = get_terms( array(
					'taxonomy'   => 'mddr_media_folder',
					'fields'     => 'ids',
					'hide_empty' => false,
					'meta_query' => array(
						array(
							'key'   => 'mddr_folder_author',
							'value' => $current_user_id,
						),
					),
				) );

				$args = array(
					'post_type'      => $post_type,
					'post_status'    => ( $post_type === 'attachment' ) ? 'inherit' : 'publish',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'no_found_rows'  => false,
				);

				if ( ! empty( $user_folders ) && ! is_wp_error( $user_folders ) ) :
					$args['tax_query'] = array(
						array(
							'taxonomy' => 'mddr_media_folder',
							'field'    => 'term_id',
							'terms'    => $user_folders,
							'operator' => 'NOT IN',
						),
					);
				endif;

				$query = new WP_Query( $args );
				$uncat = $query->found_posts;

			else :
				// Default Logic: Count files not in ANY folder
				$args = array(
					'post_type'      => $post_type,
					'post_status'    => ( $post_type === 'attachment' ) ? 'inherit' : 'publish',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'no_found_rows'  => false,
					'tax_query'      => array(
						array(
							'taxonomy' => 'mddr_media_folder',
							'operator' => 'NOT EXISTS',
						),
					),
				);
				$query = new WP_Query( $args );
				$uncat = $query->found_posts;
			endif;

			return array(
				'all'           => (int) $total,
				'uncategorized' => (int) $uncat,
			);
		}
	}

	new MDDR_Media_Folders();

endif;
