<?php
namespace Sitewide_Sales\templates\fancy_coupon;

/**
 * Fancy Coupon Template for Sitewide Sales
 * Templates must at the very least:
 * 0. Update the namespace at the top of the page.
 * 1. Add themselves to the templates list.
 * 2. Load any CSS or JS for the banner.
 * 3. Load any CSS or JS for the landing page.
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