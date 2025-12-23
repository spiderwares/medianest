'use strict';

jQuery(function ($) {

    class WPMN_Media_Folder {

        highlightActive() {
            const sidebar = wpmn_admin_media.admin.sidebar;
            sidebar.find('.wpmn_folder_button.is-active').removeClass('is-active');
            sidebar.find('.wpmn_folder_icon.is-active, .wpmn_all_files_icon.is-active, .wpmn_uncategorized_icon.is-active').removeClass('is-active');

            const btn = sidebar.find(`.wpmn_folder_button[data-folder-slug="${wpmn_admin_media.admin.state.activeFolder}"]`).addClass('is-active');
            btn.find('.wpmn_folder_icon, .wpmn_all_files_icon, .wpmn_uncategorized_icon').addClass('is-active');
        }

        updateActionButtons() {
            const isSpecial = ['all', 'uncategorized'].includes(wpmn_admin_media.admin.state.activeFolder);
            wpmn_admin_media.admin.sidebar.find('.wpmn_media_sidebar_action_rename, .wpmn_media_sidebar_action_delete')
                .prop('disabled', isSpecial).toggleClass('disabled', isSpecial);
        }

        updateFolderIdVisibility() {
            wpmn_admin_media.admin.sidebar.find('.wpmn_folder_button[data-folder-id]').each((i, el) => {
                const btn = $(el), id = btn.data('folder-id'), name = btn.data('folder-name');
                btn.find('.wpmn_folder_button__label').text((wpmn_admin_media.admin.showFolderId ? `#${id} ` : '') + name);
            });
        }

        refreshState(data) {
            wpmn_admin_media.admin.state.folders = data.folders || [];
            wpmn_admin_media.admin.state.counts = data.counts || {};

            // Validate active folder
            if (wpmn_admin_media.admin.state.activeFolder.startsWith('term-')) {
                const id = wpmn_admin_media.admin.state.activeFolder.replace('term-', '');
                if (!this.findFolderById(id, wpmn_admin_media.admin.state.folders)) {
                    wpmn_admin_media.admin.state.activeFolder = 'all';
                    wpmn_admin_media.admin.setStorage('wpmnActiveFolder', 'all');
                }
            }
            wpmn_admin_media.admin.renderSidebar();

            // Trigger hook for Pro version
            if (typeof wp !== 'undefined' && wp.hooks) {
                wp.hooks.doAction('wpmnFoldersLoaded');
            }
        }

        buildTreeList(nodes) {
            const expanded = JSON.parse(
                wpmn_admin_media.admin.getStorage('wpmnExpandedFolders', '{}')
            );

            const ul = $('<ul role="group"></ul>');

            nodes.forEach(node => {
                const isExpanded = !!expanded[node.id];
                const hasChildren = !!node.children?.length;

                const li = $('<li>', {
                    class: 'wpmn_folder_node',
                    role: 'treeitem',
                    'aria-expanded': isExpanded
                });

                const arrow = $('<span class="wpmn_toggle_arrow">')
                    .toggleClass('has-children', hasChildren);

                const btn = $(`
                    <button type="button" class="wpmn_folder_button"
                        data-folder-slug="term-${node.id}"
                        data-folder-id="${node.id}"
                        data-folder-name="${node.name}"
                        data-color="${node.color || ''}">
                        <input type="checkbox" class="wpmn_folder_checkbox" value="${node.id}">
                        <img src="${wpmn_media_library.baseUrl || ''}assets/img/folder.svg"
                            class="wpmn_folder_icon" aria-hidden="true">
                        <span class="wpmn_folder_button__label"></span>
                        <span class="wpmn_count">${node.count || 0}</span>
                    </button>
                `);

                const icon = btn.find('.wpmn_folder_icon');
                this.applyIconColor(icon, node.color || '');

                btn.find('.wpmn_folder_button__label').text(
                    (wpmn_admin_media.admin.showFolderId ? `#${node.id} ` : '') + node.name
                );

                li.append(
                    $('<div class="wpmn_folder_row"></div>').append(arrow, btn)
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
            const item = wpmn_admin_media.admin.sidebar.find('.wpmn_more_menu_item[data-action="hide-folder-id"]');
            const icon = item.find('.dashicons');
            const label = item.find('span:not(.dashicons)');

            if (wpmn_admin_media.admin.showFolderId) {
                label.text(item.data('text-hide'));
                icon.removeClass(item.data('icon-show')).addClass(item.data('icon-hide'));
            } else {
                label.text(item.data('text-show'));
                icon.removeClass(item.data('icon-hide')).addClass(item.data('icon-show'));
            }
        }

        setupDroppableTargets() {
            const sidebar = wpmn_admin_media.admin.sidebar;

            sidebar.find('.wpmn_folder_button').each((_, el) => {
                const btn = $(el);
                const slug = btn.data('folder-slug');

                if (btn.hasClass('ui-droppable')) {
                    btn.droppable('destroy');
                }

                if (!slug || (slug !== 'uncategorized' && !slug.startsWith('term-'))) {
                    return;
                }

                btn.droppable({
                    accept: '.attachments .attachment, .wpmn_media_layout #the-list tr.wpmn_draggable',
                    hoverClass: 'is-drop-hover',
                    tolerance: 'pointer',
                    drop: (_, ui) => {
                        const folderId = slug.startsWith('term-')
                            ? parseInt(slug.replace('term-', ''), 10)
                            : 0;

                        const ids = this.getDraggedMediaIds(ui);

                        if (!ids.length) {
                            return alert(wpmn_admin_media.admin.getText('noSelection'));
                        }

                        this.assignMediaToFolder(folderId, ids);
                    }
                });
            });
        }

        getDraggedMediaIds(ui) {
            // Grid view
            if (ui.draggable.hasClass('attachment')) {
                const selected = $('.attachments .attachment.selected')
                    .map((_, el) => parseInt($(el).data('id')))
                    .get();

                return selected.length
                    ? selected
                    : [parseInt(ui.draggable.data('id'))];
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
            const admin = wpmn_admin_media.admin;
            const sidebar = admin.sidebar;

            sidebar.find('.wpmn_tree_loader').prop('hidden', false);

            admin.apiCall('assign_media', {
                folder_id: folderId,
                attachment_ids: ids
            })
                .then(data => {
                    this.refreshState(data);
                    admin.showToast(admin.getText('itemMoved'));

                    const active = admin.state.activeFolder;
                    const isCurrent =
                        (active === 'uncategorized' && folderId === 0) ||
                        (active === 'term-' + folderId);

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
                .catch(alert)
                .finally(() => {
                    sidebar.find('.wpmn_tree_loader').prop('hidden', true);
                });
        }

        getFilteredTree() {
            if (!wpmn_admin_media.admin.state.searchTerm) return wpmn_admin_media.admin.state.folders;
            return wpmn_admin_media.admin.state.searchResults;
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
            const url = wpmn_media_library.restUrl + 'folders';

            $.ajax({
                url: url,
                method: 'GET',
                data: {
                    search: term,
                    nonce: wpmn_media_library.nonce
                },
                success: (res) => {
                    if (res && res.success && res.data && res.data.folders) {
                        wpmn_admin_media.admin.state.searchResults = res.data.folders;
                        wpmn_admin_media.admin.renderSidebar();
                    }
                },
            });
        }

        handleContextNewFolder(parentId) {
            wpmn_admin_media.admin.state.activeFolder = 'term-' + parentId;
            this.highlightActive();
            this.updateActionButtons();
            wpmn_admin_media.admin.toggleNewFolderForm(true);
        }

        handleContextRename(folderId) {
            wpmn_admin_media.admin.state.activeFolder = 'term-' + folderId;
            this.highlightActive();
            this.updateActionButtons();
            const folder = this.findFolderById(folderId, wpmn_admin_media.admin.state.folders);
            if (folder) wpmn_admin_media.admin.startInlineRename(folder);
        }

        handleContextCut(folderId) {
            wpmn_admin_media.admin.clipboard = { action: 'cut', folderId: folderId };
            wpmn_admin_media.admin.sidebar.find('.wpmn_folder_button').removeClass('is-cut');
            wpmn_admin_media.admin.sidebar.find(`.wpmn_folder_button[data-folder-id="${folderId}"]`).addClass('is-cut');
        }

        handleContextPaste(targetFolderId) {
            if (!wpmn_admin_media.admin.clipboard.folderId || wpmn_admin_media.admin.clipboard.action !== 'cut') {
                return;
            }

            const sourceFolderId = wpmn_admin_media.admin.clipboard.folderId;
            if (sourceFolderId === targetFolderId) {
                wpmn_admin_media.admin.showToast(wpmn_admin_media.admin.getText('moveSelf'));
                return;
            }

            if (this.childFolder(targetFolderId, sourceFolderId)) {
                wpmn_admin_media.admin.showToast(wpmn_admin_media.admin.getText('moveSubfolder'));
                return;
            }
            this.moveFolderToParent(sourceFolderId, targetFolderId);
        }

        handleContextDelete(folderId) {
            wpmn_admin_media.admin.state.activeFolder = 'term-' + folderId;
            const folder = this.findFolderById(folderId, wpmn_admin_media.admin.state.folders);
            if (folder) wpmn_admin_media.admin.openDeleteDialog(folder);
        }

        childFolder(childId, parentId, nodes = null) {
            if (nodes === null) nodes = wpmn_admin_media.admin.state.folders;

            const parent = this.findFolderById(parentId, nodes);
            if (!parent || !parent.children) return false;

            for (const child of parent.children) {
                if (child.id == childId) return true;
                if (this.childFolder(childId, child.id, [child])) return true;
            }
            return false;
        }

        moveFolderToParent(folderId, newParentId) {
            wpmn_admin_media.admin.apiCall('move_folder', { folder_id: folderId, new_parent: newParentId })
                .then(data => {
                    this.refreshState(data);
                    wpmn_admin_media.admin.clipboard = { action: null, folderId: null };
                    wpmn_admin_media.admin.sidebar.find('.wpmn_folder_button').removeClass('is-cut');
                    wpmn_admin_media.admin.showToast(wpmn_admin_media.admin.getText('folderMoved'));
                })
                .catch(alert);
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
                '#ffeb3b': 'invert(91%) sepia(45%) saturate(1113%) hue-rotate(3deg) brightness(107%) contrast(101%)',
                '#cddc39': 'invert(87%) sepia(19%) saturate(2321%) hue-rotate(34deg) brightness(103%) contrast(87%)',
                '#2196f3': 'invert(52%) sepia(61%) saturate(3015%) hue-rotate(185deg) brightness(97%) contrast(96%)',
                '#03a9f4': 'invert(53%) sepia(94%) saturate(2035%) hue-rotate(169deg) brightness(101%) contrast(101%)',
                '#e3f2fd': 'invert(96%) sepia(10%) saturate(676%) hue-rotate(186deg) brightness(105%) contrast(101%)',
                '#4caf50': 'invert(62%) sepia(8%) saturate(2506%) hue-rotate(71deg) brightness(98%) contrast(85%)',
                '#8bc34a': 'invert(75%) sepia(20%) saturate(1212%) hue-rotate(44deg) brightness(98%) contrast(83%)',
                '#aed581': 'invert(87%) sepia(16%) saturate(666%) hue-rotate(43deg) brightness(101%) contrast(84%)',
                '#673ab7': 'invert(22%) sepia(87%) saturate(3226%) hue-rotate(256deg) brightness(84%) contrast(91%)',
                '#9c27b0': 'invert(21%) sepia(87%) saturate(4422%) hue-rotate(279deg) brightness(86%) contrast(95%)',
                '#b39ddb': 'invert(74%) sepia(16%) saturate(1001%) hue-rotate(218deg) brightness(94%) contrast(86%)',
                '#e91e63': 'invert(22%) sepia(99%) saturate(4051%) hue-rotate(329deg) brightness(93%) contrast(98%)',
                '#f06292': 'invert(69%) sepia(48%) saturate(3665%) hue-rotate(303deg) brightness(99%) contrast(93%)',
                '#9e9e9e': 'invert(67%) sepia(0%) saturate(103%) hue-rotate(187deg) brightness(95%) contrast(88%)'
            };

            const filter = filters[hex.toLowerCase()] || '';
            icon.each(function () {
                $(this)[0].style.setProperty('filter', filter, filter ? 'important' : '');
            });
        }

    }

    if (!window.wpmn_media_folder) {
        window.wpmn_media_folder = {};
    }

    window.wpmn_media_folder.folder = new WPMN_Media_Folder();
});
