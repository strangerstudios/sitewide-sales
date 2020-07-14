<?php

namespace Sitewide_Sales\includes\classes;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

class SWSales_License{

    public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_license_page' ) );
    }

    public static function add_license_page() {
        add_submenu_page(
			'edit.php?post_type=sitewide_sale',
			__( 'License', 'sitewide-sales' ),
			__( 'License', 'sitewide-sales' ),
			'manage_options',
			'sitewide_sales_license',
			array( __CLASS__, 'show_license_page' )
		);
    }

    public static function show_license_page() {

        //only let admins get here
        if ( ! function_exists( 'current_user_can' ) || ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'sws_license') ) ) {
            die( __( 'You do not have permissions to perform this action.', 'paid-memberships-pro' ) );
        } ?>
        <div class="wrap sitewide_sales_admin">
			<div class="sitewide_sales_banner">
				<a class="sitewide_sales_logo" title="<?php esc_attr_e( 'Sitewide Sales', 'sitewide-sales' ); ?>" target="_blank" href="https://sitewidesales.com/?utm_source=plugin&utm_medium=sitewide-sales-reports&utm_campaign=homepage"><img src="<?php echo esc_url( plugins_url( 'images/Sitewide-Sales.png', SWSALES_BASENAME ) ); ?>" border="0" alt="<?php esc_attr_e( 'Sitewide Sales(c) - All Rights Reserved', 'sitewide-sales' ); ?>" /></a>
				<div class="sitewide_sales_meta">
					<span class="sitewide_sales_version">v<?php echo SWSALES_VERSION; ?></span>
					<a href="https://www.strangerstudios.com/wordpress-plugins/sitewide-sales/documentation/?utm_source=plugin&utm_medium=swsales-admin-header&utm_campaign=documentation" target="_blank" title="<?php esc_attr_e( 'Documentation', 'sitewide-sales' ); ?>"><?php esc_html_e( 'Documentation', 'sitewide-sales' ); ?></a>
					<a href="https://www.strangerstudios.com/wordpress-plugins/sitewide-sales/documentation/support/?utm_source=plugin&utm_medium=swsales-admin-header&utm_campaign=support" target="_blank" title="<?php esc_attr_e( 'Get Support', 'sitewide-sales' );?>"><?php esc_html_e( 'Get Support', 'sitewide-sales' );?></a>
				</div>
			</div>
            <div class="about-wrap">
            <?php
               if ( ! empty( $_REQUEST['sws-verify-submit'] ) && wp_verify_nonce( $_POST['sws-key-nonce'], 'sws-key-nonce' ) ) {
                
                $key = preg_replace("/[^a-zA-Z0-9]/", "", $_REQUEST['sws-license-key']);
    
                delete_option( 'sws_license_key' );
    
                $valid = sws_license_is_valid( $key, NULL, true );
    
                if ( $valid ) { ?>
                    <div id="message" class="sitewide_sales_message sitewide_sales_success">
                        <p><?php _e( 'Your license key has been validated.', 'sitewide-sales' ); ?></p>
                    </div>
                <?php } else {
                    // handle error here.
                }
                update_option( 'sws_license_key', $key, 'no' );
                }

               $key = get_option( 'sws_license_key', '' );
               $sws_license_check = get_option( 'sws_license_check', array( 'license' => false, 'enddate' => 0 ) );
            ?>
            <h2><?php esc_html_e( 'Sitewide Sales Support License', 'sitewide-sales' ); ?></h2>
            

            <div class="about-text">
                <?php if(!sws_license_is_valid() && empty($key)) { ?>
                    <p class="sitewide_sales_message sitewide_sales_alert"><strong><?php _e('Enter your support license key.</strong> Your license key can be found in your membership email receipt or in your <a href="https://www.paidmembershipspro.com/membership-account/?redirect_to=%2Fmembership-account%2F%3Futm_source%3Dplugin%26utm_medium%3Dpmpro-license%26utm_campaign%3Dmembership-account%26utm_content%3Dno-key" target="_blank">Membership Account</a>.', 'paid-memberships-pro' );?></p>
                <?php } elseif(!sws_license_is_valid()) { ?>
                    <p class="sitewide_sales_message sitewide_sales_error"><strong><?php _e('Your license is invalid or expired.', 'paid-memberships-pro' );?></strong> <?php _e('Visit the PMPro <a href="https://www.paidmembershipspro.com/membership-account/?redirect_to=%2Fmembership-account%2F%3Futm_source%3Dplugin%26utm_medium%3Dpmpro-license%26utm_campaign%3Dmembership-account%26utm_content%3Dkey-not-valid" target="_blank">Membership Account</a> page to confirm that your account is active and to find your license key.', 'paid-memberships-pro' );?></p>
                <?php } else { ?>													
                    <p class="sitewide_sales_message sitewide_sales_success"><?php printf(__('<strong>Thank you!</strong> A valid <strong>%s</strong> license key has been used to activate your support license on this site.', 'paid-memberships-pro' ), ucwords($sws_license_check['license']));?></p>
                <?php } ?>
            </div>

            <form action="" method="post">
			<table class="form-table">
				<tbody>
					<tr id="sws-settings-key-box">
						<td>
							<input type="password" name="sws-license-key" id="sws-license-key" value="<?php echo esc_attr($key);?>" placeholder="<?php _e('Enter license key here...', 'sitewide-sales' );?>" size="40"  />
							<?php wp_nonce_field( 'sws-key-nonce', 'sws-key-nonce' ); ?>
							<?php submit_button( __( 'Validate Key', 'sitewide-sales' ), 'primary', 'sws-verify-submit', false ); ?>
						</td>
					</tr>
				</tbody>
			</table>
			</form>

            <p>
				<?php if ( ! sws_license_is_valid() ) { ?>
					<a class="button button-primary button-hero" href="https://www.paidmembershipspro.com/membership-checkout/?level=20&utm_source=plugin&utm_medium=pmpro-license&utm_campaign=plus-checkout&utm_content=buy-plus" target="_blank"><?php echo esc_html( 'Buy Plus License', 'paid-memberships-pro' ); ?></a>
					<a class="button button-hero" href="https://www.paidmembershipspro.com/pricing/?utm_source=plugin&utm_medium=pmpro-license&utm_campaign=pricing&utm_content=view-license-options" target="_blank"><?php echo esc_html( 'View Support License Options', 'paid-memberships-pro' ); ?></a>
				<?php } else { ?>
					<a class="button button-primary button-hero" href="https://www.paidmembershipspro.com/membership-account/?redirect_to=%2Fmembership-account%2F%3Futm_source%3Dplugin%26utm_medium%3Dpmpro-license%26utm_campaign%3Dmembership-account%26utm_content%3Dview-account" target="_blank"><?php echo esc_html( 'Manage My Account', 'paid-memberships-pro' ); ?></a>
					<a class="button button-hero" href="https://www.paidmembershipspro.com/membership-account/?redirect_to=%2Fnew-topic%2F%3Futm_source%3Dplugin%26utm_medium%3Dpmpro-license%26utm_campaign%3Dsupport%26utm_content%3Dnew-support-ticket" target="_blank"><?php echo esc_html( 'Open Support Ticket', 'paid-memberships-pro' ); ?></a>
				<?php } ?>
			</p>

			<hr />

            <div class="clearfix"></div>

            <img class="sws_icon alignright" src="<?php echo esc_url( plugins_url( 'images/Sitewide-Sales_icon.png', SWSALES_BASENAME ) ); ?>" border="0" alt="Sitewide Sales Logo" />
            <?php
                $allowed_pmpro_license_strings_html = array (
                    'a' => array (
                        'href' => array(),
                        'target' => array(),
                        'title' => array(),
                    ),
                    'strong' => array(),
                    'em' => array(),		);
            ?>

            <?php
                echo '<p>' . sprintf( wp_kses( __( 'Sitewide Sales is distributed under the <a href="%s" title="GPLv2 license" target="_blank">GPLv2 license</a>. This means, among other things, that you may use the software on this site or any other site free of charge.', 'paid-memberships-pro' ), $allowed_pmpro_license_strings_html ), '#' ) . '</p>';
            ?>

            <?php
                echo '<p>' . wp_kses( __( '<strong>Sitewide Sales offers plans for automatic updates and premium support.</strong> These plans include a Plus license key which we recommend for all public websites running Sitewide Sales. A Plus license key allows you to automatically update when a new security, bug fix, or feature enhancement is released.' ), $allowed_pmpro_license_strings_html ) . '</p>';
            ?>

            <?php
                echo '<p>' . wp_kses( __( '<strong>Need help?</strong> Your license allows you to open new tickets in our private support area. Purchases are backed by a 30 day, no questions asked refund policy.' ), $allowed_pmpro_license_strings_html ) . '</p>';
            ?>

            <p><a href="#" target="_blank"><?php echo esc_html( 'View Support License Options &raquo;', 'paid-memberships-pro' ); ?></a></p>

            </div> <!-- end about-text -->
            </div> <!-- end about-wrap -->
        <?php
            
    }

} // End of class

