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
			wpmn_get_template(
				'media/upload-folder.php',
				array(),
			);
		}		
		
		public function wpmn_get_folders() {
			check_ajax_referer( 'wpmn_media_nonce', 'nonce' );

			$folders = $this->get_folder_tree();
			wp_send_json_success( array( 
				'folders' => $folders 
			) );
		}

		public function get_folder_tree() {

			$terms = get_terms(array(
				'taxonomy'   => 'wpmn_media_folder',
				'hide_empty' => false,
				'orderby'    => 'term_id',
				'order'      => 'ASC',
			) );

			if ( is_wp_error( $terms ) ) :
				return [];
			endif;	

			$group = [];
			foreach ( $terms as $t ) :	
				$group[ $t->parent ][] = $t;
			endforeach;
			return $this->build_tree( 0, $group );
		}

		public function build_tree( $parent, $group ) {

			if ( empty( $group[ $parent ] ) ) :
				return [];
			endif;
			
			$tree = [];
			foreach ( $group[ $parent ] as $term ) :	
				$tree[] = array(
					'id'       => $term->term_id,
					'name'     => $term->name,
					'children' => $this->build_tree( $term->term_id, $group ),
				);
			endforeach;
			return $tree;
		}

        public function wpmn_auto_upload( $post_id ) {
			$folder = sanitize_text_field($_REQUEST['wpmn_upload_folder'] ?? '');

			if (!$folder || $folder === 'all' || $folder === 'uncategorized') return;

			$term_id = absint(str_replace('term-', '', $folder));
			if ($term_id && term_exists($term_id, 'wpmn_media_folder')) :
				wp_set_object_terms($post_id, [$term_id], 'wpmn_media_folder');
			endif;
		}

	}

	new WPMN_Upload_Folder();

endif;
