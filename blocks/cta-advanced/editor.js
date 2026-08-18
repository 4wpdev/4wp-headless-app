( function ( wp ) {
	if ( ! wp?.blocks?.registerBlockType ) {
		return;
	}

	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls, MediaUpload, MediaUploadCheck } = wp.blockEditor;
	const { PanelBody, TextControl, TextareaControl, ToggleControl, Button } = wp.components;
	const { createElement: el, Fragment } = wp.element;
	const { __ } = wp.i18n;

	const defaults = window.grvCtaAdvancedDefaults || {};

	registerBlockType( 'grv/cta-advanced', {
		apiVersion: 3,
		title: __( 'CTA Advanced', '4wp-headless-app' ),
		category: 'grv',
		icon: 'video-alt3',
		description: __( 'Відео, цитата, соцмережі та кнопки заклику до дії.', '4wp-headless-app' ),
		keywords: [ 'cta', 'video', 'social', 'grv' ],
		attributes: {
			eyebrow: { type: 'string', default: defaults.eyebrow || '' },
			headingLine1: { type: 'string', default: defaults.headingLine1 || '' },
			headingLine2: { type: 'string', default: defaults.headingLine2 || '' },
			introText: { type: 'string', default: defaults.introText || '' },
			introHighlight: { type: 'string', default: defaults.introHighlight || '' },
			bodyText: { type: 'string', default: defaults.bodyText || '' },
			quote: { type: 'string', default: defaults.quote || '' },
			quoteAuthor: { type: 'string', default: defaults.quoteAuthor || '' },
			socialLabel: { type: 'string', default: defaults.socialLabel || '' },
			showSocialLinks: { type: 'boolean', default: defaults.showSocialLinks !== false },
			videoUrl: { type: 'string', default: defaults.videoUrl || '' },
			videoId: { type: 'number', default: 0 },
			primaryButtonLabel: { type: 'string', default: defaults.primaryButtonLabel || '' },
			primaryButtonHref: { type: 'string', default: defaults.primaryButtonHref || '' },
			secondaryButtonLabel: { type: 'string', default: defaults.secondaryButtonLabel || '' },
			secondaryButtonHref: { type: 'string', default: defaults.secondaryButtonHref || '' },
			showBreadcrumb: { type: 'boolean', default: false },
			breadcrumbItems: { type: 'array', default: [] },
		},
		supports: { html: false, anchor: true },
		edit: function ( props ) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps( {
				className: 'grv-cta-advanced-editor',
				style: {
					padding: '1.25rem',
					border: '1px dashed #c3a24d',
					borderRadius: '8px',
					background: '#141414',
					color: '#f5f5f5',
				},
			} );

			function setVideo( media ) {
				if ( ! media ) {
					setAttributes( { videoId: 0, videoUrl: '' } );
					return;
				}
				const url = media.url || '';
				setAttributes( {
					videoId: media.id || 0,
					videoUrl: url,
				} );
			}

			return el(
				Fragment,
				{},
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __( 'Заголовок', '4wp-headless-app' ), initialOpen: true },
						el( TextControl, {
							label: __( 'Eyebrow', '4wp-headless-app' ),
							value: attributes.eyebrow || '',
							onChange: function ( v ) { setAttributes( { eyebrow: v } ); },
						} ),
						el( TextControl, {
							label: __( 'Рядок 1', '4wp-headless-app' ),
							value: attributes.headingLine1 || '',
							onChange: function ( v ) { setAttributes( { headingLine1: v } ); },
						} ),
						el( TextControl, {
							label: __( 'Рядок 2 (золотий)', '4wp-headless-app' ),
							value: attributes.headingLine2 || '',
							onChange: function ( v ) { setAttributes( { headingLine2: v } ); },
						} )
					),
					el(
						PanelBody,
						{ title: __( 'Текст', '4wp-headless-app' ), initialOpen: false },
						el( TextareaControl, {
							label: __( 'Вступ', '4wp-headless-app' ),
							value: attributes.introText || '',
							onChange: function ( v ) { setAttributes( { introText: v } ); },
						} ),
						el( TextControl, {
							label: __( 'Підсвітка у вступі', '4wp-headless-app' ),
							value: attributes.introHighlight || '',
							onChange: function ( v ) { setAttributes( { introHighlight: v } ); },
						} ),
						el( TextareaControl, {
							label: __( 'Другий абзац', '4wp-headless-app' ),
							value: attributes.bodyText || '',
							onChange: function ( v ) { setAttributes( { bodyText: v } ); },
						} ),
						el( TextareaControl, {
							label: __( 'Цитата', '4wp-headless-app' ),
							value: attributes.quote || '',
							onChange: function ( v ) { setAttributes( { quote: v } ); },
						} ),
						el( TextControl, {
							label: __( 'Автор цитати', '4wp-headless-app' ),
							value: attributes.quoteAuthor || '',
							onChange: function ( v ) { setAttributes( { quoteAuthor: v } ); },
						} )
					),
					el(
						PanelBody,
						{ title: __( 'Відео', '4wp-headless-app' ), initialOpen: false },
						el( TextControl, {
							label: __( 'URL відео', '4wp-headless-app' ),
							value: attributes.videoUrl || '',
							onChange: function ( v ) { setAttributes( { videoUrl: v, videoId: 0 } ); },
						} ),
						el(
							MediaUploadCheck,
							{},
							el( MediaUpload, {
								onSelect: setVideo,
								allowedTypes: [ 'video' ],
								value: attributes.videoId || 0,
								render: function ( obj ) {
									return el(
										Button,
										{ variant: 'secondary', onClick: obj.open },
										attributes.videoId
											? __( 'Змінити відео', '4wp-headless-app' )
											: __( 'Завантажити відео', '4wp-headless-app' )
									);
								},
							} )
						),
						attributes.videoId
							? el(
								Button,
								{
									variant: 'link',
									isDestructive: true,
									onClick: function () { setVideo( null ); },
									style: { marginTop: '8px' },
								},
								__( 'Прибрати відео', '4wp-headless-app' )
							)
							: null
					),
					el(
						PanelBody,
						{ title: __( 'Месенджери та кнопки', '4wp-headless-app' ), initialOpen: false },
						el( ToggleControl, {
							label: __( 'Показувати месенджери (Viber, Telegram, WhatsApp)', '4wp-headless-app' ),
							help: __( 'Посилання з панелі Social Links — лише direct-канали, не Instagram/Facebook.', '4wp-headless-app' ),
							checked: attributes.showSocialLinks !== false,
							onChange: function ( v ) { setAttributes( { showSocialLinks: v } ); },
						} ),
						el( TextControl, {
							label: __( 'Підпис над месенджерами', '4wp-headless-app' ),
							value: attributes.socialLabel || '',
							onChange: function ( v ) { setAttributes( { socialLabel: v } ); },
						} ),
						el( TextControl, {
							label: __( 'Кнопка 1', '4wp-headless-app' ),
							value: attributes.primaryButtonLabel || '',
							onChange: function ( v ) { setAttributes( { primaryButtonLabel: v } ); },
						} ),
						el( TextControl, {
							label: __( 'Посилання 1', '4wp-headless-app' ),
							value: attributes.primaryButtonHref || '',
							onChange: function ( v ) { setAttributes( { primaryButtonHref: v } ); },
						} ),
						el( TextControl, {
							label: __( 'Кнопка 2', '4wp-headless-app' ),
							value: attributes.secondaryButtonLabel || '',
							onChange: function ( v ) { setAttributes( { secondaryButtonLabel: v } ); },
						} ),
						el( TextControl, {
							label: __( 'Посилання 2', '4wp-headless-app' ),
							value: attributes.secondaryButtonHref || '',
							onChange: function ( v ) { setAttributes( { secondaryButtonHref: v } ); },
						} )
					),
					window.grvBreadcrumbInspector ? window.grvBreadcrumbInspector( {
						attributes: attributes,
						setAttributes: setAttributes,
						PanelBody: PanelBody,
						ToggleControl: ToggleControl,
						TextControl: TextControl,
						el: el,
						__: __,
					} ) : null
				),
				el(
					'div',
					blockProps,
					el( 'div', { style: { fontSize: '11px', color: '#c9a227', letterSpacing: '0.12em', textTransform: 'uppercase', marginBottom: '8px' } }, attributes.eyebrow || '' ),
					el(
						'div',
						{ style: { fontSize: '22px', fontWeight: 800, lineHeight: 1.25, marginBottom: '12px' } },
						( attributes.headingLine1 || '' ) + ' ',
						el( 'span', { style: { color: '#c9a227' } }, attributes.headingLine2 || '' )
					),
					el( 'div', { style: { fontSize: '13px', color: '#bbb', marginBottom: '12px', lineHeight: 1.5 } }, attributes.introText || '' ),
					attributes.videoUrl
						? el( 'div', { style: { fontSize: '12px', color: '#888', marginBottom: '8px' } }, '▶ ' + attributes.videoUrl )
						: null,
					el(
						'div',
						{ style: { display: 'flex', gap: '8px', flexWrap: 'wrap', marginTop: '12px' } },
						el( 'span', { style: { padding: '6px 12px', background: '#c9a227', color: '#141414', borderRadius: '4px', fontSize: '12px', fontWeight: 700 } }, attributes.primaryButtonLabel || '' ),
						el( 'span', { style: { padding: '6px 12px', border: '1px solid #555', borderRadius: '4px', fontSize: '12px' } }, attributes.secondaryButtonLabel || '' )
					)
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
