<?php
namespace Sitewide_Sales\templates\scroll;

/**
 * Scroll Template for Sitewide Sales
 *
 */

/**
 * Add template to list.
 */
function swsale_templates( $templates ) {
	$templates['scroll'] = 'Scroll';

	return $templates;
}
add_filter( 'swsale_templates', __NAMESPACE__ . '\swsale_templates' );
 
/**
 * Load our landing page and banner CSS/JS if needed.
 */
function wp_enqueue_scripts() {
	// Load landing page CSS if needed.
	if ( swsales_landing_page_template() == 'scroll' ) {
		wp_register_style( 'swsales_scroll_landing_page', plugins_url( 'templates/scroll/landing-page.css', SWSALES_BASENAME ), null, SWSALES_VERSION );
		wp_enqueue_style( 'swsales_scroll_landing_page' ); 
	}

	// Load banner CSS if needed.
	if ( swsales_banner_template() == 'scroll' ) {
		wp_register_style( 'swsales_scroll_banner', plugins_url( 'templates/scroll/banner.css', SWSALES_BASENAME ), null, SWSALES_VERSION );
		wp_enqueue_style( 'swsales_scroll_banner' );
	} 
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\wp_enqueue_scripts' );

function swsales_landing_page_content_scroll( $content ) {
	$content_before = '<div id="swsale-landing-page-wrap-scroll" class="swsales-landing-page-wrap">';
	$content_after = '</div>';

	$background_image = wp_get_attachment_image_src( get_post_thumbnail_id( get_queried_object_id() ), 'full' );
	if ( ! empty( $background_image[0] ) ) {
		$content_before .= '<div class="swsales-landing-page-background-image" style="background-image: url(' . $background_image[0] . ')">';
		$content_after .= '</div>';
	}

	$content = $content_before . $content . $content_after;

	return $content;
}
add_action( 'swsales_landing_page_content_scroll', __NAMESPACE__ . '\swsales_landing_page_content_scroll' );
