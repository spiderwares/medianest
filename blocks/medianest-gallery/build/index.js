(function (wp) {
    var registerBlockType = wp.blocks.registerBlockType,
        el = wp.element.createElement,
        __ = wp.i18n.__,
        InspectorControls = wp.blockEditor.InspectorControls,
        useBlockProps = wp.blockEditor.useBlockProps,
        PanelBody = wp.components.PanelBody,
        SelectControl = wp.components.SelectControl,
        ToggleControl = wp.components.ToggleControl,
        RangeControl = wp.components.RangeControl,
        Spinner = wp.components.Spinner,
        ServerSideRender = wp.serverSideRender,
        useState = wp.element.useState,
        useEffect = wp.element.useEffect,
        apiFetch = wp.apiFetch;

    registerBlockType('medianest/block-medianest-gallery', {
        title: 'MediaNest Gallery',
        icon: 'images-alt2',
        category: 'common',
        attributes: {
            selectedFolder: { type: 'array', default: [] },
            columns: { type: 'integer', default: 3 },
            isCropped: { type: 'boolean', default: true },
            hasCaption: { type: 'boolean', default: false },
            hasLightbox: { type: 'boolean', default: false },
            layout: { type: 'string', default: 'flex' },
            linkTo: { type: 'string', default: 'none' },
            sortBy: { type: 'string', default: 'date' },
            sortType: { type: 'string', default: 'DESC' },
        },
        edit: function (props) {
            var attributes = props.attributes,
                setAttributes = props.setAttributes,
                [rawFolders, setRawFolders] = useState([]),
                [isLoading, setIsLoading] = useState(true),
                [isOpen, setIsOpen] = useState(false);

            // Fetch raw folder tree
            useEffect(function () {
                apiFetch({ path: '/medianest/v1/folders' })
                    .then(function (response) {
                        if (response.success && response.data.folders) {
                            setRawFolders(response.data.folders);
                        }
                    })
                    .finally(function () {
                        setIsLoading(false);
                    });
            }, []);

            // Helper to find folder name by ID
            function getFolderName(id, nodes) {
                function findNode(searchId, currentNodes) {
                    if (!currentNodes) return null;
                    for (var i = 0; i < currentNodes.length; i++) {
                        if (currentNodes[i].id == searchId) return currentNodes[i].text;
                        if (currentNodes[i].children) {
                            var found = findNode(searchId, currentNodes[i].children);
                            if (found) return found;
                        }
                    }
                    return null;
                }

                var name = findNode(id, nodes);
                return name ? name : 'Folder ' + id;
            }

            // Recursive Tree Renderer
            function FolderTree({ nodes, depth = 0 }) {
                return nodes.map(function (node) {
                    var isChecked = attributes.selectedFolder.includes(node.id);

                    return el('div', { key: node.id, style: { marginLeft: (depth * 20) + 'px' } },
                        el('div', {
                            className: 'wpmn_tree_item',
                            onClick: function () {
                                var newSelected;
                                if (isChecked) {
                                    newSelected = attributes.selectedFolder.filter(function (id) { return id !== node.id; });
                                } else {
                                    newSelected = [...attributes.selectedFolder, node.id];
                                }
                                setAttributes({ selectedFolder: newSelected });
                            }
                        },
                            el('input', {
                                type: 'checkbox',
                                checked: isChecked,
                                readOnly: true
                            }),
                            el('span', { className: 'dashicons dashicons-category' }),
                            el('span', null, node.text)
                        ),
                        node.children && node.children.length > 0 && el(FolderTree, { nodes: node.children, depth: depth + 1 })
                    );
                });
            }

            return el(wp.element.Fragment, null,
                el(InspectorControls, null,
                    el(PanelBody, { title: __('Gallery Settings', 'medianest') },

                        // Custom Tree Select Trigger
                        el('label', { className: 'components-base-control__label' }, __('Folders', 'medianest')),
                        el('div', {
                            className: 'components-base-control__field wpmn_select_container'
                        },
                            el('div', {
                                className: 'wpmn_select_trigger',
                                onClick: function () { setIsOpen(!isOpen); }
                            },
                                attributes.selectedFolder.length === 0 && el('span', { className: 'wpmn-placeholder' }, __('Select folder', 'medianest')),
                                attributes.selectedFolder.map(function (id) {
                                    return el('div', {
                                        key: id,
                                        className: 'wpmn_selected_tag'
                                    },
                                        el('span', null, getFolderName(id, rawFolders)),
                                        el('span', {
                                            className: 'dashicons dashicons-dismiss',
                                            onClick: function (e) {
                                                e.stopPropagation();
                                                setAttributes({ selectedFolder: attributes.selectedFolder.filter(function (i) { return i !== id; }) });
                                            }
                                        })
                                    );
                                }),
                                el('span', {
                                    className: 'dashicons ' + (isOpen ? 'dashicons-arrow-up-alt2' : 'dashicons-arrow-down-alt2') + ' wpmn_arrow'
                                })
                            ),
                            // Dropdown Content
                            isOpen && el('div', {
                                className: 'wpmn_select_dropdown'
                            },
                                isLoading ? el(Spinner) : (rawFolders.length > 0 ? el(FolderTree, { nodes: rawFolders }) : el('div', { className: 'wpmn_no_folders' }, __('No folders found', 'medianest')))
                            )
                        ),

                        el(RangeControl, {
                            label: __('Columns', 'medianest'),
                            value: attributes.columns,
                            onChange: function (val) { setAttributes({ columns: val }); },
                            min: 1, max: 6
                        }),

                        el(ToggleControl, {
                            label: __('Crop Images', 'medianest'),
                            checked: attributes.isCropped,
                            onChange: function (val) { setAttributes({ isCropped: val }); },
                            help: __('Thumbnails are cropped to align.', 'medianest')
                        }),

                        el(ToggleControl, {
                            label: __('Caption', 'medianest'),
                            checked: attributes.hasCaption,
                            onChange: function (val) { setAttributes({ hasCaption: val }); },
                            help: __('Display image caption', 'medianest')
                        }),

                        el(ToggleControl, {
                            label: __('Add Lightbox', 'medianest'),
                            checked: attributes.hasLightbox,
                            onChange: function (val) { setAttributes({ hasLightbox: val }); }
                        }),

                        el(SelectControl, {
                            label: __('Layout', 'medianest'),
                            value: attributes.layout,
                            options: [
                                { label: 'Flex', value: 'flex' },
                                { label: 'Grid', value: 'grid' },
                                { label: 'Masonry', value: 'masonry' },
                                { label: 'Carousel', value: 'carousel' }
                            ],
                            onChange: function (val) { setAttributes({ layout: val }); }
                        }),

                        el(SelectControl, {
                            label: __('Link To', 'medianest'),
                            value: attributes.linkTo,
                            options: [
                                { label: 'None', value: 'none' },
                                { label: 'Media File', value: 'media' },
                                { label: 'Attachment Page', value: 'attachment' }
                            ],
                            onChange: function (val) { setAttributes({ linkTo: val }); }
                        }),

                        el(SelectControl, {
                            label: __('Sort By', 'medianest'),
                            value: attributes.sortBy,
                            options: [
                                { label: 'By Name', value: 'name' },
                                { label: 'By Date', value: 'date' },
                                { label: 'By Modified', value: 'modified' },
                                { label: 'By Author', value: 'author' },
                                { label: 'By Title', value: 'title' },
                                { label: 'By File Name', value: 'file_name' }
                            ],
                            onChange: function (val) { setAttributes({ sortBy: val }); }
                        }),

                        el(SelectControl, {
                            label: __('Sort Type', 'medianest'),
                            value: attributes.sortType,
                            options: [
                                { label: 'Ascending', value: 'ASC' },
                                { label: 'Descending', value: 'DESC' }
                            ],
                            onChange: function (val) { setAttributes({ sortType: val }); }
                        })
                    )
                ),
                el('div', useBlockProps(),
                    attributes.selectedFolder.length === 0 ?
                        el('div', { className: 'components-placeholder is-large' },
                            el('div', { className: 'components-placeholder__label' }, __('MediaNest Gallery', 'medianest')),
                            el('div', { className: 'components-placeholder__instructions' }, __('Please select folders from the block settings.', 'medianest'))
                        ) :
                        el('div', {
                            className: 'wpmn_editor_preview',
                            onClick: function (event) {
                                event.preventDefault();
                            }
                        },
                            el(ServerSideRender, {
                                block: 'medianest/block-medianest-gallery',
                                attributes: attributes
                            })
                        )
                )
            );
        },
        save: function () {
            return null; // Rendered via PHP
        }
    });
})(window.wp);
