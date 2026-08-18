( function ( wp ) {
	if ( ! wp?.blocks?.registerBlockType ) {
		return;
	}

	const { registerBlockType } = wp.blocks;
	const { useBlockProps, MediaUpload, MediaUploadCheck, InspectorControls } = wp.blockEditor;
	const { Button, SelectControl, TextControl, TextareaControl, PanelBody, ToggleControl } = wp.components;
	const { createElement: el, useMemo, useState, Fragment } = wp.element;
	const { __ } = wp.i18n;

	const blockData = window.grvHeroSliderData || { defaults: { slides: [] } };

	function ensureSlides( slides ) {
		if ( Array.isArray( slides ) && slides.length ) {
			return slides.map( function ( slide ) {
				return {
					id: slide.id || '',
					tag: slide.tag || '',
					title: slide.title || '',
					subtitle: slide.subtitle || '',
					cta: slide.cta || '',
					href: slide.href || '/',
					imageUrl: slide.imageUrl || '',
					imageId: slide.imageId || 0,
					imagePosition: slide.imagePosition || 'center',
				};
			} );
		}
		return ( blockData.defaults.slides || [] ).map( function ( slide ) {
			return Object.assign( {}, slide );
		} );
	}

	function slideLabel( slide, index ) {
		const name = slide.tag || slide.title || __( 'Slide', '4wp-headless-app' );
		return ( index + 1 ) + '. ' + String( name ).replace( /\s+/g, ' ' ).trim();
	}

	registerBlockType( 'grv/hero-slider', {
		apiVersion: 3,
		title: __( 'Hero Slider', '4wp-headless-app' ),
		category: 'grv',
		icon: 'slides',
		description: __( 'Повноекранний hero-слайдер з фоновими зображеннями та CTA.', '4wp-headless-app' ),
		keywords: [ 'hero', 'slider', 'banner', 'grv' ],
		attributes: {
			heightMode: { type: 'string', default: 'full' },
			minHeight: { type: 'number', default: 640 },
			slides: { type: 'array', default: [] },
			secondaryButtonLabel: { type: 'string', default: 'Консультація' },
			secondaryButtonHref: { type: 'string', default: '/contacts' },
			showBreadcrumb: { type: 'boolean', default: false },
			breadcrumbItems: { type: 'array', default: [] },
		},
		supports: { html: false, anchor: true },
		edit: function ( props ) {
			const { attributes, setAttributes } = props;
			const slides = useMemo( function () {
				return ensureSlides( attributes.slides );
			}, [ attributes.slides ] );
			const [ openSlide, setOpenSlide ] = useState( 0 );

			const isCustomHeight = ( attributes.heightMode || 'full' ) === 'custom';

			const blockProps = useBlockProps( {
				className: 'grv-hero-slider-editor',
				style: {
					padding: '1.25rem',
					border: '1px dashed #c3a24d',
					borderRadius: '10px',
					background: '#faf8f3',
					color: '#1a1a1a',
				},
			} );

			function setSlides( next ) {
				setAttributes( { slides: next } );
			}

			function updateSlide( index, patch ) {
				const next = slides.map( function ( slide, i ) {
					return i === index ? Object.assign( {}, slide, patch ) : slide;
				} );
				setSlides( next );
			}

			function moveSlide( index, direction ) {
				const target = index + direction;
				if ( target < 0 || target >= slides.length ) {
					return;
				}
				const next = slides.slice();
				const tmp = next[ index ];
				next[ index ] = next[ target ];
				next[ target ] = tmp;
				setSlides( next );
				if ( openSlide === index ) {
					setOpenSlide( target );
				} else if ( openSlide === target ) {
					setOpenSlide( index );
				}
			}

			function removeSlide( index ) {
				setSlides( slides.filter( function ( _, i ) { return i !== index; } ) );
				if ( openSlide === index ) {
					setOpenSlide( Math.max( 0, index - 1 ) );
				} else if ( openSlide > index ) {
					setOpenSlide( openSlide - 1 );
				}
			}

			function addSlide() {
				const nextIndex = slides.length;
				setSlides( slides.concat( [ {
					id: 'slide-' + ( slides.length + 1 ),
					tag: '',
					title: '',
					subtitle: '',
					cta: 'Дізнатись більше',
					href: '/',
					imageUrl: '',
					imageId: 0,
					imagePosition: 'center',
				} ] ) );
				setOpenSlide( nextIndex );
			}

			return el(
				Fragment,
				{},
				el(
					InspectorControls,
					{},
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
				el(
					'div',
					{ className: 'grv-hero-slider-editor__settings' },
					el( SelectControl, {
						label: __( 'Height', '4wp-headless-app' ),
						value: attributes.heightMode || 'full',
						options: [
							{ label: __( 'Full screen', '4wp-headless-app' ), value: 'full' },
							{ label: __( 'Custom min height', '4wp-headless-app' ), value: 'custom' },
						],
						onChange: function ( value ) { setAttributes( { heightMode: value } ); },
					} ),
					isCustomHeight
						? el( TextControl, {
							label: __( 'Min height (px)', '4wp-headless-app' ),
							type: 'number',
							min: 320,
							max: 1200,
							value: String( attributes.minHeight || 640 ),
							onChange: function ( value ) {
								setAttributes( { minHeight: Math.max( 320, Math.min( 1200, parseInt( value, 10 ) || 640 ) ) } );
							},
						} )
						: el( 'p', { className: '4wp-hero-slider-editor__full-height-note' }, __( 'Uses 100vh / 100svh on the frontend.', '4wp-headless-app' ) )
				),
				el( TextControl, {
					label: __( 'Secondary button', '4wp-headless-app' ),
					value: attributes.secondaryButtonLabel || '',
					onChange: function ( value ) { setAttributes( { secondaryButtonLabel: value } ); },
					help: __( 'Порожньо = кнопка не показується.', '4wp-headless-app' ),
				} ),
				el( TextControl, {
					label: __( 'Secondary link', '4wp-headless-app' ),
					value: attributes.secondaryButtonHref || '',
					onChange: function ( value ) { setAttributes( { secondaryButtonHref: value } ); },
				} ),
				el(
					'div',
					{ className: 'grv-hero-slider-editor__slides' },
					slides.map( function ( slide, index ) {
						const isOpen = openSlide === index;

						return el(
							'div',
							{
								key: 'slide-' + index,
								className: 'grv-hero-slider-editor__slide',
								style: {
									border: isOpen ? '1px solid #c3a24d' : '1px solid #ddd',
									borderRadius: '8px',
									overflow: 'hidden',
									background: '#fff',
									boxShadow: isOpen ? '0 2px 8px rgba(201, 162, 77, 0.15)' : 'none',
								},
							},
							el(
								'button',
								{
									type: 'button',
									className: 'grv-hero-slider-editor__slide-toggle',
									onClick: function () { setOpenSlide( isOpen ? -1 : index ); },
									style: {
										display: 'flex',
										alignItems: 'center',
										justifyContent: 'space-between',
										width: '100%',
										padding: '12px 0',
										border: 'none',
										background: isOpen ? '#fff9eb' : 'transparent',
										color: '#1a1a1a',
										fontWeight: 600,
										fontSize: '14px',
										lineHeight: 1.4,
										cursor: 'pointer',
										textAlign: 'left',
									},
								},
								el( 'span', { style: { flex: 1, paddingRight: '12px' } }, slideLabel( slide, index ) ),
								el( 'span', {
									'aria-hidden': true,
									style: {
										color: isOpen ? '#b8911e' : '#666',
										fontSize: '11px',
										fontWeight: 700,
										flexShrink: 0,
									},
								}, isOpen ? '▲' : '▼' )
							),
							isOpen
								? el(
									'div',
									{ className: 'grv-hero-slider-editor__slide-body', style: { padding: '12px 0 0', borderTop: '1px solid #e6dfd0', background: 'transparent' } },
									el(
										'div',
										{ style: { display: 'flex', gap: '8px', flexWrap: 'wrap', marginBottom: '10px' } },
										el( Button, { variant: 'secondary', onClick: function () { moveSlide( index, -1 ); }, disabled: index === 0 }, '↑' ),
										el( Button, { variant: 'secondary', onClick: function () { moveSlide( index, 1 ); }, disabled: index === slides.length - 1 }, '↓' ),
										el( Button, { variant: 'secondary', isDestructive: true, onClick: function () { removeSlide( index ); }, disabled: slides.length <= 1 }, __( 'Remove', '4wp-headless-app' ) )
									),
									slide.imageUrl
										? el( 'img', { src: slide.imageUrl, alt: '', style: { width: '100%', maxHeight: '120px', objectFit: 'cover', borderRadius: '6px', marginBottom: '10px' } } )
										: null,
									el(
										MediaUploadCheck,
										{},
										el( MediaUpload, {
											onSelect: function ( media ) {
												updateSlide( index, { imageId: media.id || 0, imageUrl: media.url || '' } );
											},
											allowedTypes: [ 'image' ],
											value: slide.imageId || 0,
											render: function ( obj ) {
												return el( Button, { variant: 'secondary', onClick: obj.open, style: { marginBottom: '10px' } }, slide.imageId ? __( 'Change image', '4wp-headless-app' ) : __( 'Select image', '4wp-headless-app' ) );
											},
										} )
									),
									el( TextControl, {
										label: __( 'Tag', '4wp-headless-app' ),
										value: slide.tag || '',
										onChange: function ( value ) { updateSlide( index, { tag: value } ); },
									} ),
									el( TextareaControl, {
										label: __( 'Title (use new line)', '4wp-headless-app' ),
										value: slide.title || '',
										onChange: function ( value ) { updateSlide( index, { title: value } ); },
										rows: 3,
										help: __( 'Новий рядок = перенос. Другий рядок можна обгорнути в <span>…</span> — буде менший і тонший.', '4wp-headless-app' ),
									} ),
									el( TextareaControl, {
										label: __( 'Subtitle', '4wp-headless-app' ),
										value: slide.subtitle || '',
										onChange: function ( value ) { updateSlide( index, { subtitle: value } ); },
										rows: 3,
									} ),
									el( TextControl, {
										label: __( 'Primary button', '4wp-headless-app' ),
										value: slide.cta || '',
										onChange: function ( value ) { updateSlide( index, { cta: value } ); },
										help: __( 'Порожньо = кнопка не показується.', '4wp-headless-app' ),
									} ),
									el( TextControl, {
										label: __( 'Primary link', '4wp-headless-app' ),
										value: slide.href || '',
										onChange: function ( value ) { updateSlide( index, { href: value } ); },
									} ),
									el( TextControl, {
										label: __( 'Image position (CSS)', '4wp-headless-app' ),
										value: slide.imagePosition || 'center',
										onChange: function ( value ) { updateSlide( index, { imagePosition: value } ); },
										help: 'center, center 30%',
									} )
								)
								: null
						);
					} )
				),
				el( Button, { variant: 'primary', onClick: addSlide, style: { marginTop: '12px' } }, __( 'Add slide', '4wp-headless-app' ) )
			)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
