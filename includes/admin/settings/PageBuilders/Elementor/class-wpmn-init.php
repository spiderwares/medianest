<?php

namespace MediaNest\PageBuilders\Elementor;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WPMN_Init' ) ) :

    /**
     * Main WPMN_Init Class
     *
     * @class WPMN_Init
     * @version 1.0.0
     */
    class WPMN_Init {

        /**
         * The single instance of the class
         */
        private static $instance = null;

        /**
         * Get the single instance
         *
         * @return WPMN_Init
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
         * Register MediaNest category in Elementor
         */
        public function register_elementor_category($elements_manager) {

            $elements_manager->add_category(
                'medianest',
                array(
                    'title' => esc_html__('MediaNest', 'medianest'),
                    'icon' => 'fa fa-folder-open',
                )
            );
        }

        /**
         * Register Elementor widgets 
         */
        public function register_widgets($widgets_manager) {
            require_once(WPMN_PATH . 'includes/admin/settings/PageBuilders/Elementor/widgets/class-wpmn-gallery-widget.php');
            
            if (class_exists('\Elementor\Widget_Base')) :
                $widgets_manager->register(new WPMN_Gallery_Widget());
            endif;
        }
        
        /**
         * Register Elementor widgets 
         */
        public function register_widgets_legacy() {
            if (class_exists('\Elementor\Widget_Base') && class_exists('\Elementor\Plugin')) :
                require_once(WPMN_PATH . 'includes/admin/settings/PageBuilders/Elementor/widgets/class-wpmn-gallery-widget.php');
                
                \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new WPMN_Gallery_Widget());
            endif;
        }

        /**
         * Load editor scripts
         */
        public function editor_scripts() {
            wp_enqueue_style(
                'wpmn-editor',
                plugin_dir_url( __FILE__ ) . 'Elementor/assets/css/wpmn-editor.css',
                array(),
                WPMN_VERSION
            );
        }

        /**
         * Load frontend styles
         */
        public function frontend_styles() {
            wp_enqueue_style(
                'wpmn-frontend',
                plugin_dir_url( __FILE__ ) . 'Elementor/assets/css/wpmn-frontend.css',
                array(),
                WPMN_VERSION
            );
        }
    }

endif;
