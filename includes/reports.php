<?php

/**
 * Report Widget, needs to be outside namespace because
 * function name is 'calculated' in core
 */
function pmpro_report_pmpro_sws_reports_widget() {
	esc_html_e( 'View reports for your most recent sales.', 'pmpro-sitewide-sale' );
	$sitewide_sales = get_posts(
		[
			'post_type'   => 'pmpro_sitewide_sale',
			'post_status' => 'publish',
			'numberposts' => 5,
			'orderby'     => 'ID',
			'order'       => 'DESC',
		]
	);
	if ( ! empty( $sitewide_sales ) ) {
		foreach ( $sitewide_sales as $sitewide_sale ) {
			echo '<p>';
			echo '<strong><a href="' . admin_url( 'admin.php?page=pmpro-reports&report=pmpro_sws_reports&pmpro_sws_sitewide_sale_id=' . $sitewide_sale->ID ) . '">' . esc_html( get_the_title( $sitewide_sale->ID ) ) . '</a></strong>';
			echo ' (';
			echo date_i18n( get_option( 'date_format' ), strtotime( $sitewide_sale->pmpro_sws_start_date, current_time( 'timestamp' ) ) );
			echo ' - ';
			echo date_i18n( get_option( 'date_format' ), strtotime( $sitewide_sale->pmpro_sws_end_date, current_time( 'timestamp' ) ) );
			echo ')';
			echo '</p>';
		}
	}
}

/**
 * Report Page, needs to be outside namespace because
 * function name is 'calculated' in core
 */
function pmpro_report_pmpro_sws_reports_page() {
	global $wpdb;
	$options        = PMPro_Sitewide_Sales\includes\classes\PMPro_SWS_Settings::get_options();
	$sitewide_sales = get_posts(
		[
			'post_type'   => 'pmpro_sitewide_sale',
			'post_status' => 'publish',
			'numberposts' => -1,
			'orderby'     => 'ID',
			'order'       => 'DESC',
		]
	);
	if ( ! empty( $_REQUEST['pmpro_sws_sitewide_sale_id'] ) ) {
		$sitewide_sale_id = intval( $_REQUEST['pmpro_sws_sitewide_sale_id'] );
	} else {
		$sitewide_sale_id = $options['active_sitewide_sale_id'];
	}

	$stats = PMPro_Sitewide_Sales\includes\classes\PMPro_SWS_Reports::get_stats_for_sale( $sitewide_sale_id );

	?>
	<form id="posts-filter" method="get" action="">
		<h1>
			<?php esc_html_e( 'Sitewide Sales Report', 'pmpro-sitewide-sales' ); ?>
		</h1>
		<ul class="subsubsub">
			<li>
				<?php esc_html_e( 'Show reports for ', 'pmpro-sitewide-sales' ); ?>
				<select name="pmpro_sws_sitewide_sale_id">
				<?php
				foreach ( $sitewide_sales as $sitewide_sale ) {
					echo '<option value="' . esc_attr( $sitewide_sale->ID ) . '" ' . selected( $sitewide_sale_id, $sitewide_sale->ID ) . '>' . esc_html( get_the_title( $sitewide_sale->ID ) ) . '</option>';
				}
				?>
				</select>

				<input type="hidden" name="page" value="pmpro-reports" />
				<input type="hidden" name="report" value="pmpro_sws_reports" />
				<input type="submit" class="button" value="<?php echo esc_attr_e( 'Generate Report', 'pmpro-sitewide-sales' ); ?>" />
			</li>
		</ul>
		<div class="clear"></div>
		<hr />
		<br />
		<h1 class="wp-heading-inline"><?php echo get_the_title( $sitewide_sale_id ); ?></h1>
		<a href="<?php echo esc_url( get_edit_post_link( $sitewide_sale_id ) ); ?>" class="page-title-action"><?php esc_html_e( 'Edit', 'pmpro-sitewide-sale' ); ?></a>
		<div class="pmpro_sws_reports-box">
			<h1 class="pmpro_sws_reports-box-title"><?php esc_html_e( 'Overall Sale Performance', 'pmpro-sitewide-sales' ); ?></h1>
			<p>
			<?php
				printf(
					wp_kses_post( 'All visitors from %s to %s.', 'pmpro-sitewide-sales' ),
					date( get_option( 'date_format' ), strtotime( $stats['start_date'], current_time( 'timestamp' ) ) ),
					date( get_option( 'date_format' ), strtotime( $stats['end_date'], current_time( 'timestamp' ) ) )
				);
			?>
			</p>
			<hr />
			<div class="pmpro_sws_reports-data pmpro_sws_reports-data-4col">
				<div id="pmpro_sws_reports-data-section_banner" class="pmpro_sws_reports-data-section">
					<h1><?php echo esc_attr( $stats['banner_impressions'] ); ?></h1>
					<p><?php esc_html_e( 'Banner Reach', 'pmpro-sitewide-sales' ); ?></p>
				</div>
				<div id="pmpro_sws_reports-data-section_sales" class="pmpro_sws_reports-data-section">
					<h1><?php echo esc_attr( $stats['landing_page_visits'] ); ?></h1>
					<p>
						<?php
							printf(
								wp_kses_post( '<a href="%s" title="%s">Landing</a> Page Visits', 'pmpro-sitewide-sales' ),
								$stats['landing_page_url'],
								$stats['landing_page_title']
							);
						?>
					</p>
				</div>
				<div id="pmpro_sws_reports-data-section_sales" class="pmpro_sws_reports-data-section">
					<h1><?php echo esc_attr( $stats['checkout_conversions_with_code'] ); ?></h1>
					<p>
						<?php
							printf(
								wp_kses_post( 'Checkouts using <a href="%s">%s</a>', 'pmpro-sitewide-sales' ),
								admin_url( 'admin.php?page=pmpro-discountcodes&edit=' . $stats['discount_code_id'] ),
								$stats['discount_code']
							);
						?>
					</p>
				</div>
				<div class="pmpro_sws_reports-data-section">
					<h1><?php echo esc_attr( pmpro_formatPrice( $stats['new_rev_with_code'] ) ); ?></h1>
					<p><?php esc_html_e( 'Sale Revenue', 'pmpro-sitewide-sales' ); ?></p>
				</div>
			</div>
		</div>
		<div class="pmpro_sws_reports-box">
			<h1 class="pmpro_sws_reports-box-title"><?php esc_html_e( 'Revenue Breakdown', 'pmpro-sitewide-sales' ); ?></h1>
			<p>
				<?php
				printf(
					wp_kses_post( 'All orders from %s to %s.', 'pmpro-sitewide-sales' ),
					date( get_option( 'date_format' ), strtotime( $stats['start_date'], current_time( 'timestamp' ) ) ),
					date( get_option( 'date_format' ), strtotime( $stats['end_date'], current_time( 'timestamp' ) ) )
				);
				?>
			</p>
			<hr />
			<div class="pmpro_sws_reports-data pmpro_sws_reports-data-4col">
				<div class="pmpro_sws_reports-data-section">
					<h1><?php echo esc_attr( pmpro_formatPrice( $stats['new_rev_with_code'] ) ); ?></h1>
					<p>
						<?php esc_html_e( 'Sale Revenue', 'pmpro-sitewide-sales' ); ?>
						<br />
						(<?php echo round( PMPro_Sitewide_Sales\includes\classes\PMPro_SWS_Reports::divide_into_percent( $stats['new_rev_with_code'], $stats['total_rev'] ), 2 ); ?>%)
					</p>
				</div>
				<div class="pmpro_sws_reports-data-section">
					<h1><?php echo esc_attr( pmpro_formatPrice( $stats['new_rev_without_code'] ) ); ?></h1>
					<p>
						<?php esc_html_e( 'Other New Revenue', 'pmpro-sitewide-sales' ); ?>
						<br />
						(<?php echo round( PMPro_Sitewide_Sales\includes\classes\PMPro_SWS_Reports::divide_into_percent( $stats['new_rev_without_code'], $stats['total_rev'] ), 2 ); ?>%)
					</p>
				</div>
				<div class="pmpro_sws_reports-data-section">
					<h1><?php echo esc_attr( pmpro_formatPrice( $stats['old_rev'] ) ); ?></h1>
					<p>
						<?php esc_html_e( 'Renewals', 'pmpro-sitewide-sales' ); ?>
						<br />
						(<?php echo round( PMPro_Sitewide_Sales\includes\classes\PMPro_SWS_Reports::divide_into_percent( $stats['old_rev'], $stats['total_rev'] ), 2 ); ?>%)
					</p>
				</div>
				<div class="pmpro_sws_reports-data-section">
					<h1><?php echo esc_attr( pmpro_formatPrice( $stats['total_rev'] ) ); ?></h1>
					<p><?php esc_html_e( 'Total Revenue in Period', 'pmpro-sitewide-sales' ); ?></p>
				</div>
			</div>
		</div>
	</form>
	<?php
}
