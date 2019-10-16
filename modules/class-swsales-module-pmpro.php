<?php

namespace Sitewide_Sales\modules;

use Sitewide_Sales\includes\classes;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

class SWSales_Module_PMPro {

	/**
	 * Initial plugin setup
	 *
	 * @package sitewide-sale/modules
	 */
	public static function init() {
		// Register sale type.
		add_filter( 'swsales_sale_types', array( __CLASS__, 'register_sale_type' ) );

		// Add fields to Edit Sitewide Sale page.
		add_action( 'swsales_after_choose_sale_type', array( __CLASS__, 'add_choose_discount_code' ) );
		add_action( 'swsales_after_choose_landing_page', array( __CLASS__, 'add_set_landing_page_default_level' ) );
		add_action( 'swsales_after_banners_settings', array( __CLASS__, 'add_hide_banner_by_level' ) );

		// Bail on additional functionality if PMPro is not active.
		if ( ! defined( 'PMPRO_VERSION' ) ) {
			return;
		}

		// Enable saving of fields added above.
		add_action( 'swsales_save_metaboxes', array( __CLASS__, 'save_metaboxes' ), 10, 2 );

		// Enqueue JS for Edit Sitewide Sale page.
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );

		// SWSale compatibility when editing/saving a discount code.
		//add_action( 'admin_notices', array( __CLASS__, 'return_from_editing_discount_code_box' ) );
		//add_action( 'pmpro_save_discount_code', array( __CLASS__, 'discount_code_on_save' ) );
		add_action( 'wp_ajax_swsales_pmpro_create_discount_code', array( __CLASS__, 'create_discount_code_ajax' ) );

		// Default level for sale page.
		add_action( 'wp', array( __CLASS__, 'load_pmpro_preheader' ), 0 ); // Priority 0 so that the discount code applies.

		// Custom PMPro banner rules (hide for levels and hide at checkout).
		add_filter( 'swsales_is_checkout_page', array( __CLASS__, 'is_checkout_page' ), 10, 2 );
		add_filter( 'swsales_show_banner', array( __CLASS__, 'show_banner' ), 10, 2 );

		// PMPro automatic discount application.
		add_action( 'init', array( __CLASS__, 'automatic_discount_application' ) );

		// PMPro-specific reports.
		add_filter( 'swsales_checkout_conversions_title', array( __CLASS__, 'checkout_conversions_title' ), 10, 2 );
		add_filter( 'swsales_get_checkout_conversions', array( __CLASS__, 'checkout_conversions' ), 10, 2 );
		add_filter( 'swsales_get_revenue', array( __CLASS__, 'total_revenue' ), 10, 2 );
		add_action( 'swsales_additional_reports', array( __CLASS__, 'additional_report' ) );

	}

	/**
	 * Register PMPro module with SWSales
	 *
	 * @param  array $sale_types that are registered in SWSales.
	 * @return array
	 */
	public static function register_sale_type( $sale_types ) {
		$sale_types['pmpro'] = 'Paid Memberships Pro';
		return $sale_types;
	} // end register_sale_type()

	/**
	 * Adds option to choose discount code in Edit Sitewide Sale page.
	 *
	 * @param SWSales_Sitewide_Sale $cur_sale that is being edited.
	 */
	public static function add_choose_discount_code( $cur_sale ) {
		?>
		<tr class='swsales-module-row swsales-module-row-pmpro'>
			<?php if ( ! defined( 'PMPRO_VERSION' ) ) { ?>
				<th></th>
				<td><?php _e( 'The Paid Memberships Pro plugin is not active.', 'sitewide-sales' ); ?></td>

				<?php
			} else {
				global $wpdb;
				$codes            = $wpdb->get_results( "SELECT * FROM $wpdb->pmpro_discount_codes", OBJECT );
				$current_discount = $cur_sale->get_meta_value( 'swsales_pmpro_discount_code_id', null );
				?>
					<th><label for="swsales_pmpro_discount_code_id"><?php esc_html_e( 'Discount Code', 'sitewide-sales' );?></label></th>
					<td>
						<select class="discount_code_select swsales_option" id="swsales_pmpro_discount_code_select" name="swsales_pmpro_discount_code_id">
							<option value="0"><?php esc_html_e( '- Choose One -', 'sitewide-sales' ); ?></option>
							<?php
							$code_found = false;
							foreach ( $codes as $code ) {
								$selected_modifier = '';
								if ( $code->id === $current_discount ) {
									$selected_modifier = ' selected="selected"';
									$code_found        = true;
								}
								echo '<option value="' . esc_attr( $code->id ) . '"' . $selected_modifier . '>' . esc_html( $code->code ) . '</option>';
							}
							?>
						</select>
						<p>
							<span id="swsales_pmpro_after_discount_code_select">
							<?php
							if ( $code_found ) {
								$edit_code_url = admin_url( 'admin.php?page=pmpro-discountcodes&edit=' . $current_discount );
							} else {
								$edit_code_url = '#';
							}
							?>
								<a target="_blank" class="button button-secondary" id="swsales_pmpro_edit_discount_code" href="<?php echo esc_url( $edit_code_url ); ?>"><?php esc_html_e( 'edit code', 'sitewide-sales' ); ?></a>
								<?php
								esc_html_e( ' or ', 'sitewide-sales' );
							?>
							</span>
							<button type="button" id="swsales_pmpro_create_discount_code" class="button button-secondary"><?php esc_html_e( 'create a new discount code', 'sitewide-sales' ); ?></button>
							<p><small class="pmpro_lite"><?php esc_html_e( 'Select the code that will be automatically applied for users that complete an applicable membership checkout after visiting your Landing Page.', 'sitewide-sales' ); ?></small></p>
						</p>
					</td>
				<?php } ?>
				</tr>
		<?php
	} // end add_choose_discount_code()

	/**
	 * Adds option to choose the default level for checkout on SWSale
	 * landing page in Edit Sitewide Sale page.
	 *
	 * @param SWSales_Sitewide_Sale $cur_sale that is being edited.
	 */
	public static function add_set_landing_page_default_level( $cur_sale ) {
		?>
		<tr class='swsales-module-row swsales-module-row-pmpro'>
			<?php if ( ! defined( 'PMPRO_VERSION' ) ) { ?>
				<th></th>
				<td><?php _e( 'The Paid Memberships Pro plugin is not active.', 'sitewide-sales' ); ?></td>
				<?php
			} else {
				?>
				<th><label for="swsales_pmpro_landing_page_default_level"><?php esc_html_e( 'Checkout Level', 'sitewide-sales' ); ?></label></th>
				<td>
					<select id="swsales_pmpro_landing_page_default_level" name="swsales_pmpro_landing_page_default_level">
					<option value="0"><?php esc_html_e( '- Choose One -', 'sitewide-sales' ); ?></option>
					<?php
						$all_levels = pmpro_getAllLevels( true, true );
						$default_level = $cur_sale->get_meta_value( 'swsales_pmpro_landing_page_default_level', null );
					foreach ( $all_levels as $level ) {
						?>
						<option value="<?php echo esc_attr( $level->id ); ?>" <?php selected( $default_level, $level->id ); ?>><?php echo esc_textarea( $level->name ); ?></option>
						<?php
					}
					?>
				</select>
				<p><small class="pmpro_lite"><?php esc_html_e( 'Using the [pmpro_checkout] shortcode on your Landing Page will display a checkout form for this level.', 'sitewide-sales' ); ?></small></p>
				</td>
				<?php } ?>
				</tr>
		<?php
	} // end add_set_landing_page_default_level

	/**
	 * Adds option to hide banners for users who have certain levels
	 * in Edit Sitewide Sale page.
	 *
	 * @param SWSales_Sitewide_Sale $cur_sale that is being edited.
	 */
	public static function add_hide_banner_by_level( $cur_sale ) {
		?>
		<tr class='swsales-module-row swsales-module-row-pmpro'>
			<?php if ( ! defined( 'PMPRO_VERSION' ) ) { ?>
				<th></th>
				<td><?php _e( 'The Paid Memberships Pro plugin is not active.', 'sitewide-sales' ); ?></td>
				<?php
			} else {
				?>
				<th scope="row" valign="top"><label><?php esc_html_e( 'Hide Banner by Membership Level', 'sitewide-sales' ); ?></label></th>
					<td>
						<input type="hidden" name="swsales_pmpro_hide_for_levels_exists" value="1" />
						<select multiple class="swsales_option" id="swsales_pmpro_hide_levels_select" name="swsales_pmpro_hide_for_levels[]" style="width:12em">
						<?php
							$all_levels = pmpro_getAllLevels( true, true );
							$hide_for_levels = json_decode( $cur_sale->get_meta_value( 'swsales_pmpro_hide_for_levels', array() ) );
						foreach ( $all_levels as $level ) {
							$selected_modifier = in_array( $level->id, $hide_for_levels ) ? ' selected="selected"' : '';
							echo '<option value="' . esc_attr( $level->id ) . '"' . $selected_modifier . '>' . esc_html( $level->name ) . '</option>';
						}
						?>
						</select>
						<p><small class="pmpro_lite"><?php esc_html_e( 'This setting will hide the banner for members of the selected levels.', 'sitewide-sales' ); ?></small></p>
					</td>
					<?php
			}
			?>
		</tr>
		<?php
	}

	/**
	 * Saves PMPro module fields when saving Sitewide Sale.
	 *
	 * @param int     $post_id of the sitewide sale being edited.
	 * @param WP_Post $post object of the sitewide sale being edited.
	 */
	public static function save_metaboxes( $post_id, $post ) {
		if ( isset( $_POST['swsales_pmpro_discount_code_id'] ) ) {
			update_post_meta( $post_id, 'swsales_pmpro_discount_code_id', intval( $_POST['swsales_pmpro_discount_code_id'] ) );
		}
		if ( isset( $_POST['swsales_pmpro_landing_page_default_level'] ) ) {
			update_post_meta( $post_id, 'swsales_pmpro_landing_page_default_level', intval( $_POST['swsales_pmpro_landing_page_default_level'] ) );
		}

		if ( ! empty( $_POST['swsales_pmpro_hide_for_levels'] ) && is_array( $_POST['swsales_pmpro_hide_for_levels'] ) ) {
			$swsales_pmpro_hide_for_levels = array_map( 'intval', $_POST['swsales_pmpro_hide_for_levels'] );
			update_post_meta( $post_id, 'swsales_pmpro_hide_for_levels', wp_json_encode( $swsales_pmpro_hide_for_levels ) );
		} else {
			update_post_meta( $post_id, 'swsales_pmpro_hide_for_levels', wp_json_encode( array() ) );
		}
	}

	/**
	 * Enqueues /modules/js/swsales-module-pmpro-metaboxes.js
	 */
	public static function enqueue_scripts() {
		global $wpdb, $typenow;
		if ( 'sitewide_sale' === $typenow ) {
			wp_register_script( 'swsales_module_pmpro_metaboxes', plugins_url( 'modules/js/swsales-module-pmpro-metaboxes.js', SWSALES_BASENAME ), array( 'jquery' ), '1.0.4' );
			wp_enqueue_script( 'swsales_module_pmpro_metaboxes' );

			wp_localize_script(
				'swsales_module_pmpro_metaboxes',
				'swsales',
				array(
					'create_discount_code_nonce' => wp_create_nonce( 'swsales_pmpro_create_discount_code' ),
					'admin_url'                  => admin_url(),
				)
			);

		}
	} // end enqueue_scripts()

	/**
	 * COMMENTED OUT
	 * Updates Sitewide Sale's discount code id on save
	 *
	 * @param int $saveid discount code being saved.
	 */
	public static function discount_code_on_save( $saveid ) {
		if ( isset( $_REQUEST['swsales_pmpro_callback'] ) ) {
			update_post_meta( intval( $_REQUEST['swsales_pmpro_callback'] ), 'swsales_pmpro_discount_code_id', $saveid );
			?>
			<script type="text/javascript">
				window.location = "<?php echo esc_url( admin_url( 'post.php?post=' . intval( $_REQUEST['swsales_pmpro_callback'] ) . '&action=edit' ) ); ?>";
			</script>
			<?php
		}
	} // end discount_code_on_save()

	/**
	 * COMMENTED OUT
	 * Displays a link back to Sitewide Sale when discount code is edited/saved
	 */
	public static function return_from_editing_discount_code_box() {
		if ( isset( $_REQUEST['swsales_pmpro_callback'] ) && 'memberships_page_pmpro-discountcodes' === get_current_screen()->base ) {
			?>
			<div class="notice notice-success">
				<p><?php esc_html_e( 'Click ', 'sitewide-sales' ); ?>
					<a href="<?php echo esc_url( admin_url( 'post.php?post=' . intval( $_REQUEST['swsales_pmpro_callback'] ) . '&action=edit' ) ); ?>">
						<?php esc_html_e( 'here', 'sitewide-sales' ); ?>
					</a>
					<?php esc_html_e( ' to go back to editing Sitewide Sale', 'sitewide-sales' ); ?>
				</p>
			</div>
			<?php
		}
	} // end return_from_editing_discount_code_box()

	/**
	 * AJAX callback to create a new discount code for your sale
	 */
	public static function create_discount_code_ajax() {
		global $wpdb;
		check_ajax_referer( 'swsales_pmpro_create_discount_code', 'nonce' );
		if ( ! function_exists( 'pmpro_getDiscountCode' ) ) {
			exit;
		}
		$sitewide_sale_id = intval( $_REQUEST['swsales_pmpro_id'] );
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
		$wpdb->insert(
			$wpdb->pmpro_discount_codes,
			array(
				'id'      => 0,
				'code'    => pmpro_getDiscountCode(),
				'starts'  => sanitize_text_field( $_REQUEST['swsales_start'] ),
				'expires' => sanitize_text_field( $_REQUEST['swsales_end'] ),
				'uses'    => 0,
			),
			array(
				'%d',
				'%s',
				'%s',
				'%s',
				'%d',
			)
		);
		if ( ! empty( $wpdb->last_error ) ) {
			$r = array(
				'status' => 'error',
				'error'  => esc_html__( 'Error inserting discount code. Try doing it manually.', 'sitewide-sales' ),
			);
		} else {
			$discount_code = $wpdb->get_row( "SELECT * FROM $wpdb->pmpro_discount_codes WHERE id = '" . intval( $wpdb->insert_id ) . "' LIMIT 1" );
			$r             = array(
				'status' => 'success',
				'code'   => $discount_code,
			);
		}
		echo json_encode( $r );
		exit;
	} // end create_discount_code_ajax()

	/**
	 * Get the default level to use on a landing page
	 *
	 * @param int $post_id Post ID of the landing page.
	 */
	public static function get_default_level( $post_id = null ) {
		global $post, $wpdb;

		// Guess.
		$all_levels = pmpro_getAllLevels( true, true );
		if ( ! empty( $all_levels ) ) {
			$keys     = array_keys( $all_levels );
			$level_id = $keys[0];
		} else {
			return false;
		}
		// Default post_id.
		if ( empty( $post_id ) ) {
			$post_id = $post->ID;
		}
		// Must have a post_id.
		if ( empty( $post_id ) ) {
			return $level_id;
		}
		// Get sale for this $post_id.
		$sitewide_sale = classes\SWSales_Sitewide_Sale::get_sitewide_sale_for_landing_page( $post_id );
		if ( null !== $sitewide_sale ) {
			// Check for setting.
			$default_level_id = $sitewide_sale->get_meta_value( 'swsales_pmpro_landing_page_default_level' );
			// No default set? get the discount code for this sale.
			if ( ! empty( $default_level_id ) ) {
				// Use the setting.
				$level_id = $default_level_id;
			} else {
				// Check for discount code.
				$discount_code_id = $sitewide_sale->get_meta_value( 'swsales_pmpro_discount_code_id' );
				// Get first level that uses this code.
				if ( ! empty( $discount_code_id ) ) {
					$first_code_level_id = $wpdb->get_var( "SELECT level_id FROM $wpdb->pmpro_discount_codes_levels WHERE code_id = '" . esc_sql( $discount_code_id ) . "' ORDER BY level_id LIMIT 1" );
					if ( ! empty( $first_code_level_id ) ) {
						$level_id = $first_code_level_id;
					}
				}
			}
		}
		return $level_id;
	}

	/**
	 * Load the checkout preheader on the landing page.
	 */
	public static function load_pmpro_preheader() {
		global $wpdb;
		// Make sure PMPro is loaded.
		if ( ! defined( 'PMPRO_DIR' ) ) {
			return;
		}
		// Don't do this in the dashboard.
		if ( is_admin() ) {
			return;
		}
		// Check if this is the landing page.
		$queried_object = get_queried_object();
		if ( empty( $queried_object ) || empty( $queried_object->ID ) || null === classes\SWSales_Sitewide_Sale::get_sitewide_sale_for_landing_page( $queried_object->ID ) ) {
			return;
		}

		// Choose a default level if none specified.
		if ( empty( $_REQUEST['level'] ) ) {
			$_REQUEST['level'] = self::get_default_level( $queried_object->ID );
		}
		// Set the discount code if none specified.
		if ( empty( $_REQUEST['discount_code'] ) ) {
			$sitewide_sale             = classes\SWSales_Sitewide_Sale::get_sitewide_sale_for_landing_page( $queried_object->ID );
			$discount_code_id          = $sitewide_sale->get_meta_value( 'swsales_pmpro_discount_code_id' );
			$_REQUEST['discount_code'] = $wpdb->get_var( $wpdb->prepare( "SELECT code FROM $wpdb->pmpro_discount_codes WHERE id=%d LIMIT 1", $discount_code_id ) );
		}

		if ( ! has_shortcode( $queried_object->post_content, 'swsales' ) ) {
			return;
		}
		require_once PMPRO_DIR . '/preheaders/checkout.php';
	}

	/**
	 * Returns whether the current page is the landing page
	 * for the passed Sitewide Sale.
	 *
	 * @param boolean               $is_checkout_page current value from filter.
	 * @param SWSales_Sitewide_Sale $sitewide_sale being checked.
	 * @return boolean
	 */
	public static function is_checkout_page( $is_checkout_page, $sitewide_sale ) {
		if ( 'pmpro' !== $sitewide_sale->get_sale_type() ) {
			return $is_checkout_page;
		}
		global $pmpro_pages;
		return is_page( $pmpro_pages['checkout'] ) ? true : $is_checkout_page;
	}

	/**
	 * Returns whether the banner should be shown for the current Sitewide Sale.
	 *
	 * @param boolean               $show_banner current value from filter.
	 * @param SWSales_Sitewide_Sale $sitewide_sale being checked.
	 * @return boolean
	 */
	public static function show_banner( $show_banner, $sitewide_sale ) {
		if ( 'pmpro' !== $sitewide_sale->get_sale_type() ) {
			return $show_banner;
		}
		// Hide for users with membership in $hide_for_levels.
		$hide_for_levels  = json_decode( $sitewide_sale->get_meta_value( 'swsales_pmpro_hide_for_levels', array() ) );
		$membership_level = pmpro_getMembershipLevelForUser();
		if ( ! empty( $hide_for_levels ) && ! empty( $membership_level )
			&& in_array( $membership_level->ID, $hide_for_levels ) ) {
			return false;
		}
		return $show_banner;
	}

	/**
	 * Automatically applies discount code if user has the cookie set from sale page
	 */
	public static function automatic_discount_application() {
		$active_sitewide_sale = classes\SWSales_Sitewide_Sale::get_active_sitewide_sale();
		if ( 'pmpro' !== $active_sitewide_sale->get_sale_type() ) {
			return;
		}
		global $wpdb, $pmpro_pages;
		if ( empty( $_REQUEST['level'] ) || ! empty( $_REQUEST['discount_code'] ) ) {
			return;
		}
		$discount_code_id = $active_sitewide_sale->get_meta_value( 'swsales_pmpro_discount_code_id', null );
		if ( null === $discount_code_id || ! $active_sitewide_sale->is_running() ) {
			return;
		}
		$cookie_name = 'swsales_' . $active_sitewide_sale->get_id() . '_tracking';
		if ( ! isset( $_COOKIE[ $cookie_name ] ) || false == strpos( $_COOKIE[ $cookie_name ], ';1' ) ) {
			return;
		}
		$_REQUEST['discount_code'] = $wpdb->get_var( $wpdb->prepare( "SELECT code FROM $wpdb->pmpro_discount_codes WHERE id=%d LIMIT 1", $discount_code_id ) );
	}

	/**
	 * Set PMPro module checkout conversion title for Sitewide Sale report.
	 *
	 * @param string               $cur_title     set by filter.
	 * @param SWSale_Sitewide_Sale $sitewide_sale to generate report for.
	 * @return string
	 */
	public static function checkout_conversions_title( $cur_title, $sitewide_sale ) {
		if ( 'pmpro' !== $sitewide_sale->get_sale_type() ) {
			return $cur_title;
		}
		global $wpdb;
		$discount_code_id = $sitewide_sale->get_meta_value( 'swsales_pmpro_discount_code_id', null );
		$discount_code = $wpdb->get_var( $wpdb->prepare( "SELECT code FROM $wpdb->pmpro_discount_codes WHERE id=%d LIMIT 1", $discount_code_id ) );;

		if ( null === $discount_code_id || empty( $discount_code ) ) {
			return $cur_title;
		}

		return sprintf(
			__( 'Checkouts using <a href="%s">%s</a>', 'sitewide-sales' ),
			admin_url( 'admin.php?page=pmpro-discountcodes&edit=' . $discount_code_id ),
			$discount_code
		);
	}

	/**
	 * Set PMPro module checkout conversions for Sitewide Sale report.
	 *
	 * @param string               $cur_conversions set by filter.
	 * @param SWSale_Sitewide_Sale $sitewide_sale to generate report for.
	 * @return string
	 */
	public static function checkout_conversions( $cur_conversions, $sitewide_sale ) {
		if ( 'pmpro' !== $sitewide_sale->get_sale_type() ) {
			return $cur_conversions;
		}
		global $wpdb;

		return $wpdb->get_var(
			$wpdb->prepare(
				"
				SELECT COUNT(*)
				FROM $wpdb->pmpro_discount_codes_uses
				WHERE code_id = %d
					AND timestamp >= %s
					AND timestamp < %s
			",
				intval( $sitewide_sale->get_meta_value( 'swsales_pmpro_discount_code_id', null ) ),
				$sitewide_sale->get_start_date( 'Y-m-d' ) . ' 00:00:00',
				$sitewide_sale->get_end_date( 'Y-m-d' ) . ' 23:59:59'
			) . ''
		);
	}

	/**
	 * Set PMPro module total revenue for Sitewide Sale report.
	 *
	 * @param string               $cur_revenue set by filter.
	 * @param SWSale_Sitewide_Sale $sitewide_sale to generate report for.
	 * @param bool                 $format_price whether to run output through pmpro_formatPrice().
	 * @return string
	 */
	public static function total_revenue( $cur_revenue, $sitewide_sale, $format_price = true ) {
		if ( 'pmpro' !== $sitewide_sale->get_sale_type() ) {
			return $cur_revenue;
		}
		global $wpdb;

		$total_rev = $wpdb->get_var(
			$wpdb->prepare(
				"
				SELECT SUM(mo.total)
				FROM $wpdb->pmpro_membership_orders mo
				WHERE mo.status NOT IN('refunded', 'review', 'token', 'error')
					AND mo.timestamp >= %s
					AND mo.timestamp < %s
			",
				$sitewide_sale->get_start_date( 'Y-m-d' ) . ' 00:00:00',
				$sitewide_sale->get_end_date( 'Y-m-d' ) . ' 23:59:59'
			)
		);
		return $format_price ? pmpro_formatPrice( $total_rev ) : $total_rev;
	}

	/**
	 * Add additional PMPro module revenue report for Sitewide Sale.
	 *
	 * @param SWSale_Sitewide_Sale $sitewide_sale to generate report for.
	 * @return string
	 */
	public static function additional_report( $sitewide_sale ) {
		if ( 'pmpro' !== $sitewide_sale->get_sale_type() ) {
			return;
		}
		global $wpdb;
		$total_rev         = floatval( self::total_revenue( null, $sitewide_sale, false ) );
		$new_rev_with_code = floatval(
			$wpdb->get_var(
				$wpdb->prepare(
					"
					SELECT SUM(total) FROM (
						SELECT mo.total  as total
						FROM $wpdb->pmpro_membership_orders mo
							LEFT JOIN $wpdb->pmpro_discount_codes_uses dcu
								ON dcu.order_id = mo.id
						WHERE dcu.code_id = %d #discount code is used
							AND mo.status NOT IN('refunded', 'review', 'token', 'error')
							AND mo.timestamp >= %s
							AND mo.timestamp < %s
						GROUP BY mo.id
					) temp
				",
					intval( $sitewide_sale->get_meta_value( 'swsales_pmpro_discount_code_id', null ) ),
					$sitewide_sale->get_start_date( 'Y-m-d' ) . ' 00:00:00',
					$sitewide_sale->get_end_date( 'Y-m-d' ) . ' 23:59:59'
				)
			)
		);
		$new_rev_without_code = $wpdb->get_var(
			$wpdb->prepare(
				"
			SELECT SUM(total) FROM (
				SELECT mo.total  as total
				FROM $wpdb->pmpro_membership_orders mo
					LEFT JOIN $wpdb->pmpro_discount_codes_uses dcu
						ON dcu.order_id = mo.id
					LEFT JOIN $wpdb->pmpro_membership_orders mo2
						ON mo.user_id = mo2.user_id
							AND mo2.id <> mo.id
							AND mo2.status NOT IN('refunded', 'review', 'token', 'error')
				WHERE (dcu.code_id IS NULL OR dcu.code_id <> %d) #null or different code
					AND mo.status NOT IN('refunded', 'review', 'token', 'error')
					AND mo.timestamp >= %s
					AND mo.timestamp < %s
					#no other order for the same user
					AND mo2.id IS NULL
				GROUP BY mo.id
				) temp
			",
				intval( $sitewide_sale->get_meta_value( 'swsales_pmpro_discount_code_id', null ) ),
				$sitewide_sale->get_start_date( 'Y-m-d' ) . ' 00:00:00',
				$sitewide_sale->get_end_date( 'Y-m-d' ) . ' 23:59:59'
			)
		);
		$renewals = $wpdb->get_var(
			$wpdb->prepare(
				"
				SELECT SUM(total) FROM (
					SELECT mo.total  as total
					FROM $wpdb->pmpro_membership_orders mo
						LEFT JOIN $wpdb->pmpro_discount_codes_uses dcu
							ON dcu.order_id = mo.id
						LEFT JOIN $wpdb->pmpro_membership_orders mo2
							ON mo.user_id = mo2.user_id
								AND mo2.id <> mo.id
								AND mo2.status NOT IN('refunded', 'review', 'token', 'error')
					WHERE (dcu.code_id IS NULL OR dcu.code_id <> %d) #null or different code
						AND mo.status NOT IN('refunded', 'review', 'token', 'error')
						AND mo.timestamp >= %s
						AND mo.timestamp < %s
						#another order for the same user
						AND mo2.id IS NOT NULL
					GROUP BY mo.id
					) temp
			",
				intval( $sitewide_sale->get_meta_value( 'swsales_pmpro_discount_code_id', null ) ),
				$sitewide_sale->get_start_date( 'Y-m-d' ) . ' 00:00:00',
				$sitewide_sale->get_end_date( 'Y-m-d' ) . ' 23:59:59'
			)
		);

		?>
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
			<div class="swsales_reports-data swsales_reports-data-4col">
				<div class="swsales_reports-data-section">
					<h1><?php echo esc_attr( pmpro_formatPrice( $new_rev_with_code ) ); ?></h1>
					<p>
						<?php esc_html_e( 'Sale Revenue', 'sitewide-sales' ); ?>
						<br />
						(<?php echo( esc_html( 0 == $total_rev ? 'NA' : round( ( $new_rev_with_code / $total_rev ) * 100, 2 ) ) ); ?>%)
					</p>
				</div>
				<div class="swsales_reports-data-section">
					<h1><?php echo esc_attr( pmpro_formatPrice( $new_rev_without_code ) ); ?></h1>
					<p>
						<?php esc_html_e( 'Other New Revenue', 'sitewide-sales' ); ?>
						<br />
						(<?php echo( esc_html( 0 == $total_rev ? 'NA' : round( ( $new_rev_without_code / $total_rev ) * 100, 2 ) ) ); ?>%)
					</p>
				</div>
				<div class="swsales_reports-data-section">
					<h1><?php echo esc_attr( pmpro_formatPrice( $renewals ) ); ?></h1>
					<p>
						<?php esc_html_e( 'Renewals', 'sitewide-sales' ); ?>
						<br />
						(<?php echo( esc_html( 0 == $total_rev ? 'NA' : round( ( $renewals / $total_rev ) * 100, 2 ) ) ); ?>%)
					</p>
				</div>
				<div class="swsales_reports-data-section">
					<h1><?php echo esc_attr( pmpro_formatPrice( $total_rev ) ); ?></h1>
					<p><?php esc_html_e( 'Total Revenue in Period', 'sitewide-sales' ); ?></p>
				</div>
			</div>
		</div>
		<?php
	}

}
SWSales_Module_PMPro::init();
