'use strict';

jQuery(function ($) {

    class WPMN_Media_Folder {

        highlightActive() {
            wpmn_admin_media.admin.sidebar.find('.is-active').removeClass('is-active');
            const btn = wpmn_admin_media.admin.sidebar.find(`.wpmn_folder_button[data-folder-slug="${wpmn_admin_media.admin.state.activeFolder}"]`).addClass('is-active');
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
                const labelText = (wpmn_admin_media.admin.showFolderId ? `#${node.id} ` : '') + node.name;
                btn.find('.wpmn_folder_button__label').text(labelText);

                row.append(arrow, btn);
                li.append(row);
                if (node.children?.length) li.append(this.buildTreeList(node.children));
                ul.append(li);
            });
            return ul;
        }

        setupDroppableTargets() {
            wpmn_admin_media.admin.sidebar.find('.wpmn_folder_button').each((i, el) => {
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
                        if (!ids.length) return alert(wpmn_admin_media.admin.getText('noSelection'));
                        this.assignMediaToFolder(folderId, ids);
                    }
                });
            });
        }

        assignMediaToFolder(folderId, ids) {
            wpmn_admin_media.admin.sidebar.find('.wpmn_tree_loader').prop('hidden', false);
            wpmn_admin_media.admin.apiCall('assign_media', { folder_id: folderId, attachment_ids: ids }).then(data => {
                this.refreshState(data);
                wpmn_admin_media.admin.showToast(wpmn_admin_media.admin.getText('itemMoved'));

                // Remove items if moving out of current folder
                const isCurrentFolder = (wpmn_admin_media.admin.state.activeFolder === 'uncategorized' && folderId === 0) ||
                    (wpmn_admin_media.admin.state.activeFolder === 'term-' + folderId);

                if (!isCurrentFolder && wpmn_admin_media.admin.state.activeFolder !== 'all') {
                    ids.forEach(id => $('.attachments .attachment[data-id="' + id + '"]').remove());
                }
            }).catch(alert).finally(() => wpmn_admin_media.admin.sidebar.find('.wpmn_tree_loader').prop('hidden', true));
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
                data: { search: term },
                beforeSend: (xhr) => {
                    xhr.setRequestHeader('X-WP-Nonce', wpmn_media_library.restNonce);
                    wpmn_admin_media.admin.sidebar.find('.wpmn_folder_tree').addClass('wpmn_loading');
                },
                success: (res) => {
                    if (res && res.success && res.data && res.data.folders) {
                        wpmn_admin_media.admin.state.searchResults = res.data.folders;
                        wpmn_admin_media.admin.renderSidebar();
                    }
                },
                error: (err) => console.error('Search failed', err),
                complete: () => {
                    wpmn_admin_media.admin.sidebar.find('.wpmn_folder_tree').removeClass('wpmn_loading');
                }
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

    }

    if (!window.wpmn_media_folder) {
        window.wpmn_media_folder = {};
    }

    // Attach folder instance to global namespace
    window.wpmn_media_folder.folder = new WPMN_Media_Folder();
});
