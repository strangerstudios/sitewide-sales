<?php

namespace PMPro_Sitewide_Sales\includes\classes;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

class PMPro_SWS_Setup {
	/**
	 *
	 * Initial plugin setup
	 *
	 * @package pmpro-sitewide-sale/includes
	 */
	public static function init() {
		register_activation_hook( PMPROSWS_BASENAME, array( __CLASS__, 'pmpro_sws_admin_notice_activation_hook' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'pmpro_sws_admin_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'pmpro_sws_frontend_scripts' ) );
		add_filter( 'plugin_row_meta', array( __CLASS__, 'pmpro_sws_plugin_row_meta' ), 10, 2 );
		add_filter( 'plugin_action_links_' . PMPROSWS_BASENAME, array( __CLASS__, 'pmpro_sws_plugin_action_links' ) );
		add_action( 'admin_notices', array( __CLASS__, 'pmpro_sws_admin_notice' ) );
	}

	/**
	 * Enqueues selectWoo
	 */
	public static function pmpro_sws_admin_scripts() {
		$screen = get_current_screen();

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_register_script( 'selectWoo', plugins_url( 'includes/js/selectWoo.full' . $suffix . '.js', PMPROSWS_BASENAME ), array( 'jquery' ), '1.0.4' );
		wp_enqueue_script( 'selectWoo' );
		wp_register_style( 'selectWooCSS', plugins_url( 'includes/css/selectWoo' . $suffix . '.css', PMPROSWS_BASENAME ) );
		wp_enqueue_style( 'selectWooCSS' );

		wp_register_style( 'pmpro-sitewide-sales_admin', plugins_url( 'includes/css/admin.css', PMPROSWS_BASENAME ), null, PMPROSWS_VERSION );
		wp_enqueue_style( 'pmpro-sitewide-sales_admin' );
	}

	/**
	 * Enqueues frontend stylesheet.
	 */
	public static function pmpro_sws_frontend_scripts() {
		wp_register_style( 'pmpro-sitewide-sales_frontend', plugins_url( 'includes/css/frontend.css', PMPROSWS_BASENAME ), null, PMPROSWS_VERSION );
		wp_enqueue_style( 'pmpro-sitewide-sales_frontend' );
		
		/*
		 * Load Google Fonts depending on selected template.
		 */
		// See if any Sitewide Sale CPTs have this post ID set as the Landing Page.
		$sitewide_sale_id = get_post_meta( get_queried_object_id(), 'pmpro_sws_sitewide_sale_id', true );
		
		if ( ! empty( $sitewide_sale_id ) ) {
			// Check the landing page custom template, add the custom Google Font.
			$landing_template = get_post_meta( $sitewide_sale_id, 'pmpro_sws_landing_page_template', true );
			if ( ! empty( $landing_template ) ) {
				if ( $landing_template === 'vintage' ) {
					$query_args = array(
						'family' => 'Lobster',
						'subset' => 'latin,latin-ext'
					);
					wp_enqueue_style( 'pmprosws_google_font_lobster', add_query_arg( $query_args, "//fonts.googleapis.com/css" ), array(), null );
				}
			}
		}

		// See if there is an active Sitewide Sale and the banner needs a Google Font.
		$options              = PMPro_SWS_Settings::get_options();
		$active_sitewide_sale_id = $options['active_sitewide_sale_id'];
		
		if ( ! empty( $active_sitewide_sale_id ) ) {
			$banner_template = get_post_meta( $active_sitewide_sale_id, 'pmpro_sws_banner_template', true );
			if ( ! empty( $banner_template ) ) {
				if ( $banner_template === 'vintage' ) {
					$query_args = array(
						'family' => 'Lobster',
						'subset' => 'latin,latin-ext'
					);
					wp_enqueue_style( 'pmprosws_google_font_lobster', add_query_arg( $query_args, "//fonts.googleapis.com/css" ), null, PMPROSWS_VERSION );
				}
			}
		}
	}

	/**
	 * Runs only when the plugin is activated.
	 *
	 * @since 0.0.0
	 */
	public static function pmpro_sws_admin_notice_activation_hook() {
		// Create transient data.
		set_transient( 'pmpro-sws-admin-notice', true, 5 );
	}

	/**
	 * Returns if the user is on the login page (currently works for TML)
	 * Can probably switch to is_login_page from PMPro core
	 */
	public static function is_login_page() {
		global $post;
		$slug = get_site_option( 'tml_login_slug' );
		if ( false === $slug ) {
			$slug = 'login';
		}
		return ( ( ! empty( $post->post_name ) && $slug === $post->post_name ) || is_page( 'login' ) || in_array( $GLOBALS['pagenow'], array( 'wp-login.php', 'wp-register.php' ) ) );
	}

	/**
	 * Return an array of PMPro membership level ids for paid levels only
	 */
	public static function get_paid_level_ids() {
		static $paid_level_ids;

		if ( ! isset( $paid_levels ) && function_exists( 'pmpro_getAllLevels' ) ) {
			$all_levels     = pmpro_getAllLevels( true, true );
			$paid_level_ids = array();
			foreach ( $all_levels as $level ) {
				if ( ! pmpro_isLevelFree( $level ) ) {
					$paid_level_ids[] = $level->id;
				}
			}
		}

		return $paid_level_ids;
	}

	/**
	 * Returns true of there are any posts of type sitewide_sale, false otherwise.
	 */
	public static function has_sitewide_sales() {
		global $wpdb;
		$sale_id = $wpdb->get_var(
			"SELECT *
									FROM $wpdb->posts
									WHERE post_type = 'pmpro_sitewide_sale'
										AND post_status <> 'auto-draft'
									LIMIT 1"
		);
		if ( ! empty( $sale_id ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Admin Notice on Activation.
	 *
	 * @since 0.1.0
	 */
	public static function pmpro_sws_admin_notice() {
		// Check transient, if available display notice.
		if ( get_transient( 'pmpro-sws-admin-notice' ) ) { ?>
			<div class="updated notice is-dismissible">
				<p>
				<?php
					global $wpdb;
					$has_sws_post = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_type = 'pmpro_sitewide_sale' LIMIT 1" );
				if ( $has_sws_post ) {
					printf( __( 'Thank you for activating. You can <a href="%s">view your Sitewide Sales here</a>.', 'pmpro-sitewide-sales' ), get_admin_url( null, 'edit.php?post_type=pmpro_sitewide_sale' ) );
				} else {
					printf( __( 'Thank you for activating. You can <a href="%s">create your first Sitewide Sale here</a>.', 'pmpro-sitewide-sales' ), get_admin_url( null, 'post-new.php?post_type=pmpro_sitewide_sale' ) );
				}
				?>
				</p>
			</div>
			<?php
			// Delete transient, only display this notice once.
			delete_transient( 'pmpro-sws-admin-notice' );
		}
	}

	/**
	 * Function to add links to the plugin action links
	 *
	 * @param array $links Array of links to be shown in plugin action links.
	 */
	public static function pmpro_sws_plugin_action_links( $links ) {
		if ( current_user_can( 'manage_options' ) ) {
			$new_links = array(
				'<a href="' . get_admin_url( null, 'edit.php?post_type=pmpro_sitewide_sale' ) . '">' . __( 'View Sitewide Sales', 'pmpro-sitewide-sales' ) . '</a>',
			);
		}
		return array_merge( $new_links, $links );
	}

	/**
	 * Function to add links to the plugin row meta
	 *
	 * @param array  $links Array of links to be shown in plugin meta.
	 * @param string $file Filename of the plugin meta is being shown for.
	 */
	public static function pmpro_sws_plugin_row_meta( $links, $file ) {
		if ( strpos( $file, 'pmpro-sitewide-sale.php' ) !== false ) {
			$new_links = array(
				'<a href="' . esc_url( 'https://www.paidmembershipspro.com/add-ons/sitewide-sales/' ) . '" title="' . esc_attr( __( 'View Documentation', 'pmpro' ) ) . '">' . __( 'Docs', 'pmpro-sitewide-sales' ) . '</a>',
				'<a href="' . esc_url( 'https://www.paidmembershipspro.com/support/' ) . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'pmpro' ) ) . '">' . __( 'Support', 'pmpro-sitewide-sales' ) . '</a>',
			);
			$links     = array_merge( $links, $new_links );
		}
		return $links;
	}

}
