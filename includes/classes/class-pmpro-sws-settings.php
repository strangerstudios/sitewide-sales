<?php

namespace PMPro_Sitewide_Sales\includes\classes;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

class PMPro_SWS_Settings {
	/**
	 * Initial plugin setup
	 *
	 * @package pmpro-sitewide-sale/includes
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
		add_filter( 'pmpro_custom_advanced_settings', array( __CLASS__, 'custom_advanced_settings' ) );
	}

	/**
	 * Init settings page
	 */
	public static function admin_init() {
		register_setting( 'pmpro-sws-group', 'pmpro_sitewide_sales', array( __CLASS__, 'validate' ) );
	}

	/**
	 * Get the Sitewide Sale Options
	 *
	 * @return array [description]
	 */
	public static function get_options() {
		static $options;

		if ( empty( $options ) ) {
			$options = get_option( 'pmpro_sitewide_sales' );

			// Set the defaults.
			if ( empty( $options ) || ! array_key_exists( 'active_sitewide_sale_id', $options ) ) {
				$options = self::reset_options();
			}
		}
		return $options;
	}

	/**
	 * Sets SWS settings to default
	 */
	public static function reset_options() {
		return array(
			'active_sitewide_sale_id' => false,
		);
	}

	/**
	 * Save options
	 *
	 * @param array $options contains information about sale to be saved.
	 */
	public static function save_options( $options ) {
		update_option( 'pmpro_sitewide_sales', $options, 'no' );
	}

	/**
	 * Validates sitewide sale options
	 *
	 * @param  array $input info to be validated.
	 */
	public static function validate( $input ) {
		$options = self::get_options();
		if ( ! empty( $input['active_sitewide_sale_id'] ) && '-1' !== $input['active_sitewide_sale_id'] ) {
			$options['active_sitewide_sale_id'] = trim( $input['active_sitewide_sale_id'] );
		} else {
			$options['active_sitewide_sale_id'] = false;
		}
		return $options;
	}

	/**
	 * Is the current page the active sitewide sale landing page?
	 */
	public static function is_active_sitewide_sale_landing_page( $post_id = false ) {
		global $post;

		// default to global post
		if ( empty( $post_id ) ) {
			$post_id = $post->ID;
		}

		if ( empty( $post_id ) ) {
			return false;
		}

		$options = self::get_options();

		if ( empty( $options['active_sitewide_sale_id'] ) ) {
			return false;
		}

		$landing_page_id = get_post_meta( $options['active_sitewide_sale_id'], 'pmpro_sws_landing_page_post_id', true );

		if ( ! empty( $landing_page_id ) && $landing_page_id == $post_id ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Add an Advanced Setting to allow non-Memberlite themes to enable Templates.
	 */
	public static function custom_advanced_settings( $custom_fields ) {
		if ( ! defined( 'MEMBERLITE_VERSION' ) ) {
			if ( ! is_array( $custom_fields ) ) {
				$custom_fields = array();
			}
			$custom_fields['pmpro_sws_allow_template'] = array(
				'field_name'  => 'pmpro_sws_allow_template',
				'field_type'  => 'select',
				'label'       => __( 'Allow Sitewide Sale Custom Templates', 'pmpro-sitewide-sale' ),
				'description' => __( 'Check this box to allow the use of custom Landing Page and Banner templates. Note that we cannot ensure theme compatiblity for included templates.', 'pmpro-sitewide-sale' ),
				'options'     => array(
					0 => 'No',
					1 => 'Yes',
				),
			);
		}
		
		return $custom_fields;
	}
}
