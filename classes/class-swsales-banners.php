<?php
namespace Sitewide_Sales\classes;

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
					'#swsales-banner-top',
					'#swsales-banner-top .swsales-banner-title',
					'#swsales-banner-top .swsales-banner-content',
					'#swsales-banner-top .swsales-banner-button-wrap',
					'#swsales-banner-top .swsales-banner-button',
				),
			),
			'bottom'       => array(
				'option_title'  => __( 'Yes, Bottom of Site', 'sitewide-sales' ),
				'callback'      => array( __CLASS__, 'hook_bottom_banner' ),
				'css_selectors' => array(
					'#swsales-banner-bottom',
					'#swsales-banner-bottom .swsales-dismiss',
					'#swsales-banner-bottom .swsales-banner-title',
					'#swsales-banner-bottom .swsales-banner-content',
					'#swsales-banner-bottom .swsales-banner-button-wrap',
					'#swsales-banner-bottom .swsales-banner-button',
					'#swsales-banner-bottom .swsales-banner-inner',
					'#swsales-banner-bottom .swsales-banner-inner-left',
					'#swsales-banner-bottom .swsales-banner-inner-right',
				),
			),
			'bottom_right' => array(
				'option_title'  => __( 'Yes, Bottom Right of Site', 'sitewide-sales' ),
				'callback'      => array( __CLASS__, 'hook_bottom_right_banner' ),
				'css_selectors' => array(
					'#swsales-banner-bottom-right',
					'#swsales-banner-bottom-right .swsales-dismiss',
					'#swsales-banner-bottom-right .swsales-banner-title',
					'#swsales-banner-bottom-right .swsales-banner-content',
					'#swsales-banner-bottom-right .swsales-banner-button-wrap',
					'#swsales-banner-bottom-right .swsales-banner-button',
				),
			),
		);

		/**
		 * Modify Registered Banners
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

		// Unless we are previewing, don't show the banner on certain pages.
		if ( ! $preview ) {

			// Sale doesn't have landing page, or user is on landing page.
			$landing_page_post_id = $active_sitewide_sale->get_landing_page_post_id();
			if ( empty( $landing_page_post_id ) || $landing_page_post_id < 0 || is_page( $landing_page_post_id ) ) {
				return;
			}

			// Use banner set to no.
			if ( 'no' === $active_sitewide_sale->get_use_banner() ) {
				return;
			}

			// Don't show on login page.
			if ( SWSales_Setup::is_login_page() ) {
				return;
			}

			// Don't show on checkout page if option is set and user is on checkout page.
			if ( $active_sitewide_sale->get_hide_on_checkout() && apply_filters( 'swsales_is_checkout_page', false, $active_sitewide_sale ) ) {
				return;
			}

			// Hide before/after the start/end dates.
			if ( ! $active_sitewide_sale->is_running() ) {
				return;
			}

			// Show banner filter.
			if ( ! apply_filters( 'swsales_show_banner', true, $active_sitewide_sale ) ) {
				return;
			}
		}
		// Display the appropriate banner
		// get_post_meta( $active_sitewide_sale, 'use_banner', true ) will be something like top, bottom, etc.
		$registered_banners = self::get_registered_banners();
		$banner_to_use      = $active_sitewide_sale->get_use_banner();
		if ( current_user_can( 'administrator' ) && isset( $_REQUEST['swsales_preview_sale_banner_type'] ) ) {
			$banner_to_use = $_REQUEST['swsales_preview_sale_banner_type'];
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

		/* Maybe use JavaScript here to detect the height of the bar and adjust margin-top of html element. */
		ob_start(); 
		?>
		<div id="swsales-banner-top" class="swsales-banner">
			<div class="swsales-banner-inner">
				<p class="swsales-banner-title"><?php echo wp_kses_post( $active_sitewide_sale->get_banner_title() ); ?></p>
				<p class="swsales-banner-content"><?php echo apply_filters( 'swsales_banner_text', $active_sitewide_sale->get_banner_text(), 'top', $active_sitewide_sale ); ?></p>
				<?php do_action( 'swsales_before_banner_button', $active_sitewide_sale ); ?>
				<span class="swsales-banner-button-wrap"><a class="swsales-banner-button" href="<?php echo esc_url( get_permalink( $active_sitewide_sale->get_landing_page_post_id() ) ); ?>"><?php echo wp_kses_post( $active_sitewide_sale->get_link_text() ); ?></a></span>
			</div> <!-- end swsales-banner-inner -->
		</div> <!-- end swsales-banner -->
		<?php

		$content = ob_get_contents();
		ob_end_clean();

		// Filter for templates to modify the banner content.
		$banner_template = $active_sitewide_sale->get_banner_template();
		if ( ! empty( $banner_template ) ) {
			$content = apply_filters( 'swsales_banner_content_' . $banner_template, $content, 'top' );
		}

		// Filter for themes and plugins to modify the banner content.
		$content = apply_filters( 'swsales_banner_content', $content, $banner_template, 'top' );

		// Echo the banner content.	
		echo $content;
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

		ob_start(); 
		?>
		<div id="swsales-banner-bottom" class="swsales-banner">
			<div class="swsales-banner-inner">
				<a href="javascript:void(0);" onclick="document.getElementById('swsales-banner-bottom').style.display = 'none';" class="swsales-dismiss" title="Dismiss"></a>
				<div class="swsales-banner-inner-left">
					<p class="swsales-banner-title"><?php echo wp_kses_post( $active_sitewide_sale->get_banner_title() ); ?></p>
					<p class="swsales-banner-content"><?php echo apply_filters( 'swsales_banner_text', $active_sitewide_sale->get_banner_text(), 'bottom', $active_sitewide_sale ); ?></p>
				</div>
				<div class="swsales-banner-inner-right">
					<?php do_action( 'swsales_before_banner_button', $active_sitewide_sale ); ?>
					<span class="swsales-banner-button-wrap"><a class="swsales-banner-button" href="<?php echo esc_url( get_permalink( $active_sitewide_sale->get_landing_page_post_id() ) ); ?>"><?php echo wp_kses_post( $active_sitewide_sale->get_link_text() ); ?></a></span>
				</div>
			</div> <!-- end swsales-banner-inner -->
		</div> <!-- end swsales-banner -->
		<?php

		$content = ob_get_contents();
		ob_end_clean();

		// Filter for templates to modify the banner content.
		$banner_template = $active_sitewide_sale->get_banner_template();
		if ( ! empty( $banner_template ) ) {
			$content = apply_filters( 'swsales_banner_content_' . $banner_template, $content, 'bottom' );
		}

		// Filter for themes and plugins to modify the banner content.
		$content = apply_filters( 'swsales_banner_content', $content, $banner_template, 'bottom' );

		// Echo the banner content.	
		echo $content;
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

		ob_start(); 
		?>
		<div id="swsales-banner-bottom-right" class="swsales-banner">
			<div class="swsales-banner-inner">
				<a href="javascript:void(0);" onclick="document.getElementById('swsales-banner-bottom-right').style.display = 'none';" class="swsales-dismiss" title="Dismiss"></a>
				<p class="swsales-banner-title"><?php echo wp_kses_post( $active_sitewide_sale->get_banner_title() ); ?></p>
				<p class="swsales-banner-content"><?php echo apply_filters( 'swsales_banner_text', $active_sitewide_sale->get_banner_text(), 'bottom_right', $active_sitewide_sale ); ?></p>
			</div> <!-- end swsales-banner-inner -->
			<?php do_action( 'swsales_before_banner_button', $active_sitewide_sale ); ?>
			<span class="swsales-banner-button-wrap"><a class="swsales-banner-button" href="<?php echo esc_url( get_permalink( $active_sitewide_sale->get_landing_page_post_id() ) ); ?>"><?php echo wp_kses_post( $active_sitewide_sale->get_link_text() ); ?></a></span>
		</div> <!-- end swsales-banner -->
		<?php

		$content = ob_get_contents();
		ob_end_clean();

		// Filter for templates to modify the banner content.
		$banner_template = $active_sitewide_sale->get_banner_template();
		if ( ! empty( $banner_template ) ) {
			$content = apply_filters( 'swsales_banner_content_' . $banner_template, $content, 'bottom_right' );
		}

		// Filter for themes and plugins to modify the banner content.
		$content = apply_filters( 'swsales_banner_content', $content, $banner_template, 'bottom_right' );

		// Echo the banner content.	
		echo $content;
	}
}
