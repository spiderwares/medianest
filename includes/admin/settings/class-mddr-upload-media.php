<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'MDDR_Upload_Media' ) ) :

	/**
     * Main MDDR_Upload_Media Class
     *
     * @class MDDR_Upload_Media
     * @version 1.0.0
     */
	class MDDR_Upload_Media {

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
			add_action( 'wp_ajax_mddr_get_folders_for_upload', [ $this, 'get_folders' ] );
			add_action( 'wp_ajax_nopriv_mddr_get_folders_for_upload', [ $this, 'get_folders' ] );
            add_action( 'add_attachment', [ $this, 'auto_upload' ] );
		}

		public function render_folder() {
			
            if ( get_current_screen() && 'upload' === get_current_screen()->id ) :
                return;
            endif;

			$mddr_labels = MDDR_Helper::get_folder_labels();

			mddr_get_template(
				'media/upload-folder.php',
				array(
					'mddr_labels' => $mddr_labels,
				),
			);
		}		
		
		public function get_folders() {
			
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'mddr_media_nonce' ) ) :
                wp_die( esc_html__( 'Security check failed.', 'media-directory' ) );
            endif;
            
            // Check if MDDR_Media_Folders class exists to avoid fatal error
            if ( class_exists( 'MDDR_Media_Folders' ) ) :
			    $folders = MDDR_Media_Folders::folder_tree();
            else :
                $folders = [];
            endif;

			wp_send_json_success( array( 
				'folders' => $folders 
			) );
		}

        public function auto_upload( $post_id ) {
			// Verify nonce for security
			if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ), 'mddr_media_nonce' ) ) :
				return;
			endif;

			$folder = sanitize_text_field(
				wp_unslash(
					isset( $_REQUEST['mddr_upload_folder'] ) ? $_REQUEST['mddr_upload_folder'] : ''
				)
			);

			if (!$folder || $folder === 'all' || $folder === 'uncategorized') return;

			$term_id = absint(str_replace('term-', '', $folder));
			if ($term_id && term_exists($term_id, 'mddr_media_folder')) :
				wp_set_object_terms($post_id, [$term_id], 'mddr_media_folder');
			endif;
		}

	}

	new MDDR_Upload_Media();

endif;
