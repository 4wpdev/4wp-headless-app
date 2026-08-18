( function ( wp ) {
	if ( ! wp?.blocks?.registerBlockType ) {
		return;
	}

	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, TextControl } = wp.components;
	const { createElement: el, Fragment } = wp.element;

	registerBlockType( 'grv/cta-strip', {
		apiVersion: 3,
		title: 'GRV CTA (смуга)',
		category: 'grv',
		icon: 'megaphone',
		description: 'Золота смуга з закликом до дії перед футером.',
		attributes: {
			title: {
				type: 'string',
				default: 'Готові розпочати ваш проект?',
			},
			buttonLabel: {
				type: 'string',
				default: "Зв'язатись з нами",
			},
			buttonHref: {
				type: 'string',
				default: '/contacts',
			},
		},
		supports: { html: false, anchor: true },
		edit: function ( props ) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps( {
				className: 'grv-cta-strip-editor',
				style: {
					padding: '1rem 1.25rem',
					background: 'linear-gradient(135deg, #b8911e, #f5d06a)',
					borderRadius: '8px',
					color: '#141414',
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
						{ title: 'CTA', initialOpen: true },
						el( TextControl, {
							label: 'Текст',
							value: attributes.title || '',
							onChange: function ( v ) {
								setAttributes( { title: v } );
							},
						} ),
						el( TextControl, {
							label: 'Кнопка',
							value: attributes.buttonLabel || '',
							onChange: function ( v ) {
								setAttributes( { buttonLabel: v } );
							},
						} ),
						el( TextControl, {
							label: 'Посилання кнопки',
							value: attributes.buttonHref || '',
							onChange: function ( v ) {
								setAttributes( { buttonHref: v } );
							},
							help: '/contacts або tel:+380660371690',
						} )
					)
				),
				el(
					'div',
					blockProps,
					el( 'strong', {}, attributes.title || '' ),
					el(
						'span',
						{
							style: {
								display: 'inline-block',
								marginLeft: '12px',
								padding: '4px 12px',
								background: '#141414',
								color: '#f5d06a',
								borderRadius: '4px',
								fontSize: '12px',
							},
						},
						( attributes.buttonLabel || '' ) + ' →'
					)
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
