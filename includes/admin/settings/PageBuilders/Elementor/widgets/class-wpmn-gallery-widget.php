<?php

namespace MediaNest\PageBuilders\Elementor;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WPMN_Gallery_Widget' ) ) :

    /**
     * MediaNest Gallery Widget for Elementor
     * 
     * @since 1.0.0
     */
    class WPMN_Gallery_Widget extends \Elementor\Widget_Base {

        /**
         * Get widget name.
         *
         * @return string Widget name.
         */
        public function get_name() {
            return 'medianest_gallery';
        }

        /**
         * Get widget title.
         */
        public function get_title() {
            return esc_html__('MediaNest Gallery', 'medianest');
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
            return ['medianest'];
        }

        /**
         * Register widget controls.
         */
        protected function register_controls() {
            $folders = \WPMN_Media_Folders::folder_tree('folder_only', 'attachment');
            
            $folder_options = array(
                '0' => esc_html__('Select Folder', 'medianest'),
            );
            
            $this->build_folder_options($folders, $folder_options);

            $this->start_controls_section(
                'content_section',
                array(
                    'label' => esc_html__('Gallery Settings', 'medianest'),
                    'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
                )
            );

            $this->add_control(
                'folder_id',
                array(
                    'label' => esc_html__('Select Folder', 'medianest'),
                    'type'  => \Elementor\Controls_Manager::SELECT,
                    'options' => $folder_options,
                    'default' => '0',
                    'description' => esc_html__('Choose a folder to display images from', 'medianest'),
                )
            );

            $this->add_control(
                'columns',
                array(
                    'label' => esc_html__('Columns', 'medianest'),
                    'type'  => \Elementor\Controls_Manager::SELECT,
                    'options' => array(
                        '1' => '1',
                        '2' => '2',
                        '3' => '3',
                        '4' => '4',
                        '5' => '5',
                        '6' => '6',
                    ),
                    'default' => '3',
                    'description' => esc_html__('Number of columns to display', 'medianest'),
                )
            );

            $this->add_control(
                'link_to',
                array(  
                    'label' => esc_html__('Link To', 'medianest'),
                    'type'  => \Elementor\Controls_Manager::SELECT,
                    'options' => array(
                        'none' => esc_html__('None', 'medianest'),
                        'file' => esc_html__('Media File', 'medianest'),
                        'post' => esc_html__('Attachment Page', 'medianest'),
                    ),
                    'default' => 'file',
                    'description' => esc_html__('Link behavior when clicking on image', 'medianest'),
                )
            );

            $this->add_control(
                'size',
                array(
                    'label'       => esc_html__('Image Size', 'medianest'),
                    'type'        => \Elementor\Controls_Manager::SELECT,
                    'options'     => array_merge(
                        array(
                            'full' => esc_html__('Full', 'medianest')
                        ), 
                        $this->get_image_sizes()),
                    'default'     => 'medium',
                    'description' => esc_html__('Select image size', 'medianest'),
                )
            );

            $this->add_control(
                'orderby',
                array(
                    'label' => esc_html__('Order By', 'medianest'),
                    'type'  => \Elementor\Controls_Manager::SELECT,
                    'options' => array(
                        'date'  => esc_html__('Date', 'medianest'),
                        'title' => esc_html__('Title', 'medianest'),
                        'rand'  => esc_html__('Random', 'medianest'),
                    ),
                    'default'     => 'date',
                    'description' => esc_html__('Order images by', 'medianest'),
                )
            );

            $this->add_control(
                'order',
                array(
                    'label'  => esc_html__('Order', 'medianest'),
                    'type'   => \Elementor\Controls_Manager::SELECT,
                    'options'  => array(
                        'ASC'  => esc_html__('Ascending', 'medianest'),
                        'DESC' => esc_html__('Descending', 'medianest'),
                    ),
                    'default'     => 'DESC',
                    'description' => esc_html__('Sort order', 'medianest'),
                )
            );

            $this->end_controls_section();

            // Style section
            $this->start_controls_section(
                'style_section',
                array(
                    'label' => esc_html__('Gallery Style', 'medianest'),
                    'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
                )
            );

            $this->add_responsive_control(
                'image_spacing',
                array(
                    'label'      => esc_html__('Spacing', 'medianest'),
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
                        '{{WRAPPER}} .wpmn_gallery' => 'margin: -{{SIZE}}{{UNIT}};',
                        '{{WRAPPER}} .wpmn_gallery_item' => 'padding: {{SIZE}}{{UNIT}};',
                    ),
                )
            );

            $this->add_group_control(
                \Elementor\Group_Control_Border::get_type(),
                array(
                    'name'      => 'image_border',
                    'selector'  => '{{WRAPPER}} .wpmn_gallery_item img',
                )
            );

            $this->add_control(
                'image_border_radius',
                array(
                    'label'   => esc_html__('Border Radius', 'medianest'),
                    'type'    => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => array('px', '%'),
                    'selectors'  => array(
                        '{{WRAPPER}} .wpmn_gallery_item img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
            $sizes   = [];
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
            $folder_id_raw  = $settings['folder_id'] ?? '0';
            $folder_id      = (int)str_replace('id_', '', $folder_id_raw);

            if ($folder_id <= 0) :
                echo '<div class="wpmn-gallery-error">' . esc_html__('Please select a folder', 'medianest') . '</div>';
                return;
            endif;

            $attachment_ids = get_objects_in_term($folder_id, 'wpmn_media_folder');
            
            if (empty($attachment_ids) || is_wp_error($attachment_ids)) :
                echo '<div class="wpmn-gallery-empty">' . esc_html__('No images found in this folder', 'medianest') . '</div>';
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
                echo '<div class="wpmn-gallery-empty">' . esc_html__('No images found in this folder', 'medianest') . '</div>';
                wp_reset_postdata();
                return;
            endif;
            
            include WPMN_PATH . 'includes/admin/settings/PageBuilders/Elementor/views/gallery.php';
            wp_reset_postdata();
        }
    }

endif;
