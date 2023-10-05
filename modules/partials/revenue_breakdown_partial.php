<?php
//Bail if param it's not an array
if(! is_array( $sitewide_sales ) ) {
    if ( ! is_a( $sitewide_sales, 'Sitewide_Sales\classes\SWSales_Sitewide_Sale' )) {
        return;
    }
    //somehow do_action filter converts the array into a single object, so let's wrap in an array again.
    $sitewide_sales = array( $sitewide_sales );
} else {
    // Bail if given elements aren't SWSales_Sitewide_Sale objects.
    foreach ($sitewide_sales as $sitewide_sale) {
        if ( ! is_a( $sitewide_sale, 'Sitewide_Sales\classes\SWSales_Sitewide_Sale' ) ) {
            return;
        }
    }
    // Bail if the array comes empty
    if( count( $sitewide_sales ) < 1) {
        return;
    }
}

?>

<div class="swsales_reports-box">
    <h1 class="swsales_reports-box-title"><?php esc_html_e( 'Revenue Breakdown', 'sitewide-sales' ); ?></h1>
    <table class="reports-comparison-table-below-chart">
        <tr>
            <th>
            </th>
            <th>
                <?php esc_html_e( 'Sale Revenue', 'sitewide-sales' ); ?>
            </th>
            <th>
                <?php esc_html_e( 'Other New Revenue', 'sitewide-sales' ); ?>
            </th>

            <?php if ( $sitewide_sales[0]->get_sale_type() == 'pmpro' ) { ?>
                <th>
                    <?php esc_html_e( 'Renewals', 'sitewide-sales' ); ?>
                </th>
            <?php } ?>
            <th>
                <?php esc_html_e( 'Total new revenue in Period', 'sitewide-sales' ); ?>
            </th>
        </tr>
        <tbody>
        <?php foreach ($sitewide_sales as $sitewide_sale) { ?>
            <tr>
                <td>
                    <?php echo esc_html( $sitewide_sale->get_name() ); ?>
                </td>
                <td>
                    <?php echo esc_html( $sitewide_sale->get_revenue(true) ); ?>
                </td>
                <td>
                    <?php echo esc_html( $sitewide_sale->get_other_revenue(true) ); ?>
                </td>
                <?php if ( $sitewide_sales[0]->get_sale_type() == 'pmpro' ) { ?>
                    <td>
                        <?php if ( $sitewide_sale->get_sale_type() == 'pmpro' ) {
                                echo esc_html( $sitewide_sale->get_renewal_revenue() );
                            }
                        ?>
                    </td>
                <?php } ?>
                <td>
                    <?php echo esc_html( $sitewide_sale->get_total_revenue() ); ?>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>