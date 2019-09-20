<?php

namespace Sitewide_Sales\modules;

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

		// Enable saving of fields added above.
		add_action( 'swsales_save_metaboxes', array( __CLASS__, 'save_metaboxes' ), 10, 3 );

		// Enqueue JS for Edit Sitewide Sale page.
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );

		// SWSale compatibility when editing/saving a discount code.
		add_action( 'admin_notices', array( __CLASS__, 'return_from_editing_discount_code_box' ) );
		add_action( 'pmpro_save_discount_code', array( __CLASS__, 'discount_code_on_save' ) );
		add_action( 'wp_ajax_pmpro_sws_create_discount_code', array( __CLASS__, 'create_discount_code_ajax' ) );

		// TODO: Default level for sale page.

		// TODO: Custom PMPro banner rules (hide for levels and hide at checkout).

		// TODO: PMPro automatic discount application.

	}

	public static function register_sale_type( $sale_types ) {
		$sale_types['pmpro'] = 'Paid Memberships Pro';
		return $sale_types;
	} // end register_sale_type()

	public static function add_choose_discount_code( $post ) {
		global $wpdb;
		$codes            = $wpdb->get_results( "SELECT * FROM $wpdb->pmpro_discount_codes", OBJECT );
		$current_discount = get_post_meta( $post->ID, 'swsales_pmpro_discount_code_id', true );
		if ( empty( $current_discount ) ) {
			$current_discount = false;
		}
		?>
		<tr class='swsales-module-row swsales-module-row-pmpro'>
			<?php if ( ! defined( 'PMPRO_VERSION' ) ) { ?>
				<th></th>
				<td><?php _e( 'The Paid Memberships Pro plugin is not active.', 'sitewide-sales' ); ?></td>
			
			<?php } else { ?>
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

	public static function add_set_landing_page_default_level( $post ) {
		$default_level = get_post_meta( $post->ID, 'pmpro_sws_landing_page_default_level_id', true );
		?>
		<tr class='swsales-module-row swsales-module-row-pmpro'>
			<?php if ( ! defined( 'PMPRO_VERSION' ) ) { ?>
				<th></th>
				<td><?php _e( 'The Paid Memberships Pro plugin is not active.', 'sitewide-sales' ); ?></td>
			<?php } else { ?>
				<th><label for="swsales_pmpro_landing_page_default_level"><?php esc_html_e( 'Checkout Level', 'sitewide-sales' ); ?></label></th>
				<td>
					<select id="swsales_pmpro_landing_page_default_level" name="swsales_pmpro_landing_page_default_level">
					<option value="0"><?php esc_html_e( '- Choose One -', 'sitewide-sales' ); ?></option>
					<?php
						$all_levels = pmpro_getAllLevels( true, true );
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

	public static function add_hide_banner_by_level( $post ) {
		$hide_for_levels = get_post_meta( $post->ID, 'pmpro_sws_hide_for_levels', true );
			if ( empty( $hide_for_levels ) ) {
				$hide_for_levels = array();
			}
		?>
		<tr class='swsales-module-row swsales-module-row-pmpro'>
			<?php if ( ! defined( 'PMPRO_VERSION' ) ) { ?>
				<th></th>
				<td><?php _e( 'The Paid Memberships Pro plugin is not active.', 'sitewide-sales' ); ?></td>
			<?php } else { ?>
				<th scope="row" valign="top"><label><?php esc_html_e( 'Hide Banner by Membership Level', 'sitewide-sales' ); ?></label></th>
					<td>
						<input type="hidden" name="swsales_pmpro_hide_for_levels_exists" value="1" />
						<select multiple class="swsales_option" id="swsales_pmpro_hide_levels_select" name="swsales_pmpro_hide_for_levels[]" style="width:12em">
						<?php
							$all_levels = pmpro_getAllLevels( true, true );
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
	}

	public static function save_metaboxes( $post_id, $post ) {
		if ( isset( $_POST['swsales_pmpro_discount_code_id'] ) ) {
			update_post_meta( $post_id, 'swsales_pmpro_discount_code_id', intval( $_POST['swsales_pmpro_discount_code_id'] ) );
		}
		if ( isset( $_POST['swsales_pmpro_landing_page_default_level'] ) ) {
			update_post_meta( $post_id, 'swsales_pmpro_landing_page_default_level', intval( $_POST['swsales_pmpro_landing_page_default_level'] ) );
		}
		if ( ! empty( $_POST['swsales_pmpro_hide_for_levels'] ) && is_array( $_POST['swsales_pmpro_hide_for_levels'] ) ) {
			$pmpro_sws_hide_for_levels = array_map( 'intval', $_POST['swsales_pmpro_hide_for_levels'] );
			update_post_meta( $post_id, 'swsales_pmpro_hide_for_levels', $pmpro_sws_hide_for_levels );
		} elseif ( isset( $_POST['swsales_pmpro_hide_for_levels'] ) ) {
			update_post_meta( $post_id, 'swsales_pmpro_hide_for_levels', false );
		}
	}

	/**
	 * Enqueues js/pmpro-sws-cpt-meta.js
	 */
	public static function enqueue_scripts() {
		global $wpdb, $typenow;
		if ( 'sitewide_sale' === $typenow ) {
			wp_register_script( 'swsales_module_pmpro_metaboxes', plugins_url( 'modules/js/swsales-module-pmpro-metaboxes.js', SWSALES_BASENAME ), array( 'jquery' ), '1.0.4' );
			wp_enqueue_script( 'swsales_module_pmpro_metaboxes' );

			$pages_with_swsales_shortcode = $wpdb->get_col(
				"SELECT ID
				 FROM $wpdb->posts
				 WHERE post_type = 'page'
				 	AND post_status IN( 'publish', 'draft' )
					AND post_content LIKE '%[swsales%'"
			);

			wp_localize_script(
				'swsales_module_pmpro_metaboxes',
				'swsales',
				array(
					'create_discount_code_nonce' => wp_create_nonce( 'swsales_create_discount_code' ),
					'admin_url'                  => admin_url(),
				)
			);

		}
	} // end enqueue_scripts()
	
	/**
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
	 * Displays a link back to Sitewide Sale when discount code is edited/saved
	 */
	public static function return_from_editing_discount_code_box() {
		if ( isset( $_REQUEST['swsales_pmpro_callback'] ) && 'memberships_page_pmpro-discountcodes' === get_current_screen()->base ) {
			?>
			<div class="notice notice-success">
				<p><?php esc_html_e( 'Click ', 'pmpro-sitewide-sales' ); ?>
					<a href="<?php echo esc_url( admin_url( 'post.php?post=' . intval( $_REQUEST['swsales_pmpro_callback'] ) . '&action=edit' ) ); ?>">
						<?php esc_html_e( 'here', 'pmpro-sitewide-sales' ); ?>
					</a>
					<?php esc_html_e( ' to go back to editing Sitewide Sale', 'pmpro-sitewide-sales' ); ?>
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

}
SWSales_Module_PMPro::init();
