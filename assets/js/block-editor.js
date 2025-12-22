/**
 * Gutenberg Block Editor Script
 *
 * @package NivoSearch
 * @since 1.0.0
 */

(function () {
    const { registerBlockType } = wp.blocks;
    const { InspectorControls } = wp.blockEditor;
    const { PanelBody, SelectControl, Spinner } = wp.components;
    const { createElement: el, useEffect } = wp.element;
    const { useSelect } = wp.data;

    registerBlockType('nivo-search/ajax-search', {
        title: 'Nivo Search',
        icon: 'search',
        category: 'woocommerce',
        description: 'Add an AJAX-powered product search box',

        attributes: {
            presetId: {
                type: 'number',
                default: 0
            }
        },

        edit: function (props) {
            const { attributes, setAttributes } = props;
            const { presetId } = attributes;

            // Fetch available presets
            const presets = useSelect((select) => {
                return select('core').getEntityRecords('postType', 'nivo_search_preset', {
                    per_page: -1,
                    status: 'publish'
                });
            }, []);

            // Auto-set first preset when loaded if presetId is 0
            useEffect(() => {
                if (presets && presets.length > 0 && presetId === 0) {
                    setAttributes({ presetId: presets[0].id });
                }
            }, [presets, presetId]);

            // Build preset options
            const presetOptions = [];

            if (presets) {
                presets.forEach(preset => {
                    presetOptions.push({
                        label: preset.title.rendered,
                        value: preset.id
                    });
                });
            }

            return el('div', {},
                el(InspectorControls, {},
                    el(PanelBody, { title: 'Search Settings', initialOpen: true },
                        presets ? el(SelectControl, {
                            label: 'Select Preset',
                            value: presetId,
                            options: presetOptions,
                            onChange: (value) => setAttributes({ presetId: parseInt(value) }),
                            help: 'Choose a search preset to customize appearance and behavior'
                        }) : el(Spinner)
                    )
                ),
                el('div', {},
                    el('div', {
                        style: {
                            display: 'flex',
                            alignItems: 'center',
                            gap: '8px',
                            marginBottom: '10px',
                            padding: '12px',
                            background: '#f0f0f0',
                            borderRadius: '4px',
                            border: '1px solid #ddd'
                        }
                    },
                        el('svg', {
                            width: '20',
                            height: '20',
                            viewBox: '0 0 24 24',
                            fill: 'none',
                            stroke: '#667eea',
                            strokeWidth: '2',
                            strokeLinecap: 'round',
                            strokeLinejoin: 'round'
                        },
                            el('circle', { cx: '11', cy: '11', r: '8' }),
                            el('path', { d: 'm21 21-4.35-4.35' })
                        ),
                        el('div', {},
                            el('strong', { style: { color: '#667eea', fontSize: '16px', display: 'block' } }, 'Nivo Search'),
                            el('p', { style: { margin: '4px 0 0 0', fontSize: '13px', color: '#666' } },
                                presetId ? `Using Preset ID: ${presetId}` : 'Loading...'
                            )
                        )
                    )
                )
            );
        },

        save: function () {
            return null; // Rendered via PHP
        }
    });
})();