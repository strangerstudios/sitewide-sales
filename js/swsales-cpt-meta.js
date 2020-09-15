jQuery( document ).ready(
	function($) {
		// show new install screen if it was rendered
		var swsales_new_install = $( 'div.pmpro-new-install' );
		if (swsales_new_install.length > 0) {
			$( '#posts-filter' ).hide();
			$( '#posts-filter' ).siblings( 'ul.subsubsub' ).hide();
			swsales_new_install.insertAfter( 'hr.wp-header-end' );
			swsales_new_install.show();
		}

		// multiselects
		$( "#swsales_landing_page_select" ).selectWoo();
		$( "#swsales_use_banner_select" ).selectWoo();

		// removing some buttons from the edit post page for our CPT
		jQuery( '.wp-editor-tabs' ).remove();
		jQuery( '#insert-media-button' ).remove();

		// make sure save all settings buttons don't prompt the leave site alert
		$( 'input[type=submit]' ).click(
			function(){
				$( window ).off( 'beforeunload.edit-post' );
			}
		);

		// toggling the landing page input layout
		function swsales_toggle_landing_page() {
			var landing_page_id = $( '#swsales_landing_page_select' ).val();
			if (landing_page_id == 0) {
				$( '#swsales_after_landing_page_select' ).hide();
				$( '.swsales_shortcode_warning' ).hide();
			} else {
				$( '#swsales_edit_landing_page' ).attr( 'href', swsales.admin_url + 'post.php?post=' + landing_page_id + '&action=edit' );
				$( '#swsales_view_landing_page' ).attr( 'href', swsales.home_url + '?p=' + landing_page_id );
				if ( swsales.pages_with_shortcodes == null ) {
					swsales.pages_with_shortcodes = [];
				}
				if ( swsales.pages_with_shortcodes.indexOf( landing_page_id ) > -1) {
					$( '.swsales_shortcode_warning' ).hide();
				} else {
					$( '.swsales_shortcode_warning' ).show();
				}
				$( '#swsales_after_landing_page_select' ).show();
			}
		}
		$( '#swsales_landing_page_select' ).change(
			function(){
				swsales_toggle_landing_page();
			}
		);
		swsales_toggle_landing_page();

		// create new landing page AJAX
		$( '#swsales_create_landing_page' ).click(
			function() {
				var data = {
					'action': 'swsales_create_landing_page',
					'swsales_id': $( '#post_ID' ).val(),
					'swsales_landing_page_title': $( '#title' ).val(),
					'nonce': swsales.create_landing_page_nonce,
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
							swsales.pages_with_shortcodes.push( String( response.post.ID ) );
							$( '#swsales_landing_page_select' ).append( '<option value="' + response.post.ID + '">' + response.post.post_title + ' (' + swsales.str_draft + ')</option>' );
							$( '#swsales_landing_page_select' ).val( response.post.ID );
							swsales_toggle_landing_page();
						}
					}
				);
			}
		);

		// toggling the banner settings and banner CSS hint
		function swsales_toggle_banner_settings() {
			var banner = $( '#swsales_use_banner_select' ).val();

			if (typeof banner == 'undefined' ) {
				return;
			}

			if (banner.length < 1 || banner == 'no') {
				$( '#swsales_banner_options' ).hide();
				$( '#swsales_css_selectors_description' ).hide();
				$( '.swsales_banner_css_selectors' ).hide();
			} else {
				$( '#swsales_css_selectors_description' ).show();
				$( '.swsales_banner_css_selectors' ).hide();
				$( '.swsales_banner_css_selectors[data-swsales-banner=' + banner + ']' ).show();
				$( '#swsales_banner_options' ).show();
			}
		}
		$( '#swsales_use_banner_select' ).change(
			function(){
				swsales_toggle_banner_settings();
			}
		);
		swsales_toggle_banner_settings();
		
		// Hiding/Showing module fields
		function swsales_toggle_module_rows() {
			var module = $( '#swsales_sale_type_select' ).val();
			$( '.swsales-module-row' ).hide();
			$( '.swsales-module-row-' + module ).show();
		}
		$( '#swsales_sale_type_select' ).change(
			function(){
				swsales_toggle_module_rows();
			}
		);
		swsales_toggle_module_rows();
	}
);
