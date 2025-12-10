'use strict';

jQuery(function ($) {
	class WPMN_Media_Admin {

		constructor() {
			this.init();
		}

		init() {
			this.initDefaults();
			this.injectSidebarLayout();
			this.bindEvents();
			this.fetchFolders();
			this.dragAndDropRefresh();

			if (this.state.activeFolder && this.state.activeFolder !== 'all') {
				setTimeout(() => this.triggerMediaFilter(this.state.activeFolder), 500);
			}

			this.updateCustomToolbar();
		}

		updateCustomToolbar() {
			const toolbar = $('.media-toolbar.wp-filter');
			let container = $('.wpmn_custom_class');

			// Check visibility conditions
			const settings = (() => {
				try { return JSON.parse(localStorage.getItem('wpmnSettings')) || {}; }
				catch { return {}; }
			})();
			const show = settings.showBreadcrumb ?? true;

			if (!show || !this.settings.showBreadcrumb) {
				container.remove();
				return;
			}

			if (!container.length) {
				if (!toolbar.length) return;
				container = $('<div class="wpmn_custom_class"></div>').insertAfter(toolbar);
			}

			container.empty();
			$('<span class="dashicons dashicons-admin-home"></span>').on('click', () => this.changeFolder('all')).appendTo(container);

			let path = [];
			if (this.state.activeFolder?.startsWith('term-')) {
				const id = Number(this.state.activeFolder.replace('term-', ''));
				path = this.getFolderPath(id, this.state.folders) || [];
			}

			path.forEach((folder, i) => {
				container.append('<span class="wpmn_path_separator">/</span>');
				const isLast = i === path.length - 1;
				const el = $('<span>').addClass(isLast ? 'wpmn_path_folder' : 'wpmn_path_folders').text(folder.name);

				if (!isLast) {
					el.on('click', () => this.changeFolder('term-' + folder.id));
				}

				container.append(el);
			});
		}

		initDefaults() {
			this.settings = window.wpmnMediaLibrary || {};
			let savedFolder = 'all';
			try {
				const localSettings = JSON.parse(localStorage.getItem('wpmnSettings') || '{}');
				if (localSettings.defaultFolder) {
					savedFolder = localSettings.defaultFolder;
				} else {
					savedFolder = localStorage.getItem('wpmnActiveFolder') || 'all';
				}
			} catch (e) {
				savedFolder = localStorage.getItem('wpmnActiveFolder') || 'all';
			}

			this.showFolderId = false;
			try {
				this.showFolderId = localStorage.getItem('wpmnShowFolderId') === '1';
			} catch (e) {
				this.showFolderId = false;
			}

			this.state = {
				activeFolder: savedFolder,
				folders: [],
				counts: {
					all: 0,
					uncategorized: 0,
				},
				searchTerm: '',
			};
			this.sidebar = $('#wpmn_media_sidebar');
			this.pendingDeleteId = null;
			this.toastTimeout = null;
		}

		// ===== Event Binding =====
		bindEvents() {
			$(document.body).on('click', '.wpmn_folder_button', this.handleFolderClick.bind(this));
			$(document.body).on('input', '.wpmn_media_sidebar_search', this.handleSearch.bind(this));
			$(document.body).on('click', '.wpmn_media_sidebar_new_folder', this.toggleNewFolderForm.bind(this, true));
			$(document.body).on('click', '.wpmn_new_folder_cancel', this.toggleNewFolderForm.bind(this, false));
			$(document.body).on('click', '.wpmn_new_folder_save', this.handleCreateFolder.bind(this));
			$(document.body).on('keydown', '.wpmn_new_folder_input', this.handleNewFolderKeydown.bind(this));
			$(document.body).on('click', '.wpmn_media_sidebar_action_rename', this.handleRenameFolder.bind(this));
			$(document.body).on('click', '.wpmn_rename_inline_save', this.handleInlineRenameSave.bind(this));
			$(document.body).on('click', '.wpmn_rename_inline_cancel', this.cancelInlineRename.bind(this));
			$(document.body).on('keydown', '.wpmn_rename_inline_input', this.handleRenameKeydown.bind(this));
			$(document.body).on('click', '.wpmn_media_sidebar_action_delete', this.handleDeleteFolder.bind(this));
			$(document.body).on('click', '.wpmn_delete_confirm', this.confirmDeleteFolder.bind(this));
			$(document.body).on('click', '.wpmn_delete_cancel', this.closeDeleteDialog.bind(this));
			$(document.body).on('click', '.wpmn_media_sidebar_toggle', this.handleSidebarToggle.bind(this));
			$(document.body).on('click', '.wpmn_toggle_arrow', this.handleToggleClick.bind(this));
			$(document.body).on('click', '.wpmn_media_sidebar_action_sort', this.handleSortClick.bind(this));
			$(document.body).on('click', '.wpmn_media_sidebar_action--more', this.handleMoreClick.bind(this));
			$(document.body).on('click', '.wpmn_more_menu_item[data-action="settings"]', this.openSettingsDialog.bind(this));
			$(document.body).on('click', '.wpmn_more_menu_item[data-action="hide-folder-id"]', this.toggleFolderId.bind(this));
			$(document.body).on('click', '.wpmn_settings_dialog__close', this.closeSettingsDialog.bind(this));
			$(document.body).on('click', '.wpmn_settings_dialog__cancel', this.closeSettingsDialog.bind(this));
			$(document.body).on('click', '.wpmn_settings_dialog__save', this.saveSettings.bind(this));
			$(document.body).on('click', '.wpmn_theme_btn', this.handleThemeClick.bind(this));
			$(document.body).on('click', '.wpmn_clear_data_btn', this.handleClearData.bind(this));
			$(document).on('click', (event) => {
				if (!$(event.target).closest('.wpmn_media_sidebar_action_sort, .wpmn_sort_menu').length) {
					this.sidebar.find('.wpmn_sort_menu').prop('hidden', true);
				}
				if (!$(event.target).closest('.wpmn_media_sidebar_action--more, .wpmn_more_menu').length) {
					this.sidebar.find('.wpmn_more_menu').prop('hidden', true);
				}
			});
		}

		// ===== Keyboard Handlers =====
		handleNewFolderKeydown(event) {
			if ('Enter' === event.key) this.handleCreateFolder();
			if ('Escape' === event.key) this.toggleNewFolderForm(false);
		}

		handleRenameKeydown(event) {
			if ('Enter' === event.key) this.handleInlineRenameSave(event);
			if ('Escape' === event.key) this.cancelInlineRename(event);
		}

		// ===== Menu Handlers =====
		handleSidebarToggle(event) {
			event.preventDefault();
			this.toggleSidebar();
		}

		handleToggleClick(event) {
			event.preventDefault();
			event.stopPropagation();
			const arrow = $(event.currentTarget),
				li = arrow.closest('.wpmn_folder_node'),
				children = li.children('ul');

			if (children.length) {
				const isExpanded = li.attr('aria-expanded') === 'true';
				li.attr('aria-expanded', !isExpanded);
				children.slideToggle(200);
			}
		}

		handleSortClick(event) {
			this.handleMenuToggle(event, '.wpmn_sort_menu', '.wpmn_more_menu');
		}

		handleMoreClick(event) {
			this.handleMenuToggle(event, '.wpmn_more_menu', '.wpmn_sort_menu');
		}

		handleMenuToggle(event, targetMenuClass, otherMenuClass) {
			event.preventDefault();
			event.stopPropagation();

			const menu = this.sidebar.find(targetMenuClass);
			this.sidebar.find(otherMenuClass).prop('hidden', true);
			menu.prop('hidden', !menu.prop('hidden'));

			if (!menu.prop('hidden') && targetMenuClass === '.wpmn_sort_menu') {
			}
		}

		handleSearch(event) {
			this.state.searchTerm = $(event.currentTarget).val().toLowerCase();
			this.renderTree();
			this.setupDroppableTargets();
		}

		// ===== Settings Dialog =====
		openSettingsDialog(event) {
			event.preventDefault();
			const dialog = $('.wpmn_dialog_backdrop');
			this.sidebar.find('.wpmn_more_menu').prop('hidden', true);
			this.loadSettings();
			dialog.prop('hidden', false);
			setTimeout(() => dialog.addClass('is-visible'), 10);
			$(document).on('keydown.wpmnSettings', (e) => {
				if ('Escape' === e.key) {
					e.preventDefault();
					this.closeSettingsDialog();
				}
			});
		}

		closeSettingsDialog() {
			const dialog = $('.wpmn_dialog_backdrop');
			dialog.removeClass('is-visible').prop('hidden', true);
			$(document).off('keydown.wpmnSettings');
		}

		loadSettings() {
			const settings = JSON.parse(localStorage.getItem('wpmnSettings') || '{}');
			const select = $('#wpmn_default_folder');
			select.empty();

			select.append($('<option></option>').val('all').text('All Files'));
			select.append($('<option></option>').val('uncategorized').text('Uncategorized'));

			// Dynamic options
			const addOptions = (nodes, depth = 0) => {
				if (!nodes) return;
				nodes.forEach(node => {
					let prefix = '';
					for (let i = 0; i < depth; i++) prefix += '\u00A0\u00A0';
					if (depth > 0) prefix += '-';

					select.append($('<option></option>').val('term-' + node.id).text(prefix + node.name));

					if (node.children && node.children.length) {
						addOptions(node.children, depth + 1);
					}
				});
			};

			if (this.state.folders) {
				addOptions(this.state.folders);
			}

			select.val(settings.defaultFolder || 'all');
			$('#wpmn_show_breadcrumb').prop('checked', !!settings.showBreadcrumb);
			if (settings.theme) {
				$('.wpmn_theme_btn').removeClass('wpmn_theme_btn--active');
				$('.wpmn_theme_btn[data-theme="' + settings.theme + '"]').addClass('wpmn_theme_btn--active');
			}
		}

		saveSettings() {
			const defaultFolder = $('#wpmn_default_folder').val(),
				showBreadcrumb = $('#wpmn_show_breadcrumb').is(':checked'),
				selectedTheme = $('.wpmn_theme_btn.wpmn_theme_btn--active').data('theme'),
				settings = { defaultFolder, showBreadcrumb, theme: selectedTheme };

			localStorage.setItem('wpmnSettings', JSON.stringify(settings));
			this.closeSettingsDialog();
			this.renderSidebar();
			this.showToast('Settings saved successfully!');
		}

		// ===== Theme Management =====
		handleThemeClick(event) {
			const __this = $(event.currentTarget),
				selectedTheme = __this.data('theme');

			if (selectedTheme !== 'default') return;

			$('.wpmn_theme_btn').removeClass('wpmn_theme_btn--active');
			__this.addClass('wpmn_theme_btn--active');

			const defaultFolder = $('#wpmn_default_folder').val(),
				showBreadcrumb = $('#wpmn_show_breadcrumb').is(':checked'),
				settings = { defaultFolder, showBreadcrumb, theme: 'default' };

			localStorage.setItem('wpmnSettings', JSON.stringify(settings));
		}

		// ===== Folder Management =====
		handleFolderClick(event) {
			const slug = $(event.currentTarget).data('folder-slug');
			if (slug) this.changeFolder(slug);
		}

		changeFolder(slug) {
			if (slug === this.state.activeFolder) return;
			this.state.activeFolder = slug;
			try {
				localStorage.setItem('wpmnActiveFolder', slug);
			} catch (e) { }

			this.highlightActive();
			this.setupDroppableTargets();
			this.updateActionButtons();
			this.updateCustomToolbar();
			this.triggerMediaFilter(slug)
		}

		updateActionButtons() {
			const renameBtn = this.sidebar.find('.wpmn_media_sidebar_action_rename'),
				deleteBtn = this.sidebar.find('.wpmn_media_sidebar_action_delete'),
				isSpecialFolder = this.state.activeFolder === 'all' || this.state.activeFolder === 'uncategorized';

			renameBtn.prop('disabled', isSpecialFolder).toggleClass('disabled', isSpecialFolder);
			deleteBtn.prop('disabled', isSpecialFolder).toggleClass('disabled', isSpecialFolder);
		}

		// ===== Folder Creation =====
		toggleNewFolderForm(show) {
			const form = this.sidebar.find('.wpmn_new_folder_form'),
				input = this.sidebar.find('.wpmn_new_folder_input');

			if (show) {
				form.prop('hidden', false);
				input.val('').focus();
			} else {
				form.prop('hidden', true);
				input.val('');
			}
		}

		handleCreateFolder() {
			const input = this.sidebar.find('.wpmn_new_folder_input'),
				name = input.val().trim();

			if (!name) {
				input.focus();
				return;
			}

			$.ajax({
				type: 'POST',
				url: this.settings.ajaxUrl,
				data: {
					action: 'wpmn_ajax',
					request_type: 'create_folder',
					name: name,
					parent: this.state.activeFolder.indexOf('term-') === 0 ? this.state.activeFolder.replace('term-', '') : 0,
					nonce: this.settings.nonce
				},
				success: (response) => {
					if (response && response.success) {
						this.refreshStateFromResponse(response.data);
						this.toggleNewFolderForm(false);
						this.showToast(this.settings.wpmn_folder ? this.settings.wpmn_folder.created : 'Folder created.');
					} else {
						const message = (response && response.data && response.data.message) || ((this.settings.wpmn_folder && this.settings.wpmn_folder.errorGeneric) || 'An error occurred.');
						alert(message);
					}
				}
			});
		}

		// ===== Folder Renaming =====
		handleRenameFolder() {
			if (!this.state.activeFolder.startsWith('term-')) {
				alert((this.settings.wpmn_folder && this.settings.wpmn_folder.selectFolderFirst) || 'Select a folder first.');
				return;
			}
			const folderId = parseInt(this.state.activeFolder.replace('term-', ''), 10),
				folder = this.findFolderById(folderId, this.state.folders);
			if (folder) this.startInlineRename(folder);
		}

		startInlineRename(folder) {
			const selector = '.wpmn_folder_button[data-folder-slug="' + this.state.activeFolder + '"]',
				button = this.sidebar.find(selector);
			if (!button.length) return;

			this.sidebar.find('.wpmn_folder_rename_inline').each((index, element) => {
				const form = $(element),
					originalButton = form.data('originalButton');
				if (originalButton && originalButton.length) originalButton.show();
				form.remove();
			});

			const li = button.closest('.wpmn_folder_node'),
				form = $('<div class="wpmn_folder_rename_inline"></div>').attr('data-folder-id', folder.id),
				folderIconUrl = (this.settings.baseUrl || '') + 'assets/img/folder.svg',
				icon = $('<img />').attr('src', folderIconUrl).attr('alt', '').addClass('wpmn_folder_icon').attr('aria-hidden', 'true'),
				input = $('<input type="text" class="wpmn_rename_inline_input" />').val(folder.name),
				actions = $('<div class="wpmn_folder_rename_inline__actions"></div>'),
				cancel = $('<button type="button" class="button button-secondary wpmn_rename_inline_cancel"></button>').text('Cancel'),
				save = $('<button type="button" class="button button-primary wpmn_rename_inline_save"></button>').text('Save');

			actions.append(cancel, save);
			form.append(icon, input, actions);
			form.data('originalButton', button);
			button.hide();
			li.prepend(form);
			setTimeout(() => input.trigger('focus').select(), 10);
		}

		handleInlineRenameSave(event) {
			const form = $(event.currentTarget).closest('.wpmn_folder_rename_inline');
			if (!form.length) return;

			const folderId = parseInt(form.attr('data-folder-id'), 10),
				input = form.find('.wpmn_rename_inline_input'),
				name = input.val().trim();

			if (!folderId || !name) {
				input.focus();
				return;
			}

			$.ajax({
				type: 'POST',
				url: this.settings.ajaxUrl,
				data: {
					action: 'wpmn_ajax',
					request_type: 'rename_folder',
					folder_id: folderId,
					name: name,
					nonce: this.settings.nonce
				},
				success: (response) => {
					if (response && response.success) {
						this.refreshStateFromResponse(response.data);
						this.showToast(this.settings.wpmn_folder ? this.settings.wpmn_folder.renamed : 'Folder renamed.');
					} else {
						const message = (response && response.data && response.data.message) || ((this.settings.wpmn_folder && this.settings.wpmn_folder.errorGeneric) || 'An error occurred.');
						alert(message);
					}
				},
				complete: () => this.cleanupInlineRename(form)
			});
		}

		cancelInlineRename(event) {
			if (event && event.preventDefault) event.preventDefault();
			const form = $(event.currentTarget).closest('.wpmn_folder_rename_inline');
			if (form.length) this.cleanupInlineRename(form);
		}

		cleanupInlineRename(form) {
			const originalButton = form.data('originalButton');
			if (originalButton && originalButton.length) originalButton.show();
			form.remove();
		}

		// ===== Folder Deletion =====
		handleDeleteFolder() {
			if (!this.state.activeFolder.startsWith('term-')) {
				alert((this.settings.wpmn_folder && this.settings.wpmn_folder.selectFolderFirst) || 'Select a folder first.');
				return;
			}
			const folderId = parseInt(this.state.activeFolder.replace('term-', ''), 10),
				folder = this.findFolderById(folderId, this.state.folders);
			if (folder) this.openDeleteDialog(folder);
		}

		openDeleteDialog(folder) {
			this.pendingDeleteId = folder.id;
			const dialog = this.sidebar.find('[data-delete-dialog]'),
				message = dialog.find('.wpmn_dialog_message'),
				template = (this.settings.wpmn_folder && this.settings.wpmn_folder.deleteConfirm);

			message.text(template.replace('%s', folder.name));
			dialog.prop('hidden', false);
			setTimeout(() => dialog.addClass('is-visible'), 10);
			$(document).on('keydown.wpmnDialog', (event) => {
				if ('Escape' === event.key) {
					event.preventDefault();
					this.closeDeleteDialog();
				}
			});
		}

		closeDeleteDialog() {
			const dialog = this.sidebar.find('[data-delete-dialog]');
			dialog.removeClass('is-visible').prop('hidden', true);
			this.pendingDeleteId = null;
			$(document).off('keydown.wpmnDialog');
		}

		confirmDeleteFolder() {
			if (!this.pendingDeleteId) {
				this.closeDeleteDialog();
				return;
			}

			$.ajax({
				type: 'POST',
				url: this.settings.ajaxUrl,
				data: {
					action: 'wpmn_ajax',
					request_type: 'delete_folder',
					folder_id: this.pendingDeleteId,
					nonce: this.settings.nonce
				},
				success: (response) => {
					if (response && response.success) {
						this.refreshStateFromResponse(response.data);
						this.showToast(this.settings.wpmn_folder ? this.settings.wpmn_folder.deleted : 'Folder deleted.');
					} else {
						const message = (response && response.data && response.data.message) || ((this.settings.wpmn_folder && this.settings.wpmn_folder.errorGeneric) || 'An error occurred.');
						alert(message);
					}
				},
				complete: () => this.closeDeleteDialog()
			});
		}

		// ===== Folder Fetching & Rendering =====
		fetchFolders() {
			$.ajax({
				type: 'POST',
				url: this.settings.ajaxUrl,
				data: {
					action: 'wpmn_ajax',
					request_type: 'get_folders',
					nonce: this.settings.nonce
				},
				success: (response) => {
					if (response && response.success) {
						this.refreshStateFromResponse(response.data);
					} else {
						const message = (response && response.data && response.data.message) || ((this.settings.wpmn_folder && this.settings.wpmn_folder.errorGeneric) || 'An error occurred.');
						alert(message);
					}
				}
			});
		}

		refreshStateFromResponse(data) {
			if (data && data.folders) this.state.folders = data.folders;
			if (data && data.counts) this.state.counts = data.counts;
			if (data && data.activeSlug) this.state.activeFolder = data.activeSlug;

			// Validate active folder
			if (this.state.activeFolder && this.state.activeFolder.toString().indexOf('term-') === 0) {
				const id = parseInt(this.state.activeFolder.replace('term-', ''), 10);
				if (!this.findFolderById(id, this.state.folders)) {
					this.state.activeFolder = 'all';
					try { localStorage.setItem('wpmnActiveFolder', 'all'); } catch (e) { }
				}
			}

			this.renderSidebar();
		}

		renderSidebar() {
			this.updateCounts();
			this.renderTree();
			this.highlightActive();
			this.updateActionButtons();
			this.setupDroppableTargets();
			this.updateFolderIdVisibility();
			this.updateCustomToolbar();
		}

		updateCounts() {
			this.sidebar.find('.wpmn_count_all').text(this.state.counts.all || 0);
			this.sidebar.find('.wpmn_count_uncategorized').text(this.state.counts.uncategorized || 0);
		}

		renderTree() {
			const treeContainer = this.sidebar.find('.wpmn_folder_tree');
			if (!treeContainer.length) return;

			treeContainer.empty();
			let nodes = this.getFilteredTree();

			if (!nodes.length) {
				if (this.state.searchTerm) {
					const emptyText = (this.settings.wpmn_folder && this.settings.wpmn_folder.emptyTree) || 'No folders yet.';
					treeContainer.append($('<p class="wpmn_empty_tree"></p>').text(emptyText));
					return;
				}

				const header = (this.settings.wpmn_folder && this.settings.wpmn_folder.emptyTitle) || 'Create your first folder',
					description = (this.settings.wpmn_folder && this.settings.wpmn_folder.emptyDescription) || 'There are no folders available. Please add a folder to better manage your files.',
					btnLabel = (this.settings.wpmn_folder && this.settings.wpmn_folder.emptyButton) || 'Add Folder',
					empty = $('<div class="wpmn_empty_state"></div>'),
					icon = $('<div class="wpmn_empty_state_icon" aria-hidden="true"></div>').html('&#128194;'),
					title = $('<p class="wpmn_empty_state_title"></p>').text(header),
					desc = $('<p class="wpmn_empty_state_description"></p>').text(description),
					btn = $('<button type="button" class="button button-primary wpmn_add_folder"></button>').text(btnLabel)
						.on('click', (event) => {
							event.preventDefault();
							this.toggleNewFolderForm(true);
						});

				empty.append(icon, title, desc, btn);
				treeContainer.append(empty);
				return;
			}

			treeContainer.append(this.buildTreeList(nodes));
		}

		getFilteredTree() {
			if (!this.state.searchTerm) return this.state.folders;

			const term = this.state.searchTerm;
			const filterNodes = (nodes) => {
				const result = [];
				nodes.forEach((node) => {
					const children = node.children ? filterNodes(node.children) : [],
						nameMatch = node.name.toLowerCase().indexOf(term) !== -1;
					if (nameMatch || children.length) {
						const clone = $.extend(true, {}, node);
						clone.children = children;
						result.push(clone);
					}
				});
				return result;
			};
			return filterNodes(this.state.folders);
		}

		findFolderById(id, nodes) {
			if (!nodes) return null;
			for (const node of nodes) {
				if (parseInt(node.id, 10) === parseInt(id, 10)) return node;
				if (node.children && node.children.length) {
					const found = this.findFolderById(id, node.children);
					if (found) return found;
				}
			}
			return null;
		}

		getFolderPath(id, nodes, path = []) {
			if (!nodes) return null;
			for (const node of nodes) {
				if (parseInt(node.id, 10) === parseInt(id, 10)) {
					return [...path, node];
				}
				if (node.children && node.children.length) {
					const result = this.getFolderPath(id, node.children, [...path, node]);
					if (result) return result;
				}
			}
			return null;
		}

		buildTreeList(nodes) {
			const ul = $('<ul role="group"></ul>');
			nodes.forEach((node) => {
				const slug = 'term-' + node.id,
					li = $('<li class="wpmn_folder_node" role="treeitem"></li>').attr('aria-expanded', (node.children && node.children.length) ? 'true' : 'false'),
					row = $('<div class="wpmn_folder_row"></div>'),
					child = node.children && node.children.length > 0,
					arrow = $('<span class="wpmn_toggle_arrow"></span>');

				if (child) arrow.addClass('has-children');
				row.append(arrow);

				const button = $('<button type="button" class="wpmn_folder_button" />').attr('data-folder-slug', slug).attr('data-folder-type', 'term')
					.attr('data-folder-id', node.id)
					.attr('data-folder-name', node.name),
					folderIconUrl = (this.settings.baseUrl || '') + 'assets/img/folder.svg';
				button.append($('<img />').attr('src', folderIconUrl).attr('alt', '').addClass('wpmn_folder_icon').attr('aria-hidden', 'true'));
				const labelText = (this.showFolderId ? ('#' + node.id + ' ') : '') + node.name;
				button.append($('<span class="wpmn_folder_button__label"></span>').text(labelText));

				let countVal = node.count || 0;

				button.append($('<span class="wpmn_count"></span>').text(countVal));
				row.append(button);
				li.append(row);

				if (node.children && node.children.length) {
					li.append(this.buildTreeList(node.children));
				}

				ul.append(li);
			});

			return ul;
		}

		highlightActive() {
			const __this = this.sidebar.find('.wpmn_folder_button');
			__this.removeClass('is-active').find('.wpmn_folder_icon, .wpmn_all_files_icon, .wpmn_uncategorized_icon').removeClass('is-active');
			const active = __this.filter(`[data-folder-slug="${this.state.activeFolder}"]`).addClass('is-active');
			active.find('.wpmn_folder_icon, .wpmn_all_files_icon, .wpmn_uncategorized_icon').addClass('is-active');
		}

		// ===== Drag & Drop =====
		setupDroppableTargets() {
			const __this = this.sidebar.find('.wpmn_folder_button');
			__this.each((index, element) => {
				const button = $(element);
				if (button.hasClass('ui-droppable')) button.droppable('destroy');

				const slug = button.data('folder-slug');
				if (!slug || (slug !== 'uncategorized' && slug.indexOf('term-') !== 0)) return;

				button.droppable({
					accept: '.attachments .attachment',
					hoverClass: 'is-drop-hover',
					tolerance: 'pointer',
					drop: (event, ui) => {
						let folderId = 0;
						if (slug.indexOf('term-') === 0) {
							folderId = parseInt(slug.replace('term-', ''), 10);
						}

						let ids = this.getSelectedAttachmentIds();
						if (!ids.length && ui.draggable && ui.draggable.length) {
							ids = [parseInt(ui.draggable.data('id'), 10)];
						}

						if (!ids.length) {
							alert((this.settings.wpmn_folder && this.settings.wpmn_folder.noSelection) || 'Select at least one media item.');
							return;
						}

						this.assignMediaToFolder(folderId, ids);
					},
				});
			});
		}

		getSelectedAttachmentIds() {
			const ids = [];
			$('.attachments .attachment.selected').each((index, element) => {
				const id = parseInt($(element).data('id'), 10);
				if (id) ids.push(id);
			});
			return ids;
		}

		assignMediaToFolder(folderId, ids) {
			this.toggleLoader(true);
			$.ajax({
				type: 'POST',
				url: this.settings.ajaxUrl,
				data: {
					action: 'wpmn_ajax',
					request_type: 'assign_media',
					folder_id: folderId,
					attachment_ids: ids,
					nonce: this.settings.nonce
				},
				success: (response) => {
					if (response && response.success) {
						this.refreshStateFromResponse(response.data);
						this.showToast('Item moved successfully.');

						let isSameFolder = false;
						if (this.state.activeFolder === 'uncategorized' && folderId === 0) {
							isSameFolder = true;
						} else if (typeof this.state.activeFolder === 'string' && this.state.activeFolder.indexOf('term-') === 0) {
							const currentId = parseInt(this.state.activeFolder.replace('term-', ''), 10);
							if (currentId === folderId) {
								isSameFolder = true;
							}
						}

						if (this.state.activeFolder !== 'all' && !isSameFolder) {
							if (ids && ids.length) {
								ids.forEach((id) => {
									$('.attachments .attachment[data-id="' + id + '"]').remove();
								});
							}
						}
					} else {
						const message = (response && response.data && response.data.message) || ((this.settings.wpmn_folder && this.settings.wpmn_folder.errorGeneric) || 'An error occurred.');
						alert(message);
					}
				},
				complete: () => {
					this.toggleLoader(false);
				}
			});
		}

		toggleLoader(show) {
			const loader = this.sidebar.find('.wpmn_tree_loader');
			loader.prop('hidden', !show);
		}

		dragAndDropRefresh() {
			if (this._dragRefreshTimer) return;
			this._dragRefreshTimer = setInterval(() => this.makeAttachmentsDraggable(), 500);
		}

		makeAttachmentsDraggable() {
			const attachments = $('.attachments .attachment').not('.wpmn_draggable');
			if (!attachments.length) return;

			attachments
				.addClass('wpmn_draggable')
				.draggable({
					helper: function () {
						const selected = $('.attachments .attachment.selected'),
							count = $(this).hasClass('selected') ? selected.length : 1,
							text = count === 1 ? 'Move 1 item' : 'Move ' + count + ' items';
						return $('<div class="wpmn_drag_helper_pill"></div>').text(text);
					},
					cursor: 'move',
					cursorAt: { left: 20, top: 20 },
					appendTo: 'body',
					zIndex: 99999,
					revert: 'invalid',
				});
		}

		// ===== Layout Management =====
		injectSidebarLayout() {
			const wrap = $('#wpbody-content .wrap');
			if (!this.sidebar.length || !wrap.length || wrap.hasClass('wpmn_media_layout')) return;

			const contentChildren = wrap.children().not(this.sidebar);
			if (!contentChildren.length) return;

			contentChildren.wrapAll('<div class="wpmn_media_content"></div>');
			wrap.prepend(this.sidebar);
			wrap.addClass('wpmn_media_layout');
		}

		toggleSidebar() {
			const wrap = $('#wpbody-content .wrap');
			if (!wrap.length) return;

			wrap.toggleClass('wpmn_media_layout_collapsed');

			localStorage.setItem(
				'wpmnSidebarCollapsed',
				wrap.hasClass('wpmn_media_layout_collapsed') ? '1' : '0'
			);
		}

		// ===== Toast Notifications =====
		showToast(message) {
			if (!message) return;

			if (this.toastTimeout) {
				window.clearTimeout(this.toastTimeout);
				this.toastTimeout = null;
			}

			let toast = $('.wpmn_toast');
			if (!toast.length) {
				toast = $('<div class="wpmn_toast" role="status" aria-live="polite"></div>');
				const icon = $('<span class="wpmn_toast_icon"></span>').html('&#10003;'),
					msg = $('<p class="wpmn_toast_message"></p>');
				toast.append(icon, msg);
				$('body').append(toast);
			}

			toast.find('.wpmn_toast_message').text(message);
			void toast[0].offsetWidth;
			toast.addClass('wpmn_toast_visible');

			this.toastTimeout = window.setTimeout(() => {
				toast.removeClass('wpmn_toast_visible');
			}, 2500);
		}

		toggleFolderId(event) {
			if (event && event.preventDefault) event.preventDefault();

			this.showFolderId = !this.showFolderId;
			localStorage.setItem('wpmnShowFolderId', this.showFolderId ? '1' : '0');
			this.updateFolderIdVisibility();
			this.sidebar.find('.wpmn_more_menu').prop('hidden', true);
		}

		updateFolderIdVisibility() {
			const buttons = this.sidebar.find('.wpmn_folder_button');
			buttons.each((i, el) => {
				const btn = $(el),
					id = btn.attr('data-folder-id'),
					name = btn.attr('data-folder-name') || btn.find('.wpmn_folder_button__label').text().replace(/^#\d+\s+/, '');
				if (id && name !== undefined) {
					const label = btn.find('.wpmn_folder_button__label');
					if (this.showFolderId) {
						label.text('#' + id + ' ' + name);
					} else {
						label.text(name);
					}
				}
			});
		}

		triggerMediaFilter(slug) {
			if (typeof wp === 'undefined' || !wp.media || !wp.media.frame) return;
			const library = wp.media.frame.state().get('library');
			if (library) {
				library.props.set('wpmn_folder', slug);
			}
		}

		handleClearData(e) {
			e.preventDefault();

			const confirmMessage = (this.settings.wpmn_folder && this.settings.wpmn_folder.confirmClearData) || 'Are you sure you want to delete all Medianest data?';
			if (!confirm(confirmMessage)) {
				return;
			}

			const btn = $(e.currentTarget);
			const action = btn.data('action');

			btn.prop('disabled', true);

			$.ajax({
				type: 'POST',
				url: this.settings.ajaxUrl || ajaxurl,
				data: {
					action: 'wpmn_ajax',
					request_type: action,
					nonce: this.settings.nonce
				},
				success: (response) => {
					if (response && response.success) {
						this.showToast('All data cleared successfully.');
						location.reload();
					} else {
						const message = (response && response.data && response.data.message) || 'Error occurred';
						this.showToast('Error: ' + message);
						btn.prop('disabled', false);
					}
				}
			});
		}

	}

	new WPMN_Media_Admin();
});
