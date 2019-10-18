<?php

namespace Sitewide_Sales\includes\classes;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

class SWSales_Post_Types {

	/**
	 * [init description]
	 *
	 * @return [type] [description]
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_sitewide_sale_cpt' ) );
		add_filter( 'manage_sitewide_sale_posts_columns', array( __CLASS__, 'set_sitewide_sale_columns' ) );
		add_action( 'manage_sitewide_sale_posts_custom_column', array( __CLASS__, 'fill_sitewide_sale_columns' ), 10, 2 );
		add_filter( 'months_dropdown_results', '__return_empty_array' );
		add_filter( 'post_row_actions', array( __CLASS__, 'remove_sitewide_sale_row_actions' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_swsales_set_active_sitewide_sale', array( __CLASS__, 'set_active_sitewide_sale' ) );
		add_filter( 'wp_insert_post_data', array( __CLASS__, 'force_publish_status' ), 10, 2 );
	}

	/**
	 * [register_sitewide_sale_cpt description]
	 *
	 * @return [type] [description]
	 */
	public static function register_sitewide_sale_cpt() {

		// Set the custom post type labels.
		$labels['name']                  = _x( 'Sitewide Sales', 'Post Type General Name', 'sitewide-sales' );
		$labels['singular_name']         = _x( 'Sitewide Sale', 'Post Type Singular Name', 'sitewide-sales' );
		$labels['all_items']             = __( 'All Sitewide Sales', 'sitewide-sales' );
		$labels['menu_name']             = __( 'Sitewide Sales', 'sitewide-sales' );
		$labels['name_admin_bar']        = __( 'Sitewide Sales', 'sitewide-sales' );
		$labels['all_items']             = __( 'All Sitewide Sales', 'sitewide-sales' );
		$labels['add_new_item']          = __( 'Add New Sitewide Sale', 'sitewide-sales' );
		$labels['add_new']               = __( 'Add New', 'sitewide-sales' );
		$labels['new_item']              = __( 'New Sitewide Sale', 'sitewide-sales' );
		$labels['edit_item']             = __( 'Edit Sitewide Sale', 'sitewide-sales' );
		$labels['update_item']           = __( 'Update Sitewide Sale', 'sitewide-sales' );
		$labels['view_item']             = __( 'View Sitewide Sale', 'sitewide-sales' );
		$labels['search_items']          = __( 'Search Sitewide Sales', 'sitewide-sales' );
		$labels['not_found']             = __( 'Not found', 'sitewide-sales' );
		$labels['not_found_in_trash']    = __( 'Not found in Trash', 'sitewide-sales' );
		$labels['insert_into_item']      = __( 'Insert into Sitewide Sale', 'sitewide-sales' );
		$labels['uploaded_to_this_item'] = __( 'Uploaded to this Sitewide Sale', 'sitewide-sales' );
		$labels['items_list']            = __( 'Sitewide Sales list', 'sitewide-sales' );
		$labels['items_list_navigation'] = __( 'Sitewide Sales list navigation', 'sitewide-sales' );
		$labels['filter_items_list']     = __( 'Filter sitewide sales list', 'sitewide-sales' );

		// Build the post type args.
		$args['labels']              = __( 'Sitewide Sales', 'sitewide-sales' );
		$args['labels']              = $labels;
		$args['description']         = __( 'Sitewide Sales', 'sitewide-sales' );
		$args['public']              = false;
		$args['publicly_queryable']  = false;
		$args['show_ui']             = true;
		$args['show_in_menu']        = true;
		$args['menu_position']       = 56;
		$args['show_in_nav_menus']   = true;
		$args['can_export']          = true;
		$args['has_archive']         = false;
		$args['rewrite']             = false;
		$args['exclude_from_search'] = true;
		$args['query_var']           = false;
		$args['capability_type']     = 'page';
		$args['show_in_rest']        = false;
		$args['rest_base']           = 'sitewide_sale';
		$args['supports']            = array(
			'title',
		);
		register_post_type( 'sitewide_sale', $args );
	}

	/**
	 * [enqueue_scripts description]
	 *
	 * @return [type] [description]
	 */
	public static function enqueue_scripts() {
		wp_register_script( 'swsales_set_active_sitewide_sale', plugins_url( 'js/swsales-set-active-sitewide-sale.js', SWSALES_BASENAME ), array( 'jquery' ), '1.0.4' );
		wp_enqueue_script( 'swsales_set_active_sitewide_sale' );
	}

	/**
	 * set_sitewide_sale_columns Assigning labels to WP_List_Table columns will add a checkbox to the full list page's Screen Options.
	 *
	 * @param [type] $columns [description]
	 */
	public static function set_sitewide_sale_columns( $columns ) {
		unset( $columns['date'] );
		$columns['sale_date']    = __( 'Sale Date', 'sitewide-sales' );
		$columns['sale_type']    = __( 'Sale Type', 'sitewide-sales' );
		$columns['landing_page'] = __( 'Landing Page', 'sitewide-sales' );
		$columns['reports']      = __( 'Reports', 'sitewide-sales' );
		$columns['set_active']   = __( 'Select Active Sale', 'sitewide-sales' );

		return $columns;
	}

	/**
	 * [fill_sitewide_sale_columns description]
	 *
	 * @param  [type] $column  [description]
	 * @param  [type] $post_id [description]
	 * @return [type]          [description]
	 */
	public static function fill_sitewide_sale_columns( $column, $post_id ) {
		$sitewide_sale = SWSales_Sitewide_Sale::get_sitewide_sale( $post_id );

		switch ( $column ) {
			case 'sale_date':
				echo esc_html( $sitewide_sale->get_start_date() . ' - ' . $sitewide_sale->get_end_date() );
				break;
			case 'sale_type':
				$sale_type = get_post_meta( $post_id, 'swsales_sale_type', true );
				if ( 0 !== $sale_type ) {
					$sale_types = apply_filters( 'swsales_sale_types', array() );
					echo esc_html( $sale_types[ $sale_type ] );
				}
				break;
			case 'landing_page':
				$landing_page = $sitewide_sale->get_landing_page_post_id();
				if ( ! empty( $landing_page ) ) {
					$title = get_the_title( $landing_page );
					if ( ! empty( $title ) ) {
						echo '<a href="' . esc_url( get_permalink( $landing_page ) ) . '">' . esc_html( $title ) . '</a>';
					}
				} else {
					echo '-';
				}
				break;
			case 'reports':
					echo '<a class="button button-primary" href="' . admin_url( 'edit.php?post_type=sitewide_sale&page=sitewide_sales_reports&sitewide_sale=' . $post_id ) . '">' . esc_html__( 'View Reports', 'pmpro-sitewide-sales' ) . '</a>';
				break;
			case 'set_active':
				$options = SWSales_Settings::get_options();
				if ( array_key_exists( 'active_sitewide_sale_id', $options ) && $post_id == $options['active_sitewide_sale_id'] ) {
					echo '<button class="button button-primary swsales_column_set_active" id="swsales_column_set_active_' . $post_id . '">' . __( 'Remove Active', 'sitewide-sales' ) . '</button>';
				} else {
					echo '<button class="button button-secondary swsales_column_set_active" id="swsales_column_set_active_' . $post_id . '">' . __( 'Set Active', 'sitewide-sales' ) . '</button>';
				}
				break;
		}
	}

	/**
	 * [set_active_sitewide_sale description]
	 */
	public static function set_active_sitewide_sale() {
		$sitewide_sale_id = $_POST['sitewide_sale_id'];
		$options          = SWSales_Settings::get_options();

		if ( array_key_exists( 'active_sitewide_sale_id', $options ) && $sitewide_sale_id == $options['active_sitewide_sale_id'] ) {
			$options['active_sitewide_sale_id'] = false;
		} else {
			$options['active_sitewide_sale_id'] = $sitewide_sale_id;
		}

		SWSales_Settings::save_options( $options );
	}

	/**
	 * [remove_sitewide_sale_row_actions description]
	 */
	public static function remove_sitewide_sale_row_actions( $actions, $post ) {
		// Removes the "Quick Edit" action.
		if ( $post->post_type === 'sitewide_sale' ) {
			unset( $actions['inline hide-if-no-js'] );
		}
		
		return $actions;
	}

	/**
	 * Make sure status is always publish.
	 * We must allow trash and auto-draft as well.
	 */
	public static function force_publish_status( $data, $postarr ) {
		if ( $data['post_type'] === 'sitewide_sale'
		   && $data['post_status'] !== 'trash'
		   && $data['post_status'] !== 'auto-draft' ) {
			$data['post_status'] = 'publish';
		}

		return $data;
	}

}
