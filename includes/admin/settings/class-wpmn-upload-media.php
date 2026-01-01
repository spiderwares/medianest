<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WPMN_Upload_Media' ) ) :

	/**
     * Main WPMN_Upload_Media Class
     *
     * @class WPMN_Upload_Media
     * @version 1.0.0
     */
	class WPMN_Upload_Media {

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
			add_action( 'pre-upload-ui', [ $this, 'render_folder' ] );
			add_action( 'wp_ajax_wpmn_get_folders_for_upload', [ $this, 'get_folders' ] );
			add_action( 'wp_ajax_nopriv_wpmn_get_folders_for_upload', [ $this, 'get_folders' ] );
            add_action( 'add_attachment', [ $this, 'auto_upload' ] );
		}

		public function render_folder() {
			
            if ( get_current_screen() && 'upload' === get_current_screen()->id ) :
                return;
            endif;

			$wpmn_labels = WPMN_Helper::get_folder_labels();

			wpmn_get_template(
				'media/upload-folder.php',
				array(
					'wpmn_labels' => $wpmn_labels,
				),
			);
		}		
		
		public function get_folders() {
			
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wpmn_media_nonce' ) ) :
                wp_die( esc_html__( 'Security check failed.', 'medianest' ) );
            endif;
            
            // Check if WPMN_Media_Folders class exists to avoid fatal error
            if ( class_exists( 'WPMN_Media_Folders' ) ) :
			    $folders = WPMN_Media_Folders::folder_tree();
            else :
                $folders = [];
            endif;

			wp_send_json_success( array( 
				'folders' => $folders 
			) );
		}

        public function auto_upload( $post_id ) {
			// Verify nonce for security
			if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ), 'wpmn_media_nonce' ) ) :
				return;
			endif;

			$folder = sanitize_text_field(
				wp_unslash(
					isset( $_REQUEST['wpmn_upload_folder'] ) ? $_REQUEST['wpmn_upload_folder'] : ''
				)
			);

			if (!$folder || $folder === 'all' || $folder === 'uncategorized') return;

			$term_id = absint(str_replace('term-', '', $folder));
			if ($term_id && term_exists($term_id, 'wpmn_media_folder')) :
				wp_set_object_terms($post_id, [$term_id], 'wpmn_media_folder');
			endif;
		}

	}

	new WPMN_Upload_Media();

endif;
