<?php
/**
 * Post Type Tab: Post Type
 * Loads the Post Type section in the plugin settings page.
 * 
 * @package Medianest
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Retrieve the post type fields from the Settings class.
 * @var array $fields Array of post type settings fields.
 * 
 */
$wpmn_fields = WPMN_Settings_Fields::post_type_field();

/**
 * Fetch the saved settings from the WordPress options table.
 * @var array|false $options Retrieved settings or false if not set.
 * 
 */
$wpmn_options = get_option( 'wpmn_settings', true );

/**
 * Load the post type form template for the Post Type tab.
 */
wpmn_get_template(
	'fields/settings-forms.php',
	array(
		'title'      => 'Post Type',         // Section title.
		'metaKey'    => 'wpmn_settings',   // Option meta key.
		'fields'     => $wpmn_fields,           // Field definitions.
		'options' 	 => $wpmn_options,          // Saved option values.
	),
);
