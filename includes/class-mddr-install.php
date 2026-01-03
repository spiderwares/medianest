<?php

/**
 * Installation related functions and actions.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'MDDR_Install' ) ) :

    /**
     * Main MDDR_Install Class
     *
     * @class MDDR_Install
     * @version 1.0.0
     */
    class MDDR_Install {

        /**
         * Initialize hooks and filters.
         */
        public static function init() {
            add_filter( 'plugin_action_links_' . plugin_basename( MDDR_FILE ), array( __CLASS__, 'plugin_action_links' ) );
            add_filter( 'plugin_row_meta', array( __CLASS__, 'plugin_row_meta' ), 10, 2 );
        }

        /**
         * Initialize plugin.
         *
         * Creates tables, roles, and necessary pages on plugin activation.
         */
        public static function install() {
            if ( ! is_blog_installed() ) :
                return;
            endif;
        }

        /**
         * Add link to Documentation.
         *
         * @param array $links Array of action links.
         * @param string $file Plugin file.
         * @return array Modified array of action links.
         */
        public static function plugin_row_meta( $links, $file ) {
            if ( plugin_basename( MDDR_FILE ) === $file ) :
                $doc_url   = esc_url( 'https://documentation.cosmicinfosoftware.com/media-directory/documents/getting-started/introduction/' );
                $doc_label = esc_html( 'Documentation' );
        
                $new_links = array(
                    '<a href="' . $doc_url . '" target="_blank">' . $doc_label . '</a>',
                );
        
                $links = array_merge( $links, $new_links );
            endif;
        
            return $links;
        }

        /**
         * Add plugin action links.
         *
         * @param array $links Array of action links.
         * @return array Modified array of action links.
         */
        public static function plugin_action_links( $links ) {
            $action_links = array(
                'settings' => sprintf(
                    '<a href="%s" aria-label="%s">%s</a>',
                    admin_url( 'admin.php?page=cosmic-mddr' ),
                    esc_attr__( 'Settings', 'media-directory' ),
                    esc_html__( 'Settings', 'media-directory' )
                ),
            );
            return array_merge( $action_links, $links );
        }

    }

    // Initialize the installation process
    MDDR_Install::init();

endif;
