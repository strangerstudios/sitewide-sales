<?php
/**
 * Sets up the Sale Content block, does not format frontend.
 *
 * @package blocks/sale-content
 **/

namespace SWSales\blocks\sale_content;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

// Only load if Gutenberg is available.
if ( ! function_exists( 'register_block_type' ) ) {
	return;
}

add_action( 'init', __NAMESPACE__ . '\register_dynamic_block' );
/**
 * Register the dynamic block.
 *
 * @return void
 */
function register_dynamic_block() {
	// Hook server side rendering into render callback.
	register_block_type( 'swsales/sale-content', [
		'render_callback' => __NAMESPACE__ . '\render_dynamic_block',
	] );
}

/**
 * Server rendering for the sale content block.
 *
 * @param array $attributes.
 * @return string
 **/
function render_dynamic_block( $attributes, $content ) {
	$sitewide_sale = new \Sitewide_Sales\classes\SWSales_Sitewide_Sale();
	$sitewide_sale_id = get_post_meta( get_queried_object_id(), 'swsales_sitewide_sale_id', true );
	$sale_found = $sitewide_sale->load_sitewide_sale( $sitewide_sale_id );

	// Return nothing if no sale found.
	if ( ! $sale_found ) {
		return;
	}

	// Get the time period for the sale based on sale settings and current date.
	$sale_period = $sitewide_sale->get_time_period();

	// Allow admins to preview the sale period using a URL attribute.
	if ( current_user_can( 'administrator' ) && isset( $_REQUEST['swsales_preview_time_period'] ) ) {
		$sale_period = $_REQUEST['swsales_preview_time_period'];
	}

	/**
	 * If the block attributes period matches the sale period, output the contents.
	 * Otherwise, output nothing.
	 */
	if ( $sale_period === $attributes['period'] ) {
		return do_blocks( $content );
	}
}
