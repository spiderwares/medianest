<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'MDDR_Init' ) ) :

    /**
     * Main MDDR_Init Class
     *
     * @class MDDR_Init
     * @version 1.0.0
     */
    class MDDR_Init {

        /**
         * The single instance of the class
         */
        private static $instance = null;

        /**
         * Get the single instance
         *
         * @return MDDR_Init
         */
        public static function getInstance() {
            if (null == self::$instance) :
                self::$instance = new self();
            endif;
            return self::$instance;
        }

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
            if ($this->is_elementor_active()) :
                add_action('elementor/elements/categories_registered', array($this, 'register_elementor_category'));
                
                add_action('elementor/widgets/register', array($this, 'register_widgets'));
                add_action('elementor/widgets/widgets_registered', array($this, 'register_widgets_legacy'));
                
                // Load scripts and styles
                add_action('elementor/editor/before_enqueue_scripts', array($this, 'editor_scripts'));
                add_action('elementor/frontend/after_enqueue_styles', array($this, 'frontend_styles'));
            endif;
        }

        /**
         * Check if Elementor is active
         */
        private function is_elementor_active() {
            return did_action('elementor/loaded');
        }
        
        /**
         * Register Media Directory category in Elementor
         */
        public function register_elementor_category($elements_manager) {

            $elements_manager->add_category(
                'media-directory',
                array(
                    'title' => esc_html__('Media Directory', 'media-directory'),
                    'icon' => 'fa fa-folder-open',
                )
            );
        }

        /**
         * Register Elementor widgets 
         */
        public function register_widgets($widgets_manager) {
            require_once(MDDR_PATH . 'includes/admin/settings/PageBuilders/Elementor/widgets/class-mddr-gallery-widget.php');
            
            if (class_exists('\Elementor\Widget_Base')) :
                $widgets_manager->register(new MDDR_Gallery_Widget());
            endif;
        }
        
        /**
         * Register Elementor widgets 
         */
        public function register_widgets_legacy() {
            if (class_exists('\Elementor\Widget_Base') && class_exists('\Elementor\Plugin')) :
                require_once(MDDR_PATH . 'includes/admin/settings/PageBuilders/Elementor/widgets/class-mddr-gallery-widget.php');
                
                \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new MDDR_Gallery_Widget());
            endif;
        }

        /**
         * Load editor scripts
         */
        public function editor_scripts() {
            wp_enqueue_style(
                'mddr-editor',
                plugin_dir_url( __FILE__ ) . 'assets/css/mddr-editor.css',
                array(),
                MDDR_VERSION
            );
        }

        /**
         * Load frontend styles
         */
        public function frontend_styles() {
            wp_enqueue_style(
                'mddr-frontend',
                plugin_dir_url( __FILE__ ) . 'assets/css/mddr-frontend.css',
                array(),
                MDDR_VERSION
            );
        }
    }

    MDDR_Init::getInstance();

endif;
