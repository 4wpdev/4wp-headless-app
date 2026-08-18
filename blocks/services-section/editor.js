( function ( wp ) {
	if ( ! wp?.blocks?.registerBlockType ) {
		return;
	}

	const { registerBlockType } = wp.blocks;
	const { useBlockProps, MediaUpload, MediaUploadCheck, RichText } = wp.blockEditor;
	const { Button, SelectControl, TextControl, TextareaControl, PanelBody } = wp.components;
	const { createElement: el, useMemo } = wp.element;
	const { __ } = wp.i18n;

	const blockData = window.grvServicesSectionData || { icons: [], defaults: { items: [] } };

	function ensureItems( items ) {
		if ( Array.isArray( items ) && items.length ) {
			return items.map( function ( item ) {
				return {
					icon: item.icon || 'home',
					title: item.title || '',
					imageUrl: item.imageUrl || '',
					imageId: item.imageId || 0,
					description: item.description || '',
					link: item.link || '',
				};
			} );
		}
		return ( blockData.defaults.items || [] ).map( function ( item ) {
			return Object.assign( {}, item );
		} );
	}

	registerBlockType( 'grv/services-section', {
		apiVersion: 3,
		title: __( 'Services Section', '4wp-headless-app' ),
		category: 'grv',
		icon: 'grid-view',
		description: __( 'Сітка послуг: підзаголовок, картки з іконкою, зображенням і списком.', '4wp-headless-app' ),
		keywords: [ 'services', 'grv', 'послуги' ],
		attributes: {
			subTitle: { type: 'string', default: blockData.defaults.subTitle || 'Що ми робимо' },
			title: { type: 'string', default: blockData.defaults.title || 'Перелік послуг' },
			items: { type: 'array', default: [] },
		},
		supports: { html: false, anchor: true },
		edit: function ( props ) {
			const { attributes, setAttributes } = props;
			const items = useMemo( function () {
				return ensureItems( attributes.items );
			}, [ attributes.items ] );

			const blockProps = useBlockProps( {
				className: 'grv-services-section-editor',
				style: {
					padding: '1.25rem',
					border: '1px dashed #c3a24d',
					borderRadius: '10px',
					background: '#faf8f3',
				},
			} );

			function setItems( next ) {
				setAttributes( { items: next } );
			}

			function updateItem( index, patch ) {
				const next = items.map( function ( item, i ) {
					return i === index ? Object.assign( {}, item, patch ) : item;
				} );
				setItems( next );
			}

			function moveItem( index, direction ) {
				const target = index + direction;
				if ( target < 0 || target >= items.length ) {
					return;
				}
				const next = items.slice();
				const tmp = next[ index ];
				next[ index ] = next[ target ];
				next[ target ] = tmp;
				setItems( next );
			}

			function removeItem( index ) {
				setItems( items.filter( function ( _, i ) { return i !== index; } ) );
			}

			function addItem() {
				setItems( items.concat( [ {
					icon: 'home',
					title: '',
					imageUrl: '',
					imageId: 0,
					description: '<ul><li></li></ul>',
					link: '/',
				} ] ) );
			}

			const iconOptions = ( blockData.icons || [] ).map( function ( icon ) {
				return { label: icon.label, value: icon.slug };
			} );

			return el(
				'div',
				blockProps,
				el(
					'div',
					{ className: '4wp-editor-block-intro', style: { textAlign: 'center', marginBottom: '20px', paddingBottom: '16px', borderBottom: '1px solid #e6dfd0' } },
					el( RichText, {
						tagName: 'p',
						className: 'grv-services-subtitle',
						value: attributes.subTitle || '',
						onChange: function ( value ) { setAttributes( { subTitle: value } ); },
						placeholder: __( 'SubTitle', '4wp-headless-app' ),
						style: { color: '#b8911e', fontSize: '11px', letterSpacing: '0.14em', textTransform: 'uppercase', fontWeight: 600, margin: 0 },
					} ),
					el( RichText, {
						tagName: 'h2',
						className: 'grv-services-title',
						value: attributes.title || '',
						onChange: function ( value ) { setAttributes( { title: value } ); },
						placeholder: __( 'Title', '4wp-headless-app' ),
						style: { fontSize: '28px', fontWeight: 800, margin: '8px 0 0', lineHeight: 1.2 },
					} )
				),
				items.map( function ( item, index ) {
					return el(
						PanelBody,
						{
							key: 'item-' + index,
							title: ( index + 1 ) + '. ' + ( item.title || __( 'New item', '4wp-headless-app' ) ),
							initialOpen: index === 0,
							style: { marginBottom: '12px', background: '#fff', border: '1px solid #e6dfd0', borderRadius: '8px' },
							className: '4wp-editor-inner-card',
						},
						el(
							'div',
							{ style: { display: 'grid', gap: '12px', padding: '4px 0 8px' } },
							el(
								'div',
								{ style: { display: 'flex', gap: '8px', flexWrap: 'wrap' } },
								el( Button, { variant: 'secondary', onClick: function () { moveItem( index, -1 ); }, disabled: index === 0 }, '↑' ),
								el( Button, { variant: 'secondary', onClick: function () { moveItem( index, 1 ); }, disabled: index === items.length - 1 }, '↓' ),
								el( Button, { variant: 'secondary', isDestructive: true, onClick: function () { removeItem( index ); } }, __( 'Remove', '4wp-headless-app' ) )
							),
							el( SelectControl, {
								label: __( 'Icon', '4wp-headless-app' ),
								value: item.icon || 'home',
								options: iconOptions,
								onChange: function ( value ) { updateItem( index, { icon: value } ); },
							} ),
							el( TextControl, {
								label: __( 'Title', '4wp-headless-app' ),
								value: item.title || '',
								onChange: function ( value ) { updateItem( index, { title: value } ); },
							} ),
							el( TextControl, {
								label: __( 'Link', '4wp-headless-app' ),
								value: item.link || '',
								onChange: function ( value ) { updateItem( index, { link: value } ); },
								help: '/budivnytstvo',
							} ),
							el(
								'div',
								{},
								el( 'p', { style: { margin: '0 0 6px', fontWeight: 600 } }, __( 'Image', '4wp-headless-app' ) ),
								item.imageUrl
									? el( 'img', { src: item.imageUrl, alt: '', style: { width: '100%', maxHeight: '140px', objectFit: 'cover', borderRadius: '6px', marginBottom: '8px' } } )
									: null,
								el(
									MediaUploadCheck,
									{},
									el( MediaUpload, {
										onSelect: function ( media ) {
											updateItem( index, {
												imageId: media.id || 0,
												imageUrl: media.url || '',
											} );
										},
										allowedTypes: [ 'image' ],
										value: item.imageId || 0,
										render: function ( obj ) {
											return el(
												Button,
												{ variant: 'secondary', onClick: obj.open },
												item.imageId ? __( 'Change image', '4wp-headless-app' ) : __( 'Select image', '4wp-headless-app' )
											);
										},
									} )
								),
								item.imageId
									? el( Button, { variant: 'link', isDestructive: true, onClick: function () { updateItem( index, { imageId: 0, imageUrl: '' } ); } }, __( 'Remove image', '4wp-headless-app' ) )
									: null
							),
							el( TextareaControl, {
								label: __( 'Description (HTML)', '4wp-headless-app' ),
								value: item.description || '',
								onChange: function ( value ) { updateItem( index, { description: value } ); },
								help: '<ul><li>…</li></ul>, <b>, <i>',
								rows: 6,
							} ),
							item.description
								? el( 'div', {
									className: 'grv-services-desc-preview',
									style: { fontSize: '13px', color: '#444', borderTop: '1px dashed #ddd', paddingTop: '8px' },
									dangerouslySetInnerHTML: { __html: item.description },
								} )
								: null
						)
					);
				} ),
				el(
					Button,
					{ variant: 'primary', onClick: addItem, style: { marginTop: '8px' } },
					__( 'Add item', '4wp-headless-app' )
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
