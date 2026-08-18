( function ( wp ) {
	if ( ! wp?.blocks?.registerBlockType ) {
		return;
	}

	const { registerBlockType } = wp.blocks;
	const { useBlockProps, MediaUpload, MediaUploadCheck } = wp.blockEditor;
	const { Button, TextControl, TextareaControl, PanelBody } = wp.components;
	const { createElement: el, useMemo } = wp.element;
	const { __ } = wp.i18n;

	const blockData = window.grvStepsSectionData || { defaults: { steps: [] } };

	function itemsToText( items ) {
		return Array.isArray( items ) ? items.join( '\n' ) : '';
	}

	function textToItems( text ) {
		return String( text || '' )
			.split( /\r\n|\r|\n/ )
			.map( function ( line ) { return line.trim(); } )
			.filter( Boolean );
	}

	function ensureSteps( steps ) {
		if ( Array.isArray( steps ) && steps.length ) {
			return steps.map( function ( step ) {
				return {
					label: step.label || '',
					description: step.description || '',
					imageUrl: step.imageUrl || '',
					imageId: step.imageId || 0,
					items: Array.isArray( step.items ) ? step.items : textToItems( step.items ),
				};
			} );
		}
		return ( blockData.defaults.steps || [] ).map( function ( step ) {
			return Object.assign( {}, step, {
				items: Array.isArray( step.items ) ? step.items.slice() : [],
			} );
		} );
	}

	registerBlockType( 'grv/steps-section', {
		apiVersion: 3,
		title: __( 'Steps / Етапи', '4wp-headless-app' ),
		category: 'grv',
		icon: 'editor-ol',
		description: __( 'Етапи робіт з фото та списком пунктів (як у будівництві).', '4wp-headless-app' ),
		keywords: [ 'steps', 'stages', 'етапи', 'grv' ],
		attributes: {
			eyebrow: { type: 'string', default: blockData.defaults.eyebrow || 'Етапи будівництва' },
			title: { type: 'string', default: blockData.defaults.title || 'Як ми будуємо' },
			steps: { type: 'array', default: [] },
		},
		supports: { html: false, anchor: true },
		edit: function ( props ) {
			const { attributes, setAttributes } = props;
			const steps = useMemo( function () {
				return ensureSteps( attributes.steps );
			}, [ attributes.steps ] );

			const blockProps = useBlockProps( {
				className: 'grv-steps-section-editor',
				style: {
					padding: '1.25rem',
					border: '1px dashed #c3a24d',
					borderRadius: '10px',
					background: '#faf8f3',
				},
			} );

			function setSteps( next ) {
				setAttributes( { steps: next } );
			}

			function updateStep( index, patch ) {
				setSteps( steps.map( function ( step, i ) {
					return i === index ? Object.assign( {}, step, patch ) : step;
				} ) );
			}

			function moveStep( index, direction ) {
				const target = index + direction;
				if ( target < 0 || target >= steps.length ) return;
				const next = steps.slice();
				const tmp = next[ index ];
				next[ index ] = next[ target ];
				next[ target ] = tmp;
				setSteps( next );
			}

			function removeStep( index ) {
				setSteps( steps.filter( function ( _, i ) { return i !== index; } ) );
			}

			function addStep() {
				setSteps( steps.concat( [ {
					label: '',
					description: '',
					imageUrl: '',
					imageId: 0,
					items: [],
				} ] ) );
			}

			return el(
				'div',
				blockProps,
				el(
					'div',
					{ style: { textAlign: 'center', marginBottom: '16px', paddingBottom: '12px', borderBottom: '1px solid #e6dfd0' } },
					el( TextControl, {
						label: __( 'Eyebrow', '4wp-headless-app' ),
						value: attributes.eyebrow || '',
						onChange: function ( v ) { setAttributes( { eyebrow: v } ); },
					} ),
					el( TextControl, {
						label: __( 'Заголовок', '4wp-headless-app' ),
						value: attributes.title || '',
						onChange: function ( v ) { setAttributes( { title: v } ); },
					} )
				),
				steps.map( function ( step, index ) {
					return el(
						PanelBody,
						{
							key: 'step-' + index,
							title: ( index + 1 ) + '. ' + ( step.label || __( 'Новий етап', '4wp-headless-app' ) ),
							initialOpen: index === 0,
						},
						el(
							'div',
							{ style: { display: 'grid', gap: '10px' } },
							el(
								'div',
								{ style: { display: 'flex', gap: '8px', flexWrap: 'wrap' } },
								el( Button, { variant: 'secondary', onClick: function () { moveStep( index, -1 ); }, disabled: index === 0 }, '↑' ),
								el( Button, { variant: 'secondary', onClick: function () { moveStep( index, 1 ); }, disabled: index === steps.length - 1 }, '↓' ),
								el( Button, { variant: 'secondary', isDestructive: true, onClick: function () { removeStep( index ); } }, __( 'Remove', '4wp-headless-app' ) )
							),
							el( TextControl, {
								label: __( 'Назва етапу', '4wp-headless-app' ),
								value: step.label || '',
								onChange: function ( v ) { updateStep( index, { label: v } ); },
							} ),
							el( TextareaControl, {
								label: __( 'Опис', '4wp-headless-app' ),
								value: step.description || '',
								onChange: function ( v ) { updateStep( index, { description: v } ); },
							} ),
							el( TextareaControl, {
								label: __( 'Пункти (по одному в рядку)', '4wp-headless-app' ),
								value: itemsToText( step.items ),
								onChange: function ( v ) { updateStep( index, { items: textToItems( v ) } ); },
							} ),
							el(
								MediaUploadCheck,
								{},
								el( MediaUpload, {
									onSelect: function ( media ) {
										updateStep( index, {
											imageId: media.id || 0,
											imageUrl: ( media.sizes && media.sizes.large && media.sizes.large.url ) || media.url || '',
										} );
									},
									allowedTypes: [ 'image' ],
									value: step.imageId || 0,
									render: function ( obj ) {
										return el(
											'div',
											{},
											step.imageUrl
												? el( 'img', { src: step.imageUrl, alt: '', style: { maxWidth: '100%', borderRadius: '8px', marginBottom: '8px' } } )
												: null,
											el( Button, { variant: 'secondary', onClick: obj.open },
												step.imageUrl ? __( 'Змінити фото', '4wp-headless-app' ) : __( 'Обрати фото', '4wp-headless-app' )
											),
											step.imageUrl
												? el( Button, {
													variant: 'link',
													isDestructive: true,
													onClick: function () { updateStep( index, { imageId: 0, imageUrl: '' } ); },
												}, __( 'Прибрати', '4wp-headless-app' ) )
												: null
										);
									},
								} )
							)
						)
					);
				} ),
				el( Button, { variant: 'primary', onClick: addStep, style: { marginTop: '12px' } }, __( '+ Етап', '4wp-headless-app' ) )
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
