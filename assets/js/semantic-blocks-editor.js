/**
 * Wunderkiste Toolkit - Semantic Blocks Editor
 * Version: 2.9 - with icon choice and its own category
 * Gutenberg Block Registration for HTML5 Semantic Elements
 */
( function( blocks, element, blockEditor, components, i18n ) {
    const { registerBlockType } = blocks;
    const { createElement: el, Fragment } = element;
    const { InnerBlocks, InspectorControls, useBlockProps } = blockEditor;
    const { PanelBody, TextControl, ToggleControl, SelectControl, RadioControl } = components;
    const { __ } = i18n;

    // Available icon styles for the Details block
    const iconStyles = [
        { label: __( 'Arrow (▶ ▼)', 'wunderkiste-toolkit' ), value: 'arrow', closed: '▶', open: '▼' },
        { label: __( 'Plus/Minus (+ −)', 'wunderkiste-toolkit' ), value: 'plus', closed: '+', open: '−' },
        { label: __( 'Chevron (› ⌄)', 'wunderkiste-toolkit' ), value: 'chevron', closed: '›', open: '⌄' },
        { label: __( 'Caret (⯈ ⯆)', 'wunderkiste-toolkit' ), value: 'caret', closed: '⯈', open: '⯆' },
        { label: __( 'Folder (📁 📂)', 'wunderkiste-toolkit' ), value: 'folder', closed: '📁', open: '📂' },
        { label: __( 'Circle (⊕ ⊖)', 'wunderkiste-toolkit' ), value: 'circle', closed: '⊕', open: '⊖' },
        { label: __( 'Square (⊞ ⊟)', 'wunderkiste-toolkit' ), value: 'square', closed: '⊞', open: '⊟' },
        { label: __( 'Dot (● ○)', 'wunderkiste-toolkit' ), value: 'dot', closed: '●', open: '○' },
        { label: __( 'No icon', 'wunderkiste-toolkit' ), value: 'none', closed: '', open: '' },
    ];

    // Helper: find the icon for the current style
    function getIconForStyle( styleValue, isOpen ) {
        const style = iconStyles.find( s => s.value === styleValue ) || iconStyles[0];
        return isOpen ? style.open : style.closed;
    }

    // Wrapper Blocks (article, section, aside, etc.)
    const wrapperBlocks = [
        { tag: 'article', title: 'Article', icon: 'media-text', description: __( 'Semantic container for self-contained content.', 'wunderkiste-toolkit' ) },
        { tag: 'section', title: 'Section', icon: 'screenoptions', description: __( 'Thematic section with a heading.', 'wunderkiste-toolkit' ) },
        { tag: 'aside', title: 'Aside', icon: 'align-right', description: __( 'Complementary content, sidebar element.', 'wunderkiste-toolkit' ) },
        { tag: 'header', title: 'Header', icon: 'arrow-up-alt', description: __( 'Introductory area of a section.', 'wunderkiste-toolkit' ) },
        { tag: 'footer', title: 'Footer', icon: 'arrow-down-alt', description: __( 'Footer area of a section.', 'wunderkiste-toolkit' ) },
        { tag: 'main', title: 'Main', icon: 'editor-expand', description: __( 'Main content of the page (only once per page).', 'wunderkiste-toolkit' ) },
        { tag: 'figure', title: 'Figure', icon: 'format-image', description: __( 'Figure with an optional caption.', 'wunderkiste-toolkit' ) },
        { tag: 'address', title: 'Address', icon: 'location', description: __( 'Contact information.', 'wunderkiste-toolkit' ) },
        { tag: 'nav', title: 'Nav', icon: 'menu', description: __( 'Navigation area.', 'wunderkiste-toolkit' ) },
    ];

    // Register each wrapper block
    wrapperBlocks.forEach( function( config ) {
        registerBlockType( 'seowk/' + config.tag, {
            title: config.title,
            description: config.description,
            icon: config.icon,
            category: 'seowk-semantic',
            supports: {
                align: [ 'wide', 'full' ],
                anchor: true,
                customClassName: true,
                html: false,
            },
            attributes: {
                tagName: { type: 'string', default: config.tag },
                cssClass: { type: 'string', default: '' },
                cssId: { type: 'string', default: '' },
            },
            edit: function( props ) {
                const { attributes, setAttributes } = props;
                const blockProps = useBlockProps( {
                    className: 'seowk-semantic-block seowk-semantic-' + config.tag + ( attributes.cssClass ? ' ' + attributes.cssClass : '' ),
                    // Translatable placeholder text for the empty-state ::after in the editor CSS.
                    style: { '--seowk-placeholder': JSON.stringify( __( 'Add blocks here…', 'wunderkiste-toolkit' ) ) },
                } );

                return el( Fragment, {},
                    el( InspectorControls, {},
                        el( PanelBody, { title: __( 'Settings', 'wunderkiste-toolkit' ), initialOpen: true },
                            el( TextControl, {
                                label: __( 'CSS class', 'wunderkiste-toolkit' ),
                                value: attributes.cssClass,
                                onChange: function( value ) { setAttributes( { cssClass: value } ); },
                            } ),
                            el( TextControl, {
                                label: __( 'CSS ID', 'wunderkiste-toolkit' ),
                                value: attributes.cssId,
                                onChange: function( value ) { setAttributes( { cssId: value } ); },
                            } )
                        )
                    ),
                    el( 'div', blockProps,
                        el( 'div', { className: 'seowk-semantic-label' }, '<' + config.tag + '>' ),
                        el( InnerBlocks, { 
                            templateLock: false,
                            renderAppender: InnerBlocks.ButtonBlockAppender,
                        } ),
                        el( 'div', { className: 'seowk-semantic-label seowk-semantic-label-end' }, '</' + config.tag + '>' )
                    )
                );
            },
            save: function() {
                return el( InnerBlocks.Content );
            },
        } );
    } );

    // Details/Accordion block with icon choice
    registerBlockType( 'seowk/details', {
        title: __( 'Details / Accordion', 'wunderkiste-toolkit' ),
        description: __( 'Collapsible area with a summary and configurable icon.', 'wunderkiste-toolkit' ),
        icon: 'arrow-down',
        category: 'seowk-semantic',
        supports: {
            anchor: true,
            customClassName: true,
        },
        attributes: {
            cssClass: { type: 'string', default: '' },
            cssId: { type: 'string', default: '' },
            summary: { type: 'string', default: __( 'Show more', 'wunderkiste-toolkit' ) },
            open: { type: 'boolean', default: false },
            iconStyle: { type: 'string', default: 'arrow' },
            iconPosition: { type: 'string', default: 'left' },
        },
        edit: function( props ) {
            const { attributes, setAttributes } = props;
            const blockProps = useBlockProps( {
                className: 'seowk-details-editor seowk-icon-' + attributes.iconStyle + ' seowk-icon-' + attributes.iconPosition + ( attributes.cssClass ? ' ' + attributes.cssClass : '' ),
            } );

            const currentIcon = getIconForStyle( attributes.iconStyle, attributes.open );
            const iconPositionOptions = [
                { label: __( 'Left', 'wunderkiste-toolkit' ), value: 'left' },
                { label: __( 'Right', 'wunderkiste-toolkit' ), value: 'right' },
            ];

            return el( Fragment, {},
                el( InspectorControls, {},
                    el( PanelBody, { title: __( 'Summary & behavior', 'wunderkiste-toolkit' ), initialOpen: true },
                        el( TextControl, {
                            label: __( 'Summary text', 'wunderkiste-toolkit' ),
                            value: attributes.summary,
                            onChange: function( value ) { setAttributes( { summary: value } ); },
                        } ),
                        el( ToggleControl, {
                            label: __( 'Open by default', 'wunderkiste-toolkit' ),
                            checked: attributes.open,
                            onChange: function( value ) { setAttributes( { open: value } ); },
                        } )
                    ),
                    el( PanelBody, { title: __( 'Icon settings', 'wunderkiste-toolkit' ), initialOpen: true },
                        el( SelectControl, {
                            label: __( 'Icon style', 'wunderkiste-toolkit' ),
                            value: attributes.iconStyle,
                            options: iconStyles.map( function( style ) {
                                return { label: style.label, value: style.value };
                            } ),
                            onChange: function( value ) { setAttributes( { iconStyle: value } ); },
                        } ),
                        el( RadioControl, {
                            label: __( 'Icon position', 'wunderkiste-toolkit' ),
                            selected: attributes.iconPosition,
                            options: iconPositionOptions,
                            onChange: function( value ) { setAttributes( { iconPosition: value } ); },
                        } ),
                        el( 'div', { className: 'seowk-icon-preview', style: { marginTop: '15px', padding: '10px', background: '#f0f0f0', borderRadius: '4px', textAlign: 'center' } },
                            el( 'p', { style: { margin: '0 0 5px', fontSize: '12px', color: '#666' } }, __( 'Preview:', 'wunderkiste-toolkit' ) ),
                            el( 'span', { style: { fontSize: '24px', display: 'block' } }, 
                                getIconForStyle( attributes.iconStyle, false ) + ' → ' + getIconForStyle( attributes.iconStyle, true )
                            )
                        )
                    ),
                    el( PanelBody, { title: __( 'CSS settings', 'wunderkiste-toolkit' ), initialOpen: false },
                        el( TextControl, {
                            label: __( 'CSS class', 'wunderkiste-toolkit' ),
                            value: attributes.cssClass,
                            onChange: function( value ) { setAttributes( { cssClass: value } ); },
                        } ),
                        el( TextControl, {
                            label: __( 'CSS ID', 'wunderkiste-toolkit' ),
                            value: attributes.cssId,
                            onChange: function( value ) { setAttributes( { cssId: value } ); },
                        } )
                    )
                ),
                el( 'div', blockProps,
                    el( 'div', { 
                        className: 'seowk-details-summary-editor',
                        style: { 
                            flexDirection: attributes.iconPosition === 'right' ? 'row-reverse' : 'row',
                            justifyContent: attributes.iconPosition === 'right' ? 'space-between' : 'flex-start'
                        }
                    },
                        attributes.iconStyle !== 'none' && el( 'span', { 
                            className: 'seowk-details-icon-editor',
                            style: { fontSize: '16px', minWidth: '24px', textAlign: 'center' }
                        }, currentIcon ),
                        el( TextControl, {
                            value: attributes.summary,
                            onChange: function( value ) { setAttributes( { summary: value } ); },
                            placeholder: __( 'Enter summary…', 'wunderkiste-toolkit' ),
                            style: { flex: 1 }
                        } )
                    ),
                    el( 'div', { className: 'seowk-details-content-editor' },
                        el( InnerBlocks, { 
                            templateLock: false,
                            renderAppender: InnerBlocks.ButtonBlockAppender,
                        } )
                    )
                )
            );
        },
        save: function() {
            return el( InnerBlocks.Content );
        },
    } );

    // Mark/Highlight Block
    registerBlockType( 'seowk/mark', {
        title: __( 'Mark / Highlight', 'wunderkiste-toolkit' ),
        description: __( 'Highlighted text.', 'wunderkiste-toolkit' ),
        icon: 'edit',
        category: 'seowk-semantic',
        supports: {
            customClassName: true,
        },
        attributes: {
            cssClass: { type: 'string', default: '' },
            text: { type: 'string', default: '' },
        },
        edit: function( props ) {
            const { attributes, setAttributes } = props;
            const blockProps = useBlockProps( {
                className: 'seowk-mark-editor',
            } );

            return el( Fragment, {},
                el( InspectorControls, {},
                    el( PanelBody, { title: __( 'Settings', 'wunderkiste-toolkit' ), initialOpen: true },
                        el( TextControl, {
                            label: __( 'CSS class', 'wunderkiste-toolkit' ),
                            value: attributes.cssClass,
                            onChange: function( value ) { setAttributes( { cssClass: value } ); },
                        } )
                    )
                ),
                el( 'div', blockProps,
                    el( 'mark', { className: attributes.cssClass || '' },
                        el( TextControl, {
                            value: attributes.text,
                            onChange: function( value ) { setAttributes( { text: value } ); },
                            placeholder: __( 'Enter highlighted text…', 'wunderkiste-toolkit' ),
                        } )
                    )
                )
            );
        },
        save: function( props ) {
            const { attributes } = props;
            return el( 'mark', { className: attributes.cssClass || null }, attributes.text );
        },
    } );

} )(
    window.wp.blocks,
    window.wp.element,
    window.wp.blockEditor,
    window.wp.components,
    window.wp.i18n
);
