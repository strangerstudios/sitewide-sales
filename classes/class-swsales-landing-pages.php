<?php

namespace Sitewide_Sales\classes;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

class SWSales_Landing_Pages {
	/**
	 * Initial plugin setup
	 *
	 * @package sitewide-sale/includes
	 */
	public static function init() {
		add_shortcode( 'sitewide_sales', array( __CLASS__, 'shortcode' ) );
		add_shortcode( 'sitewide_sale', array( __CLASS__, 'shortcode' ) );
		add_filter( 'edit_form_after_title', array( __CLASS__, 'add_edit_form_after_title' ) );
		add_filter( 'body_class', array( __CLASS__, 'add_body_class' ) );
		add_filter( 'display_post_states', array( __CLASS__, 'add_display_post_states' ), 10, 2 );
		add_filter( 'page_row_actions', array( __CLASS__, 'add_page_row_actions' ), 10, 2 );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_edit_swsales_metabox' ) );
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

		// $atts    ::= array of attributes
		extract( shortcode_atts( array(
			'sitewide_sale_id' => '',
			'time_period' => ''
		), $atts ) );

		// Get the Sitewide Sale to show for this shortcode output from atts or load the active sale.
		if ( ! empty( $sitewide_sale_id ) ) {
			$sale_found = $sitewide_sale->load_sitewide_sale( $sitewide_sale_id );
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

		// Get the time period for the sale based on sale settings and current date.
		$sale_period = $sitewide_sale->get_time_period();

		// Allow admins to preview the sale period using a URL attribute.
		if ( current_user_can( 'administrator' ) && isset( $_REQUEST['swsales_preview_time_period'] ) ) {
			$sale_period = $_REQUEST['swsales_preview_time_period'];
		}

		// Our return string.
		$r = '';

		/**
		 * If there is no period in shortcode atts, output the contents.
		 * If the shortcode atts period matches the sale period, output the contents.
		 * Otherwise, output nothing.
		 */
		if ( empty( $time_period ) || ! empty( $time_period ) && $sale_period === $time_period ) {
			$r .= '<div class="swsales-landing-page-content swsales-landing-page-content-' . $sale_period . '">';
			$r .= apply_filters( 'the_content', $sitewide_sale->get_sale_content_for_time_period( $sale_period ) );
			$r .= '</div> <!-- .swsales_landing_content -->';
		}

		// Template specific filter only if we have a return string to adjust.
		if ( ! empty( $r ) ) {
			$landing_template = $sitewide_sale->get_landing_page_template();
			if ( ! empty( $landing_template ) ) {
				$r = apply_filters( 'swsales_landing_page_content_' . $landing_template, $r, $sitewide_sale );
			}
		}

		// Filter for themes and plugins to modify the [sitewide_sales] shortcode output.	
		$r = apply_filters( 'swsales_landing_page_content', $r, $landing_template );

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
			$classes[] = 'swsales-landing-page';
			$landing_template = get_post_meta( $sitewide_sale_id, 'swsales_landing_page_template', true );
			if ( ! empty( $landing_template ) ) {
				$classes[] = 'swsales-landing-page-' . esc_html( $landing_template );
			}
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

	/**
	 * Register meta box(es).
	 */
	public static function add_edit_swsales_metabox() {
		if ( empty( $_REQUEST['post'] ) ) {
			return;
		}
		$post_id = $_REQUEST['post'];
		if ( ! empty( get_post_meta( $post_id, 'swsales_sitewide_sale_id', true ) ) ) {
			add_meta_box( 'swsales_edit_sitewide_sale', "<span style='color:#bd2300'>" . __( 'Sitewide Sale Landing Page', 'sitewide-sales' ) . '</span>', array( __CLASS__, 'edit_swsales_metabox_content' ), 'page', 'side', 'high' );
		}
	}

	/**
	 * Meta box display callback.
	 *
	 * @param WP_Post $post Current post object.
	 */
	public static function edit_swsales_metabox_content( $post ) {
		?>
		<p>
		<?php esc_html_e( 'Use the [sitewide_sales] shortcode to display your sale content on this landing page.', 'sitewide-sales' ); ?>
	</p>
	</br>
	<p>
		<?php printf( "<a href='%s' target='_blank'>" . __( 'Edit your Sitewide Sale', 'sitewide-sales' ) . '</a>', esc_url( get_edit_post_link( get_post_meta( $post->ID, 'swsales_sitewide_sale_id', true ) ) ) ); ?>
	</p>
		<?php
	}
}
