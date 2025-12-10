<?php
/**
 * Media Library customisations for Medianest.
 *
 * @package Medianest
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WPMN_Media_Library' ) ) :

	/**
	 * Handles the custom left sidebar inside the WordPress media library.
	 */
	class WPMN_Media_Library {

		/**
		 * Boot hooks.
		 */
		public function __construct() {
			add_action( 'admin_footer-upload.php', array( $this, 'render_sidebar' ) );
            add_action( 'attachment_fields_to_edit', array( $this, 'add_folder_field' ), 10, 2 );
            add_filter( 'ajax_query_attachments_args', array( $this, 'wpmn_filter_attachments' ) );
		}

		/**
		 * Output the sidebar template at the end of the media library markup.
		 */
		public function render_sidebar() {
			wpmn_get_template(
				'media/library-sidebar.php',
				array(),
			);
		}

        public function wpmn_filter_attachments( $query ) {
            if ( ! empty( $_REQUEST['query']['wpmn_folder'] ) ) {
                $folder = sanitize_text_field( $_REQUEST['query']['wpmn_folder'] );

                if ( 'uncategorized' === $folder ) {
                    $terms = get_terms( array(
                        'taxonomy' => 'wpmn_media_folder',
                        'fields'   => 'ids',
                        'hide_empty' => false,
                    ) );
                    
                    if ( ! empty( $terms ) ) {
                        $query['tax_query'] = array(
                            array(
                                'taxonomy' => 'wpmn_media_folder',
                                'field'    => 'term_id',
                                'terms'    => $terms,
                                'operator' => 'NOT IN',
                            ),
                        );
                    }
                } elseif ( strpos( $folder, 'term-' ) === 0 ) {
                    $term_id = absint( str_replace( 'term-', '', $folder ) );
                    if ( $term_id > 0 ) {
                        $query['tax_query'] = array(
                            array(
                                'taxonomy' => 'wpmn_media_folder',
                                'field'    => 'term_id',
                                'terms'    => $term_id,
                                'include_children' => false,
                            ),
                        );
                    }
                }
            }
            return $query;
        }

        public function add_folder_field( $form_fields, $post ) {
            $form_fields['wpmn_media_folder'] = array(
                'label' => __( 'MediaNest Folder', 'medianest' ),
                'input' => 'select',
                'options' => $this->get_folders(),
            );
            return $form_fields;
        }

        public function get_folders() {
            $terms = get_terms(
                array(
                    'taxonomy' => 'wpmn_media_folder',
                    'hide_empty' => false,
                )
            );
            $folders = array();
            foreach ( $terms as $term ) {
                $folders[ $term->term_id ] = $term->name;
            }
            return $folders;
        }
	}

	new WPMN_Media_Library();

endif;
