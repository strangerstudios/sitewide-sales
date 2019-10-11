jQuery( document ).ready(
	function($) {

		// multiselects
		$( "#swsales_wc_coupon_select" ).selectWoo();

		// toggling the discount code input layout
		function swsales_wc_toggle_coupon() {
			var coupon_id = $( '#swsales_wc_coupon_select' ).val();

			if (coupon_id == 0) {
				$( '#swsales_wc_after_coupon_select' ).hide();
			} else {
				$( '#swsales_wc_edit_coupon' ).attr( 'href', swsales.admin_url + 'post.php?action=edit&post=' + coupon_id );
				$( '#swsales_wc_after_coupon_select' ).show();
			}
		}
		$( '#swsales_wc_coupon_select' ).change(
			function(){
				swsales_wc_toggle_coupon();
			}
		);
		swsales_wc_toggle_coupon();

		// create new discount code AJAX
		// TODO: Update this function to wc coupons.
		/*
		$( '#swsales_pmpro_create_discount_code' ).click(
			function() {
				var data = {
					'action': 'swsales_pmpro_create_discount_code',
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
							$( '#swsales_pmpro_discount_code_select' ).append( '<option value="' + response.code.id + '">' + response.code.code + '</option>' );
							$( '#swsales_pmpro_discount_code_select' ).val( response.code.id );
							swsales_pmpro_toggle_discount_code();
						}
					}
				);
			}
		);
		*/
	}
);
