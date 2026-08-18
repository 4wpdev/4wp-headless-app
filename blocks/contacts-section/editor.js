( function ( wp ) {
	if ( ! wp?.blocks?.registerBlockType ) {
		return;
	}

	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, TextControl, ToggleControl } = wp.components;
	const { createElement: el, Fragment } = wp.element;
	const { __ } = wp.i18n;

	registerBlockType( 'grv/contacts-section', {
		apiVersion: 3,
		title: __( 'Contacts', '4wp-headless-app' ),
		category: 'grv',
		icon: 'phone',
		description: __( 'Контакти + форма замовлення дзвінка. Телефон/email/графік — з налаштувань сайту.', '4wp-headless-app' ),
		keywords: [ 'contacts', 'form', 'callback', 'grv' ],
		attributes: {
			eyebrow: { type: 'string', default: 'Контакти' },
			title: { type: 'string', default: 'Напишіть або зателефонуйте' },
			socialLabel: { type: 'string', default: 'Написати напряму' },
			formTitle: { type: 'string', default: 'Замовити дзвінок' },
			formSubtitle: { type: 'string', default: 'Залиште номер — ми передзвонимо протягом 30 хвилин' },
			buttonLabel: { type: 'string', default: 'Перетелефонуйте мені' },
			formNote: { type: 'string', default: 'Передзвонюємо протягом 30 хвилин у робочий час' },
			showMessengers: { type: 'boolean', default: true },
		},
		supports: { html: false, anchor: true },
		edit: function ( props ) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps( {
				className: 'grv-contacts-section-editor',
				style: {
					padding: '1.25rem',
					border: '1px dashed #c3a24d',
					borderRadius: '10px',
					background: '#faf8f3',
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
						{ title: __( 'Ліва колонка', '4wp-headless-app' ), initialOpen: true },
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
							label: __( 'Підпис месенджерів', '4wp-headless-app' ),
							value: attributes.socialLabel || '',
							onChange: function ( v ) { setAttributes( { socialLabel: v } ); },
						} ),
						el( ToggleControl, {
							label: __( 'Показувати Viber / Telegram / WhatsApp', '4wp-headless-app' ),
							checked: attributes.showMessengers !== false,
							onChange: function ( v ) { setAttributes( { showMessengers: v } ); },
						} )
					),
					el(
						PanelBody,
						{ title: __( 'Форма', '4wp-headless-app' ), initialOpen: true },
						el( TextControl, {
							label: __( 'Заголовок форми', '4wp-headless-app' ),
							value: attributes.formTitle || '',
							onChange: function ( v ) { setAttributes( { formTitle: v } ); },
						} ),
						el( TextControl, {
							label: __( 'Підзаголовок', '4wp-headless-app' ),
							value: attributes.formSubtitle || '',
							onChange: function ( v ) { setAttributes( { formSubtitle: v } ); },
						} ),
						el( TextControl, {
							label: __( 'Кнопка', '4wp-headless-app' ),
							value: attributes.buttonLabel || '',
							onChange: function ( v ) { setAttributes( { buttonLabel: v } ); },
						} ),
						el( TextControl, {
							label: __( 'Примітка під кнопкою', '4wp-headless-app' ),
							value: attributes.formNote || '',
							onChange: function ( v ) { setAttributes( { formNote: v } ); },
						} )
					)
				),
				el(
					'div',
					blockProps,
					el( 'p', { style: { color: '#b8911e', fontSize: '11px', letterSpacing: '0.12em', textTransform: 'uppercase', fontWeight: 600, margin: 0 } }, attributes.eyebrow || '' ),
					el( 'h3', { style: { margin: '8px 0 12px', fontSize: '22px', fontWeight: 800 } }, attributes.title || '' ),
					el( 'div', { style: { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px' } },
						el( 'div', { style: { padding: '12px', background: '#fff', borderRadius: '8px', border: '1px solid #e6dfd0', fontSize: '12px', color: '#666' } },
							__( 'Телефон / Email / Графік — з Site Settings', '4wp-headless-app' )
						),
						el( 'div', { style: { padding: '12px', background: '#1a1a1a', color: '#fff', borderRadius: '8px', fontSize: '13px' } },
							el( 'strong', {}, attributes.formTitle || '' ),
							el( 'p', { style: { margin: '6px 0 0', color: '#aaa', fontSize: '11px' } }, attributes.formSubtitle || '' ),
							el( 'p', { style: { margin: '10px 0 0', color: '#e5b93c', fontWeight: 700, fontSize: '11px', textTransform: 'uppercase' } }, attributes.buttonLabel || '' )
						)
					)
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
