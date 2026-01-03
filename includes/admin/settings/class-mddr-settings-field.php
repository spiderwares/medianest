<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'MDDR_Settings_Fields' ) ) :

    /**
     * Main MDDR_Settings_Fields Class
     *
     * @class MDDR_Settings_Fields
     * @version 1.0.0
     */
    class MDDR_Settings_Fields {

        /**
         * Generates the general fields for media-directory configuration.
         *
         * @return array The general fields for the general configuration.
         */
        public static function general_field() {

            $fields = array(

                'user_separate_folders' => array(
                    'title'      => esc_html__( 'Individual folders for each user?', 'media-directory' ),
                    'field_type' => 'mddrswitch',
                    'default'    => 'no',
                    'name'       => 'mddr_settings[user_separate_folders]',
                ),

                'breadcrumb_navigation' => array(
                    'title'      => esc_html__( 'Show Breadcrumb Navigation', 'media-directory' ),
                    'field_type' => 'mddrswitch',
                    'default'    => 'yes',
                    'name'       => 'mddr_settings[breadcrumb_navigation]',
                ),

                'secure_svg_upload' => array(
                    'title'      => esc_html__( 'Enable Secure SVG Upload', 'media-directory' ),
                    'field_type' => 'mddrswitch',
                    'default'    => 'no',
                    'name'       => 'mddr_settings[secure_svg_upload]',
                ),

                'api_folder_search' => array(
                    'title'      => esc_html__( 'Enable API Folder Search', 'media-directory' ),
                    'field_type' => 'mddrswitch',
                    'default'    => 'no',
                    'name'       => 'mddr_settings[api_folder_search]',
                ),

                'folder_file_count' => array(
                    'title'         => esc_html__('Folder File Count', 'media-directory'),
                    'field_type'    => 'mddrtitle',
                    'extra_class'   => 'heading',
                    'default'       => '',
                ),
                
                'folder_count_mode' => array(
                    'title'      => esc_html__( 'Folder Count Mode', 'media-directory' ),
                    'field_type' => 'mddrselect',
                    'default'    => 'folder_only',
                    'name'       => 'mddr_settings[folder_count_mode]',
                    'options'    => array(
                        'folder_only'  => esc_html__( 'Count only files in this folder', 'media-directory' ),
                        'all_files'    => esc_html__( 'Count files in parent and subfolders (Pro)', 'media-directory' ),
                    ),
                    'disabled_options' => array('all_files' ),
                ),

                'choose_theme' => array(
                    'title'         => esc_html__('Choose Theme', 'media-directory'),
                    'field_type'    => 'mddrtitle',
                    'extra_class'   => 'heading',
                    'default'       => '',
                ),

                'theme_design'  => array(
                    'title'         => esc_html__( 'Select Theme Design', 'media-directory' ),
                    'field_type'    => 'mddrradio',
                    'options'       => array(
                        'default'       =>  'default.svg',
                        'windows (Pro)' =>  'windows.svg',
                        'dropbox (Pro)' =>  'dropbox.svg',
                    ),
                    'default'       => 'default',
                    'name'          => 'mddr_settings[theme_design]',
                    'disabled_options' => array('windows (Pro)', 'dropbox (Pro)' ),
                ),
            );

            return apply_filters( 'mddr_settings_fields', $fields );
        }

        public static function tools_field() {

            $fields = array(

                'rest_api_key' => array(
                    'title'      => 'REST API key',
                    'field_type' => 'mddrbutton',
                    'desc'       => esc_html__( 'Please see Media Directory API for developers', 'media-directory' ) .
                                    ' <a href="https://documentation.cosmicinfosoftware.com/media-directory/documents/plugin-settings/api-settings/" target="_blank">' . 
                                    esc_html__( 'here.', 'media-directory' ) . '</a>',
                    'name'       => 'mddr_settings[rest_api_key]',
                    'button_text' => esc_html__( 'Generate', 'media-directory' ),
                    'action'      => 'mddr_generate_api_key',
                ),

                'mddr_attachment_size' => array(
                    'title'       => esc_html__( 'Attachment Size', 'media-directory' ),
                    'field_type'  => 'mddrbutton',
                    'desc'        => esc_html__( 'Generate attachment size used in "Sort by size" function.', 'media-directory' ),
                    'button_text' => esc_html__( 'Generate', 'media-directory' ),
                    'action'      => 'mddr_generate_attachment_size',
                    'btn_class'   => 'mddr_generate_size_btn',
                ),
                
                'mddr_clear_all_data' => array(
                    'title'       => esc_html__( 'Clear Entire Data', 'media-directory' ),
                    'field_type'  => 'mddrbutton',
                    'desc'        => esc_html__( 'This action will remove all Media Directory data and settings.', 'media-directory' ),
                    'button_text' => esc_html__( 'Clear', 'media-directory' ),
                    'action'      => 'mddr_clear_all_data',
                    'btn_class'   => 'mddr_clear_data_btn', 
                ),
            );

            return apply_filters( 'mddr_tools_fields', $fields );
        }

        public static function import_export_field() {

            $fields = array(

                'export_csv' => array(
                    'title'         => esc_html__('Export CSV', 'media-directory'),
                    'field_type'    => 'mddrbutton',
                    'desc'          => esc_html__('The current directory structure will be exported.', 'media-directory'),
                    'button_text'   => esc_html__('Export Now', 'media-directory'),
                    'action'        => 'mddr_export_folders',
                ),

                'import_csv' => array(
                    'title'         => esc_html__('Import CSV', 'media-directory'),
                    'field_type'    => 'mddrbutton',
                    'desc'          => esc_html__('Import directory structure from a CSV file.', 'media-directory'),
                    'button_text'   => esc_html__('Import Now', 'media-directory'),
                    'action'        => 'mddr_import_folders',
                ),
            );

            return apply_filters( 'mddr_import_export_fields', $fields );
        }

        public static function post_type_field() {
            return apply_filters( 'mddr_post_type_fields', array() );
        }

    }

endif;
