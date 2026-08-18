( function ( wp ) {
	if ( ! wp?.blocks?.registerBlockType ) {
		return;
	}

	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, TextControl, TextareaControl, ToggleControl, CheckboxControl } = wp.components;
	const { createElement: el, Fragment } = wp.element;
	const { __ } = wp.i18n;

	const blockData = window.grvTeamSectionData || { members: [] };

	registerBlockType( 'grv/team-section', {
		apiVersion: 3,
		title: __( 'Team Section', '4wp-headless-app' ),
		category: 'grv',
		icon: 'groups',
		description: __( 'Карусель команди з CPT Team.', '4wp-headless-app' ),
		keywords: [ 'team', 'carousel', 'about', 'grv' ],
		attributes: {
			eyebrow: { type: 'string', default: 'Наша команда' },
			titleLine1: { type: 'string', default: 'Люди, які' },
			titleHighlight: { type: 'string', default: 'будують твій дім' },
			memberIds: { type: 'array', default: [] },
			showCta: { type: 'boolean', default: true },
			ctaTitle: { type: 'string', default: 'Хочеш до' },
			ctaHighlight: { type: 'string', default: 'команди?' },
			ctaText: { type: 'string', default: 'Набираємо хлопців, які вміють і хочуть працювати.' },
			ctaButtonLabel: { type: 'string', default: 'Доєднатись' },
			showBreadcrumb: { type: 'boolean', default: false },
			breadcrumbItems: { type: 'array', default: [] },
		},
		supports: { html: false, anchor: true },
		edit: function ( props ) {
			const { attributes, setAttributes } = props;
			const memberIds = Array.isArray( attributes.memberIds ) ? attributes.memberIds.map( Number ) : [];
			const members = blockData.members || [];

			const blockProps = useBlockProps( {
				className: 'grv-team-section-editor',
				style: {
					padding: '1.5rem',
					border: '1px dashed #c3a24d',
					borderRadius: '12px',
					background: '#141414',
					color: '#fff',
				},
			} );

			function toggleMember( id ) {
				const next = memberIds.includes( id )
					? memberIds.filter( function ( x ) { return x !== id; } )
					: memberIds.concat( [ id ] );
				setAttributes( { memberIds: next } );
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
							value: attributes.titleLine1 || '',
							onChange: function ( v ) { setAttributes( { titleLine1: v } ); },
						} ),
						el( TextControl, {
							label: __( 'Акцент (золотий)', '4wp-headless-app' ),
							value: attributes.titleHighlight || '',
							onChange: function ( v ) { setAttributes( { titleHighlight: v } ); },
						} )
					),
					el(
						PanelBody,
						{ title: __( 'Члени команди', '4wp-headless-app' ), initialOpen: true },
						el( 'p', { style: { fontSize: '12px', color: '#666', marginTop: 0 } },
							__( 'Порожньо = усі опубліковані Team. Порядок — menu order у CPT.', '4wp-headless-app' )
						),
						members.length
							? members.map( function ( member ) {
								return el( CheckboxControl, {
									key: member.id,
									label: member.title,
									checked: memberIds.includes( member.id ),
									onChange: function () { toggleMember( member.id ); },
								} );
							} )
							: el( 'p', {}, __( 'Немає опублікованих team_member. Додайте в CPT Team.', '4wp-headless-app' ) )
					),
					el(
						PanelBody,
						{ title: __( 'CTA-картка', '4wp-headless-app' ), initialOpen: false },
						el( ToggleControl, {
							label: __( 'Показати картку «Хочеш до команди?»', '4wp-headless-app' ),
							checked: attributes.showCta !== false,
							onChange: function ( v ) { setAttributes( { showCta: v } ); },
						} ),
						el( TextControl, {
							label: __( 'CTA title', '4wp-headless-app' ),
							value: attributes.ctaTitle || '',
							onChange: function ( v ) { setAttributes( { ctaTitle: v } ); },
						} ),
						el( TextControl, {
							label: __( 'CTA highlight', '4wp-headless-app' ),
							value: attributes.ctaHighlight || '',
							onChange: function ( v ) { setAttributes( { ctaHighlight: v } ); },
						} ),
						el( TextareaControl, {
							label: __( 'CTA text', '4wp-headless-app' ),
							value: attributes.ctaText || '',
							onChange: function ( v ) { setAttributes( { ctaText: v } ); },
						} ),
						el( TextControl, {
							label: __( 'CTA button', '4wp-headless-app' ),
							value: attributes.ctaButtonLabel || '',
							onChange: function ( v ) { setAttributes( { ctaButtonLabel: v } ); },
							help: __( 'Кнопка дзвонить на телефон із налаштувань сайту.', '4wp-headless-app' ),
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
					el( 'p', { style: { color: '#c9a227', fontSize: '11px', letterSpacing: '0.12em', textTransform: 'uppercase', fontWeight: 600, margin: 0 } }, attributes.eyebrow || '' ),
					el( 'h3', { style: { margin: '10px 0 0', fontSize: '28px', fontWeight: 900, lineHeight: 1.15 } },
						( attributes.titleLine1 || '' ),
						el( 'br' ),
						el( 'span', { style: { color: '#c9a227' } }, attributes.titleHighlight || '' )
					),
					el( 'p', { style: { margin: '12px 0 0', fontSize: '13px', color: '#999' } },
						memberIds.length
							? __( 'Вибрано: ', '4wp-headless-app' ) + memberIds.length
							: __( 'Усі члени CPT Team', '4wp-headless-app' )
					)
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
