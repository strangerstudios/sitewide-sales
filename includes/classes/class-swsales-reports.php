<?php

namespace Sitewide_Sales\includes\classes;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

class SWSales_Reports {

	/**
	 * Adds actions for class
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_reports_page' ) );
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

	public static function show_reports_page() {
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
		?>
			<form method="get" action="/wp-admin/edit.php?post_type=sitewide_sale&page=sitewide_sales_reports">
				<input type="hidden" name="post_type" value="sitewide_sale" />
				<input type="hidden" name="page" value="sitewide_sale_reports" />
				<label for="sitewide_sale">Choose a Sitewide Sale to show reports for:</label>
				<select id="swsales_select_report" name="sitewide_sale" onchange="this.form.submit()">
					<option value=""></option>
					<?php
					foreach ( $all_sitewide_sales as $sitewide_sale_id ) {
							$sale              = SWSales_Sitewide_Sale::get_sitewide_sale( $sitewide_sale_id );
							$selected_modifier = ( ! ( null === $sale_to_show ) && $sale->get_id() === $sale_to_show->get_id() ) ? 'selected="selected"' : '';
						?>
							<option value="<?php echo( esc_html( $sale->get_id() ) ); ?>" <?php echo( esc_html( $selected_modifier ) ); ?>>
								<?php echo( esc_html( $sale->get_name() ) ); ?>
							</option>
							<?php
					}
					?>
				</select>
			</form>
		<?php

		// Show report for sitewide sale if applicable.
		if ( null !== $sale_to_show ) {
			$sale_to_show->show_report();
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

		wp_register_script( 'swsales_tracking', plugins_url( 'js/swsales-tracking.js', SWSALES_BASENAME ), array( 'jquery', 'utils' ) );

		$swsales_data = array(
			'landing_page'      => is_page( $active_sitewide_sale->get_landing_page_post_id() ),
			'sitewide_sale_id'  => $active_sitewide_sale->get_id(),
			'ajax_url'          => admin_url( 'admin-ajax.php' ),
		);

		wp_localize_script( 'swsales_tracking', 'swsales', $swsales_data );
		wp_enqueue_script( 'swsales_tracking' );
	}

	/**
	 * Ajax call to update SWS statistics
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
