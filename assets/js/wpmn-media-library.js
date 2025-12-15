'use strict';

jQuery(function ($) {
    class WPMN_Media_Admin {

        constructor() {
            this.init();
        }

        init() {
            this.defaultSettings();
            this.injectSidebarLayout();
            this.bindEvents();
            this.fetchFolders();
            this.dragAndDropRefresh();
            if (this.state.activeFolder !== 'all') {
                setTimeout(() => this.triggerMediaFilter(this.state.activeFolder), 500);
            }
            this.updateCustomToolbar();
        }

        getStorage(key, defCode) {
            try { return localStorage.getItem(key) || defCode; } catch { return defCode; }
        }

        setStorage(key, val) {
            try { localStorage.setItem(key, val); } catch { }
        }

        getText(key, def) {
            return wpmn_media_library.wpmn_folder?.[key] || def;
        }

        apiCall(request_type, data = {}) {
            const isFormData = data instanceof FormData;
            if (isFormData) {
                data.append('action', 'wpmn_ajax');
                data.append('request_type', request_type);
                data.append('nonce', wpmn_media_library.nonce);
            }

            return new Promise((resolve, reject) => {
                $.ajax({
                    type: 'POST',
                    url: wpmn_media_library.ajaxUrl,
                    data: isFormData ? data : {
                        action: 'wpmn_ajax',
                        request_type,
                        nonce: wpmn_media_library.nonce,
                        ...data
                    },
                    processData: !isFormData,
                    contentType: isFormData ? false : 'application/x-www-form-urlencoded; charset=UTF-8',
                    success: (res) => res.success ? resolve(res.data) : reject(res?.data?.message || this.getText('errorGeneric', 'An error occurred.')),
                    error: () => reject('Network error'),
                });
            });
        }

        defaultSettings() {
            this.settings = window.wpmn_media_library || {};
            const savedSettings = JSON.parse(this.getStorage('wpmnSettings', '{}'));
            this.state = {
                activeFolder: savedSettings.defaultFolder || 'all',
                folders: [],
                counts: { all: 0, uncategorized: 0 },
                searchTerm: '',
                searchResults: []
            };
            this.showFolderId = this.getStorage('wpmnShowFolderId') === '1';
            this.searchDebounce = null;
            this.sidebar = $('#wpmn_media_sidebar');
            this.toastTimeout = null;
            this.isBulkSelect = false;
            this.clipboard = { action: null, folderId: null };
        }

        bindEvents() {
            $(document.body).on('click', '.wpmn_folder_button', this.handleFolderClick.bind(this));
            $(document.body).on('input', '.wpmn_media_sidebar_search', this.handleSearch.bind(this));
            $(document.body).on('click', '.wpmn_media_sidebar_new_folder', () => this.toggleNewFolderForm(true));
            $(document.body).on('click', '.wpmn_new_folder_cancel', () => this.toggleNewFolderForm(false));
            $(document.body).on('click', '.wpmn_new_folder_save', this.handleCreateFolder.bind(this));
            $(document.body).on('keydown', '.wpmn_new_folder_input', (e) => {
                if (e.key === 'Enter') this.handleCreateFolder();
                if (e.key === 'Escape') this.toggleNewFolderForm(false);
            });
            $(document.body).on('click', '.wpmn_media_sidebar_action_rename', this.handleRenameFolder.bind(this));
            $(document.body).on('click', '.wpmn_rename_inline_save', this.handleInlineRenameSave.bind(this));
            $(document.body).on('click', '.wpmn_rename_inline_cancel', this.cancelInlineRename.bind(this));
            $(document.body).on('keydown', '.wpmn_rename_inline_input', (e) => {
                if (e.key === 'Enter') this.handleInlineRenameSave(e);
                if (e.key === 'Escape') this.cancelInlineRename(e);
            });
            $(document.body).on('click', '.wpmn_delete_confirm', this.confirmDeleteFolder.bind(this));
            $(document.body).on('click', '.wpmn_delete_cancel', this.closeDeleteDialog.bind(this));
            $(document.body).on('click', '.wpmn_media_sidebar_toggle', (e) => { e.preventDefault(); this.toggleSidebar(); });
            $(document.body).on('click', '.wpmn_toggle_arrow', this.handleToggleClick.bind(this));
            $(document.body).on('click', '.wpmn_media_sidebar_action_sort', (e) => this.handleMenuToggle(e, '.wpmn_sort_menu'));
            $(document.body).on('click', '.wpmn_media_sidebar_action--more', (e) => this.handleMenuToggle(e, '.wpmn_more_menu'));
            $(document.body).on('click', '.wpmn_more_menu_item[data-action="settings"]', this.openSettingsDialog.bind(this));
            $(document.body).on('click', '.wpmn_more_menu_item[data-action="hide-folder-id"]', this.toggleFolderId.bind(this));
            $(document.body).on('click', '.wpmn_settings_dialog__close, .wpmn_settings_dialog__cancel', this.closeSettingsDialog.bind(this));
            $(document.body).on('click', '.wpmn_settings_dialog__save', this.saveSettings.bind(this));
            $(document.body).on('click', '.wpmn_theme_btn', this.handleThemeClick.bind(this));
            $(document.body).on('click', '.wpmn_clear_data_btn', this.handleClearData.bind(this));
            $(document.body).on('click', '.wpmn_more_menu_item[data-action="bulk-select"]', this.enableBulkSelect.bind(this));
            $(document.body).on('click', '.wpmn_bulk_cancel_btn', this.disableBulkSelect.bind(this));
            $(document.body).on('click', '.wpmn_delete_trigger', this.handleDeleteTrigger.bind(this));
            $(document.body).on('change', '.wpmn_folder_checkbox', this.handleCheckboxChange.bind(this));
            $(document.body).on('click', '.wpmn_generate_size_btn', this.handleGenerateSize.bind(this));
            $(document.body).on('click', (e) => {
                if (!$(e.target).closest('.wpmn_media_sidebar_action_sort, .wpmn_sort_menu').length) this.sidebar.find('.wpmn_sort_menu').prop('hidden', true);
                if (!$(e.target).closest('.wpmn_media_sidebar_action--more, .wpmn_more_menu').length) this.sidebar.find('.wpmn_more_menu').prop('hidden', true);
                if (!$(e.target).closest('.wpmn_folder_context_menu').length) this.hideContextMenu();
            });
            $(document.body).on('click', '.wpmn_import_btn', this.handleImport.bind(this));
            $(document.body).on('click', '.wpmn_export_btn', this.handleExport.bind(this));
            $(document.body).on('contextmenu', '.wpmn_folder_button', this.handleFolderContextMenu.bind(this));
            $(document.body).on('click', '.wpmn_context_menu_item', this.handleContextMenuClick.bind(this));
            $(document.body).on('click', '.wpmn_generate_api_btn', this.handleGenerateApiKey.bind(this));
            // Monitor tab changes in media modal
            // $(document.body).on('click', '.media-router .media-menu-item', this.handleMediaModalTabChange.bind(this));

        }

        handleToggleClick(e) {
            e.preventDefault();
            e.stopPropagation();
            const li = $(e.currentTarget).closest('.wpmn_folder_node');
            li.attr('aria-expanded', li.attr('aria-expanded') !== 'true');
            li.children('ul').slideToggle(200);
        }

        handleMenuToggle(e, menuClass) {
            e.preventDefault();
            e.stopPropagation();
            const menus = {
                '.wpmn_sort_menu': '.wpmn_more_menu',
                '.wpmn_more_menu': '.wpmn_sort_menu'
            };
            const menu = this.sidebar.find(menuClass);
            this.sidebar.find(menus[menuClass]).prop('hidden', true);
            menu.prop('hidden', !menu.prop('hidden'));
        }

        enableBulkSelect(e) {
            if (e) e.preventDefault();
            this.isBulkSelect = true;
            this.sidebar.addClass('is-bulk-select').find('.wpmn_media_sidebar_action_rename, .wpmn_action_wrapper').prop('hidden', true);
            this.sidebar.find('.wpmn_delete_trigger').prop('disabled', true).addClass('disabled');
            this.sidebar.find('.wpmn_bulk_cancel_btn').prop('hidden', false);
            this.toggleNewFolderForm(false);
        }

        disableBulkSelect(e) {
            if (e) e.preventDefault();
            this.isBulkSelect = false;
            this.sidebar.removeClass('is-bulk-select').find('.wpmn_media_sidebar_action_rename, .wpmn_action_wrapper').prop('hidden', false);
            this.sidebar.find('.wpmn_bulk_cancel_btn, .wpmn_folder_checkbox:checked').prop('hidden', true).prop('checked', false); // Hide cancel and uncheck
            this.sidebar.find('.wpmn_bulk_cancel_btn').prop('hidden', true); // Fix: ensure hidden
            this.updateActionButtons();
        }

        handleCheckboxChange() {
            const hasChecked = this.sidebar.find('.wpmn_folder_checkbox:checked').length > 0;
            this.sidebar.find('.wpmn_delete_trigger').prop('disabled', !hasChecked).toggleClass('disabled', !hasChecked);
        }

        handleDeleteTrigger(e) {
            if (this.isBulkSelect) {
                const checked = this.sidebar.find('.wpmn_folder_checkbox:checked');
                if (checked.length) this.openDeleteDialog(null, checked.length);
            } else {
                this.handleDeleteFolder();
            }
        }

        handleSearch(e) {
            const term = $(e.currentTarget).val().toLowerCase().trim();
            this.state.searchTerm = term;

            if (this.searchDebounce) clearTimeout(this.searchDebounce);

            if (!term) {
                this.state.searchResults = [];
                this.renderSidebar();
                return;
            }

            this.searchDebounce = setTimeout(() => this.performSearch(term), 300);
        }

        performSearch(term) {
            const url = wpmn_media_library.restUrl + 'folders';

            $.ajax({
                url: url,
                method: 'GET',
                data: { search: term },
                beforeSend: (xhr) => {
                    xhr.setRequestHeader('X-WP-Nonce', wpmn_media_library.restNonce);
                    this.sidebar.find('.wpmn_folder_tree').addClass('wpmn_loading');
                },
                success: (res) => {
                    if (res && res.success && res.data && res.data.folders) {
                        this.state.searchResults = res.data.folders;
                        this.renderSidebar();
                    }
                },
                error: (err) => console.error('Search failed', err),
                complete: () => {
                    this.sidebar.find('.wpmn_folder_tree').removeClass('wpmn_loading');
                }
            });
        }

        openSettingsDialog(e) {
            e.preventDefault();
            this.sidebar.find('.wpmn_more_menu').prop('hidden', true);
            const settings = JSON.parse(this.getStorage('wpmnSettings', '{}'));
            const select = $('#wpmn_default_folder').empty();

            select.append(new Option('All Files', 'all'), new Option('Uncategorized', 'uncategorized'));
            const addOptions = (nodes, depth = 0) => {
                nodes?.forEach(node => {
                    select.append(new Option((depth > 0 ? '-'.repeat(depth) + ' ' : '') + node.name, 'term-' + node.id));
                    addOptions(node.children, depth + 1);
                });
            };
            addOptions(this.state.folders);

            select.val(settings.defaultFolder || 'all');
            $('#wpmn_show_breadcrumb').prop('checked', settings.showBreadcrumb ?? true);
            $('.wpmn_theme_btn').removeClass('wpmn_theme_btn--active').filter(`[data-theme="${settings.theme || 'default'}"]`).addClass('wpmn_theme_btn--active');

            $('.wpmn_dialog_backdrop:not([data-delete-dialog])').prop('hidden', false).addClass('is-visible');
        }

        closeSettingsDialog() {
            $('.wpmn_dialog_backdrop:not([data-delete-dialog])').removeClass('is-visible').prop('hidden', true);
        }

        saveSettings() {
            const settings = {
                defaultFolder: $('#wpmn_default_folder').val(),
                showBreadcrumb: $('#wpmn_show_breadcrumb').is(':checked'),
                theme: $('.wpmn_theme_btn--active').data('theme') || 'default'
            };
            this.setStorage('wpmnSettings', JSON.stringify(settings));
            this.closeSettingsDialog();
            this.renderSidebar();
            this.showToast(this.getText('settingsSaved'));
        }

        handleThemeClick(e) {
            const __this = $(e.currentTarget);
            if (__this.data('theme') !== 'default') return;
            $('.wpmn_theme_btn').removeClass('wpmn_theme_btn--active');
            __this.addClass('wpmn_theme_btn--active');
        }

        handleFolderClick(e) {
            if (this.isBulkSelect && !$(e.target).hasClass('wpmn_folder_checkbox')) {
                const check = $(e.currentTarget).find('.wpmn_folder_checkbox');
                check.prop('checked', !check.prop('checked')).trigger('change');
                return;
            }
            const slug = $(e.currentTarget).data('folder-slug');
            if (slug && slug !== this.state.activeFolder) this.changeFolder(slug);
        }

        changeFolder(slug) {
            this.state.activeFolder = slug;
            this.setStorage('wpmnActiveFolder', slug);
            this.highlightActive();
            this.setupDroppableTargets();
            this.updateActionButtons();
            this.updateCustomToolbar();
            this.triggerMediaFilter(slug);
        }

        updateActionButtons() {
            const isSpecial = ['all', 'uncategorized'].includes(this.state.activeFolder);
            this.sidebar.find('.wpmn_media_sidebar_action_rename, .wpmn_media_sidebar_action_delete')
                .prop('disabled', isSpecial).toggleClass('disabled', isSpecial);
        }

        toggleNewFolderForm(show) {
            this.sidebar.find('.wpmn_new_folder_form').prop('hidden', !show);
            if (show) this.sidebar.find('.wpmn_new_folder_input').val('').focus();
        }

        handleCreateFolder() {
            const input = this.sidebar.find('.wpmn_new_folder_input'), name = input.val().trim();
            if (!name) return input.focus();

            const parent = this.state.activeFolder.startsWith('term-') ? this.state.activeFolder.replace('term-', '') : 0;
            this.apiCall('create_folder', { name, parent }).then(data => {
                this.refreshState(data);
                this.toggleNewFolderForm(false);
                this.showToast(this.getText('created', 'Folder created.'));
            }).catch(alert);
        }

        handleRenameFolder() {
            if (!this.state.activeFolder.startsWith('term-')) return alert(this.getText('selectFolderFirst', 'Select a folder first.'));
            const folder = this.findFolderById(this.state.activeFolder.replace('term-', ''), this.state.folders);
            if (folder) this.startInlineRename(folder);
        }

        startInlineRename(folder) {
            this.cleanupInlineRename();
            const __this = this.sidebar.find(`.wpmn_folder_button[data-folder-slug="${this.state.activeFolder}"]`);
            if (!__this.length) return;

            const form = $(`<div class="wpmn_folder_rename_inline" data-folder-id="${folder.id}">
				<img src="${wpmn_media_library.baseUrl || ''}assets/img/folder.svg" class="wpmn_folder_icon">
				<input type="text" class="wpmn_rename_inline_input" value="${folder.name}">
				<div class="wpmn_folder_rename_inline__actions">
					<button type="button" class="button button-secondary wpmn_rename_inline_cancel">Cancel</button>
					<button type="button" class="button button-primary wpmn_rename_inline_save">Save</button>
				</div>
			</div>`).data('originalButton', __this);

            __this.hide().parent().prepend(form);
            setTimeout(() => form.find('input').focus().select(), 10);
        }

        handleInlineRenameSave(e) {
            const form = $(e.currentTarget).closest('.wpmn_folder_rename_inline'),
                name = form.find('input').val().trim(), id = form.data('folder-id');
            if (!name) return form.find('input').focus();

            this.apiCall('rename_folder', { folder_id: id, name }).then(data => {
                this.refreshState(data);
                this.showToast(this.getText('renamed', 'Folder renamed.'));
                this.cleanupInlineRename();
            }).catch(alert);
        }

        cancelInlineRename(e) {
            if (e) e.preventDefault();
            this.cleanupInlineRename();
        }

        cleanupInlineRename() {
            $('.wpmn_folder_rename_inline').each((i, el) => {
                const form = $(el);
                form.data('originalButton')?.show();
                form.remove();
            });
        }

        handleDeleteFolder() {
            if (!this.state.activeFolder.startsWith('term-')) return alert(this.getText('selectFolderFirst', 'Select a folder first.'));
            const folder = this.findFolderById(this.state.activeFolder.replace('term-', ''), this.state.folders);
            if (folder) this.openDeleteDialog(folder);
        }

        openDeleteDialog(folder, bulkCount = 0) {
            this.pendingDeleteId = folder?.id || null;
            this.isBulkDeleteAction = !folder;
            const dialog = this.sidebar.find('[data-delete-dialog]'), msg = dialog.find('.wpmn_dialog_message');

            msg.text(folder
                ? (this.getText('deleteConfirm', 'Are you sure you want to delete %s?').replace('%s', folder.name))
                : `Are you sure you want to delete ${bulkCount} folder(s)?`
            );

            dialog.prop('hidden', false).addClass('is-visible');
        }

        closeDeleteDialog() {
            this.sidebar.find('[data-delete-dialog]').removeClass('is-visible').prop('hidden', true);
            this.pendingDeleteId = null;
        }

        confirmDeleteFolder() {
            if (this.isBulkDeleteAction) {
                const ids = this.sidebar.find('.wpmn_folder_checkbox:checked').map((i, el) => $(el).val()).get();
                if (!ids.length) return this.closeDeleteDialog();
                this.apiCall('delete_folders_bulk', { folder_ids: ids }).then(data => {
                    this.refreshState(data);
                    this.disableBulkSelect();
                    this.showToast(this.getText('deleted', 'Folders deleted.'));
                }).catch(alert).finally(() => this.closeDeleteDialog());
            } else if (this.pendingDeleteId) {
                this.apiCall('delete_folder', { folder_id: this.pendingDeleteId }).then(data => {
                    this.refreshState(data);
                    this.showToast(this.getText('deleted', 'Folder deleted.'));
                }).catch(alert).finally(() => this.closeDeleteDialog());
            }
        }

        fetchFolders() {
            this.apiCall('get_folders').then(data => this.refreshState(data)).catch(console.error);
        }

        refreshState(data) {
            this.state.folders = data.folders || [];
            this.state.counts = data.counts || {};

            // Validate active folder
            if (this.state.activeFolder.startsWith('term-')) {
                const id = this.state.activeFolder.replace('term-', '');
                if (!this.findFolderById(id, this.state.folders)) {
                    this.state.activeFolder = 'all';
                    this.setStorage('wpmnActiveFolder', 'all');
                }
            }
            this.renderSidebar();
        }

        renderSidebar() {
            this.sidebar.find('.wpmn_count_all').text(this.state.counts.all || 0);
            this.sidebar.find('.wpmn_count_uncategorized').text(this.state.counts.uncategorized || 0);

            const tree = this.sidebar.find('.wpmn_folder_tree').empty();
            const nodes = this.getFilteredTree();

            if (!nodes.length) {
                if (this.state.searchTerm) {
                    tree.append(`<p class="wpmn_empty_tree">${this.getText('emptyTree', 'No folders yet.')}</p>`);
                } else {
                    tree.append(`
						<div class="wpmn_empty_state">
							<div class="wpmn_empty_state_icon">&#128194;</div>
							<p class="wpmn_empty_state_title">${this.getText('emptyTitle', 'Create your first folder')}</p>
							<p class="wpmn_empty_state_description">${this.getText('emptyDescription', 'There are no folders available.')}</p>
							<button type="button" class="button button-primary wpmn_add_folder">${this.getText('emptyButton', 'Add Folder')}</button>
						</div>
					`).find('.wpmn_add_folder').on('click', () => this.toggleNewFolderForm(true));
                }
            } else {
                tree.append(this.buildTreeList(nodes));
            }

            this.highlightActive();
            this.updateActionButtons();
            this.setupDroppableTargets();
            this.updateFolderIdVisibility();
            this.updateCustomToolbar();
        }

        getFilteredTree() {
            if (!this.state.searchTerm) return this.state.folders;
            return this.state.searchResults;
        }

        findFolderById(id, nodes) {
            if (!nodes) return null;
            for (const node of nodes) {
                if (node.id == id) return node;
                const found = this.findFolderById(id, node.children);
                if (found) return found;
            }
            return null;
        }

        buildTreeList(nodes) {
            const ul = $('<ul role="group"></ul>');
            nodes.forEach(node => {
                const slug = 'term-' + node.id;
                const li = $('<li class="wpmn_folder_node" role="treeitem"></li>').attr('aria-expanded', !!node.children?.length);
                const row = $('<div class="wpmn_folder_row"></div>');
                const arrow = $('<span class="wpmn_toggle_arrow"></span>').toggleClass('has-children', !!node.children?.length);

                const btn = $(`<button type="button" class="wpmn_folder_button" data-folder-slug="${slug}" data-folder-id="${node.id}" data-folder-name="${node.name}">
					<input type="checkbox" class="wpmn_folder_checkbox" value="${node.id}">
					<img src="${wpmn_media_library.baseUrl || ''}assets/img/folder.svg" class="wpmn_folder_icon" aria-hidden="true">
					<span class="wpmn_folder_button__label"></span>
					<span class="wpmn_count">${node.count || 0}</span>
				</button>`);

                // Set text safely
                const labelText = (this.showFolderId ? `#${node.id} ` : '') + node.name;
                btn.find('.wpmn_folder_button__label').text(labelText);

                row.append(arrow, btn);
                li.append(row);
                if (node.children?.length) li.append(this.buildTreeList(node.children));
                ul.append(li);
            });
            return ul;
        }

        highlightActive() {
            this.sidebar.find('.is-active').removeClass('is-active');
            const btn = this.sidebar.find(`.wpmn_folder_button[data-folder-slug="${this.state.activeFolder}"]`).addClass('is-active');
            btn.find('.wpmn_folder_icon, .wpmn_all_files_icon, .wpmn_uncategorized_icon').addClass('is-active');
        }

        setupDroppableTargets() {
            this.sidebar.find('.wpmn_folder_button').each((i, el) => {
                const btn = $(el), slug = btn.data('folder-slug');
                if (btn.hasClass('ui-droppable')) btn.droppable('destroy');
                if (!slug || (slug !== 'uncategorized' && !slug.startsWith('term-'))) return;

                btn.droppable({
                    accept: '.attachments .attachment',
                    hoverClass: 'is-drop-hover',
                    tolerance: 'pointer',
                    drop: (e, ui) => {
                        const folderId = slug.startsWith('term-') ? parseInt(slug.replace('term-', ''), 10) : 0;
                        let ids = $('.attachments .attachment.selected').map((i, el) => parseInt($(el).data('id'))).get();
                        if (!ids.length && ui.draggable) ids = [parseInt(ui.draggable.data('id'))];
                        if (!ids.length) return alert(this.getText('noSelection'));
                        this.assignMediaToFolder(folderId, ids);
                    }
                });
            });
        }

        assignMediaToFolder(folderId, ids) {
            this.sidebar.find('.wpmn_tree_loader').prop('hidden', false);
            this.apiCall('assign_media', { folder_id: folderId, attachment_ids: ids }).then(data => {
                this.refreshState(data);
                this.showToast(this.getText('itemMoved'));

                // Remove items if moving out of current folder
                const isCurrentFolder = (this.state.activeFolder === 'uncategorized' && folderId === 0) ||
                    (this.state.activeFolder === 'term-' + folderId);

                if (!isCurrentFolder && this.state.activeFolder !== 'all') {
                    ids.forEach(id => $('.attachments .attachment[data-id="' + id + '"]').remove());
                }
            }).catch(alert).finally(() => this.sidebar.find('.wpmn_tree_loader').prop('hidden', true));
        }

        dragAndDropRefresh() {
            if (!this._dragTimer) this._dragTimer = setInterval(() => {
                const attachments = $('.attachments .attachment').not('.wpmn_draggable');
                if (attachments.length) {
                    attachments.addClass('wpmn_draggable').draggable({
                        helper: function () {
                            const count = $(this).hasClass('selected') ? $('.attachments .attachment.selected').length : 1;
                            return $('<div class="wpmn_drag_helper_pill"></div>').text(count === 1 ? 'Move 1 item' : `Move ${count} items`);
                        },
                        cursor: 'move', cursorAt: { left: 20, top: 20 }, appendTo: 'body', zIndex: 200000, revert: 'invalid'
                    });
                }
            }, 500);
        }

        injectSidebarLayout() {
            // Case 1: Standard Media Library Page
            const wrap = $('#wpbody-content .wrap');
            if (this.sidebar.length && wrap.length && !wrap.hasClass('wpmn_media_layout') && !$('.media-modal').length) {
                wrap.children().not(this.sidebar).wrapAll('<div class="wpmn_media_content"></div>');
                wrap.prepend(this.sidebar).addClass('wpmn_media_layout');
                if (this.getStorage('wpmnSidebarCollapsed') === '1') wrap.addClass('wpmn_media_layout_collapsed');
            }

            // Case 2: Media Modal
            setInterval(() => {
                const menu = $('.media-modal .media-frame-menu');
                if (menu.length && !menu.find('.wpmn_custom_sidebar_container').length) {
                    const container = $('<div class="wpmn_custom_sidebar_container"></div>');
                    menu.append(container);

                    if (this.sidebar.length) {
                        container.append(this.sidebar);
                        this.sidebar.show();
                    }

                    this.sidebar.addClass('in-modal');
                }
            }, 1000);
        }

        toggleSidebar() {
            const wrap = $('#wpbody-content .wrap').toggleClass('wpmn_media_layout_collapsed');
            this.setStorage('wpmnSidebarCollapsed', wrap.hasClass('wpmn_media_layout_collapsed') ? '1' : '0');
        }

        showToast(message) {
            if (!message) return;
            if (this.toastTimeout) clearTimeout(this.toastTimeout);
            let toast = $('.wpmn_toast');
            if (!toast.length) {
                toast = $('<div class="wpmn_toast" role="status"><span class="wpmn_toast_icon">&#10003;</span><p class="wpmn_toast_message"></p></div>').appendTo('body');
            }
            toast.find('.wpmn_toast_message').text(message);
            toast.addClass('wpmn_toast_visible');
            this.toastTimeout = setTimeout(() => toast.removeClass('wpmn_toast_visible'), 2500);
        }

        toggleFolderId(e) {
            if (e) e.preventDefault();
            this.showFolderId = !this.showFolderId;
            this.setStorage('wpmnShowFolderId', this.showFolderId ? '1' : '0');
            this.updateFolderIdVisibility();
            this.sidebar.find('.wpmn_more_menu').prop('hidden', true);
        }

        handleMediaModalTabChange(e) {
            const target = $(e.target);
            const isUploadTab = target.text().trim().includes('Upload files');

            if (isUploadTab) {
                $('.media-frame-menu .wpmn_custom_sidebar_container').hide();
                $('.media-modal .media-frame-menu').css('width', 'auto');
                setTimeout(() => this.injectUploadFolderSelect(), 100);
            } else {
                $('.media-frame-menu .wpmn_custom_sidebar_container').show();
                $('.media-modal .media-frame-menu').css('width', '330px');
            }
        }

        injectUploadFolderSelect() {
            const container = $('.media-uploader-status .upload-ui, .uploader-inline-content .upload-ui');
            if (container.length && !container.find('.wpmn-upload-folder-select').length) {

                const wrapper = $('<div class="wpmn-upload-folder-select" style="margin-top: 15px; display: flex; align-items: center; justify-content: center; gap: 10px;"></div>');
                wrapper.append('<label>Choose folder:</label>');

                const select = $('<select id="wpmn-upload-select" style="max-width: 200px;"></select>');
                select.append(new Option('Uncategorized', '0'));

                const addOptions = (nodes, depth = 0) => {
                    nodes?.forEach(node => {
                        select.append(new Option((depth > 0 ? '-'.repeat(depth) + ' ' : '') + node.name, node.id));
                        addOptions(node.children, depth + 1);
                    });
                };
                addOptions(this.state.folders);

                // Pre-select active folder if applicable
                const currentId = this.state.activeFolder.startsWith('term-') ? this.state.activeFolder.replace('term-', '') : 0;
                select.val(currentId);

                select.on('change', (e) => {
                    const id = $(e.target).val();
                    if (id == '0') {
                        this.changeFolder('uncategorized');
                    } else {
                        this.changeFolder('term-' + id);
                    }
                });

                wrapper.append(select);
                container.append(wrapper);
            }
        }

        updateFolderIdVisibility() {
            this.sidebar.find('.wpmn_folder_button[data-folder-id]').each((i, el) => {
                const btn = $(el), id = btn.data('folder-id'), name = btn.data('folder-name');
                btn.find('.wpmn_folder_button__label').text((this.showFolderId ? `#${id} ` : '') + name);
            });
        }

        updateCustomToolbar() {
            const toolbar = $('.media-toolbar.wp-filter');
            if (!toolbar.length) return;

            const settings = JSON.parse(this.getStorage('wpmnSettings', '{}'));
            let container = $('.wpmn_breadcrumb');
            if ((settings.showBreadcrumb === false) || !wpmn_media_library.showBreadcrumb) return container.remove();

            if (!container.length) container = $('<div class="wpmn_breadcrumb"></div>').insertAfter(toolbar);
            container.empty().append($('<span class="dashicons dashicons-admin-home"></span>').on('click', () => this.changeFolder('all')));

            const id = this.state.activeFolder.startsWith('term-') ? this.state.activeFolder.replace('term-', '') : null,
                path = id ? this.getFolderPath(id, this.state.folders) : [];

            path?.forEach((folder, i) => {
                container.append('<span class="wpmn_breadcrumb_line">/</span>');
                const isLast = i === path.length - 1,
                    el = $('<span>').addClass(isLast ? 'wpmn_breadcrumb_folder' : 'wpmn_breadcrumb_folders').text(folder.name);
                if (!isLast) el.on('click', () => this.changeFolder('term-' + folder.id));
                container.append(el);
            });
        }

        getFolderPath(id, nodes, path = []) {
            for (const node of nodes) {
                if (node.id == id) return [...path, node];
                if (node.children) {
                    const res = this.getFolderPath(id, node.children, [...path, node]);
                    if (res) return res;
                }
            }
            return null;
        }

        triggerMediaFilter(slug) {
            wp?.media?.frame?.state().get('library')?.props.set('wpmn_folder', slug);
        }

        handleClearData(e) {
            e.preventDefault();
            if (!confirm(this.getText('confirmClearData'))) return;
            const __this = $(e.currentTarget).prop('disabled', true);
            this.apiCall(__this.data('action')).then(() => {
                this.showToast(this.getText('allDataCleared'));
                location.reload();
            }).catch(msg => {
                this.showToast(this.getText('errorPrefix') + msg);
                __this.prop('disabled', false);
            });
        }

        handleImport(e) {
            e.preventDefault();
            const __this = $(e.currentTarget),
                input = $('#wpmn_import_file'),
                file = input[0].files[0];

            if (!file) return alert(this.getText('selectCsvFile'));
            if (__this.prop('disabled')) return;

            __this.prop('disabled', true).text('Importing...');

            const formData = new FormData();
            formData.append('csv_file', file);

            this.apiCall(__this.data('action'), formData).then(res => {
                __this.prop('disabled', false).text('Import Now');
                this.showToast(this.getText('foldersImported'));
                input.val('');
            }).catch(msg => {
                __this.prop('disabled', false).text('Import Now');
                this.showToast(this.getText('errorPrefix') + msg);
            });
        }

        handleExport(e) {
            e.preventDefault();
            const __this = $(e.currentTarget),
                action = __this.data('action');

            this.disableButton(__this);
            const form = this.createForm(action);
            form.appendTo('body').submit().remove();
            this.enableButton(__this);
        }

        handleGenerateSize(e) {
            e.preventDefault();
            const __this = $(e.currentTarget),
                originalText = __this.text();

            if (__this.prop('disabled')) return;

            __this.prop('disabled', true).text('Generating...');

            this.apiCall(__this.data('action')).then(res => {
                __this.prop('disabled', false).text(originalText);
                this.showToast(res.message || 'Attachment sizes generated.');
            }).catch(err => {
                __this.prop('disabled', false).text(originalText);
            });
        }

        disableButton(__this) {
            __this.prop('disabled', true).text('Exporting...');
        }

        enableButton(__this) {
            setTimeout(() => {
                __this.prop('disabled', false);
                __this.text('Export Now');
            }, 1000);
        }

        createForm(action) {
            const form = $('<form>', {
                method: 'POST',
                action: wpmn_media_library.ajaxUrl
            });

            form.append($('<input>', { type: 'hidden', name: 'action', value: 'wpmn_ajax' }));
            form.append($('<input>', { type: 'hidden', name: 'request_type', value: action }));

            return form;
        }

        handleGenerateApiKey(e) {
            e.preventDefault();
            const __this = $(e.currentTarget);

            if (__this.prop('disabled')) return;
            __this.prop('disabled', true).text('Generating...');

            this.apiCall('wpmn_generate_api_key').then(res => {
                __this.prop('disabled', false).text('Generate');
                $('.wpmn_api_key_input').val(res.key);
                this.showToast(res.message || 'API Key generated successfully.');
            }).catch(msg => {
                __this.prop('disabled', false).text('Generate');
                this.showToast(this.getText('errorPrefix') + msg);
            });
        }

        handleFolderContextMenu(e) {
            e.preventDefault();
            e.stopPropagation();

            const __this = $(e.currentTarget),
                folderSlug = __this.data('folder-slug'),
                folderId = __this.data('folder-id'),
                folderName = __this.data('folder-name');

            // Don't show context menu on special folders
            if (['all', 'uncategorized'].includes(folderSlug)) {
                return;
            }

            this.hideContextMenu();
            this.showContextMenu(e.pageX, e.pageY, folderId, folderName);
        }

        showContextMenu(x, y, folderId, folderName) {
            const menu = this.sidebar.find('.wpmn_folder_context_menu');

            if (!menu.length) {
                console.error('Context menu template not found');
                return;
            }

            // Update folder ID
            menu.data('folder-id', folderId).attr('data-folder-id', folderId);

            const pasteItem = menu.find('[data-action="paste"]');
            if (this.clipboard.folderId !== null) {
                pasteItem.removeClass('disabled');
            } else {
                pasteItem.addClass('disabled');
            }

            menu.prop('hidden', false);

            const menuWidth = menu.outerWidth(),
                menuHeight = menu.outerHeight(),
                windowWidth = $(window).width(),
                windowHeight = $(window).height();

            let posX = x;
            let posY = y;

            if (x + menuWidth > windowWidth) {
                posX = windowWidth - menuWidth - 10;
            }
            if (y + menuHeight > windowHeight) {
                posY = windowHeight - menuHeight - 10;
            }

            menu.css({ left: posX + 'px', top: posY + 'px' }).addClass('is-visible');
        }

        hideContextMenu() {
            const menu = this.sidebar.find('.wpmn_folder_context_menu');
            menu.removeClass('is-visible').prop('hidden', true);
        }

        handleContextMenuClick(e) {
            e.preventDefault();
            e.stopPropagation();

            const item = $(e.currentTarget);
            if (item.hasClass('disabled') || item.hasClass('wpmn_pro_feature')) {
                this.hideContextMenu();
                return;
            }

            const action = item.data('action'),
                menu = item.closest('.wpmn_folder_context_menu'),
                folderId = menu.data('folder-id');
            this.hideContextMenu();

            switch (action) {
                case 'new_folder':
                    this.handleContextNewFolder(folderId);
                    break;
                case 'rename':
                    this.handleContextRename(folderId);
                    break;
                case 'cut':
                    this.handleContextCut(folderId);
                    break;
                case 'paste':
                    this.handleContextPaste(folderId);
                    break;
                case 'delete':
                    this.handleContextDelete(folderId);
                    break;
            }
        }

        handleContextNewFolder(parentId) {
            this.state.activeFolder = 'term-' + parentId;
            this.highlightActive();
            this.updateActionButtons();
            this.toggleNewFolderForm(true);
        }

        handleContextRename(folderId) {
            this.state.activeFolder = 'term-' + folderId;
            this.highlightActive();
            this.updateActionButtons();
            const folder = this.findFolderById(folderId, this.state.folders);
            if (folder) this.startInlineRename(folder);
        }

        handleContextCut(folderId) {
            this.clipboard = { action: 'cut', folderId: folderId };
            this.sidebar.find('.wpmn_folder_button').removeClass('is-cut');
            this.sidebar.find(`.wpmn_folder_button[data-folder-id="${folderId}"]`).addClass('is-cut');
        }

        handleContextPaste(targetFolderId) {
            if (!this.clipboard.folderId || this.clipboard.action !== 'cut') {
                return;
            }

            const sourceFolderId = this.clipboard.folderId;
            if (sourceFolderId === targetFolderId) {
                this.showToast(this.getText('moveSelf'));
                return;
            }

            if (this.isChildFolder(targetFolderId, sourceFolderId)) {
                this.showToast(this.getText('moveSubfolder'));
                return;
            }
            this.moveFolderToParent(sourceFolderId, targetFolderId);
        }

        handleContextDelete(folderId) {
            this.state.activeFolder = 'term-' + folderId;
            const folder = this.findFolderById(folderId, this.state.folders);
            if (folder) this.openDeleteDialog(folder);
        }

        isChildFolder(childId, parentId, nodes = null) {
            if (nodes === null) nodes = this.state.folders;

            const parent = this.findFolderById(parentId, nodes);
            if (!parent || !parent.children) return false;

            for (const child of parent.children) {
                if (child.id == childId) return true;
                if (this.isChildFolder(childId, child.id, [child])) return true;
            }
            return false;
        }

        moveFolderToParent(folderId, newParentId) {
            this.apiCall('move_folder', { folder_id: folderId, new_parent: newParentId })
                .then(data => {
                    this.refreshState(data);
                    this.clipboard = { action: null, folderId: null };
                    this.sidebar.find('.wpmn_folder_button').removeClass('is-cut');
                    this.showToast(this.getText('folderMoved'));
                })
                .catch(alert);
        }
    }
    new WPMN_Media_Admin();
});
