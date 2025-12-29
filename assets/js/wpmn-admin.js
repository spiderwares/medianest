'use strict';

jQuery(function ($) {

    class WPMN_Admin {

        constructor() {
            window.wpmn_admin_media.admin = this;
            this.init();
        }

        init() {
            this.defaultSettings();
            this.injectSidebarLayout();
            this.bindEvents();
            this.fetchFolders();
            this.dragAndDropRefresh();
            this.updateCustomToolbar();
            this.initResizer();

            setTimeout(() => {
                const settings = JSON.parse(this.getStorage('wpmnSettings', '{}'));
                if (settings.defaultSort) {
                    this.applySortFromSettings(settings.defaultSort);
                }
            }, 1000);
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

        getPostType() {
            if (this.sidebar.hasClass('in-modal')) return 'attachment';
            const urlParams = new URLSearchParams(window.location.search),
                post_type = urlParams.get('post_type');
            if (post_type) return post_type;
            if (window.location.pathname.includes('upload.php')) return 'attachment';
            return wpmn_media_library.postType || 'post';
        }

        apiCall(request_type, data = {}) {
            const isFormData = data instanceof FormData;
            const post_type = this.getPostType();

            if (isFormData) {
                data.append('action', 'wpmn_ajax');
                data.append('request_type', request_type);
                data.append('nonce', wpmn_media_library.nonce);
                if (!data.has('post_type')) data.append('post_type', post_type);
            }

            return new Promise((resolve, reject) => {
                $.ajax({
                    type: 'POST',
                    url: wpmn_media_library.ajaxUrl,
                    data: isFormData ? data : {
                        action: 'wpmn_ajax',
                        request_type,
                        nonce: wpmn_media_library.nonce,
                        post_type,
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

            let activeFolderFromUrl = null;
            const urlParams = new URLSearchParams(window.location.search);
            if (window.location.pathname.includes('upload.php') || window.location.pathname.includes('edit.php')) {
                activeFolderFromUrl = urlParams.get('wpmn_folder');
            }

            this.state = {
                activeFolder: activeFolderFromUrl || savedSettings.defaultFolder || 'all',
                folders: [],
                counts: { all: 0, uncategorized: 0 },
                searchTerm: '',
                searchResults: []
            };
            this.searchDebounce = null;
            this.sidebar = $('#wpmn_media_sidebar');

            const postType = this.getPostType();
            this.showFolderId = this.getStorage('wpmnShowFolderId_' + postType) === '1';

            this.sidebarWidth = parseInt(this.getStorage('wpmnSidebarWidth', '300'));
            if (this.sidebar.length && this.sidebarWidth !== 300) {
                this.sidebar.css('width', this.sidebarWidth + 'px');
                this.updateModalLayout();
            }
            this.toastTimeout = null;
            this.isBulkSelect = false;
            this.clipboard = { action: null, folderId: null };
            this.allCollapsed = false;

            const media = window.location.pathname.includes('upload.php');
            const isList = window.location.pathname.includes('edit.php');
            const gridMode = media && (window.location.search.includes('mode=grid') || !window.location.search.includes('mode=list'));

            if (this.state.activeFolder !== 'all' && !activeFolderFromUrl) {
                if (gridMode) {
                    setTimeout(() => this.triggerMediaFilter(this.state.activeFolder, savedSettings.defaultSort), 1000);
                } else if (isList || media) {
                    this.triggerMediaFilter(this.state.activeFolder, savedSettings.defaultSort);
                }
            }
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
            $(document.body).on('click', '.wpmn_media_sidebar_action_more', (e) => this.handleMenuToggle(e, '.wpmn_more_menu'));
            $(document.body).on('click', '.wpmn_more_menu_item[data-action="settings"]', this.openSettingsDialog.bind(this));
            $(document.body).on('click', '.wpmn_more_menu_item[data-action="bulk-select"]', this.enableBulkSelect.bind(this));
            $(document.body).on('click', '.wpmn_more_menu_item[data-action="hide-folder-id"]', this.toggleFolderId.bind(this));
            $(document.body).on('click', '.wpmn_bulk_cancel_btn', this.disableBulkSelect.bind(this));
            $(document.body).on('click', '.wpmn_settings_dialog_close, .wpmn_settings_dialog_cancel', this.closeSettingsDialog.bind(this));
            $(document.body).on('click', '.wpmn_settings_dialog_save', this.saveSettings.bind(this));
            $(document.body).on('click', '.wpmn_theme_btn', this.handleThemeClick.bind(this));
            $(document.body).on('click', '.wpmn_clear_data_btn', this.handleClearData.bind(this));
            $(document.body).on('click', '.wpmn_delete_trigger', this.handleDeleteTrigger.bind(this));
            $(document.body).on('change', '.wpmn_folder_checkbox', this.handleCheckboxChange.bind(this));
            $(document.body).on('click', '.wpmn_generate_size_btn', this.handleGenerateSize.bind(this));
            $(document.body).on('click', (e) => {
                if (!$(e.target).closest('.wpmn_media_sidebar_action_sort, .wpmn_sort_menu').length) this.sidebar.find('.wpmn_sort_menu').prop('hidden', true);
                if (!$(e.target).closest('.wpmn_media_sidebar_action_more, .wpmn_more_menu').length) this.sidebar.find('.wpmn_more_menu').prop('hidden', true);
                if (!$(e.target).closest('.wpmn_folder_context_menu').length) this.hideContextMenu();
            });
            $(document.body).on('click', '.wpmn_import_btn', this.handleImport.bind(this));
            $(document.body).on('click', '.wpmn_export_btn', this.handleExport.bind(this));
            $(document.body).on('contextmenu', '.wpmn_folder_button', this.handleFolderContextMenu.bind(this));
            $(document.body).on('click', '.wpmn_context_menu_item', this.handleContextMenuClick.bind(this));
            $(document.body).on('click', '.wpmn_generate_api_btn', this.handleGenerateApiKey.bind(this));
            $(document.body).on('change', '.wpmn_folder_dropdown', this.handleAttachmentFolderChange.bind(this));
        }

        initResizer() {
            let isResizing = false;
            let startX, startWidth;

            $(document.body).on('mousedown', '.wpmn_sidebar_resize_handle', (e) => {
                isResizing = true;
                startX = e.pageX;
                startWidth = this.sidebar.outerWidth();
                $(document.body).addClass('wpmn_sidebar_is_resizing');
                e.preventDefault();
            });

            $(document).on('mousemove', (e) => {
                if (!isResizing) return;
                let newWidth = startWidth + (e.pageX - startX);
                if (newWidth < 300) newWidth = 300;
                if (newWidth > 630) newWidth = 630;

                this.sidebarWidth = newWidth;
                this.sidebar.css('width', newWidth + 'px');
                this.updateModalLayout();
                $(window).trigger('resize');
            });

            $(document).on('mouseup', () => {
                if (!isResizing) return;
                isResizing = false;
                $(document.body).removeClass('wpmn_sidebar_is_resizing');
                this.setStorage('wpmnSidebarWidth', this.sidebarWidth);
            });
        }

        updateModalLayout() {
            if (this.sidebar.hasClass('in-modal')) {
                const width = this.sidebarWidth + 30;
                $('.media-modal .media-frame-menu').css('width', width + 'px');
                $('.media-modal .media-frame-content, .media-modal .media-frame-title, .media-modal .media-frame-router')
                    .css('left', width + 'px');
            }
        }

        handleToggleClick(e) {
            if (e) e.preventDefault();
            e.stopPropagation();
            const li = $(e.currentTarget).closest('.wpmn_folder_node');
            const isExpanded = li.attr('aria-expanded') === 'true';

            li.attr('aria-expanded', !isExpanded);

            if (isExpanded) {
                li.children('ul').slideUp(300);
            } else {
                li.children('ul').slideDown(300);
            }

            const folderId = li.find('.wpmn_folder_button').first().data('folder-id');
            if (folderId) {
                const expanded = JSON.parse(this.getStorage('wpmnExpandedFolders', '{}'));
                if (!isExpanded) {
                    expanded[folderId] = true;
                } else {
                    delete expanded[folderId];
                }
                this.setStorage('wpmnExpandedFolders', JSON.stringify(expanded));
            }
        }

        handleMenuToggle(e, menuClass) {
            if (e) e.preventDefault();
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
            this.sidebar.find('.wpmn_bulk_cancel_btn').prop('hidden', true);
            wpmn_media_folder.folder.updateActionButtons();
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

            this.searchDebounce = setTimeout(() => wpmn_media_folder.folder.performSearch(term), 300);
        }

        openSettingsDialog(e) {
            if (e) e.preventDefault();
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
            $('#wpmn_default_sort').val(settings.defaultSort || 'default');
            $('.wpmn_theme_btn').removeClass('wpmn_theme_btn--active').filter(`[data-theme="${settings.theme || 'default'}"]`).addClass('wpmn_theme_btn--active');
            $('.wpmn_dialog_backdrop:not([data-delete-dialog])').prop('hidden', false).addClass('is-visible');
        }

        closeSettingsDialog() {
            $('.wpmn_dialog_backdrop:not([data-delete-dialog])').removeClass('is-visible').prop('hidden', true);
        }

        saveSettings() {
            const oldSettings = JSON.parse(this.getStorage('wpmnSettings', '{}'));
            const oldDefault = oldSettings.defaultFolder;

            const settings = {
                defaultFolder: $('#wpmn_default_folder').val(),
                defaultSort: $('#wpmn_default_sort').val() || 'default',
                theme: $('.wpmn_theme_btn--active').data('theme') || 'default'
            };

            // Save to localStorage for instant UI updates
            this.setStorage('wpmnSettings', JSON.stringify(settings));
            this.apiCall('save_settings', {
                default_folder: settings.defaultFolder,
                default_sort: settings.defaultSort,
                theme_design: settings.theme
            }).then(() => {
                this.showToast(this.getText('settingsSaved'));
            }).catch(err => {
                console.error('Failed to save settings:', err);
                this.showToast('Settings saved locally only');
            });

            this.closeSettingsDialog();
            this.renderSidebar();
            this.applySortFromSettings(settings.defaultSort);

            if (settings.defaultFolder && settings.defaultFolder !== oldDefault) {
                this.changeFolder(settings.defaultFolder, settings.defaultSort);
            }
        }

        applySortFromSettings(sortValue) {
            if (typeof wp !== 'undefined' && wp.hooks) {
                wp.hooks.doAction('wpmnSortFolders', sortValue);
            }
        }

        handleThemeClick(e) {
            const __this = $(e.currentTarget);
            $('.wpmn_theme_btn').removeClass('wpmn_theme_btn--active');
            __this.addClass('wpmn_theme_btn--active');
        }

        handleFolderClick(e) {
            if (this.isBulkSelect) {
                if (!$(e.target).hasClass('wpmn_folder_checkbox')) {
                    const check = $(e.currentTarget).find('.wpmn_folder_checkbox');
                    check.prop('checked', !check.prop('checked')).trigger('change');
                }
                return;
            }
            const slug = $(e.currentTarget).data('folder-slug');
            if (slug && slug !== this.state.activeFolder) this.changeFolder(slug);
        }

        changeFolder(slug, sortVal = null) {
            this.state.activeFolder = slug;
            this.setStorage('wpmnActiveFolder', slug);
            wpmn_media_folder.folder.highlightActive();
            wpmn_media_folder.folder.setupDroppableTargets();
            wpmn_media_folder.folder.updateActionButtons();
            this.updateCustomToolbar();

            const mediaFrame = wp?.media?.frame?.state;
            if (mediaFrame && window.location.search.includes('wpmn_folder')) {
                const url = new URL(window.location.href);
                url.searchParams.delete('wpmn_folder');
                window.history.replaceState({}, '', url.toString());
            }

            this.triggerMediaFilter(slug, sortVal);
            if (typeof wp !== 'undefined' && wp.hooks) {
                wp.hooks.doAction('wpmnFolderChanged', slug);
            }
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
                wpmn_media_folder.folder.refreshState(data);
                this.toggleNewFolderForm(false);
                this.showToast(this.getText('created', 'Folder created.'));
            }).catch(alert);
        }

        handleRenameFolder() {
            if (!this.state.activeFolder.startsWith('term-')) return alert(this.getText('selectFolderFirst', 'Select a folder first.'));
            const folder = wpmn_media_folder.folder.findFolderById(this.state.activeFolder.replace('term-', ''), this.state.folders);
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

            __this.hide().before(form);
            setTimeout(() => form.find('input').focus().select(), 10);
        }

        handleInlineRenameSave(e) {
            const form = $(e.currentTarget).closest('.wpmn_folder_rename_inline'),
                name = form.find('input').val().trim(), id = form.data('folder-id');
            if (!name) return form.find('input').focus();

            this.apiCall('rename_folder', { folder_id: id, name }).then(data => {
                wpmn_media_folder.folder.refreshState(data);
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
            const folder = wpmn_media_folder.folder.findFolderById(this.state.activeFolder.replace('term-', ''), this.state.folders);
            if (folder) this.openDeleteDialog(folder);
        }

        openDeleteDialog(folder, bulkCount = 0) {
            this.pendingDeleteId = folder?.id || null;
            this.isBulkDeleteAction = !folder;
            const dialog = this.sidebar.find('[data-delete-dialog]'),
                msg = dialog.find('.wpmn_dialog_message');

            msg.text(this.getText('deleteConfirm'));
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
                    wpmn_media_folder.folder.refreshState(data);
                    this.disableBulkSelect();
                    this.showToast(this.getText('deleted', 'Folders deleted.'));
                }).catch(alert).finally(() => this.closeDeleteDialog());
            } else if (this.pendingDeleteId) {
                this.apiCall('delete_folder', { folder_id: this.pendingDeleteId }).then(data => {
                    wpmn_media_folder.folder.refreshState(data);
                    this.showToast(this.getText('deleted', 'Folder deleted.'));
                }).catch(alert).finally(() => this.closeDeleteDialog());
            }
        }

        fetchFolders() {
            const postType = this.getPostType();
            this.showFolderId = this.getStorage('wpmnShowFolderId_' + postType) === '1';

            this.apiCall('get_folders', { post_type: postType })
                .then(data => {
                    // Load user settings from database
                    if (data.settings) {
                        const localSettings = JSON.parse(this.getStorage('wpmnSettings', '{}'));
                        const dbSettings = {
                            defaultFolder: data.settings.default_folder || localSettings.defaultFolder || 'all',
                            defaultSort: data.settings.default_sort || localSettings.defaultSort || 'default',
                            theme: data.settings.theme_design || localSettings.theme || 'default'
                        };

                        // Update localStorage with database settings
                        this.setStorage('wpmnSettings', JSON.stringify(dbSettings));
                        this.applyTheme(dbSettings.theme);
                    }

                    wpmn_media_folder.folder.refreshState(data);
                    this.updateSidebarLabels(postType);
                }).catch(console.error);
        }

        applyTheme(theme) {
            this.sidebar.removeClass('wpmn_theme_windows wpmn_theme_dropbox');
            if (theme && theme !== 'default') {
                this.sidebar.addClass('wpmn_theme_' + theme);
            }
        }

        updateSidebarLabels(postType) {
            this.sidebar.find('.wpmn_count_all').prev('span').text('All Files');
            this.sidebar.find('.wpmn_count_uncategorized').prev('span').text('Uncategorized');
        }

        renderSidebar() {
            const settings = JSON.parse(this.getStorage('wpmnSettings', '{}'));
            const theme = settings.theme || 'default';
            this.sidebar.removeClass('wpmn_theme_windows wpmn_theme_dropbox');
            if (theme !== 'default') {
                this.sidebar.addClass('wpmn_theme_' + theme);
            }

            this.sidebar.find('.wpmn_count_all').text(this.state.counts.all || 0);
            this.sidebar.find('.wpmn_count_uncategorized').text(this.state.counts.uncategorized || 0);
            const tree = this.sidebar.find('.wpmn_folder_tree').empty();
            const nodes = wpmn_media_folder.folder.getFilteredTree();

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
                tree.append(wpmn_media_folder.folder.buildTreeList(nodes));
            }

            wpmn_media_folder.folder.highlightActive();
            wpmn_media_folder.folder.setupDroppableTargets();
            wpmn_media_folder.folder.updateFolderIdVisibility();
            wpmn_media_folder.folder.updateActionButtons();
            this.updateCustomToolbar();
            wpmn_media_folder.folder.updateFolderIdMenuText();
        }

        dragAndDropRefresh() {
            if (this._dragTimer) return;

            const getHelper = (count) =>
                $('<div class="wpmn_drag_helper_pill"></div>').text(count === 1 ? 'Move 1 item' : `Move ${count} items`);

            const draggableOptions = (getCount) => ({
                helper: function () {
                    return getHelper(getCount.call(this));
                },
                cursor: 'move',
                cursorAt: { left: 20, top: 20 },
                appendTo: 'body',
                zIndex: 200000,
                revert: 'invalid',
                start() { $(this).css('opacity', 0.5); },
                stop() { $(this).css('opacity', 1); }
            });
            this._dragTimer = setInterval(() => {

                // Grid view
                $('.attachments .attachment').not('.wpmn_draggable').addClass('wpmn_draggable')
                    .draggable(draggableOptions(function () {
                        return $(this).hasClass('selected') ? $('.attachments .attachment.selected').length : 1;
                    }));

                // List view
                $('.wpmn_media_layout #the-list tr').not('.wpmn_draggable').addClass('wpmn_draggable')
                    .draggable(draggableOptions(function () {
                        return $(this).find('input[type="checkbox"]').is(':checked') ? $('#the-list input[type="checkbox"]:checked').length : 1;
                    }));

            }, 500);
        }

        injectSidebarLayout() {
            const wrap = $('#wpbody-content .wrap');

            if (this.sidebar.length && wrap.length && !wrap.hasClass('wpmn_media_layout') && !$('.media-modal').length) {
                wrap.children().not(this.sidebar).wrapAll('<div class="wpmn_media_content"></div>');
                wrap.prepend(this.sidebar).addClass('wpmn_media_layout');
                $('#screen-meta, #screen-meta-links').prependTo(wrap.find('.wpmn_media_content'));

                if (this.getStorage('wpmnSidebarCollapsed') === '1') {
                    wrap.addClass('wpmn_media_layout_collapsed');
                }
            }

            setInterval(() => {
                const modal = $('.media-modal:visible');
                const wasInModal = this.sidebar.hasClass('in-modal');

                if (!modal.length) {
                    if (wasInModal) {
                        this.sidebar.removeClass('in-modal');
                        this.fetchFolders();
                    }
                    return;
                }

                const menu = modal.find('.media-frame-menu');
                const router = modal.find('.media-router');
                const isUploadTab = router.find('.media-menu-item.active')
                    .is('#menu-item-upload')
                    || router.find('.media-menu-item.active').text().trim().includes('Upload files');

                if (menu.length && !menu.find('.wpmn_sidebar_container').length) {
                    menu.append('<div class="wpmn_sidebar_container"></div>').find('.wpmn_sidebar_container').append(this.sidebar.show());
                    this.sidebar.addClass('in-modal');
                    this.fetchFolders();
                }

                this.sidebar.toggle(!isUploadTab);

                if (this.sidebar.hasClass('in-modal')) {
                    this.updateModalLayout();
                }
            }, 1000);
        }

        toggleSidebar() {
            const wrap = $('#wpbody-content .wrap').toggleClass('wpmn_media_layout_collapsed');
            this.setStorage('wpmnSidebarCollapsed', wrap.hasClass('wpmn_media_layout_collapsed') ? '1' : '0');

            setTimeout(() => {
                $(window).trigger('resize');
            }, 100);
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
            e.preventDefault();
            this.showFolderId = !this.showFolderId;
            const postType = this.getPostType();
            this.setStorage('wpmnShowFolderId_' + postType, this.showFolderId ? '1' : '0');
            wpmn_media_folder.folder.updateFolderIdVisibility();
            wpmn_media_folder.folder.updateFolderIdMenuText();
            this.sidebar.find('.wpmn_more_menu').prop('hidden', true);
        }

        updateCustomToolbar() {
            let target = $('.media-toolbar .wp-filter');

            if (!target.length) return;

            const settings = JSON.parse(this.getStorage('wpmnSettings', '{}'));
            let container = $('.wpmn_breadcrumb');
            if (settings.showBreadcrumb === false || !wpmn_media_library.showBreadcrumb) {
                container.remove();
                return;
            }

            if (!container.length) {
                container = $('<div class="wpmn_breadcrumb"></div>');
                container.insertAfter(target);
            }

            container.empty().append($('<span class="dashicons dashicons-admin-home"></span>').on('click', () => this.changeFolder('all')));
            const id = this.state.activeFolder.startsWith('term-') ? this.state.activeFolder.replace('term-', '') : null;
            const path = (id ? this.getFolderPath(id, this.state.folders) : []) || [];

            path.forEach((folder, i) => {
                container.append('<span class="wpmn_breadcrumb_line">/</span>');
                const isLast = i === path.length - 1;
                const item = $('<span>').addClass(isLast ? 'wpmn_breadcrumb_folder' : 'wpmn_breadcrumb_folders').text(folder.name);

                if (!isLast) {
                    item.on('click', () => this.changeFolder(`term-${folder.id}`));
                }
                container.append(item);
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

        triggerMediaFilter(slug, sortVal = null) {
            const mediaFrame = wp?.media?.frame;
            const inModal = $('.media-modal').is(':visible');
            const media = window.location.pathname.includes('upload.php');
            const listView = window.location.pathname.includes('edit.php');
            const gridMode = media && (window.location.search.includes('mode=grid') || !window.location.search.includes('mode=list'));

            if (mediaFrame && (gridMode || inModal)) {

                const library = mediaFrame.state().get('library');
                if (library) {
                    library.props.set('wpmn_folder', slug);
                } else if (inModal) {
                    const activeState = mediaFrame.state();
                    if (activeState && activeState.get('library')) {
                        activeState.get('library').props.set('wpmn_folder', slug);
                    }
                }

                if (sortVal && sortVal !== 'default' && typeof wp !== 'undefined' && wp.hooks) {
                    wp.hooks.doAction('wpmnSortFolders', sortVal);
                }
            } else {
                const url = new URL(window.location.href);

                if (sortVal && sortVal !== 'default') {
                    const [field, order] = sortVal.split('-');
                    if (field && order) {
                        let orderby = field;
                        if (field === 'size') orderby = 'wpmn_filesize';

                        url.searchParams.set('orderby', orderby);
                        url.searchParams.set('order', order.toUpperCase());
                    }
                }

                if (!media && !listView) {
                    const postType = this.getPostType();
                    const adminUrl = wpmn_media_library.ajaxUrl.replace('admin-ajax.php', 'edit.php');
                    const newUrl = new URL(adminUrl);
                    newUrl.searchParams.set('post_type', postType);
                    newUrl.searchParams.set('wpmn_folder', slug);

                    if (url.searchParams.has('orderby')) newUrl.searchParams.set('orderby', url.searchParams.get('orderby'));
                    if (url.searchParams.has('order')) newUrl.searchParams.set('order', url.searchParams.get('order'));

                    window.location.href = newUrl.toString();
                    return;
                }

                const currentFolder = url.searchParams.get('wpmn_folder');
                if (currentFolder !== slug) {
                    url.searchParams.set('wpmn_folder', slug);
                }

                if (url.toString() !== window.location.href) {
                    window.location.href = url.toString();
                }
            }
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

        createForm(action) {
            const form = $('<form>', {
                method: 'POST',
                action: wpmn_media_library.ajaxUrl
            });

            form.append($('<input>', { type: 'hidden', name: 'action', value: 'wpmn_ajax' }));
            form.append($('<input>', { type: 'hidden', name: 'request_type', value: action }));
            form.append($('<input>', { type: 'hidden', name: 'nonce', value: wpmn_media_library.nonce }));
            return form;
        }

        handleExport(e) {
            e.preventDefault();

            const __this = $(e.currentTarget),
                action = __this.data('action'),
                originalText = __this.text();

            if (__this.prop('disabled')) return;
            __this.prop('disabled', true).text('Exporting...')
            try {
                const form = this.createForm(action);
                form.appendTo('body').submit().remove();
            } finally {
                setTimeout(() => {
                    __this.prop('disabled', false).text(originalText);
                }, 1000);
            }
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

            if (typeof wp !== 'undefined' && wp.hooks) {
                wp.hooks.doAction('wpmnShowContextMenu', menu, folderId);
            }
        }

        hideContextMenu() {
            const menu = this.sidebar.find('.wpmn_folder_context_menu');
            menu.removeClass('is-visible').prop('hidden', true);
        }

        handleContextMenuClick(e) {
            e.preventDefault();
            e.stopPropagation();

            const item = $(e.currentTarget);
            const action = item.data('action'),
                menu = item.closest('.wpmn_folder_context_menu'),
                folderId = menu.data('folder-id');
            this.hideContextMenu();

            switch (action) {
                case 'new_folder':
                    wpmn_media_folder.folder.handleContextNewFolder(folderId);
                    break;
                case 'rename':
                    wpmn_media_folder.folder.handleContextRename(folderId);
                    break;
                case 'cut':
                    wpmn_media_folder.folder.handleContextCut(folderId);
                    break;
                case 'paste':
                    wpmn_media_folder.folder.handleContextPaste(folderId);
                    break;
                case 'delete':
                    wpmn_media_folder.folder.handleContextDelete(folderId);
                    break;
                case 'duplicate':
                    if (typeof wp !== 'undefined' && wp.hooks) {
                        wp.hooks.doAction('wpmnFolderDuplicate', folderId);
                    }
                    break;
                case 'download':
                    if (typeof wp !== 'undefined' && wp.hooks) {
                        wp.hooks.doAction('wpmnFolderDownload', folderId);
                    }
                    break;
                case 'pin-folder':
                    if (typeof wp !== 'undefined' && wp.hooks) {
                        wp.hooks.doAction('wpmnFolderPin', folderId);
                    }
                    break;
            }
        }

        handleAttachmentFolderChange(e) {
            const __this = $(e.currentTarget),
                folderId = __this.val(),
                attachmentId = __this.attr('id').split('_').pop(),
                loader = __this.next('.wpmn_folder_loader');

            loader.addClass('is-active').css('visibility', 'visible');
            __this.prop('disabled', true);

            this.apiCall('assign_media', {
                folder_id: folderId,
                attachment_ids: [attachmentId],
            })
                .then(res => {
                    this.showToast(this.getText('itemMoved'));
                    wpmn_media_folder.folder.refreshState(res);

                    const active = this.state.activeFolder;
                    const inCurrentFolder =
                        (active === 'uncategorized' && folderId == 0) ||
                        active === `term-${folderId}`;

                    if (active !== 'all' && !inCurrentFolder) {
                        $(`.attachments .attachment[data-id="${attachmentId}"]`).remove();

                        try {
                            const library = wp?.media?.frame?.state()?.get('library');
                            const model = library?.get(attachmentId);
                            if (model) library.remove(model);
                        } catch (e) { }
                    }
                })
                .catch(msg => this.showToast(this.getText('errorPrefix') + msg))
                .finally(() => {
                    loader.removeClass('is-active').css('visibility', 'hidden');
                    __this.prop('disabled', false);
                });
        }
    }

    if (!window.wpmn_admin_media) {
        window.wpmn_admin_media = {};
    }

    window.wpmn_admin_media.admin = new WPMN_Admin();
});
