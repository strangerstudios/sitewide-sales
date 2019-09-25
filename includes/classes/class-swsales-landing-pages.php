<?php

namespace Sitewide_Sales\includes\classes;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

class SWSales_Landing_Pages {
	/**
	 * Initial plugin setup
	 *
	 * @package sitewide-sale/includes
	 */
	public static function init() {
		add_shortcode( 'swsales', array( __CLASS__, 'shortcode' ) );
		add_filter( 'edit_form_after_title', array( __CLASS__, 'add_edit_form_after_title' ) );
		add_filter( 'body_class', array( __CLASS__, 'add_body_class' ) );
		add_filter( 'display_post_states', array( __CLASS__, 'add_display_post_states' ), 10, 2 );
		add_filter( 'page_row_actions', array( __CLASS__, 'add_page_row_actions' ), 10, 2 );
	}

	/**
	 * Displays pre-sale content, sale content, or post-sale content
	 * depending on page and date
	 *
	 * Attribute sitewide_sale_id sets Sitewide Sale to get meta from.
	 * Attribute sale_content sets time period to display.
	 *
	 * @param array $atts attributes passed with shortcode.
	 */
	public static function shortcode( $atts ) {
		$sitewide_sale = new SWSales_Sitewide_Sale();

		if ( is_array( $atts ) && array_key_exists( 'sitewide_sale_id', $atts ) ) {
			$sale_found = $sitewide_sale->load_sitewide_sale( $atts['sitewide_sale_id'] );
			if ( ! $sale_found ) {
				return '';
			}
		} else {
			$post_id       = get_the_ID();
			$sitewide_sales = get_posts(
				array(
					'post_type'      => 'sitewide_sale',
					'meta_key'       => 'swsales_landing_page_post_id',
					'meta_value'     => '' . $post_id,
					'posts_per_page' => 1,
				)
			);
			if ( 0 === count( $sitewide_sales ) ) {
				return '';
			}
			$sitewide_sale->load_sitewide_sale( $sitewide_sales[0]->ID );
		}
		$time_period = $sitewide_sale->get_time_period();

		if ( current_user_can( 'administrator' ) && isset( $_REQUEST['swsales_preview_time_period'] ) ) {
			$time_period = $_REQUEST['swsales_preview_time_period'];
		} elseif ( is_array( $atts ) && array_key_exists( 'time_period', $atts ) ) {
			$time_period = $atts['time_period'];
		}

		// Our return string.
		$r = '<div class="swsales_landing_content swsales_landing_content_' . $time_period . '">';
		$r .= apply_filters( 'the_content', $sitewide_sale->get_sale_content_for_time_period( $time_period ) );
		$r .= '</div> <!-- .swsales_landing_content -->';

		// Filter for themes and plugins to modify the [swsales] shortcode output.
		$r = apply_filters( 'swsales_landing_page_content', $r, $atts );

		return $r;
	}

	/**
	 * Add notice that a page is linked to a Sitewide Sale on the Edit Page screen.
	 *
	 * @param WP_Post $post The current post object.
	 */
	public static function add_edit_form_after_title( $post ) {

		// Check if this post has an associated Sitewide Sale.
		$sitewide_sale_id = get_post_meta( $post->ID, 'swsales_sitewide_sale_id', true );

		if ( ! empty( $sitewide_sale_id ) ) {
			echo '<div id="message" class="notice notice-info inline"><p>This is a Sitewide Sale Landing Page. <a target="_blank" href="' . get_edit_post_link( $sitewide_sale_id ) . '">Edit the Sitewide Sale</a></p></div>';
		}
	}

	/**
	 * Add the 'swsales-sitewide-sale-landing-page' to the body_class filter when viewing a Landing Pages.
	 *
	 * @param array $classes An array of classes already in place for the body class.
	 */
	public static function add_body_class( $classes ) {

		// See if any Sitewide Sale CPTs have this post ID set as the Landing Page.
		$sitewide_sale_id = get_post_meta( get_queried_object_id(), 'swsales_sitewide_sale_id', true );

		if ( ! empty( $sitewide_sale_id ) ) {
			// This is a landing page, add the custom class.
			$classes[] = 'swsales-sitewide-sale-landing-page';
		}

		return $classes;
	}

	/**
	 * Add a post display state for special Landing Pages in the page list table.
	 *
	 * @param array   $post_states An array of post display states.
	 * @param WP_Post $post The current post object.
	 */
	public static function add_display_post_states( $post_states, $post ) {
		// Check if this post has an associated Sitewide Sale.
		$sitewide_sale_id = get_post_meta( $post->ID, 'swsales_sitewide_sale_id', true );

		if ( ! empty( $sitewide_sale_id ) ) {
			$post_states['swsales_landing_page'] = __( 'Sitewide Sale Landing Page', 'sitewide-sales' );
		}

		return $post_states;
	}

	/**
	 * Add page row action to edit the associated Sitewide Sale for special Landing Pages in the page list table.
	 *
	 * @param array   $actions An array of page row actions.
	 * @param WP_Post $post The current post object.
	 */
	public static function add_page_row_actions( $actions, $post ) {
		// Check if this post has an associated Sitewide Sale.
		$sitewide_sale_id = get_post_meta( $post->ID, 'swsales_sitewide_sale_id', true );

		if ( ! empty( $sitewide_sale_id ) ) {
			$actions['swsales_edit_sale'] = sprintf(
				'<a href="%s" rel="bookmark" aria-label="%s">%s</a>',
				esc_url( get_edit_post_link( $sitewide_sale_id ) ),
				esc_attr__( 'Edit Sitewide Sale', 'sitewide-sales' ),
				esc_html__( 'Edit Sitewide Sale', 'sitewide-sales' )
			);
		}

		return $actions;
	}
}
