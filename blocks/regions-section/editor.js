( function ( wp ) {
	if ( ! wp?.blocks?.registerBlockType ) {
		return;
	}

	const { registerBlockType } = wp.blocks;
	const { useBlockProps, RichText } = wp.blockEditor;
	const { Button, TextControl, TextareaControl, ToggleControl } = wp.components;
	const { createElement: el, useMemo } = wp.element;
	const { __ } = wp.i18n;

	const blockData = window.grvRegionsSectionData || { defaults: { stats: [] } };

	function ensureStats( stats ) {
		if ( Array.isArray( stats ) && stats.length ) {
			return stats.map( function ( stat ) {
				return { value: stat.value || '', label: stat.label || '' };
			} );
		}
		return ( blockData.defaults.stats || [] ).map( function ( stat ) {
			return Object.assign( {}, stat );
		} );
	}

	registerBlockType( 'grv/regions-section', {
		apiVersion: 3,
		title: __( 'Regions Section', '4wp-headless-app' ),
		category: 'grv',
		icon: 'location-alt',
		description: __( 'Географія: акордеон регіонів, статистика, CTA. Дані регіонів — з Geo Areas сайту.', '4wp-headless-app' ),
		keywords: [ 'regions', 'geo', 'map', 'grv' ],
		attributes: {
			subTitle: { type: 'string', default: blockData.defaults.subTitle || 'Географія' },
			title: { type: 'string', default: blockData.defaults.title || '' },
			intro: { type: 'string', default: blockData.defaults.intro || '' },
			useGeoCatalog: { type: 'boolean', default: true },
			showStats: { type: 'boolean', default: true },
			stats: { type: 'array', default: [] },
			regions: { type: 'array', default: [] },
			ctaTitle: { type: 'string', default: blockData.defaults.ctaTitle || '' },
			ctaText: { type: 'string', default: blockData.defaults.ctaText || '' },
			ctaButtonLabel: { type: 'string', default: blockData.defaults.ctaButtonLabel || '' },
			ctaButtonHref: { type: 'string', default: blockData.defaults.ctaButtonHref || '/contacts' },
		},
		supports: { html: false, anchor: true },
		edit: function ( props ) {
			const { attributes, setAttributes } = props;
			const stats = useMemo( function () {
				return ensureStats( attributes.stats );
			}, [ attributes.stats ] );

			const blockProps = useBlockProps( {
				className: 'grv-regions-section-editor',
				style: {
					padding: '1.25rem',
					border: '1px dashed #c3a24d',
					borderRadius: '10px',
					background: '#faf8f3',
				},
			} );

			function updateStat( index, patch ) {
				const next = stats.map( function ( stat, i ) {
					return i === index ? Object.assign( {}, stat, patch ) : stat;
				} );
				setAttributes( { stats: next } );
			}

			return el(
				'div',
				blockProps,
				el(
					'div',
					{ className: '4wp-editor-block-intro', style: { textAlign: 'center', marginBottom: '20px', paddingBottom: '16px', borderBottom: '1px solid #e6dfd0' } },
					el( RichText, {
						tagName: 'p',
						value: attributes.subTitle || '',
						onChange: function ( value ) { setAttributes( { subTitle: value } ); },
						placeholder: __( 'SubTitle', '4wp-headless-app' ),
						style: { color: '#b8911e', fontSize: '11px', letterSpacing: '0.14em', textTransform: 'uppercase', fontWeight: 600, margin: 0 },
					} ),
					el( RichText, {
						tagName: 'h2',
						value: attributes.title || '',
						onChange: function ( value ) { setAttributes( { title: value } ); },
						placeholder: __( 'Title', '4wp-headless-app' ),
						style: { fontSize: '28px', fontWeight: 800, margin: '8px 0', lineHeight: 1.2 },
					} ),
					el( RichText, {
						tagName: 'p',
						value: attributes.intro || '',
						onChange: function ( value ) { setAttributes( { intro: value } ); },
						placeholder: 'Будівництво, фасадні роботи…',
						style: { color: '#666', fontSize: '14px', maxWidth: '640px', margin: '12px auto 0', lineHeight: 1.5 },
					} )
				),
				el( ToggleControl, {
					label: __( 'Use site Geo Areas catalog', '4wp-headless-app' ),
					help: __( 'Regions accordion is built from exported geo_area data. Custom regions override — later.', '4wp-headless-app' ),
					checked: attributes.useGeoCatalog !== false,
					onChange: function ( value ) { setAttributes( { useGeoCatalog: value } ); },
				} ),
				el( ToggleControl, {
					label: __( 'Show stats bar', '4wp-headless-app' ),
					checked: !! attributes.showStats,
					onChange: function ( value ) { setAttributes( { showStats: value } ); },
				} ),
				attributes.showStats
					? stats.map( function ( stat, index ) {
						return el(
							'div',
							{
								key: 'stat-' + index,
								style: { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '8px', marginBottom: '8px' },
							},
							el( TextControl, {
								label: __( 'Stat value', '4wp-headless-app' ),
								value: stat.value,
								onChange: function ( value ) { updateStat( index, { value: value } ); },
							} ),
							el( TextControl, {
								label: __( 'Stat label', '4wp-headless-app' ),
								value: stat.label,
								onChange: function ( value ) { updateStat( index, { label: value } ); },
							} )
						);
					} )
					: null,
				attributes.showStats
					? el( Button, {
						variant: 'secondary',
						onClick: function () { setAttributes( { stats: stats.concat( [ { value: '', label: '' } ] ) } ); },
						style: { marginBottom: '16px' },
					}, __( 'Add stat', '4wp-headless-app' ) )
					: null,
				el( TextControl, {
					label: __( 'CTA title', '4wp-headless-app' ),
					value: attributes.ctaTitle || '',
					onChange: function ( value ) { setAttributes( { ctaTitle: value } ); },
				} ),
				el( TextareaControl, {
					label: __( 'CTA text', '4wp-headless-app' ),
					value: attributes.ctaText || '',
					onChange: function ( value ) { setAttributes( { ctaText: value } ); },
					rows: 3,
				} ),
				el( TextControl, {
					label: __( 'CTA button', '4wp-headless-app' ),
					value: attributes.ctaButtonLabel || '',
					onChange: function ( value ) { setAttributes( { ctaButtonLabel: value } ); },
				} ),
				el( TextControl, {
					label: __( 'CTA link', '4wp-headless-app' ),
					value: attributes.ctaButtonHref || '',
					onChange: function ( value ) { setAttributes( { ctaButtonHref: value } ); },
				} ),
				attributes.useGeoCatalog !== false
					? el( 'p', { style: { marginTop: '16px', fontSize: '12px', color: '#666', fontStyle: 'italic' } }, __( 'Preview: oblasts and districts load from site Geo Areas on the frontend.', '4wp-headless-app' ) )
					: el( 'p', { style: { marginTop: '16px', fontSize: '12px', color: '#a00' } }, __( 'Custom regions mode: editor UI coming soon. Add regions in seed/export for now.', '4wp-headless-app' ) )
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
