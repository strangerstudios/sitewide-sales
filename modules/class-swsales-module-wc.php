<?php

namespace Sitewide_Sales\modules;

use Sitewide_Sales\includes\classes;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

class SWSales_Module_WC {

	/**
	 * Initial plugin setup
	 *
	 * @package sitewide-sale/modules
	 */
	public static function init() {
		// Register sale type.
		add_filter( 'swsales_sale_types', array( __CLASS__, 'register_sale_type' ) );

		// Add fields to Edit Sitewide Sale page.
		add_action( 'swsales_after_choose_sale_type', array( __CLASS__, 'add_choose_coupon' ) );

		// Bail on additional functionality if WC is not active.
		if ( ! class_exists( 'WooCommerce', false ) ) {
			return;
		}

		// Enable saving of fields added above.
		add_action( 'swsales_save_metaboxes', array( __CLASS__, 'save_metaboxes' ), 10, 2 );

		// Enqueue JS for Edit Sitewide Sale page.
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );

		// Generate coupons from editing sitewide sale.
		add_action( 'wp_ajax_swsales_wc_create_coupon', array( __CLASS__, 'create_coupon_ajax' ) );

		// Custom WC banner rules (hide at checkout).
		add_filter( 'swsales_is_checkout_page', array( __CLASS__, 'is_checkout_page' ), 10, 2 );

		// Automatic coupon application.
		add_filter( 'wp', array( __CLASS__, 'automatic_coupon_application' ) );

		// WC-specific reports.
		add_filter( 'swsales_checkout_conversions_title', array( __CLASS__, 'checkout_conversions_title' ), 10, 2 );
		add_filter( 'swsales_get_checkout_conversions', array( __CLASS__, 'checkout_conversions' ), 10, 2 );
		add_filter( 'swsales_get_revenue', array( __CLASS__, 'total_revenue' ), 10, 2 );
	}

	/**
	 * Register WooCommerce module with SWSales
	 *
	 * @param  array $sale_types that are registered in SWSales.
	 * @return array
	 */
	public static function register_sale_type( $sale_types ) {
		$sale_types['wc'] = 'WooCommerce';
		return $sale_types;
	} // end register_sale_type()

	/**
	 * Adds option to choose coupon in Edit Sitewide Sale page.
	 *
	 * @param SWSales_Sitewide_Sale $cur_sale that is being edited.
	 */
	public static function add_choose_coupon( $cur_sale ) {
		?>
		<tr class='swsales-module-row swsales-module-row-wc'>
			<?php if ( ! class_exists( 'WooCommerce', false ) ) { ?>
				<th></th>
				<td><?php _e( 'The WooCommerce plugin is not active.', 'sitewide-sales' ); ?></td>
				<?php
			} else {
				$args = array(
					'posts_per_page'   => -1,
					'orderby'          => 'title',
					'order'            => 'asc',
					'post_type'        => 'shop_coupon',
					'post_status'      => 'publish',
				);

				$coupons = get_posts( $args );
				$current_coupon = intval( $cur_sale->get_meta_value( 'swsales_wc_coupon_id', null ) );
				?>
					<th><label for="swsales_wc_coupon_id"><?php esc_html_e( 'Coupon', 'sitewide-sales' );?></label></th>
					<td>
						<select class="coupon_select swsales_option" id="swsales_wc_coupon_select" name="swsales_wc_coupon_id">
							<option value="0"><?php esc_html_e( '- Choose One -', 'sitewide-sales' ); ?></option>
							<?php
							$coupon_found = false;
							foreach ( $coupons as $coupon ) {
								$selected_modifier = '';
								if ( $coupon->ID === $current_coupon ) {
									$selected_modifier = ' selected="selected"';
									$coupon_found        = true;
								}
								echo '<option value="' . esc_attr( $coupon->ID ) . '"' . $selected_modifier . '>' . esc_html( $coupon->post_title ) . '</option>';
							}
							?>
						</select>
						<p>
							<span id="swsales_wc_after_coupon_select">
							<?php
							if ( $coupon_found ) {
								$edit_coupon_url = get_edit_post_link( $coupon->ID );
							} else {
								$edit_coupon_url = '#';
							}
							?>
								<a target="_blank" class="button button-secondary" id="swsales_wc_edit_coupon" href="<?php echo esc_url( $edit_coupon_url ); ?>"><?php esc_html_e( 'edit coupon', 'sitewide-sales' ); ?></a>
								<?php
								esc_html_e( ' or ', 'sitewide-sales' );
								?>
							</span>
							<button type="button" id="swsales_wc_create_coupon" class="button button-secondary"><?php esc_html_e( 'create a new coupon', 'sitewide-sales' ); ?></button>
							<p><small><?php esc_html_e( 'Select the coupon that will be automatically applied for users when they visit your Landing Page.', 'sitewide-sales' ); ?></small></p>
						</p>
					</td>
				<?php } ?>
				</tr>
		<?php
	} // end add_choose_coupon()

	/**
	 * Saves WC module fields when saving Sitewide Sale.
	 *
	 * @param int     $post_id of the sitewide sale being edited.
	 * @param WP_Post $post object of the sitewide sale being edited.
	 */
	public static function save_metaboxes( $post_id, $post ) {
		if ( isset( $_POST['swsales_wc_coupon_id'] ) ) {
			update_post_meta( $post_id, 'swsales_wc_coupon_id', intval( $_POST['swsales_wc_coupon_id'] ) );
		}
	}

	/**
	 * Enqueues /modules/js/swsales-module-wc-metaboxes.js
	 */
	public static function enqueue_scripts() {
		global $wpdb, $typenow;
		if ( 'sitewide_sale' === $typenow ) {
			wp_register_script( 'swsales_module_wc_metaboxes', plugins_url( 'modules/js/swsales-module-wc-metaboxes.js', SWSALES_BASENAME ), array( 'jquery' ), '1.0.4' );
			wp_enqueue_script( 'swsales_module_wc_metaboxes' );

			wp_localize_script(
				'swsales_module_wc_metaboxes',
				'swsales',
				array(
					'create_coupon_nonce' => wp_create_nonce( 'swsales_wc_create_coupon' ),
					'admin_url'           => admin_url(),
				)
			);

		}
	} // end enqueue_scripts()

	/**
	 * AJAX callback to create a new coupon for your sale
	 */
	public static function create_coupon_ajax() {
		global $wpdb;
		check_ajax_referer( 'swsales_wc_create_coupon', 'nonce' );

		$sitewide_sale_id = intval( $_REQUEST['swsales_wc_id'] );
		if ( empty( $sitewide_sale_id ) ) {
			echo json_encode(
				array(
					'status' => 'error',
					'error'  => esc_html__(
						'No sitewide sale ID given. Try doing it manually.',
						'sitewide-sales'
					),
				)
			);
			exit;
		}

		/**
		 * Create a coupon programatically
		 */
		global $wpdb;
		while ( empty( $coupon_code ) ) {
			$scramble = md5( AUTH_KEY . current_time( 'timestamp' ) . SECURE_AUTH_KEY );
			$coupon_code = strtoupper( substr( $scramble, 0, 10 ) );
			if ( get_page_by_title( $coupon_code, OBJECT, 'shop_coupon' ) !== null || is_numeric( $coupon_code ) ) {
				$coupon_code = null;
			}
		}
		$amount        = '50'; // Amount.
		$discount_type = 'percent'; // Type: fixed_cart, percent, fixed_product, percent_product.

		$coupon = array(
			'post_title'   => $coupon_code,
			'post_content' => '',
			'post_status'  => 'publish',
			'post_author'  => 1,
			'post_type'    => 'shop_coupon',
		);

		$new_coupon_id = wp_insert_post( $coupon );

		// Add meta.
		update_post_meta( $new_coupon_id, 'discount_type', $discount_type );
		update_post_meta( $new_coupon_id, 'coupon_amount', $amount );
		update_post_meta( $new_coupon_id, 'individual_use', 'no' );
		update_post_meta( $new_coupon_id, 'product_ids', '' );
		update_post_meta( $new_coupon_id, 'exclude_product_ids', '' );
		update_post_meta( $new_coupon_id, 'usage_limit', '' );
		update_post_meta( $new_coupon_id, 'expiry_date', '' );
		update_post_meta( $new_coupon_id, 'apply_before_tax', 'yes' );
		update_post_meta( $new_coupon_id, 'free_shipping', 'no' );

		$r = array(
			'status'      => 'success',
			'coupon_id'   => $new_coupon_id,
			'coupon_code' => $coupon_code,
		);
		echo wp_json_encode( $r );
		exit;
	} // end create_discount_code_ajax()

	/**
	 * Returns whether the current page is the landing page
	 * for the passed Sitewide Sale.
	 *
	 * @param boolean               $is_checkout_page current value from filter.
	 * @param SWSales_Sitewide_Sale $sitewide_sale being checked.
	 * @return boolean
	 */
	public static function is_checkout_page( $is_checkout_page, $sitewide_sale ) {
		if ( 'wc' !== $sitewide_sale->get_sale_type() ) {
			return $is_checkout_page;
		}
		return is_page( wc_get_page_id( 'cart' ) ) ? true : $is_checkout_page;
	}

	public static function automatic_coupon_application() {
		$active_sitewide_sale = classes\SWSales_Sitewide_Sale::get_active_sitewide_sale();
		if ( 'wc' !== $active_sitewide_sale->get_sale_type() ) {
			return;
		}
		$cookie_name = 'swsales_' . $active_sitewide_sale->get_id() . '_tracking';
		if (
			is_page( $active_sitewide_sale->get_landing_page_post_id() ) ||
			( isset( $_COOKIE[ $cookie_name ] ) &&
			false !== strpos( $_COOKIE[ $cookie_name ], ';1' ) )
		) {
			$cart = WC()->cart;
			$coupon = new \WC_Coupon( $active_sitewide_sale->get_meta_value( 'swsales_wc_coupon_id', null ) );
			if ( ! $cart->has_discount( $coupon->get_code() ) && $coupon->is_valid() ) {
				$cart->apply_coupon( $coupon->get_code() );
			}
		}
	}

	/**
	 * Set WC module checkout conversion title for Sitewide Sale report.
	 *
	 * @param string               $cur_title     set by filter.
	 * @param SWSale_Sitewide_Sale $sitewide_sale to generate report for.
	 * @return string
	 */
	public static function checkout_conversions_title( $cur_title, $sitewide_sale ) {
		if ( 'wc' !== $sitewide_sale->get_sale_type() ) {
			return $cur_title;
		}
		$coupon_id = $sitewide_sale->get_meta_value( 'swsales_wc_coupon_id', null );
		$coupon_code = wc_get_coupon_code_by_id( $coupon_id );

		if ( null === $coupon_id || empty( $coupon_code ) ) {
			return $cur_title;
		}

		return sprintf(
			__( 'Purchases using <a href="%s">%s</a>', 'sitewide-sales' ),
			get_edit_post_link( $coupon_id ),
			$coupon_code
		);
	}

	/**
	 * Set WC module checkout conversions for Sitewide Sale report.
	 *
	 * @param string               $cur_conversions set by filter.
	 * @param SWSale_Sitewide_Sale $sitewide_sale to generate report for.
	 * @return string
	 */
	public static function checkout_conversions( $cur_conversions, $sitewide_sale ) {
		if ( 'wc' !== $sitewide_sale->get_sale_type() ) {
			return $cur_conversions;
		}

		$coupon_id   = $sitewide_sale->get_meta_value( 'swsales_wc_coupon_id', null );
		$coupon_code = wc_get_coupon_code_by_id( $coupon_id );

		$orders = wc_get_orders(
			array(
				'date_paid' => $sitewide_sale->get_start_date( 'Y-m-d' ) . '...' . $sitewide_sale->get_end_date( 'Y-m-d' ),
			)
		);

		$conversion_count = 0;
		foreach ( $orders as $order ) {
			foreach ( $order->get_used_coupons() as $order_coupon_code ) {
				if ( strtoupper( $coupon_code ) === strtoupper( $order_coupon_code ) ) {
					$conversion_count++;
				}
			}
		}

		return strval( $conversion_count );
	}

	/**
	 * Set WC module total revenue for Sitewide Sale report.
	 *
	 * @param string               $cur_revenue set by filter.
	 * @param SWSale_Sitewide_Sale $sitewide_sale to generate report for.
	 * @return string
	 */
	public static function total_revenue( $cur_revenue, $sitewide_sale ) {
		if ( 'wc' !== $sitewide_sale->get_sale_type() ) {
			return $cur_conversions;
		}

		$coupon_id   = $sitewide_sale->get_meta_value( 'swsales_wc_coupon_id', null );
		$coupon_code = wc_get_coupon_code_by_id( $coupon_id );

		$orders = wc_get_orders(
			array(
				'date_paid' => $sitewide_sale->get_start_date( 'Y-m-d' ) . '...' . $sitewide_sale->get_end_date( 'Y-m-d' ),
			)
		);

		$total_revenue = 0.00;
		foreach ( $orders as $order ) {
			foreach ( $order->get_used_coupons() as $order_coupon_code ) {
				if ( strtoupper( $coupon_code ) === strtoupper( $order_coupon_code ) ) {
					$total_revenue += $order->total;
				}
			}
		}

		return wp_strip_all_tags( wc_price( $total_revenue ) );
	}
}
SWSales_Module_WC::init();
