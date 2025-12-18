<?php
/**
 * Media Upload Folder Selector
 *
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WPMN_Upload_Folder' ) ) :

	/**
     * Main WPMN_Upload_Folder Class
     *
     * @class WPMN_Upload_Folder
     * @version 1.0.0
     */
	class WPMN_Upload_Folder {

		/**
		 * Constructor for the class.
		 */
		public function __construct() {
			$this->events_handler();
		}

		/**
         * Initialize hooks and filters.
         */
		public function events_handler(){
			add_action( 'pre-upload-ui', [ $this, 'wpmn_render_folder' ] );
			add_action( 'wp_ajax_wpmn_get_folders_for_upload', [ $this, 'wpmn_get_folders' ] );
            add_action( 'add_attachment', [ $this, 'wpmn_auto_upload' ] );
		}

		public function wpmn_render_folder() {
			
            if ( get_current_screen() && 'upload' === get_current_screen()->id ) {
                return;
            }

			wpmn_get_template(
				'media/upload-folder.php',
				array(),
			);
		}		
		
		public function wpmn_get_folders() {
			
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wpmn_media_nonce' ) ) :
                wp_die( esc_html__( 'Security check failed.', 'medianest' ) );
            endif;
            
            // Check if WPMN_Media_Folders class exists to avoid fatal error
            if ( class_exists( 'WPMN_Media_Folders' ) ) {
			    $folders = WPMN_Media_Folders::folder_tree();
            } else {
                $folders = [];
            }

			wp_send_json_success( array( 
				'folders' => $folders 
			) );
		}

        public function wpmn_auto_upload( $post_id ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$folder = sanitize_text_field( wp_unslash( $_REQUEST['wpmn_upload_folder'] ?? '' ) );

			if (!$folder || $folder === 'all' || $folder === 'uncategorized') return;

			$term_id = absint(str_replace('term-', '', $folder));
			if ($term_id && term_exists($term_id, 'wpmn_media_folder')) :
				wp_set_object_terms($post_id, [$term_id], 'wpmn_media_folder');
			endif;
		}

	}

	new WPMN_Upload_Folder();

endif;
