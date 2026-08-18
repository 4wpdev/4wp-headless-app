( function ( wp ) {
	if ( ! wp?.blocks?.registerBlockType ) {
		return;
	}

	var registerBlockType = wp.blocks.registerBlockType;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InnerBlocks = wp.blockEditor.InnerBlocks;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var __ = wp.i18n.__;

	var ALLOWED_BLOCKS = [
		'core/paragraph',
		'core/heading',
		'core/list',
		'core/list-item',
		'core/quote',
		'core/image',
		'core/separator',
		'core/table',
		'core/html',
		'core/freeform',
		'core/embed',
		'core/buttons',
		'core/button',
	];

	registerBlockType( 'grv/rich-text', {
		apiVersion: 3,
		title: __( 'Розширений текст', '4wp-headless-app' ),
		category: 'grv',
		icon: 'editor-paragraph',
		description: __(
			'SEO / контентний текст з повним форматуванням (жирний, курсив, посилання, списки, заголовки).',
			'4wp-headless-app'
		),
		keywords: [ 'seo', 'text', 'content', 'rich', 'html', 'grv' ],
		attributes: {
			title: { type: 'string', default: '' },
			content: { type: 'string', default: '' },
		},
		supports: {
			html: false,
			anchor: true,
			align: [ 'wide' ],
		},
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps( {
				className: 'grv-rich-text-editor',
				style: {
					padding: '1.25rem 1.5rem',
					border: '1px solid #c3a24d',
					borderRadius: '8px',
					background: '#fff',
					color: '#111',
				},
			} );

			return el(
				Fragment,
				{},
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __( 'Розширений текст', '4wp-headless-app' ), initialOpen: true },
						el( TextControl, {
							label: __( 'Заголовок секції (опційно)', '4wp-headless-app' ),
							value: attributes.title || '',
							onChange: function ( v ) {
								setAttributes( { title: v } );
							},
							help: __( 'Показується над текстом на сайті. Можна залишити порожнім.', '4wp-headless-app' ),
						} )
					)
				),
				el(
					'div',
					blockProps,
					el(
						'p',
						{
							style: {
								margin: '0 0 12px',
								fontSize: '11px',
								letterSpacing: '0.08em',
								textTransform: 'uppercase',
								color: '#888',
								fontWeight: 600,
							},
						},
						__( 'Розширений текст (SEO)', '4wp-headless-app' )
					),
					attributes.title
						? el(
								'h2',
								{ style: { margin: '0 0 12px', fontSize: '1.35rem' } },
								attributes.title
						  )
						: null,
					el( InnerBlocks, {
						allowedBlocks: ALLOWED_BLOCKS,
						template: [ [ 'core/paragraph', { placeholder: 'Напишіть SEO-текст…' } ] ],
						templateLock: false,
					} )
				)
			);
		},
		save: function () {
			return el( InnerBlocks.Content );
		},
	} );
} )( window.wp );
