<?php
/**
 * Media folder taxonomy
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WPMN_Export' ) ) :

	/**
     * Main WPMN_Export Class
     *
     * @class WPMN_Export
     * @version 1.0.0
     */
	class WPMN_Export {

        public static function export_folders_request() {

			global $wpdb;

			$terms = get_terms(array(
				'taxonomy'   => 'wpmn_media_folder',
				'hide_empty' => false,
				'orderby'    => 'term_id',
				'order'      => 'ASC',
			) );

			if (is_wp_error($terms)) :
				wp_send_json_error(['message' => 'Failed to fetch folders.']);
			endif;

			$csv_data       = [['id', 'name', 'parent', 'type', 'ord', 'created_by', 'attachment_ids', 'post_type']];
			$by_post_type   = [];
			$parent_groups  = [];

			// Pre-process terms to group by post_type and parent
			foreach ($terms as $term) :
				$post_type = get_term_meta($term->term_id, 'wpmn_post_type', true) ?: 'attachment';
				$by_post_type[$post_type][] = $term;
				$parent_groups[$term->parent][] = $term->term_id;
			endforeach;

			// Iterate through each post type group
			foreach ($by_post_type as $post_type => $type_terms) :
				foreach ($type_terms as $term) :
					$created_by  = get_term_meta($term->term_id, 'wpmn_folder_owner', true) ?: 1;
					$siblings    = $parent_groups[$term->parent] ?? [];
					$ord         = array_search($term->term_id, $siblings) ?: 0;

					// Get objects inside this folder for the specific post type
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery
					$attachment_ids = $wpdb->get_col($wpdb->prepare(
						"SELECT DISTINCT tr.object_id
						FROM {$wpdb->term_relationships} tr
						INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
						INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
						WHERE tt.taxonomy = %s AND tt.term_id = %d
						AND p.post_type = %s AND p.post_status != 'trash'
						ORDER BY tr.object_id ASC",
						'wpmn_media_folder', $term->term_id, $post_type
					));

					$csv_data[] = array(
						$term->term_id,
						$term->name,
						$term->parent,
						0,
						$ord,
						$created_by,
						implode('|', $attachment_ids ?: []),
						$post_type,
					);
				endforeach;
			endforeach;

			// Output CSV
			$filename = 'medianest.csv';
			header('Content-Type: text/csv; charset=utf-8');
			header("Content-Disposition: attachment; filename=$filename");

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
			$output = fopen('php://output', 'w');
			foreach ($csv_data as $row) :
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputcsv
				fputcsv($output, $row);
			endforeach;

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			fclose($output);
			exit;
		}
	}

	new WPMN_Export();
	
endif;
