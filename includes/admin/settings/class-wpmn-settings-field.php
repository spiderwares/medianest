<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WPMN_Settings_Fields' ) ) :

    /**
     * Class WPMN_Settings_Fields
     * Handles the admin settings for Medianest.
     */
    class WPMN_Settings_Fields {

        /**
         * Generates the settings fields for medianest configuration.
         *
         * @return array The settings fields for the settings configuration.
         */
        public static function settings_field() {

            $fields = array(

                'user_separate_folders' => array(
                    'title'      => esc_html__( 'Individual folders for each user?', 'medianest' ),
                    'field_type' => 'wpmnswitch',
                    'default'    => 'no',
                    'name'       => 'wpmn_settings[user_separate_folders]',
                ),

                'breadcrumb_navigation' => array(
                    'title'      => esc_html__( 'Show Breadcrumb Navigation', 'medianest' ),
                    'field_type' => 'wpmnswitch',
                    'default'    => 'yes',
                    'name'       => 'wpmn_settings[breadcrumb_navigation]',
                ),

                'secure_svg_upload' => array(
                    'title'      => esc_html__( 'Enable Secure SVG Upload', 'medianest' ),
                    'field_type' => 'wpmnswitch',
                    'default'    => 'no',
                    'name'       => 'wpmn_settings[secure_svg_upload]',
                ),

                'api_folder_search' => array(
                    'title'      => esc_html__( 'Enable API Folder Search', 'medianest' ),
                    'field_type' => 'wpmnswitch',
                    'default'    => 'no',
                    'name'       => 'wpmn_settings[api_folder_search]',
                ),

                'folder_file_count' => array(
                    'title'         => esc_html__('Folder File Count', 'medianest'),
                    'field_type'    => 'wpmntitle',
                    'extra_class'   => 'heading',
                    'default'       => '',
                ),
                
                'folder_count_mode' => array(
                    'title'      => esc_html__( 'Folder Count Mode', 'medianest' ),
                    'field_type' => 'wpmnradio',
                    'default'    => 'folder_only',
                    'name'       => 'wpmn_settings[folder_count_mode]',
                    'options'    => array(
                        'folder_only'  => esc_html__( 'Count only files in this folder', 'medianest' )
                    ),
                ),

                'choose_theme' => array(
                    'title'         => esc_html__('Choose Theme', 'medianest'),
                    'field_type'    => 'wpmntitle',
                    'extra_class'   => 'heading',
                    'default'       => '',
                ),

                'theme_design'  => array(
                    'title'          => esc_html__( 'Select Theme Design', 'medianest' ),
                    'field_type'    => 'wpmnradio',
                    'options'       => array(
                        'default'   =>  'default.svg',
                        'windows'   =>  'windows.svg',
                        'dropbox'   =>  'dropbox.svg',
                    ),
                    'default'       => 'default',
                    'name'          => 'wpmn_settings[theme_design]',
                    'disabled_options' => array('windows', 'dropbox' ),
                ),

                // 'post_type_selection' => array(
                //     'title'         => esc_html__('Post Type Selection', 'medianest'),
                //     'field_type'    => 'wpmntitle',
                //     'extra_class'   => 'heading',
                //     'default'       => '',
                // ),

                // 'post_types' => array(
                //     'title'      => esc_html__( 'Choose MediaNest Post Types', 'medianest' ),
                //     'field_type' => 'wpmncheckbox',
                //     'default'    => array( '' ),
                //     'name'       => 'wpmn_settings[post_types]',
                //     'options'    => self::get_post_types(),
                // ),

            );

            return apply_filters( 'wpmn_settings_fields', $fields );
        }

        /**
         * Get all available post types for checkbox selection.
         *
         * @return array Post types array.
         */
        // public static function get_post_types() {

        //     $args = array( 'show_ui' => true );

        //     $post_types = get_post_types($args, 'objects');
        //     $post_type_options = array();

        //     foreach ($post_types as $post_type) {
        //         if ($post_type->name !== 'attachment') {
        //             $post_type_options[$post_type->name] = $post_type->label;
        //         }
        //     }

        //     return $post_type_options;
        // }

        public static function tools_field() {
            $fields = array(
                'wpmn_clear_all_data' => array(
                    'title'       => esc_html__( 'Clear Entire Data', 'medianest' ),
                    'field_type'  => 'wpmnrequest',
                    'desc'        => esc_html__( 'This action will remove all MediaNest data and settings and restore the WordPress media library to its default state.', 'medianest' ),
                    'button_text' => esc_html__( 'Clear', 'medianest' ),
                    'action'      => 'wpmn_clear_all_data',
                ),
            );
            return apply_filters( 'wpmn_tools_fields', $fields );
        }

    }

endif;
