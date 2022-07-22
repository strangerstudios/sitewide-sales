<?php

namespace Sitewide_Sales\classes;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

class SWSales_Reports {

	/**
	 * Adds actions for class
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_reports_page' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_tracking_js' ) );
		add_action( 'wp_ajax_swsales_ajax_tracking', array( __CLASS__, 'ajax_tracking' ) );
		add_action( 'wp_ajax_nopriv_swsales_ajax_tracking', array( __CLASS__, 'ajax_tracking' ) );
	}

	public static function add_reports_page() {
		add_submenu_page(
			'edit.php?post_type=sitewide_sale',
			__( 'Reports', 'sitewide-sales' ),
			__( 'Reports', 'sitewide-sales' ),
			'manage_options',
			'sitewide_sales_reports',
			array( __CLASS__, 'show_reports_page' )
		);
	}

	public static function show_reports_page() { ?>
		<div class="wrap sitewide_sales_admin">
			<h1><?php esc_html_e( 'Reports', 'sitewide-sales' ); ?></h1>
			<?php
				// Get all sitewide_sale ids.
				$all_sitewide_sales = get_posts(
					array(
						'fields'         => 'ids',
						'posts_per_page' => -1,
						'post_type'      => 'sitewide_sale',
					)
				);

				// Choose sale to show.
				$sale_to_show = null;
				if ( isset( $_REQUEST['sitewide_sale'] ) ) {
					$sale_to_show = SWSales_Sitewide_Sale::get_sitewide_sale( $_REQUEST['sitewide_sale'] );
				}
				if ( null === $sale_to_show ) {
					$sale_to_show = SWSales_Sitewide_Sale::get_active_sitewide_sale();
				}

				// Select field to choose a sitewide sale.
				if ( ! empty ( $all_sitewide_sales ) ) { ?>
					<form method="get" action="/wp-admin/edit.php">
						<input type="hidden" name="post_type" value="sitewide_sale" />
						<input type="hidden" name="page" value="sitewide_sales_reports" />
						<label for="sitewide_sale"><?php esc_html_e( 'Show reports for', 'sitewide-sales' ); ?></label>
						<select id="swsales_select_report" name="sitewide_sale" onchange="this.form.submit()">
							<?php
							foreach ( $all_sitewide_sales as $sitewide_sale_id ) {
									$sale              = SWSales_Sitewide_Sale::get_sitewide_sale( $sitewide_sale_id );
									$selected_modifier = ( ! ( null === $sale_to_show ) && $sale->get_id() === $sale_to_show->get_id() ) ? 'selected="selected"' : '';
								?>
								<option value="<?php esc_attr_e( $sale->get_id() ); ?>" <?php echo( esc_html( $selected_modifier ) ); ?>>
									<?php echo( esc_html( $sale->get_name() ) ); ?>
								</option>
								<?php
							}
							?>
						</select>
					</form>
					<hr />
					<?php
				} else { ?>
					<div class="sitewide_sales_message sitewide_sales_alert"><?php printf(__( 'No Sitewide Sales found. <a href="%s">Create your first Sitewide Sale &raquo;</a>', 'sitewide-sales' ), admin_url( 'post-new.php?post_type=sitewide_sale' ) ); ?></div>
					<?php
				}

				// Show report for sitewide sale if applicable.
				if ( null !== $sale_to_show ) {
					SWSales_Reports::show_report( $sale_to_show );
				}
			?>
		</div> <!-- sitewide-sales_admin -->
		<?php
	}

	/**
	 * Show report content for a Sitewide Sale.
	 *
	 * @param SWSales_Sitewide_Sale $sitewide_sale to show report for.
	 */
	public static function show_report( $sitewide_sale ) {
		if ( ! is_a( $sitewide_sale, 'Sitewide_Sales\classes\SWSales_Sitewide_Sale' ) ) {
			return;
		}
		?>
		<div class="swsales_reports-box">
			<h1 class="swsales_reports-box-title"><?php esc_html_e( 'Overall Sale Performance', 'sitewide-sales' ); ?></h1>
			<p>
			<?php
				printf(
					wp_kses_post( 'All visitors from %s to %s.', 'sitewide-sales' ),
					esc_html( $sitewide_sale->get_start_date() ),
					esc_html( $sitewide_sale->get_end_date() )
				);
			?>
			</p>
			<hr />
			<div class="swsales_reports-data swsales_reports-data-4col">
				<div id="swsales_reports-data-section_banner" class="swsales_reports-data-section">
					<h1><?php echo esc_attr( $sitewide_sale->get_banner_impressions() ); ?></h1>
					<p><?php esc_html_e( 'Banner Reach', 'sitewide-sales' ); ?></p>
				</div>
				<div id="swsales_reports-data-section_sales" class="swsales_reports-data-section">
					<h1><?php echo esc_attr( $sitewide_sale->get_landing_page_visits() ); ?></h1>
					<p>
						<?php
							printf(
								wp_kses_post( '<a href="%s" title="%s">Landing</a> Page Visits', 'sitewide-sales' ),
								get_permalink( $sitewide_sale->get_landing_page_post_id() ),
								get_the_title( $sitewide_sale->get_landing_page_post_id() )
							);
						?>
					</p>
				</div>
				<div id="swsales_reports-data-section_sales" class="swsales_reports-data-section">
					<h1><?php echo esc_attr( $sitewide_sale->get_checkout_conversions() ); ?></h1>
					<p>
						<?php
							printf(
								wp_kses_post( apply_filters( 'swsales_checkout_conversions_title', __( 'Checkout Conversions', 'sitewide-sales' ), $sitewide_sale ) )
							);
						?>
					</p>
				</div>
				<div class="swsales_reports-data-section">
					<h1><?php echo esc_attr( $sitewide_sale->get_revenue() ); ?></h1>
					<p><?php esc_html_e( 'Sale Revenue', 'sitewide-sales' ); ?></p>
				</div>
			</div>
			<?php
			// Daily Revenue Chart.
			// Build an array with each day of sale as a key to store revenue data in.
			$date_array = array();
			$period = new \DatePeriod(
				new \DateTime( $sitewide_sale->get_start_date() ),
				new \DateInterval('P1D'),
				new \DateTime( $sitewide_sale->get_end_date() . ' + 1 day' )
			);
			foreach ($period as $key => $value) {
				$date_array[ $value->format('Y-m-d') ] = 0.0;     
			}

			$daily_revenue_chart_data = apply_filters( 'swsales_daily_revenue_chart_data', $date_array, $sitewide_sale );
			if ( is_array( $daily_revenue_chart_data ) && $daily_revenue_chart_data !== $date_array ) {
				?>
				<hr>
				<div id="chart_div" style="clear: both; width: 100%; height: 500px;"></div>
				<script>
					// Draw the chart.
					google.charts.load('current', {'packages':['corechart']});
					google.charts.setOnLoadCallback(drawVisualization);
					function drawVisualization() {

						var data = google.visualization.arrayToDataTable([
							[
								{ label: 'DAY' },
								{ label: 'Revenue' },
							],
							<?php foreach($daily_revenue_chart_data as $date => $value) { ?>
								[
									'<?php echo esc_html( date_i18n( get_option('date_format'), strtotime( $date ) ) ); ?>',
									<?php echo esc_html( $value );?>
								],
							<?php } ?>
						]);

						var options = {
							colors: ['#51a351'],
							chartArea: {width: '90%'},
							hAxis: {
								textStyle: {color: '#555555', fontSize: '12', italic: false},
								maxAlternation: 1
							},
							vAxis: {
								textPosition: 'none',
							},
							seriesType: 'bars',
							series: {1: {type: 'line', color: 'red'}},
							legend: {position: 'none'},
						};

						<?php
						$daily_revenue_chart_currency_format = array(
							'currency_symbol' => '$',
							'decimals' => 2,
							'decimal_separator' => '.',
							'thousands_separator' => ',',
							'position' => 'prefix' // Either "prefix" or "suffix".
						);
						$daily_revenue_chart_currency_format = apply_filters( 'swsales_daily_revenue_chart_currency_format', $daily_revenue_chart_currency_format, $sitewide_sale );
						?>
						var formatter = new google.visualization.NumberFormat({
							'<?php echo esc_html( $daily_revenue_chart_currency_format['position'] );?>': '<?php echo esc_html( html_entity_decode( $daily_revenue_chart_currency_format['currency_symbol'] ) ); ?>',
							'decimalSymbol': '<?php echo esc_html( html_entity_decode( $daily_revenue_chart_currency_format['decimal_separator'] ) ); ?>',
							'fractionDigits': <?php echo intval( $daily_revenue_chart_currency_format['decimals'] ); ?>,
							'groupingSymbol': '<?php echo esc_html( html_entity_decode( $daily_revenue_chart_currency_format['thousands_separator'] ) ); ?>',
						});
						formatter.format(data, 1);

						var chart = new google.visualization.ComboChart(document.getElementById('chart_div'));
						chart.draw(data, options);
					}
				</script>
				<?php
			}
			?>
		</div>
		<?php
		do_action( 'swsales_additional_reports', $sitewide_sale );
	}

	/**
	 * Show summarized report content for a Sitewide Sale.
	 *
	 * @param SWSales_Sitewide_Sale $sitewide_sale to show report for.
	 */
	public static function show_quick_report( $sitewide_sale ) {
		if ( ! is_a( $sitewide_sale, 'Sitewide_Sales\classes\SWSales_Sitewide_Sale' ) ) {
			return;
		}
		?>
		<div class="swsales_reports-quick-data-section">
			<span class="swsales_reports-quick-data-label"><?php esc_html_e( 'Banner Reach', 'sitewide-sales' ); ?></span>
			<span class="swsales_reports-quick-data-value"><?php echo esc_attr( $sitewide_sale->get_banner_impressions() ); ?></span>
		</div>
		<div class="swsales_reports-quick-data-section">
			<span class="swsales_reports-quick-data-label"><?php esc_html_e( 'Landing Page Visits', 'sitewide-sales' ); ?></span>
			<span class="swsales_reports-quick-data-value"><?php echo esc_attr( $sitewide_sale->get_landing_page_visits() ); ?></span>
		</div>
		<div class="swsales_reports-quick-data-section">
			<span class="swsales_reports-quick-data-label"><?php esc_html_e( 'Conversions', 'sitewide-sales' ); ?></span>
			<span class="swsales_reports-quick-data-value"><?php echo esc_attr( $sitewide_sale->get_checkout_conversions() ); ?></span>
		</div>
		<div class="swsales_reports-quick-data-section">
			<span class="swsales_reports-quick-data-label"><?php esc_html_e( 'Sale Revenue', 'sitewide-sales' ); ?></span>
			<span class="swsales_reports-quick-data-value"><?php echo esc_attr( $sitewide_sale->get_revenue() ); ?></span>
		</div>
		<?php
	}

	public static function admin_enqueue_scripts() {
		global $typenow;
		$screen = get_current_screen();
		$screens_to_load_on = array( 'sitewide_sale_page_sitewide_sales_reports', 'sitewide_sale' ); // Reports page and editing a sitewide sale.
		if ( ! empty( $screen ) && in_array( $screen->id, $screens_to_load_on ) ) {
			wp_enqueue_script( 'corechart', plugins_url( 'js/corechart.js', SWSALES_BASENAME ) );
		}
	}

	/**
	 * Setup JS vars and enqueue our JS for tracking user behavior
	 */
	public static function enqueue_tracking_js() {
		$active_sitewide_sale = SWSales_Sitewide_Sale::get_active_sitewide_sale();
		if ( null === $active_sitewide_sale ) {
			return;
		}

		wp_register_script( 'swsales_tracking', plugins_url( 'js/swsales.js', SWSALES_BASENAME ), array( 'jquery', 'utils' ) );

		$landing_page_post_id = $active_sitewide_sale->get_landing_page_post_id();
		$swsales_data = array(
			'landing_page'      => ! empty( $landing_page_post_id ) && is_page( $landing_page_post_id ),
			'sitewide_sale_id'  => $active_sitewide_sale->get_id(),
			'banner_close_behavior'  => $active_sitewide_sale->get_banner_close_behavior(),
			'ajax_url'          => admin_url( 'admin-ajax.php' ),
		);

		wp_localize_script( 'swsales_tracking', 'swsales', $swsales_data );
		wp_enqueue_script( 'swsales_tracking' );
	}

	/**
	 * Ajax call to update SWSales statistics
	 */
	public static function ajax_tracking() {
		if ( ! isset( $_POST['sitewide_sale_id'] ) || ! isset( $_POST['report'] ) ) {
			echo 'Missing information in request. ';
			return;
		}

		$sitewide_sale_id    = intval( $_POST['sitewide_sale_id'] );
		$report_to_increment = sanitize_text_field( $_POST['report'] );
		$valid_reports       = array( 'swsales_banner_impressions', 'swsales_landing_page_visits' );
		if ( in_array( $report_to_increment, $valid_reports, true ) ) {
			$old_report_val = get_post_meta( $sitewide_sale_id, $report_to_increment, true );
			if ( ! is_numeric( $old_report_val ) ) {
				$old_report_val = 0;
			}
			update_post_meta( $sitewide_sale_id, $report_to_increment, intval( $old_report_val ) + 1 );
			echo 'Success. ';
			return;
		}
		echo 'Invalid Report. ';
		return;
	}
}
