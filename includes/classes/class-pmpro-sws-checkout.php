<?php

namespace PMPro_Sitewide_Sales\includes\classes;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

class PMPro_SWS_Checkout {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'automatic_discount_application' ) );
		// add_filter( 'pmpro_email_body', array( __CLASS__, 'insert_upsell_into_confirmation_emails' ), 10, 2 );
		// add_filter( 'pmpro_confirmation_message', array( __CLASS__, 'insert_upsell_into_confirmation_page' ) );
		// add_filter( 'pmpro_include_pricing_fields', array( __CLASS__, 'maybe_add_choose_level_section' ) );
	}

	/**
	 * Automatically applies discount code if user has the cookie set from sale page
	 */
	public static function automatic_discount_application() {
		global $wpdb, $pmpro_pages;

		if ( empty( $_REQUEST['level'] ) || ! empty( $_REQUEST['discount_code'] ) ) {
			return;
		}
		$options              = PMPro_SWS_Settings::get_options();
		$active_sitewide_sale = $options['active_sitewide_sale_id'];
		$discount_code_id     = get_post_meta( $active_sitewide_sale, 'pmpro_sws_discount_code_id', true );

		if ( empty( $discount_code_id ) ||
		date( 'Y-m-d' ) < get_post_meta( $active_sitewide_sale, 'pmpro_sws_start_date', true ) ||
		date( 'Y-m-d' ) > get_post_meta( $active_sitewide_sale, 'pmpro_sws_end_date', true )
		) {
			return;
		}

		$cookie_name = 'pmpro_sws_' . $active_sitewide_sale . '_tracking';
		if ( ! isset( $_COOKIE[ $cookie_name ] ) || false == strpos( $_COOKIE[ $cookie_name ], ';1;' ) ) {
			return;
		}

		$_REQUEST['discount_code'] = $wpdb->get_var( $wpdb->prepare( "SELECT code FROM $wpdb->pmpro_discount_codes WHERE id=%d LIMIT 1", $discount_code_id ) );
	}

	/**
	 * Inserts upsale info into confirmation emails on checkout.
	 *
	 * @param  string     $body  current body of email.
	 * @param  PMProEmail $email email object.
	 * @return string            new email body.
	 */
	public static function insert_upsell_into_confirmation_emails( $body, $email ) {
		global $current_user;

		// Check if sending a confirmation email.
		if ( 0 !== strpos( $email->template, 'checkout' ) ) {
			return $body;
		}

		// Check if a sale is active.
		$options              = PMPro_SWS_Settings::get_options();
		$active_sitewide_sale = $options['active_sitewide_sale_id'];
		if ( false === $active_sitewide_sale || empty( $current_user->membership_level ) ) {
			return $body;
		}

		// Check if upsell is enabled/etc.
		$upsell_enabled  = get_post_meta( $active_sitewide_sale, 'pmpro_sws_upsell_enabled', true );
		$upsell_levels   = get_post_meta( $active_sitewide_sale, 'pmpro_sws_upsell_levels', true );
		$upsell_text     = get_post_meta( $active_sitewide_sale, 'pmpro_sws_upsell_text', true );
		$landing_page_id = get_post_meta( $active_sitewide_sale, 'pmpro_sws_landing_page_post_id', true );
		if ( empty( $upsell_enabled ) || false === $upsell_enabled ||
				empty( $upsell_levels ) || ! in_array( $current_user->membership_level->id, $upsell_levels ) ||
				empty( $upsell_text ) || '' === $upsell_text ||
				false === $landing_page_id || false === get_permalink( intval( $landing_page_id ) ) ) {
			return $body;
		}

		// Insert upsell.
		$upsell_text = str_replace( '!!sws_landing_page_url!!', get_permalink( intval( $landing_page_id ) ), $upsell_text );

		return $body . $upsell_text . '<br/><br/>';
	}

	/**
	 * Inserts upsale info into confirmation emails on checkout.
	 *
	 * @param  string $confirmation_message  current body of email.
	 * @return string                            new confirmation message.
	 */
	public static function insert_upsell_into_confirmation_page( $confirmation_message ) {
		global $current_user;
		$options              = PMPro_SWS_Settings::get_options();
		$active_sitewide_sale = $options['active_sitewide_sale_id'];
		if ( false === $active_sitewide_sale || empty( $current_user->membership_level ) ) {
			return $confirmation_message;
		}
		$upsell_enabled  = get_post_meta( $active_sitewide_sale, 'pmpro_sws_upsell_enabled', true );
		$upsell_levels   = get_post_meta( $active_sitewide_sale, 'pmpro_sws_upsell_levels', true );
		$upsell_text     = get_post_meta( $active_sitewide_sale, 'pmpro_sws_upsell_text', true );
		$landing_page_id = get_post_meta( $active_sitewide_sale, 'pmpro_sws_landing_page_post_id', true );
		if ( empty( $upsell_enabled ) || false === $upsell_enabled ||
				empty( $upsell_levels ) || ! in_array( $current_user->membership_level->id, $upsell_levels ) ||
				empty( $upsell_text ) || '' === $upsell_text ||
				false === $landing_page_id || false === get_permalink( intval( $landing_page_id ) ) ) {
			return $confirmation_message;
		}
		$upsell_text = str_replace( '!!sws_landing_page_url!!', get_permalink( intval( $landing_page_id ) ), $upsell_text );
		return $confirmation_message . $upsell_text . '<br/><br/>';
	}

	/**
	 * If the discount code has more than one level associated with it,
	 * let users choose which level to checkout for.
	 */
	public static function maybe_add_choose_level_section( $include_pricing_fields ) {
		global $wpdb;

		if ( PMPro_SWS_Settings::is_active_sitewide_sale_landing_page() ) {
			$options          = PMPro_SWS_Settings::get_options();
			$discount_code_id = get_post_meta( $options['active_sitewide_sale_id'], 'pmpro_sws_discount_code_id', true );

			if ( ! empty( $discount_code_id ) ) {
				$code_levels = $wpdb->get_results( "SELECT * FROM $wpdb->pmpro_discount_codes_levels WHERE code_id = " . esc_sql( $discount_code_id ), OBJECT );

				if ( count( $code_levels ) > 1 ) {
					// $include_pricing_fields = false;
					// show a radio option to choose level
					?>
					<?php
				}
			}
		}

		return $include_pricing_fields;
	}
}
