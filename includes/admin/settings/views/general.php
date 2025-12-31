<?php
/**
 * Settings Tab: General
 * Loads the General section in the plugin general page.
 * 
 * @package Medianest
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Retrieve the general fields from the General class.
 * @var array $fields Array of general fields.
 * 
 */
$wpmn_fields  = WPMN_Settings_Fields::general_field();

/**
 * Fetch the saved general from the WordPress options table.
 * @var array|false $options Retrieved general or false if not set.
 * 
 */
$wpmn_options = get_option( 'wpmn_settings', true );

/**
 * Load the settings form template for the general tab.
 */
wpmn_get_template(
	'fields/settings-forms.php',
	array(
		'title'   => 'General',         // Section title.
		'metaKey' => 'wpmn_settings',   // Option meta key.
		'fields'  => $wpmn_fields,           // Field definitions.
		'options' => $wpmn_options,          // Saved option values.
	),
);