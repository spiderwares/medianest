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
         * Settings
         */
        public $settings;

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
            $this->settings = get_option( 'wpmn_settings', [] );
            add_action( 'rest_api_init', [ $this, 'wpmn_register_routes' ] );
        }

        /**
         * Register REST API routes
         */
        public function wpmn_register_routes() {

            // GET https://your-site.com/wp-json/medianest/v1/folders
            register_rest_route( 
                WPMN_REST_API_URL, '/folders', 
                array(
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_folders' ],
                'permission_callback' => [ $this, 'check_api_permission' ], 
            ));

            // GET https://your-site.com/wp-json/medianest/v1/folder/?folder_id
            register_rest_route( 
                WPMN_REST_API_URL, '/folder', 
                array(
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_folder_details' ],
                'permission_callback' => [ $this, 'check_api_permission' ], 
            )); 

            // POST https://your-site.com/wp-json/medianest/v1/folder/set-attachment
            register_rest_route( 
                WPMN_REST_API_URL, '/folder/set-attachment', 
                array(
                'methods'             => 'POST',
                'callback'            => [ $this, 'set_attachment' ],
                'permission_callback' => [ $this, 'check_api_permission' ], 
            ));

            // GET https://your-site.com/wp-json/medianest/v1/attachment-id/?folder_id=
            register_rest_route( 
                WPMN_REST_API_URL, '/attachment-id', 
                array(
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_attachment_ids' ],
                'permission_callback' => [ $this, 'check_api_permission' ], 
            ));

            // GET https://your-site.com/wp-json/medianest/v1/attachment-count/?folder_id=
            register_rest_route( 
                WPMN_REST_API_URL, '/attachment-count', 
                array(
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_attachment_count' ],
                'permission_callback' => [ $this, 'check_api_permission' ], 
            ));

            // POST https://your-site.com/wp-json/medianest/v1/folders
            register_rest_route( 
                WPMN_REST_API_URL, '/folders', 
                array(
                'methods'             => 'POST',
                'callback'            => [ $this, 'create_new_folder' ],
                'permission_callback' => [ $this, 'check_api_permission' ], 
            ));
        }

        public static function generate_api_key_request() {
            $key = wp_generate_password( 40, false );
            $options = get_option( 'wpmn_settings', [] );
            $options['rest_api_key'] = $key;
            update_option( 'wpmn_settings', $options );

            wp_send_json_success( array(
                'key'     => $key,
                'message' => esc_html__( 'API Key generated successfully.', 'medianest' )
            ));
        }

        /**
         * Check if API Folder Search is enabled and Key is valid.
         */
        public function check_api_permission( $request ) {
            
            if ( current_user_can( 'upload_files' ) ) :
                return true;
            endif;

            if ( ! $this->is_valid_api_key( $request ) ) :
                return new WP_Error( 'invalid_api_key', 'Invalid or missing API Key.', array( 'status' => 401 ) );
            endif;

            return true;
        }

        /**
         * Validate REST API Key from Header, Parameter, or Bearer Token.
         */
        public function is_valid_api_key( $request ) {

            $saved_key = isset( $this->settings['rest_api_key'] ) ? $this->settings['rest_api_key'] : '';

            if ( empty( $saved_key ) ) :
                return false;
            endif;

            $header_key = $request->get_header( 'MediaNest-API-Key' );
            if ( $header_key === $saved_key ) :
                return true;
            endif;

            $param_key = $request->get_param( 'api_key' );
            if ( $param_key === $saved_key ) :
                return true;
            endif;

            $auth_header = $request->get_header( 'Authorization' );
            if ( ! empty( $auth_header ) && preg_match( '/Bearer\s+(.*)$/i', $auth_header, $matches ) ) :
                $bearer_token = trim( $matches[1] );
                if ( $bearer_token === $saved_key ) :
                    return true;
                endif;
            endif;
            return false;
        }

        public function get_folders( $request ) {
            
            $search         = sanitize_text_field( $request->get_param( 'search' ) );
            $post_type      = sanitize_text_field( $request->get_param( 'post_type' ) );
            $search_enabled = isset( $this->settings['api_folder_search'] ) && $this->settings['api_folder_search'] === 'yes';

            // If API Search is disabled settings, ignore the search parameter
            if ( ! $search_enabled && ! empty( $search ) ) :
                $search = '';
            endif;
            
            $user_id = absint( $request->get_param( 'user_id' ) );
            
            $args   = array(
                'taxonomy'   => 'wpmn_media_folder',
                'hide_empty' => false,
                'orderby'    => 'term_id',
                'order'      => 'ASC',
            );

            if ( ! empty( $search ) ) :
                $args['name__like'] = $search;
            endif;

            $meta_query = array(
                'relation' => 'OR',
                array(
                    'key'     => 'wpmn_post_type',
                    'value'   => 'attachment',
                    'compare' => '=',
                ),
                array(
                    'key'     => 'wpmn_post_type',
                    'compare' => 'NOT EXISTS',
                ),
            );

            if ( $user_id > 0 ) :
                $args['meta_query'] = array(
                    'relation' => 'AND',
                    array(
                        'key'     => 'wpmn_folder_author',
                        'value'   => $user_id,
                        'compare' => '=',
                    ),
                    $meta_query
                );
            else :
                $args['meta_query'] = $meta_query;
            endif;

            $terms = get_terms( $args );

            if (is_wp_error($terms)) :
                return new WP_Error('fetch_error', 'Failed to fetch folders', ['status' => 500]);
            endif;

            // Sort terms by wpmn_order meta then term_id
			usort($terms, function($a, $b) {
				$ord_a = (int) get_term_meta($a->term_id, 'wpmn_order', true);
				$ord_b = (int) get_term_meta($b->term_id, 'wpmn_order', true);
				
				if ($ord_a === $ord_b) :
					return $a->term_id <=> $b->term_id;
				endif;
				return $ord_a <=> $ord_b;
			});

            // If search yielded no results, fetch all folders
                if ( empty( $terms ) && ! empty( $search ) ) :
                    unset( $args['name__like'] );
                    $terms  = get_terms( $args );
                    $search = ''; // Reset search to trigger tree view
                endif;

            // If searching, return flat list of matches
            if ( ! empty( $search ) ) :
                $folders = [];
                foreach ( $terms as $index => $term ) :
                    $count = WPMN_Media_Folders::folder_count( $term->term_id );
                    $folders[] = array(
                        'id'         => (string) $term->term_id,
                        'key'        => (string) $term->term_id,
                        'children'   => array(),
                        'parent'     => (string) $term->parent,
                        'text'       => $term->name,
                        'title'      => $term->name,
                        'data-id'    => $term->term_id,
                        'data-count' => (string) $count,
                        'ord'        => (int) get_term_meta( $term->term_id, 'wpmn_order', true ) ?: $index,
                        'color'      => get_term_meta( $term->term_id, 'wpmn_color', true ) ?: ''
                    );
                endforeach;

                return rest_ensure_response(array(
                    'success' => true,
                    'data'    => array(
                        'folders' => $folders
                    )
                ) );
            endif;

            $group = [];
            foreach ($terms as $term) :
                $term->count_with_children = WPMN_Media_Folders::folder_count($term->term_id);
                $group[$term->parent][] = $term;
            endforeach;

            return rest_ensure_response(array(
                'success' => true,
                'data'    => array(
                    'folders' => $this->build_tree(0, $group)
                )
            ) );
        }

        public function build_tree($parent, $group) {
            if (empty($group[$parent])) return [];

            $list = array();
            foreach ($group[$parent] as $index => $term) :
                $list[] = array(
                    'id'         => (string) $term->term_id,
                    'key'        => (string) $term->term_id,
                    'children'   => $this->build_tree($term->term_id, $group),
                    'parent'     => (string) $term->parent,
                    'text'       => $term->name,
                    'title'      => $term->name,
                    'data-id'    => $term->term_id,
                    'data-count' => (string) $term->count_with_children,
                    'ord'        => (int) get_term_meta( $term->term_id, 'wpmn_order', true ) ?: $index,
                    'color'      => get_term_meta( $term->term_id, 'wpmn_color', true ) ?: '',
                );
            endforeach;
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
                'success' => true,
                'data'    => array(
                    'folder' => array(
                        'id'     => (string) $term->term_id,
                        'name'   => $term->name,
                        'parent' => (string) $term->parent,
                        'count'  => (string) $term->count,
                        'color'  => get_term_meta( $term->term_id, 'wpmn_color', true ) ?: '',
                    )
                )
            ) );
        }

        public function set_attachment($request) {
            $folder_id = absint($request->get_param('folder_id'));
            $ids       = (array) $request->get_param('ids');

            if (!$folder_id) return new WP_Error('missing_param', 'folder_id is required', ['status' => 400]);
            if (empty($ids)) return new WP_Error('missing_param', 'ids are required', ['status' => 400]);

            $term = get_term($folder_id, 'wpmn_media_folder');
            if (!$term || is_wp_error($term)) :
                return new WP_Error('not_found', 'Target folder not found', ['status' => 404]);
            endif;

            $ids = array_map('absint', $ids);
            $processed_count = 0;

            foreach ($ids as $att_id) :
                if ( get_post_type($att_id) !== 'attachment' ) continue;

                // Assign new folder (replaces existing ones of the same taxonomy)
                wp_set_object_terms($att_id, $folder_id, 'wpmn_media_folder', false);
                clean_object_term_cache($att_id, 'attachment');
                
                $processed_count++;
            endforeach;

            if ($processed_count === 0) :
                return new WP_Error('invalid_ids', 'No valid attachment IDs found', ['status' => 400]);
            endif;

            wp_update_term_count_now([$folder_id], 'wpmn_media_folder');

            return rest_ensure_response(array(
                'success' => true,
                'data'    => array(
                    'message' => 'Attachments assigned successfully.'
                )
            ));
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

            return rest_ensure_response(array(
                'success' => true,
                'data'    => array(
                    'attachment_ids' => array_map('strval', get_posts($args))
                )
            ) );
        }

        public function get_attachment_count($request) {
            $id = $request->get_param('folder_id');
            if (!$id) return new WP_Error('missing_param', 'folder_id is required', ['status' => 400]);

            return rest_ensure_response(array(
                'success' => true,
                'data'    => array(
                    'count' => (string) WPMN_Media_Folders::folder_count($id)
                )
            ) );
        }

        public function create_new_folder($request) {
            $name      = sanitize_text_field($request->get_param('name'));
            $parent_id = absint($request->get_param('parent_id')) ?: 0;
            $user_id   = absint($request->get_param('user_id')) ?: 0;

            if (!$name) return new WP_Error('missing_param', 'name is required', ['status' => 400]);

            $term = WPMN_Helper::create_folder($name, $parent_id);
            if (is_wp_error($term)) return $term;

            if ( isset( $term['term_id'] ) ) :
                update_term_meta( $term['term_id'], 'wpmn_post_type', 'attachment' );
                
                if ( $user_id > 0 ) :
                    update_term_meta( $term['term_id'], 'wpmn_folder_author', $user_id );
                endif;
            endif;

            return rest_ensure_response(array(
                'success' => true,
                'data'    => array(
                    'folder'  => array(
                        'id'     => (string) $term['term_id'],
                        'name'   => $name,
                        'parent' => (string) $parent_id,
                    )
                )
            ) );
        }

    }

    new WPMN_REST_API();

endif;
