<div class="swsales_reports-box">
    <h1 class="swsales_reports-box-title"><?php esc_html_e( 'Revenue Breakdown', 'sitewide-sales' ); ?></h1>
    <p> 
        <?php
        printf(
            wp_kses_post( 'All orders from %s to %s.', 'sitewide-sales' ),
            $sitewide_sale->get_start_date(),
            $sitewide_sale->get_end_date()
        );
        ?>
    </p>
    <hr />
    <div class="swsales_reports-data swsales_reports-data-3col">
        <div class="swsales_reports-data-section">
            <h1><?php echo esc_attr( wp_strip_all_tags( get_revenue_by_module( $new_rev_with_code) ) );//edd_currency_filter( edd_format_amount( $new_rev_with_code ) ) ) ); ?></h1>
            <p>
                <?php esc_html_e( 'Sale Revenue', 'sitewide-sales' ); ?>
                <br />
                (<?php echo( esc_html( 0 == $total_rev ? 'NA' : round( ( $new_rev_with_code / $total_rev ) * 100, 2 ) ) ); ?>%)
            </p>
        </div>
        <div class="swsales_reports-data-section">
            <h1><?php echo esc_attr( wp_strip_all_tags( edd_currency_filter( edd_format_amount( $new_rev_without_code ) ) ) ); ?></h1>
            <p>
                <?php esc_html_e( 'Other New Revenue', 'sitewide-sales' ); ?>
                <br />
                (<?php echo( esc_html( 0 == $total_rev ? 'NA' : round( ( $new_rev_without_code / $total_rev ) * 100, 2 ) ) ); ?>%)
            </p>
        </div>
        <div class="swsales_reports-data-section">
            <h1><?php echo esc_attr( wp_strip_all_tags( edd_currency_filter( edd_format_amount( $total_rev ) ) ) ); ?></h1>
            <p><?php esc_html_e( 'Total Revenue in Period', 'sitewide-sales' ); ?></p>
        </div>
    </div>
</div>

<?php

function get_revenue_by_module($rev) {
    switch( get_class()) {
        case 'a':
        break;
        default:
    
    }
}
	