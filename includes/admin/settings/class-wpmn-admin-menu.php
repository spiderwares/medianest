<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WPMN_Admin_Menu' ) ) :

    /**
     * Main WPMN_Admin_Menu Class
     *
     * @class WPMN_Admin_Menu
     * @version 1.0.0
     */
    final class WPMN_Admin_Menu {

        /**
         * The single instance of the class.
         *
         * @var WPMN_Admin_Menu
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
            $this->settings = get_option( 'wpmn_settings', [] );
            add_action( 'admin_init', [ $this, 'register_settings' ] );
            add_action( 'admin_menu', [ $this, 'admin_menu' ] );
            add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_styles' ], 15 );
            add_filter( 'upload_mimes', [ $this, 'wpmn_enable_svg_upload' ] );
            add_filter( 'wp_handle_upload_prefilter', [ $this, 'wpmn_sanitize_svg_upload' ] );
        }

        /*
         * Main WPMN_Admin_Menu Instance.
         */
        public function register_settings() {
            register_setting(
                'wpmn_settings',
                'wpmn_settings',[ 'sanitize_callback' => [ $this, 'sanitize_settings' ], ]
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
                'wpmn_settings',
                'wpmn_settings_updated',
                esc_html__( 'Settings saved Successfully.', 'medianest' ),
                'updated'
            );
            return $settings;
        }

        /**
         * Admin menu for the plugin.
         */
        public function admin_menu() {

            add_menu_page(
				esc_html__( 'Medianest', 'medianest' ),  // Page title.
                esc_html__( 'Medianest', 'medianest' ),  // Menu title.
				'manage_options',                  // Capability required to access.
				'cosmic-wpmn',                     // Menu slug.
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
                WPMN_URL . 'assets/lib/toast.min.css', 
                array(), 
                WPMN_VERSION 
            );

            wp_enqueue_script(
				'toast-min',
				WPMN_URL . 'assets/lib/toast.min.js',
				array( 'jquery' ),
				WPMN_VERSION,
				true
			);
            

            wp_enqueue_style( 
                'wpmn-admin-style', 
                WPMN_URL . 'assets/css/wpmn-admin-style.css', 
                array(), 
                WPMN_VERSION 
            );

            wp_enqueue_style(
				'wpmn-media-library',
				WPMN_URL . 'assets/css/wpmn-media-library.css',
				array(),
				WPMN_VERSION
			);

			wp_enqueue_script(
				'wpmn-media-library',
				WPMN_URL . 'assets/js/wpmn-media-library.js',
				array( 'jquery', 'jquery-ui-draggable', 'jquery-ui-droppable', 'wp-data' ),
				WPMN_VERSION,
				true
			);

            wp_enqueue_script(
				'wpmn-media-folder',
				WPMN_URL . 'assets/js/wpmn-media-folder.js',
				array( 'jquery', 'jquery-ui-draggable', 'jquery-ui-droppable', 'wpmn-media-library' ),
				WPMN_VERSION,
				true
			);

            wp_enqueue_script(
                'wpmn-upload-media',
                WPMN_URL . 'assets/js/wpmn-upload-media.js',
                array( 'jquery', 'wpmn-media-library' ),
                WPMN_VERSION,
                true
            );

            wp_enqueue_script(
                'wpmn-admin',
                WPMN_URL . 'assets/js/wpmn-admin.js',
                array( 'jquery', 'wpmn-media-library' ),
                WPMN_VERSION,
                true
            );

            // Get saved theme design from settings
            $show_breadcrumb = isset( $this->settings['breadcrumb_navigation'] ) ? $this->settings['breadcrumb_navigation'] : 'yes';

			wp_localize_script( 'wpmn-media-library',
				'wpmn_media_library', array(
					'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
					'baseUrl'        => WPMN_URL,
					'nonce'          => wp_create_nonce( 'wpmn_media_nonce' ),
                    'restUrl'        => esc_url_raw( rest_url( 'medianest/v1/' ) ),
                    'showBreadcrumb' => $show_breadcrumb === 'yes',
                    'postType'       => $screen ? $screen->post_type : 'attachment',
					'wpmn_folder'    => array(
						'newFolderPrompt'    => esc_html__( 'Enter folder name', 'medianest' ),
						'renamePrompt'       => esc_html__( 'Rename folder', 'medianest' ),
						'selectFolderFirst'  => esc_html__( 'Please select a folder first.', 'medianest' ),
						'noSelection'        => esc_html__( 'Select at least one media item.', 'medianest' ),
						'created'            => esc_html__( 'Created Successfully', 'medianest' ),
						'renamed'            => esc_html__( 'Renamed Successfully', 'medianest' ),
						'deleted'            => esc_html__( 'Deleted Successfully', 'medianest' ),
						'assigned'           => esc_html__( 'Media updated.', 'medianest' ),
						'errorGeneric'       => esc_html__( 'Something went wrong. Please try again.', 'medianest' ),
						'emptyTree'          => esc_html__( 'No folders available. Create a folder to start organizing.', 'medianest' ),
						'emptyTitle'         => esc_html__( 'Create your first folder', 'medianest' ),
						'emptyDescription'   => esc_html__( 'There are no folders available. Please add a folder to better manage your files.', 'medianest' ),
						'emptyButton'        => esc_html__( 'Add Folder', 'medianest' ),
						'deleteConfirm'      => esc_html__( 'Are you sure you want to remove this folder? The files will be moved to Uncategorized.', 'medianest' ),
					    'confirmClearData'   => esc_html__( 'Are you sure you want to delete all Medianest data?', 'medianest' ),
						'settingsSaved'      => esc_html__( 'Settings saved Successfully', 'medianest' ),
						'itemMoved'          => esc_html__( 'Item moved Successfully', 'medianest' ),
						'allDataCleared'     => esc_html__( 'All data cleared.', 'medianest' ),
						'errorPrefix'        => esc_html__( 'Error: ', 'medianest' ),
						'moveSelf'           => esc_html__( 'Cannot move folder into itself', 'medianest' ),
						'moveSubfolder'      => esc_html__( 'Cannot move folder into its own subfolder', 'medianest' ),
						'folderMoved'        => esc_html__( 'Moved Successfully', 'medianest' ),
						'selectCsvFile'      => esc_html__( 'Please select a CSV file.', 'medianest' ),
                        'foldersImported'    => esc_html__( 'Folders imported Successfully.', 'medianest' ),
                        'generatingZip'      => esc_html__( 'Generating ZIP file', 'medianest' ),
                        'colorUpdated'       => esc_html__( 'Successfully updated.', 'medianest' ),
                        'duplicated'         => esc_html__( 'Folder duplicated.', 'medianest' ),
                        'apiKeyGenerated'    => esc_html__( 'API Key generated successfully.', 'medianest' ),
                        'sizesGenerated'     => esc_html__( 'Attachment sizes generated.', 'medianest' ),
					),
				)
			);
        }

        /**
         * Content for the admin menu page.
         */
        public function admin_menu_content() {

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Tab navigation parameter, not form processing.
            $active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'settings';
            require_once WPMN_PATH . 'includes/admin/settings/views/file-menu.php';
        }

        /**
         * Enable SVG upload.
         */
        public function wpmn_enable_svg_upload( $mimes ) {
            $svg_enabled = isset( $this->settings['secure_svg_upload'] ) ? $this->settings['secure_svg_upload'] : 'no';
            
            if ( $svg_enabled === 'yes' ) :
                $mimes['svg']  = 'image/svg+xml';
                $mimes['svgz'] = 'image/svg+xml';
            endif;
            
            return $mimes;
        }

        public function wpmn_sanitize_svg_upload( $file ) {

            $type = wp_check_filetype( $file['name'], null );
            if ( $type['type'] !== 'image/svg+xml' ) :
                return $file;
            endif;

            // Read SVG content
            $svg = file_get_contents( $file['tmp_name'] );
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
                file_put_contents( $file['tmp_name'], $clean_svg );
            else :
                $file['error'] = esc_html__( 'SVG sanitization failed.', 'medianest' );
            endif;

            return $file;
        }
    }

    // Initialize the class.
    new WPMN_Admin_Menu();

endif;
