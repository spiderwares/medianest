<?php
/**
 * Media folder taxonomy
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WPMN_Helper' ) ) :

	/**
     * Main WPMN_Helper Class
     *
     * @class WPMN_Helper
     * @version 1.0.0
     */
	class WPMN_Helper {

		public static function wpmn_get_folder_labels() {
			return array(
				'choose_folder'  => esc_html__( 'Choose folder:', 'medianest' ),
				'all'            => esc_html__( 'All Files', 'medianest' ),
				'uncategorized'  => esc_html__( 'Uncategorized', 'medianest' ),
			);
		}

		public static function create_folder( $name, $parent ) {
            $settings 	= get_option( 'wpmn_settings', [] );
            $user_mode 	= isset($settings['user_separate_folders']) && $settings['user_separate_folders'] === 'yes';
            $args 		= array( 'parent' => $parent );
            
            if ( $user_mode && is_user_logged_in() ) :
                $unique_slug  = sanitize_title( $name ) . '-' . get_current_user_id();
                $args['slug'] = $unique_slug;
            endif;

			$result = wp_insert_term( $name, 'wpmn_media_folder', $args );
            
			if ( is_wp_error( $result ) && $result->get_error_code() === 'term_exists' ) :

				$counter = 1;
				while ( $counter <= 100 ) :
					$new_name = $name . ' (' . $counter . ')';
                    
                    // Logic to check existence properly with user mode
                    if ( $user_mode && is_user_logged_in() ) :
                         $new_unique_slug = sanitize_title( $new_name ) . '-' . get_current_user_id();
                         $dup_args 		  = array( 'parent' => $parent, 'slug' => $new_unique_slug );
                         
                         $dup_result = wp_insert_term( $new_name, 'wpmn_media_folder', $dup_args );
                         
						if ( ! is_wp_error( $dup_result ) ) :
							return $dup_result;
						endif;
                    else :
                        if ( ! term_exists( $new_name, 'wpmn_media_folder', $parent ) ) :
                            return wp_insert_term( 
                                $new_name, 
                                'wpmn_media_folder',
                                array( 'parent' => $parent )
                            );
                        endif;
                    endif;

					$counter++;
				endwhile;
			endif;
			return $result;
		}

		public static function create_folder_request() {

			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wpmn_media_nonce' ) ) :
                wp_die( esc_html__( 'Security check failed.', 'medianest' ) );
            endif;
			
			$name      = isset($_POST['name']) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
			$parent    = isset($_POST['parent']) ? absint($_POST['parent']) : 0;
            $post_type = isset($_POST['post_type']) ? sanitize_text_field( wp_unslash( $_POST['post_type'] ) ) : 'attachment';

			if (!$name) :
				wp_send_json_error(array('message' => 'Folder name is required.'));
			endif;

			$result = self::create_folder($name, $parent);
            
            if ( ! is_wp_error( $result ) && isset( $result['term_id'] ) ) :
                update_term_meta( $result['term_id'], 'wpmn_post_type', $post_type );
                update_term_meta( $result['term_id'], 'wpmn_folder_author', get_current_user_id() );
            endif;

			self::send_response($result);
		}

		public static function rename_folder_request() {

			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wpmn_media_nonce' ) ) :
                wp_die( esc_html__( 'Security check failed.', 'medianest' ) );
            endif;

			$id   = isset($_POST['folder_id']) ? absint($_POST['folder_id']) : 0;
			$name = isset($_POST['name']) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';

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

			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wpmn_media_nonce' ) ) :
                wp_die( esc_html__( 'Security check failed.', 'medianest' ) );
            endif;

			$id 		= isset($_POST['folder_id']) ? absint($_POST['folder_id']) : 0;
			$post_type  = isset($_POST['post_type']) ? sanitize_text_field( wp_unslash( $_POST['post_type'] ) ) : 'attachment';

			if (!$id) :
				wp_send_json_error(['message' => 'Invalid folder.']);
			endif;

			self::delete_folder_recursively($id);
			wp_send_json_success(WPMN_Media_Folders::payload(null, $post_type));
		}

		public static function delete_folder_recursively($id) {

			$children = get_term_children($id, 'wpmn_media_folder');
			if (!is_wp_error($children) && !empty($children)) :
				foreach ($children as $child_id) :
					wp_delete_term($child_id, 'wpmn_media_folder');
				endforeach;
			endif;

			return wp_delete_term($id, 'wpmn_media_folder');
		}

		public static function delete_folders_bulk_request() {

			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wpmn_media_nonce' ) ) :
                wp_die( esc_html__( 'Security check failed.', 'medianest' ) );
            endif;

			$ids       = isset($_POST['folder_ids']) ? array_map('absint', (array) $_POST['folder_ids']) : [];
			$post_type = isset($_POST['post_type']) ? sanitize_text_field( wp_unslash( $_POST['post_type'] ) ) : 'attachment';

			if (empty($ids)) :
				wp_send_json_error(['message' => 'No folders selected.']);
			endif;

			foreach ($ids as $id) :
				self::delete_folder_recursively($id);
			endforeach;
			wp_send_json_success(WPMN_Media_Folders::payload(null, $post_type));
		}

		public static function assign_media_request() {

			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wpmn_media_nonce' ) ) :
                wp_die( esc_html__( 'Security check failed.', 'medianest' ) );
            endif;

			global $wpdb;
			
			$folder_id = isset($_POST['folder_id']) ? absint($_POST['folder_id']) : 0;
			$items     = isset($_POST['attachment_ids']) ? array_map('absint', (array) $_POST['attachment_ids']) : [];
            $post_type = isset($_POST['post_type']) ? sanitize_text_field( wp_unslash( $_POST['post_type'] ) ) : 'attachment';

			if (empty($items)) :
				wp_send_json_error(['message' => 'No media selected.']);
			endif;

			foreach ($items as $attachment_id) :
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->query( $wpdb->prepare(
					"DELETE tr FROM {$wpdb->term_relationships} tr
					INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
					WHERE tr.object_id = %d AND tt.taxonomy = %s",
					$attachment_id, 'wpmn_media_folder'
				));
				
				if ( $folder_id > 0 ) :
					// Verify term exists and get taxonomy_id
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery
					$tt_id = $wpdb->get_var( $wpdb->prepare(
						"SELECT tt.term_taxonomy_id FROM {$wpdb->term_taxonomy} tt
						WHERE tt.term_id = %d AND tt.taxonomy = %s",
						$folder_id, 'wpmn_media_folder'
					));
					
					if ( $tt_id ) :
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery
						$wpdb->insert( $wpdb->term_relationships, array(
							'object_id' => $attachment_id,
							'term_taxonomy_id' => $tt_id,
							'term_order' => 0
						), array( '%d', '%d', '%d' ));
					endif;
				endif;
				
				clean_object_term_cache( $attachment_id, $post_type );
			endforeach;
			
			if ( $folder_id > 0 ) :
				wp_update_term_count_now( array( $folder_id ), 'wpmn_media_folder' );
			endif;
			wp_send_json_success(WPMN_Media_Folders::payload(null, $post_type));
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

		public static function move_folder_request() {

			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wpmn_media_nonce' ) ) :
                wp_die( esc_html__( 'Security check failed.', 'medianest' ) );
            endif;

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
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $post_type  = isset($_POST['post_type']) ? sanitize_text_field( wp_unslash( $_POST['post_type'] ) ) : 'attachment';
			wp_send_json_success(WPMN_Media_Folders::payload(null, $post_type));
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
                /* translators: %d: number of attachments */
                'message' => sprintf( esc_html__( 'Generated sizes for %d attachments.', 'medianest' ), $count )
            ) );
        }

        public static function save_settings_request() {
            if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wpmn_media_nonce' ) ) :
                wp_die( esc_html__( 'Security check failed.', 'medianest' ) );
            endif;

            $user_id = get_current_user_id();
            if ( ! $user_id ) :
                wp_send_json_error( array( 'message' => esc_html__( 'User not logged in.', 'medianest' ) ) );
            endif;

            // Global settings (site-wide)
            $settings = get_option( 'wpmn_settings', [] );
            
            if ( isset( $_POST['folder_count_mode'] ) ) :
                $settings['folder_count_mode'] = sanitize_text_field( wp_unslash( $_POST['folder_count_mode'] ) );
			endif;

            update_option( 'wpmn_settings', $settings );

            // User-specific settings (per-user preferences)
            if ( isset( $_POST['default_folder'] ) ) :
                update_user_meta( $user_id, 'wpmn_default_folder', sanitize_text_field( wp_unslash( $_POST['default_folder'] ) ) );
            endif;

            if ( isset( $_POST['default_sort'] ) ) :
                update_user_meta( $user_id, 'wpmn_default_sort', sanitize_text_field( wp_unslash( $_POST['default_sort'] ) ) );
            endif;

            if ( isset( $_POST['theme_design'] ) ) :
                update_user_meta( $user_id, 'wpmn_theme_design', sanitize_text_field( wp_unslash( $_POST['theme_design'] ) ) );
            endif;

            wp_send_json_success( array(
                'message' => esc_html__( 'Settings saved successfully.', 'medianest' )
            ) );
        }
	}

	new WPMN_Helper();

endif;
