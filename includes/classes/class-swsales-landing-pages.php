<?php

namespace PMPro_Sitewide_Sales\includes\classes;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

class PMPro_SWS_Landing_Pages {
	/**
	 * Initial plugin setup
	 *
	 * @package pmpro-sitewide-sale/includes
	 */
	public static function init() {
		add_action( 'wp', array( __CLASS__, 'pmpro_preheader' ), 1 );
		add_shortcode( 'pmpro_sws', array( __CLASS__, 'shortcode' ) );
		add_filter( 'edit_form_after_title', array( __CLASS__, 'add_edit_form_after_title' ) );
		add_filter( 'body_class', array( __CLASS__, 'add_body_class' ) );
		add_filter( 'display_post_states', array( __CLASS__, 'add_display_post_states' ), 10, 2 );
		add_filter( 'page_row_actions', array( __CLASS__, 'add_page_row_actions' ), 10, 2 );
	}

	/**
	 * Get the default level to use on a landing page
	 *
	 * @param $post_id Post ID of the landing page
	 */
	public static function get_default_level( $post_id ) {
		global $post, $wpdb;

		// guess
		$all_levels = pmpro_getAllLevels( true, true );
		if ( ! empty( $all_levels ) ) {
			$keys     = array_keys( $all_levels );
			$level_id = $keys[0];
		} else {
			return false;
		}

		// default post_id
		if ( empty( $post_id ) ) {
			$post_id = $post->ID;
		}

		// must have a post_id
		if ( empty( $post_id ) ) {
			return $level_id;
		}

		// get sale for this $post_id
		$sitewide_sale_id = get_post_meta( $post_id, 'pmpro_sws_sitewide_sale_id', true );

		if ( ! empty( $sitewide_sale_id ) ) {
			// check for setting
			$default_level_id = get_post_meta( $sitewide_sale_id, 'pmpro_sws_landing_page_default_level_id', true );

			// no default set? get the discount code for this sale
			if ( ! empty( $default_level_id ) ) {
				// use the setting
				$level_id = $default_level_id;
			} else {
				// check for discount code
				$discount_code_id = get_post_meta( $sitewide_sale_id, 'pmpro_sws_discount_code_id', true );

				// get first level that uses this code
				if ( ! empty( $discount_code_id ) ) {
					$first_code_level_id = $wpdb->get_var( "SELECT level_id FROM $wpdb->pmpro_discount_codes_levels WHERE code_id = '" . esc_sql( $discount_code_id ) . "' ORDER BY level_id LIMIT 1" );

					if ( ! empty( $first_code_level_id ) ) {
						$level_id = $first_code_level_id;
					}
				}
			}
		}

		return $level_id;
	}

	/**
	 * Load the checkout preheader on the landing page.
	 */
	public static function pmpro_preheader() {
		global $wpdb;

		// Make sure PMPro is loaded.
		if ( ! defined( 'PMPRO_DIR' ) ) {
			return;
		}

		// Don't do this in the dashboard.
		if ( is_admin() ) {
			return;
		}

		// Check if this is the landing page
		$queried_object = get_queried_object();
		if ( empty( $queried_object ) || empty( $queried_object->ID ) ) {
			return;
		}

		// Choose a default level if none specified.
		if ( empty( $_REQUEST['level'] ) ) {
			$_REQUEST['level'] = self::get_default_level( $queried_object->ID );
		}

		// Set the discount code if none specified.
		if ( empty( $_REQUEST['discount_code'] ) ) {
			$sitewide_sale_id          = get_post_meta( $queried_object->ID, 'pmpro_sws_sitewide_sale_id', true );
			$discount_code_id          = get_post_meta( $sitewide_sale_id, 'pmpro_sws_discount_code_id', true );
			$_REQUEST['discount_code'] = $wpdb->get_var( $wpdb->prepare( "SELECT code FROM $wpdb->pmpro_discount_codes WHERE id=%d LIMIT 1", $discount_code_id ) );
		}

		if ( ! has_shortcode( $queried_object->post_content, 'pmpro_sws' ) ) {
			return;
		}
		require_once PMPRO_DIR . '/preheaders/checkout.php';
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
		$sitewide_sale = null;

		if ( is_array( $atts ) && array_key_exists( 'sitewide_sale_id', $atts ) ) {
			$sitewide_sale = get_post( $atts['sitewide_sale_id'] );
			if ( empty( $sitewide_sale ) && 'pmpro_sitewide_sale' !== $sitewide_sale->post_type ) {
				return '';
			}
		} else {
			$post_id       = get_the_ID();
			$sitewide_sale = get_posts(
				array(
					'post_type'      => 'pmpro_sitewide_sale',
					'meta_key'       => 'pmpro_sws_landing_page_post_id',
					'meta_value'     => '' . $post_id,
					'posts_per_page' => 1,
				)
			);

			if ( 1 > count( $sitewide_sale ) ) {
				return '';
			}
			$sitewide_sale = $sitewide_sale[0];
		}

		$sale_content           = 'sale';
		$possible_sale_contents = [ 'pre-sale', 'sale', 'post-sale' ];

		$sale_start_date = get_post_meta( $sitewide_sale->ID, 'pmpro_sws_start_date', true );
		$sale_end_date   = get_post_meta( $sitewide_sale->ID, 'pmpro_sws_end_date', true );

		if ( current_user_can( 'administrator' ) && isset( $_REQUEST['pmpro_sws_preview_content'] ) && in_array( $_REQUEST['pmpro_sws_preview_content'], $possible_sale_contents ) ) {
			$sale_content = $_REQUEST['pmpro_sws_preview_content'];
		} elseif ( is_array( $atts ) && array_key_exists( 'sale_content', $atts ) ) {
			if ( in_array( $atts['sale_content'], $possible_sale_contents ) ) {
				$sale_content = $atts['sale_content'];
			} else {
				return '';
			}
		} elseif ( date( 'Y-m-d', current_time( 'timestamp' ) ) < $sale_start_date ) {
			$sale_content = 'pre-sale';
		} elseif ( date( 'Y-m-d', current_time( 'timestamp' ) ) > $sale_end_date ) {
			$sale_content = 'post-sale';
		}

		// Our return string.
		$r = '';

		// Display the wrapping div for selected template if using Memberlite or Advanced Setting set to "Yes".
		if ( defined( 'MEMBERLITE_VERSION' ) || pmpro_getOption( 'pmpro_sws_allow_template' ) === 'Yes' ) {
			$landing_template = get_post_meta( $sitewide_sale->ID, 'pmpro_sws_landing_page_template', true );
			if ( ! empty( $landing_template ) ) {
				$r .= '<div id="pmpro_sitewide_sale_landing_page_template-' . esc_html( $landing_template ) . '" class="pmpro_sitewide_sale_landing_page_template">';

				// Display the wrapping div for photo background image if specified in $landing_template.
				if ( in_array( $landing_template, array( 'photo', 'scroll' ) ) ) {
					$background_image = wp_get_attachment_image_src( get_post_thumbnail_id( get_queried_object_id() ), 'full' );
					if ( ! empty( $background_image[0] ) ) {
						$r .= '<div class="pmpro_sitewide_sale_landing_page_template-background-image" style="background-image: url(' . $background_image[0] . ')">';
					}
				}
			}
		}

		if ( $sale_content === 'pre-sale' ) {
			$landing_content = apply_filters( 'the_content', get_post_meta( $sitewide_sale->ID, 'pmpro_sws_pre_sale_content', true ) );
			$r              .= '<div class="pmpro_sws_landing_content pmpro_sws_landing_content_pre-sale">';
			$r              .= $landing_content;
			$r              .= '</div> <!-- .pmpro_sws_landing_content -->';
		} elseif ( $sale_content === 'post-sale' ) {
			$landing_content = apply_filters( 'the_content', get_post_meta( $sitewide_sale->ID, 'pmpro_sws_post_sale_content', true ) );
			$r              .= '<div class="pmpro_sws_landing_content pmpro_sws_landing_content_post-sale">';
			$r              .= $landing_content;
			$r              .= '</div> <!-- .pmpro_sws_landing_content -->';
		} else {
			$landing_content = apply_filters( 'the_content', get_post_meta( $sitewide_sale->ID, 'pmpro_sws_sale_content', true ) );

			if ( function_exists( 'pmpro_loadTemplate' ) ) {
				$template = pmpro_loadTemplate( 'checkout', 'local', 'pages' );
			} else {
				$template = '';
			}

			$r .= '<div class="pmpro_sws_landing_content pmpro_sws_landing_content_sale">';
			$r .= $landing_content;
			$r .= '</div> <!-- .pmpro_sws_landing_content -->';
			$r .= $template;
		}

		// Display the closing div for selected template if using Memberlite or Advanced Setting set to "Yes".
		if ( defined( 'MEMBERLITE_VERSION' ) || pmpro_getOption( 'pmpro_sws_allow_template' ) === 'Yes' && ! empty( $landing_template ) ) {
			if ( ! empty( $background_image[0] ) ) {
				$r .= '</div> <!-- .pmpro_sitewide_sale_landing_page_template-background-image -->';
			}
			$r .= '</div> <!-- .pmpro_sitewide_sale_landing_page_template -->';
		}

		// Filter for themes and plugins to modify the [pmpro_sws] shortcode output.
		$r = apply_filters( 'pmpro_sws_landing_page_content', $r, $atts );

		return $r;
	}

	/**
	 * Add notice that a page is linked to a Sitewide Sale on the Edit Page screen.
	 *
	 * @param WP_Post $$post The current post object.
	 */
	public static function add_edit_form_after_title( $post ) {

		// Check if this post has an associated Sitewide Sale.
		$sitewide_sale_id = get_post_meta( $post->ID, 'pmpro_sws_sitewide_sale_id', true );

		if ( ! empty( $sitewide_sale_id ) ) {
			echo '<div id="message" class="notice notice-info inline"><p>This is a Sitewide Sale Landing Page. <a target="_blank" href="' . get_edit_post_link( $sitewide_sale_id ) . '">Edit the Sitewide Sale</a></p></div>';
		}
	}

	/**
	 * Add the 'pmpro-sitewide-sale-landing-page' to the body_class filter when viewing a Landing Pages.
	 *
	 * @param array $classes An array of classes already in place for the body class.
	 */
	public static function add_body_class( $classes ) {

		// See if any Sitewide Sale CPTs have this post ID set as the Landing Page.
		$sitewide_sale_id = get_post_meta( get_queried_object_id(), 'pmpro_sws_sitewide_sale_id', true );

		if ( ! empty( $sitewide_sale_id ) ) {
			// This is a landing page, add the custom class.
			$classes[] = 'pmpro-sitewide-sale-landing-page';

			if ( defined( 'MEMBERLITE_VERSION' ) || ( pmpro_getOption( 'pmpro_sws_allow_template' ) === 'Yes' ) ) {
				// If the landing page has a custom template, add the custom class.
				$landing_template = get_post_meta( $sitewide_sale_id, 'pmpro_sws_landing_page_template', true );
				if ( ! empty( $landing_template ) ) {
					$classes[] = 'pmpro-sitewide-sale-landing-page-' . esc_html( $landing_template );
				}
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
		$sitewide_sale_id = get_post_meta( $post->ID, 'pmpro_sws_sitewide_sale_id', true );

		if ( ! empty( $sitewide_sale_id ) ) {
			$post_states['pmpro_sws_landing_page'] = __( 'Sitewide Sale Landing Page', 'pmpro-sitewide-sales' );
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
		$sitewide_sale_id = get_post_meta( $post->ID, 'pmpro_sws_sitewide_sale_id', true );

		if ( ! empty( $sitewide_sale_id ) ) {
			$actions['pmpro_sws_edit_sale'] = sprintf(
				'<a href="%s" rel="bookmark" aria-label="%s">%s</a>',
				esc_url( get_edit_post_link( $sitewide_sale_id ) ),
				esc_attr__( 'Edit Sitewide Sale', 'pmpro-sitewide-sales' ),
				esc_html__( 'Edit Sitewide Sale', 'pmpro-sitewide-sales' )
			);
		}

		return $actions;
	}
}
