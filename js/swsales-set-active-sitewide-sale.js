jQuery( document ).ready(
	function($) {
		$( '.swsales_column_set_active' ).click(
			function(){
				var data = {
					'action': 'swsales_set_active_sitewide_sale',
					'sitewide_sale_id': this.id.substr( 26 ),
					'nonce': swsales_set_active.nonce,
				};
				$.post( ajaxurl, data )
			}
		);
	}
);
