( function ( wp ) {
	if ( ! wp?.blocks?.registerBlockType ) {
		return;
	}

	const { registerBlockType } = wp.blocks;
	const { useBlockProps, RichText, MediaUpload, MediaUploadCheck } = wp.blockEditor;
	const { Button, SelectControl, TextControl, TextareaControl, PanelBody, ToggleControl } = wp.components;
	const { createElement: el, useMemo } = wp.element;
	const { __ } = wp.i18n;

	const blockData = window.grvLinkedSectionData || { defaults: {}, presets: {} };

	function ensureBullets( bullets ) {
		if ( Array.isArray( bullets ) && bullets.length ) {
			return bullets.map( function ( item ) {
				return typeof item === 'string' ? item : ( item.text || '' );
			} );
		}
		return [];
	}

	function ensureStats( stats ) {
		if ( Array.isArray( stats ) && stats.length ) {
			return stats.map( function ( stat ) {
				return { value: stat.value || '', label: stat.label || '' };
			} );
		}
		return [];
	}

	function emptyStatRows( count ) {
		var rows = [];
		var n = count || 4;
		for ( var i = 0; i < n; i += 1 ) {
			rows.push( { value: '', label: '' } );
		}
		return rows;
	}

	function ensureGallery( gallery ) {
		if ( Array.isArray( gallery ) && gallery.length ) {
			return gallery.map( function ( item ) {
				return {
					imageUrl: item.imageUrl || item.url || '',
					imageId: item.imageId || 0,
				};
			} );
		}
		return [];
	}

	registerBlockType( 'grv/linked-section', {
		apiVersion: 3,
		title: __( 'Linked Section', '4wp-headless-app' ),
		category: 'grv',
		icon: 'align-pull-left',
		description: __( 'Текст + відео або галерея. Вирівнювання, статистика, кнопки.', '4wp-headless-app' ),
		keywords: [ 'linked', 'video', 'gallery', 'grv' ],
		attributes: {
			subTitle: { type: 'string', default: blockData.defaults.sub_title || '' },
			align: { type: 'string', default: 'left' },
			mediaType: { type: 'string', default: 'video' },
			videoUrl: { type: 'string', default: '' },
			videoId: { type: 'number', default: 0 },
			gallery: { type: 'array', default: [] },
			headingLine1: { type: 'string', default: '' },
			headingHighlight: { type: 'string', default: '' },
			headingLevel: { type: 'string', default: 'h2' },
			intro: { type: 'string', default: '' },
			bullets: { type: 'array', default: [] },
			showStats: { type: 'boolean', default: false },
			stats: { type: 'array', default: [] },
			primaryButtonLabel: { type: 'string', default: 'Дізнатись більше' },
			primaryButtonHref: { type: 'string', default: '/' },
			secondaryButtonLabel: { type: 'string', default: 'Консультація' },
			secondaryButtonHref: { type: 'string', default: '/contacts' },
		},
		supports: { html: false, anchor: true },
		edit: function ( props ) {
			const { attributes, setAttributes } = props;
			const bullets = useMemo( function () { return ensureBullets( attributes.bullets ); }, [ attributes.bullets ] );
			const stats = useMemo( function () { return ensureStats( attributes.stats ); }, [ attributes.stats ] );
			const gallery = useMemo( function () { return ensureGallery( attributes.gallery ); }, [ attributes.gallery ] );
			const headingTag = attributes.headingLevel === 'h3' ? 'h3' : 'h2';

			const blockProps = useBlockProps( {
				className: 'grv-linked-section-editor',
				style: {
					padding: '1.25rem',
					border: '1px dashed #c3a24d',
					borderRadius: '10px',
					background: '#faf8f3',
				},
			} );

			function applyPreset( key ) {
				const preset = blockData.presets[ key ];
				if ( ! preset ) {
					return;
				}
				setAttributes( {
					subTitle: preset.sub_title || '',
					align: preset.align || 'left',
					mediaType: preset.media_type || 'video',
					videoUrl: preset.video_url || '',
					videoId: 0,
					gallery: ( preset.gallery || [] ).map( function ( url ) {
						return { imageUrl: url, imageId: 0 };
					} ),
					headingLine1: preset.heading_line1 || '',
					headingHighlight: preset.heading_highlight || '',
					headingLevel: preset.heading_level || 'h2',
					intro: preset.intro || '',
					bullets: preset.bullets || [],
					showStats: !! preset.show_stats,
					stats: preset.stats || [],
					primaryButtonLabel: preset.primary_button_label || '',
					primaryButtonHref: preset.primary_button_href || '/',
					secondaryButtonLabel: preset.secondary_button_label || '',
					secondaryButtonHref: preset.secondary_button_href || '/contacts',
				} );
			}

			function updateBullet( index, value ) {
				const next = bullets.slice();
				next[ index ] = value;
				setAttributes( { bullets: next } );
			}

			function updateGalleryItem( index, patch ) {
				const next = gallery.map( function ( item, i ) {
					return i === index ? Object.assign( {}, item, patch ) : item;
				} );
				setAttributes( { gallery: next } );
			}

			const isVideo = ( attributes.mediaType || 'video' ) === 'video';
			const mediaOnRight = ( attributes.align || 'left' ) === 'right';

			return el(
				'div',
				blockProps,
				el(
					'div',
					{ style: { display: 'flex', gap: '8px', flexWrap: 'wrap', marginBottom: '16px' } },
					el( Button, { variant: 'secondary', onClick: function () { applyPreset( 'build' ); } }, __( 'Preset: Build', '4wp-headless-app' ) ),
					el( Button, { variant: 'secondary', onClick: function () { applyPreset( 'facade' ); } }, __( 'Preset: Facade', '4wp-headless-app' ) ),
					el( Button, { variant: 'secondary', onClick: function () { applyPreset( 'repair' ); } }, __( 'Preset: Repair', '4wp-headless-app' ) )
				),
				el( SelectControl, {
					label: __( 'Media position', '4wp-headless-app' ),
					value: attributes.align || 'left',
					options: [
						{ label: __( 'Media left', '4wp-headless-app' ), value: 'left' },
						{ label: __( 'Media right', '4wp-headless-app' ), value: 'right' },
					],
					onChange: function ( value ) { setAttributes( { align: value } ); },
				} ),
				el( SelectControl, {
					label: __( 'Media type', '4wp-headless-app' ),
					value: attributes.mediaType || 'video',
					options: [
						{ label: __( 'Video', '4wp-headless-app' ), value: 'video' },
						{ label: __( 'Gallery', '4wp-headless-app' ), value: 'gallery' },
					],
					onChange: function ( value ) { setAttributes( { mediaType: value } ); },
				} ),
				el( SelectControl, {
					label: __( 'Heading', '4wp-headless-app' ),
					value: attributes.headingLevel === 'h3' ? 'h3' : 'h2',
					options: [
						{ label: 'H2', value: 'h2' },
						{ label: 'H3', value: 'h3' },
					],
					onChange: function ( value ) { setAttributes( { headingLevel: value === 'h3' ? 'h3' : 'h2' } ); },
					help: __( 'За замовчуванням H2. Можна змінити на H3.', '4wp-headless-app' ),
				} ),
				el( ToggleControl, {
					label: __( 'Show stats', '4wp-headless-app' ),
					checked: !! attributes.showStats,
					onChange: function ( value ) {
						var patch = { showStats: !! value };
						// Seed input rows when enabling — empty array = no fields visible.
						if ( value && ( ! Array.isArray( attributes.stats ) || ! attributes.stats.length ) ) {
							patch.stats = emptyStatRows( 4 );
						}
						setAttributes( patch );
					},
					help: attributes.showStats
						? __( 'Заповніть значення і підписи нижче.', '4wp-headless-app' )
						: __( 'Увімкніть, щоб додати блоки статистики.', '4wp-headless-app' ),
				} ),
				el(
					'div',
					{ className: 'grv-linked-section-editor__columns' },
					el(
						'div',
						{ className: 'grv-linked-section-editor__content', style: { order: mediaOnRight ? 2 : 1, padding: '12px', background: '#fff', border: '1px solid #e6dfd0', borderRadius: '8px' } },
						el( RichText, {
							tagName: 'p',
							value: attributes.subTitle || '',
							onChange: function ( value ) { setAttributes( { subTitle: value } ); },
							placeholder: __( 'SubTitle', '4wp-headless-app' ),
						} ),
						el( RichText, {
							tagName: headingTag,
							value: attributes.headingLine1 || '',
							onChange: function ( value ) { setAttributes( { headingLine1: value } ); },
							placeholder: __( 'Heading line 1', '4wp-headless-app' ),
						} ),
						el( TextControl, {
							label: __( 'Heading highlight (gold)', '4wp-headless-app' ),
							value: attributes.headingHighlight || '',
							onChange: function ( value ) { setAttributes( { headingHighlight: value } ); },
						} ),
						el( TextareaControl, {
							label: __( 'Intro', '4wp-headless-app' ),
							value: attributes.intro || '',
							onChange: function ( value ) { setAttributes( { intro: value } ); },
							rows: 3,
						} ),
						bullets.map( function ( bullet, index ) {
							return el( TextControl, {
								key: 'bullet-' + index,
								label: __( 'Bullet', '4wp-headless-app' ) + ' ' + ( index + 1 ),
								value: bullet,
								onChange: function ( value ) { updateBullet( index, value ); },
							} );
						} ),
						el( Button, {
							variant: 'secondary',
							onClick: function () { setAttributes( { bullets: bullets.concat( [ '' ] ) } ); },
						}, __( 'Add bullet', '4wp-headless-app' ) ),
						attributes.showStats
							? el(
								'div',
								{
									style: {
										marginTop: '16px',
										marginBottom: '8px',
										padding: '12px',
										border: '1px solid #e6dfd0',
										borderRadius: '8px',
										background: '#faf8f3',
									},
								},
								el( 'p', {
									style: {
										margin: '0 0 12px',
										fontWeight: 700,
										fontSize: '12px',
										letterSpacing: '0.08em',
										textTransform: 'uppercase',
										color: '#b8911e',
									},
								}, __( 'Статистика', '4wp-headless-app' ) ),
								( stats.length ? stats : emptyStatRows( 4 ) ).map( function ( stat, index ) {
									return el(
										'div',
										{
											key: 'stat-' + index,
											className: 'grv-linked-section-editor__stat-row',
											style: {
												display: 'grid',
												gridTemplateColumns: '1fr 1fr auto',
												gap: '8px',
												alignItems: 'end',
												marginBottom: '10px',
											},
										},
										el( TextControl, {
											label: __( 'Значення', '4wp-headless-app' ) + ' ' + ( index + 1 ),
											value: stat.value,
											placeholder: '15+',
											onChange: function ( value ) {
												var base = stats.length ? stats : emptyStatRows( 4 );
												var next = base.map( function ( row, i ) {
													return i === index ? Object.assign( {}, row, { value: value } ) : row;
												} );
												setAttributes( { stats: next } );
											},
										} ),
										el( TextControl, {
											label: __( 'Підпис', '4wp-headless-app' ),
											value: stat.label,
											placeholder: __( 'років досвіду', '4wp-headless-app' ),
											onChange: function ( value ) {
												var base = stats.length ? stats : emptyStatRows( 4 );
												var next = base.map( function ( row, i ) {
													return i === index ? Object.assign( {}, row, { label: value } ) : row;
												} );
												setAttributes( { stats: next } );
											},
										} ),
										el( Button, {
											variant: 'link',
											isDestructive: true,
											onClick: function () {
												var base = stats.length ? stats : emptyStatRows( 4 );
												setAttributes( {
													stats: base.filter( function ( _, i ) { return i !== index; } ),
												} );
											},
										}, '×' )
									);
								} ),
								el( Button, {
									variant: 'secondary',
									onClick: function () {
										var base = stats.length ? stats : emptyStatRows( 4 );
										setAttributes( { stats: base.concat( [ { value: '', label: '' } ] ) } );
									},
								}, __( 'Додати показник', '4wp-headless-app' ) )
							)
							: null,
						el( TextControl, {
							label: __( 'Primary button', '4wp-headless-app' ),
							value: attributes.primaryButtonLabel || '',
							onChange: function ( value ) { setAttributes( { primaryButtonLabel: value } ); },
							help: __( 'Порожньо = кнопка не показується на сайті.', '4wp-headless-app' ),
						} ),
						el( TextControl, {
							label: __( 'Primary link', '4wp-headless-app' ),
							value: attributes.primaryButtonHref || '',
							onChange: function ( value ) { setAttributes( { primaryButtonHref: value } ); },
						} ),
						el( TextControl, {
							label: __( 'Secondary button', '4wp-headless-app' ),
							value: attributes.secondaryButtonLabel || '',
							onChange: function ( value ) { setAttributes( { secondaryButtonLabel: value } ); },
							help: __( 'Порожньо = кнопка не показується на сайті.', '4wp-headless-app' ),
						} ),
						el( TextControl, {
							label: __( 'Secondary link', '4wp-headless-app' ),
							value: attributes.secondaryButtonHref || '',
							onChange: function ( value ) { setAttributes( { secondaryButtonHref: value } ); },
						} )
					),
					el(
						'div',
						{ className: 'grv-linked-section-editor__media', style: { order: mediaOnRight ? 1 : 2, padding: '12px', background: '#fff', border: '1px solid #e6dfd0', borderRadius: '8px' } },
						isVideo
							? el(
								'div',
								{},
								attributes.videoUrl
									? el( 'video', { src: attributes.videoUrl, controls: true, style: { width: '100%', borderRadius: '8px' } } )
									: el( 'p', { style: { color: '#888' } }, __( 'No video selected', '4wp-headless-app' ) ),
								el(
									MediaUploadCheck,
									{},
									el( MediaUpload, {
										onSelect: function ( media ) {
											setAttributes( {
												videoId: media.id || 0,
												videoUrl: media.url || '',
											} );
										},
										allowedTypes: [ 'video' ],
										value: attributes.videoId || 0,
										render: function ( obj ) {
											return el( Button, { variant: 'secondary', onClick: obj.open }, __( 'Select video', '4wp-headless-app' ) );
										},
									} )
								)
							)
							: gallery.map( function ( item, index ) {
								return el(
									PanelBody,
									{ key: 'gallery-' + index, title: __( 'Image', '4wp-headless-app' ) + ' ' + ( index + 1 ), initialOpen: index === 0 },
									item.imageUrl
										? el( 'img', { src: item.imageUrl, alt: '', style: { width: '100%', marginBottom: '8px', borderRadius: '6px' } } )
										: null,
									el(
										MediaUploadCheck,
										{},
										el( MediaUpload, {
											onSelect: function ( media ) {
												updateGalleryItem( index, { imageId: media.id || 0, imageUrl: media.url || '' } );
											},
											allowedTypes: [ 'image' ],
											value: item.imageId || 0,
											render: function ( obj ) {
												return el( Button, { variant: 'secondary', onClick: obj.open }, __( 'Select image', '4wp-headless-app' ) );
											},
										} )
									),
									el( Button, {
										variant: 'link',
										isDestructive: true,
										onClick: function () {
											setAttributes( { gallery: gallery.filter( function ( _, i ) { return i !== index; } ) } );
										},
									}, __( 'Remove', '4wp-headless-app' ) )
								);
							} ),
						! isVideo
							? el( Button, {
								variant: 'secondary',
								onClick: function () { setAttributes( { gallery: gallery.concat( [ { imageUrl: '', imageId: 0 } ] ) } ); },
							}, __( 'Add image', '4wp-headless-app' ) )
							: null
					)
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
