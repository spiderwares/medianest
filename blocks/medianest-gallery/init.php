<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WPMN_Gallery_Block' ) ) :

    class WPMN_Gallery_Block {

        public function __construct() {
            $this->events_handler();
        }

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
                'medianest-gallery', 
                WPMN_URL . 'assets/lib/photoswipe.css', 
                array(), 
                WPMN_VERSION 
            );

            wp_register_style( 
                'wpmn-lightbox-skin', 
                WPMN_URL . 'assets/lib/wpmn-lightbox-skin.css', 
                array( 'medianest-gallery' ), 
                WPMN_VERSION 
            );

            wp_register_script( 
                'medianest-gallery', 
                WPMN_URL . 'assets/lib/photoswipe.min.js', 
                array(), 
                WPMN_VERSION, 
                true 
            );

            wp_register_script( 
                'medianest-gallery-ui-default', 
                WPMN_URL . 'assets/lib/photoswipe-ui-default.min.js', 
                array(), 
                WPMN_VERSION, 
                true 
            );
            
            wp_register_script( 
                'medianest-gallery-lightbox', 
                WPMN_URL . 'assets/lib/wpmn-photoswipe.js', 
                array( 'medianest-gallery', 'medianest-gallery-ui-default' ), 
                WPMN_VERSION, 
                true 
            );
            
            register_block_type( __DIR__ . '/build' );
        }

        /**
         * Enqueue frontend assets
         */
        public function enqueue_frontend_assets() {
            wp_enqueue_style( 'wpmn-lightbox-skin' );
            wp_enqueue_script( 'medianest-gallery-lightbox' );
        }

        /**
         * Register REST API route for Gutenberg rendering
         */
        public function register_rest_route() {
            register_rest_route(
                'medianest/v1',
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
            
            if ( ! class_exists( 'WPMN_Gallery_Render' ) ) :
                require_once __DIR__ . '/build/render.php';
            endif;

            $html = WPMN_Gallery_Render::render_gallery( $attributes );
            
            wp_send_json( array(
                'html' => $html,
            ) );
        }

    }

    new WPMN_Gallery_Block();
    
endif;
