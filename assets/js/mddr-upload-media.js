'use strict';

jQuery(function ($) {

    class MDDR_Upload_Media {

        constructor() {
            this.init();
        }

        init() {
            this.folders = [];
            this.bindEvents();
            this.observeDOM();
            this.loadFolders();
            this.bindUploader();
        }

        bindEvents() {
            $(document.body).on('change', '.mddr_select_upload_folder', this.handleFolderChange.bind(this));
        }

        handleFolderChange(e) {
            const folderId = $(e.currentTarget).val();

            $('.mddr_select_upload_folder').val(folderId);
            localStorage.setItem('mddr_selected_upload_folder', folderId);

            this.uploaderParams(folderId);
            this.browserParams(folderId);
        }

        observeDOM() {
            const observer = new MutationObserver((mutations) => {
                mutations.forEach(({ addedNodes }) => {
                    if (!addedNodes.length) return;

                    const dropdowns = $(addedNodes).find('.mddr_select_upload_folder');
                    if (dropdowns.length) {
                        this.populateDropdowns(dropdowns);
                        this.restoreSelection();
                    }
                });
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }

        loadFolders(callback) {

            $.ajax({
                type: 'POST',
                url: mddr_media_library.ajaxUrl,
                data: {
                    action: 'mddr_get_folders_for_upload',
                    nonce: mddr_media_library.nonce
                },
                success: (response) => {
                    if (response?.success && response.data?.folders) {

                        this.folders = response.data.folders;

                        this.populateDropdowns($('.mddr_select_upload_folder'));
                        this.restoreSelection();
                        $('.mddr_upload_folder_selector').show();

                        if (typeof callback === 'function') {
                            callback(true, response.data.folders);
                        }

                    } else {
                        if (typeof callback === 'function') {
                            callback(false, []);
                        }
                    }
                }
            });
        }

        populateDropdowns(elements) {
            if (!this.folders.length) return;

            const optionsHtml = this.buildOptionsHtml(this.folders);

            elements.each((_, el) => {
                const select = $(el);
                if (select.find('option').length <= 2) {
                    select.append(optionsHtml);
                }
            });
        }

        buildOptionsHtml(items, indent = '') {
            let html = '';

            items.forEach(item => {
                html += `<option value="${item.id}">${indent}${item.name}</option>`;
                if (item.children?.length) {
                    html += this.buildOptionsHtml(item.children, indent + '-');
                }
            });

            return html;
        }

        restoreSelection() {
            const saved = localStorage.getItem('mddr_selected_upload_folder');
            if (saved) {
                $('.mddr_select_upload_folder').val(saved);
                this.browserParams(saved);
            }
        }

        bindUploader() {

            const bindParams = (__this) => {
                if (__this._mddrBound) return;
                __this._mddrBound = true;

                const setParams = (uploader) => {
                    const folderId = $('.mddr_select_upload_folder:visible').val() || localStorage.getItem('mddr_selected_upload_folder');

                    if (folderId) {
                        uploader.settings.multipart_params = uploader.settings.multipart_params || {};
                        uploader.settings.multipart_params.mddr_upload_folder = folderId;
                        uploader.settings.multipart_params.nonce = mddr_media_library.nonce;
                    }
                };

                __this.bind('FilesAdded', setParams);
                __this.bind('BeforeUpload', setParams);
            };

            // Plupload
            if (window.plupload?.Uploader) {
                const originalInit = plupload.Uploader.prototype.init;
                plupload.Uploader.prototype.init = function () {
                    originalInit.apply(this, arguments);
                    bindParams(this);
                };
            }

            // WP Uploader
            if (window.wp?.Uploader) {
                const originalInit = wp.Uploader.prototype.init;
                wp.Uploader.prototype.init = function () {
                    originalInit.apply(this, arguments);
                    if (this.uploader) {
                        bindParams(this.uploader);
                    }
                };

                if (wp.Uploader.queue) {
                    wp.Uploader.queue.each((uploaderWrapper) => {
                        const up = uploaderWrapper.uploader || uploaderWrapper;
                        bindParams(up);
                    });
                }
            }

            if (window.uploader) {
                bindParams(window.uploader);
            }
        }

        uploaderParams(folderId) {
            if (window.uploader) {
                uploader.settings.multipart_params = uploader.settings.multipart_params || {};
                uploader.settings.multipart_params.mddr_upload_folder = folderId;
                uploader.settings.multipart_params.nonce = mddr_media_library.nonce;
            }
        }

        browserParams(folderId) {
            const form = $('#file_form, form.media-upload-form');

            if (!form.length) return;
            let input = form.find('input[name="mddr_upload_folder"]');
            if (!input.length) {
                input = $('<input>', {
                    type: 'hidden',
                    name: 'mddr_upload_folder'
                }).prependTo(form);

                $('<input>', {
                    type: 'hidden',
                    name: 'nonce',
                    value: mddr_media_library.nonce
                }).prependTo(form);
            }

            input.val(folderId);
        }

    }

    new MDDR_Upload_Media();

});
