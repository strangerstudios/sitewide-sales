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
				$sales_to_show = array();
				if ( isset( $_REQUEST['sitewide_sale'] ) ) {
					if ( ! is_array( $_REQUEST['sitewide_sale'] ) ) {
						$_REQUEST['sitewide_sale'] = array( $_REQUEST['sitewide_sale'] );
					}

					foreach ($_REQUEST['sitewide_sale'] as $key => $value) {
						$sales_to_show[] = SWSales_Sitewide_Sale::get_sitewide_sale( (int)$value );
					}
				}
				if ( empty( $sales_to_show ) && ! empty( SWSales_Sitewide_Sale::get_active_sitewide_sale() ) ) {
					$sales_to_show[] = SWSales_Sitewide_Sale::get_active_sitewide_sale();
				}

				// Show dropdown to choose sale to show.
				if ( ! empty ( $all_sitewide_sales ) ) { ?>
					<form method="get" action="/wp-admin/edit.php">
						<input type="hidden" name="post_type" value="sitewide_sale" />
						<input type="hidden" name="page" value="sitewide_sales_reports" />
						<div class="swsales_reports-filters">
							<?php 

							for ($i = 0; $i < 2; $i++) {
								$class_modifier = ( $i === 0 ) ? 'left' : 'right';
								$label_wording = ( $i === 0 ) ? 'Show reports for' : 'vs.';
								
								$selected =  $sales_to_show !== null && array_key_exists( $i, $sales_to_show )  ? $sales_to_show[$i] : null;
							?>
								<label for="swsales_select_report_<?php echo $class_modifier ?>"><?php esc_html_e( $label_wording, 'sitewide-sales' ); ?></label>
								<select id="swsales_select_report_<?php echo $class_modifier ?>"  name="sitewide_sale[]">
									<?php
									if($i == 1) {
									?>
									<option selected value><?php esc_html_e( '- Choose One -', 'sitewide-sales' ); ?></option>
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
									<a target="_blank" href="<?php echo SWSales_Reports::build_CSV_report_link( $selected ); ?>" class="button button-primary action"><?php esc_html_e( 'Export to CSV', 'sitewide-sales' ); ?></a>
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
					<?php
				} else { ?>
					<div class="sitewide_sales_message sitewide_sales_alert"><?php printf(__( 'No Sitewide Sales found. <a href="%s">Create your first Sitewide Sale &raquo;</a>', 'sitewide-sales' ), admin_url( 'post-new.php?post_type=sitewide_sale' ) ); ?></div>
					<?php
				}

				// Show report for sitewide sale if applicable.
				SWSales_Reports::show_report( $sales_to_show );
			?>
		</div> <!-- sitewide-sales_admin -->
		<?php
	}

	/**
	 * Show report content for selected Sitewide Sales objects.
	 *
	 * @param Array An array of SWSales_Sitewide_Sale objects to show report for.
	 * @since TBD.
	 */
	public static function show_report( $sitewide_sales ) {
		// Bail if the array comes empty
		if ( count( $sitewide_sales ) < 1 ) {
			return;
		}

		// Bail if given elements aren't SWSales_Sitewide_Sale objects.
		foreach ( $sitewide_sales as $sitewide_sale ) {
			if ( ! is_a( $sitewide_sale, 'Sitewide_Sales\classes\SWSales_Sitewide_Sale' ) ) {
				return;
			}
		}
		?>
		<div class="swsales_reports-box">
			<h2 class="swsales_reports-box-title"><?php esc_html_e( 'Overall Sale Performance', 'sitewide-sales' ); ?></h2>
			<table>
				<thead>
					<tr>
						<th></th>
						<th><?php esc_html_e( 'Banner Reach', 'sitewide-sales' ); ?></th>
						<th><?php esc_html_e( 'Landing Page Visits', 'sitewide-sales' ); ?></th>
						<th><?php esc_html_e( 'Conversions', 'sitewide-sales' ); ?></th>
						<th><?php esc_html_e( 'Sale Revenue', 'sitewide-sales' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php
					$daily_chart_data = [];
					$max_sale_days = 0;
					foreach ( $sitewide_sales as $key => $sitewide_sale ) {
						$diff_rate = null;
						/*
						if ( count( $sitewide_sales ) > 1 && $key === 0 ) {
							$diff_rate = self::build_diff_rate_array( $sitewide_sale, $sitewide_sales[1]  );
						}
						*/
					?>
					<tr>
						<th>
							<div class="swsales_reports-sale-value">
								<a href="<?php echo esc_url( add_query_arg( array( 'post' => $sitewide_sale->get_id(), 'action' => 'edit' ), admin_url( 'post.php' ) ) ); ?>"><?php echo esc_attr( $sitewide_sale->get_name() ); ?></a>
							</div>
							<div class="swsales_reports-sale-value-description">
								<?php
									printf(
										wp_kses_post( 'All visitors from %s to %s.', 'sitewide-sales' ),
										esc_html( $sitewide_sale->get_start_date() ),
										esc_html( $sitewide_sale->get_end_date() )
									);
								?>
							</div>
						</th>
						<td>
							<div class="swsales_reports-sale-value">
								<?php
									echo esc_attr( $sitewide_sale->get_banner_impressions() );
									if ( ! empty( $diff_rate ) ) {
										echo $diff_rate['banner_impressions'];
									}
								?>
							</div>
						</td>
						<td>
							<div class="swsales_reports-sale-value">
								<?php
									$landing_page_visits = $sitewide_sale->get_landing_page_visits();
									echo esc_attr( $landing_page_visits );
									if ( ! empty( $diff_rate ) ) {
										echo $diff_rate['landing_page_visits'];
									}
								?>
							</div>
							<?php
								if ( ! empty( $sitewide_sale->get_landing_page_post_id() ) ) {
									echo '<div class="swsales_reports-sale-value-description">';
									echo '<a target="_blank" href="' . get_permalink( $sitewide_sale->get_landing_page_post_id() ) . '" title="' . get_the_title( $sitewide_sale->get_landing_page_post_id() ) . '">';
									echo _n( 'Landing Page Visit', 'Landing Page Visits', $landing_page_visits, 'sitewide-sales' );
									echo '</a>';
									echo '</div>';
								}
							?>
						</td>
						<td>
							<div class="swsales_reports-sale-value">
								<?php
									echo esc_attr( $sitewide_sale->get_checkout_conversions() );
									if ( ! empty( $diff_rate ) ) {
										echo $diff_rate['checkout_conversions'];
									}
								?>
							</div>
							<div class="swsales_reports-sale-value-description">
								<?php
									printf(
										wp_kses_post( apply_filters( 'swsales_checkout_conversions_title', __( 'Checkout Conversions', 'sitewide-sales' ), $sitewide_sale ) )
									);
								?>
							</div>
						</td>
						<td>
							<div class="swsales_reports-sale-value">
								<?php
									echo esc_attr( $sitewide_sale->get_sale_revenue(true) );
									if ( ! empty( $diff_rate ) ) {
										echo $diff_rate['revenue'];
									}
								?>
							</div>
						</td>
					</tr>
					<?php
					// Get daily sale revenue.
					$daily_revenue_chart_data = $sitewide_sale->get_daily_sale_revenue();

					/**
					* Filter the number of days shown in the report chart. Default is 21 days.
					* Since this is the compare report, let's show the first x days instead of the most recent.
					*/
					$daily_revenue_chart_days = (int) apply_filters( 'swsales_daily_revenue_chart_days', '21' );
					if ( count( $daily_revenue_chart_data ) > $daily_revenue_chart_days ) {
						// Slice the array to only show the first x days.
						$daily_revenue_chart_data = array_slice( $daily_revenue_chart_data, 0, $daily_revenue_chart_days, true );
						$data_sliced = true;
					}

					// If this sale has the most days, save the number of days.
					if ( count( $daily_revenue_chart_data ) > $max_sale_days ) {
						$max_sale_days = count( $daily_revenue_chart_data );
					}

					// Save this data to be displayed.
					$daily_chart_data[$sitewide_sale->get_id()] = $daily_revenue_chart_data;
				} ?>
				</tbody>
			</table>
		</div>
		<?php
			// Show a report for the primary sale.
			$primary_sale_array_key = array_key_first( $daily_chart_data );
			$primary_sale_chart_data = $daily_chart_data[$primary_sale_array_key];

			if ( is_array( $daily_chart_data ) ) {
				// We have a comparison sale. Set up an array of sale data without dates.
				$comparison_sale_array_key = array_key_last( $daily_chart_data );
				$comparison_sale_chart_data = array();
				foreach ( $daily_chart_data[$comparison_sale_array_key] as $date => $value ) {
					$comparison_sale_chart_data[] = $value;
				}
			}

			// Get the best day for primary sale to highlight in the chart.
			$highest_daily_revenue = max( $primary_sale_chart_data );
			if ( $highest_daily_revenue > 0 ) {
				$highest_daily_revenue_key = array_search( $highest_daily_revenue, $primary_sale_chart_data );
			}

			// Display the chart.
			if ( is_array( $primary_sale_chart_data ) ) { ?>
				<div class="swsales_reports-box swsales_chart_area">
					<h2><?php esc_html_e( 'Sale Revenue By Day', 'sitewide-sales' ); ?></h2>
					<?php if ( ! empty( $data_sliced ) ) { ?>
						<div class="swsales_chart_description">
							<?php esc_html_e( sprintf( __( 'This chart shows the last %s days of sale performance.', 'sitewide-sales' ), $daily_revenue_chart_days ) ); ?>
						</div>
					<?php } ?>
					<div id="chart_div"></div>
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
						dataTable.addColumn('number', <?php echo wp_json_encode( esc_html__( 'Comparison Sale Revenue', 'sitewide-sales' ) ); ?>);
						dataTable.addRows([
							<?php
								$count_day = 0;
								foreach( $primary_sale_chart_data as $date => $value ) { ?>
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
									<?php
										if ( isset( $comparison_sale_chart_data[$count_day] ) ) {
											echo wp_json_encode( (int) $comparison_sale_chart_data[$count_day] );
										} else {
											echo 0;
										}
									?>,
								],
								<?php
								$count_day++;
								}
							?>
						]);
						var options = {
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
								viewWindow: {
									min: 0,
								},
							},
							viewWindowMode: 'explicit',
							seriesType: 'bars',
							series: {
								1: {
									color: '#999999',
									pointSize: 2,
									pointsVisible: true,
									tooltip: false,
									type: 'line'
								},
							},
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

				</script>
				<?php
			}
		?>
		<div class="swsales_reports-box">
			<h2 class="swsales_reports-box-title"><?php esc_html_e( 'Revenue Breakdown', 'sitewide-sales' ); ?></h2>
			<table>
				<thead>
					<tr>
						<th></th>
						<th><?php esc_html_e( 'Sale Revenue', 'sitewide-sales' ); ?></th>
						<th><?php esc_html_e( 'Other New Revenue', 'sitewide-sales' ); ?></th>
						<th><?php esc_html_e( 'Renewals', 'sitewide-sales' ); ?></th>
						<th><?php esc_html_e( 'Total Revenue in Period', 'sitewide-sales' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $sitewide_sales as $sitewide_sale ) { ?>
					<tr>
						<th>
							<div class="swsales_reports-sale-value">
								<a href="<?php echo esc_url( add_query_arg( array( 'post' => $sitewide_sale->get_id(), 'action' => 'edit' ), admin_url( 'post.php' ) ) ); ?>"><?php echo esc_attr( $sitewide_sale->get_name() ); ?></a>
							</div>
							<div class="swsales_reports-sale-value-description">
								<?php
									printf(
										wp_kses_post( 'All visitors from %s to %s.', 'sitewide-sales' ),
										esc_html( $sitewide_sale->get_start_date() ),
										esc_html( $sitewide_sale->get_end_date() )
									);
								?>
							</div>
						</th>
						<td>
							<div class="swsales_reports-sale-value">
								<?php echo esc_html( $sitewide_sale->get_sale_revenue(true) ); ?>
							</div>
							<?php if ( is_numeric( $sitewide_sale->get_sale_revenue(false) ) ) { ?>
								<div class="swsales_reports-sale-value-description">
									<?php echo( esc_html( '(' . round( ( $sitewide_sale->get_sale_revenue(false) / $sitewide_sale->get_total_revenue(false) ) * 100, 2 ) . '%)' ) ); ?>
								</div>
							<?php } ?>
						</td>
						<td>
							<div class="swsales_reports-sale-value">
								<?php echo esc_html( $sitewide_sale->get_other_revenue(true) ); ?>
							</div>
							<?php if ( is_numeric( $sitewide_sale->get_other_revenue(false) ) ) { ?>
								<div class="swsales_reports-sale-value-description">
									<?php echo esc_html( '(' . round( ( $sitewide_sale->get_other_revenue(false) / $sitewide_sale->get_total_revenue(false) ) * 100, 2 ) . '%)' );
									?>
								</div>
							<?php } ?>
						</td>
						<td>
							<div class="swsales_reports-sale-value">
								<?php echo esc_html( $sitewide_sale->get_renewal_revenue(true) ); ?>
							</div>
							<?php if ( is_numeric( $sitewide_sale->get_renewal_revenue(false) ) ) { ?>
								<div class="swsales_reports-sale-value-description">
									<?php
										echo esc_html( '(' . round( ( $sitewide_sale->get_renewal_revenue(false) / $sitewide_sale->get_total_revenue(false) ) * 100, 2 ) . '%)' );
									?>
								</div>
							<?php } ?>
						</td>
						<td>
							<div class="swsales_reports-sale-value">
								<?php echo esc_html( $sitewide_sale->get_total_revenue(true) ); ?>
							</div>
						</td>
					</tr>
				<?php } ?>
				</tbody>
			</table>
		</div>
		<?php
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
			<span class="swsales_reports-quick-data-value"><?php echo esc_attr( $sitewide_sale->get_sale_revenue(true) ); ?></span>
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
			'revenue' => 'get_sale_revenue',
		];

		foreach ($attributes as $key => $method) {
			$value_1 = (float)$sitewide_sale_1->$method();
			$value_2 = (float)$sitewide_sale_2->$method();

			if ( $value_1 == $value_2 ) {
				// No change.
				$diff_rate[$key] = '';
			} elseif ( empty( $value_2 ) ) {
				// Avoid divide by 0.
				$diff_rate[$key] = self::build_rate_markup( 0, true );
			} elseif ($value_1 > $value_2) {
				// Growth.
				$diff = round( ( ( $value_1 - $value_2 ) / $value_2 ) * 100, 2 );
				$diff_rate[$key] = self::build_rate_markup($diff, true);
			} else {
				// Decline.
				$diff = round( ( ( $value_2 - $value_1 ) / $value_2 ) * 100, 2 );
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
		if ( empty( $rate ) ) {
			return '<span class="sale-rate ' . $span_class .'"><span class="dashicons ' . $dash_class . '">';
		}
		return '<span class="sale-rate ' . $span_class .'"><span class="dashicons ' . $dash_class . '"></span>' . $rate . '%</span>';
	}
}
