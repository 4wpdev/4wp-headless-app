( function ( $ ) {
	if ( typeof fwpGeoTermOrder === 'undefined' ) {
		return;
	}

	var $list = $( '#the-list' );
	if ( ! $list.length ) {
		return;
	}

	var snapshot = [];
	var $notice = $( '<div class="fwp-geo-term-order-notice notice" style="display:none;"></div>' );
	$list.closest( '.wp-list-table' ).before( $notice );

	function showNotice( message, type ) {
		$notice
			.removeClass( 'notice-success notice-error notice-warning' )
			.addClass( 'notice-' + ( type || 'success' ) )
			.text( message )
			.show();
	}

	function rowId( $row ) {
		var id = ( $row.attr( 'id' ) || '' ).replace( 'tag-', '' );
		return parseInt( id, 10 ) || 0;
	}

	function isValidOrder() {
		var lastParent = 0;
		var valid = true;

		$list.children( 'tr[id^="tag-"]' ).each( function () {
			var $row = $( this );
			var id = rowId( $row );

			if ( $row.hasClass( 'level-0' ) ) {
				lastParent = id;
				return;
			}

			if ( $row.hasClass( 'level-1' ) ) {
				var expectedParent = fwpGeoTermOrder.parents[ id ] || 0;
				if ( ! lastParent || expectedParent !== lastParent ) {
					valid = false;
					return false;
				}
			}
		} );

		return valid;
	}

	function collectOrder() {
		var items = [];
		var order = 0;

		$list.children( 'tr[id^="tag-"]' ).each( function () {
			var id = rowId( $( this ) );
			if ( ! id ) {
				return;
			}
			items.push( { id: id, order: order } );
			order += 10;
		} );

		return items;
	}

	function saveOrder() {
		$.post( fwpGeoTermOrder.ajaxUrl, {
			action: 'fwp_save_geo_area_term_order',
			nonce: fwpGeoTermOrder.nonce,
			order: JSON.stringify( collectOrder() ),
		} )
			.done( function ( response ) {
				if ( response && response.success ) {
					showNotice( fwpGeoTermOrder.i18n.saved, 'success' );
					snapshot = $list.sortable( 'toArray', { attribute: 'id' } );
					return;
				}
				showNotice( fwpGeoTermOrder.i18n.error, 'error' );
			} )
			.fail( function () {
				showNotice( fwpGeoTermOrder.i18n.error, 'error' );
			} );
	}

	$list.sortable( {
		items: '> tr[id^="tag-"]',
		axis: 'y',
		handle: '.fwp-term-drag-handle',
		placeholder: 'fwp-term-sort-placeholder',
		forcePlaceholderSize: true,
		helper: function ( event, $row ) {
			var $originals = $row.children();
			var $helper = $row.clone();
			$helper.children().each( function ( index ) {
				$( this ).width( $originals.eq( index ).width() );
			} );
			return $helper;
		},
		start: function () {
			snapshot = $list.sortable( 'toArray', { attribute: 'id' } );
		},
		stop: function () {
			if ( ! isValidOrder() ) {
				$list.sortable( 'cancel' );
				showNotice( fwpGeoTermOrder.i18n.invalid, 'warning' );
				return;
			}
			saveOrder();
		},
	} );

	snapshot = $list.sortable( 'toArray', { attribute: 'id' } );
} )( jQuery );
