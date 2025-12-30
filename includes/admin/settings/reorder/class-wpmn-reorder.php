<?php
/**
 * Media folder taxonomy
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WPMN_Reorder' ) ) :

	class WPMN_Reorder {

		public static function reorder_folder_request() {

			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wpmn_media_nonce' ) ) :
                wp_die( esc_html__( 'Security check failed.', 'medianest' ) );
            endif;

			$folder_id = isset($_POST['folder_id']) ? absint($_POST['folder_id']) : 0;
			$target_id = isset($_POST['target_id']) ? absint($_POST['target_id']) : 0;
			$position  = isset( $_POST['position'] ) ? sanitize_text_field( wp_unslash( $_POST['position'] ) ) : 'after';
            $post_type = isset( $_POST['post_type'] ) ? sanitize_text_field( wp_unslash( $_POST['post_type'] ) ) : 'attachment';

			if (!$folder_id || !$target_id) :
				wp_send_json_error(['message' => 'Invalid IDs.']);
			endif;

			$target_term = get_term($target_id, 'wpmn_media_folder');
			if (!$target_term || is_wp_error($target_term)) :
				wp_send_json_error(['message' => 'Target folder not found.']);
			endif;

			wp_update_term($folder_id, 'wpmn_media_folder', array(
				'parent' => $target_term->parent
			) );

			// Get all siblings
			$siblings = get_terms(array(
				'taxonomy'   => 'wpmn_media_folder',
				'parent'     => $target_term->parent,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC'
			) );

			usort($siblings, function($a, $b) {
				$ord_a = (int) get_term_meta($a->term_id, 'wpmn_order', true);
				$ord_b = (int) get_term_meta($b->term_id, 'wpmn_order', true);
				return $ord_a <=> $ord_b;
			});

			$new_order = [];
			foreach ($siblings as $sibling) :
				if ($sibling->term_id == $folder_id) continue;

				if ($sibling->term_id == $target_id) :
					if ($position === 'before') :
						$new_order[] = $folder_id;
						$new_order[] = $sibling->term_id;
					else :
						$new_order[] = $sibling->term_id;
						$new_order[] = $folder_id;
					endif;
				else :
					$new_order[] = $sibling->term_id;
				endif;
			endforeach;

			if (!in_array($folder_id, $new_order)) :
				if ($position === 'before') :
					array_unshift($new_order, $folder_id);
				else :
					$new_order[] = $folder_id;
				endif;
			endif;

			// Save order
			foreach ($new_order as $index => $term_id) :
				update_term_meta($term_id, 'wpmn_order', $index);
			endforeach;

			wp_send_json_success(WPMN_Media_Folders::payload(null, $post_type));
		}
	}

	new WPMN_Reorder();

endif;
