<?php
/**
 * Media Library customisations for Medianest.
 *
 * @package Medianest
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WPMN_Media_Library' ) ) :

    /**
     * Main WPMN_Media_Library Class
     *
     * @class WPMN_Media_Library
     * @version 1.0.0
     */
	class WPMN_Media_Library {

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
			add_action( 'admin_footer-upload.php', array( $this, 'wpmn_render_sidebar' ) );
			add_action( 'admin_footer-post.php', array( $this, 'wpmn_render_sidebar' ) );
			add_action( 'admin_footer-post-new.php', array( $this, 'wpmn_render_sidebar' ) );
            add_action( 'attachment_fields_to_edit', array( $this, 'add_folder_field' ), 10, 2 );
            add_filter( 'ajax_query_attachments_args', array( $this, 'wpmn_filter_attachments' ) );
            add_filter( 'manage_media_columns', array( $this, 'add_file_size_column' ) );
            add_action( 'manage_media_custom_column', array( $this, 'display_file_size_column' ), 10, 2 );
            add_filter( 'manage_upload_sortable_columns', array( $this, 'register_file_size_sortable' ) );
		}

		public function wpmn_render_sidebar() {
			wpmn_get_template(
				'media/library-sidebar.php',
				array(),
			);
		}

        public function wpmn_filter_attachments( $query ) {

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if ( empty( $_REQUEST['query']['wpmn_folder'] ) ) :
                return $query;
            endif;

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $folder = sanitize_text_field( wp_unslash( $_REQUEST['query']['wpmn_folder'] ) );
            if ( $folder === 'uncategorized' ) :

                $terms = get_terms(array(
                    'taxonomy'   => 'wpmn_media_folder',
                    'fields'     => 'ids',
                    'hide_empty' => false,
                ) );

                if ( ! empty( $terms ) ) :
                    $query['tax_query'] = array(
                        array(
                            'taxonomy' => 'wpmn_media_folder',
                            'field'    => 'term_id',
                            'terms'    => $terms,
                            'operator' => 'NOT IN',
                        )
                    );
                endif;
                return $query;
            endif;

            if ( strpos( $folder, 'term-' ) === 0 ) :
                $term_id = absint( str_replace( 'term-', '', $folder ) );
                if ( $term_id ) :
                    $query['tax_query'] = array(
                        array(
                            'taxonomy'         => 'wpmn_media_folder',
                            'field'            => 'term_id',
                            'terms'            => $term_id,
                            'include_children' => false,
                        )
                    );
                endif;
            endif;

            return $query;
        }

        public function add_folder_field( $form_fields, $post ) {
            $terms = wp_get_object_terms( $post->ID, 'wpmn_media_folder' );
            $folder_name = esc_html__( 'Uncategorized', 'medianest' );
            
            if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) :
                $folder_name = $terms[0]->name;
            endif;
            
            $form_fields['wpmn_media_folder'] = array(
                'label'   => esc_html__( 'MediaNest Folder', 'medianest' ),
                'input'   => 'html',
                'html'    => esc_html( $folder_name ),
            );
            return $form_fields;
        }

        public function get_folders() {
            $terms = get_terms(array(
                'taxonomy'   => 'wpmn_media_folder',
                'hide_empty' => false,
                'fields'     => 'id=>name',
            ) );

            return is_array( $terms ) ? $terms : [];
        }

        public function add_file_size_column( $columns ) {
            $columns['wpmn_filesize'] = esc_html__( 'File Size', 'medianest' );
            return $columns;
        }

        public function display_file_size_column( $column_name, $id ) {
            if ( 'wpmn_filesize' !== $column_name ) return;

            $file_path = get_attached_file( $id );
            if ( $file_path && file_exists( $file_path ) ) :
                $bytes = filesize( $file_path );
                echo esc_html( size_format( $bytes, 2 ) );
            else :
                echo '—';
            endif;
        }

        public function register_file_size_sortable( $columns ) {
            $columns['wpmn_filesize'] = 'wpmn_filesize';
            return $columns;
        }

	}

	new WPMN_Media_Library();

endif;
