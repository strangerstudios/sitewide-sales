function swsales_get_tracking_cookie() {
	var cookie_string = wpCookies.get( 'swsales_' + swsales.sitewide_sale_id + '_tracking', '/' );
	var cookie_array;
	if ( null == cookie_string ) {
		cookie_array = {'banner': 0, 'landing_page': 0};
	} else {
		// get array from the cookie text
		var parts    = cookie_string.split( ';' );
		cookie_array = {'banner': parts[0], 'landing_page': parts[1]};
	}

	return cookie_array;
}

function swsales_set_tracking_cookie(cookie_array) {
	var cookie_string = cookie_array.banner + ';' + cookie_array.landing_page;
	wpCookies.set( 'swsales_' + swsales.sitewide_sale_id + '_tracking', cookie_string, 86400 * 30, '/' );
}

function swsales_send_ajax(report) {
	jQuery.post(
    swsales.ajax_url, 
    {
        'action': 'swsales_ajax_tracking',
        'report': report,
				'sitewide_sale_id': swsales.sitewide_sale_id
    }, 
    function(response) {
        //console.log('The server responded: ', response);
    }
);
}

function swsales_track() {
	var cookie = swsales_get_tracking_cookie();
	if ( jQuery( '.swsales-banner' ).length ) {
		if ( cookie['banner'] == 0 ) {
			cookie['banner'] = 1;
			swsales_send_ajax( 'swsales_banner_impressions' );
			swsales_set_tracking_cookie( cookie );
		}
	}

	if ( swsales.landing_page == 1 ) {
		if ( cookie['landing_page'] == 0 ) {
			cookie['landing_page'] = 1;
			swsales_send_ajax( 'swsales_landing_page_visits' );
			swsales_set_tracking_cookie( cookie );
		}
	}
}

jQuery( document ).ready(
	function() {
		console.log(swsales);
		swsales_track();
	}
);
