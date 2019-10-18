<?php
namespace Sitewide_Sales\templates\fancy_coupon;

/**
 * Fancy Coupon Template for Sitewide Sales
 *
 */

/**
 * Add template to list.
 */
function swsale_templates( $templates ) {
	$templates['fancy_coupon'] = 'Fancy Coupon';

	return $templates;
}
add_filter( 'swsale_templates', __NAMESPACE__ . '\swsale_templates' );

/**
 * Load our landing page and banner CSS/JS if needed.
 */
function wp_enqueue_scripts() {
	// Load landing page CSS if needed.
	if ( swsales_landing_page_template() == 'fancy_coupon' ) {
		wp_register_style( 'swsales_fancy_coupon_landing_page', plugins_url( 'templates/fancy-coupon/landing-page.css', SWSALES_BASENAME ), null, SWSALES_VERSION );
		wp_enqueue_style( 'swsales_fancy_coupon_landing_page' ); 
	}

	// Load banner CSS if needed.
	if ( swsales_banner_template() == 'fancy_coupon' ) {
		wp_register_style( 'swsales_fancy_coupon_banner', plugins_url( 'templates/fancy-coupon/banner.css', SWSALES_BASENAME ), null, SWSALES_VERSION );
		wp_enqueue_style( 'swsales_fancy_coupon_banner' );
	}
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\wp_enqueue_scripts' );

function swsales_landing_page_content_fancy_coupon( $content ) {
	$content_before = '<div id="swsale-landing-page-wrap-fancy-coupon" class="swsales-landing-page-wrap">';
	
	$content_after = '<div class="swsales-landing-page-fancy-coupon-coupon">';
	$content_after .= '<h3><small>' . esc_html( 'USE CODE', 'sitewide-sales' ) . '</small><br />';
	$content_after .= '-- get code here --';
	$content_after .= '</h3></div>';

	$content = $content_before . $content . $content_after;

	return $content;
}
add_action( 'swsales_landing_page_content_fancy_coupon', __NAMESPACE__ . '\swsales_landing_page_content_fancy_coupon' );
