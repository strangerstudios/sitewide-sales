<?php

namespace Sitewide_Sales\includes\classes;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

/**
 * Register a meta box using a class.
 */
class SWSales_MetaBoxes {

	/**
	 * Constructor.
	 */
	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		add_action( 'load-post.php', array( __CLASS__, 'init_metabox' ) );
		add_action( 'load-post-new.php', array( __CLASS__, 'init_metabox' ) );
		//add_action( 'pmpro_save_discount_code', array( __CLASS__, 'discount_code_on_save' ) );
		add_action( 'save_post', array( __CLASS__, 'landing_page_on_save' ), 10, 3 );
		//add_action( 'admin_notices', array( __CLASS__, 'return_from_editing_discount_code_box' ) );
		add_action( 'enter_title_here', array( __CLASS__, 'update_title_placeholder_text' ), 10, 2 );
		add_filter( 'redirect_post_location', array( __CLASS__, 'redirect_after_page_save' ), 10, 2 );
		add_action( 'wp_ajax_swsales_create_landing_page', array( __CLASS__, 'create_landing_page_ajax' ) );
		//add_action( 'wp_ajax_swsales_create_discount_code', array( __CLASS__, 'create_discount_code_ajax' ) );
	}

	/**
	 * Enqueues js/pmpro-sws-cpt-meta.js
	 */
	public static function enqueue_scripts() {
		global $wpdb, $typenow;
		if ( 'sitewide_sale' === $typenow ) {
			wp_register_script( 'swsales_cpt_meta', plugins_url( 'js/swsales-cpt-meta.js', SWSALES_BASENAME ), array( 'jquery' ), '1.0.4' );
			wp_enqueue_script( 'swsales_cpt_meta' );

			$pages_with_swsales_shortcode = $wpdb->get_col(
				"SELECT ID
				 FROM $wpdb->posts
				 WHERE post_type = 'page'
				 	AND post_status IN( 'publish', 'draft' )
					AND post_content LIKE '%[swsales%'"
			);

			wp_localize_script(
				'swsales_cpt_meta',
				'swsales',
				array(
					'create_landing_page_nonce'  => wp_create_nonce( 'swsales_create_landing_page' ),
					'home_url'                   => home_url(),
					'admin_url'                  => admin_url(),
					'pages_with_shortcodes'      => $pages_with_swsales_shortcode,
					'str_draft'                  => esc_html__( 'Draft', 'sitewide-sales' ),
				)
			);

		}
	}

	/**
	 * Meta box initialization.
	 */
	public static function init_metabox() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_sws_metaboxes' ) );
		add_action( 'save_post', array( __CLASS__, 'save_sws_metaboxes' ), 10, 2 );
	}

	/**
	 * Add/remove the metaboxes.
	 */
	public static function add_sws_metaboxes() {
		add_meta_box(
			'swsales_cpt_publish_sitewide_sale',
			__( 'Admin', 'sitewide-sales' ),
			array( __CLASS__, 'publish_sitewide_sale' ),
			array( 'sitewide_sale' ),
			'side',
			'high'
		);
		add_meta_box(
			'swsales_cpt_step_1',
			__( 'Step 1: Start and End Dates', 'sitewide-sales' ),
			array( __CLASS__, 'display_step_1' ),
			array( 'sitewide_sale' ),
			'normal',
			'high'
		);
		add_meta_box(
			'swsales_cpt_step_2',
			__( 'Step 2: Sale Type', 'sitewide-sales' ),
			array( __CLASS__, 'display_step_2' ),
			array( 'sitewide_sale' ),
			'normal',
			'high'
		);
		add_meta_box(
			'swsales_cpt_step_3',
			__( 'Step 3: Landing Page', 'sitewide-sales' ),
			array( __CLASS__, 'display_step_3' ),
			array( 'sitewide_sale' ),
			'normal',
			'high'
		);
		add_meta_box(
			'swsales_cpt_step_4',
			__( 'Step 4: Banners', 'sitewide-sales' ),
			array( __CLASS__, 'display_step_4' ),
			array( 'sitewide_sale' ),
			'normal',
			'high'
		);
		add_meta_box(
			'swsales_cpt_step_5',
			__( 'Step 5: Reports', 'sitewide-sales' ),
			array( __CLASS__, 'display_step_5' ),
			array( 'sitewide_sale' ),
			'normal',
			'high'
		);

		// remove some default metaboxes
		remove_meta_box( 'slugdiv', 'sitewide_sale', 'normal' );
		remove_meta_box( 'submitdiv', 'sitewide_sale', 'side' );
	}

	public static function publish_sitewide_sale( $post ) {
		wp_nonce_field( 'custom_nonce_action', 'custom_nonce' );

		global $cur_sale;
		if ( ! isset( $cur_sale ) ) {
			$cur_sale = new SWSales_Sitewide_Sale();
			$cur_sale->load_sitewide_sale( $post->ID );
		}

		// TODO: Think about whether should automatically be set as active sitewide sale
		$init_checked = false;
		if ( isset( $_REQUEST['set_sitewide_sale'] ) && 'true' === $_REQUEST['set_sitewide_sale'] ) {
			$init_checked = true;
		} else {
			$options = SWSales_Settings::get_options();
			if ( empty( $options['active_sitewide_sale_id'] ) && $post->post_status == 'auto-draft'
				|| $cur_sale->is_active_sitewide_sale() ) {
				$init_checked = true;
			}
		}
		?>
		<div id="misc-publishing-actions">
			<div class="misc-pub-section">
				<p>
					<label for="swsales_set_as_sitewide_sale"><strong><?php esc_html_e( 'Set as Current Sitewide Sale:', 'sitewide-sales' ); ?></strong></label>
					<input name="swsales_set_as_sitewide_sale" id="swsales_set_as_sitewide_sale" type="checkbox" <?php checked( $init_checked, true ); ?> />
				</p>
			</div>
			<div class="misc-pub-section">
				<p><a target="_blank" href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-reports&report=pmpro_sws_reports&pmpro_sws_sitewide_sale_id=' . $post->ID ) ); ?>"><?php esc_html_e( 'View Sitewide Sale Reports', 'sitewide-sales' ); ?></a></p>
			</div>
		</div>
		<div id="major-publishing-actions">
			<div id="publishing-action">
				<input type="submit" class="button button-primary" value="<?php esc_html_e( 'Save All Settings', 'sitewide-sales' ); ?>">
			</div>
			<div class="clear"></div>
		</div>
		<?php
	}

	/**
	 * Filter the "Enter title here" placeholder in the title field
	 */
	public static function update_title_placeholder_text( $text, $post ) {
		if ( $post->post_type == 'sitewide_sale' ) {
			$text = esc_html__( 'Enter title here. (For reference only.)', 'sitewide-sales' );
		}

		return $text;
	}

	public static function display_step_1( $post ) {
		global $wpdb, $cur_sale;
		if ( ! isset( $cur_sale ) ) {
			$cur_sale = new SWSales_Sitewide_Sale();
			$cur_sale->load_sitewide_sale( $post->ID );
		}
		?>
		<p><?php esc_html_e( 'These fields control when the banner (if applicable) and built-in sale reporting will be active for your site. They also control what content is displayed on your sale Landing Page according to the "Landing Page" settings in Step 3 below.', 'sitewide-sales' ); ?></p>

		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row" valign="top"><label for="swsales_start_date"><?php esc_html_e( 'Sale Start Date', 'sitewide-sales' ); ?>:</label></th>
					<td>
						<select id="swsales_start_month" name="swsales_start_month">
							<?php
							for ( $i = 1; $i < 13; $i++ ) {
								?>
								<option value="<?php echo esc_attr( $i ); ?>" 
														<?php
														if ( $i == $cur_sale->get_start_month() ) {
															?>
									selected="selected"<?php } ?>><?php echo date_i18n( 'M', strtotime( $i . '/1/' . $cur_sale->get_start_year(), current_time( 'timestamp' ) ) ); ?></option>
								<?php
							}
							?>
						</select>
						<input id="swsales_start_day" name="swsales_start_day" type="text" size="2" value="<?php echo esc_attr( $cur_sale->get_start_day() ); ?>" />
						<input id="swsales_start_year" name="swsales_start_year" type="text" size="4" value="<?php echo esc_attr( $cur_sale->get_start_year() ); ?>" />
						<p><small class="pmpro_lite"><?php esc_html_e( 'Set this date to the first day of your sale.', 'sitewide-sales' ); ?></small></p>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label for="swsales_end_date"><?php esc_html_e( 'Sale End Date', 'sitewide-sales' ); ?>:</label></th>
					<td>
						<select id="swsales_end_month" name="swsales_end_month">
							<?php
							for ( $i = 1; $i < 13; $i++ ) {
								?>
								<option value="<?php echo esc_attr( $i ); ?>" 
														<?php
														if ( $i == $cur_sale->get_end_month() ) {
															?>
									selected="selected"<?php } ?>><?php echo esc_html( date_i18n( 'M', strtotime( $i . '/1/' . $cur_sale->get_end_year(), current_time( 'timestamp' ) ) ) ); ?></option>
								<?php
							}
							?>
						</select>
						<input id="swsales_end_day" name="swsales_end_day" type="text" size="2" value="<?php echo esc_attr( $cur_sale->get_end_day() ); ?>" />
						<input id="swsales_end_year" name="swsales_end_year" type="text" size="4" value="<?php echo esc_attr( $cur_sale->get_end_year() ); ?>" />
						<p><small class="pmpro_lite"><?php esc_html_e( 'Set this date to the last full day of your sale.', 'sitewide-sales' ); ?></small></p>
					</td>
				</tr>
			</tbody>
		</table>
		<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save All Settings', 'sitewide-sales' ); ?>">
		<?php
	}

	public static function display_step_2( $post ) {
		global $wpdb, $cur_sale;
		if ( ! isset( $cur_sale ) ) {
			$cur_sale = new SWSales_Sitewide_Sale();
			$cur_sale->load_sitewide_sale( $post->ID );
		}

		// Each module should add to that array using 'short_name'->'Nice Name'
		$sale_types = apply_filters( 'swsales_sale_types', array() );
		$current_sale_type = $cur_sale->get_sale_type();
		?>
		<table class="form-table">
			<tbody>
				<tr>
					<th><label for="swsales_sale_type"><?php esc_html_e( 'Sale Type', 'sitewide-sales' );?></label></th>
					<td>
						<select class="sale_type_select swsales_option" id="swsales_sale_type_select" name="swsales_sale_type">
							<option value="0"><?php esc_html_e( '- Choose One -', 'sitewide-sales' ); ?></option>
							<?php
							$sale_type_found = false;
							foreach ( $sale_types as $sale_type_short => $sale_type_nice ) {
								$selected_modifier = '';
								if ( $sale_type_short === $current_sale_type ) {
									$selected_modifier = ' selected="selected"';
									$sale_type_found        = true;
								}
								echo '<option value="' . esc_attr( $sale_type_short ) . '"' . $selected_modifier . '>' . esc_html( $sale_type_nice ) . '</option>';
							}
							?>
						</select>
					</td>
				</tr>
				<?php 
				// Add filter to add custom settings from module 
				do_action( 'swsales_after_choose_sale_type', $post );
				?>
			</tbody>
		</table>
		<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save All Settings', 'sitewide-sales' ); ?>">
		<?php
	}

	public static function display_step_3( $post ) {
		global $wpdb, $cur_sale;
		if ( ! isset( $cur_sale ) ) {
			$cur_sale = new SWSales_Sitewide_Sale();
			$cur_sale->load_sitewide_sale( $post->ID );
		}

		$pages        = get_pages( array( 'post_status' => 'publish,draft' ) );
		$current_page = $cur_sale->get_landing_page_post_id();
		$landing_template = $cur_sale->get_landing_page_template();
		?>
		<input type="hidden" id="swsales_old_landing_page_post_id" name="swsales_old_landing_page_post_id" value="<?php echo esc_attr( $current_page ); ?>" />
		<table class="form-table">
			<tbody>
				<tr>
					<th><label for="swsales_landing_page_post_id"><?php esc_html_e( 'Landing Page', 'sitewide-sales' ); ?></label></th>
					<td>
						<select class="landing_page_select swsales_option" id="swsales_landing_page_select" name="swsales_landing_page_post_id">
							<option value="0"><?php esc_html_e( '- Choose One -', 'sitewide-sales' ); ?></option>
							<?php
							$page_found = false;
							foreach ( $pages as $page ) {
								$selected_modifier = '';
								if ( $page->ID . '' === $current_page ) {
									$selected_modifier = ' selected="selected"';
									$page_found        = true;
								}
								if ( $page->post_status == 'draft' ) {
									$status_part = ' (' . esc_html__( 'Draft', 'sitewide-sales' ) . ')';
								} else {
									$status_part = '';
								}
								echo '<option value="' . esc_attr( $page->ID ) . '"' . $selected_modifier . '>' . esc_html( $page->post_title ) . $status_part . '</option>';
							}
							?>
						</select><br />
						<?php
							$current_page_post = get_post( $current_page );
						if ( ! empty( $current_page_post->post_content ) && strpos( $current_page_post->post_content, '[swsales' ) !== false ) {
							$show_shortcode_warning = false;
						} else {
							$show_shortcode_warning = true;
						}
						?>
						<p
						<?php
						if ( ! $show_shortcode_warning ) {
						?>
  style="display: none;"<?php } ?> class="swsales_shortcode_warning"><small class="pmpro_red"><?php echo wp_kses_post( '<strong>Warning:</strong> The [swsales] shortcode was not found in this post.', 'sitewide-sales' ); ?></small></p>

						<p>
							<span id="swsales_after_landing_page_select" 
							<?php
							if ( ! $page_found ) {
								?>
 style="display: none;"<?php } ?>>
							<?php
								$edit_page_url = admin_url( 'post.php?post=' . $current_page . '&action=edit&swsales_callback=' . $post->ID );
								$view_page_url = get_permalink( $current_page );
							?>
							<a target="_blank" class="button button-secondary" id="swsales_edit_landing_page" href="<?php echo esc_url( $edit_page_url ); ?>"><?php esc_html_e( 'edit page', 'sitewide-sales' ); ?></a>
							&nbsp;
							<a target="_blank" class="button button-secondary" id="swsales_view_landing_page" href="<?php echo esc_url( $view_page_url ); ?>"><?php esc_html_e( 'view page', 'sitewide-sales' ); ?></a>
							<?php
								esc_html_e( ' or ', 'sitewide-sales' );
							?>
							</span>
							<button type="button" id="swsales_create_landing_page" class="button button-secondary"><?php esc_html_e( 'create a new landing page', 'sitewide-sales' ); ?></button>
						</p>
					</td>
				</tr>
				<?php
					// Allow template selection if using Memberlite or set the Advanced Setting to "Yes".
				//if ( defined( 'MEMBERLITE_VERSION' ) || ( pmpro_getOption( 'swsales_allow_template' ) === 'Yes' ) ) {
				if ( defined( 'MEMBERLITE_VERSION' ) || true ) {
					?>
					<tr>
						<th><label for="swsales_landing_page_template"><?php esc_html_e( 'Landing Page Template', 'sitewide-sales' ); ?></label></th>
						<td>
							<select class="landing_page_select_template swsales_option" id="swsales_landing_page_template" name="swsales_landing_page_template">
								<option value="0"><?php esc_html_e( 'None', 'sitewide-sales' ); ?></option>
								<?php
								$templates = array(
									'gradient' => 'Gradient',
									'neon'     => 'Neon',
									'ocean'    => 'Ocean',
									'photo'    => 'Photo',
									'scroll'   => 'Scroll',
									'vintage'  => 'Vintage',
								);
								$templates = apply_filters( 'swsales_landing_page_templates', $templates );
								foreach ( $templates as $key => $value ) {
									echo '<option value="' . esc_attr( $key ) . '" ' . selected( $landing_template, esc_html( $key ) ) . '>' . esc_html( $value ) . '</option>';
								}
								?>
							</select>
							<p><small class="pmpro_lite"><?php esc_html_e( 'Stylish templates available for your theme.', 'sitewide-sales' ); ?></small></p>
						</td>
					</tr>
				<?php 
					} 
					// Add filter for modules here.
					do_action( 'swsales_after_choose_landing_page', $post );
				?>
			</tbody>
		</table>
		<hr />
		<p><?php _e( 'Use the [swsales] shortcode in your landing page to automatically display the following sections before, during, and after the sale. Alternatively, you can remove the shortcode and manually update the landing page content.', 'sitewide-sales' ); ?></p>
		<p
		<?php
		if ( ! $show_shortcode_warning ) {
			?>
  style="display: none;"<?php } ?> class="swsales_shortcode_warning"><small class="pmpro_red"><?php echo wp_kses_post( '<strong>Warning:</strong> The chosen Landing Page does not include the [swsales] shortcode, so the following sections will not be displayed.', 'sitewide-sales' ); ?></small></p>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row" valign="top"><label><?php esc_html_e( 'Pre-Sale Content', 'sitewide-sales' ); ?></label></th>
					<td>
						<textarea class="swsales_option" rows="4" name="swsales_pre_sale_content"><?php echo( esc_textarea( $cur_sale->get_pre_sale_content() ) ); ?></textarea><br />
						<p><small class="pmpro_lite">
							<?php esc_html_e( 'Mention when the sale is starting and how awesome it will be.', 'sitewide-sales' ); ?>
							<?php if ( ! empty( $view_page_url ) ) { ?>
								<a target="_blank" id="swsales_view_landing_page" href="<?php echo esc_url( add_query_arg( 'swsales_preview_time_period', 'pre-sale', $view_page_url ) ); ?>"><?php esc_html_e( 'preview', 'sitewide-sales' ); ?></a>
							<?php } ?>
						</small></p>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label><?php esc_html_e( 'Sale Content', 'sitewide-sales' ); ?></label></th>
					<td>
						<textarea class="swsales_option" rows="4" name="swsales_sale_content"><?php echo( esc_html( $cur_sale->get_sale_content() ) ); ?></textarea><br />
						<p><small class="pmpro_lite">
							<?php esc_html_e( 'A membership checkout form will automatically be included when the sale is active.', 'sitewide-sales' ); ?>
							<?php if ( ! empty( $view_page_url ) ) { ?>
								<a target="_blank" id="swsales_view_landing_page" href="<?php echo esc_url( add_query_arg( 'swsales_preview_time_period', 'sale', $view_page_url ) ); ?>"><?php esc_html_e( 'preview', 'sitewide-sales' ); ?></a>
							<?php } ?>
						</small></p>
						</small></p>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label><?php esc_html_e( 'Post-Sale Content', 'sitewide-sales' ); ?></label></th>
					<td>
						<textarea class="swsales_option" rows="4" name="swsales_post_sale_content"><?php echo( esc_html( $cur_sale->get_post_sale_content() ) ); ?></textarea><br />
						<p><small class="pmpro_lite">
							<?php esc_html_e( 'Mention that the sale has ended and thank your customers.', 'sitewide-sales' ); ?>
							<?php if ( ! empty( $view_page_url ) ) { ?>
								<a target="_blank" id="swsales_view_landing_page" href="<?php echo esc_url( add_query_arg( 'swsales_preview_time_period', 'post-sale', $view_page_url ) ); ?>"><?php esc_html_e( 'preview', 'sitewide-sales' ); ?></a>
							<?php } ?>
						</small></p>
					</td>
				</tr>
			</tbody>
		</table>
		<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save All Settings', 'sitewide-sales' ); ?>">
		<?php
	}

	public static function display_step_4( $post ) {
		global $cur_sale;
		if ( ! isset( $cur_sale ) ) {
			$cur_sale = new SWSales_Sitewide_Sale();
			$cur_sale->load_sitewide_sale( $post->ID );
		}

		$use_banner = $cur_sale->get_use_banner();
		$banner_template = $cur_sale->get_banner_template()

		?>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row" valign="top"><label><?php esc_html_e( 'Use the built-in banner?', 'sitewide-sales' ); ?></label></th>
					<td>
						<select class="use_banner_select swsales_option" id="swsales_use_banner_select" name="swsales_use_banner">
							<option value="no" <?php selected( $use_banner, 'no' ); ?>><?php esc_html_e( 'No', 'sitewide-sales' ); ?></option>
							<?php
								$registered_banners = SWSales_Banners::get_registered_banners();
							foreach ( $registered_banners as $banner => $data ) {
								if ( is_string( $banner ) && is_array( $data ) && ! empty( $data['option_title'] ) && is_string( $data['option_title'] ) ) {
									echo '<option value="' . esc_attr( $banner ) . '"' . selected( $use_banner, $banner ) . '>' . esc_html( $data['option_title'] ) . '</option>';
								}
							}
							?>
						</select>
						<input type="submit" class="button button-secondary" id="swsales_preview" name="swsales_preview" value="<?php esc_attr_e( 'Save and Preview', 'sitewide-sales' ); ?>">
						<p><small class="pmpro_lite"><?php esc_html_e( 'Optionally display a banner, which you can customize using additional settings below, to advertise your sale.', 'sitewide-sales' ); ?></small></p>
					</td>
				</tr>
			</tbody>
		</table>
		<table class="form-table" id="swsales_banner_options" 
		<?php
		if ( $use_banner === 'no' ) {
			?>
			style="disaply: none;"<?php } ?>>
			<tbody>
				<?php
					// Allow template selection if using Memberlite or set the Advanced Setting to "Yes".
				//if ( defined( 'MEMBERLITE_VERSION' ) || ( pmpro_getOption( 'swsales_allow_template' ) === 'Yes' ) ) {
				if ( defined( 'MEMBERLITE_VERSION' ) || true ) {
					?>
					<tr>
						<th><label for="swsales_banner_template"><?php esc_html_e( 'Banner Template', 'sitewide-sales' ); ?></label></th>
						<td>
							<select class="banner_select_template swsales_option" id="swsales_banner_template" name="swsales_banner_template">
								<option value="0"><?php esc_html_e( 'None', 'sitewide-sales' ); ?></option>
								<?php
								$templates = array(
									'gradient' => 'Gradient',
									'neon'     => 'Neon',
									'ocean'    => 'Ocean',
									'photo'    => 'Photo',
									'scroll'   => 'Scroll',
									'vintage'  => 'Vintage',
								);
								$templates = apply_filters( 'swsales_banner_templates', $templates );
								foreach ( $templates as $key => $value ) {
									echo '<option value="' . esc_attr( $key ) . '" ' . selected( $banner_template, $key ) . '>' . esc_html( $value ) . '</option>';
								}
								?>
							</select>
							<p><small class="pmpro_lite"><?php esc_html_e( 'Stylish templates available for your theme.', 'sitewide-sales' ); ?></small></p>
						</td>
					</tr>
				<?php } ?>
				<tr>
					<th><label for="swsales_banner_title"><?php esc_html_e( 'Banner Title', 'sitewide-sales' ); ?></label></th>
					<td>
						<input type="textbox" name="swsales_banner_title" value="<?php echo esc_attr( $cur_sale->get_banner_title() ); ?>">
						<p><small class="pmpro_lite"><?php esc_html_e( 'A brief title for your sale, such as the holiday or purpose of the sale. (i.e. "Limited Time Offer")', 'sitewide-sales' ); ?></small></p>
					</td>
				</tr>
				<tr>
					<th><label for="swsales_banner_text"><?php esc_html_e( 'Banner Text', 'sitewide-sales' ); ?></label></th>
					<td>
						<textarea class="swsales_option" id="swsales_banner_text" name="swsales_banner_text"><?php echo esc_textarea( $cur_sale->get_banner_text(), 'sitewide-sales' ); ?></textarea>
						<p><small class="pmpro_lite"><?php esc_html_e( 'A brief message about your sale. (i.e. "Save 50% on membership through December.")', 'sitewide-sales' ); ?></small></p>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label><?php esc_html_e( 'Button Text', 'sitewide-sales' ); ?></label></th>
					<td>
						<input class="swsales_option" type="text" name="swsales_link_text" value="<?php echo esc_attr( $cur_sale->get_link_text() ); ?>">
						<p><small class="pmpro_lite"><?php esc_html_e( 'The text displayed on the button of your banner that links to the Landing Page.', 'sitewide-sales' ); ?></small></p>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><label><?php esc_html_e( 'Custom Banner CSS', 'sitewide-sales' ); ?></label></th>
					<td>
						<textarea class="swsales_option" name="swsales_css_option"><?php echo esc_textarea( $cur_sale->get_css_option() ); ?></textarea>
						<p><small class="pmpro_lite"><?php esc_html_e( 'Optional. Use this area to add custom styles to modify the banner appearance.', 'sitewide-sales' ); ?></small></p>

						<p id="swsales_css_selectors_description" class="description" 
						<?php
						if ( empty( $use_banner ) || $use_banner == 'no' ) {
							?>
 style="display:none;"<?php } ?>><?php esc_html_e( 'Use these selectors to alter the appearance of your banners.', 'sitewide-sales' ); ?></p>
						<?php foreach ( $registered_banners as $key => $registered_banner ) { ?>
							<div data-pmprosws-banner="<?php echo esc_attr( $key ); ?>" class="swsales_banner_css_selectors" 
																  <?php
																	if ( $key != $use_banner ) {
																		?>
 style="display: none;"<?php } ?>>
							<?php
								$css_selectors = $registered_banner['css_selectors'];
							if ( is_string( $css_selectors ) ) {
								echo $css_selectors;
							} elseif ( is_array( $css_selectors ) ) {
								foreach ( $css_selectors as $css_selector ) {
									if ( is_string( $css_selector ) ) {
										echo $css_selector . ' { }<br/>';
									}
								}
							}
							?>
							</div>
						<?php } ?>
					</td>
				</tr>
				<tr>
					<?php
						$checked_modifier = $cur_sale->get_hide_on_chechout() ? ' checked' : '';
					?>
					<th scope="row" valign="top"><label><?php esc_html_e( 'Hide Banner at Checkout', 'sitewide-sales' ); ?></label></th>
					<td>
						<input type="hidden" name="swsales_hide_on_checkout_exists" value="1" />
						<input class="swsales_option" type="checkbox" id="swsales_hide_on_checkout" name="swsales_hide_on_checkout" <?php checked( $cur_sale->get_hide_on_chechout(), 1 ); ?>> <label for="swsales_hide_on_checkout"><?php esc_html_e( 'Check this box to hide the banner on checkout pages.', 'sitewide-sales' ); ?></label>
						<p><small class="pmpro_lite"><?php esc_html_e( 'Recommended: Leave checked so only users using your landing page will pay the sale price.', 'sitewide-sales' ); ?></small></p>
					</td>
				</tr>
				<?php
				//  Add filter for modlues (ex. hide banner for level)
				do_action( 'swsales_after_banners_settings', $post );
				?>
			</tbody>
		</table>
		<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save All Settings', 'sitewide-sales' ); ?>">
		<?php
	}

	public static function display_step_5( $post ) {
		global $wpdb, $cur_sale;
		if ( ! isset( $cur_sale ) ) {
			$cur_sale = new SWSales_Sitewide_Sale();
			$cur_sale->load_sitewide_sale( $post->ID );
		}
		$cur_sale->show_reports();
	}

	/**
	 * Handles saving the meta box.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return null
	 */
	public static function save_sws_metaboxes( $post_id, $post ) {
		global $wpdb;

		if ( 'sitewide_sale' !== $post->post_type ) {
			return;
		}

		// Check if user has permissions to save data.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Check if not an autosave.
		if ( wp_is_post_autosave( $post_id ) ) {
			return;
		}

		// Check if not a revision.
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( 'auto-draft' === $post->post_status || 'trash' === $post->post_status ) {
			return;
		}

		// Add nonce for security and authentication.
		$nonce_name   = isset( $_POST['custom_nonce'] ) ? sanitize_text_field( $_POST['custom_nonce'] ) : '';
		$nonce_action = 'custom_nonce_action';

		// Check if nonce is set.
		if ( ! isset( $nonce_name ) ) {
			return;
		}

		// Check if nonce is valid.
		if ( ! wp_verify_nonce( $nonce_name, $nonce_action ) ) {
			die( '<br/>Nonce failed' );
		}

		// Make sure the post title is not blank
		if ( isset( $_POST['post_title'] ) && empty( $_POST['post_title'] ) ) {
			$post->post_title = sanitize_post_field(
				'post_title',
				__( 'Sitewide Sale', 'sitewide-sales' ),
				$post->ID,
				'edit'
			);
		}

		if ( isset( $_POST['swsales_sale_type'] ) ) {
			update_post_meta( $post_id, 'swsales_sale_type', sanitize_text_field( $_POST['swsales_sale_type'] ) );
		}

		if ( ! empty( $_POST['swsales_landing_page_post_id'] ) ) {
			update_post_meta( $post_id, 'swsales_landing_page_post_id', intval( $_POST['swsales_landing_page_post_id'] ) );
			update_post_meta( intval( $_POST['swsales_landing_page_post_id'] ), 'swsales_sitewide_sale_id', $post_id );
		} elseif ( isset( $_POST['swsales_landing_page_post_id'] ) ) {
			update_post_meta( $post_id, 'swsales_landing_page_post_id', false );
			delete_post_meta( intval( $_REQUEST['swsales_old_landing_page_post_id'] ), 'swsales_sitewide_sale_id' );
		}

		if ( isset( $_POST['swsales_landing_page_template'] ) ) {
			update_post_meta( $post_id, 'swsales_landing_page_template', sanitize_text_field( $_POST['swsales_landing_page_template'] ) );
		}

		if ( isset( $_POST['swsales_start_day'] ) && is_numeric( $_POST['swsales_start_day'] ) &&
				isset( $_POST['swsales_start_month'] ) && is_numeric( $_POST['swsales_start_month'] ) &&
				isset( $_POST['swsales_start_year'] ) && is_numeric( $_POST['swsales_start_year'] ) &&
				isset( $_POST['swsales_end_day'] ) && is_numeric( $_POST['swsales_end_day'] ) &&
				isset( $_POST['swsales_end_month'] ) && is_numeric( $_POST['swsales_end_month'] ) &&
				isset( $_POST['swsales_end_year'] ) && is_numeric( $_POST['swsales_end_year'] )
		) {
			update_post_meta( $post_id, 'swsales_start_day', $_POST['swsales_start_day'] );
			update_post_meta( $post_id, 'swsales_start_month', $_POST['swsales_start_month'] );
			update_post_meta( $post_id, 'swsales_start_year', $_POST['swsales_start_year'] );
			update_post_meta( $post_id, 'swsales_end_day', $_POST['swsales_end_day'] );
			update_post_meta( $post_id, 'swsales_end_month', $_POST['swsales_end_month'] );
			update_post_meta( $post_id, 'swsales_end_year', $_POST['swsales_end_year'] );
		}

		if ( isset( $_POST['swsales_pre_sale_content'] ) ) {
			update_post_meta( $post_id, 'swsales_pre_sale_content', wp_kses_post( $_POST['swsales_pre_sale_content'] ) );
		}

		if ( isset( $_POST['swsales_sale_content'] ) ) {
			update_post_meta( $post_id, 'swsales_sale_content', wp_kses_post( $_POST['swsales_sale_content'] ) );
		}

		if ( isset( $_POST['swsales_post_sale_content'] ) ) {
			update_post_meta( $post_id, 'swsales_post_sale_content', wp_kses_post( $_POST['swsales_post_sale_content'] ) );
		}

		$possible_options = array_merge( array( 'no' => 'no' ), SWSales_Banners::get_registered_banners() );
		if ( isset( $_POST['swsales_use_banner'] ) && array_key_exists( $_POST['swsales_use_banner'], $possible_options ) ) {
			update_post_meta( $post_id, 'swsales_use_banner', sanitize_text_field( $_POST['swsales_use_banner'] ) );
		}

		if ( isset( $_POST['swsales_banner_template'] ) ) {
			update_post_meta( $post_id, 'swsales_banner_template', sanitize_text_field( $_POST['swsales_banner_template'] ) );
		}

		if ( ! empty( $_POST['swsales_banner_title'] ) ) {
			update_post_meta( $post_id, 'swsales_banner_title', wp_kses_post( $_POST['swsales_banner_title'] ) );
		} elseif ( isset( $_POST['swsales_banner_title'] ) ) {
			update_post_meta( $post_id, 'swsales_banner_title', $post->post_title );
		}

		if ( isset( $_POST['swsales_banner_text'] ) ) {
			$post->post_content = trim( wp_kses_post( stripslashes( $_POST['swsales_banner_text'] ) ) );
			remove_action( 'save_post', array( __CLASS__, 'save_sws_metaboxes' ) );
			wp_update_post( $post, true );
			add_action( 'save_post', array( __CLASS__, 'save_sws_metaboxes' ), 10, 2 );
		}

		if ( ! empty( $_POST['swsales_link_text'] ) ) {
			update_post_meta( $post_id, 'swsales_link_text', wp_kses_post( stripslashes( $_POST['swsales_link_text'] ) ) );
		} elseif ( isset( $_POST['swsales_link_text'] ) ) {
			update_post_meta( $post_id, 'swsales_link_text', 'Buy Now' );
		}

		if ( isset( $_POST['swsales_css_option'] ) ) {
			update_post_meta( $post_id, 'swsales_css_option', wp_kses_post( stripslashes( $_POST['swsales_css_option'] ) ) );
		}

		if ( ! empty( $_POST['swsales_hide_on_checkout'] ) ) {
			update_post_meta( $post_id, 'swsales_hide_on_checkout', true );
		} elseif ( isset( $_POST['swsales_hide_on_checkout_exists'] ) ) {
			update_post_meta( $post_id, 'swsales_hide_on_checkout', false );
		}

		$options = SWSales_Settings::get_options();
		if ( isset( $_POST['swsales_set_as_sitewide_sale'] ) ) {
			$options['active_sitewide_sale_id'] = $post_id;
		} elseif ( $options['active_sitewide_sale_id'] === $post_id . '' ) {
			$options['active_sitewide_sale_id'] = false;
		}
		SWSales_Settings::save_options( $options );

		do_action( 'swsales_save_metaboxes', $post_id, $post );

		if ( isset( $_POST['swsales_preview'] ) ) {
			$url_to_open = get_home_url() . '?swsales_preview_sale_banner=' . $post_id;
			wp_redirect( esc_url_raw( $url_to_open ) );
			exit();
		}
		if ( isset( $_POST['swsales_view_reports'] ) ) {
			wp_redirect( esc_url_raw( admin_url( 'admin.php?page=pmpro-reports&report=swsales_reports' ) ) );
			exit();
		}
	}

	/**
	 * Updates Sitewide Sale's landing page id on save
	 *
	 * @param int $saveid landing page being saved.
	 */
	public static function landing_page_on_save( $saveid ) {
		if ( isset( $_REQUEST['swsales_callback'] ) ) {
			update_post_meta( intval( $_REQUEST['swsales_callback'] ), 'swsales_landing_page_post_id', $saveid );
		}
	}

	/**
	 * Redirects to Sitewide Sale after landing page is saved
	 *
	 * @param  string $location Previous redirect location.
	 * @param  int    $post_id  id of page that was edited.
	 * @return string           New redirect location
	 */
	public static function redirect_after_page_save( $location, $post_id ) {
		$post_type = get_post_type( $post_id );
		// Grab referrer url to see if it was sent there from editing a sitewide sale.
		$url = $_REQUEST['_wp_http_referer'];
		if ( 'page' === $post_type && ! empty( strpos( $url, 'swsales_callback=' ) ) ) {
			// Get id of sitewide sale to redirect to.
			$sitewide_sale_id = explode( 'swsales_callback=', $url )[1];
			$sitewide_sale_id = explode( '$', $sitewide_sale_id )[0];
			$location         = esc_url_raw( admin_url( 'post.php?post=' . $sitewide_sale_id . '&action=edit' ) );
		}
		return $location;
	}

	/**
	 * AJAX callback to create a new landing page for your sale
	 */
	public static function create_landing_page_ajax() {
		check_ajax_referer( 'swsales_create_landing_page', 'nonce' );

		$sitewide_sale_id = intval( $_REQUEST['swsales_id'] );
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

		$landing_page_title = sanitize_text_field( $_REQUEST['swsales_landing_page_title'] );
		if ( empty( $landing_page_title ) ) {
			$landing_page_title = esc_html__( 'Sitewide Sale Landing Page', 'sitewide-sales' );
		}

		$landing_page_post_id = wp_insert_post(
			array(
				'post_title'   => $landing_page_title,
				'post_content' => '[swsales]',
				'post_type'    => 'page',
				'post_status'  => 'draft',
			)
		);

		if ( empty( $landing_page_post_id ) ) {
			$r = array(
				'status' => 'error',
				'error'  => esc_html__( 'Error inserting post. Try doing it manually.', 'sitewide-sales' ),
			);
		} else {
			$r = array(
				'status' => 'success',
				'post'   => get_post( $landing_page_post_id ),
			);
		}

		echo json_encode( $r );
		exit;
	}
}
