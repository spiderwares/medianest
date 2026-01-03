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

    registerBlockType('media-directory/block-media-directory-gallery', {
        title: 'Media Directory Gallery',
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
            imageHoverAnimation: { type: 'string', default: 'none' },
        },
        edit: function (props) {
            var attributes = props.attributes,
                setAttributes = props.setAttributes,
                [rawFolders, setRawFolders] = useState([]),
                [isLoading, setIsLoading] = useState(true),
                [isOpen, setIsOpen] = useState(false),
                [expandedFolders, setExpandedFolders] = useState([]);

            // Fetch raw folder tree
            useEffect(function () {
                apiFetch({ path: '/media-directory/v1/folders?post_type=attachment' })
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
                    var hasChildren = node.children && node.children.length > 0;
                    var isExpanded = expandedFolders.includes(node.id);

                    return el('div', { key: node.id, style: { marginLeft: (depth * 15) + 'px' } },
                        el('div', {
                            className: 'mddr_tree_item',
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
                            hasChildren ? el('span', {
                                className: 'dashicons ' + (isExpanded ? 'dashicons-arrow-down-alt2' : 'dashicons-arrow-right-alt2'),
                                style: { cursor: 'pointer', fontSize: '14px', lineHeight: 'inherit', marginRight: '2px', color: '#666' },
                                onClick: function (e) {
                                    e.stopPropagation();
                                    if (isExpanded) {
                                        setExpandedFolders(expandedFolders.filter(function (id) { return id !== node.id; }));
                                    } else {
                                        setExpandedFolders([...expandedFolders, node.id]);
                                    }
                                }
                            }) : el('span', { style: { width: '16px', display: 'inline-block', marginRight: '2px' } }),
                            el('input', {
                                type: 'checkbox',
                                checked: isChecked,
                                readOnly: true,
                            }),
                            el('span', {
                                className: 'dashicons dashicons-category',
                            }),
                            el('span', null, node.text)
                        ),
                        hasChildren && isExpanded && el(FolderTree, { nodes: node.children, depth: depth + 1 })
                    );
                });
            }

            return el(wp.element.Fragment, null,
                el(InspectorControls, null,
                    el(PanelBody, { title: __('Gallery Settings', 'media-directory') },

                        // Custom Tree Select Trigger
                        el('label', { className: 'components-base-control__label' }, __('Folders', 'media-directory')),
                        el('div', {
                            className: 'components-base-control__field mddr_select_container',
                        },
                            el('div', {
                                className: 'mddr_select_trigger',
                                onClick: function () { setIsOpen(!isOpen); }
                            },
                                attributes.selectedFolder.length === 0 && el('span', { className: 'mddr_placeholder' }, __('Select folders...', 'media-directory')),
                                attributes.selectedFolder.map(function (id) {
                                    return el('div', {
                                        key: id,
                                        className: 'mddr_selected_tag'
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
                                    className: 'dashicons ' + (isOpen ? 'dashicons-arrow-up-alt2' : 'dashicons-arrow-down-alt2') + ' mddr_arrow '
                                })
                            ),
                            // Dropdown Content
                            isOpen && el('div', {
                                className: 'mddr_select_dropdown',
                            },
                                isLoading ? el(Spinner) : el(FolderTree, { nodes: rawFolders })
                            )
                        ),

                        el(RangeControl, {
                            label: __('Columns', 'media-directory'),
                            value: attributes.columns,
                            onChange: function (val) { setAttributes({ columns: val }); },
                            min: 1, max: 6
                        }),

                        el(ToggleControl, {
                            label: __('Crop Images', 'media-directory'),
                            checked: attributes.isCropped,
                            onChange: function (val) { setAttributes({ isCropped: val }); },
                            help: __('Thumbnails are cropped to align.', 'media-directory')
                        }),

                        el(ToggleControl, {
                            label: __('Caption', 'media-directory'),
                            checked: attributes.hasCaption,
                            onChange: function (val) { setAttributes({ hasCaption: val }); },
                            help: __('Display image caption', 'media-directory')
                        }),

                        el(ToggleControl, {
                            label: __('Add Lightbox', 'media-directory'),
                            checked: attributes.hasLightbox,
                            onChange: function (val) { setAttributes({ hasLightbox: val }); }
                        }),

                        el(SelectControl, {
                            label: __('Layout', 'media-directory'),
                            value: attributes.layout,
                            options: [
                                { label: 'Flex', value: 'flex' },
                                { label: 'Grid', value: 'grid' },
                                { label: 'Masonry', value: 'masonry' },
                                { label: 'Carousel', value: 'carousel' }
                            ],
                            help: __('Select the display layout for media items.', 'media-directory'),
                            onChange: function (val) { setAttributes({ layout: val }); }
                        }),

                        el(SelectControl, {
                            label: __('Image Hover Animation', 'media-directory'),
                            value: attributes.imageHoverAnimation,
                            options: [
                                { label: 'None', value: 'none' },
                                { label: 'Zoom In', value: 'zoomIn' },
                                { label: 'Zoom Out', value: 'zoomOut' },
                                { label: 'Shine', value: 'shine' },
                                { label: 'Opacity', value: 'opacity' },
                                { label: 'Grayscale', value: 'grayscale' },
                                { label: 'Blur', value: 'blur' },
                                { label: 'Sepia', value: 'sepia' },
                                { label: 'Lift Up', value: 'liftUp' },
                                { label: 'Rotate Left', value: 'rotateLeft' },
                                { label: 'Rotate Right', value: 'rotateRight' }
                            ],
                            help: __('Hover on images to see animations.', 'media-directory'),
                            onChange: function (val) { setAttributes({ imageHoverAnimation: val }); }
                        }),

                        el(SelectControl, {
                            label: __('Link To', 'media-directory'),
                            value: attributes.linkTo,
                            options: [
                                { label: 'None', value: 'none' },
                                { label: 'Media File', value: 'media' },
                                { label: 'Attachment Page', value: 'attachment' }
                            ],
                            help: __('Choose where the media item should link when clicked.', 'media-directory'),
                            onChange: function (val) { setAttributes({ linkTo: val }); }
                        }),

                        el(SelectControl, {
                            label: __('Sort By', 'media-directory'),
                            value: attributes.sortBy,
                            options: [
                                { label: 'By Name', value: 'name' },
                                { label: 'By Date', value: 'date' },
                                { label: 'By Modified', value: 'modified' },
                                { label: 'By Author', value: 'author' },
                                { label: 'By Title', value: 'title' },
                                { label: 'By File Name', value: 'file_name' }
                            ],
                            help: __('Select the criteria used to order the media items.', 'media-directory'),
                            onChange: function (val) { setAttributes({ sortBy: val }); }
                        }),

                        el(SelectControl, {
                            label: __('Sort Type', 'media-directory'),
                            value: attributes.sortType,
                            options: [
                                { label: 'Ascending', value: 'ASC' },
                                { label: 'Descending', value: 'DESC' }
                            ],
                            help: __('Choose the sorting direction for the media items.', 'media-directory'),
                            onChange: function (val) { setAttributes({ sortType: val }); }
                        })
                    )
                ),
                el('div', useBlockProps(),
                    attributes.selectedFolder.length === 0 ?
                        el('div', { className: 'components-placeholder is-large' },
                            el('div', { className: 'components-placeholder__label' }, __('Media Directory Gallery', 'media-directory')),
                            el('div', { className: 'components-placeholder__instructions' }, __('Please select folders from the block settings.', 'media-directory'))
                        ) :
                        el('div', {
                            className: 'mddr_editor_preview',
                            onClick: function (event) {
                                event.preventDefault();
                            }
                        },
                            el(ServerSideRender, {
                                block: 'media-directory/block-media-directory-gallery',
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
