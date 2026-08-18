( function ( wp ) {
	if ( ! wp?.blocks?.registerBlockType ) {
		return;
	}

	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, TextControl, SelectControl, CheckboxControl } = wp.components;
	const { createElement: el, Fragment } = wp.element;
	const { __ } = wp.i18n;

	const blockData = window.grvWorksGalleryData || { works: [], geoAreas: [] };

	registerBlockType( 'grv/works-gallery', {
		apiVersion: 3,
		title: __( 'Works Gallery', '4wp-headless-app' ),
		category: 'grv',
		icon: 'format-gallery',
		description: __( 'Галерея робіт (карусель або masonry-грід) з CPT work_item.', '4wp-headless-app' ),
		keywords: [ 'portfolio', 'gallery', 'works', 'masonry', 'region', 'grv' ],
		attributes: {
			layout: { type: 'string', default: 'carousel' },
			eyebrow: { type: 'string', default: 'Фасади · Портфоліо' },
			title: { type: 'string', default: 'Наші фасадні роботи' },
			ctaLabel: { type: 'string', default: 'Всі роботи' },
			ctaHref: { type: 'string', default: '/our-works' },
			catalogLine: { type: 'string', default: 'facade' },
			geoArea: { type: 'string', default: '' },
			workIds: { type: 'array', default: [] },
			showTypeFilter: { type: 'boolean', default: false },
			showLocationFilter: { type: 'boolean', default: false },
			showRegionFilter: { type: 'boolean', default: false },
		},
		supports: { html: false, anchor: true },
		edit: function ( props ) {
			const { attributes, setAttributes } = props;
			const workIds = Array.isArray( attributes.workIds ) ? attributes.workIds.map( Number ) : [];
			const works = blockData.works || [];
			const geoAreas = blockData.geoAreas || [];
			const layout = attributes.layout === 'masonry' ? 'masonry' : 'carousel';
			const showLocation = !!( attributes.showLocationFilter || attributes.showRegionFilter );

			const geoOptions = [ { label: __( 'Усі регіони (без обмеження)', '4wp-headless-app' ), value: '' } ].concat(
				geoAreas.map( function ( g ) {
					return { label: g.label, value: g.slug };
				} )
			);

			const blockProps = useBlockProps( {
				className: 'grv-works-gallery-editor',
				style: {
					padding: '1.25rem',
					border: '1px dashed #c3a24d',
					borderRadius: '10px',
					background: '#faf8f3',
				},
			} );

			function toggleWork( id ) {
				const next = workIds.includes( id )
					? workIds.filter( function ( x ) { return x !== id; } )
					: workIds.concat( [ id ] );
				setAttributes( { workIds: next } );
			}

			function setLocationFilter( on ) {
				setAttributes( { showLocationFilter: !! on, showRegionFilter: !! on } );
			}

			return el(
				Fragment,
				{},
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __( 'Галерея', '4wp-headless-app' ), initialOpen: true },
						el( SelectControl, {
							label: __( 'Режим відображення', '4wp-headless-app' ),
							value: layout,
							options: [
								{ label: __( 'Карусель', '4wp-headless-app' ), value: 'carousel' },
								{ label: __( 'Masonry (грід)', '4wp-headless-app' ), value: 'masonry' },
							],
							onChange: function ( v ) {
								setAttributes( { layout: v === 'masonry' ? 'masonry' : 'carousel' } );
							},
						} ),
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
							label: __( 'Кнопка', '4wp-headless-app' ),
							value: attributes.ctaLabel || '',
							onChange: function ( v ) { setAttributes( { ctaLabel: v } ); },
							help: __( 'Порожньо = кнопка не показується.', '4wp-headless-app' ),
						} ),
						el( TextControl, {
							label: __( 'Посилання кнопки', '4wp-headless-app' ),
							value: attributes.ctaHref || '',
							onChange: function ( v ) { setAttributes( { ctaHref: v } ); },
						} )
					),
					el(
						PanelBody,
						{ title: __( 'Регіон і фільтри', '4wp-headless-app' ), initialOpen: true },
						el( SelectControl, {
							label: __( 'Geo Area (регіон сторінки)', '4wp-headless-app' ),
							value: attributes.geoArea || '',
							options: geoOptions,
							onChange: function ( v ) {
								var patch = { geoArea: v || '' };
								if ( v ) {
									patch.catalogLine = '';
									patch.showTypeFilter = true;
									patch.showLocationFilter = true;
									patch.showRegionFilter = true;
									patch.layout = 'masonry';
								}
								setAttributes( patch );
							},
							help: __( 'Для сторінки регіону: показує лише роботи цього geo_area. Увімкне тип + локації.', '4wp-headless-app' ),
						} ),
						el( SelectControl, {
							label: __( 'Лінія каталогу (жорсткий фільтр)', '4wp-headless-app' ),
							value: attributes.catalogLine || '',
							options: [
								{ label: __( 'Усі', '4wp-headless-app' ), value: '' },
								{ label: 'Construction', value: 'construction' },
								{ label: 'Facade', value: 'facade' },
								{ label: 'Repair', value: 'repair' },
							],
							onChange: function ( v ) { setAttributes( { catalogLine: v } ); },
							help: attributes.showTypeFilter
								? __( 'Ігнорується, поки увімкнено «Фільтр типу».', '4wp-headless-app' )
								: __( 'Портфоліо: окремий блок на тип. Регіон: залиште «Усі».', '4wp-headless-app' ),
						} ),
						el( CheckboxControl, {
							label: __( 'Фільтр типу (Будівництво / Фасад / Ремонт)', '4wp-headless-app' ),
							checked: !! attributes.showTypeFilter,
							onChange: function ( v ) { setAttributes( { showTypeFilter: !! v } ); },
							help: __( 'Для сторінки регіону — так. На портфоліо тип зазвичай окремими блоками.', '4wp-headless-app' ),
						} ),
						el( CheckboxControl, {
							label: __( 'Фільтр локацій', '4wp-headless-app' ),
							checked: showLocation,
							onChange: setLocationFilter,
							help: attributes.geoArea
								? __( 'Локації з підпису обʼєкта (Стурміка, Дачне…) у межах вибраного регіону.', '4wp-headless-app' )
								: __( 'Міста з Geo Area + локації з підпису обʼєкта.', '4wp-headless-app' ),
						} )
					),
					el(
						PanelBody,
						{ title: __( 'Роботи (опційно)', '4wp-headless-app' ), initialOpen: false },
						works.length
							? works.map( function ( work ) {
								return el( CheckboxControl, {
									key: work.id,
									label: work.title + ( work.catalog_line ? ' (' + work.catalog_line + ')' : '' ),
									checked: workIds.includes( work.id ),
									onChange: function () { toggleWork( work.id ); },
								} );
							} )
							: el( 'p', {}, __( 'Немає опублікованих work_item.', '4wp-headless-app' ) )
					)
				),
				el(
					'div',
					blockProps,
					el( 'p', { style: { color: '#b8911e', fontSize: '11px', letterSpacing: '0.12em', textTransform: 'uppercase', fontWeight: 600, margin: 0 } }, attributes.eyebrow || '' ),
					el( 'h3', { style: { margin: '8px 0 0', fontSize: '24px', fontWeight: 800 } }, attributes.title || '' ),
					el( 'p', { style: { margin: '12px 0 0', fontSize: '13px', color: '#666' } },
						( layout === 'masonry' ? 'Masonry' : 'Карусель' ) +
						( attributes.geoArea ? ' · geo: ' + attributes.geoArea : '' ) +
						( attributes.showTypeFilter ? ' · фільтр типу' : '' ) +
						( showLocation ? ' · фільтр локацій' : '' )
					)
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
