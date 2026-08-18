( function ( wp ) {
	if ( ! wp?.blocks?.registerBlockType ) {
		return;
	}

	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, TextControl, TextareaControl } = wp.components;
	const { createElement: el, Fragment } = wp.element;
	const { __ } = wp.i18n;

	registerBlockType( 'grv/cta-card', {
		apiVersion: 3,
		title: __( 'CTA Card', '4wp-headless-app' ),
		category: 'grv',
		icon: 'megaphone',
		description: __( 'Картка CTA з двома кнопками (для сторінок регіонів / портфоліо).', '4wp-headless-app' ),
		keywords: [ 'cta', 'card', 'consultation', 'grv' ],
		attributes: {
			eyebrow: { type: 'string', default: 'Готові розпочати?' },
			title: { type: 'string', default: 'Безкоштовна консультація' },
			text: {
				type: 'string',
				default: "Виїзд на об'єкт — безкоштовно. Фіксований кошторис без прихованих доплат. Ми підготуємо точний розрахунок вашого проєкту протягом 24 годин.",
			},
			primaryButtonLabel: { type: 'string', default: 'Зателефонувати' },
			primaryButtonHref: { type: 'string', default: '' },
			secondaryButtonLabel: { type: 'string', default: 'Дивитись роботи' },
			secondaryButtonHref: { type: 'string', default: '/our-works' },
		},
		supports: { html: false, anchor: true },
		edit: function ( props ) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps( {
				className: 'grv-cta-card-editor',
				style: {
					padding: '1.5rem',
					border: '1px solid #c3a24d',
					borderRadius: '12px',
					background: '#1a1814',
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
						{ title: __( 'Контент', '4wp-headless-app' ), initialOpen: true },
						el( TextControl, {
							label: __( 'Eyebrow', '4wp-headless-app' ),
							value: attributes.eyebrow || '',
							onChange: function ( v ) { setAttributes( { eyebrow: v } ); },
						} ),
						el( TextControl, {
							label: __( 'Заголовок', '4wp-headless-app' ),
							value: attributes.title || '',
							onChange: function ( v ) { setAttributes( { title: v } ); },
							help: __( 'Напр. «Безкоштовна консультація у Луцькому районі»', '4wp-headless-app' ),
						} ),
						el( TextareaControl, {
							label: __( 'Текст', '4wp-headless-app' ),
							value: attributes.text || '',
							onChange: function ( v ) { setAttributes( { text: v } ); },
							rows: 4,
						} )
					),
					el(
						PanelBody,
						{ title: __( 'Кнопки', '4wp-headless-app' ), initialOpen: true },
						el( TextControl, {
							label: __( 'Primary — текст', '4wp-headless-app' ),
							value: attributes.primaryButtonLabel || '',
							onChange: function ( v ) { setAttributes( { primaryButtonLabel: v } ); },
						} ),
						el( TextControl, {
							label: __( 'Primary — URL', '4wp-headless-app' ),
							value: attributes.primaryButtonHref || '',
							onChange: function ( v ) { setAttributes( { primaryButtonHref: v } ); },
							help: __( 'Порожньо = tel: з налаштувань сайту. Або /contacts, tel:+380…', '4wp-headless-app' ),
						} ),
						el( TextControl, {
							label: __( 'Secondary — текст', '4wp-headless-app' ),
							value: attributes.secondaryButtonLabel || '',
							onChange: function ( v ) { setAttributes( { secondaryButtonLabel: v } ); },
							help: __( 'Порожньо = кнопка схована.', '4wp-headless-app' ),
						} ),
						el( TextControl, {
							label: __( 'Secondary — URL', '4wp-headless-app' ),
							value: attributes.secondaryButtonHref || '',
							onChange: function ( v ) { setAttributes( { secondaryButtonHref: v } ); },
						} )
					)
				),
				el(
					'div',
					blockProps,
					el( 'p', { style: { color: '#c9a227', fontSize: '11px', letterSpacing: '0.14em', textTransform: 'uppercase', fontWeight: 700, margin: 0 } }, attributes.eyebrow || '' ),
					el( 'h3', { style: { margin: '12px 0 0', fontSize: '22px', fontWeight: 800 } }, attributes.title || '' ),
					el( 'div', { style: { width: '48px', height: '2px', background: '#c9a227', margin: '14px auto' } } ),
					el( 'p', { style: { margin: 0, fontSize: '13px', color: '#c4c4c4', lineHeight: 1.5 } }, attributes.text || '' )
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
