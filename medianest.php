<?php
/**
 * Plugin Name:       Medianest
 * Description:       Sort and organize media files with simple, powerful folder tools.
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.4
 * Author:            cosmicinfosoftware
 * Author URI:        https://cosmicinfosoftware.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       medianest
 *
 * @package Medianest
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! defined( 'WPMN_FILE' ) ) :
    define( 'WPMN_FILE', __FILE__ ); // Define the plugin file path.
endif;

if ( ! defined( 'WPMN_BASENAME' ) ) :
    define( 'WPMN_BASENAME', plugin_basename( WPMN_FILE ) ); // Define the plugin basename.
endif;

if ( ! defined( 'WPMN_VERSION' ) ) :
    define( 'WPMN_VERSION', '1.0.0' ); // Define the plugin version.
endif;

if ( ! defined( 'WPMN_REST_API_URL' ) ) :
    define( 'WPMN_REST_API_URL', 'medianest/v1' ); // Define the plugin version.
endif;

if ( ! defined( 'WPMN_PATH' ) ) :
    define( 'WPMN_PATH', plugin_dir_path( __FILE__ ) ); // Define the plugin directory path.
endif;

if ( ! defined( 'WPMN_URL' ) ) :
    define( 'WPMN_URL', plugin_dir_url( __FILE__ ) ); // Define the plugin directory URL.
endif;

if ( ! defined( 'WPMN_PRO_VERSION_URL' ) ) :
    define( 'WPMN_PRO_VERSION_URL', '#' ); // Pro Version URL
endif;

if ( ! class_exists( 'WPMN', false ) ) :
    include_once WPMN_PATH . 'includes/class-wpmn.php';
endif;

register_activation_hook( __FILE__, array( 'WPMN_install', 'install' ) );

WPMN::instance();