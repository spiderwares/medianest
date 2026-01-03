<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'MDDR_Admin_Menu' ) ) :

    /**
     * Main MDDR_Admin_Menu Class
     *
     * @class MDDR_Admin_Menu
     * @version 1.0.0
     */
    final class MDDR_Admin_Menu {

        /**
         * The single instance of the class.
         *
         * @var MDDR_Admin_Menu
         */
        protected static $instance = null;

        /**
		 * Plugin settings.
		 *
		 * @var array
		 */
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
            // menu
            $this->settings = get_option( 'mddr_settings', [] );
            add_action( 'admin_init', [ $this, 'register_settings' ] );
            add_action( 'admin_menu', [ $this, 'admin_menu' ] );
            add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_styles' ], 15 );
            add_filter( 'upload_mimes', [ $this, 'enable_svg_upload' ] );
            add_filter( 'wp_handle_upload_prefilter', [ $this, 'sanitize_svg_upload' ] );
            add_action( 'elementor/editor/after_enqueue_scripts', [ $this, 'enqueue_admin_styles' ] );
        }

        /*
         * Main MDDR_Admin_Menu Instance.
         */
        public function register_settings() {
            register_setting(
                'mddr_settings',
                'mddr_settings',[ 'sanitize_callback' => [ $this, 'sanitize_settings' ], ]
            );
        }

        /**
         * Sanitize settings and add success message.
         */
        public function sanitize_settings( $input ) {
            
            // Validate existing option
            if ( ! is_array( $this->settings ) ) :
                $this->settings = [];
            endif;

            if ( ! is_array( $input ) ) :
                $input = [];
            endif;
            
            $settings = array_merge( $this->settings, $input );

            add_settings_error(
                'mddr_settings',
                'mddr_settings_updated',
                esc_html__( 'Settings saved Successfully.', 'media-directory' ),
                'updated'
            );
            return $settings;
        }

        /**
         * Admin menu for the plugin.
         */
        public function admin_menu() {

            add_menu_page(
				esc_html__( 'Media Directory', 'media-directory' ),  // Page title.
                esc_html__( 'Media Directory', 'media-directory' ),  // Menu title.
				'manage_options',                  // Capability required to access.
				'cosmic-mddr',                     // Menu slug.
				[ $this, 'admin_menu_content' ],   // Callback function to render content.
                'dashicons-portfolio',                  // Icon URL.
				29                                // Position in the menu.
			);
        } 

        /**
         * Enqueue admin styles.
         * 
         */
         public function enqueue_admin_styles( $hook ) {
            $screen = get_current_screen();

            wp_enqueue_style( 
                'toast-min', 
                MDDR_URL . 'assets/lib/toast.min.css', 
                array(), 
                MDDR_VERSION 
            );

            wp_enqueue_script(
				'toast-min',
				MDDR_URL . 'assets/lib/toast.min.js',
				array( 'jquery' ),
				MDDR_VERSION,
				true
			);
            

            wp_enqueue_style( 
                'mddr-admin-style', 
                MDDR_URL . 'assets/css/mddr-admin-style.css', 
                array(), 
                MDDR_VERSION 
            );

            wp_enqueue_style(
				'mddr-media-library',
				MDDR_URL . 'assets/css/mddr-media-library.css',
				array(),
				MDDR_VERSION
			);

            wp_enqueue_style(
				'mddr-tab',
				MDDR_URL . 'assets/css/mddr-tab.css',
				array(),
				MDDR_VERSION 
			);

			wp_enqueue_script(
				'mddr-media-library',
				MDDR_URL . 'assets/js/mddr-media-library.js',
				array( 'jquery', 'jquery-ui-draggable', 'jquery-ui-droppable', 'wp-data' ),
				MDDR_VERSION,
				true
			);

            wp_enqueue_script(
				'mddr-media-folder',
				MDDR_URL . 'assets/js/mddr-media-folder.js',
				array( 'jquery', 'jquery-ui-draggable', 'jquery-ui-droppable', 'mddr-media-library' ),
				MDDR_VERSION,
				true
			);

            wp_enqueue_script(
                'mddr-upload-media',
                MDDR_URL . 'assets/js/mddr-upload-media.js',
                array( 'jquery', 'mddr-media-library' ),
                MDDR_VERSION,
                true
            );

            wp_enqueue_script(
                'mddr-admin',
                MDDR_URL . 'assets/js/mddr-admin.js',
                array( 'jquery', 'mddr-media-library' ),
                MDDR_VERSION,
                true
            );

            // Get saved theme design from settings
            $show_breadcrumb = isset( $this->settings['breadcrumb_navigation'] ) ? $this->settings['breadcrumb_navigation'] : 'yes';

			wp_localize_script( 'mddr-media-library',
				'mddr_media_library', array(
					'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
					'baseUrl'        => MDDR_URL,
					'nonce'          => wp_create_nonce( 'mddr_media_nonce' ),
                    'restUrl'        => esc_url_raw( rest_url( 'media-directory/v1/' ) ),
                    'showBreadcrumb' => $show_breadcrumb === 'yes',
                    'postType'       => $screen ? $screen->post_type : 'attachment',
					'mddr_folder'    => array(
						'newFolderPrompt'    => esc_html__( 'Enter folder name', 'media-directory' ),
						'renamePrompt'       => esc_html__( 'Rename folder', 'media-directory' ),
						'selectFolderFirst'  => esc_html__( 'Please select a folder first.', 'media-directory' ),
						'noSelection'        => esc_html__( 'Select at least one media item.', 'media-directory' ),
						'created'            => esc_html__( 'Created Successfully', 'media-directory' ),
						'renamed'            => esc_html__( 'Renamed Successfully', 'media-directory' ),
						'deleted'            => esc_html__( 'Deleted Successfully', 'media-directory' ),
						'assigned'           => esc_html__( 'Media updated.', 'media-directory' ),
						'errorGeneric'       => esc_html__( 'Something went wrong. Please try again.', 'media-directory' ),
						'emptyTree'          => esc_html__( 'No folders available. Create a folder to start organizing.', 'media-directory' ),
						'emptyTitle'         => esc_html__( 'Create your first folder', 'media-directory' ),
						'emptyDescription'   => esc_html__( 'There are no folders available. Please add a folder to better manage your files.', 'media-directory' ),
						'emptyButton'        => esc_html__( 'Add Folder', 'media-directory' ),
						'deleteConfirm'      => esc_html__( 'Are you sure you want to remove this folder? The files will be moved to Uncategorized.', 'media-directory' ),
					    'confirmClearData'   => esc_html__( 'Are you sure you want to delete all Media Directory data?', 'media-directory' ),
						'settingsSaved'      => esc_html__( 'Settings saved Successfully', 'media-directory' ),
						'itemMoved'          => esc_html__( 'Item moved Successfully', 'media-directory' ),
						'allDataCleared'     => esc_html__( 'All data cleared.', 'media-directory' ),
						'errorPrefix'        => esc_html__( 'Error: ', 'media-directory' ),
						'moveSelf'           => esc_html__( 'Cannot move folder into itself', 'media-directory' ),
						'moveSubfolder'      => esc_html__( 'Cannot move folder into its own subfolder', 'media-directory' ),
						'folderMoved'        => esc_html__( 'Moved Successfully', 'media-directory' ),
						'selectCsvFile'      => esc_html__( 'Please select a CSV file.', 'media-directory' ),
                        'foldersImported'    => esc_html__( 'Folders imported Successfully.', 'media-directory' ),
                        'generatingZip'      => esc_html__( 'Generating ZIP file', 'media-directory' ),
                        'colorUpdated'       => esc_html__( 'Successfully updated.', 'media-directory' ),
                        'duplicated'         => esc_html__( 'Folder duplicated.', 'media-directory' ),
                        'apiKeyGenerated'    => esc_html__( 'API Key generated successfully.', 'media-directory' ),
                        'sizesGenerated'     => esc_html__( 'Attachment sizes generated.', 'media-directory' ),
					),
				)
			);
        }

        /**
         * Content for the admin menu page.
         */
        public function admin_menu_content() {

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Tab navigation parameter, not form processing.
            $active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';
            require_once MDDR_PATH . 'includes/admin/settings/views/file-menu.php';
        }

        /**
         * Enable SVG upload.
         */
        public function enable_svg_upload( $mimes ) {
            $svg_enabled = isset( $this->settings['secure_svg_upload'] ) ? $this->settings['secure_svg_upload'] : 'no';
            
            if ( $svg_enabled === 'yes' ) :
                $mimes['svg']  = 'image/svg+xml';
                $mimes['svgz'] = 'image/svg+xml';
            endif;
            
            return $mimes;
        }

        public function sanitize_svg_upload( $file ) {

            $type = wp_check_filetype( $file['name'], null );
            if ( $type['type'] !== 'image/svg+xml' ) :
                return $file;
            endif;

            // Use WP_Filesystem instead of direct file_get_contents/file_put_contents
            require_once ABSPATH . 'wp-admin/includes/file.php';
            if ( ! function_exists( 'WP_Filesystem' ) ) :
                require_once ABSPATH . 'wp-admin/includes/file.php';
            endif;
            
            WP_Filesystem();
            global $wp_filesystem;

            // Read SVG content
            $svg = $wp_filesystem->get_contents( $file['tmp_name'] );
            if ( ! $svg ) :
                return $file;
            endif;

            // Sanitize (if sanitizer exists)
            if ( class_exists( 'Sanitizer' ) ) :
                $sanitizer = new Sanitizer();
                $clean_svg = $sanitizer->sanitize( $svg );
            else :
                $clean_svg = $svg; 
            endif;

            if ( $clean_svg ) :
                $wp_filesystem->put_contents( $file['tmp_name'], $clean_svg );
            else :
                $file['error'] = esc_html__( 'SVG sanitization failed.', 'media-directory' );
            endif;

            return $file;
        }
    }

    // Initialize the class.
    new MDDR_Admin_Menu();

endif;
