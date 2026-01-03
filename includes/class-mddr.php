<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'MDDR' ) ) :

    /**
     * Main MDDR Class
     *
     * @class MDDR
     * @version 1.0.0
     */
    final class MDDR {

        /**
         * The single instance of the class.
         *
         * @var MDDR
         */
        protected static $instance = null;

        /**
         * The public class instance.
         *
         * @var MDDR_Public
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
         * Main MDDR Instance.
         *
         * Ensures only one instance of MDDR is loaded or can be loaded.
         *
         * @static
         * @return MDDR - Main instance.
         */
        public static function instance() {
            if ( is_null( self::$instance ) ) :
                self::$instance = new self();

                /**
                 * Fire a custom action to allow dependencies
                 * after the successful plugin setup
                 */
                do_action( 'mddr_plugin_loaded' );
            endif;
            return self::$instance;
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
            
            require_once MDDR_PATH . 'includes/mddr-core-functions.php';
            require_once MDDR_PATH . 'includes/admin/settings/Helper/class-mddr-helper.php';
            require_once MDDR_PATH . 'includes/admin/settings/class-mddr-media-folders.php';
            require_once MDDR_PATH . 'includes/admin/settings/RestAPI/class-mddr-rest-api.php';
            include_once MDDR_PATH . 'blocks/media-directory-gallery/init.php';

            // Elementor Support
            require_once MDDR_PATH . 'includes/admin/settings/PageBuilders/Elementor/class-mddr-init.php';
        }
        
        /**
         * Include Admin required files.
        */
        public function includes_admin() {
            require_once MDDR_PATH . 'includes/class-mddr-install.php';
            require_once MDDR_PATH . 'includes/admin/settings/class-mddr-admin-menu.php';
            require_once MDDR_PATH . 'includes/admin/settings/class-mddr-settings-field.php';
            require_once MDDR_PATH . 'includes/admin/settings/Import-Export/class-mddr-import.php';
            require_once MDDR_PATH . 'includes/admin/settings/Import-Export/class-mddr-export.php';
            require_once MDDR_PATH . 'includes/admin/settings/Reorder/class-mddr-reorder.php';
            require_once MDDR_PATH . 'includes/admin/settings/class-mddr-media-library.php';
            require_once MDDR_PATH . 'includes/admin/settings/class-mddr-upload-media.php';
        }

        /**
         * Include Public required files.
         */
        public function includes_public() {
        }
    }

endif;
