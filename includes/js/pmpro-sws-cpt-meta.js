jQuery( document ).ready(
	function($) {
		// show new install screen if it was rendered
		var pmpro_sws_new_install = $( 'div.pmpro-new-install' );
		if (pmpro_sws_new_install.length > 0) {
			$( '#posts-filter' ).hide();
			$( '#posts-filter' ).siblings( 'ul.subsubsub' ).hide();
			pmpro_sws_new_install.insertAfter( 'hr.wp-header-end' );
			pmpro_sws_new_install.show();
		}

		// multiselects
		$( "#pmpro_sws_discount_code_select" ).selectWoo();
		$( "#pmpro_sws_landing_page_select" ).selectWoo();
		$( "#pmpro_sws_use_banner_select" ).selectWoo();
		$( "#pmpro_sws_hide_levels_select" ).selectWoo();
		$( "#pmpro_sws_upsell_levels" ).selectWoo();

		// removing some buttons from the edit post page for our CPT
		jQuery( '.wp-editor-tabs' ).remove();
		jQuery( '#insert-media-button' ).remove();

		// make sure save all settings buttons don't prompt the leave site alert
		$( 'input[type=submit]' ).click(
			function(){
				$( window ).off( 'beforeunload.edit-post' );
			}
		);

		// toggling the discount code input layout
		function pmpro_sws_toggle_discount_code() {
			var discount_code_id = $( '#pmpro_sws_discount_code_select' ).val();

			if (discount_code_id == 0) {
				$( '#pmpro_sws_after_discount_code_select' ).hide();
			} else {
				$( '#pmpro_sws_edit_discount_code' ).attr( 'href', pmpro_sws.admin_url + 'admin.php?page=pmpro-discountcodes&edit=' + discount_code_id );
				$( '#pmpro_sws_after_discount_code_select' ).show();
			}
		}
		$( '#pmpro_sws_discount_code_select' ).change(
			function(){
				pmpro_sws_toggle_discount_code();
			}
		);
		pmpro_sws_toggle_discount_code();

		// create new discount code AJAX
		$( '#pmpro_sws_create_discount_code' ).click(
			function() {
				var data = {
					'action': 'pmpro_sws_create_discount_code',
					'pmpro_sws_id': $( '#post_ID' ).val(),
					'pmpro_sws_start': $( '#pmpro_sws_start_year' ).val() + '-'
							 + $( '#pmpro_sws_start_month' ).val() + '-'
							 + $( '#pmpro_sws_start_day' ).val(),
					'pmpro_sws_end': $( '#pmpro_sws_end_year' ).val() + '-'
							 + $( '#pmpro_sws_end_month' ).val() + '-'
							 + $( '#pmpro_sws_end_day' ).val(),
					'nonce': pmpro_sws.create_discount_code_nonce,
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
							$( '#pmpro_sws_discount_code_select' ).append( '<option value="' + response.code.id + '">' + response.code.code + '</option>' );
							$( '#pmpro_sws_discount_code_select' ).val( response.code.id );
							pmpro_sws_toggle_discount_code();
						}
					}
				);
			}
		);

		// toggling the landing page input layout
		function pmpro_sws_toggle_landing_page() {
			var landing_page_id = $( '#pmpro_sws_landing_page_select' ).val();
			if (landing_page_id == 0) {
				$( '#pmpro_sws_after_landing_page_select' ).hide();
				$( '.pmpro_sws_shortcode_warning' ).hide();
			} else {
				$( '#pmpro_sws_edit_landing_page' ).attr( 'href', pmpro_sws.admin_url + 'post.php?post=' + landing_page_id + '&action=edit' );
				$( '#pmpro_sws_view_landing_page' ).attr( 'href', pmpro_sws.home_url + '?p=' + landing_page_id );
				if ( pmpro_sws.pages_with_shortcodes.indexOf( landing_page_id ) > -1) {
					$( '.pmpro_sws_shortcode_warning' ).hide();
				} else {
					$( '.pmpro_sws_shortcode_warning' ).show();
				}
				$( '#pmpro_sws_after_landing_page_select' ).show();
			}
		}
		$( '#pmpro_sws_landing_page_select' ).change(
			function(){
				pmpro_sws_toggle_landing_page();
			}
		);
		pmpro_sws_toggle_landing_page();

		// create new landing page AJAX
		$( '#pmpro_sws_create_landing_page' ).click(
			function() {
				var data = {
					'action': 'pmpro_sws_create_landing_page',
					'pmpro_sws_id': $( '#post_ID' ).val(),
					'pmpro_sws_landing_page_title': $( '#title' ).val(),
					'nonce': pmpro_sws.create_landing_page_nonce,
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
							pmpro_sws.pages_with_shortcodes.push( String( response.post.ID ) );
							$( '#pmpro_sws_landing_page_select' ).append( '<option value="' + response.post.ID + '">' + response.post.post_title + ' (' + pmpro_sws.str_draft + ')</option>' );
							$( '#pmpro_sws_landing_page_select' ).val( response.post.ID );
							pmpro_sws_toggle_landing_page();
						}
					}
				);
			}
		);

		// toggling the banner settings and banner CSS hint
		function pmpro_sws_toggle_banner_settings() {
			var banner = $( '#pmpro_sws_use_banner_select' ).val();

			if (typeof banner == 'undefined' ) {
				return;
			}

			if (banner.length < 1 || banner == 'no') {
				$( '#pmpro_sws_banner_options' ).hide();
				$( '#pmpro_sws_css_selectors_description' ).hide();
				$( '.pmpro_sws_banner_css_selectors' ).hide();
			} else {
				$( '#pmpro_sws_css_selectors_description' ).show();
				$( '.pmpro_sws_banner_css_selectors' ).hide();
				$( '.pmpro_sws_banner_css_selectors[data-pmprosws-banner=' + banner + ']' ).show();
				$( '#pmpro_sws_banner_options' ).show();
			}
		}
		$( '#pmpro_sws_use_banner_select' ).change(
			function(){
				pmpro_sws_toggle_banner_settings();
			}
		);
		pmpro_sws_toggle_banner_settings();

		// toggling the upsell settings
		$( '#pmpro_sws_upsell_enabled' ).change(
			function(){
				if (this.checked) {
					$( '.pmpro_sws_upsell_settings' ).show();
				} else {
					$( '.pmpro_sws_upsell_settings' ).hide();
				}
			}
		);
	}
);
