( function ( wp ) {
	if ( ! wp?.blocks?.registerBlockType ) {
		return;
	}

	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, TextControl } = wp.components;
	const { createElement: el, Fragment } = wp.element;
	const { __ } = wp.i18n;

	registerBlockType( 'grv/social-links-bar', {
		apiVersion: 3,
		title: __( 'Social Links Bar', '4wp-headless-app' ),
		category: 'grv',
		icon: 'share',
		description: __( 'Смуга з текстом і pill-кнопками соцмереж із налаштувань сайту.', '4wp-headless-app' ),
		keywords: [ 'social', 'instagram', 'grv' ],
		attributes: {
			eyebrow: { type: 'string', default: 'Слідкуйте за нашими роботами' },
			title: { type: 'string', default: 'Ми є скрізь' },
			titleHighlight: { type: 'string', default: 'де потрібно' },
		},
		supports: { html: false, anchor: true },
		edit: function ( props ) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps( {
				className: 'grv-social-links-bar-editor',
				style: {
					padding: '1.25rem',
					border: '1px dashed #c3a24d',
					borderRadius: '10px',
					background: '#1a1a1a',
					color: '#fff',
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
						{ title: __( 'Текст', '4wp-headless-app' ), initialOpen: true },
						el( TextControl, {
							label: __( 'Eyebrow', '4wp-headless-app' ),
							value: attributes.eyebrow || '',
							onChange: function ( v ) { setAttributes( { eyebrow: v } ); },
						} ),
						el( TextControl, {
							label: __( 'Заголовок', '4wp-headless-app' ),
							value: attributes.title || '',
							onChange: function ( v ) { setAttributes( { title: v } ); },
						} ),
						el( TextControl, {
							label: __( 'Акцент (жовтий)', '4wp-headless-app' ),
							value: attributes.titleHighlight || '',
							onChange: function ( v ) { setAttributes( { titleHighlight: v } ); },
						} )
					)
				),
				el(
					'div',
					blockProps,
					el( 'p', { style: { margin: 0, fontSize: '11px', letterSpacing: '0.12em', textTransform: 'uppercase', color: '#999' } }, attributes.eyebrow || '' ),
					el( 'h3', { style: { margin: '8px 0 0', fontSize: '22px', fontWeight: 800 } },
						( attributes.title || '' ) + ' ',
						el( 'span', { style: { color: '#e5b93c' } }, attributes.titleHighlight || '' )
					),
					el( 'p', { style: { margin: '12px 0 0', fontSize: '12px', color: '#888' } }, __( 'Посилання беруться з Site → Social links', '4wp-headless-app' ) )
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
