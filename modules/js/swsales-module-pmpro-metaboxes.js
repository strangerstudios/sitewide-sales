jQuery( document ).ready(
	function($) {

		// multiselects
		$( "#swsales_discount_code_select" ).selectWoo();
		$( "#swsales_hide_levels_select" ).selectWoo();
		$( "#swsales_upsell_levels" ).selectWoo();

		// toggling the discount code input layout
		function swsales_toggle_discount_code() {
			var discount_code_id = $( '#swsales_discount_code_select' ).val();

			if (discount_code_id == 0) {
				$( '#swsales_after_discount_code_select' ).hide();
			} else {
				$( '#swsales_edit_discount_code' ).attr( 'href', swsales.admin_url + 'admin.php?page=pmpro-discountcodes&edit=' + discount_code_id );
				$( '#swsales_after_discount_code_select' ).show();
			}
		}
		$( '#swsales_discount_code_select' ).change(
			function(){
				swsales_toggle_discount_code();
			}
		);
		swsales_toggle_discount_code();

		// create new discount code AJAX
		$( '#swsales_create_discount_code' ).click(
			function() {
				var data = {
					'action': 'swsales_create_discount_code',
					'swsales_pmpro_id': $( '#post_ID' ).val(),
					'swsales_start': $( '#swsales_start_year' ).val() + '-'
							 + $( '#swsales_start_month' ).val() + '-'
							 + $( '#swsales_start_day' ).val(),
					'swsales_end': $( '#swsales_end_year' ).val() + '-'
							 + $( '#swsales_end_month' ).val() + '-'
							 + $( '#swsales_end_day' ).val(),
					'nonce': swsales.create_discount_code_nonce,
				};
				$.post(
					ajaxurl,
					data,
					function(response) {
						response = $.parseJSON( response );
						if (response.status == 'error' ) {
							alert( response.error );
						} else {
							// success
							$( '#swsales_discount_code_select' ).append( '<option value="' + response.code.id + '">' + response.code.code + '</option>' );
							$( '#swsales_discount_code_select' ).val( response.code.id );
							swsales_toggle_discount_code();
						}
					}
				);
			}
		);

		// toggling the upsell settings
		$( '#swsales_upsell_enabled' ).change(
			function(){
				if (this.checked) {
					$( '.swsales_upsell_settings' ).show();
				} else {
					$( '.swsales_upsell_settings' ).hide();
				}
			}
		);
	}
);
