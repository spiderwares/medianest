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
                    'field_type' => 'wpmnselect',
                    'default'    => 'folder_only',
                    'name'       => 'wpmn_settings[folder_count_mode]',
                    'options'    => array(
                        'folder_only'  => esc_html__( 'Count only files in this folder', 'medianest' ),
                        'all_files'    => esc_html__( 'Count files in parent and subfolders (Pro)', 'medianest' ),
                    ),
                    'disabled_options' => array('all_files' ),
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
                        'default'       =>  'default.svg',
                        'windows (Pro)' =>  'windows.svg',
                        'dropbox (Pro)' =>  'dropbox.svg',
                    ),
                    'default'       => 'default',
                    'name'          => 'wpmn_settings[theme_design]',
                    'disabled_options' => array('windows (Pro)', 'dropbox (Pro)' ),
                ),
            );

            return apply_filters( 'wpmn_settings_fields', $fields );
        }

        public static function tools_field() {

            $fields = array(

                'rest_api_key' => array(
                    'title'      => 'REST API key',
                    'field_type' => 'wpmnbutton',
                    'desc'       => esc_html__( 'Please see MediaNest API for developers', 'medianest' ) .
                                ' <a href="https://plugin.cosmicinfosoftware.com/medianest/" target="_blank">' . 
                                esc_html__( 'here.', 'medianest' ) . '</a>',
                    'name'       => 'wpmn_settings[rest_api_key]',
                    'button_text' => esc_html__( 'Generate', 'medianest' ),
                    'action'      => 'wpmn_generate_api_key',
                ),

                'wpmn_attachment_size' => array(
                    'title'       => esc_html__( 'Attachment Size', 'medianest' ),
                    'field_type'  => 'wpmnbutton',
                    'desc'        => esc_html__( 'Generate attachment size used in "Sort by size" function.', 'medianest' ),
                    'button_text' => esc_html__( 'Generate', 'medianest' ),
                    'action'      => 'wpmn_generate_attachment_size',
                    'btn_class'   => 'wpmn_generate_size_btn',
                ),
                
                'wpmn_clear_all_data' => array(
                    'title'       => esc_html__( 'Clear Entire Data', 'medianest' ),
                    'field_type'  => 'wpmnbutton',
                    'desc'        => esc_html__( 'This action will remove all MediaNest data and settings and restore the WordPress media library to its default state.', 'medianest' ),
                    'button_text' => esc_html__( 'Clear', 'medianest' ),
                    'action'      => 'wpmn_clear_all_data',
                    'btn_class'   => 'wpmn_clear_data_btn', 
                ),
            );

            return apply_filters( 'wpmn_tools_fields', $fields );
        }

        public static function import_export_field() {

            $fields = array(

                'export_csv' => array(
                    'title'         => esc_html__('Export CSV', 'medianest'),
                    'field_type'    => 'wpmnbutton',
                    'desc'          => esc_html__('The current directory structure will be exported.', 'medianest'),
                    'button_text'   => esc_html__('Export Now', 'medianest'),
                    'action'        => 'wpmn_export_folders',
                ),

                'import_csv' => array(
                    'title'         => esc_html__('Import CSV', 'medianest'),
                    'field_type'    => 'wpmnbutton',
                    'desc'          => esc_html__('Import directory structure from a CSV file.', 'medianest'),
                    'button_text'   => esc_html__('Import Now', 'medianest'),
                    'action'        => 'wpmn_import_folders',
                ),
            );

            return apply_filters( 'wpmn_import_export_fields', $fields );
        }

        public static function post_type_field() {
            return apply_filters( 'wpmn_post_type_fields', array() );
        }

    }

endif;
