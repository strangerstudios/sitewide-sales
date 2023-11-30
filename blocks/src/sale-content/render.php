<?php
$output = '';

// Is this post a sale landing page? If so, load the sale.
$sitewide_sale_id = get_post_meta( get_queried_object_id(), 'swsales_sitewide_sale_id', true );

// Set 'Always' period to empty string.
if ( empty( $attributes['period'] ) ) {
    $attributes['period'] = '';
}

if ( ! empty( $sitewide_sale_id ) ) {
    $sitewide_sale = new \Sitewide_Sales\classes\SWSales_Sitewide_Sale();
    $sale_found = $sitewide_sale->get_sitewide_sale( $sitewide_sale_id );
    $sitewide_sale = $sale_found;
}

// Or, try to load the active sale.
if ( empty( $sale_found ) ) {
    $sitewide_sale = new \Sitewide_Sales\classes\SWSales_Sitewide_Sale();
    $sale_found = $sitewide_sale->get_active_sitewide_sale();
    $sitewide_sale = $sale_found;
}

// Still no sale? Return nothing and don't render the inner blocks.
if ( ! $sale_found ) {
    echo $output;
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
if ( empty( $attributes['period'] ) || $sale_period === $attributes['period'] ) {
    echo $output = do_blocks( $content );
}