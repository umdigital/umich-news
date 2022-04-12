(function(){
    const __ = wp.i18n.__;
    const { useBlockProps, InnerBlocks } = wp.blockEditor;
    const { createElement } = wp.element;
    const { TextControl } = wp.components;

    wp.blocks.registerBlockType( 'umichnews/news', {
        edit: ( props ) => {
            return createElement(
                'div',
                useBlockProps({
                    className: props.className
                }),
                createElement( wp.blockEditor.InspectorControls,
                    null,
                    createElement(
                        wp.components.PanelBody, {
                            title: 'News Display Options',
                            initialOpen: true
                        },
                        createElement(
                            wp.components.SelectControl, {
                                label: 'News Type',
                                value: props.attributes.type,
                                options: [
                                    {
                                        value: 'in-the-news',
                                        label: 'In the News',
                                    }
                                ],
                                onChange: function( value ){
                                    props.setAttributes({
                                        type: value
                                    });
                                }
                            }
                        ),
                        createElement(
                            wp.components.TextControl, {
                                label: 'Items Per Page',
                                type:  'number',
                                value: props.attributes.limit,
                                onChange: function( value ){
                                    props.setAttributes({
                                        limit: parseInt( value )
                                    });
                                }
                            }
                        ),
                        createElement(
                            wp.components.ToggleControl, {
                                label: 'Show Date',
                                checked: props.attributes.showDate,
                                onChange: function( value ){
                                    props.setAttributes({
                                        showDate: value
                                    });
                                }
                            }
                        ),
                        createElement(
                            wp.components.ToggleControl, {
                                label: 'Paginate',
                                checked: props.attributes.paginate,
                                onChange: function( value ){
                                    props.setAttributes({
                                        paginate: value
                                    });
                                }
                            }
                        ),
                        createElement(
                            wp.components.TextControl, {
                                label: 'Page Number URL Variable',
                                value: props.attributes.pagevar,
                                type : 'string',
                                onChange: function( value ){
                                    props.setAttributes({
                                        pagevar: value
                                    });
                                }
                            }
                        ),
                        createElement(
                            wp.components.TextControl, {
                                label: 'Custom Template',
                                type : 'text',
                                help : 'Custom template to use instead of the default one.',
                                value: props.attributes.template,
                                onChange: function( value ){
                                    props.setAttributes({
                                        template: value
                                    });
                                }
                            }
                        ),
                    )
                ),
                createElement( wp.serverSideRender, {
                    block: 'umichnews/news',
                    attributes: props.attributes
                })
            )
        }
    });
}());
