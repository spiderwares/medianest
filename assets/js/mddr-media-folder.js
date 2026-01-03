'use strict';

jQuery(function ($) {

    class MDDR_Media_Folder {

        highlightActive() {
            const sidebar = mddr_media_library.admin.sidebar;
            sidebar.find('.mddr_folder_button.is-active').removeClass('is-active');
            sidebar.find('.mddr_folder_icon.is-active, .mddr_all_files_icon.is-active, .mddr_uncategorized_icon.is-active').removeClass('is-active');

            const btn = sidebar.find(`.mddr_folder_button[data-folder-slug="${mddr_media_library.admin.state.activeFolder}"]`).addClass('is-active');
            btn.find('.mddr_folder_icon, .mddr_all_files_icon, .mddr_uncategorized_icon').addClass('is-active');
        }

        updateActionButtons() {
            const isSpecial = ['all', 'uncategorized'].includes(mddr_media_library.admin.state.activeFolder);
            mddr_media_library.admin.sidebar.find('.mddr_media_sidebar_action_rename, .mddr_media_sidebar_action_delete')
                .prop('disabled', isSpecial).toggleClass('disabled', isSpecial);
        }

        updateFolderIdVisibility() {
            mddr_media_library.admin.sidebar.find('.mddr_folder_button[data-folder-id]').each((i, el) => {
                const btn = $(el), id = btn.data('folder-id'), name = btn.data('folder-name');
                btn.find('.mddr_folder_button_label').text((mddr_media_library.admin.showFolderId ? `#${id} ` : '') + name);
            });
        }

        refreshState(data) {
            mddr_media_library.admin.state.folders = data.folders || [];
            mddr_media_library.admin.state.counts = data.counts || {};

            // Validate active folder
            if (mddr_media_library.admin.state.activeFolder.startsWith('term-')) {
                const id = mddr_media_library.admin.state.activeFolder.replace('term-', '');
                if (!this.findFolderById(id, mddr_media_library.admin.state.folders)) {
                    mddr_media_library.admin.state.activeFolder = 'all';
                    mddr_media_library.admin.setStorage('mddrActiveFolder', 'all');
                }
            }
            mddr_media_library.admin.renderSidebar();

            // Trigger hook for Pro version
            if (typeof wp !== 'undefined' && wp.hooks) {
                wp.hooks.doAction('mddrFoldersLoaded');
            }
        }

        buildTreeList(nodes) {
            const expanded = JSON.parse(
                mddr_media_library.admin.getStorage('mddrExpandedFolders', '{}')
            );

            const ul = $('<ul role="group"></ul>');

            nodes.forEach(node => {
                const isExpanded = !!expanded[node.id],
                    hasChildren = !!node.children?.length;

                const li = $('<li>', {
                    class: 'mddr_folder_node',
                    role: 'treeitem',
                    'aria-expanded': isExpanded
                });

                const arrow = $('<span class="mddr_toggle_arrow">')
                    .toggleClass('has-children', hasChildren);

                const btn = $(`
                    <button type="button" class="mddr_folder_button"
                        data-folder-slug="term-${node.id}"
                        data-folder-id="${node.id}"
                        data-folder-name="${node.name}"
                        data-color="${node.color || ''}">
                        <input type="checkbox" class="mddr_folder_checkbox" value="${node.id}">
                        <img src="${mddr_media_library.baseUrl || ''}assets/img/folder.svg"
                            class="mddr_folder_icon" aria-hidden="true">
                        <span class="mddr_folder_button_label"></span>
                        <span class="mddr_count">${node.count || 0}</span>
                    </button>
                `);

                const icon = btn.find('.mddr_folder_icon');
                this.applyIconColor(icon, node.color || '');

                btn.find('.mddr_folder_button_label').text(
                    (mddr_media_library.admin.showFolderId ? `#${node.id} ` : '') + node.name
                );

                li.append(
                    $('<div class="mddr_folder_row"></div>').append(
                        arrow,
                        $('<div class="mddr_folder_btn_wrapper"></div>').append(
                            $('<div class="mddr_drop_indicator drop-top"></div>'),
                            btn,
                            $('<div class="mddr_drop_indicator drop-bottom"></div>')
                        )
                    )
                );

                if (hasChildren) {
                    const children = this.buildTreeList(node.children);
                    if (!isExpanded) children.hide();
                    li.append(children);
                }

                ul.append(li);
            });

            return ul;
        }

        updateFolderIdMenuText() {
            const item = mddr_media_library.admin.sidebar.find('.mddr_more_menu_item[data-action="hide-folder-id"]'),
                icon = item.find('.dashicons'),
                label = item.find('span:not(.dashicons)');

            if (mddr_media_library.admin.showFolderId) {
                label.text(item.data('text-hide'));
                icon.removeClass(item.data('icon-show')).addClass(item.data('icon-hide'));
            } else {
                label.text(item.data('text-show'));
                icon.removeClass(item.data('icon-hide')).addClass(item.data('icon-show'));
            }
        }

        setupDroppableTargets() {
            const sidebar = mddr_media_library.admin.sidebar,
                folderButtons = sidebar.find('.mddr_folder_button[data-folder-id]');

            folderButtons.each((_, el) => {
                const __this = $(el);
                if (__this.hasClass('ui-draggable')) {
                    __this.draggable('destroy');
                }

                __this.draggable({
                    cancel: '.mddr_folder_checkbox',
                    distance: 5,
                    delay: 0,
                    helper: function () {
                        const folderName = $(this).data('folder-name'),
                            folderIcon = $(this).find('.mddr_folder_icon').prop('src');
                        return $(`
                            <div class="mddr_drag_helper_pill mddr_folder_drag_helper">
                                <img src="${folderIcon}" class="mddr_drag_folder_icon" />
                                <span>${folderName}</span>
                            </div>
                        `);
                    },
                    cursorAt: { left: 60, top: 15 },
                    appendTo: 'body',
                    zIndex: 2147483647,
                    revert: 'invalid',
                    start() {
                        $(this).closest('.mddr_folder_node').addClass('is-dragging');
                        $(this).css('opacity', 0.5);
                    },
                    stop() {
                        $(this).closest('.mddr_folder_node').removeClass('is-dragging');
                        $(this).css('opacity', 1);
                    }
                });
            });

            // Handle droppable on folder rows for sorting/nesting and special buttons
            sidebar.find('.mddr_folder_row, .mddr_media_sidebar_section .mddr_folder_button').each((_, el) => {
                const __this = $(el),
                    btn = __this.hasClass('mddr_folder_button') ? __this : __this.find('.mddr_folder_button'),
                    slug = btn.data('folder-slug');

                if (__this.hasClass('ui-droppable')) {
                    __this.droppable('destroy');
                }

                if (!slug || (slug !== 'uncategorized' && slug !== 'all' && !slug.startsWith('term-'))) return;

                __this.droppable({
                    accept: '.attachments .attachment, .mddr_media_layout #the-list tr.mddr_draggable, .mddr_folder_button[data-folder-id]',
                    hoverClass: 'has-media-hover',
                    tolerance: 'pointer',
                    over: (event, ui) => {
                        if (!ui.draggable.hasClass('mddr_folder_button')) return;
                    },
                    out: (event, ui) => {
                        __this.find('.mddr_drop_indicator').hide();
                        __this.find('.mddr_folder_btn_wrapper').removeClass('is-nest-hover');
                    },
                    drop: (event, ui) => {
                        const targetFolderId = slug.startsWith('term-') ? parseInt(slug.replace('term-', ''), 10) : 0;
                        __this.find('.mddr_drop_indicator').hide();
                        __this.find('.mddr_folder_btn_wrapper').removeClass('is-nest-hover');

                        if (ui.draggable.hasClass('mddr_folder_button')) {
                            const isSpecial = ['all', 'uncategorized'].includes(slug);
                            if (isSpecial) return;

                            const draggedFolderId = parseInt(ui.draggable.data('folder-id'));

                            // Reorder logic based on mouse position
                            const offsetY = event.pageY - __this.offset().top,
                                height = __this.outerHeight();

                            let action = 'nest';
                            if (offsetY < height * 0.25) action = 'before';
                            else if (offsetY > height * 0.75) action = 'after';

                            if (draggedFolderId === targetFolderId) return;
                            if (this.childFolder(targetFolderId, draggedFolderId)) {
                                mddr_media_library.admin.showToast(mddr_media_library.admin.getText('moveSubfolder', 'Cannot move folder to its subfolder'));
                                return;
                            }

                            if (action === 'nest') {
                                this.moveFolderToParent(draggedFolderId, targetFolderId);
                            } else {
                                this.reorderFolder(draggedFolderId, targetFolderId, action);
                            }
                        } else {
                            const ids = this.getDraggedMediaIds(ui);
                            if (!ids.length) return mddr_media_library.admin.showToast(mddr_media_library.admin.getText('noSelection'), 'error');
                            this.assignMediaToFolder(targetFolderId, ids);
                        }
                    }
                });

                // Performance: Separate mousemove to show indicators
                __this.on('mousemove', function (e) {
                    if (!$('.is-dragging').length) return;

                    const wrapper = $(this).find('.mddr_folder_btn_wrapper'),
                        offsetY = e.pageY - $(this).offset().top,
                        height = $(this).outerHeight();

                    $(this).find('.mddr_drop_indicator').hide();
                    wrapper.removeClass('is-nest-hover');

                    if (offsetY < height * 0.25) {
                        $(this).find('.drop-top').show();
                    } else if (offsetY > height * 0.75) {
                        $(this).find('.drop-bottom').show();
                    } else {
                        wrapper.addClass('is-nest-hover');
                    }
                });
            });
        }

        applySortFromSettings(sortValue) {
            if (typeof wp !== 'undefined' && wp.hooks) {
                wp.hooks.doAction('mddrSortFolders', sortValue);
            }
        }

        reorderFolder(folderId, targetId, position) {
            mddr_media_library.admin.apiCall('reorder_folder', {
                folder_id: folderId,
                target_id: targetId,
                position: position
            })
                .then(data => {
                    this.refreshState(data);
                    mddr_media_library.admin.showToast(mddr_media_library.admin.getText('folderMoved', 'Folder reordered successfully'));
                })
                .catch(err => mddr_media_library.admin.showToast(err, 'error'));
        }

        getDraggedMediaIds(ui) {
            // Grid view
            if (ui.draggable.hasClass('attachment')) {
                const selected = $('.attachments .attachment.selected').map((_, el) => parseInt($(el).data('id'))).get();
                return selected.length ? selected : [parseInt(ui.draggable.data('id'))];
            }

            // List view
            if (ui.draggable.is('tr')) {
                const checked = $('#the-list input[type="checkbox"]:checked');

                if (checked.length) {
                    return checked.map((_, el) =>
                        parseInt($(el).closest('tr').attr('id').replace('post-', ''))
                    ).get();
                }
                return [parseInt(ui.draggable.attr('id').replace('post-', ''))];
            }
            return [];
        }

        assignMediaToFolder(folderId, ids) {
            const admin = mddr_media_library.admin,
                sidebar = admin.sidebar;

            sidebar.find('.mddr_tree_loader').prop('hidden', false);

            admin.apiCall('assign_media', {
                folder_id: folderId,
                attachment_ids: ids
            })
                .then(data => {
                    this.refreshState(data);
                    admin.showToast(admin.getText('itemMoved'));

                    const active = admin.state.activeFolder,
                        isCurrent = (active === 'uncategorized' && folderId === 0) || (active === 'term-' + folderId);

                    if (isCurrent || active === 'all') return;

                    ids.forEach(id => {
                        $(`.attachments .attachment[data-id="${id}"]`).remove();

                        $(`#the-list tr#post-${id}`).fadeOut(300, function () {
                            $(this).remove();

                            if (!$('#the-list tr').length) {
                                $('#the-list').html(
                                    '<tr class="no-items"><td colspan="7">No items found.</td></tr>'
                                );
                            }
                        });
                    });
                })
                .catch(err => admin.showToast(err, 'error'))
                .finally(() => {
                    sidebar.find('.mddr_tree_loader').prop('hidden', true);
                });
        }

        getFilteredTree() {
            if (!mddr_media_library.admin.state.searchTerm) return mddr_media_library.admin.state.folders;
            return mddr_media_library.admin.state.searchResults;
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

        performSearch(term) {

            if (mddr_media_library.admin.state.folders && mddr_media_library.admin.state.folders.length) {
                const results = this.searchLocalFolders(term, mddr_media_library.admin.state.folders);
                mddr_media_library.admin.state.searchResults = results;
                mddr_media_library.admin.renderSidebar();
                return;
            }

            const url = mddr_media_library.restUrl + 'folders';
            $.ajax({
                url: url,
                method: 'GET',
                data: {
                    search: term,
                    nonce: mddr_media_library.nonce
                },
                success: (res) => {
                    if (res && res.success && res.data && res.data.folders) {
                        mddr_media_library.admin.state.searchResults = res.data.folders;
                        mddr_media_library.admin.renderSidebar();
                    }
                },
            });
        }

        searchLocalFolders(term, nodes) {
            let results = [];
            for (const node of nodes) {
                if (node.name.toLowerCase().includes(term)) {
                    results.push({
                        ...node,
                        children: []
                    });
                }
                if (node.children && node.children.length) {
                    results = results.concat(this.searchLocalFolders(term, node.children));
                }
            }
            return results;
        }

        handleContextNewFolder(parentId) {
            mddr_media_library.admin.state.activeFolder = 'term-' + parentId;
            this.highlightActive();
            this.updateActionButtons();
            mddr_media_library.admin.toggleNewFolderForm(true);
        }

        handleContextRename(folderId) {
            mddr_media_library.admin.state.activeFolder = 'term-' + folderId;
            this.highlightActive();
            this.updateActionButtons();
            const folder = this.findFolderById(folderId, mddr_media_library.admin.state.folders);
            if (folder) mddr_media_library.admin.startInlineRename(folder);
        }

        handleContextCut(folderId) {
            mddr_media_library.admin.clipboard = { action: 'cut', folderId: folderId };
            mddr_media_library.admin.sidebar.find('.mddr_folder_button').removeClass('is-cut');
            mddr_media_library.admin.sidebar.find(`.mddr_folder_button[data-folder-id="${folderId}"]`).addClass('is-cut');
        }

        handleContextPaste(targetFolderId) {
            if (!mddr_media_library.admin.clipboard.folderId || mddr_media_library.admin.clipboard.action !== 'cut') return;

            const __this = mddr_media_library.admin.clipboard.folderId;
            if (__this === targetFolderId) {
                mddr_media_library.admin.showToast(mddr_media_library.admin.getText('moveSelf'));
                return;
            }

            if (this.childFolder(targetFolderId, __this)) {
                mddr_media_library.admin.showToast(mddr_media_library.admin.getText('moveSubfolder'));
                return;
            }
            this.moveFolderToParent(__this, targetFolderId);
        }

        handleContextDelete(folderId) {
            mddr_media_library.admin.state.activeFolder = 'term-' + folderId;
            const folder = this.findFolderById(folderId, mddr_media_library.admin.state.folders);
            if (folder) mddr_media_library.admin.openDeleteDialog(folder);
        }

        childFolder(childId, parentId, nodes = null) {
            if (nodes === null) nodes = mddr_media_library.admin.state.folders;

            const parent = this.findFolderById(parentId, nodes);
            if (!parent || !parent.children) return false;

            for (const child of parent.children) {
                if (child.id == childId) return true;
                if (this.childFolder(childId, child.id, [child])) return true;
            }
            return false;
        }

        moveFolderToParent(folderId, newParentId) {
            mddr_media_library.admin.apiCall('move_folder', { folder_id: folderId, new_parent: newParentId })
                .then(data => {
                    this.refreshState(data);
                    mddr_media_library.admin.clipboard = { action: null, folderId: null };
                    mddr_media_library.admin.sidebar.find('.mddr_folder_button').removeClass('is-cut');
                    mddr_media_library.admin.showToast(mddr_media_library.admin.getText('folderMoved'));
                })
                .catch(err => mddr_media_library.admin.showToast(err, 'error'));
        }

        applyIconColor(icon, hex) {
            if (!icon || !icon.length) return;

            if (!hex) {
                icon.each(function () {
                    $(this)[0].style.setProperty('filter', '', '');
                });
                return;
            }

            const filters = {
                '#f44336': 'invert(37%) sepia(94%) saturate(4522%) hue-rotate(346deg) brightness(97%) contrast(92%)',
                '#ff5722': 'invert(46%) sepia(99%) saturate(2256%) hue-rotate(345deg) brightness(101%) contrast(101%)',
                '#ff9800': 'invert(62%) sepia(98%) saturate(1455%) hue-rotate(3deg) brightness(106%) contrast(102%)',
                '#ffc107': 'invert(84%) sepia(21%) saturate(6926%) hue-rotate(360deg) brightness(105%) contrast(103%)',
                '#1a237e': 'invert(18%) sepia(82%) saturate(3600%) hue-rotate(225deg) brightness(78%) contrast(95%)',
                '#311b92': 'invert(13%) sepia(78%) saturate(4200%) hue-rotate(250deg) brightness(80%) contrast(95%)',
                '#2196f3': 'invert(52%) sepia(61%) saturate(3015%) hue-rotate(185deg) brightness(97%) contrast(96%)',
                '#03a9f4': 'invert(53%) sepia(94%) saturate(2035%) hue-rotate(169deg) brightness(101%) contrast(101%)',
                '#4caf50': 'invert(62%) sepia(8%) saturate(2506%) hue-rotate(71deg) brightness(98%) contrast(85%)',
                '#8bc34a': 'invert(75%) sepia(20%) saturate(1212%) hue-rotate(44deg) brightness(98%) contrast(83%)',
                '#673ab7': 'invert(22%) sepia(87%) saturate(3226%) hue-rotate(256deg) brightness(84%) contrast(91%)',
                '#9c27b0': 'invert(21%) sepia(87%) saturate(4422%) hue-rotate(279deg) brightness(86%) contrast(95%)',
                '#b39ddb': 'invert(74%) sepia(16%) saturate(1001%) hue-rotate(218deg) brightness(94%) contrast(86%)',
                '#e91e63': 'invert(22%) sepia(99%) saturate(4051%) hue-rotate(329deg) brightness(93%) contrast(98%)',
                '#f06292': 'invert(69%) sepia(48%) saturate(3665%) hue-rotate(303deg) brightness(99%) contrast(93%)',
                '#3e2723': 'invert(18%) sepia(25%) saturate(900%) hue-rotate(10deg) brightness(70%) contrast(90%)',
                '#9e9e9e': 'invert(67%) sepia(0%) saturate(103%) hue-rotate(187deg) brightness(95%) contrast(88%)',
                '#000000': 'invert(0%) sepia(0%) saturate(0%) hue-rotate(0deg) brightness(0%) contrast(100%)'
            };

            const filter = filters[hex.toLowerCase()] || '';
            icon.each(function () {
                $(this)[0].style.setProperty('filter', filter, filter ? 'important' : '');
            });
        }

    }

    window.mddr_media_folder = window.mddr_media_folder || {};
    window.mddr_media_folder.folder = new MDDR_Media_Folder();
});
