'use strict';

jQuery(function ($) {

    class MDDR_Admin {

        constructor() {
            this.init();
        }

        init() {
            this.bindEvents();
        }

        bindEvents() {
            $(document.body).on('click', '.mddr_clear_data_btn', this.handleClearData.bind(this))
            $(document.body).on('click', '.mddr_import_btn', this.handleImport.bind(this));
            $(document.body).on('click', '.mddr_export_btn', this.handleExport.bind(this));
            $(document.body).on('click', '.mddr_generate_size_btn', this.handleGenerateSize.bind(this));
            $(document.body).on('click', '.mddr_generate_api_btn', this.handleGenerateApiKey.bind(this));
            $(document.body).on('change', '.mddr_folder_dropdown', this.handleAttachmentFolderChange.bind(this));
        }

        handleClearData(e) {
            e.preventDefault();
            if (!confirm(mddr_media_library.admin.getText('confirmClearData'))) return;
            const __this = $(e.currentTarget).prop('disabled', true);
            mddr_media_library.admin.apiCall(__this.data('action')).then(() => {
                mddr_media_library.admin.showToast(mddr_media_library.admin.getText('allDataCleared'));
                location.reload();
            }).catch(msg => {
                mddr_media_library.admin.showToast(mddr_media_library.admin.getText('errorPrefix') + msg, 'error');
                __this.prop('disabled', false);
            });
        }

        handleImport(e) {
            e.preventDefault();
            const __this = $(e.currentTarget),
                input = $('#mddr_import_file'),
                file = input[0].files[0];

            if (!file) return mddr_media_library.admin.showToast(mddr_media_library.admin.getText('selectCsvFile'), 'error');
            if (__this.prop('disabled')) return;

            __this.prop('disabled', true).text('Importing...');
            const formData = new FormData();
            formData.append('csv_file', file);

            mddr_media_library.admin.apiCall(__this.data('action'), formData).then(res => {
                __this.prop('disabled', false).text('Import Now');
                mddr_media_library.admin.showToast(mddr_media_library.admin.getText('foldersImported'));
                input.val('');
            }).catch(msg => {
                __this.prop('disabled', false).text('Import Now');
                mddr_media_library.admin.showToast(mddr_media_library.admin.getText('errorPrefix') + msg, 'error');
            });
        }

        handleExport(e) {
            e.preventDefault();

            const __this = $(e.currentTarget),
                action = __this.data('action'),
                originalText = __this.text();

            if (__this.prop('disabled')) return;
            __this.prop('disabled', true).text('Exporting...')
            try {
                const form = mddr_media_library.admin.createForm(action);
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
            mddr_media_library.admin.apiCall(__this.data('action')).then(res => {
                __this.prop('disabled', false).text(originalText);
                mddr_media_library.admin.showToast(mddr_media_library.admin.getText('sizesGenerated'));
            }).catch(err => {
                __this.prop('disabled', false).text(originalText);
            });
        }

        handleGenerateApiKey(e) {
            e.preventDefault();
            const __this = $(e.currentTarget);

            if (__this.prop('disabled')) return;
            __this.prop('disabled', true).text('Generating...');

            mddr_media_library.admin.apiCall('mddr_generate_api_key').then(res => {
                __this.prop('disabled', false).text('Generate');
                $('.mddr_api_key_input').val(res.key);
                mddr_media_library.admin.showToast(mddr_media_library.admin.getText('apiKeyGenerated'));
            }).catch(msg => {
                __this.prop('disabled', false).text('Generate');
                mddr_media_library.admin.showToast(mddr_media_library.admin.getText('errorPrefix') + msg, 'error');
            });
        }

        handleAttachmentFolderChange(e) {
            const __this = $(e.currentTarget),
                folderId = __this.val(),
                attachmentId = __this.attr('id').split('_').pop(),
                loader = __this.next('.mddr_folder_loader');

            loader.addClass('is-active').css('visibility', 'visible');
            __this.prop('disabled', true);

            mddr_media_library.admin.apiCall('assign_media', {
                folder_id: folderId,
                attachment_ids: [attachmentId],
            })
                .then(res => {
                    mddr_media_library.admin.showToast(mddr_media_library.admin.getText('itemMoved'));
                    mddr_media_folder.folder.refreshState(res);

                    const active = mddr_media_library.admin.state.activeFolder;
                    const inCurrentFolder =
                        (active === 'uncategorized' && folderId == 0) ||
                        active === `term-${folderId}`;

                    if (active !== 'all' && !inCurrentFolder) {
                        $(`.attachments .attachment[data-id="${attachmentId}"]`).remove();

                        try {
                            const __this = wp?.media?.frame?.state()?.get('library'),
                                model = __this?.get(attachmentId);
                            if (model) __this.remove(model);
                        } catch (e) { }
                    }
                })
                .catch(msg => mddr_media_library.admin.showToast(mddr_media_library.admin.getText('errorPrefix') + msg, 'error'))
                .finally(() => {
                    loader.removeClass('is-active').css('visibility', 'hidden');
                    __this.prop('disabled', false);
                });
        }

    }

    new MDDR_Admin();

});
