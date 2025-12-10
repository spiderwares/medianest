
'use strict';

jQuery(function ($) {
    class WPMN_Upload_Folder {

        constructor() {
            this.init();
        }

        init() {
            this.select = $('#wpmn_select_upload_folder');
            this.wrapper = $('#wpmn_upload_folder_selector');
            this.ajaxUrl = wpmnUploadSelector.ajaxUrl;
            this.nonce = wpmnUploadSelector.nonce;
            this.loadFolders();
            this.bindEvents();
        }

        bindEvents() {
            $(document).on('change', '#wpmn_select_upload_folder', (e) => {
                localStorage.setItem('wpmn_selected_upload_folder', this.select.val());
            });
        }

        loadFolders() {
            $.post(
                this.ajaxUrl,
                {
                    action: 'wpmn_get_folders_for_upload',
                    nonce: this.nonce
                },
                (response) => {
                    if (response?.success && response.data?.folders) {
                        this.populateDropdown(response.data.folders);
                        this.restoreSelection();
                        this.wrapper.show();
                    }
                }
            );
        }

        populateDropdown(folders) {
            folders.forEach((folder) => {
                this.select.append(this.optionHTML(folder.id, folder.name));
                this.addChildFolders(folder.children, '-');
            });
        }

        addChildFolders(children, indent) {
            if (!children?.length) return;

            children.forEach((child) => {
                this.select.append(this.optionHTML(child.id, indent + child.name));
                if (child.children?.length) {
                    this.addChildFolders(child.children, indent + '-');
                }
            });
        }

        optionHTML(id, name) {
            return `<option value="term-${id}">${name}</option>`;
        }

        restoreSelection() {
            const saved = localStorage.getItem('wpmn_selected_upload_folder');
            if (saved) {
                this.select.val(saved);
            }
        }
    }

    new WPMN_Upload_Folder();
});
