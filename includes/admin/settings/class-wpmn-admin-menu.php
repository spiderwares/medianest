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
        private function events_handler() {
            // menu
            $this->settings = get_option( 'wpmn_settings', [] );
            add_action( 'admin_init', [ $this, 'register_settings' ] );
            add_action( 'admin_menu', [ $this, 'admin_menu' ] );
            add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_styles' ], 15 );
        }

        /*
        * Main WPMN_Admin_Menu Instance..
        *
        * @static
        * @return WPMN_Admin_Menu - Main instance.
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
            add_settings_error(
                'wpmn_settings',
                'wpmn_settings_updated',
                esc_html__( 'Settings saved successfully.', 'medianest' ),
                'updated'
            );
            return $input;
        }

         public function enqueue_admin_styles( $hook ) {

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

            // Localize upload selector data for media-new.php and upload.php pages
            if ( in_array( $hook, array( 'media-new.php', 'upload.php' ), true ) ) {
                wp_enqueue_script(
                    'wpmn-upload-folder',
                    WPMN_URL . 'assets/js/wpmn-upload-folder.js',
                    array( 'jquery' ),
                    WPMN_VERSION,
                    true
                );
                
                wp_localize_script(
                    'wpmn-media-library',
                    'wpmnUploadSelector',
                    array(
                        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                        'nonce'   => wp_create_nonce( 'wpmn_upload_nonce' ),
                    )
                );
            }

            // Get saved theme design from settings
            $saved_theme = isset( $this->settings['theme_design'] ) ? sanitize_key( $this->settings['theme_design'] ) : 'default';
            $show_breadcrumb = isset( $this->settings['breadcrumb_navigation'] ) ? $this->settings['breadcrumb_navigation'] : 'yes';
            
			wp_localize_script(
				'wpmn-media-library',
				'wpmnMediaLibrary',
				array(
					'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
					'baseUrl'      => WPMN_URL,
					'nonce'        => wp_create_nonce( 'wpmn_media_nonce' ),
					'theme'        => $saved_theme,
                    'showBreadcrumb' => $show_breadcrumb === 'yes',
					'wpmn_folder'         => array(
						'newFolderPrompt'    => esc_html__( 'Enter folder name', 'medianest' ),
						'renamePrompt'       => esc_html__( 'Rename folder', 'medianest' ),
						'selectFolderFirst'  => esc_html__( 'Please select a folder first.', 'medianest' ),
						'noSelection'        => esc_html__( 'Select at least one media item.', 'medianest' ),
						'created'            => esc_html__( 'Folder created.', 'medianest' ),
						'renamed'            => esc_html__( 'Folder renamed.', 'medianest' ),
						'deleted'            => esc_html__( 'Folder deleted.', 'medianest' ),
						'assigned'           => esc_html__( 'Media updated.', 'medianest' ),
						'errorGeneric'       => esc_html__( 'Something went wrong. Please try again.', 'medianest' ),
						'emptyTree'          => esc_html__( 'No folders available. Create a folder to start organizing.', 'medianest' ),
						'emptyTitle'         => esc_html__( 'Create your first folder', 'medianest' ),
						'emptyDescription'   => esc_html__( 'There are no folders available. Please add a folder to better manage your files.', 'medianest' ),
						'emptyButton'        => esc_html__( 'Add Folder', 'medianest' ),
						'deleteConfirm'      => esc_html__( 'Are you sure you want to delete this folder? The files it contains will be automatically moved to the “Uncategorized” folder.', 'medianest' ),
						'confirmClearData'   => esc_html__( 'Are you sure you want to delete all Medianest data?', 'medianest' ),
					),
				)
			);
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
                'dashicons-images-alt2',                  // Icon URL.
				29                                // Position in the menu.
			);
        } 

        /**
         * Content for the admin menu page.
         */
        public function admin_menu_content() {

            $active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'settings';
            require_once WPMN_PATH . 'includes/admin/settings/views/file-menu.php';
        }
    }

    // Initialize the class.
    new WPMN_Admin_Menu();

endif;
