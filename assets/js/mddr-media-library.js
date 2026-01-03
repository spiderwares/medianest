'use strict';

jQuery(function ($) {

    class MDDR_Media_Library {

        constructor() {
            window.mddr_media_library.admin = this;
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
        }

        defaultSettings() {
            this.settings = window.mddr_media_library || {};
            const savedSettings = JSON.parse(this.getStorage('mddrSettings', '{}'));

            let activeFolderFromUrl = null;
            const urlParams = new URLSearchParams(window.location.search);
            if (window.location.pathname.includes('upload.php') || window.location.pathname.includes('edit.php')) {
                activeFolderFromUrl = urlParams.get('mddr_folder');
            }

            this.state = {
                activeFolder: activeFolderFromUrl || savedSettings.defaultFolder || 'all',
                folders: [],
                counts: { all: 0, uncategorized: 0 },
                searchTerm: '',
                searchResults: []
            };
            this.searchDebounce = null;
            this.sidebar = $('#mddr_media_sidebar');

            const postType = this.getPostType();
            this.showFolderId = this.getStorage('mddrShowFolderId_' + postType) === '1';

            this.sidebarWidth = parseInt(this.getStorage('mddrSidebarWidth', '300'));
            if (this.sidebar.length && this.sidebarWidth !== 300) {
                this.sidebar.css('width', this.sidebarWidth + 'px');
                this.updateModalLayout();
            }

            this.notyf = typeof Notyf !== 'undefined' ? new Notyf({
                duration: 3000,
                position: { x: 'center', y: 'top' },
                types: [
                    {
                        type: 'success',
                        background: '#32bc54',
                        icon: {
                            className: 'mddr-notyf-icon mddr-notyf-icon--success',
                            text: '✓'
                        }
                    },
                    {
                        type: 'error',
                        background: '#f24444',
                        icon: {
                            className: 'mddr-notyf-icon mddr-notyf-icon--error',
                            text: '✕'
                        }
                    }
                ]
            }) : null;

            this.toastTimeout = null;
            this.isBulkSelect = false;
            this.clipboard = { action: null, folderId: null };
            this.allCollapsed = false;

            const media = window.location.pathname.includes('upload.php'),
                isList = window.location.pathname.includes('edit.php'),
                gridMode = media && (window.location.search.includes('mode=grid') || !window.location.search.includes('mode=list'));

            if (this.state.activeFolder !== 'all' && !activeFolderFromUrl) {
                if (gridMode) {
                    setTimeout(() => this.triggerMediaFilter(this.state.activeFolder, savedSettings.defaultSort), 1000);
                } else if (isList || media) {
                    this.triggerMediaFilter(this.state.activeFolder, savedSettings.defaultSort);
                }
            }
        }

        bindEvents() {
            $(document.body).on('click', '.mddr_folder_button', this.handleFolderClick.bind(this));
            $(document.body).on('input', '.mddr_media_sidebar_search', this.handleSearch.bind(this));
            $(document.body).on('click', '.mddr_media_sidebar_new_folder', this.toggleNewFolderForm.bind(this, true));
            $(document.body).on('click', '.mddr_new_folder_cancel', this.toggleNewFolderForm.bind(this, false));
            $(document.body).on('click', '.mddr_new_folder_save', this.handleCreateFolder.bind(this));
            $(document.body).on('keydown', '.mddr_new_folder_input', this.handleNewFolderKeydown.bind(this));
            $(document.body).on('click', '.mddr_media_sidebar_action_rename', this.handleRenameFolder.bind(this));
            $(document.body).on('click', '.mddr_rename_inline_save', this.handleInlineRenameSave.bind(this));
            $(document.body).on('click', '.mddr_rename_inline_cancel', this.cancelInlineRename.bind(this));
            $(document.body).on('keydown', '.mddr_rename_inline_input', this.handleInlineRenameKeydown.bind(this));
            $(document.body).on('click', '.mddr_delete_confirm', this.confirmDeleteFolder.bind(this));
            $(document.body).on('click', '.mddr_delete_cancel', this.closeDeleteDialog.bind(this));
            $(document.body).on('click', '.mddr_media_sidebar_toggle', this.handleSidebarToggleClick.bind(this));
            $(document.body).on('click', '.mddr_toggle_arrow', this.handleToggleClick.bind(this));
            $(document.body).on('click', '.mddr_media_sidebar_action_sort', this.handleSortMenuToggle.bind(this));
            $(document.body).on('click', '.mddr_media_sidebar_action_more', this.handleMoreMenuToggle.bind(this));
            $(document.body).on('click', '.mddr_more_menu_item[data-action="settings"]', this.openSettingsDialog.bind(this));
            $(document.body).on('click', '.mddr_more_menu_item[data-action="bulk-select"]', this.enableBulkSelect.bind(this));
            $(document.body).on('click', '.mddr_more_menu_item[data-action="hide-folder-id"]', this.toggleFolderId.bind(this));
            $(document.body).on('click', '.mddr_bulk_cancel_btn', this.disableBulkSelect.bind(this));
            $(document.body).on('click', '.mddr_settings_dialog_close, .mddr_settings_dialog_cancel', this.closeSettingsDialog.bind(this));
            $(document.body).on('click', '.mddr_settings_dialog_save', this.saveSettings.bind(this));
            $(document.body).on('click', '.mddr_theme_btn', this.handleThemeClick.bind(this));
            $(document.body).on('click', '.mddr_delete_trigger', this.handleDeleteTrigger.bind(this));
            $(document.body).on('change', '.mddr_folder_checkbox', this.handleCheckboxChange.bind(this));
            $(document.body).on('click', this.handleDocumentClick.bind(this));
            $(document.body).on('contextmenu', '.mddr_folder_button', this.handleFolderContextMenu.bind(this));
            $(document.body).on('click', '.mddr_context_menu_item', this.handleContextMenuClick.bind(this));
        }

        handleNewFolderKeydown(e) {
            if (e.key === 'Enter') this.handleCreateFolder();
            if (e.key === 'Escape') this.toggleNewFolderForm(false);
        }

        handleInlineRenameKeydown(e) {
            if (e.key === 'Enter') this.handleInlineRenameSave(e);
            if (e.key === 'Escape') this.cancelInlineRename(e);
        }

        handleSidebarToggleClick(e) {
            e.preventDefault();
            this.toggleSidebar();
        }

        handleSortMenuToggle(e) {
            this.handleMenuToggle(e, '.mddr_sort_menu');
        }

        handleMoreMenuToggle(e) {
            this.handleMenuToggle(e, '.mddr_more_menu');
        }

        handleDocumentClick(e) {
            if (!$(e.target).closest('.mddr_media_sidebar_action_sort, .mddr_sort_menu').length) {
                this.sidebar.find('.mddr_sort_menu').prop('hidden', true);
            }
            if (!$(e.target).closest('.mddr_media_sidebar_action_more, .mddr_more_menu').length) {
                this.sidebar.find('.mddr_more_menu').prop('hidden', true);
            }
            if (!$(e.target).closest('.mddr_folder_context_menu').length) {
                this.hideContextMenu();
            }
        }

        getStorage(key, defCode) {
            try { return localStorage.getItem(key) || defCode; } catch { return defCode; }
        }

        setStorage(key, val) {
            try { localStorage.setItem(key, val); } catch { }
        }

        getText(key, def) {
            return mddr_media_library.mddr_folder?.[key] || def;
        }

        getPostType() {
            if (this.sidebar.hasClass('in-modal')) return 'attachment';
            const urlParams = new URLSearchParams(window.location.search),
                post_type = urlParams.get('post_type');
            if (post_type) return post_type;
            if (window.location.pathname.includes('upload.php')) return 'attachment';
            return mddr_media_library.postType || 'post';
        }

        apiCall(request_type, data = {}) {
            const isFormData = data instanceof FormData,
                post_type = this.getPostType();

            if (isFormData) {
                data.append('action', 'mddr_ajax');
                data.append('request_type', request_type);
                data.append('nonce', mddr_media_library.nonce);
                if (!data.has('post_type')) data.append('post_type', post_type);
            }

            return new Promise((resolve, reject) => {
                $.ajax({
                    type: 'POST',
                    url: mddr_media_library.ajaxUrl,
                    data: isFormData ? data : {
                        action: 'mddr_ajax',
                        request_type,
                        nonce: mddr_media_library.nonce,
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

        initResizer() {
            let isResizing = false;
            let startX, startWidth;

            $(document.body).on('mousedown', '.mddr_sidebar_resize_handle', (e) => {
                isResizing = true;
                startX = e.pageX;
                startWidth = this.sidebar.outerWidth();
                $(document.body).addClass('mddr_sidebar_is_resizing');
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
                $(document.body).removeClass('mddr_sidebar_is_resizing');
                this.setStorage('mddrSidebarWidth', this.sidebarWidth);
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
            const __this = $(e.currentTarget).closest('.mddr_folder_node'),
                isExpanded = __this.attr('aria-expanded') === 'true';

            __this.attr('aria-expanded', !isExpanded);

            if (isExpanded) {
                __this.children('ul').slideUp(300);
            } else {
                __this.children('ul').slideDown(300);
            }

            const folderId = __this.find('.mddr_folder_button').first().data('folder-id');
            if (folderId) {
                const expanded = JSON.parse(this.getStorage('mddrExpandedFolders', '{}'));
                if (!isExpanded) {
                    expanded[folderId] = true;
                } else {
                    delete expanded[folderId];
                }
                this.setStorage('mddrExpandedFolders', JSON.stringify(expanded));
            }
        }

        handleMenuToggle(e, menuClass) {
            if (e) e.preventDefault();
            e.stopPropagation();
            const menus = {
                '.mddr_sort_menu': '.mddr_more_menu',
                '.mddr_more_menu': '.mddr_sort_menu'
            };
            const menu = this.sidebar.find(menuClass);
            this.sidebar.find(menus[menuClass]).prop('hidden', true);
            menu.prop('hidden', !menu.prop('hidden'));
        }

        enableBulkSelect(e) {
            if (e) e.preventDefault();
            this.isBulkSelect = true;
            this.sidebar.addClass('is-bulk-select').find('.mddr_media_sidebar_action_rename, .mddr_action_wrapper').prop('hidden', true);
            this.sidebar.find('.mddr_delete_trigger').prop('disabled', true).addClass('disabled');
            this.sidebar.find('.mddr_bulk_cancel_btn').prop('hidden', false);
            this.toggleNewFolderForm(false);
        }

        disableBulkSelect(e) {
            if (e) e.preventDefault();
            this.isBulkSelect = false;
            this.sidebar.removeClass('is-bulk-select').find('.mddr_media_sidebar_action_rename, .mddr_action_wrapper').prop('hidden', false);
            this.sidebar.find('.mddr_bulk_cancel_btn, .mddr_folder_checkbox:checked').prop('hidden', true).prop('checked', false); // Hide cancel and uncheck
            this.sidebar.find('.mddr_bulk_cancel_btn').prop('hidden', true);
            mddr_media_folder.folder.updateActionButtons();
        }

        handleCheckboxChange() {
            const hasChecked = this.sidebar.find('.mddr_folder_checkbox:checked').length > 0;
            this.sidebar.find('.mddr_delete_trigger').prop('disabled', !hasChecked).toggleClass('disabled', !hasChecked);
        }

        handleDeleteTrigger(e) {
            if (this.isBulkSelect) {
                const checked = this.sidebar.find('.mddr_folder_checkbox:checked');
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

            this.searchDebounce = setTimeout(() => mddr_media_folder.folder.performSearch(term), 300);
        }

        openSettingsDialog(e) {
            if (e) e.preventDefault();
            this.sidebar.find('.mddr_more_menu').prop('hidden', true);
            const settings = JSON.parse(this.getStorage('mddrSettings', '{}')),
                select = $('#mddr_default_folder').empty();

            select.append(new Option('All Files', 'all'), new Option('Uncategorized', 'uncategorized'));
            const addOptions = (nodes, depth = 0) => {
                nodes?.forEach(node => {
                    select.append(new Option((depth > 0 ? '-'.repeat(depth) + ' ' : '') + node.name, 'term-' + node.id));
                    addOptions(node.children, depth + 1);
                });
            };
            addOptions(this.state.folders);

            let df = settings.defaultFolder || 'all';
            if (select.find('option[value="' + df + '"]').length === 0) {
                df = 'all';
            }
            select.val(df);

            $('#mddr_default_sort').val(settings.defaultSort || 'default');

            let currentTheme = settings.theme || 'default';
            let themeBtn = $(`.mddr_theme_btn[data-theme="${currentTheme}"]`);
            if (!themeBtn.length) {
                currentTheme = 'default';
                themeBtn = $(`.mddr_theme_btn[data-theme="default"]`);
            }
            $('.mddr_theme_btn').removeClass('mddr_theme_btn--active');
            themeBtn.addClass('mddr_theme_btn--active');
            $('.mddr_dialog_backdrop:not([data-delete-dialog])').prop('hidden', false).addClass('is-visible');
        }

        closeSettingsDialog() {
            $('.mddr_dialog_backdrop:not([data-delete-dialog])').removeClass('is-visible').prop('hidden', true);
        }

        saveSettings() {
            const oldSettings = JSON.parse(this.getStorage('mddrSettings', '{}')),
                oldDefault = oldSettings.defaultFolder,
                settings = {
                    defaultFolder: $('#mddr_default_folder').val(),
                    defaultSort: $('#mddr_default_sort').val() || 'default',
                    theme: $('.mddr_theme_btn--active').data('theme') || 'default'
                };

            // Save to localStorage for instant UI updates
            this.setStorage('mddrSettings', JSON.stringify(settings));
            this.apiCall('save_settings', {
                default_folder: settings.defaultFolder,
                default_sort: settings.defaultSort,
                theme_design: settings.theme
            }).then(() => {
                this.showToast(this.getText('settingsSaved'));
                this.closeSettingsDialog();
                this.renderSidebar();

                if (settings.defaultFolder && settings.defaultFolder !== oldDefault) {
                    this.changeFolder(settings.defaultFolder, settings.defaultSort);
                } else {
                    mddr_media_folder.folder.applySortFromSettings(settings.defaultSort);
                }
            }).catch(err => {
                console.error('Failed to save settings:', err);
                this.closeSettingsDialog();
            });
        }

        handleThemeClick(e) {
            const __this = $(e.currentTarget);
            $('.mddr_theme_btn').removeClass('mddr_theme_btn--active');
            __this.addClass('mddr_theme_btn--active');
        }

        handleFolderClick(e) {
            if (this.isBulkSelect) {
                if (!$(e.target).hasClass('mddr_folder_checkbox')) {
                    const check = $(e.currentTarget).find('.mddr_folder_checkbox');
                    check.prop('checked', !check.prop('checked')).trigger('change');
                }
                return;
            }
            const slug = $(e.currentTarget).data('folder-slug');
            if (slug && slug !== this.state.activeFolder) this.changeFolder(slug);
        }

        changeFolder(slug, sortVal = null) {
            this.state.activeFolder = slug;
            this.setStorage('mddrActiveFolder', slug);
            mddr_media_folder.folder.highlightActive();
            mddr_media_folder.folder.setupDroppableTargets();
            mddr_media_folder.folder.updateActionButtons();
            this.updateCustomToolbar();

            const __this = wp?.media?.frame?.state;
            if (__this && window.location.search.includes('mddr_folder')) {
                const url = new URL(window.location.href);
                url.searchParams.delete('mddr_folder');
                window.history.replaceState({}, '', url.toString());
            }

            this.triggerMediaFilter(slug, sortVal);
            if (typeof wp !== 'undefined' && wp.hooks) {
                wp.hooks.doAction('mddrFolderChanged', slug);
            }
        }

        toggleNewFolderForm(show) {
            this.sidebar.find('.mddr_new_folder_form').prop('hidden', !show);
            if (show) this.sidebar.find('.mddr_new_folder_input').val('').focus();
        }

        handleCreateFolder() {
            const input = this.sidebar.find('.mddr_new_folder_input'), name = input.val().trim();
            if (!name) return input.focus();

            const parent = this.state.activeFolder.startsWith('term-') ? this.state.activeFolder.replace('term-', '') : 0;
            this.apiCall('create_folder', { name, parent }).then(data => {
                mddr_media_folder.folder.refreshState(data);
                this.toggleNewFolderForm(false);
                this.showToast(this.getText('created', 'Folder created.'));
            }).catch(err => this.showToast(err, 'error'));
        }

        handleRenameFolder() {
            if (!this.state.activeFolder.startsWith('term-')) return this.showToast(this.getText('selectFolderFirst', 'Select a folder first.'), 'error');
            const folder = mddr_media_folder.folder.findFolderById(this.state.activeFolder.replace('term-', ''), this.state.folders);
            if (folder) this.startInlineRename(folder);
        }

        startInlineRename(folder) {
            this.cleanupInlineRename();
            const __this = this.sidebar.find(`.mddr_folder_button[data-folder-slug="${this.state.activeFolder}"]`);
            if (!__this.length) return;

            const form = $(`<div class="mddr_folder_rename_inline" data-folder-id="${folder.id}">
        		<img src="${mddr_media_library.baseUrl || ''}assets/img/folder.svg" class="mddr_folder_icon">
        		<input type="text" class="mddr_rename_inline_input" value="${folder.name}">
        		<div class="mddr_folder_rename_inline_actions">
        			<button type="button" class="button button-secondary mddr_rename_inline_cancel">Cancel</button>
        			<button type="button" class="button button-primary mddr_rename_inline_save">Save</button>
        		</div>
        	</div>`).data('originalButton', __this);

            __this.hide().before(form);
            setTimeout(() => form.find('input').focus().select(), 10);
        }

        handleInlineRenameSave(e) {
            const form = $(e.currentTarget).closest('.mddr_folder_rename_inline'),
                name = form.find('input').val().trim(), id = form.data('folder-id');
            if (!name) return form.find('input').focus();

            this.apiCall('rename_folder', { folder_id: id, name }).then(data => {
                mddr_media_folder.folder.refreshState(data);
                this.showToast(this.getText('renamed', 'Folder renamed.'));
                this.cleanupInlineRename();
            }).catch(err => this.showToast(err, 'error'));
        }

        cancelInlineRename(e) {
            if (e) e.preventDefault();
            this.cleanupInlineRename();
        }

        cleanupInlineRename() {
            $('.mddr_folder_rename_inline').each((i, el) => {
                const form = $(el);
                form.data('originalButton')?.show();
                form.remove();
            });
        }

        handleDeleteFolder() {
            if (!this.state.activeFolder.startsWith('term-')) return this.showToast(this.getText('selectFolderFirst', 'Select a folder first.'), 'error');
            const folder = mddr_media_folder.folder.findFolderById(this.state.activeFolder.replace('term-', ''), this.state.folders);
            if (folder) this.openDeleteDialog(folder);
        }

        openDeleteDialog(folder, bulkCount = 0) {
            this.pendingDeleteId = folder?.id || null;
            this.isBulkDeleteAction = !folder;
            const dialog = this.sidebar.find('[data-delete-dialog]'),
                msg = dialog.find('.mddr_dialog_message');

            msg.text(this.getText('deleteConfirm'));
            dialog.prop('hidden', false).addClass('is-visible');
        }

        closeDeleteDialog() {
            this.sidebar.find('[data-delete-dialog]').removeClass('is-visible').prop('hidden', true);
            this.pendingDeleteId = null;
        }

        confirmDeleteFolder() {
            if (this.isBulkDeleteAction) {
                const ids = this.sidebar.find('.mddr_folder_checkbox:checked').map((i, el) => $(el).val()).get();
                if (!ids.length) return this.closeDeleteDialog();
                this.apiCall('delete_folders_bulk', { folder_ids: ids }).then(data => {
                    mddr_media_folder.folder.refreshState(data);
                    this.disableBulkSelect();
                    this.showToast(this.getText('deleted', 'Folders deleted.'));
                }).catch(err => this.showToast(err, 'error')).finally(() => this.closeDeleteDialog());
            } else if (this.pendingDeleteId) {
                this.apiCall('delete_folder', { folder_id: this.pendingDeleteId }).then(data => {
                    mddr_media_folder.folder.refreshState(data);
                    this.showToast(this.getText('deleted', 'Folder deleted.'));
                }).catch(err => this.showToast(err, 'error')).finally(() => this.closeDeleteDialog());
            }
        }

        fetchFolders() {
            const postType = this.getPostType();
            this.showFolderId = this.getStorage('mddrShowFolderId_' + postType) === '1';

            this.apiCall('get_folders', { post_type: postType })
                .then(data => {
                    if (data.settings) {
                        const localSettings = JSON.parse(this.getStorage('mddrSettings', '{}')),
                            dbSettings = {
                                defaultFolder: data.settings.default_folder || localSettings.defaultFolder || 'all',
                                defaultSort: data.settings.default_sort || localSettings.defaultSort || 'default',
                                theme: data.settings.theme_design || localSettings.theme || 'default'
                            };

                        // Update localStorage with database settings
                        this.setStorage('mddrSettings', JSON.stringify(dbSettings));
                        this.applyTheme(dbSettings.theme);
                    }

                    mddr_media_folder.folder.refreshState(data);
                    this.updateSidebarLabels(postType);
                }).catch(console.error);
        }

        applyTheme(theme) {
            this.sidebar.removeClass('mddr_theme_windows mddr_theme_dropbox');
            if (theme && theme !== 'default') {
                this.sidebar.addClass('mddr_theme_' + theme);
            }
        }

        updateSidebarLabels(postType) {
            this.sidebar.find('.mddr_count_all').prev('span').text('All Files');
            this.sidebar.find('.mddr_count_uncategorized').prev('span').text('Uncategorized');
        }

        renderSidebar() {
            const settings = JSON.parse(this.getStorage('mddrSettings', '{}')),
                theme = settings.theme || 'default';
            this.sidebar.removeClass('mddr_theme_windows mddr_theme_dropbox');
            if (theme !== 'default') {
                this.sidebar.addClass('mddr_theme_' + theme);
            }

            this.sidebar.find('.mddr_count_all').text(this.state.counts.all || 0);
            this.sidebar.find('.mddr_count_uncategorized').text(this.state.counts.uncategorized || 0);
            const tree = this.sidebar.find('.mddr_folder_tree').empty(),
                nodes = mddr_media_folder.folder.getFilteredTree();

            if (!nodes.length) {
                if (this.state.searchTerm) {
                    tree.append(`<p class="mddr_empty_tree">${this.getText('emptyTree', 'No folders yet.')}</p>`);
                } else {
                    tree.append(`
						<div class="mddr_empty_state">
							<div class="mddr_empty_state_icon">&#128194;</div>
							<p class="mddr_empty_state_title">${this.getText('emptyTitle', 'Create your first folder')}</p>
							<p class="mddr_empty_state_description">${this.getText('emptyDescription', 'There are no folders available.')}</p>
							<button type="button" class="button button-primary mddr_add_folder">${this.getText('emptyButton', 'Add Folder')}</button>
						</div>
					`).find('.mddr_add_folder').on('click', () => this.toggleNewFolderForm(true));
                }
            } else {
                tree.append(mddr_media_folder.folder.buildTreeList(nodes));
            }

            mddr_media_folder.folder.highlightActive();
            mddr_media_folder.folder.setupDroppableTargets();
            mddr_media_folder.folder.updateFolderIdVisibility();
            mddr_media_folder.folder.updateActionButtons();
            this.updateCustomToolbar();
            mddr_media_folder.folder.updateFolderIdMenuText();
        }

        dragAndDropRefresh() {
            if (this._dragTimer) return;

            const getHelper = (count) =>
                $('<div class="mddr_drag_helper_pill"></div>').text(count === 1 ? 'Move 1 item' : `Move ${count} items`);

            const draggableOptions = (getCount) => ({
                helper: function () {
                    return getHelper(getCount.call(this));
                },
                cursor: 'move',
                cursorAt: { left: 20, top: 20 },
                appendTo: 'body',
                zIndex: 2147483647,
                revert: 'invalid',
                start() { $(this).css('opacity', 0.5); },
                stop() { $(this).css('opacity', 1); }
            });
            this._dragTimer = setInterval(() => {

                // Grid view
                $('.attachments .attachment').not('.mddr_draggable').addClass('mddr_draggable')
                    .draggable(draggableOptions(function () {
                        return $(this).hasClass('selected') ? $('.attachments .attachment.selected').length : 1;
                    }));

                // List view
                $('.mddr_media_layout #the-list tr').not('.mddr_draggable').addClass('mddr_draggable')
                    .draggable(draggableOptions(function () {
                        return $(this).find('input[type="checkbox"]').is(':checked') ? $('#the-list input[type="checkbox"]:checked').length : 1;
                    }));

            }, 500);
        }

        injectSidebarLayout() {
            const wrap = $('#wpbody-content .wrap');

            if (this.sidebar.length && wrap.length && !wrap.hasClass('mddr_media_layout') && !$('.media-modal').length) {
                wrap.children().not(this.sidebar).wrapAll('<div class="mddr_media_content"></div>');
                wrap.prepend(this.sidebar).addClass('mddr_media_layout');
                $('#screen-meta, #screen-meta-links').prependTo(wrap.find('.mddr_media_content'));

                if (this.getStorage('mddrSidebarCollapsed') === '1') {
                    wrap.addClass('mddr_media_layout_collapsed');
                }
            }

            setInterval(() => {
                const __this = $('.media-modal:visible'),
                    wasInModal = this.sidebar.hasClass('in-modal');

                if (!__this.length) {
                    if (wasInModal) {
                        this.sidebar.removeClass('in-modal');
                        this.fetchFolders();
                    }
                    return;
                }

                const menu = __this.find('.media-frame-menu'),
                    router = __this.find('.media-router'),
                    uploadTab = router.find('.media-menu-item.active').is('#menu-item-upload')
                        || router.find('.media-menu-item.active').text().trim().includes('Upload files');

                if (menu.length && !menu.find('.mddr_sidebar_container').length) {
                    menu.append('<div class="mddr_sidebar_container"></div>').find('.mddr_sidebar_container').append(this.sidebar.show());
                    this.sidebar.addClass('in-modal');
                    this.fetchFolders();
                }

                this.sidebar.toggle(!uploadTab);

                if (this.sidebar.hasClass('in-modal')) {
                    this.updateModalLayout();
                }

                // Ensure breadcrumb is present if enabled
                this.updateCustomToolbar();
            }, 1000);
        }

        toggleSidebar() {
            const wrap = $('#wpbody-content .wrap').toggleClass('mddr_media_layout_collapsed');
            this.setStorage('mddrSidebarCollapsed', wrap.hasClass('mddr_media_layout_collapsed') ? '1' : '0');

            setTimeout(() => {
                $(window).trigger('resize');
            }, 100);
        }

        showToast(message, type = 'success') {
            if (!message || !this.notyf) return;
            this.notyf.open({ type: type, message: message });
        }

        toggleFolderId(e) {
            e.preventDefault();
            this.showFolderId = !this.showFolderId;
            const postType = this.getPostType();
            this.setStorage('mddrShowFolderId_' + postType, this.showFolderId ? '1' : '0');
            mddr_media_folder.folder.updateFolderIdVisibility();
            mddr_media_folder.folder.updateFolderIdMenuText();
            this.sidebar.find('.mddr_more_menu').prop('hidden', true);
        }

        updateCustomToolbar() {
            const isMediaGrid = window.location.pathname.includes('upload.php') && (window.location.search.includes('mode=grid') || !window.location.search.includes('mode=list'));
            const isMediaList = window.location.pathname.includes('upload.php') && window.location.search.includes('mode=list');
            const isOtherList = window.location.pathname.includes('edit.php');
            const inModal = $('.media-modal').is(':visible');

            // Prioritize .wp-filter as requested by user, with fallbacks for different views
            let target = $('.wp-filter').first();

            if (!target.length) {
                if (inModal || isMediaGrid) {
                    target = $('.media-toolbar').first();
                } else if (isMediaList || isOtherList) {
                    target = $('.tablenav.top').first();
                    if (!target.length) target = $('.wp-header-end').first();
                    if (!target.length) target = $('.wrap h1').first();
                }
            }

            if (!target || !target.length) return;

            const settings = JSON.parse(this.getStorage('mddrSettings', '{}'));
            let container = $('.mddr_breadcrumb');

            // Source of truth for showing breadcrumb
            const showBreadcrumb = (settings.showBreadcrumb !== undefined) ? settings.showBreadcrumb : (this.settings.showBreadcrumb !== false);

            if (!showBreadcrumb) {
                container.remove();
                return;
            }

            if (!container.length) {
                container = $('<div class="mddr_breadcrumb"></div>');
                container.insertAfter(target);
            }

            container.empty().append($('<span class="dashicons dashicons-admin-home"></span>')
                .attr('title', this.getText('all', 'All Files'))
                .on('click', () => this.changeFolder('all')));

            let path = [];
            if (this.state.activeFolder === 'uncategorized') {
                path = [{ id: 'uncategorized', name: this.getText('uncategorized', 'Uncategorized') }];
            } else if (this.state.activeFolder.startsWith('term-')) {
                const id = this.state.activeFolder.replace('term-', '');
                path = this.getFolderPath(id, this.state.folders) || [];
            }

            path.forEach((folder, i) => {
                container.append('<span class="mddr_breadcrumb_line">/</span>');
                const isLast = i === path.length - 1;
                let folderName = folder.name;
                if (folder.id === 'uncategorized') folderName = this.getText('uncategorized', 'Uncategorized');

                const item = $('<span>').addClass(isLast ? 'mddr_breadcrumb_folder' : 'mddr_breadcrumb_folders').text(folderName);

                if (!isLast) {
                    const slug = folder.id === 'uncategorized' ? 'uncategorized' : `term-${folder.id}`;
                    item.on('click', () => this.changeFolder(slug));
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
            const __this = wp?.media?.frame,
                inModal = $('.media-modal').is(':visible'),
                media = window.location.pathname.includes('upload.php'),
                gridMode = media && (window.location.search.includes('mode=grid') || !window.location.search.includes('mode=list'));

            if (__this && (gridMode || inModal)) {

                const library = __this.state().get('library');
                if (library) {
                    library.props.set('mddr_folder', slug);
                } else if (inModal) {
                    const activeState = __this.state();
                    if (activeState && activeState.get('library')) {
                        activeState.get('library').props.set('mddr_folder', slug);
                    }
                }

                if (sortVal && sortVal !== 'default' && typeof wp !== 'undefined' && wp.hooks) {
                    wp.hooks.doAction('mddrSortFolders', sortVal);
                }
            } else {
                const url = new URL(window.location.href);

                if (sortVal && sortVal !== 'default') {
                    const [field, order] = sortVal.split('-');
                    if (field && order) {
                        let orderby = field;
                        if (field === 'size') orderby = 'mddr_filesize';

                        url.searchParams.set('orderby', orderby);
                        url.searchParams.set('order', order.toUpperCase());
                    }
                }

                const currentFolder = url.searchParams.get('mddr_folder');
                if (currentFolder !== slug) {
                    url.searchParams.set('mddr_folder', slug);
                }

                // Add nonce for secure filtering
                // url.searchParams.set('nonce', mddr_media_library.nonce);

                if (url.toString() !== window.location.href) {
                    window.location.href = url.toString();
                }
            }
        }

        createForm(action) {
            const form = $('<form>', {
                method: 'POST',
                action: mddr_media_library.ajaxUrl
            });

            form.append($('<input>', { type: 'hidden', name: 'action', value: 'mddr_ajax' }));
            form.append($('<input>', { type: 'hidden', name: 'request_type', value: action }));
            form.append($('<input>', { type: 'hidden', name: 'nonce', value: mddr_media_library.nonce }));
            return form;
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
            this.showContextMenu(e.clientX, e.clientY, folderId, folderName);
        }

        showContextMenu(x, y, folderId, folderName) {
            const menu = this.sidebar.find('.mddr_folder_context_menu');

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

            // Calculate position to keep it in viewport
            const menuWidth = menu.outerWidth(),
                menuHeight = menu.outerHeight(),
                windowWidth = $(window).width(),
                windowHeight = $(window).height();

            if (x + menuWidth > windowWidth) {
                x = windowWidth - menuWidth - 10;
            }
            if (x < 10) x = 10;

            if (y + menuHeight > windowHeight) {
                y = windowHeight - menuHeight - 10;
            }
            if (y < 10) y = 10;

            menu.css({ left: x + 'px', top: y + 'px' }).addClass('is-visible');

            if (typeof wp !== 'undefined' && wp.hooks) {
                wp.hooks.doAction('mddrShowContextMenu', menu, folderId);
            }
        }

        hideContextMenu() {
            this.sidebar.find('.mddr_folder_context_menu').removeClass('is-visible').prop('hidden', true);
        }

        handleContextMenuClick(e) {
            e.preventDefault();
            e.stopPropagation();

            const item = $(e.currentTarget),
                action = item.data('action'),
                menu = item.closest('.mddr_folder_context_menu'),
                folderId = menu.data('folder-id');
            this.hideContextMenu();

            switch (action) {
                case 'new_folder':
                    mddr_media_folder.folder.handleContextNewFolder(folderId);
                    break;
                case 'rename':
                    mddr_media_folder.folder.handleContextRename(folderId);
                    break;
                case 'cut':
                    mddr_media_folder.folder.handleContextCut(folderId);
                    break;
                case 'paste':
                    mddr_media_folder.folder.handleContextPaste(folderId);
                    break;
                case 'delete':
                    mddr_media_folder.folder.handleContextDelete(folderId);
                    break;
                case 'duplicate':
                    if (typeof wp !== 'undefined' && wp.hooks) {
                        wp.hooks.doAction('mddrFolderDuplicate', folderId);
                    }
                    break;
                case 'download':
                    if (typeof wp !== 'undefined' && wp.hooks) {
                        wp.hooks.doAction('mddrFolderDownload', folderId);
                    }
                    break;
                case 'pin-folder':
                    if (typeof wp !== 'undefined' && wp.hooks) {
                        wp.hooks.doAction('mddrFolderPin', folderId);
                    }
                    break;
            }
        }
    }

    window.mddr_media_library = window.mddr_media_library || {};
    window.mddr_media_library.admin = new MDDR_Media_Library();
});
