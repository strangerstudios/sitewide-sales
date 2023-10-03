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
		add_action("wp_ajax_sws_stats_csv", array(__CLASS__, "sws_stats_csv"));
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

	/**
	 * Handles the Sales Export
	 */
	public static function sws_stats_csv() {
		require_once(dirname(__FILE__) . "/../adminpages/report-csv.php");
		exit;
	}

	/**
	 * Gets a $csv_export_link for a sitewide sale.
	 *
	 * @param SWSales_Sitewide_Sale $sitewide_sale to get link for.
	 * @return string $csv_export_link.
	 * @since TBD.
	 */
	public static function build_CSV_report_link($sitewide_sale) {
		//Bail if param is not correct.
		if(! is_a( $sitewide_sale, 'Sitewide_Sales\classes\SWSales_Sitewide_Sale' ) ) {
			return;
		}
		$csv_export_link = add_query_arg(
			array(
				'action' => 'sws_stats_csv',
				'sitewide_sale' => $sitewide_sale->get_id(),
			),
			admin_url( 'admin-ajax.php' ) );

		return esc_url( $csv_export_link );
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
				$sales_to_show = null;
				if ( isset( $_REQUEST['sitewide_sale'] ) ) {
					if ( is_array( $_REQUEST['sitewide_sale'] ) ) {
						$sales_to_show = SWSales_Sitewide_Sale::get_sitewide_sales( $_REQUEST['sitewide_sale'] );
					} else {
						$sales_to_show = array ( 0 => SWSales_Sitewide_Sale::get_sitewide_sale( $_REQUEST['sitewide_sale'] ) );
					}
				} else {
					$sales_to_show[0] =  SWSales_Sitewide_Sale::get_active_sitewide_sale();
				}

				if ( ! empty ( $all_sitewide_sales ) ) { ?>
					<form method="get" action="/wp-admin/edit.php">
						<input type="hidden" name="post_type" value="sitewide_sale" />
						<input type="hidden" name="page" value="sitewide_sales_reports" />
						<div class="drops-wrapper">
							<?php 

							for ($i = 0; $i < 2; $i++) {
								$class_modifier = ( $i === 0 ) ? 'left' : 'right';
								$label_wording = ( $i === 0 ) ? 'Show reports for' : 'Vs.';
								
								$selected =  $sales_to_show !== null && array_key_exists( $i, $sales_to_show )  ? $sales_to_show[$i] : null;
							?>
								<label for="swsales_select_report_<?php echo $class_modifier ?>"><?php esc_html_e( $label_wording, 'sitewide-sales' ); ?></label>
								<select id="swsales_select_report_<?php echo $class_modifier ?>"  name="sitewide_sale[]">
									<?php
									if($i == 1) {
									?>
									<option disabled selected value> -- select an option -- </option>
									<?php
									}
									foreach ( $all_sitewide_sales as $sitewide_sale_id ) {
										$sale   = SWSales_Sitewide_Sale::get_sitewide_sale( $sitewide_sale_id );
										$selected_modifier =  $selected != null && $sale === $selected ? 'selected="selected"' : '';
									?>
										<option value="<?php esc_attr_e( $sale->get_id() ); ?>" <?php echo( esc_html( $selected_modifier ) ); ?>>
											<?php echo( esc_html( $sale->get_name() ) ); ?>
										</option>
										<?php
									}
									?>
								</select>
								<?php if ($selected != null) { ?>
									<a target="_blank" href="<?php echo SWSales_Reports::build_CSV_report_link( $selected ); ?>" class="page-title-action pmpro-has-icon pmpro-has-icon-download"><?php esc_html_e( 'Export to CSV', 'sitewide-sales' ); ?></a>
						<?php	}
							}
						?>
						</div>
					</form>
					<script>
					jQuery(document).ready(function($) {
						$('#swsales_select_report_left, #swsales_select_report_right').on('change', evt => {
							$changed = $(evt.target);
							$changed.closest('form').submit();
							});
						});
					</script>
					<hr />
					<?php
				} else { ?>
					<div class="sitewide_sales_message sitewide_sales_alert"><?php printf(__( 'No Sitewide Sales found. <a href="%s">Create your first Sitewide Sale &raquo;</a>', 'sitewide-sales' ), admin_url( 'post-new.php?post_type=sitewide_sale' ) ); ?></div>
					<?php
				}

				// Show report for sitewide sale if applicable.
				if ( $sales_to_show != null && count( $sales_to_show ) > 1) {
					SWSales_Reports::show_compare_report( $sales_to_show );
				} else if ( $sales_to_show != null && count( $sales_to_show ) == 1 ) {
					SWSales_Reports::show_report( $sales_to_show[0] );
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
			$date_array_all = array();
			$period = new \DatePeriod(
				new \DateTime( $sitewide_sale->get_start_date( 'Y-m-d' ) ),
				new \DateInterval('P1D'),
				new \DateTime( $sitewide_sale->get_end_date( 'Y-m-d' ) . ' + 1 day' )
			);
			foreach ($period as $key => $value) {
				$date_array_all[ $value->format('Y-m-d') ] = 0.0;
			}

			/**
			 * Filter the number of days shown in the report chart. Defauly is 31 days.
			 */
			$daily_revenue_chart_days = (int) apply_filters( 'swsales_daily_revenue_chart_days', '31' );
			$date_array = array_slice( $date_array_all, ( $daily_revenue_chart_days * -1 ), $daily_revenue_chart_days, true );

			$daily_revenue_chart_data = apply_filters( 'swsales_daily_revenue_chart_data', 'N/A', $date_array, $sitewide_sale );

			// Get the best day to highlight in the chart.
			$highest_daily_revenue = max( $daily_revenue_chart_data );
			if ( $highest_daily_revenue > 0 ) {
				$highest_daily_revenue_key = array_search( $highest_daily_revenue, $daily_revenue_chart_data );
			}

			// Display the chart.
			if ( is_array( $daily_revenue_chart_data ) ) { ?>
				<hr>
				<div class="swsales_chart_area">
					<div id="chart_div"></div>
					<?php if ( count( $date_array_all ) > $daily_revenue_chart_days ) { ?>
						<div class="swsales_chart_description"><p><center><em>
							<?php esc_html_e( sprintf( __( 'This chart shows the last %s days of sale performance.', 'sitewide-sales' ), $daily_revenue_chart_days ) ); ?>
						</em></center></p></div>
					<?php } ?>
				</div> <!-- end swsales_chart_area -->
				<script>
					// Draw the chart.
					google.charts.load('current', {'packages':['corechart']});
					google.charts.setOnLoadCallback(drawVisualization);
					function drawVisualization() {
						var dataTable = new google.visualization.DataTable();
						dataTable.addColumn('string', <?php echo wp_json_encode( esc_html__( 'DAY', 'sitewide-sales' ) ); ?>);
						dataTable.addColumn('number', <?php echo wp_json_encode( esc_html__( 'Sale Revenue', 'sitewide-sales' ) ); ?>);
						dataTable.addColumn({type: 'string', role: 'style'});
						dataTable.addColumn({type: 'string', role: 'annotation'});
						dataTable.addRows([
							<?php foreach( $daily_revenue_chart_data as $date => $value ) { ?>
								[
									<?php
										echo wp_json_encode( esc_html( date_i18n( get_option('date_format'), strtotime( $date ) ) ) );
									?>,
									<?php echo wp_json_encode( (int) $value ); ?>,
									<?php
										if ( date( 'd.m.Y' ) === date( 'd.m.Y', strtotime( $date ) ) ) {
											echo wp_json_encode( 'color: #5EC16C;' );
										} else {
											echo wp_json_encode( '' );
										}
									?>,
									<?php
										if ( ! empty( $highest_daily_revenue_key ) && $date === $highest_daily_revenue_key ) {
											echo wp_json_encode( esc_html__( 'Best Day', 'sitewide-sales' ) );
										} elseif ( date( 'd.m.Y' ) === date( 'd.m.Y', strtotime( $date ) ) ) {
											echo wp_json_encode( esc_html__( 'Today', 'sitewide-sales' ) );
										} else {
											echo wp_json_encode( '' );
										}
									?>,
								],
							<?php } ?>
						]);
						var options = {
							title: swsales_report_title(),
							titlePosition: 'top',
							titleTextStyle: {
								color: '#555555',
							},
							legend: {position: 'none'},
							colors: ['#31825D'],
							chartArea: {
								width: '90%',
							},
							hAxis: {
								textStyle: {
									color: '#555555',
									fontSize: '12',
									italic: false,
								},
							},
							vAxis: {
								textStyle: {
									color: '#555555',
									fontSize: '12',
									italic: false,
								},
							},
							seriesType: 'bars',
							annotations: {
								alwaysOutside: true,
								stemColor : 'none',
							},
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
						formatter.format(dataTable, 1);

						var chart = new google.visualization.ColumnChart(document.getElementById('chart_div'));
						chart.draw(dataTable, options);
					}

					function swsales_report_title() {
						return <?php echo wp_json_encode( esc_html( sprintf( __( 'Sale Revenue by Day for %s to %s.', 'sitewide-sales' ), $sitewide_sale->get_start_date(), $sitewide_sale->get_end_date() ) ) ); ?>;
					}

				</script>
				<?php
			}
			?>
		</div>
		<?php

		do_action( 'swsales_additional_reports', array( $sitewide_sale ) );
	}

	/**
	 * Given a SWSales_Sitewide_Sale returns an Array with calculated data from it.
	 * 
	 * @param SWSales_Sitewide_Sale $sitewide_sale A  SWSales_Sitewide_Sale.
	 * @return an Array with calculated data from a SWSales_Sitewide_Sale object.
	 * @since TBD.
	 * 
	 */
	public static function build_chart_data($sitewide_sale) {
		// Daily Revenue Chart.
		// Build an array with each day of sale as a key to store revenue data in.
		$date_array_all = array();
		$period = new \DatePeriod(
			new \DateTime( $sitewide_sale->get_start_date( 'Y-m-d' ) ),
			new \DateInterval('P1D'),
			new \DateTime( $sitewide_sale->get_end_date( 'Y-m-d' ) . ' + 1 day' )
		);
		foreach ($period as $key => $value) {
			$date_array_all[ $value->format('Y-m-d') ] = 0.0;
		}

		/**
		* Filter the number of days shown in the report chart. Default is 31 days.
		*/
		$daily_revenue_chart_days = (int) apply_filters( 'swsales_daily_revenue_chart_days', '31' );
		$date_array = array_slice( $date_array_all, ( $daily_revenue_chart_days * -1 ), $daily_revenue_chart_days, true );

		$daily_revenue_chart_data = apply_filters( 'swsales_daily_revenue_chart_data', 'N/A', $date_array, $sitewide_sale );

		// Get the best day to highlight in the chart.
		$highest_daily_revenue = max( $daily_revenue_chart_data );
		$highest_daily_revenue_key = $highest_daily_revenue > 0  ? array_search( $highest_daily_revenue, $daily_revenue_chart_data ) : null;

		return  array ('daily_revenue_chart_data' => $daily_revenue_chart_data,
		'date_array_all' => $date_array_all, 'highest_daily_revenue_key' => $highest_daily_revenue_key,
		'daily_revenue_chart_days' => $daily_revenue_chart_days );
	}

	/**
	 * Show report content for selected Sitewide Sales objects.
	 *
	 * @param Array An array of SWSales_Sitewide_Sale objects to show report for.
	 * @since TBD.
	 */
	public static function show_compare_report( $sitewide_sales ) {
		//Bail if param it's not an array
		if(! is_array( $sitewide_sales ) ) {
			return;
		}

		// Bail if the array comes empty
		if( count( $sitewide_sales ) < 1) {
			return;
		}

		// Bail if given elements aren't SWSales_Sitewide_Sale objects.
		foreach ($sitewide_sales as $sitewide_sale) {
			if ( ! is_a( $sitewide_sale, 'Sitewide_Sales\classes\SWSales_Sitewide_Sale' ) ) {
				return;
			}
		}
			?>
		<div class="swsales_reports-box">
			<table class="reports-comparison-table-above-chart">
				<tr>
					<th>
					</th>
					<th>
					<?php esc_html_e( 'Banner Reach', 'sitewide-sales' ); ?>
					</th>
					<th>
						<?php esc_html_e( 'Landing Page Visits', 'sitewide-sales' ); ?>
					</th>
					<th>
						<?php esc_html_e( 'Checkouts Using [coupon-code]', 'sitewide-sales' ); ?>

					</th>
					<th>
						<?php esc_html_e( 'Revenue', 'sitewide-sales' ); ?>
					</th>
				</tr>
				<tbody>
					
					<?php 
					$ret = [];
					$diff_rate = [];
					if( count( $sitewide_sales ) > 1 ) {
						$diff_rate = self::build_diff_rate_array( $sitewide_sales[0], $sitewide_sales[1] );
					}
					foreach ($sitewide_sales as $key => $sitewide_sale) {
						$should_print_diff = !empty( $diff_rate) && $key == 0;
						?>

						<tr>
							<td class="sale_name">
							<?php echo esc_attr( $sitewide_sale->get_name() ); ?>
							</td>
							<td class="sale_numbers">
								<?php echo esc_attr( $sitewide_sale->get_banner_impressions() );
									if( $should_print_diff ) {
										echo $diff_rate['banner_impressions'];
									}
								?>

							</td>
							<td class="sale_numbers">
								<?php echo esc_attr( $sitewide_sale->get_landing_page_visits() );
								if( $should_print_diff ) {
									echo $diff_rate['landing_page_visits'];
								}
								?>

							</td>
							<td class="sale_numbers">
								<?php echo esc_attr( $sitewide_sale->get_checkout_conversions() );
									if( $should_print_diff ) {
										echo $diff_rate['checkout_conversions'];
									}
								?>
							</td>
							<td class="sale_numbers">
								<?php echo esc_attr( $sitewide_sale->get_revenue() );
								if( $should_print_diff ) {
									echo $diff_rate['revenue'];
								}
								?>
							<td>
						</tr>
					<?php
					$ret[$sitewide_sale->get_id()] = SWSales_Reports::build_chart_data($sitewide_sale);
				} ?>
				</tbody>
			</table>

	<?php
			// Display the chart.
			if ( is_array( $ret ) ) { ?>
				<hr>
					<h3><?php esc_html_e( 'Revenue by Day', 'sitewide-sales' ); ?></h3>
					<div id="chart_div"></div>
				</div> <!-- end swsales_chart_area -->
				<script>
					// Draw the chart.
					google.charts.load('current', {'packages':['corechart']});
					google.charts.setOnLoadCallback(drawVisualization);
					function drawVisualization() {
						const dataTable = new google.visualization.DataTable();
						dataTable.addColumn('string', <?php echo wp_json_encode( esc_html__( 'Sale Day', 'sitewide-sales' ) ); ?>);
						<?php
							foreach ($sitewide_sales as $sitewide_sale) {
						?>
							dataTable.addColumn('number', <?php echo wp_json_encode( esc_html( $sitewide_sale->get_name(), 'sitewide-sales' ) ); ?>);
						<?php } ?>
						dataTable.addColumn({type: 'string', role: 'annotation'});
						dataTable.addRows([
							<?php
									$data = $ret[$sitewide_sales[0]->get_id()];
									$daily_revenue_chart_data = $data['daily_revenue_chart_data'];
									$date_array_all = $data['date_array_all'];
									$highest_daily_revenue_key = $data['highest_daily_revenue_key'];
									$daily_revenue_chart_days = $data['daily_revenue_chart_days'];
									foreach( $daily_revenue_chart_data as $date => $value ) { ?>
									[
										 <?php
											echo wp_json_encode( esc_html( date_i18n( get_option('date_format'), strtotime( $date ) ) ) );
										 ?>,
										 <?php  echo wp_json_encode( (int) $value ); ?>,
										<?php if( count( $sitewide_sales ) > 1 ) {
											foreach( $sitewide_sales as $key => $sitewide_sale ) {
												if( $key === 0 ) {
													continue;
												}
												$revenue_to_compare = isset( $ret[$sitewide_sale->get_id()]['daily_revenue_chart_data'][$date] ) ?
												$ret[$sitewide_sale->get_id()]['daily_revenue_chart_data'][$date] : 0;
													echo wp_json_encode((int) $revenue_to_compare  ) . ',';
											}
										}
										?>
										<?php
											if ( ! empty( $highest_daily_revenue_key ) && $date === $highest_daily_revenue_key ) {
												echo wp_json_encode( esc_html__( 'Best Day', 'sitewide-sales' ) );
											} elseif ( date( 'd.m.Y' ) === date( 'd.m.Y', strtotime( $date ) ) ) {
												echo wp_json_encode( esc_html__( 'Today', 'sitewide-sales' ) );
											} else {
												echo wp_json_encode( '' );
											}
										?>,
									],
						<?php
							}
						?>
						]);
						const options = {
							//If we start supporting more than two we'll need to figure further colors below. Could be random hexa despite that could bring some ugly colors and accessibility issues. 
							colors: ['#DC5D36', '#0D3D54'],
							legend: {
								position: 'top',
								alignment:'center'
							},
							height: 600,
							chartArea: {
								width: '80%',
							},

							hAxis: {
								textStyle: {
									color: '#555555',
									fontSize: '12',
									italic: false,
								},
							},
							vAxis: {
								textStyle: {
									fontSize: '12',
									italic: false,
								},
							},
							seriesType: 'bars',
							annotations: {
								alwaysOutside: true,
								stemColor : 'none',
							},
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
						const formatter = new google.visualization.NumberFormat({
							'<?php echo esc_html( $daily_revenue_chart_currency_format['position'] );?>': '<?php echo esc_html( html_entity_decode( $daily_revenue_chart_currency_format['currency_symbol'] ) ); ?>',
							'decimalSymbol': '<?php echo esc_html( html_entity_decode( $daily_revenue_chart_currency_format['decimal_separator'] ) ); ?>',
							'fractionDigits': <?php echo intval( $daily_revenue_chart_currency_format['decimals'] ); ?>,
							'groupingSymbol': '<?php echo esc_html( html_entity_decode( $daily_revenue_chart_currency_format['thousands_separator'] ) ); ?>',
						});
						<?php
							foreach( $sitewide_sales as $key => $sitewide_sale) {
						?>
								formatter.format(dataTable, <?php echo wp_json_encode( (int) $key ) + 1 ?>);

						<?php }
						?>


						const chart = new google.visualization.ColumnChart(document.getElementById('chart_div'));
						chart.draw(dataTable, options);
					}

					function swsales_report_title() {
						return <?php echo wp_json_encode( esc_html( sprintf( __( 'Revenue by Day', 'sitewide-sales' ) ) ) ); ?>;
					}

				</script>
				<?php
			}
			?>
		<?php
		do_action( 'swsales_additional_reports', $sitewide_sales );
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

	/**
	 * Given 2 SWSales_Sitewide_Sale objects, returns an array with the difference rate between them over several attributes.
	 *
	 * @param SWSales_Sitewide_Sale $sitewide_sale_1 A  SWSales_Sitewide_Sale.
	 * @param SWSales_Sitewide_Sale $sitewide_sale_2 A  SWSales_Sitewide_Sale.
	 * @return an Array with the markup representing difference rate between them over several attributes.
	 * @since TBD.
	 */
	private static function build_diff_rate_array($sitewide_sale_1, $sitewide_sale_2) {
		// No need to validate params, we did it before.
		$diff_rate = [];

		$attributes = [
			'banner_impressions' => 'get_banner_impressions',
			'landing_page_visits' => 'get_landing_page_visits',
			'checkout_conversions' => 'get_checkout_conversions',
			'revenue' => 'get_revenue',
		];

		foreach ($attributes as $key => $method) {
			$value_1 = $sitewide_sale_1->$method();
			$value_2 = $sitewide_sale_2->$method();
			if ($value_1 > $value_2) {
				$diff = $value_2 != 0 ? ( ($value_1 - $value_2) / $value_2 ) * 100 : "-";
				$diff_rate[$key] = self::build_rate_markup($diff, true);
			} else {
				$diff = $value_1 != 0 ?  ( ($value_1 - $value_2 - $value_1) ) * 100 / $value_1 : "-";
				$diff_rate[$key] = self::build_rate_markup($diff, false);
			}
		}

		return $diff_rate;
	}

	/**
	 * Given a rate and a boolean indicating if it's a growth or decline, returns the markup representing the rate.
	 *
	 * @param Obj can be a float or a String $rate The rate to represent.
	 * @param Boolean $is_growth Indicates if the rate is a growth or decline.
	 * @return String with the markup representing the rate.
	 * @since TBD.
	 */
	private static function build_rate_markup( $rate, $is_growth ) {
		$dash_class = $is_growth ? 'dashicons-arrow-up-alt2' : 'dashicons-arrow-down-alt2';
		$span_class = $is_growth ? 'swsales_growth' : 'swsales_decline';
		return '<span class="sale-rate ' . $span_class .'"><span class="dashicons ' . $dash_class . '"></span>(' . $rate . '%)</span>';
	}
}
