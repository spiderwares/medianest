<?php
/**
 * Plugin Name:       Media Directory
 * Description:       Organize and manage your media files using simple folder tools for a cleaner media library.
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.4
 * Author:            cosmicinfosoftware
 * Author URI:        https://cosmicinfosoftware.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       media-directory
 *
 * @package Media Directory
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! defined( 'MDDR_FILE' ) ) :
    define( 'MDDR_FILE', __FILE__ ); // Define the plugin file path.
endif;

if ( ! defined( 'MDDR_BASENAME' ) ) :
    define( 'MDDR_BASENAME', plugin_basename( MDDR_FILE ) ); // Define the plugin basename.
endif;

if ( ! defined( 'MDDR_VERSION' ) ) :
    define( 'MDDR_VERSION', '1.0.0' ); // Define the plugin version.
endif;

if ( ! defined( 'MDDR_REST_API_URL' ) ) :
    define( 'MDDR_REST_API_URL', 'media-directory/v1' ); // Define the plugin version.
endif;

if ( ! defined( 'MDDR_PATH' ) ) :
    define( 'MDDR_PATH', plugin_dir_path( __FILE__ ) ); // Define the plugin directory path.
endif;

if ( ! defined( 'MDDR_URL' ) ) :
    define( 'MDDR_URL', plugin_dir_url( __FILE__ ) ); // Define the plugin directory URL.
endif;

if ( ! defined( 'MDDR_PRO_VERSION_URL' ) ) :
    define( 'MDDR_PRO_VERSION_URL', '#' ); // Pro Version URL
endif;

if ( ! class_exists( 'MDDR', false ) ) :
    include_once MDDR_PATH . 'includes/class-mddr.php';
endif;

register_activation_hook( __FILE__, array( 'MDDR_install', 'install' ) );

MDDR::instance();