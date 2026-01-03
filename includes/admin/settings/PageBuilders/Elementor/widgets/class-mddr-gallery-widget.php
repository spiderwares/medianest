<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'MDDR_Gallery_Widget' ) ) :

    /**
     * Main MDDR_Gallery_Widget Class
     *
     * @class MDDR_Gallery_Widget
     * @version 1.0.0
     */
    class MDDR_Gallery_Widget extends \Elementor\Widget_Base {

        /**
         * Get widget name.
         *
         * @return string Widget name.
         */
        public function get_name() {
            return 'media_directory_gallery';
        }

        /**
         * Get widget title.
         */
        public function get_title() {
            return esc_html__('Media Directory Gallery', 'media-directory');
        }

        /**
         * Get widget icon.
         */
        public function get_icon() {
            return 'eicon-gallery-grid';
        }

        /**
         * Get widget categories.
         */
        public function get_categories() {
            return ['media-directory'];
        }

        /**
         * Register widget controls.
         */
        protected function register_controls() {
            $folders = \MDDR_Media_Folders::folder_tree('folder_only', 'attachment');
            
            $folder_options = array(
                '0' => esc_html__('Select Folder', 'media-directory'),
            );
            
            $this->build_folder_options($folders, $folder_options);

            $this->start_controls_section(
                'content_section',
                array(
                    'label' => esc_html__('Gallery Settings', 'media-directory'),
                    'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
                )
            );

            $this->add_control(
                'folder_id',
                array(
                    'label'   => esc_html__('Select Folder', 'media-directory'),
                    'type'    => \Elementor\Controls_Manager::SELECT,
                    'options' => $folder_options,
                    'default' => '0',
                    'description' => esc_html__('Choose a folder to display images from', 'media-directory'),
                )
            );

            $this->add_control(
                'columns',
                array(
                    'label'   => esc_html__('Columns', 'media-directory'),
                    'type'    => \Elementor\Controls_Manager::SELECT,
                    'options' => array(
                        '1' => '1',
                        '2' => '2',
                        '3' => '3',
                        '4' => '4',
                        '5' => '5',
                        '6' => '6',
                    ),
                    'default' => '3',
                    'description' => esc_html__('Number of columns to display', 'media-directory'),
                )
            );

            $this->add_control(
                'link_to',
                array(  
                    'label'   => esc_html__('Link To', 'media-directory'),
                    'type'    => \Elementor\Controls_Manager::SELECT,
                    'options' => array(
                        'none' => esc_html__('None', 'media-directory'),
                        'file' => esc_html__('Media File', 'media-directory'),
                        'post' => esc_html__('Attachment Page', 'media-directory'),
                    ),
                    'default' => 'file',
                    'description' => esc_html__('Link behavior when clicking on image', 'media-directory'),
                )
            );

            $this->add_control(
                'size',
                array(
                    'label'    => esc_html__('Image Size', 'media-directory'),
                    'type'     => \Elementor\Controls_Manager::SELECT,
                    'options'  => array_merge(
                        array(
                            'full' => esc_html__('Full', 'media-directory')
                        ), 
                        $this->get_image_sizes()),
                    'default'     => 'medium',
                    'description' => esc_html__('Select image size', 'media-directory'),
                )
            );

            $this->add_control(
                'orderby',
                array(
                    'label'   => esc_html__('Order By', 'media-directory'),
                    'type'    => \Elementor\Controls_Manager::SELECT,
                    'options' => array(
                        'date'  => esc_html__('Date', 'media-directory'),
                        'title' => esc_html__('Title', 'media-directory'),
                        'rand'  => esc_html__('Random', 'media-directory'),
                    ),
                    'default'     => 'date',
                    'description' => esc_html__('Order images by', 'media-directory'),
                )
            );

            $this->add_control(
                'order',
                array(
                    'label'  => esc_html__('Order', 'media-directory'),
                    'type'   => \Elementor\Controls_Manager::SELECT,
                    'options'  => array(
                        'ASC'  => esc_html__('Ascending', 'media-directory'),
                        'DESC' => esc_html__('Descending', 'media-directory'),
                    ),
                    'default'     => 'DESC',
                    'description' => esc_html__('Sort order', 'media-directory'),
                )
            );

            $this->end_controls_section();

            // Style section
            $this->start_controls_section(
                'style_section',
                array(
                    'label' => esc_html__('Gallery Style', 'media-directory'),
                    'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
                )
            );

            $this->add_responsive_control(
                'image_spacing',
                array(
                    'label'      => esc_html__('Spacing', 'media-directory'),
                    'type'       => \Elementor\Controls_Manager::SLIDER,
                    'size_units' => array('px', 'em'),
                    'range'      => array(
                        'px' => array(
                            'min' => 0,
                            'max' => 50,
                        ),
                        'em' => array(
                            'min' => 0,
                            'max' => 5,
                        ),
                    ),
                    'default' => array(
                        'unit' => 'px',
                        'size' => 10,
                    ),
                    'selectors' => array(
                        '{{WRAPPER}} .mddr_gallery' => 'margin: -{{SIZE}}{{UNIT}};',
                        '{{WRAPPER}} .mddr_gallery_item' => 'padding: {{SIZE}}{{UNIT}};',
                    ),
                )
            );

            $this->add_group_control(
                \Elementor\Group_Control_Border::get_type(),
                array(
                    'name'      => 'image_border',
                    'selector'  => '{{WRAPPER}} .mddr_gallery_item img',
                )
            );

            $this->add_control(
                'image_border_radius',
                array(
                    'label'   => esc_html__('Border Radius', 'media-directory'),
                    'type'    => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => array('px', '%'),
                    'selectors'  => array(
                        '{{WRAPPER}} .mddr_gallery_item img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ),
                )
            );

            $this->end_controls_section();
        }

        /**
         * For older versions of Elementor
         */
        protected function _register_controls() {
            $this->register_controls();
        }

        /**
         * Recursively builds folder options including all children
         */
        private function build_folder_options($folders, &$options, $prefix = '') {
            if (!is_array($folders)) return;
            
            foreach ($folders as $folder) :
                $options['id_' . $folder['id']] = $prefix . $folder['name'];
                
                if (!empty($folder['children']) && is_array($folder['children'])) :
                    $this->build_folder_options($folder['children'], $options, $prefix . '— ');
                endif;
            endforeach;
        }

        /**
         * Get available image sizes
         */
        private function get_image_sizes() {
            $sizes    = [];
            $wp_sizes = get_intermediate_image_sizes();
            
            foreach ($wp_sizes as $size) :
                $sizes[$size] = $size;
            endforeach;
            
            return $sizes;
        }

        /**
         * Render widget output on the frontend.
         */
        protected function render() {
            $settings       = $this->get_settings_for_display();
            $folder_id_raw  = isset($settings['folder_id']) ? $settings['folder_id'] : '0';
            $folder_id      = (int)str_replace('id_', '', $folder_id_raw);

            if ($folder_id <= 0) :
                echo '<div class="mddr-gallery-error">' . esc_html__('Please select a folder', 'media-directory') . '</div>';
                return;
            endif;

            $attachment_ids = get_objects_in_term($folder_id, 'mddr_media_folder');
            
            if (empty($attachment_ids) || is_wp_error($attachment_ids)) :
                echo '<div class="mddr-gallery-empty">' . esc_html__('No images found in this folder', 'media-directory') . '</div>';
                return;
            endif;

            $args = array(
                'post_type'      => 'attachment',
                'post_status'    => 'inherit',
                'post__in'       => $attachment_ids,
                'posts_per_page' => -1,
                'orderby'        => $settings['orderby'],
                'order'          => $settings['order'],
            );

            $query = new \WP_Query($args);
            
            if (!$query->have_posts()) :
                echo '<div class="mddr-gallery-empty">' . esc_html__('No images found in this folder', 'media-directory') . '</div>';
                wp_reset_postdata();
                return;
            endif;
            
            include MDDR_PATH . 'includes/admin/settings/PageBuilders/Elementor/views/mddr-gallery.php';
            wp_reset_postdata();
        }
    }

endif;
