( function ( wp ) {
	if ( ! wp?.blocks?.registerBlockType ) {
		return;
	}

	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, TextControl, TextareaControl } = wp.components;
	const { createElement: el, Fragment } = wp.element;
	const { __ } = wp.i18n;

	registerBlockType( 'grv/cta-related', {
		apiVersion: 3,
		title: __( 'CTA Related', '4wp-headless-app' ),
		category: 'grv',
		icon: 'arrow-right-alt',
		description: __( 'Блок «наступний крок» з посиланням на пов’язану послугу.', '4wp-headless-app' ),
		keywords: [ 'cta', 'next', 'related', 'grv' ],
		attributes: {
			intro: { type: 'string', default: 'Фасад готовий — беремось за внутрішнє. Ремонт і реконструкція будь-якої складності під ключ.' },
			eyebrow: { type: 'string', default: 'Наступний крок' },
			titlePrefix: { type: 'string', default: 'А далі' },
			titleHighlight: { type: 'string', default: 'РЕМОНТНІ РОБОТИ' },
			href: { type: 'string', default: '/remont' },
		},
		supports: { html: false, anchor: true },
		edit: function ( props ) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps( {
				className: 'grv-cta-related-editor',
				style: {
					padding: '1.5rem',
					border: '2px solid #c3a24d',
					borderRadius: '12px',
					background: '#1a1a1a',
					color: '#fff',
					textAlign: 'center',
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
						{ title: __( 'CTA Related', '4wp-headless-app' ), initialOpen: true },
						el( TextareaControl, {
							label: __( 'Intro', '4wp-headless-app' ),
							value: attributes.intro || '',
							onChange: function ( v ) { setAttributes( { intro: v } ); },
						} ),
						el( TextControl, {
							label: __( 'Eyebrow', '4wp-headless-app' ),
							value: attributes.eyebrow || '',
							onChange: function ( v ) { setAttributes( { eyebrow: v } ); },
						} ),
						el( TextControl, {
							label: __( 'Префікс («А далі»)', '4wp-headless-app' ),
							value: attributes.titlePrefix || '',
							onChange: function ( v ) { setAttributes( { titlePrefix: v } ); },
						} ),
						el( TextControl, {
							label: __( 'Акцент (жовтий)', '4wp-headless-app' ),
							value: attributes.titleHighlight || '',
							onChange: function ( v ) { setAttributes( { titleHighlight: v } ); },
						} ),
						el( TextControl, {
							label: __( 'Посилання', '4wp-headless-app' ),
							value: attributes.href || '',
							onChange: function ( v ) { setAttributes( { href: v } ); },
							help: '/remont',
						} )
					)
				),
				el(
					'div',
					blockProps,
					el( 'p', { style: { color: '#aaa', fontSize: '14px', margin: '0 0 16px' } }, attributes.intro || '' ),
					el( 'p', { style: { color: '#e5b93c', fontSize: '11px', letterSpacing: '0.14em', textTransform: 'uppercase', fontWeight: 600, margin: '0 0 8px' } }, attributes.eyebrow || '' ),
					el( 'p', { style: { fontSize: '22px', fontWeight: 800, margin: 0 } },
						( attributes.titlePrefix || '' ) + ' ',
						el( 'span', { style: { color: '#e5b93c' } }, attributes.titleHighlight || '' ),
						' →'
					)
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
