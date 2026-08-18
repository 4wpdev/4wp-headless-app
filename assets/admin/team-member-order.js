( function ( $ ) {
	if ( typeof fwpTeamMemberOrder === 'undefined' ) {
		return;
	}

	var $list = $( '#the-list' );
	if ( ! $list.length ) {
		return;
	}

	var $notice = $( '<div class="fwp-team-order-notice notice" style="display:none;margin:8px 0 12px;"></div>' );
	$list.closest( '.wp-list-table' ).before( $notice );

	function showNotice( message, type ) {
		$notice
			.removeClass( 'notice-success notice-error notice-warning' )
			.addClass( 'notice-' + ( type || 'success' ) )
			.text( message )
			.show();
	}

	function collectOrder() {
		var items = [];
		var order = 0;

		$list.children( 'tr[id^="post-"]' ).each( function () {
			var id = parseInt( ( $( this ).attr( 'id' ) || '' ).replace( 'post-', '' ), 10 ) || 0;
			if ( ! id ) {
				return;
			}
			items.push( { id: id, order: order } );
			$( this ).find( '.fwp-team-order-num' ).text( String( order ) );
			order += 10;
		} );

		return items;
	}

	function saveOrder() {
		$.post( fwpTeamMemberOrder.ajaxUrl, {
			action: 'fwp_save_team_member_order',
			nonce: fwpTeamMemberOrder.nonce,
			order: JSON.stringify( collectOrder() ),
		} )
			.done( function ( response ) {
				if ( response && response.success ) {
					showNotice( fwpTeamMemberOrder.i18n.saved, 'success' );
					return;
				}
				showNotice( fwpTeamMemberOrder.i18n.error, 'error' );
			} )
			.fail( function () {
				showNotice( fwpTeamMemberOrder.i18n.error, 'error' );
			} );
	}

	// Drag whole row (links/checkboxes still clickable — cancel on interactive elements).
	$list.sortable( {
		items: '> tr[id^="post-"]',
		axis: 'y',
		cancel: 'a, button, input, select, textarea, .inline-edit-row',
		placeholder: 'fwp-team-sort-placeholder',
		forcePlaceholderSize: true,
		tolerance: 'pointer',
		helper: function ( event, $row ) {
			var $originals = $row.children();
			var $helper = $row.clone();
			$helper.children().each( function ( index ) {
				$( this ).width( $originals.eq( index ).width() );
			} );
			return $helper;
		},
		update: function () {
			saveOrder();
		},
	} );
} )( jQuery );
