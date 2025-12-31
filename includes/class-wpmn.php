<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WPMN' ) ) :

    /**
     * Main WPMN Class
     *
     * @class WPMN
     * @version 1.0.0
     */
    final class WPMN {

        /**
         * The single instance of the class.
         *
         * @var WPMN
         */
        protected static $instance = null;

        /**
         * The public class instance.
         *
         * @var WPMN_Public
         */
        public $public = null;

        /**
         * Constructor for the class.
         */
        public function __construct() {
            $this->events_handler();
            $this->includes();
        }

        /**
         * Initialize hooks and filters.
         */
        public function events_handler() {
            add_action( 'plugins_loaded', array( $this, 'includes' ), 11 );
        }

        /**
         * Main WPMN Instance.
         *
         * Ensures only one instance of WPMN is loaded or can be loaded.
         *
         * @static
         * @return WPMN - Main instance.
         */
        public static function instance() {
            if ( is_null( self::$instance ) ) :
                self::$instance = new self();

                /**
                 * Fire a custom action to allow dependencies
                 * after the successful plugin setup
                 */
                do_action( 'wpmn_plugin_loaded' );
            endif;
            return self::$instance;
        }

        /**
         * Flush rewrite rules on plugin activation.
         */
        public static function plugin_activate() {
            
            // Save default options on first activation
            // $default_options = include_once WPMN_PATH . 'includes/static/wpmn-default-option.php';
            // $existingOption  = get_option( 'wpmn_settings' );

            // // If the option is not set, update it with the default value
            // if ( ! $existingOption ) :
            //     update_option( 'wpmn_settings', $default_options['wpmn_settings'] );
            // endif;
        }

        /**
         * Include required files.
         */
        public function includes() {
            if ( is_admin() ) :
                $this->includes_admin();
           else :
                $this->includes_public();
            endif;
            
            require_once WPMN_PATH . 'includes/wpmn-core-functions.php';
            require_once WPMN_PATH . 'includes/admin/settings/Helper/class-wpmn-helper.php';
            require_once WPMN_PATH . 'includes/admin/settings/class-wpmn-media-folders.php';
            require_once WPMN_PATH . 'includes/admin/settings/RestAPI/class-wpmn-rest-api.php';
            include_once WPMN_PATH . 'blocks/medianest-gallery/init.php';

            // Elementor Support
            require_once WPMN_PATH . 'includes/admin/settings/PageBuilders/Elementor/class-wpmn-init.php';
            \MediaNest\PageBuilders\Elementor\WPMN_Init::getInstance();
        }
        
        /**
         * Include Admin required files.
        */
        public function includes_admin() {
            require_once WPMN_PATH . 'includes/class-wpmn-install.php';
            require_once WPMN_PATH . 'includes/admin/settings/class-wpmn-admin-menu.php';
            require_once WPMN_PATH . 'includes/admin/tab/class-wpmn.tab.php';
            require_once WPMN_PATH . 'includes/admin/settings/class-wpmn-settings-field.php';
            require_once WPMN_PATH . 'includes/admin/settings/Import-Export/class-wpmn-import.php';
            require_once WPMN_PATH . 'includes/admin/settings/Import-Export/class-wpmn-export.php';
            require_once WPMN_PATH . 'includes/admin/settings/Reorder/class-wpmn-reorder.php';
            require_once WPMN_PATH . 'includes/admin/settings/class-wpmn-media-library.php';
            require_once WPMN_PATH . 'includes/admin/settings/class-wpmn-upload-media.php';
        }

        /**
         * Include Public required files.
         */
        public function includes_public() {
        }
    }

endif;
