<?php

namespace Sitewide_Sales\includes\classes;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

class SWSales_About {

	/**
	 * Adds actions for class
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_about_page' ) );
	}

	public static function add_about_page() {
		add_submenu_page(
			'edit.php?post_type=sitewide_sale',
			__( 'About', 'sitewide-sales' ),
			__( 'About', 'sitewide-sales' ),
			'manage_options',
			'sitewide_sales_about',
			array( __CLASS__, 'show_about_page' )
		);
	}

	public static function show_about_page() { ?>
		<div class="wrap sitewide_sales_admin">
			<div class="sitewide_sales_banner">
				<a class="sitewide_sales_logo" title="<?php esc_attr_e( 'Sitewide Sales', 'sitewide-sales' ); ?>" target="_blank" href="https://sitewidesales.com/?utm_source=plugin&utm_medium=sitewide-sales-reports&utm_campaign=homepage"><img src="<?php echo esc_url( plugins_url( 'images/Sitewide-Sales.png', SWSALES_BASENAME ) ); ?>" border="0" alt="<?php esc_attr_e( 'Sitewide Sales(c) - All Rights Reserved', 'sitewide-sales' ); ?>" /></a>
				<div class="sitewide_sales_meta">
					<span class="sitewide_sales_version">v<?php echo SWSALES_VERSION; ?></span>
					<a href="https://www.strangerstudios.com/wordpress-plugins/sitewide-sales/documentation/?utm_source=plugin&utm_medium=swsales-admin-header&utm_campaign=documentation" target="_blank" title="<?php esc_attr_e( 'Documentation', 'sitewide-sales' ); ?>"><?php esc_html_e( 'Documentation', 'sitewide-sales' ); ?></a>
					<a href="https://www.strangerstudios.com/wordpress-plugins/sitewide-sales/documentation/support/?utm_source=plugin&utm_medium=swsales-admin-header&utm_campaign=support" target="_blank" title="<?php esc_attr_e( 'Get Support', 'sitewide-sales' );?>"><?php esc_html_e( 'Get Support', 'sitewide-sales' );?></a>
					<?php if ( sws_license_is_valid() ) { ?>
						<?php printf(__( '<a class="sws_license_tag sws_license_tag-valid" href="%s">Valid License</a>', 'sitewide-sales' ), admin_url( 'edit.php?post_type=sitewide_sale&page=sitewide_sales_license' ) ); ?>
					<?php } elseif ( ! defined( 'SWS_LICENSE_NAG' ) || SWS_LICENSE_NAG == true ) { ?>
						<?php printf(__( '<a class="sws_license_tag sws_license_tag-invalid" href="%s">No License</a>', 'sitewide-sales' ), admin_url('edit.php?post_type=sitewide_sale&page=sitewide_sales_license' ) ); ?>
					<?php } ?>

				</div>
			</div>
			
			<div class="sws-wrap">
				<h2><?php esc_html_e( 'About Sitewide Sales', 'sitewide-sales' ); ?></h2>
				<div class="sws-text">
					<?php
						$allowed_html = array (
							'a' => array (
								'href' => array(),
								'target' => array(),
								'title' => array(),
							),
							'strong' => array(),
							'em' => array(),		);
					?>

					<?php
						echo '<p>' . sprintf( wp_kses( __( 'Sitewide Sales helps you run Black Friday, Cyber Monday, or other flash sales on your WordPress-powered eCommerce or membership site. We currently offer integration for <a href="%s" title="Paid Memberships Pro" target="_blank">Paid Memberships Pro</a> and <a href="%s" title="WooCommerce" target="_blank">WooCommerce</a>.', 'sitewide-sales' ), $allowed_html ), 'https://www.paidmembershipspro.com/?utm_source=sitewide-sales&utm_medium=about&utm_campaign=homepage', 'https://woocommerce.com' ) . '</p>';
					?>

					<?php
						echo '<h3>' . esc_html( 'Getting Started', 'sitewide-sales' ) . '</h3>';
					?>

					<img class="sitewide_sales_icon alignright" src="<?php echo esc_url( plugins_url( 'images/Sitewide-Sales_icon.png', SWSALES_BASENAME ) ); ?>" border="0" alt="<?php esc_attr_e( 'Sitewide Sales(c) - All Rights Reserved', 'sitewide-sales' ); ?>" />

					<?php
						echo '<p>' . wp_kses( __( 'This plugin handles your banners, notification bars, landing pages, and reporting. Running a sale like this used to require three or more separate plugins. Now you can run your sale with a single tool. At the same time, the Sitewide Sales plugin is flexible enough that you can use specific banner and landing page plugins if wanted.', 'sitewide-sales' ), $allowed_html ) . '</p>';
					?>

					<?php
						echo '<p>' . wp_kses( __( 'Before you get started, take some time thinking about the sale you will set up. This important first step will help you run a structured, well designed sale and will significantly cut down on the setup time. Some things to consider include:', 'sitewide-sales' ), $allowed_html ) . '</p>';
					?>

					<ul class="ul-disc">
						<li><?php esc_html_e( 'What is the main purpose for your sale?', 'sitewide-sales' ); ?></li>
						<li><?php esc_html_e( 'What date will your sale begin?', 'sitewide-sales' ); ?></li>
						<li><?php esc_html_e( 'When will your sale end?', 'sitewide-sales' ); ?></li>
						<li><?php esc_html_e( 'If you\'re running a sale on products, which products will your discount apply for?', 'sitewide-sales' ); ?></li>
						<li><?php esc_html_e( 'If you\'re running a sale on membership, which existing members (if any) do you want to know about the sale?', 'sitewide-sales' ); ?></li>
						<li><?php esc_html_e( 'What general look and feel do you want to use as part of the marketing surrounding your sale?', 'sitewide-sales' ); ?></li>
					</ul>

					<h3><?php esc_html_e( "We're here to help make your sale a measurable success.", 'sitewide-sales' ); ?></h3>

					<?php
						echo '<p>' . wp_kses( __( 'Check out the Sitewide Sales documentation site for additional setup instructions, sample landing page and banner content, as well as developer documentation to further extend the templates, reporting, and integration options.', 'sitewide-sales' ), $allowed_html ) . '</p>';
					?>

					<p><a href="https://www.strangerstudios.com/wordpress-plugins/sitewide-sales/documentation/?utm_source=plugin&utm_medium=swsales-about&utm_campaign=documentation" target="_blank" title="<?php esc_attr_e( 'Documentation', 'sitewide-sales' ); ?>"><?php esc_html_e( 'Documentation', 'sitewide-sales' ); ?></a> | <a href="https://www.strangerstudios.com/wordpress-plugins/sitewide-sales/documentation/support/?utm_source=plugin&utm_medium=swsales-about&utm_campaign=support" target="_blank" title="<?php esc_attr_e( 'View Support Options &raquo;', 'sitewide-sales' ); ?>"><?php esc_html_e( 'View Support Options &raquo;', 'sitewide-sales' ); ?></a></p>

				</div> <!-- end about-text -->
			</div> <!-- end about-wrap -->

		</div> <!-- sitewide-sales_admin -->
		<?php
	}
}
