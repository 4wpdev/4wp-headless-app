( function ( wp ) {
	if ( ! wp?.blocks?.registerBlockType ) {
		return;
	}

	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, TextControl, CheckboxControl, Button } = wp.components;
	const { createElement: el, Fragment, useMemo } = wp.element;
	const { __ } = wp.i18n;

	const blockData = window.grvFaqBlockData || { items: [], manageUrl: '', newItemUrl: '' };

	function truncate( text, max ) {
		if ( ! text || text.length <= max ) {
			return text || '';
		}
		return text.slice( 0, max ).trim() + '…';
	}

	registerBlockType( 'grv/faq-section', {
		apiVersion: 3,
		title: __( 'GRV FAQ', '4wp-headless-app' ),
		category: 'grv',
		icon: 'editor-help',
		description: 'Оберіть питання з бібліотеки FAQ для цієї сторінки.',
		keywords: [ 'faq', 'grv', 'questions', __( 'питання', '4wp-headless-app' ) ],
		attributes: {
			title: {
				type: 'string',
				default: 'Часті запитання',
			},
			faqIds: {
				type: 'array',
				default: [],
			},
		},
		supports: {
			html: false,
			anchor: true,
		},
		edit: function ( props ) {
			const { attributes, setAttributes } = props;
			const selectedIds = Array.isArray( attributes.faqIds ) ? attributes.faqIds : [];
			const allItems = blockData.items || [];

			const selectedItems = useMemo(
				function () {
					const byId = new Map( allItems.map( function ( item ) {
						return [ item.id, item ];
					} ) );
					return selectedIds
						.map( function ( id ) {
							return byId.get( id );
						} )
						.filter( Boolean );
				},
				[ allItems, selectedIds ]
			);

			function toggleItem( id ) {
				const next = selectedIds.includes( id )
					? selectedIds.filter( function ( value ) {
						return value !== id;
					} )
					: selectedIds.concat( [ id ] );
				setAttributes( { faqIds: next } );
			}

			const blockProps = useBlockProps( {
				className: 'grv-faq-section-editor',
				style: {
					padding: '1rem 1.25rem',
					border: '1px dashed #c3a24d',
					borderRadius: '8px',
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
						{ title: 'Секція', initialOpen: true },
						el( TextControl, {
							label: 'Заголовок секції',
							value: attributes.title || '',
							onChange: function ( value ) {
								setAttributes( { title: value } );
							},
						} )
					),
					el(
						PanelBody,
						{ title: __( 'FAQ library', '4wp-headless-app' ), initialOpen: true },
						allItems.length
							? allItems.map( function ( item ) {
								return el( CheckboxControl, {
									key: item.id,
									label: item.question,
									help: truncate( item.answer, 90 ),
									checked: selectedIds.includes( item.id ),
									onChange: function () {
										toggleItem( item.id );
									},
								} );
							} )
							: el(
								'p',
								{ style: { margin: 0 } },
								__( 'No FAQ items yet. Create them in the FAQ menu.', '4wp-headless-app' )
							),
						el(
							'div',
							{ style: { display: 'flex', gap: '8px', marginTop: '12px', flexWrap: 'wrap' } },
							blockData.newItemUrl
								? el(
									Button,
									{
										variant: 'primary',
										href: blockData.newItemUrl,
										target: '_blank',
										rel: 'noopener noreferrer',
									},
									__( 'Add FAQ item', '4wp-headless-app' )
								)
								: null,
							blockData.manageUrl
								? el(
									Button,
									{
										variant: 'secondary',
										href: blockData.manageUrl,
										target: '_blank',
										rel: 'noopener noreferrer',
									},
									__( 'Manage all FAQ', '4wp-headless-app' )
								)
								: null
						)
					)
				),
				el(
					'div',
					blockProps,
					el(
						'div',
						{
							style: {
								display: 'flex',
								justifyContent: 'space-between',
								alignItems: 'center',
								gap: '12px',
								marginBottom: '12px',
							},
						},
						el( 'strong', {}, attributes.title || __( 'Часті запитання', '4wp-headless-app' ) ),
						el(
							'span',
							{ style: { fontSize: '12px', color: '#666' } },
							selectedItems.length + ' ' + __( 'items selected', '4wp-headless-app' )
						)
					),
					selectedItems.length
						? el(
							'div',
							{ style: { display: 'grid', gap: '8px' } },
							selectedItems.map( function ( item, index ) {
								return el(
									'div',
									{
										key: item.id,
										className: '4wp-editor-inner-card',
										style: {
											padding: '10px 12px',
											borderRadius: '6px',
											background: '#fff',
											border: '1px solid #e6dfd0',
										},
									},
									el(
										'div',
										{ style: { fontWeight: 600, fontSize: '13px', marginBottom: '4px' } },
										( index + 1 ) + '. ' + item.question
									),
									el(
										'div',
										{ style: { fontSize: '12px', color: '#666', lineHeight: 1.45 } },
										item.answer
									)
								);
							} )
						)
						: el(
							'p',
							{ style: { margin: 0, fontSize: '13px', color: '#666' } },
							__(
								'Select questions in the block sidebar (FAQ library) or create new items in FAQ → Add New.',
								'4wp-headless-app'
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
