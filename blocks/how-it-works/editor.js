( function ( wp ) {
	if ( ! wp?.blocks?.registerBlockType ) {
		return;
	}

	const { registerBlockType } = wp.blocks;
	const { useBlockProps, RichText } = wp.blockEditor;
	const { Button, SelectControl, TextControl, TextareaControl, PanelBody } = wp.components;
	const { createElement: el, useMemo } = wp.element;
	const { __ } = wp.i18n;

	const blockData = window.grvHowItWorksData || { icons: [], defaults: { steps: [] } };

	function ensureSteps( steps ) {
		if ( Array.isArray( steps ) && steps.length ) {
			return steps.map( function ( step ) {
				return {
					icon: step.icon || 'phone',
					title: step.title || '',
					desc: step.desc || '',
					detail: step.detail || '',
				};
			} );
		}
		return ( blockData.defaults.steps || [] ).map( function ( step ) {
			return Object.assign( {}, step );
		} );
	}

	registerBlockType( 'grv/how-it-works', {
		apiVersion: 3,
		title: __( 'How It Works', '4wp-headless-app' ),
		category: 'grv',
		icon: 'list-view',
		description: __( 'Кроки процесу роботи: іконка, заголовок, короткий і розгорнутий опис.', '4wp-headless-app' ),
		keywords: [ 'steps', 'process', 'grv', 'як це працює' ],
		attributes: {
			subTitle: { type: 'string', default: blockData.defaults.subTitle || 'Процес роботи' },
			title: { type: 'string', default: blockData.defaults.title || 'Як це працює' },
			intro: { type: 'string', default: blockData.defaults.intro || '' },
			steps: { type: 'array', default: [] },
		},
		supports: { html: false, anchor: true },
		edit: function ( props ) {
			const { attributes, setAttributes } = props;
			const steps = useMemo( function () {
				return ensureSteps( attributes.steps );
			}, [ attributes.steps ] );

			const blockProps = useBlockProps( {
				className: 'grv-how-it-works-editor',
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
				const next = steps.map( function ( step, i ) {
					return i === index ? Object.assign( {}, step, patch ) : step;
				} );
				setSteps( next );
			}

			function moveStep( index, direction ) {
				const target = index + direction;
				if ( target < 0 || target >= steps.length ) {
					return;
				}
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
					icon: 'phone',
					title: '',
					desc: '',
					detail: '',
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
						placeholder: __( 'Intro', '4wp-headless-app' ),
						style: { color: '#666', fontSize: '14px', maxWidth: '520px', margin: '12px auto 0', lineHeight: 1.5 },
					} )
				),
				steps.map( function ( step, index ) {
					return el(
						PanelBody,
						{
							key: 'step-' + index,
							title: ( index + 1 ) + '. ' + ( step.title || __( 'New step', '4wp-headless-app' ) ),
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
								el( Button, { variant: 'secondary', onClick: function () { moveStep( index, -1 ); }, disabled: index === 0 }, '↑' ),
								el( Button, { variant: 'secondary', onClick: function () { moveStep( index, 1 ); }, disabled: index === steps.length - 1 }, '↓' ),
								el( Button, { variant: 'secondary', isDestructive: true, onClick: function () { removeStep( index ); } }, __( 'Remove', '4wp-headless-app' ) )
							),
							el( SelectControl, {
								label: __( 'Icon', '4wp-headless-app' ),
								value: step.icon || 'phone',
								options: iconOptions,
								onChange: function ( value ) { updateStep( index, { icon: value } ); },
							} ),
							el( TextControl, {
								label: __( 'Title', '4wp-headless-app' ),
								value: step.title || '',
								onChange: function ( value ) { updateStep( index, { title: value } ); },
							} ),
							el( TextareaControl, {
								label: __( 'Short description', '4wp-headless-app' ),
								value: step.desc || '',
								onChange: function ( value ) { updateStep( index, { desc: value } ); },
								rows: 3,
							} ),
							el( TextareaControl, {
								label: __( 'Detail (expanded)', '4wp-headless-app' ),
								value: step.detail || '',
								onChange: function ( value ) { updateStep( index, { detail: value } ); },
								rows: 4,
							} )
						)
					);
				} ),
				el(
					Button,
					{ variant: 'primary', onClick: addStep, style: { marginTop: '8px' } },
					__( 'Add step', '4wp-headless-app' )
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
