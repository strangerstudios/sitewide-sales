<?php

namespace Sitewide_Sales\includes\classes;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

class SWSales_Privacy {

    /**
     * Initialize the class and all it's functions.
     */
    public static function init() {
        add_action( 'admin_init', array( __CLASS__, 'add_privacy_policy_content' ) );
    }

    public static function add_privacy_policy_content() {
        if ( ! function_exists( 'wp_add_privacy_policy_content') ) {
            return;
        }

        $content = '<p class="privacy-policy-tutorial">' . esc_html__( 'This sample text includes what information Sitewide Sales may be collecting or processing while installed and activated, as well as who may have access to that data. Depending on your sales configuration the data shared may vary. We advise to consult with a lawyer when deciding on what information to disclose on your privacy policy.', 'sitewide-sales' ) .'</p>';
        $content .= '<h2>' . esc_html__( 'What information we collect and store', 'sitewide-sales' ) . '</h2>';

        $content .= '<p>' . esc_html__( "While a sale is active, we'll track:", 'sitewide-sales' ) . '</p>';
        $content .= '<ul>';
        $content .= '<li>' . esc_html__( 'If a user has visited the landing page, a numeric value is stored inside a cookie.', 'sitewide-sales' ) . '</li>';
        $content .= '<li>' . esc_html__( "If a user has completed a sale after viewing the sale's active landing page. This will link the order to the sale analytics, a numeric value is stored inside a cookie.", 'sitewide-sales' ) . '</li>';
        $content .= '</ul>';
        $content .= '<p>' . esc_html__( 'Non personal identifying information is stored/tracked during a sale run through Sitewide Sales.', 'sitewide-sales' );

        $content .= '<h2>' . esc_html__( 'Who has access to sale information', 'sitewide-sales' ) . '</h2>';
        $content .= '<p>' . esc_html__( 'Administrators of our website will have access to general sale data. This is non-personalized information such as conversion rates (completed sales) and the number of views the landing page had received during this period. ', 'sitewide-sales' );
        

        wp_add_privacy_policy_content( 'Sitewide Sales', $content );
    }

} // End of Class