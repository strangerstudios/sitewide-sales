<?php
namespace PMPro_Sitewide_Sales\includes\classes;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

/**
 * Handles registering banners and displaying banners on frontend.
 */
class PMPro_SWS_Banners {

	/**
	 * Adds actions
	 */
	public static function init() {
		add_action( 'wp', array( __CLASS__, 'choose_banner' ) );
		add_action( 'wp_head', array( __CLASS__, 'apply_custom_css' ), 10 );
		
		// Run some filters we like on banner content
		add_filter( 'pmpro_sws_banner_content', 'wpautop', 5, 1 );
		add_filter( 'pmpro_sws_banner_content', 'do_shortcode', 10, 1 );		
	}

	/**
	 * Gets info about available banners including name and available
	 * css selectors.
	 *
	 * @return array banner_name => array( option_title=>string, callback=>string, css_selctors=>array(strings) )
	 */
	public static function get_registered_banners() {

		$registered_banners = array(
			'top'          => array(
				'option_title'  => __( 'Yes, Top of Site', 'pmpro_sitewide_Sales' ),
				'callback'      => array( __CLASS__, 'hook_top_banner' ),
				'css_selectors' => array(
					'.pmpro_sws_banner',
					'#pmpro_sws_banner_top',
					'#pmpro_sws_banner_top h3',
					'#pmpro_sws_banner_top .pmpro_btn',
				),
			),
			'bottom'       => array(
				'option_title'  => __( 'Yes, Bottom of Site', 'pmpro_sitewide_sales' ),
				'callback'      => array( __CLASS__, 'hook_bottom_banner' ),
				'css_selectors' => array(
					'.pmpro_sws_banner',
					'#pmpro_sws_banner_bottom',
					'#pmpro_sws_banner_bottom .dismiss',
					'.pmpro_sws_banner-inner',
					'.pmpro_sws_banner-inner-left',
					'.pmpro_sws_banner-inner-left h3',
					'.pmpro_sws_banner-inner-right',
					'.pmpro_sws_banner-inner-right .pmpro_btn',
				),
			),
			'bottom_right' => array(
				'option_title'  => __( 'Yes, Bottom Right of Site', 'pmpro_sitewide_sales' ),
				'callback'      => array( __CLASS__, 'hook_bottom_right_banner' ),
				'css_selectors' => array(
					'.pmpro_sws_banner',
					'#pmpro_sws_banner_bottom_right',
					'#pmpro_sws_banner_bottom_right .dismiss',
					'#pmpro_sws_banner_bottom_right h3',
					'#pmpro_sws_banner_bottom_right .pmpro_btn',
				),
			),
		);

		/**
		 * Modify Registerted Banners
		 *
		 * @since 0.0.1
		 *
		 * @param array $registered_banners contains all currently registered banners.
		 */
		$registered_banners = apply_filters( 'pmpro_sws_registered_banners', $registered_banners );

		return $registered_banners;
	}

	/**
	 * Logic for when to show banners/which banner to show
	 */
	public static function choose_banner() {
		global $pmpro_pages;

		// bail if Paid Memberships Pro is not active
		if ( ! function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
			return;
		}

		// get some settings
		$options              = PMPro_SWS_Settings::get_options();
		$active_sitewide_sale = $options['active_sitewide_sale_id'];
		$membership_level     = pmpro_getMembershipLevelForUser();

		// are we previewing?
		$preview = false;
		if ( current_user_can( 'administrator' ) && isset( $_REQUEST['pmpro_sws_preview_sale_banner'] ) ) {
			$active_sitewide_sale = intval( $_REQUEST['pmpro_sws_preview_sale_banner'] );
			$preview              = true;
		}

		// unless we are previewing, don't show the banner on certain pages
		if ( ! $preview ) {
			// no active sale
			if ( empty( $active_sitewide_sale ) ) {
				return;
			}

			// no discount code
			$discount_code_id = get_post_meta( $active_sitewide_sale, 'pmpro_sws_discount_code_id', true );
			if ( empty( $discount_code_id ) || $discount_code_id < 0 ) {
				return;
			}

			// no landing page or on it
			$landing_page_post_id = get_post_meta( $active_sitewide_sale, 'pmpro_sws_landing_page_post_id', true );
			if ( empty( $landing_page_post_id ) || $landing_page_post_id < 0 || is_page( $landing_page_post_id ) ) {
				return;
			}

			// use banner set to false
			$use_banner = get_post_meta( $active_sitewide_sale, 'pmpro_sws_use_banner', true );
			if ( empty( $use_banner ) || 'no' === $use_banner ) {
				return;
			}

			// don't show on login page
			if ( PMPro_SWS_Setup::is_login_page() ) {
				return;
			}

			// don't show on checkout page if set that way
			$hide_on_checkout = get_post_meta( $active_sitewide_sale, 'pmpro_sws_hide_on_checkout', true );
			if ( $hide_on_checkout && is_page( $pmpro_pages['checkout'] ) ) {
				return;
			}

			// don't show banner to users of certain Levels
			$hide_for_levels = get_post_meta( $active_sitewide_sale, 'pmpro_sws_hide_for_levels', true );
			if ( ! empty( $hide_for_levels ) && ! empty( $membership_level )
				&& in_array( $membership_level->ID, $hide_for_levels ) ) {
				return;
			}

			// hide before/after the start/end dates
			$start_date = get_post_meta( $active_sitewide_sale, 'pmpro_sws_start_date', true );
			$end_date   = get_post_meta( $active_sitewide_sale, 'pmpro_sws_end_date', true );
			$today      = date( 'Y-m-d', current_time( 'timestamp' ) );
			if ( $today < $start_date || $today > $end_date ) {
				return;
			}
		}

		// Display the appropriate banner
		// get_post_meta( $active_sitewide_sale, 'use_banner', true ) will be something like top, bottom, etc.
		$registered_banners = self::get_registered_banners();
		$banner_to_use      = get_post_meta( $active_sitewide_sale, 'pmpro_sws_use_banner', true );
		if ( current_user_can( 'administrator' ) && isset( $_REQUEST['pmpro_sws_preview_banner_type'] ) ) {
			$banner_to_use = $_REQUEST['pmpro_sws_preview_banner_type'];
		}
		if ( array_key_exists( $banner_to_use, $registered_banners ) && array_key_exists( 'callback', $registered_banners[ $banner_to_use ] ) ) {
			$callback_func = $registered_banners[ $banner_to_use ]['callback'];
			if ( is_array( $callback_func ) ) {
				if ( 2 >= count( $callback_func ) && method_exists( $callback_func[0], $callback_func[1] ) && is_callable( $callback_func[0], $callback_func[1] ) ) {
					call_user_func( $callback_func[0] . '::' . $callback_func[1] );
				}
			} elseif ( is_string( $callback_func ) ) {
				if ( is_callable( $callback_func ) ) {
					call_user_func( $callback_func );
				}
			}
		}
	}

	/**
	 * Applies user's custom css to banner
	 */
	public static function apply_custom_css() {
		$options              = PMPro_SWS_Settings::get_options();
		$active_sitewide_sale = $options['active_sitewide_sale_id'];
		if ( current_user_can( 'administrator' ) && isset( $_REQUEST['pmpro_sws_preview_sale_banner'] ) ) {
			$active_sitewide_sale = $_REQUEST['pmpro_sws_preview_sale_banner'];
		}
		if ( false === $active_sitewide_sale || 'pmpro_sitewide_sale' !== get_post_type( $active_sitewide_sale ) ) {
			// $active_sitewide_sale not set or is a different post type.
			return;
		}

		$css = get_post_meta( $active_sitewide_sale, 'pmpro_sws_css_option', true )
		?>
		<!--Sitewide Sale Add On for Paid Memberships Pro Custom CSS-->
		<style type="text/css">
		<?php
		if ( ! empty( $css ) ) {
			echo $css;
		}
		?>
		</style>
		<!--/Sitewide Sale Add On for Paid Memberships Pro Custom CSS-->
		<?php
	}

	/**
	 * Sets top banner to be added
	 */
	public static function hook_top_banner() {
		add_action( 'wp_head', array( __CLASS__, 'show_top_banner' ) );
	}

	/**
	 * Adds top banner
	 */
	public static function show_top_banner() {
		$options              = PMPro_SWS_Settings::get_options();
		$active_sitewide_sale = $options['active_sitewide_sale_id'];
		if ( current_user_can( 'administrator' ) && isset( $_REQUEST['pmpro_sws_preview_sale_banner'] ) ) {
			$active_sitewide_sale = $_REQUEST['pmpro_sws_preview_sale_banner'];
		}

		// Display the wrapping div for selected template.
		if ( defined( 'MEMBERLITE_VERSION' ) || ( pmpro_getOption( 'pmpro_sws_allow_template' ) === 'Yes' ) ) {
			$banner_template = get_post_meta( $active_sitewide_sale, 'pmpro_sws_banner_template', true );
			if ( empty( $banner_template ) ) {
				$banner_template = false;
			}
		}
		/* Maybe use JavaScript here to detect the height of the bar and adjust margin-top of html elemenet. */
		?>
		<div id="pmpro_sws_banner_top" class="pmpro_sws_banner
		<?php
		if ( ! empty( $banner_template ) ) {
			echo ' pmpro_sws_banner_template-' . esc_html( $banner_template ); }
		?>
		">
			<div class="pmpro_sws_banner-inner">
				<h3><?php echo wp_kses_post( get_post_meta( $active_sitewide_sale, 'pmpro_sws_banner_title', true ) ); ?></h3>
				<?php echo apply_filters( 'pmpro_sws_banner_content', get_post_field( 'post_content', $active_sitewide_sale ) ); ?>
				<?php do_action( 'pmpro_sws__before_banner_button', $active_sitewide_sale ); ?>
				<span class="pmpro_sws_banner-button"><a class="pmpro_btn" href="<?php echo esc_url( get_permalink( get_post_meta( $active_sitewide_sale, 'pmpro_sws_landing_page_post_id', true ) ) ); ?>"><?php echo esc_html( get_post_meta( $active_sitewide_sale, 'pmpro_sws_link_text', true ) ); ?></a></span>
			</div>
		</div> <!-- end pmpro_sws_banner -->
		<?php
	}

	/**
	 * Sets bottom banner to be added
	 */
	public static function hook_bottom_banner() {
		add_action( 'wp_footer', array( __CLASS__, 'show_bottom_banner' ) );
	}

	/**
	 * Adds bottom banner
	 */
	public static function show_bottom_banner() {
		$options              = PMPro_SWS_Settings::get_options();
		$active_sitewide_sale = $options['active_sitewide_sale_id'];
		if ( current_user_can( 'administrator' ) && isset( $_REQUEST['pmpro_sws_preview_sale_banner'] ) ) {
			$active_sitewide_sale = $_REQUEST['pmpro_sws_preview_sale_banner'];
		}

		// Display the wrapping div for selected template.
		if ( defined( 'MEMBERLITE_VERSION' ) || ( pmpro_getOption( 'pmpro_sws_allow_template' ) === 'Yes' ) ) {
			$banner_template = get_post_meta( $active_sitewide_sale, 'pmpro_sws_banner_template', true );
			if ( empty( $banner_template ) ) {
				$banner_template = false;
			}
		}
		?>
		<div id="pmpro_sws_banner_bottom" class="pmpro_sws_banner
		<?php
		if ( ! empty( $banner_template ) ) {
			echo ' pmpro_sws_banner_template-' . esc_html( $banner_template ); 
		}
		?>
		">
			<div class="pmpro_sws_banner-inner">
			<a href="javascript:void(0);" onclick="document.getElementById('pmpro_sws_banner_bottom').style.display = 'none';" class="dismiss" title="Dismiss"></a>
				<div class="pmpro_sws_banner-inner-left">
					<h3><?php echo wp_kses_post( get_post_meta( $active_sitewide_sale, 'pmpro_sws_banner_title', true ) ); ?></h3>
					<?php echo apply_filters( 'pmpro_sws_banner_content', get_post_field( 'post_content', $active_sitewide_sale ) ); ?>					
				</div>
				<div class="pmpro_sws_banner-inner-right">
					<?php do_action( 'pmpro_sws__before_banner_button', $active_sitewide_sale ); ?>
					<span class="pmpro_sws_banner-button"><a class="pmpro_btn" href="<?php echo esc_url( get_permalink( get_post_meta( $active_sitewide_sale, 'pmpro_sws_landing_page_post_id', true ) ) ); ?>"><?php echo wp_kses_post( get_post_meta( $active_sitewide_sale, 'pmpro_sws_link_text', true ) ); ?></a></span>
				</div>
			</div> <!-- end pmpro_sws_banner-inner -->
		</div> <!-- end pmpro_sws_banner -->
		<?php
	}

	/**
	 * Sets bottom right banner to be added
	 */
	public static function hook_bottom_right_banner() {
		add_action( 'wp_footer', array( __CLASS__, 'show_bottom_right_banner' ) );
	}

	/**
	 * Adds bottom right banner
	 */
	public static function show_bottom_right_banner() {
		$options              = PMPro_SWS_Settings::get_options();
		$active_sitewide_sale = $options['active_sitewide_sale_id'];
		if ( current_user_can( 'administrator' ) && isset( $_REQUEST['pmpro_sws_preview_sale_banner'] ) ) {
			$active_sitewide_sale = $_REQUEST['pmpro_sws_preview_sale_banner'];
		}

		// Display the wrapping div for selected template.
		if ( defined( 'MEMBERLITE_VERSION' ) || ( pmpro_getOption( 'pmpro_sws_allow_template' ) === 'Yes' ) ) {
			$banner_template = get_post_meta( $active_sitewide_sale, 'pmpro_sws_banner_template', true );
			if ( empty( $banner_template ) ) {
				$banner_template = false;
			}
		}
		?>
		<div id="pmpro_sws_banner_bottom_right" class="pmpro_sws_banner
		<?php
		if ( ! empty( $banner_template ) ) {
			echo ' pmpro_sws_banner_template-' . esc_html( $banner_template ); }
		?>
		">
			<div class="pmpro_sws_banner-inner">
				<a href="javascript:void(0);" onclick="document.getElementById('pmpro_sws_banner_bottom_right').style.display = 'none';" class="dismiss" title="Dismiss"></a>
				<h3><?php echo wp_kses_post( get_post_meta( $active_sitewide_sale, 'pmpro_sws_banner_title', true ) ); ?></h3>
				<?php echo apply_filters( 'pmpro_sws_banner_content', get_post_field( 'post_content', $active_sitewide_sale ) ); ?>
			</div> <!-- end pmpro_sws_banner-inner -->
			<?php do_action( 'pmpro_sws__before_banner_button', $active_sitewide_sale ); ?>
			<span class="pmpro_sws_banner-button"><a class="pmpro_btn" href="<?php echo esc_url( get_permalink( get_post_meta( $active_sitewide_sale, 'pmpro_sws_landing_page_post_id', true ) ) ); ?>"><?php echo wp_kses_post( get_post_meta( $active_sitewide_sale, 'pmpro_sws_link_text', true ) ); ?></a></span>
		</div> <!-- end pmpro_sws_banner -->
		<?php
	}
}
