function pmpro_sws_get_tracking_cookie() {
	var cookie_string = wpCookies.get( 'pmpro_sws_' + pmpro_sws.sitewide_sale_id + '_tracking', '/' );
	var cookie_array;
	if ( null == cookie_string ) {
		cookie_array = {'banner': 0, 'landing_page': 0, 'confirmation_page': 0};
	} else {
		// get array from the cookie text
		var parts    = cookie_string.split( ';' );
		cookie_array = {'banner': parts[0], 'landing_page': parts[1], 'confirmation_page': parts[2]};
	}

	return cookie_array;
}

function pmpro_sws_set_tracking_cookie(cookie_array) {
	var cookie_string = cookie_array.banner + ';' + cookie_array.landing_page + ';' + cookie_array.confirmation_page;
	wpCookies.set( 'pmpro_sws_' + pmpro_sws.sitewide_sale_id + '_tracking', cookie_string, 86400 * 30, '/' );
}

function pmpro_sws_send_ajax(element) {
	var data = {
		'action': 'pmpro_sws_ajax_tracking',
		'element': element,
		'sitewide_sale_id': pmpro_sws.sitewide_sale_id
	};
	jQuery.post( pmpro_sws.ajax_url, data );
}

function pmpro_sws_track() {
	var cookie = pmpro_sws_get_tracking_cookie();
	if ( jQuery( '.pmpro_sws_banner' ).length ) {
		if ( cookie['banner'] == 0 ) {
			cookie['banner'] = 1;
			pmpro_sws_send_ajax( 'banner_impressions' );
			pmpro_sws_set_tracking_cookie( cookie );
		}
	}

	if ( pmpro_sws.landing_page == 1 ) {
		if ( cookie['landing_page'] == 0 ) {
			if ( cookie['banner'] == 1 ) {
				pmpro_sws_send_ajax( 'landing_page_after_banner' );
			}
			cookie['landing_page'] = 1;
			pmpro_sws_send_ajax( 'landing_page_visits' );
			pmpro_sws_set_tracking_cookie( cookie );
		}
	}

	if ( pmpro_sws.confirmation_page == 1 && cookie['landing_page'] == 1 ) {
		if ( cookie['confirmation_page'] == 0 ) {
			cookie['confirmation_page'] = 1;
			if ( pmpro_sws.used_sale_code == 1 ) {
				pmpro_sws_send_ajax( 'checkout_conversions_with_code' );
			} else {
				pmpro_sws_send_ajax( 'checkout_conversions_without_code' );
			}
			pmpro_sws_set_tracking_cookie( cookie );
		}
	}
}

jQuery( document ).ready(
	function() {
		// console.log(pmpro_sws);
		pmpro_sws_track();
	}
);
