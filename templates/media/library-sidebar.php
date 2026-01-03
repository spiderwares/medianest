<?php
/**
 * Media Library Sidebar Template.
 *
 * @package Media Directory
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<div id="mddr_media_sidebar" class="mddr_media_sidebar">
	<button type="button" class="mddr_media_sidebar_toggle">
		<span class="dashicons dashicons-arrow-left-alt2"></span>
	</button>
	<div class="mddr_sidebar_resize_handle"></div>

	<div class="mddr_media_sidebar_header">
		<h2><?php echo esc_html__( 'Media Directory', 'media-directory' ); ?></h2>
		<button type="button" class="button button-primary mddr_media_sidebar_new_folder">
			<img src="<?php echo esc_url( MDDR_URL . 'assets/img/new-folder.svg'); ?>" alt="" class="mddr_new_folder_icon" />
			<span class="dashicons dashicons-plus mddr_new_folders_icon"></span>
			<?php echo esc_html__( 'New Folder', 'media-directory' ); ?>
		</button>
	</div>

	<div class="mddr_media_sidebar_actions">
		<button type="button" class="button mddr_media_sidebar_action mddr_media_sidebar_action_rename" disabled>
			<img src="<?php echo esc_url( MDDR_URL . 'assets/img/rename.svg'); ?>" alt="" class="mddr_rename_icon" />
            <span class="dashicons dashicons-edit mddr_rename_dashicon"></span>
			<?php echo esc_html__( 'Rename', 'media-directory' ); ?>
		</button>
		<button type="button" class="button mddr_media_sidebar_action mddr_media_sidebar_action_delete mddr_delete_trigger" disabled>
			<img src="<?php echo esc_url( MDDR_URL . 'assets/img/delete.svg'); ?>" alt="" class="mddr_delete_icon" />
            <span class="dashicons dashicons-trash mddr_delete_dashicon"></span>
			<?php echo esc_html__( 'Delete', 'media-directory' ); ?>
		</button>
		
		<!-- Bulk Actions -->
		<button type="button" class="button button-secondary mddr_bulk_cancel_btn" hidden>
			<?php echo esc_html__( 'Cancel', 'media-directory' ); ?>
		</button>
		<div class="mddr_action_wrapper">
			<button type="button" class="button mddr_media_sidebar_action mddr_media_sidebar_action_sort">
				<img src="<?php echo esc_url( MDDR_URL . 'assets/img/sort.svg'); ?>" alt="" class="mddr_sort_icon" />
			</button>
			<div class="mddr_sort_menu" hidden>
				<ul class="mddr_sort_menu_list">
					<?php echo wp_kses_post( apply_filters( 'mddr_sort_menu_item', '', array() ) ); ?>
					<li class="mddr_sort_menu_item has-submenu">
						<span><?php echo esc_html__( 'Count', 'media-directory' ); ?></span>
						<span class="dashicons dashicons-arrow-right-alt2"></span>
						<ul class="mddr_sort_menu_submenu">
							<li class="mddr_count_mode_item is-active" data-mode="folder_only">
								<span class="dashicons dashicons-yes mddr_check_icon"></span>
								<?php echo esc_html__( 'Count files in each folder', 'media-directory' ); ?>
							</li>
							<?php echo wp_kses_post( apply_filters( 'mddr_sort_sub_menu_item', '', array() ) ); ?>
						</ul>
					</li>
				</ul>
			</div>
		</div>

		<div class="mddr_action_wrapper">
			<button type="button" class="button mddr_media_sidebar_action mddr_media_sidebar_action_more">
				<img src="<?php echo esc_url( MDDR_URL . 'assets/img/others.svg'); ?>" alt="" class="mddr_more_icon" />
			</button>
			<div class="mddr_more_menu" hidden>
				<ul class="mddr_more_menu_list">
					<li class="mddr_more_menu_item" data-action="bulk-select">
						<span class="dashicons dashicons-yes-alt"></span>
						<span><?php echo esc_html__( 'Bulk Select', 'media-directory' ); ?></span>
					</li>
					<?php echo wp_kses_post( apply_filters( 'mddr_collapsed_menu_item', '', array() ) ); ?>
					<li class="mddr_more_menu_item" data-action="hide-folder-id"
						data-text-hide="<?php echo esc_attr__( 'Hide folder ID', 'media-directory' ); ?>"
						data-text-show="<?php echo esc_attr__( 'Show Folder ID', 'media-directory' ); ?>"
						data-icon-hide="dashicons-hidden"
						data-icon-show="dashicons-visibility">
						<span class="dashicons dashicons-visibility"></span>
						<span><?php echo esc_html__( 'Show Folder ID', 'media-directory' ); ?></span>
					</li>
					<hr>
					<li class="mddr_more_menu_item" data-action="settings">
						<span class="dashicons dashicons-admin-generic"></span>
						<span><?php echo esc_html__( 'Settings', 'media-directory' ); ?></span>
					</li>
				</ul>
			</div>
		</div>
	</div>

	<div class="mddr_media_sidebar_folders">
		<div class="mddr_media_sidebar_section">
			<button type="button" class="mddr_folder_button" data-folder-slug="all">
				<img src="<?php echo esc_url( MDDR_URL . 'assets/img/all-files.svg'); ?>" alt="" class="mddr_all_files_icon" />
				<span><?php echo esc_html( $mddr_labels['all'], 'media-directory' ); ?></span>
				<span class="mddr_count mddr_count_all" data-count="all">0</span>
			</button>

			<button type="button" class="mddr_folder_button" data-folder-slug="uncategorized">
				<img src="<?php echo esc_url( MDDR_URL . 'assets/img/uncategorized.svg'); ?>" alt="" class="mddr_uncategorized_icon" />
				<span><?php echo esc_html( $mddr_labels['uncategorized'], 'media-directory' ); ?></span>
				<span class="mddr_count mddr_count_uncategorized" data-count="uncategorized">0</span>
			</button>
		</div>

		<div class="mddr_media_sidebar_search_wrap">
			<img src="<?php echo esc_url( MDDR_URL . 'assets/img/search.svg'); ?>" alt="" class="mddr_search_icon" />
			<input type="search" id="mddr_folder_search" class="mddr_media_sidebar_search" placeholder="<?php echo esc_html__( 'Enter folder name…', 'media-directory' ); ?>">
		</div>

		<div class="mddr_new_folder_form" hidden>
			<div class="mddr_new_folder_form_field">
			<img src="<?php echo esc_url( MDDR_URL . 'assets/img/folder.svg'); ?>" alt="" class="mddr_folder_icon" />
				<input type="text" class="mddr_new_folder_input" placeholder="<?php echo esc_html__( 'Enter folder name…', 'media-directory' ); ?>" />
			</div>
			<div class="mddr_new_folder_form_actions">
				<button type="button" class="button button-secondary mddr_new_folder_cancel">
					<?php echo esc_html__( 'Cancel', 'media-directory' ); ?>
				</button>
				<button type="button" class="button button-primary mddr_new_folder_save">
					<?php echo esc_html__( 'Save', 'media-directory' ); ?>
				</button>
			</div>
		</div>

		<div class="mddr_folder_tree" role="tree"></div>
		<div class="mddr_tree_loader" hidden>
			<div class="mddr_spinner"></div>
		</div>
	</div>

	<div class="mddr_dialog_backdrop" data-delete-dialog hidden>
		<div class="mddr_dialog">
			<h3 id="mddr_delete_title" class="mddr_dialog_title">
				<?php echo esc_html__( 'Delete Folder', 'media-directory' ); ?>
			</h3>
			<p class="mddr_dialog_message"></p>
			<div class="mddr_dialog_actions">
				<button type="button" class="button button-primary mddr_delete_confirm">
					<?php echo esc_html__( 'Delete', 'media-directory' ); ?>
				</button>
				<button type="button" class="button mddr_delete_cancel">
					<?php echo esc_html__( 'Cancel', 'media-directory' ); ?>
				</button>
			</div>
		</div>
	</div>

	<div class="mddr_dialog_backdrop" hidden>
		<div class="mddr_settings_dialog">
			<div class="mddr_settings_dialog_header">
				<h3 id="mddr_settings_title" class="mddr_settings_dialog_title">
					<?php echo esc_html__( 'Media Directory Settings', 'media-directory' ); ?>
				</h3>
				<button type="button" class="mddr_settings_dialog_close">
					<span class="dashicons dashicons-no-alt"></span>
				</button>
			</div>
			<div class="mddr_settings_dialog_body">
				<div class="mddr_settings_field">
					<label for="mddr_default_folder"><?php echo esc_html__( 'Choose a default startup folder', 'media-directory' ); ?></label>
					<select id="mddr_default_folder" class="mddr_settings_select">
						<option value="all"><?php echo esc_html__( 'All Files', 'media-directory' ); ?></option>
						<option value="uncategorized"><?php echo esc_html__( 'Uncategorized', 'media-directory' ); ?></option>
					</select>
				</div>
				<?php
				$mddr_allowed = wp_kses_allowed_html( 'post' );
				$mddr_allowed['label']  = array( 'for' => true );
				$mddr_allowed['select'] = array( 'id' => true, 'class' => true );
				$mddr_allowed['option'] = array( 'value' => true, 'selected' => true );
				echo wp_kses( apply_filters( 'mddr_default_folder', '', array() ), $mddr_allowed );
				?>
				<div class="mddr_settings_field">
					<label><?php echo esc_html__( 'Choose Theme', 'media-directory' ); ?></label>
					<div class="mddr_settings_theme_buttons">
						<button type="button" class="mddr_theme_btn" data-theme="default">
							<?php echo esc_html__( 'Default', 'media-directory' ); ?>
						</button>
						<?php echo wp_kses_post( apply_filters( 'mddr_theme_buttons', '', array() ) ); ?>
					</div>
				</div>
			</div>
			<div class="mddr_settings_dialog_footer">
				<button type="button" class="button mddr_settings_dialog_cancel">
					<?php echo esc_html__( 'Cancel', 'media-directory' ); ?>
				</button>
				<button type="button" class="button button-primary mddr_settings_dialog_save">
					<?php echo esc_html__( 'Save', 'media-directory' ); ?>
				</button>
			</div>
		</div>
	</div>

	<!-- Folder Context Menu -->
	<div class="mddr_folder_context_menu" data-folder-id="" hidden>
		<div class="mddr_context_menu_item" data-action="new_folder">
			<img src="<?php echo esc_url( MDDR_URL . 'assets/img/new-folder.svg'); ?>" alt="" class="mddr_folder_content" />
			<span><?php echo esc_html__( 'New Folder', 'media-directory' ); ?></span>
		</div><hr>
		<div class="mddr_context_menu_item" data-action="rename">
			<img src="<?php echo esc_url( MDDR_URL . 'assets/img/rename.svg'); ?>" alt="" class="mddr_folder_content_rename" />
			<span><?php echo esc_html__( 'Rename', 'media-directory' ); ?></span>
		</div>
		<div class="mddr_context_menu_item" data-action="cut">
			<img src="<?php echo esc_url( MDDR_URL . 'assets/img/cut.svg'); ?>" alt="" class="mddr_folder_content_cut" />
			<span><?php echo esc_html__( 'Cut', 'media-directory' ); ?></span>
		</div>
		<div class="mddr_context_menu_item" data-action="paste">
			<img src="<?php echo esc_url( MDDR_URL . 'assets/img/paste.svg'); ?>" alt="" class="mddr_folder_content_paste" />
			<span><?php echo esc_html__( 'Paste', 'media-directory' ); ?></span>
		</div>
		<div class="mddr_context_menu_item" data-action="delete">
			<img src="<?php echo esc_url( MDDR_URL . 'assets/img/delete.svg'); ?>" alt="" class="mddr_folder_content_delete" />
			<span><?php echo esc_html__( 'Delete', 'media-directory' ); ?></span>
		</div>
		<?php echo wp_kses_post( apply_filters( 'mddr_folder_context_menu', '', array() ) ); ?>
	</div>
</div>

