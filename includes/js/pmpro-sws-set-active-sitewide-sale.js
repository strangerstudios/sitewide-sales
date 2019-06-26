jQuery( document ).ready(
	function($) {
		$( '.pmpro_sws_column_set_active' ).click(
			function(){
				var data = {
					'action': 'pmpro_sws_set_active_sitewide_sale',
					'sitewide_sale_id': this.id.substr( 28 ),
				};
				$.post( ajaxurl, data )
			}
		);
	}
);
