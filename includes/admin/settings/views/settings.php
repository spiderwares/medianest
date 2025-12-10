<?php
/**
 * Settings Tab: Settings
 * Loads the Settings section in the plugin settings page.
 * 
 * @package Medianest
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Retrieve the settings fields from the Settings class.
 * @var array $fields Array of settings fields.
 * 
 */
$fields = WPMN_Settings_Fields::settings_field();

/**
 * Fetch the saved settings from the WordPress options table.
 * @var array|false $options Retrieved settings or false if not set.
 * 
 */
$options = get_option( 'wpmn_settings', true );

/**
 * Load the settings form template for the Settings tab.
 */
wpmn_get_template(
	'fields/settings-forms.php',
	array(
		'title'   => 'Settings',         // Section title.
		'metaKey' => 'wpmn_settings',   // Option meta key.
		'fields'  => $fields,           // Field definitions.
		'options' => $options,          // Saved option values.
	),
);