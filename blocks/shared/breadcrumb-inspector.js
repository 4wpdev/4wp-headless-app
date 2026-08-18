( function ( window ) {
	/**
	 * Shared breadcrumb inspector panel for GRV blocks.
	 *
	 * @param {object} opts
	 * @param {object} opts.attributes
	 * @param {Function} opts.setAttributes
	 * @param {Function} opts.PanelBody
	 * @param {Function} opts.ToggleControl
	 * @param {Function} opts.TextControl
	 * @param {Function} opts.el
	 * @param {Function} opts.__
	 */
	window.grvBreadcrumbInspector = function grvBreadcrumbInspector( opts ) {
		var attributes = opts.attributes;
		var setAttributes = opts.setAttributes;
		var PanelBody = opts.PanelBody;
		var ToggleControl = opts.ToggleControl;
		var TextControl = opts.TextControl;
		var el = opts.el;
		var __ = opts.__;

		var items = Array.isArray( attributes.breadcrumbItems ) ? attributes.breadcrumbItems : [];
		var item1 = items[ 0 ] || { label: '', href: '' };
		var item2 = items[ 1 ] || { label: '', href: '' };

		function setItem( index, patch ) {
			var next = [
				Object.assign( {}, items[ 0 ] || { label: '', href: '' } ),
				Object.assign( {}, items[ 1 ] || { label: '', href: '' } ),
			];
			next[ index ] = Object.assign( {}, next[ index ], patch );
			next = next.filter( function ( row ) {
				return row && String( row.label || '' ).trim() !== '';
			} );
			setAttributes( { breadcrumbItems: next } );
		}

		return el(
			PanelBody,
			{ title: __( 'Breadcrumb', '4wp-headless-app' ), initialOpen: false },
			el( ToggleControl, {
				label: __( 'Show breadcrumb', '4wp-headless-app' ),
				checked: !! attributes.showBreadcrumb,
				onChange: function ( value ) {
					setAttributes( { showBreadcrumb: value } );
				},
			} ),
			el( 'p', { className: 'description' }, __( 'Auto: Головна → батьківська WP-сторінка → поточна. Нижче — необовʼязкове перевизначення середніх пунктів.', '4wp-headless-app' ) ),
			el( TextControl, {
				label: __( 'Middle item 1 — label', '4wp-headless-app' ),
				value: item1.label || '',
				onChange: function ( value ) {
					setItem( 0, { label: value } );
				},
			} ),
			el( TextControl, {
				label: __( 'Middle item 1 — URL', '4wp-headless-app' ),
				value: item1.href || '',
				onChange: function ( value ) {
					setItem( 0, { href: value } );
				},
			} ),
			el( TextControl, {
				label: __( 'Middle item 2 — label', '4wp-headless-app' ),
				value: item2.label || '',
				onChange: function ( value ) {
					setItem( 1, { label: value } );
				},
			} ),
			el( TextControl, {
				label: __( 'Middle item 2 — URL', '4wp-headless-app' ),
				value: item2.href || '',
				onChange: function ( value ) {
					setItem( 1, { href: value } );
				},
			} )
		);
	};
} )( window );
