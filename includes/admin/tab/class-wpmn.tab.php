<?php
/**
 * SRWC Tab Class
 *
 * Handles the admin tab setup and related functionalities.
 *
 * @package Medianest
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WPMN_Tab' ) ) {

	/**
	 * Class WPMN_Tab
	 *
	 * Initializes the admin tab for WPMN.
	 */
	class WPMN_Tab {

		/**
		 * Constructor for WPMN_Tab class.
		 * Initializes the event handler.
		 */
		public function __construct() {
			$this->events_handler();
		}

		/**
		 * Initialize hooks for admin functionality.
		 */
		public function events_handler() {
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		}

		/**
		 * Enqueue admin-specific styles for the tab.
		 */
		public function enqueue_scripts() {
			
			// Enqueue the WPMN tab CSS.
			wp_enqueue_style(
				'wpmn-tab',
				WPMN_URL . 'includes/admin/tab/css/wpmn-tab.css',
				array(),
				WPMN_VERSION 
			);

		}

	}

	// Instantiate the WPMN_Tab class.
	new WPMN_Tab();
}
