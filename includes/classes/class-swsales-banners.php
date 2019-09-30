<?php
namespace Sitewide_Sales\includes\classes;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

/**
 * Handles registering banners and displaying banners on frontend.
 */
class SWSales_Banners {

	/**
	 * Adds actions
	 */
	public static function init() {
		add_action( 'wp', array( __CLASS__, 'choose_banner' ) );
		add_action( 'wp_head', array( __CLASS__, 'apply_custom_css' ), 10 );

		// Run some filters we like on banner content
		add_filter( 'sws_banner_content', 'wpautop', 5, 1 );
		add_filter( 'sws_banner_content', 'do_shortcode', 10, 1 );
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
				'option_title'  => __( 'Yes, Top of Site', 'sitewide_Sales' ),
				'callback'      => array( __CLASS__, 'hook_top_banner' ),
				'css_selectors' => array(
					'.swsales_banner',
					'#swsales_banner_top',
					'#swsales_banner_top h3',
					'#swsales_banner_top .swsales_btn',
				),
			),
			'bottom'       => array(
				'option_title'  => __( 'Yes, Bottom of Site', 'sitewide-sales' ),
				'callback'      => array( __CLASS__, 'hook_bottom_banner' ),
				'css_selectors' => array(
					'.swsales_banner',
					'#swsales_banner_bottom',
					'#swsales_banner_bottom .dismiss',
					'.swsales_banner-inner',
					'.swsales_banner-inner-left',
					'.swsales_banner-inner-left h3',
					'.swsales_banner-inner-right',
					'.swsales_banner-inner-right .swsales_btn',
				),
			),
			'bottom_right' => array(
				'option_title'  => __( 'Yes, Bottom Right of Site', 'sitewide-sales' ),
				'callback'      => array( __CLASS__, 'hook_bottom_right_banner' ),
				'css_selectors' => array(
					'.swsales_banner',
					'#swsales_banner_bottom_right',
					'#swsales_banner_bottom_right .dismiss',
					'#swsales_banner_bottom_right h3',
					'#swsales_banner_bottom_right .swsales_btn',
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
		$registered_banners = apply_filters( 'swsales_registered_banners', $registered_banners );

		return $registered_banners;
	}

	/**
	 * Logic for when to show banners/which banner to show
	 */
	public static function choose_banner() {
		// are we previewing?
		$preview = false;
		if ( current_user_can( 'administrator' ) && isset( $_REQUEST['swsales_preview_sale_banner'] ) ) {
			$active_sitewide_sale = SWSales_Sitewide_Sale::get_sitewide_sale( intval( $_REQUEST['swsales_preview_sale_banner'] ) );
			$preview              = true;
		} else {
			$active_sitewide_sale = SWSales_Sitewide_Sale::get_active_sitewide_sale();
		}

		if ( null === $active_sitewide_sale ) {
			return;
		}

		// unless we are previewing, don't show the banner on certain pages
		if ( ! $preview ) {

			// no landing page or on it
			$landing_page_post_id = $active_sitewide_sale->get_landing_page_post_id();
			if ( empty( $landing_page_post_id ) || $landing_page_post_id < 0 || is_page( $landing_page_post_id ) ) {
				return;
			}

			// use banner set to no
			if ( 'no' === $active_sitewide_sale->get_use_banner() ) {
				return;
			}

			// don't show on login page
			if ( SWSales_Setup::is_login_page() ) {
				return;
			}

			// don't show on checkout page if set that way
			$hide_on_checkout = $active_sitewide_sale->get_hide_on_chechout();
			// TODO: Get checkout page for current module
			//if ( $hide_on_checkout && is_page( $pmpro_pages['checkout'] ) ) {
			//	return;
			//}

			// hide before/after the start/end dates
			if ( $active_sitewide_sale->is_running() ) {
				return;
			}

			// Show banner filter
			// TODO: Pass more parameters into filter, such as not having a discount code
			if ( ! apply_filters( 'swsales_show_banner', true ) ) {
				return;
			}
		}
		// Display the appropriate banner
		// get_post_meta( $active_sitewide_sale, 'use_banner', true ) will be something like top, bottom, etc.
		$registered_banners = self::get_registered_banners();
		$banner_to_use      = $active_sitewide_sale->get_use_banner();
		if ( current_user_can( 'administrator' ) && isset( $_REQUEST['swsales_preview_banner_type'] ) ) {
			$banner_to_use = $_REQUEST['swsales_preview_banner_type'];
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
		if ( current_user_can( 'administrator' ) && isset( $_REQUEST['swsales_preview_sale_banner'] ) ) {
			$active_sitewide_sale = SWSales_Sitewide_Sale::get_sitewide_sale( intval( $_REQUEST['swsales_preview_sale_banner'] ) );
		} else {
			$active_sitewide_sale = SWSales_Sitewide_Sale::get_active_sitewide_sale();
		}

		if ( null === $active_sitewide_sale ) {
			// $active_sitewide_sale not set or is a different post type.
			return;
		}

		$css = $active_sitewide_sale->get_css_option();
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
		if ( current_user_can( 'administrator' ) && isset( $_REQUEST['swsales_preview_sale_banner'] ) ) {
			$active_sitewide_sale = SWSales_Sitewide_Sale::get_sitewide_sale( intval( $_REQUEST['swsales_preview_sale_banner'] ) );
		} else {
			$active_sitewide_sale = SWSales_Sitewide_Sale::get_active_sitewide_sale();
		}

		// Display the wrapping div for selected template.
		// if ( defined( 'MEMBERLITE_VERSION' ) || ( pmpro_getOption( 'swsales_allow_template' ) === 'Yes' ) ) {
		if ( defined( 'MEMBERLITE_VERSION' ) || true ) {
			$banner_template = $active_sitewide_sale->get_banner_template();
			if ( empty( $banner_template ) ) {
				$banner_template = false;
			}
		}
		/* Maybe use JavaScript here to detect the height of the bar and adjust margin-top of html elemenet. */
		?>
		<div id="swsales_banner_top" class="swsales_banner
		<?php
		if ( ! empty( $banner_template ) ) {
			echo ' swsales_banner_template-' . esc_html( $banner_template ); }
		?>
		">
			<div class="swsales_banner-inner">
				<h3><?php echo wp_kses_post( $active_sitewide_sale->get_banner_title() ); ?></h3>
				<?php echo apply_filters( 'swsales_banner_content', get_post_field( 'post_content', $active_sitewide_sale ) ); ?>
				<?php do_action( 'swsales__before_banner_button', $active_sitewide_sale ); ?>
				<span class="swsales_banner-button"><a class="swsales_btn" href="<?php echo esc_url( get_permalink( $active_sitewide_sale->get_landing_page_post_id() ) ); ?>"><?php echo esc_html( $active_sitewide_sale->get_link_text() ); ?></a></span>
			</div>
		</div> <!-- end swsales_banner -->
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
		if ( current_user_can( 'administrator' ) && isset( $_REQUEST['swsales_preview_sale_banner'] ) ) {
			$active_sitewide_sale = SWSales_Sitewide_Sale::get_sitewide_sale( intval( $_REQUEST['swsales_preview_sale_banner'] ) );
		} else {
			$active_sitewide_sale = SWSales_Sitewide_Sale::get_active_sitewide_sale();
		}

		// Display the wrapping div for selected template.
		// if ( defined( 'MEMBERLITE_VERSION' ) || ( pmpro_getOption( 'swsales_allow_template' ) === 'Yes' ) ) {
		if ( defined( 'MEMBERLITE_VERSION' ) || true ) {
			$banner_template = $active_sitewide_sale->get_banner_template();
			if ( empty( $banner_template ) ) {
				$banner_template = false;
			}
		}
		?>
		<div id="swsales_banner_bottom" class="swsales_banner
		<?php
		if ( ! empty( $banner_template ) ) {
			echo ' swsales_banner_template-' . esc_html( $banner_template );
		}
		?>
		">
			<div class="swsales_banner-inner">
			<a href="javascript:void(0);" onclick="document.getElementById('swsales_banner_bottom').style.display = 'none';" class="dismiss" title="Dismiss"></a>
				<div class="swsales_banner-inner-left">
					<h3><?php echo wp_kses_post( $active_sitewide_sale->get_banner_title() ); ?></h3>
					<?php echo apply_filters( 'swsales_banner_content', get_post_field( 'post_content', $active_sitewide_sale ) ); ?>					
				</div>
				<div class="swsales_banner-inner-right">
					<?php do_action( 'swsales__before_banner_button', $active_sitewide_sale ); ?>
					<span class="swsales_banner-button"><a class="swsales_btn" href="<?php echo esc_url( get_permalink( $active_sitewide_sale->get_landing_page_post_id() ) ); ?>"><?php echo wp_kses_post( $active_sitewide_sale->get_link_text() ); ?></a></span>
				</div>
			</div> <!-- end swsales_banner-inner -->
		</div> <!-- end swsales_banner -->
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
		if ( current_user_can( 'administrator' ) && isset( $_REQUEST['swsales_preview_sale_banner'] ) ) {
			$active_sitewide_sale = SWSales_Sitewide_Sale::get_sitewide_sale( intval( $_REQUEST['swsales_preview_sale_banner'] ) );
		} else {
			$active_sitewide_sale = SWSales_Sitewide_Sale::get_active_sitewide_sale();
		}

		// Display the wrapping div for selected template.
		// if ( defined( 'MEMBERLITE_VERSION' ) || ( pmpro_getOption( 'swsales_allow_template' ) === 'Yes' ) ) {
		if ( defined( 'MEMBERLITE_VERSION' ) || true ) {
			$banner_template = $active_sitewide_sale->get_banner_template();
			if ( empty( $banner_template ) ) {
				$banner_template = false;
			}
		}
		?>
		<div id="swsales_banner_bottom_right" class="swsales_banner
		<?php
		if ( ! empty( $banner_template ) ) {
			echo ' swsales_banner_template-' . esc_html( $banner_template ); }
		?>
		">
			<div class="swsales_banner-inner">
				<a href="javascript:void(0);" onclick="document.getElementById('swsales_banner_bottom_right').style.display = 'none';" class="dismiss" title="Dismiss"></a>
				<h3><?php echo wp_kses_post( $active_sitewide_sale->get_banner_title() ); ?></h3>
				<?php echo apply_filters( 'swsales_banner_content', $active_sitewide_sale->get_banner_text() ); ?>
			</div> <!-- end swsales_banner-inner -->
			<?php do_action( 'swsales__before_banner_button', $active_sitewide_sale ); ?>
			<span class="swsales_banner-button"><a class="swsales_btn" href="<?php echo esc_url( get_permalink( $active_sitewide_sale->get_landing_page_post_id() ) ); ?>"><?php echo wp_kses_post( $active_sitewide_sale->get_link_text() ); ?></a></span>
		</div> <!-- end swsales_banner -->
		<?php
	}
}
