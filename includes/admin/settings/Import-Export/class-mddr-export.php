<?php
/**
 * Media folder taxonomy
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'MDDR_Export' ) ) :

	/**
     * Main MDDR_Export Class
     *
     * @class MDDR_Export
     * @version 1.0.0
     */
	class MDDR_Export {

		public static function export_folders_request() {

			global $wpdb;

			$terms = get_terms(array(
				'taxonomy'   => 'mddr_media_folder',
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
				$post_type = get_term_meta($term->term_id, 'mddr_post_type', true) ?: 'attachment';
				$by_post_type[$post_type][] = $term;
				$parent_groups[$term->parent][] = $term->term_id;
			endforeach;

			// Iterate through each post type group
			foreach ($by_post_type as $post_type => $type_terms) :
				foreach ($type_terms as $term) :
					$created_by  = get_term_meta($term->term_id, 'mddr_folder_owner', true) ?: 1;
					$siblings 	 = isset( $parent_groups[$term->parent] ) ? $parent_groups[$term->parent] : [];
					$ord         = array_search($term->term_id, $siblings) ?: 0;

					// Get objects inside this folder for the specific post type
					$attachment_ids = get_posts( array(
						'post_type'      => $post_type,
						'posts_per_page' => -1,
						'post_status'    => 'any',
						'tax_query'      => array(
							array(
								'taxonomy' => 'mddr_media_folder',
								'field'    => 'term_id',
								'terms'    => $term->term_id,
							),
						),
						'fields'         => 'ids',
						'orderby'        => 'ID',
						'order'          => 'ASC',
					) );

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
			$filename = 'media-directory.csv';
			header('Content-Type: text/csv; charset=utf-8');
			header("Content-Disposition: attachment; filename=$filename");

			$output = '';
			foreach ($csv_data as $row) :
				$csv_row = [];
				foreach ($row as $field) :
					$field = str_replace('"', '""', $field);
					$csv_row[] = '"' . $field . '"';
				endforeach;
				$output .= implode(',', $csv_row) . "\r\n";
			endforeach;

			echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			exit;
		}
	}

	new MDDR_Export();
	
endif;
