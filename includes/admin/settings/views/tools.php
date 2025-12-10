<?php
/**
 * Tools Tab: Tools
 * Loads the Tools section in the plugin settings page.
 * 
 * @package Medianest
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Retrieve the tools fields from the Tools class.
 * @var array $fields Array of email api settings fields.
 * 
 */
$fields = WPMN_Settings_Fields::tools_field();

/**
 * Fetch the saved settings from the WordPress options table.
 * @var array|false $options Retrieved settings or false if not set.
 * 
 */
$options = get_option( 'wpmn_settings', true );

/**
 * Load the tools form template for the Tools tab.
 */
wpmn_get_template(
	'fields/settings-forms.php',
	array(
		'title'       => 'Tools',         // Section title.
		'metaKey'     => 'wpmn_settings',   // Option meta key.
		'fields'      => $fields,           // Field definitions.
		'options' 	  => $options,          // Saved option values.
		'show_submit' => false,         // Hide submit button.
	),
);