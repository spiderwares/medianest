<?php
/**
 * Media folder taxonomy
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WPMN_Export' ) ) :

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

			$csv_data   = [['id','name','parent','type','ord','created_by','attachment_ids']];
			$grouped    = [];

			// Group folders by parent
			foreach ($terms as $term) :
				$grouped[$term->parent][] = $term;
			endforeach;

			foreach ($terms as $term) :
				$created_by  = get_term_meta($term->term_id, 'wpmn_folder_owner', true) ?: 1;
				$siblings    = isset($grouped[$term->parent]) ? $grouped[$term->parent] : [];
				$ord         = array_search($term, $siblings) ?: 0;

				// Get attachments inside this folder
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$attachment_ids = $wpdb->get_col($wpdb->prepare(
					"SELECT DISTINCT tr.object_id
					FROM {$wpdb->term_relationships} tr
					INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
					INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
					WHERE tt.taxonomy = %s AND tt.term_id = %d
					AND p.post_type = 'attachment' AND p.post_status != 'trash'
					ORDER BY tr.object_id ASC",
					'wpmn_media_folder', $term->term_id
				));

				$csv_data[] = array(	
					$term->term_id,
					$term->name,
					$term->parent,
					0,
					$ord,
					$created_by,
					implode('|', $attachment_ids ?: [])
				);
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
