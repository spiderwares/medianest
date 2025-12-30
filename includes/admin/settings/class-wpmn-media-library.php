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

        public $settings;
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
            $this->settings = get_option( 'wpmn_settings', [] );
			add_action( 'admin_footer', array( $this, 'wpmn_maybe_render_sidebar' ) );
            add_action( 'attachment_fields_to_edit', array( $this, 'add_folder_field' ), 10, 2 );
            add_filter( 'ajax_query_attachments_args', array( $this, 'wpmn_filter_attachments' ) );
            add_action( 'pre_get_posts', array( $this, 'wpmn_filter_list_view' ) );
            add_action( 'pre_get_posts', array( $this, 'wpmn_filesize_orderby' ) );
            add_filter( 'manage_media_columns', array( $this, 'add_folder_column' ) );
            add_filter( 'manage_media_columns', array( $this, 'add_file_size_column' ) );
            add_action( 'manage_media_custom_column', array( $this, 'display_file_size_column' ), 10, 2 );
            add_action( 'manage_media_custom_column', array( $this, 'display_folder_column' ), 10, 2 );
            add_filter( 'manage_upload_sortable_columns', array( $this, 'register_file_size_sortable' ) );
            
            // Add columns for other supported post types
            $post_types = isset( $this->settings['post_types'] ) ? (array) $this->settings['post_types'] : [];
            
            foreach ( $post_types as $pt ) :
            add_filter( "manage_edit-{$pt}_columns", array( $this, 'add_folder_column' ) );
                add_filter( "manage_{$pt}_posts_columns", array( $this, 'add_folder_column' ) );
                add_action( "manage_{$pt}_posts_custom_column", array( $this, 'display_folder_column' ), 10, 2 );
            endforeach;

            do_action( 'wpmn_media_library_init', $this );
		}

        public function wpmn_maybe_render_sidebar() {
            $screen = get_current_screen();
            if ( ! $screen ) return;

            $enabled_post_types = isset( $this->settings['post_types'] ) ? (array) $this->settings['post_types'] : [];

            $is_media = ( $screen->id === 'upload' );
            $is_supported_post_type = in_array( $screen->post_type, $enabled_post_types );
            $is_list_view = ( $screen->base === 'edit' );
            $is_post_edit = ( $screen->base === 'post' );

            if ( $is_media || $is_post_edit || ( $is_supported_post_type && $is_list_view ) ) :
                $this->wpmn_render_sidebar();
            endif;
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

                $args = array(
                    'taxonomy'   => 'wpmn_media_folder',
                    'fields'     => 'ids',
                    'hide_empty' => false,
                );

                // Check for User Specific Folder Mode logic to match sidebar behavior
                $user_mode = isset($this->settings['user_separate_folders']) && $this->settings['user_separate_folders'] === 'yes';

                if ( $user_mode && is_user_logged_in() ) :
                    $args['meta_query'] = array(
                        array(
                            'key'     => 'wpmn_folder_author',
                            'value'   => get_current_user_id(),
                            'compare' => '=',
                        )
                    );
                endif;

                $terms = get_terms( $args );

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

        public function wpmn_filter_list_view( $query ) {
            if ( ! is_admin() || ! $query->is_main_query() ) return;

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $folder = ! empty( $_GET['wpmn_folder'] ) ? sanitize_text_field( wp_unslash( $_GET['wpmn_folder'] ) ) : '';
            if ( ! $folder || $folder === 'all' ) return;

            $screen = get_current_screen();
            $post_type = '';

            if ( $screen ) :
                $post_type = $screen->post_type;
            elseif ( isset( $_GET['post_type'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $post_type = sanitize_text_field( wp_unslash( $_GET['post_type'] ) );
            else :
                $post_type = 'post';
            endif;

            // Always allow attachment, otherwise check settings
            if ( $post_type !== 'attachment' ) :
                $enabled_post_types = isset( $this->settings['post_types'] ) ? (array) $this->settings['post_types'] : [];

                if ( ! in_array( $post_type, $enabled_post_types ) ) return;
            endif;

            if ( $folder === 'uncategorized' ) :
                $args = array(
                    'taxonomy'   => 'wpmn_media_folder',
                    'fields'     => 'ids',
                    'hide_empty' => false,
                    'meta_query' => array(
                        array(
                            'key'     => 'wpmn_post_type',
                            'value'   => $post_type,
                            'compare' => '=',
                        ),
                    ),
                );

                $user_mode = isset($this->settings['user_separate_folders']) && $this->settings['user_separate_folders'] === 'yes';

                if ( $user_mode && is_user_logged_in() ) :
                    $args['meta_query']['relation'] = 'AND';
                    $args['meta_query'][] = array(
                        'key'     => 'wpmn_folder_author',
                        'value'   => get_current_user_id(),
                        'compare' => '=',
                    );
                endif;

                $terms = get_terms( $args );

                if ( ! empty( $terms ) ) :
                    $query->set( 'tax_query', array(
                        array(
                            'taxonomy' => 'wpmn_media_folder',
                            'field'    => 'term_id',
                            'terms'    => $terms,
                            'operator' => 'NOT IN',
                        )
                    ) );
                endif;
                return;
            endif;

            if ( 0 === strpos( $folder, 'term-' ) ) :
                $term_id = absint( str_replace( 'term-', '', $folder ) );
                if ( $term_id ) :
                    $query->set( 'tax_query', array(
                        array(
                            'taxonomy'         => 'wpmn_media_folder',
                            'field'            => 'term_id',
                            'terms'            => $term_id,
                            'include_children' => false,
                        )
                    ) );
                endif;
            endif;
        }

        public function wpmn_filesize_orderby( $query ) {
            if ( ! is_admin() || ! $query->is_main_query() ) return;

            if ( 'wpmn_filesize' === $query->get( 'orderby' ) ) :
                $query->set( 'meta_key', 'wpmn_filesize' );
                $query->set( 'orderby', 'meta_value_num' );
            endif;
        }

        public function add_folder_field( $form_fields, $post ) {
            $terms       = wp_get_object_terms( $post->ID, 'wpmn_media_folder' );
            $labels      = WPMN_Helper::wpmn_get_folder_labels();
            $post_type   = get_post_type( $post->ID );
            
            // Get only terms for this post type
            $include_terms = get_terms( array(
                'taxonomy'   => 'wpmn_media_folder',
                'hide_empty' => false,
                'fields'     => 'ids',
                'meta_query' => array(
                    'relation' => 'OR',
                    array(
                        'key'     => 'wpmn_post_type',
                        'value'   => $post_type,
                        'compare' => '=',
                    ),
                ),
            ) );

            // Include legacy folders for attachments
            if ( $post_type === 'attachment' ) :
                $legacy_terms = get_terms( array(
                    'taxonomy'   => 'wpmn_media_folder',
                    'hide_empty' => false,
                    'fields'     => 'ids',
                    'meta_query' => array(
                        array(
                            'key'     => 'wpmn_post_type',
                            'compare' => 'NOT EXISTS',
                        ),
                    ),
                ) );
                $include_terms = array_merge( $include_terms, $legacy_terms );
            endif;

            $select_html = wp_dropdown_categories(array(
                'taxonomy'          => 'wpmn_media_folder',
                'hide_empty'        => false,
                'name'              => 'wpmn_media_folder_select',
                'id'                => 'wpmn_media_folder_select_' . $post->ID,
                'class'             => 'wpmn_folder_dropdown',
                'show_option_all'   => $labels['all'],
                'show_option_none'  => $labels['uncategorized'],
                'option_none_value' => '0',
                'selected'          => $terms[0]->term_id ?? 0,
                'include'           => ! empty( $include_terms ) ? $include_terms : array( -1 ), // -1 to show nothing if empty
                'echo'              => 0,
                'hierarchical'      => true,
            ) );
            $select_html = str_replace('&nbsp;&nbsp;&nbsp;', '- ', $select_html);

            $form_fields['wpmn_media_folder'] = array(
                'label' => esc_html__( 'MediaNest Folder', 'medianest' ),
                'helps' => esc_html__( 'Click the folder name to move this file to a different folder', 'medianest' ),
                'input' => 'html',
                'html'  => $select_html . '<span class="spinner wpmn_folder_loader"></span>',
            );

            return $form_fields;
        }

        public function add_folder_column( $columns ) {
            $columns['wpmn_folder_col'] = esc_html__( 'MediaNest Folder', 'medianest' );
            return $columns;
        }

        public function display_folder_column( $column_name, $id ) {

            if ( 'wpmn_folder_col' !== $column_name ) return;

            $terms = get_the_terms( $id, 'wpmn_media_folder' );
            $post_type = get_post_type( $id );
            $is_attachment = ( 'attachment' === $post_type );
            $base_url = $is_attachment ? 'upload.php' : 'edit.php';
            $query_args = array();

            if ( $is_attachment ) :
                $query_args['mode'] = 'list';
            else :
                $query_args['post_type'] = $post_type;
            endif;

            if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) :
                $folder_links = array();
                foreach ( $terms as $term ) :
                    $query_args['wpmn_folder'] = 'term-' . $term->term_id;
                    $url = add_query_arg( $query_args, admin_url( $base_url ) );
                    
                    $folder_links[] = sprintf(
                        '<a href="%s">%s</a>',
                        esc_url( $url ),
                        esc_html( $term->name )
                    );
                endforeach;
                echo wp_kses_post( implode( ', ', $folder_links ) );
            else :
                $query_args['wpmn_folder'] = 'uncategorized';
                $url = add_query_arg( $query_args, admin_url( $base_url ) );
                
                echo sprintf(
                    '<a href="%s">%s</a>',
                    esc_url( $url ),
                    esc_html__( 'Uncategorized', 'medianest' )
                );
            endif;
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
