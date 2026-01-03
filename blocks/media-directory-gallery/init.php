<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'MDDR_Gallery_Block' ) ) :

    /**
     * Main MDDR_Gallery_Block Class
     *
     * @class MDDR_Gallery_Block
     * @version 1.0.0
     */
    class MDDR_Gallery_Block {

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
            add_action( 'init', array( $this, 'block_assets' ), 10 );
            add_action( 'rest_api_init', array( $this, 'register_rest_route' ) );
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
        }

        /**
         * Enqueue block assets and register block
         */
        public function block_assets() {
        
            wp_register_style( 
                'media-directory-gallery', 
                MDDR_URL . 'assets/lib/photoswipe.css', 
                array(), 
                MDDR_VERSION 
            );

            wp_register_style( 
                'mddr-lightbox-skin', 
                MDDR_URL . 'assets/lib/mddr-lightbox-skin.css', 
                array( 'media-directory-gallery' ), 
                MDDR_VERSION 
            );

            wp_register_script( 
                'media-directory-gallery', 
                MDDR_URL . 'assets/lib/photoswipe.min.js', 
                array(), 
                MDDR_VERSION, 
                true 
            );

            wp_register_script( 
                'media-directory-gallery-ui-default', 
                MDDR_URL . 'assets/lib/photoswipe-ui-default.min.js', 
                array(), 
                MDDR_VERSION, 
                true 
            );
            
            wp_register_script( 
                'media-directory-gallery-lightbox', 
                MDDR_URL . 'assets/lib/mddr-photoswipe.js', 
                array( 'media-directory-gallery', 'media-directory-gallery-ui-default' ), 
                MDDR_VERSION, 
                true 
            );
            
            register_block_type( __DIR__ . '/build' );
        }

        /**
         * Enqueue frontend assets
         */
        public function enqueue_frontend_assets() {
            wp_enqueue_style( 'mddr-lightbox-skin' );
            wp_enqueue_script( 'media-directory-gallery-lightbox' );
        }

        /**
         * Register REST API route for Gutenberg rendering
         */
        public function register_rest_route() {
            register_rest_route(
                'media-directory/v1',
                'gutenberg-get-images',
                array(
                    'methods'             => 'POST',
                    'callback'            => array( $this, 'render_callback' ),
                    'permission_callback' => function() {
                        return current_user_can( 'upload_files' );
                    }
                )
            );
        }

        /**
         * Render callback for the REST API
         */
        public function render_callback( $request ) {
            $attributes = $request->get_params();
            
            ob_start();
            include __DIR__ . '/build/render.php';
            $html = ob_get_clean();
            
            wp_send_json( array(
                'html' => $html,
            ) );
        }

    }

    new MDDR_Gallery_Block();
    
endif;
