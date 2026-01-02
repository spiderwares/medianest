<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WPMN_Tab' ) ) {

	/**
     * Main WPMN_Tab Class
     *
     * @class WPMN_Tab
     * @version 1.0.0
     */
	class WPMN_Tab {

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
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		}

		/**
		 * Enqueue admin-specific styles for the tab.
		 */
		public function enqueue_scripts() {
			
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
