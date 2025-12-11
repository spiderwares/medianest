<?php
/**
 * Import/Export Tab: Import/Export
 * Loads the Import/Export section in the plugin settings page.
 * 
 * @package Medianest
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Retrieve the import/export fields from the Settings class.
 * @var array $fields Array of import/export settings fields.
 * 
 */
$wpmn_fields = WPMN_Settings_Fields::import_export_field();

/**
 * Fetch the saved settings from the WordPress options table.
 * @var array|false $options Retrieved settings or false if not set.
 * 
 */
$wpmn_options = get_option( 'wpmn_settings', true );

/**
 * Load the import/export form template for the Import/Export tab.
 */
wpmn_get_template(
	'fields/settings-forms.php',
	array(
		'title'       => 'Import/Export',         // Section title.
		'metaKey'     => 'wpmn_settings',   // Option meta key.
		'fields'      => $wpmn_fields,           // Field definitions.
		'options' 	  => $wpmn_options,          // Saved option values.
		'show_submit' => false,         // Hide submit button.
	),
);
