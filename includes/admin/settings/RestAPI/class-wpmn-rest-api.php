<?php
/**
 * REST API Handler
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WPMN_REST_API' ) ) :

    /**
     * Main WPMN_REST_API Class
     *
     * @class WPMN_REST_API
     * @version 1.0.0
     */
    class WPMN_REST_API {

        /**
         * Constructor for the class.
         */
        public function __construct() {
            $this->events_handler();
        }

        /**
         * Initialize hooks and filters.
         */
        public function events_handler() {
            add_action( 'rest_api_init', [ $this, 'wpmn_register_routes' ] );
        }

        /**
         * Register REST API routes
         */
        public function wpmn_register_routes() {

            // GET http://yoursite/wp-json/medianest/v1/folders
            register_rest_route( 'medianest/v1', '/folders', array(
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_folders' ],
                'permission_callback' => '__return_true', 
            ));

            // GET http://yoursite/wp-json/medianest/v1/folder?folder_id
            register_rest_route( 'medianest/v1', '/folder', array(
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_folder_details' ],
                'permission_callback' => '__return_true', 
            )); 

            // POST http://yoursite/wp-json/medianest/v1/folder/set-attachment
            register_rest_route( 'medianest/v1', '/folder/set-attachment', array(
                'methods'             => 'POST',
                'callback'            => [ $this, 'set_attachment' ],
                'permission_callback' => '__return_true', 
            ));

            // GET http://yoursite/wp-json/medianest/v1/attachment-id
            register_rest_route( 'medianest/v1', '/attachment-id', array(
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_attachment_ids' ],
                'permission_callback' => '__return_true', 
            ));

            // GET http://yoursite/wp-json/medianest/v1/attachment-count
            register_rest_route( 'medianest/v1', '/attachment-count', array(
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_attachment_count' ],
                'permission_callback' => '__return_true', 
            ));

            // POST http://yoursite/wp-json/medianest/v1/folders
            register_rest_route( 'medianest/v1', '/folders', array(
                'methods'             => 'POST',
                'callback'            => [ $this, 'create_new_folder' ],
                'permission_callback' => '__return_true', 
            ));
        }

        public function get_folders( $request ) {
            
            $search = sanitize_text_field( $request->get_param( 'search' ) );
            $args   = array(
                'taxonomy'   => 'wpmn_media_folder',
                'hide_empty' => false,
                'orderby'    => 'term_id',
                'order'      => 'ASC',
            );

            if ( ! empty( $search ) ) {
                $args['name__like'] = $search;
            }

            $terms = get_terms( $args );

            if (is_wp_error($terms)) :
                return new WP_Error('fetch_error', 'Failed to fetch folders', ['status' => 500]);
            endif;

            // If searching, return flat list of matches
            if ( ! empty( $search ) ) {
                $folders = [];
                foreach ( $terms as $index => $term ) {
                    $count = WPMN_Media_Folders::folder_count( $term->term_id );
                    $folders[] = array(
                        'id'         => (int) $term->term_id,
                        'key'        => (int) $term->term_id,
                        'children'   => array(),
                        'parent'     => (int) $term->parent,
                        'text'       => $term->name,
                        'title'      => $term->name,
                        'data-id'    => $term->term_id,
                        'data-count' => (string) $count,
                        'ord'        => (int) get_term_meta( $term->term_id, 'wpmn_order', true ) ?: $index,
                        'color'      => get_term_meta( $term->term_id, 'wpmn_color', true ) ?: '',
                        'count'      => $count,
                        'name'       => $term->name
                    );
                }

                return rest_ensure_response(array(
                    'success' => true,
                    'data'    => ['folders' => $folders]
                ) );
            }

            $group = [];
            foreach ($terms as $term) :
                $term->count_with_children = WPMN_Media_Folders::folder_count($term->term_id);
                $group[$term->parent][] = $term;
            endforeach;

            return rest_ensure_response(array(
                'success' => true,
                'data'    => ['folders' => $this->build_tree(0, $group)]
            ) );
        }

        public function build_tree($parent, $group) {
            if (empty($group[$parent])) return [];

            $list = array();
            foreach ($group[$parent] as $index => $term) {
                $list[] = array(
                    'id'         => (int) $term->term_id,
                    'key'        => (int) $term->term_id,
                    'children'   => $this->build_tree($term->term_id, $group),
                    'parent'     => (int) $term->parent,
                    'text'       => $term->name,
                    'title'      => $term->name,
                    'data-id'    => $term->term_id,
                    'data-count' => (string) $term->count_with_children,
                    'ord'        => (int) get_term_meta( $term->term_id, 'wpmn_order', true ) ?: $index,
                    'color'      => get_term_meta( $term->term_id, 'wpmn_color', true ) ?: '',
                );
            }
            return $list;
        }   

        public function get_folder_details($request) {
            $id = absint($request->get_param('folder_id'));
            if (!$id) return new WP_Error('missing_param', 'folder_id is required', ['status' => 400]);

            $term = get_term($id, 'wpmn_media_folder');
            if (!$term || is_wp_error($term)) :
                return new WP_Error('not_found', 'Folder not found', ['status' => 404]);
            endif;

            return rest_ensure_response(array(
                'folder' => array(
                    'id'     => $term->term_id,
                    'name'   => $term->name,
                    'parent' => $term->parent,
                    'count'  => $term->count,
                    'color'  => get_term_meta( $term->term_id, 'wpmn_color', true ) ?: '',
                )
            ) );
        }

        public function set_attachment($request) {
            global $wpdb;

            $folder_id = absint($request->get_param('folder'));
            $ids       = (array) $request->get_param('ids');

            if (!$folder_id) return new WP_Error('missing_param', 'folder is required', ['status' => 400]);
            if (empty($ids)) return new WP_Error('missing_param', 'ids are required', ['status' => 400]);

            $ids = array_map('absint', $ids);

            foreach ($ids as $att_id) :
                // Remove existing
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                $wpdb->query($wpdb->prepare("
                    DELETE tr FROM {$wpdb->term_relationships} tr
                    JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                    WHERE tr.object_id = %d AND tt.taxonomy = %s", 
                $att_id, 'wpmn_media_folder'));

                // Assign new
                if ($folder_id > 0) :
                    wp_set_object_terms($att_id, $folder_id, 'wpmn_media_folder', false);
                endif;

                clean_object_term_cache($att_id, 'attachment');
            endforeach;

            if ($folder_id > 0) :
                wp_update_term_count_now([$folder_id], 'wpmn_media_folder');
            endif;

            return rest_ensure_response(['success' => true, 'message' => 'Attachments assigned.']);
        }

        public function get_attachment_ids($request) {

            $folder_id = $request->get_param('folder_id');
            if ($folder_id === null) :
                return new WP_Error('missing_param', 'folder_id is required', ['status' => 400]);
            endif;

            $args = array(
                'post_type'      => 'attachment',
                'post_status'    => 'inherit',
                'posts_per_page' => -1,
                'fields'         => 'ids'
            );

            if ($folder_id > 0) :
                $args['tax_query'] = array(
                    array(
                        'taxonomy' => 'wpmn_media_folder',
                        'field'    => 'term_id',
                        'terms'    => absint($folder_id),
                    )
                );
            endif;

            return rest_ensure_response(get_posts($args));
        }

        public function get_attachment_count($request) {
            $id = $request->get_param('folder_id');
            if (!$id) return new WP_Error('missing_param', 'folder_id is required', ['status' => 400]);

            return rest_ensure_response(array(
                'count' => WPMN_Media_Folders::folder_count($id)
            ) );
        }

        public function create_new_folder($request) {
            $name      = sanitize_text_field($request->get_param('name'));
            $parent_id = absint($request->get_param('parent_id')) ?: 0;

            if (!$name) return new WP_Error('missing_param', 'name is required', ['status' => 400]);

            $term = WPMN_Helper::create_folder($name, $parent_id);
            if (is_wp_error($term)) return $term;

            return rest_ensure_response(array(
                'success' => true,
                'folder'  => array(
                    'id'     => $term['term_id'],
                    'name'   => $name,
                    'parent' => $parent_id,
                )
            ) );
        }


    }

    new WPMN_REST_API();

endif;
