<?php
/**
 * Media Library Sidebar Template.
 *
 * @package Medianest
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<div id="wpmn_media_sidebar" class="wpmn_media_sidebar">
	<button type="button" class="wpmn_media_sidebar_toggle">
		<span class="dashicons dashicons-arrow-left-alt2"></span>
	</button>
	<div class="wpmn_sidebar_resize_handle"></div>

	<div class="wpmn_media_sidebar_header">
		<h2><?php echo esc_html__( 'Medianest', 'medianest' ); ?></h2>
		<button type="button" class="button button-primary wpmn_media_sidebar_new_folder">
			<img src="<?php echo esc_url( WPMN_URL . 'assets/img/new-folder.svg'); ?>" alt="" class="wpmn_new_folder_icon" />
			<span class="dashicons dashicons-plus wpmn_new_folders_icon"></span>
			<?php echo esc_html__( 'New Folder', 'medianest' ); ?>
		</button>
	</div>

	<div class="wpmn_media_sidebar_actions">
		<button type="button" class="button wpmn_media_sidebar_action wpmn_media_sidebar_action_rename" disabled>
			<img src="<?php echo esc_url( WPMN_URL . 'assets/img/rename.svg'); ?>" alt="" class="wpmn_rename_icon" />
            <span class="dashicons dashicons-edit wpmn_rename_dashicon"></span>
			<?php echo esc_html__( 'Rename', 'medianest' ); ?>
		</button>
		<button type="button" class="button wpmn_media_sidebar_action wpmn_media_sidebar_action_delete wpmn_delete_trigger" disabled>
			<img src="<?php echo esc_url( WPMN_URL . 'assets/img/delete.svg'); ?>" alt="" class="wpmn_delete_icon" />
            <span class="dashicons dashicons-trash wpmn_delete_dashicon"></span>
			<?php echo esc_html__( 'Delete', 'medianest' ); ?>
		</button>
		
		<!-- Bulk Actions -->
		<button type="button" class="button button-secondary wpmn_bulk_cancel_btn" hidden>
			<?php echo esc_html__( 'Cancel', 'medianest' ); ?>
		</button>
		<div class="wpmn_action_wrapper">
			<button type="button" class="button wpmn_media_sidebar_action wpmn_media_sidebar_action_sort">
				<img src="<?php echo esc_url( WPMN_URL . 'assets/img/sort.svg'); ?>" alt="" class="wpmn_sort_icon" />
			</button>
			<div class="wpmn_sort_menu" hidden>
				<ul class="wpmn_sort_menu_list">
					<?php echo wp_kses_post( apply_filters( 'wpmn_sort_menu_item', '', array() ) ); ?>
					<li class="wpmn_sort_menu_item has-submenu">
						<span><?php echo esc_html__( 'Count', 'medianest' ); ?></span>
						<span class="dashicons dashicons-arrow-right-alt2"></span>
						<ul class="wpmn_sort_menu_submenu">
							<li class="wpmn_count_mode_item is-active" data-mode="folder_only">
								<span class="dashicons dashicons-yes wpmn_check_icon"></span>
								<?php echo esc_html__( 'Count files in each folder', 'medianest' ); ?>
							</li>
							<?php echo wp_kses_post( apply_filters( 'wpmn_sort_sub_menu_item', '', array() ) ); ?>
						</ul>
					</li>
				</ul>
			</div>
		</div>

		<div class="wpmn_action_wrapper">
			<button type="button" class="button wpmn_media_sidebar_action wpmn_media_sidebar_action_more">
				<img src="<?php echo esc_url( WPMN_URL . 'assets/img/others.svg'); ?>" alt="" class="wpmn_more_icon" />
			</button>
			<div class="wpmn_more_menu" hidden>
				<ul class="wpmn_more_menu_list">
					<li class="wpmn_more_menu_item" data-action="bulk-select">
						<span class="dashicons dashicons-yes-alt"></span>
						<span><?php echo esc_html__( 'Bulk Select', 'medianest' ); ?></span>
					</li>
					<?php echo wp_kses_post( apply_filters( 'wpmn_collapsed_menu_item', '', array() ) ); ?>
					<li class="wpmn_more_menu_item" data-action="hide-folder-id"
						data-text-hide="<?php echo esc_attr__( 'Hide folder ID', 'medianest' ); ?>"
						data-text-show="<?php echo esc_attr__( 'Show Folder ID', 'medianest' ); ?>"
						data-icon-hide="dashicons-hidden"
						data-icon-show="dashicons-visibility">
						<span class="dashicons dashicons-visibility"></span>
						<span><?php echo esc_html__( 'Show Folder ID', 'medianest' ); ?></span>
					</li>
					<hr>
					<li class="wpmn_more_menu_item" data-action="settings">
						<span class="dashicons dashicons-admin-generic"></span>
						<span><?php echo esc_html__( 'Settings', 'medianest' ); ?></span>
					</li>
				</ul>
			</div>
		</div>
	</div>

	<div class="wpmn_media_sidebar_folders">
		<?php $wpmn_labels = WPMN_Helper::wpmn_get_folder_labels(); ?>
		<div class="wpmn_media_sidebar_section">
			<button type="button" class="wpmn_folder_button" data-folder-slug="all">
				<img src="<?php echo esc_url( WPMN_URL . 'assets/img/all-files.svg'); ?>" alt="" class="wpmn_all_files_icon" />
				<span><?php echo esc_html( $wpmn_labels['all'], 'medianest' ); ?></span>
				<span class="wpmn_count wpmn_count_all" data-count="all">0</span>
			</button>

			<button type="button" class="wpmn_folder_button" data-folder-slug="uncategorized">
				<img src="<?php echo esc_url( WPMN_URL . 'assets/img/uncategorized.svg'); ?>" alt="" class="wpmn_uncategorized_icon" />
				<span><?php echo esc_html( $wpmn_labels['uncategorized'], 'medianest' ); ?></span>
				<span class="wpmn_count wpmn_count_uncategorized" data-count="uncategorized">0</span>
			</button>
		</div>

		<div class="wpmn_media_sidebar_search_wrap">
			<img src="<?php echo esc_url( WPMN_URL . 'assets/img/search.svg'); ?>" alt="" class="wpmn_search_icon" />
			<input type="search" id="wpmn_folder_search" class="wpmn_media_sidebar_search" placeholder="<?php echo esc_html__( 'Enter folder name…', 'medianest' ); ?>">
		</div>

		<div class="wpmn_new_folder_form" hidden>
			<div class="wpmn_new_folder_form_field">
			<img src="<?php echo esc_url( WPMN_URL . 'assets/img/folder.svg'); ?>" alt="" class="wpmn_folder_icon" />
				<input type="text" class="wpmn_new_folder_input" placeholder="<?php echo esc_html__( 'Enter folder name…', 'medianest' ); ?>" />
			</div>
			<div class="wpmn_new_folder_form_actions">
				<button type="button" class="button button-secondary wpmn_new_folder_cancel">
					<?php echo esc_html__( 'Cancel', 'medianest' ); ?>
				</button>
				<button type="button" class="button button-primary wpmn_new_folder_save">
					<?php echo esc_html__( 'Save', 'medianest' ); ?>
				</button>
			</div>
		</div>

		<div class="wpmn_folder_tree" role="tree">
			<!-- populated by JS -->
		</div>
		<div class="wpmn_tree_loader" hidden>
			<div class="wpmn_spinner"></div>
		</div>
	</div>

	<div class="wpmn_dialog_backdrop" data-delete-dialog hidden>
		<div class="wpmn_dialog">
			<h3 id="wpmn_delete_title" class="wpmn_dialog_title">
				<?php echo esc_html__( 'Delete Folder', 'medianest' ); ?>
			</h3>
			<p class="wpmn_dialog_message"></p>
			<div class="wpmn_dialog_actions">
				<button type="button" class="button button-primary wpmn_delete_confirm">
					<?php echo esc_html__( 'Delete', 'medianest' ); ?>
				</button>
				<button type="button" class="button wpmn_delete_cancel">
					<?php echo esc_html__( 'Cancel', 'medianest' ); ?>
				</button>
			</div>
		</div>
	</div>

	<div class="wpmn_dialog_backdrop" hidden>
		<div class="wpmn_settings_dialog">
			<div class="wpmn_settings_dialog_header">
				<h3 id="wpmn_settings_title" class="wpmn_settings_dialog_title">
					<?php echo esc_html__( 'Medianest Settings', 'medianest' ); ?>
				</h3>
				<button type="button" class="wpmn_settings_dialog_close">
					<span class="dashicons dashicons-no-alt"></span>
				</button>
			</div>
			<div class="wpmn_settings_dialog_body">
				<div class="wpmn_settings_field">
					<label for="wpmn_default_folder"><?php echo esc_html__( 'Choose a default startup folder', 'medianest' ); ?></label>
					<select id="wpmn_default_folder" class="wpmn_settings_select">
						<option value="all"><?php echo esc_html__( 'All Files', 'medianest' ); ?></option>
						<option value="uncategorized"><?php echo esc_html__( 'Uncategorized', 'medianest' ); ?></option>
					</select>
				</div>
				<?php
				$wpmn_allowed = wp_kses_allowed_html( 'post' );
				$wpmn_allowed['label']  = array( 'for' => true );
				$wpmn_allowed['select'] = array( 'id' => true, 'class' => true );
				$wpmn_allowed['option'] = array( 'value' => true, 'selected' => true );
				echo wp_kses( apply_filters( 'wpmn_default_folder', '', array() ), $wpmn_allowed );
				?>
				<div class="wpmn_settings_field">
					<label><?php echo esc_html__( 'Choose Theme', 'medianest' ); ?></label>
					<div class="wpmn_settings_theme_buttons">
						<button type="button" class="wpmn_theme_btn" data-theme="default">
							<?php echo esc_html__( 'Default', 'medianest' ); ?>
						</button>
						<?php echo wp_kses_post( apply_filters( 'wpmn_theme_buttons', '', array() ) ); ?>
					</div>
				</div>
			</div>
			<div class="wpmn_settings_dialog_footer">
				<button type="button" class="button wpmn_settings_dialog_cancel">
					<?php echo esc_html__( 'Cancel', 'medianest' ); ?>
				</button>
				<button type="button" class="button button-primary wpmn_settings_dialog_save">
					<?php echo esc_html__( 'Save', 'medianest' ); ?>
				</button>
			</div>
		</div>
	</div>

	<!-- Folder Context Menu -->
	<div class="wpmn_folder_context_menu" data-folder-id="" hidden>
		<div class="wpmn_context_menu_item" data-action="new_folder">
			<img src="<?php echo esc_url( WPMN_URL . 'assets/img/new-folder.svg'); ?>" alt="" class="wpmn_folder_content" />
			<span><?php echo esc_html__( 'New Folder', 'medianest' ); ?></span>
		</div><hr>
		<div class="wpmn_context_menu_item" data-action="rename">
			<img src="<?php echo esc_url( WPMN_URL . 'assets/img/rename.svg'); ?>" alt="" class="wpmn_folder_content_rename" />
			<span><?php echo esc_html__( 'Rename', 'medianest' ); ?></span>
		</div>
		<div class="wpmn_context_menu_item" data-action="cut">
			<img src="<?php echo esc_url( WPMN_URL . 'assets/img/cut.svg'); ?>" alt="" class="wpmn_folder_content_cut" />
			<span><?php echo esc_html__( 'Cut', 'medianest' ); ?></span>
		</div>
		<div class="wpmn_context_menu_item" data-action="paste">
			<img src="<?php echo esc_url( WPMN_URL . 'assets/img/paste.svg'); ?>" alt="" class="wpmn_folder_content_paste" />
			<span><?php echo esc_html__( 'Paste', 'medianest' ); ?></span>
		</div>
		<div class="wpmn_context_menu_item" data-action="delete">
			<img src="<?php echo esc_url( WPMN_URL . 'assets/img/delete.svg'); ?>" alt="" class="wpmn_folder_content_delete" />
			<span><?php echo esc_html__( 'Delete', 'medianest' ); ?></span>
		</div>
		<?php echo wp_kses_post( apply_filters( 'wpmn_folder_context_menu', '', array() ) ); ?>
	</div>
</div>

