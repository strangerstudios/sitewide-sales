<?php

namespace PMPro_Sitewide_Sales\includes\classes;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

class PMPro_SWS_Post_Types {

	/**
	 * [init description]
	 *
	 * @return [type] [description]
	 */
	public static function init() {
		add_action( 'admin_bar_menu', array( __CLASS__, 'add_cpt_to_admin_bar' ), 1001 );
		add_action( 'admin_menu', array( __CLASS__, 'add_cpt_to_menu' ) );
		add_action( 'init', array( __CLASS__, 'register_sitewide_sale_cpt' ) );
		add_filter( 'manage_pmpro_sitewide_sale_posts_columns', array( __CLASS__, 'set_sitewide_sale_columns' ) );
		add_action( 'manage_pmpro_sitewide_sale_posts_custom_column', array( __CLASS__, 'fill_sitewide_sale_columns' ), 10, 2 );
		add_filter( 'months_dropdown_results', '__return_empty_array' );
		add_filter( 'post_row_actions', array( __CLASS__, 'remove_sitewide_sale_row_actions' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_pmpro_sws_set_active_sitewide_sale', array( __CLASS__, 'set_active_sitewide_sale' ) );
		add_filter( 'wp_insert_post_data', array( __CLASS__, 'force_publish_status' ), 10, 2 );
		add_action( 'admin_notices', array( __CLASS__, 'override_list_table' ) );
	}

	/**
	 * [renaming_cpt_menu_function description]
	 *
	 * @return [type] [description]
	 */
	public static function renaming_cpt_menu_function() {
		$renaming_menu = apply_filters( 'renaming_cpt_menu_filter', 'PMPro CPTs' );
		return $renaming_menu;
	}

	/**
	 * [register_sitewide_sale_cpt description]
	 *
	 * @return [type] [description]
	 */
	public static function register_sitewide_sale_cpt() {
		$menu_name = self::renaming_cpt_menu_function();

		// Set the custom post type labels.
		$labels['name']                  = _x( 'Sitewide Sales', 'Post Type General Name', 'pmpro-sitewide-sales' );
		$labels['singular_name']         = _x( 'Sitewide Sale', 'Post Type Singular Name', 'pmpro-sitewide-sales' );
		$labels['all_items']             = __( 'All Sitewide Sales', 'pmpro-sitewide-sales' );
		$labels['menu_name']             = $menu_name;
		$labels['name_admin_bar']        = __( 'Sitewide Sales', 'pmpro-sitewide-sales' );
		$labels['all_items']             = __( 'All Sitewide Sales', 'pmpro-sitewide-sales' );
		$labels['add_new_item']          = __( 'Add New Sitewide Sale', 'pmpro-sitewide-sales' );
		$labels['add_new']               = __( 'Add New', 'pmpro-sitewide-sales' );
		$labels['new_item']              = __( 'New Sitewide Sale', 'pmpro-sitewide-sales' );
		$labels['edit_item']             = __( 'Edit Sitewide Sale', 'pmpro-sitewide-sales' );
		$labels['update_item']           = __( 'Update Sitewide Sale', 'pmpro-sitewide-sales' );
		$labels['view_item']             = __( 'View Sitewide Sale', 'pmpro-sitewide-sales' );
		$labels['search_items']          = __( 'Search Sitewide Sales', 'pmpro-sitewide-sales' );
		$labels['not_found']             = __( 'Not found', 'pmpro-sitewide-sales' );
		$labels['not_found_in_trash']    = __( 'Not found in Trash', 'pmpro-sitewide-sales' );
		$labels['insert_into_item']      = __( 'Insert into Sitewide Sale', 'pmpro-sitewide-sales' );
		$labels['uploaded_to_this_item'] = __( 'Uploaded to this Sitewide Sale', 'pmpro-sitewide-sales' );
		$labels['items_list']            = __( 'Sitewide Sales list', 'pmpro-sitewide-sales' );
		$labels['items_list_navigation'] = __( 'Sitewide Sales list navigation', 'pmpro-sitewide-sales' );
		$labels['filter_items_list']     = __( 'Filter sitewide sales list', 'pmpro-sitewide-sales' );

		// Build the post type args.
		$args['labels']              = $labels;
		$args['description']         = __( 'Sitewide Sales', 'pmpro-sitewide-sales' );
		$args['public']              = false;
		$args['publicly_queryable']  = false;
		$args['show_ui']             = true;
		$args['show_in_menu']        = false;
		$args['show_in_nav_menus']   = false;
		$args['can_export']          = true;
		$args['has_archive']         = false;
		$args['rewrite']             = false;
		$args['exclude_from_search'] = true;
		$args['query_var']           = false;
		$args['capability_type']     = 'page';
		$args['show_in_rest']        = false;
		$args['rest_base']           = 'pmpro_sitewide_sale';
		$args['supports']            = array(
			'title',
		);
		/*
		$args['rewrite']             = array(
			'with_front' => true,
			'slug' => 'sws-sitewide-sale',
		);
		*/
		register_post_type( 'pmpro_sitewide_sale', $args );
	}

	/**
	 * Adds Sitewide Sale to admin bar
	 */
	public static function add_cpt_to_admin_bar() {
		global $wp_admin_bar;

		// view menu at all?
		if ( ! current_user_can( 'pmpro_memberships_menu' ) || ! is_admin_bar_showing() ) {
			return;
		}

		// array of all caps in the menu
		$pmpro_caps = pmpro_getPMProCaps();

		// the top level menu links to the first page they have access to
		foreach ( $pmpro_caps as $cap ) {
			if ( current_user_can( $cap ) ) {
				$top_menu_page = str_replace( '_', '-', $cap );
				break;
			}
		}
		if ( current_user_can( 'manage_options' ) ) {
			$wp_admin_bar->add_menu(
				array(
					'id'     => 'pmpro-sitewide-sales',
					'parent' => 'paid-memberships-pro',
					'title'  => __( 'Sitewide Sales', 'paid-memberships-pro' ),
					'href'   => get_admin_url( null, '/edit.php?post_type=pmpro_sitewide_sale' ),
				)
			);
		}
	}

	/**
	 * Adds Sitewide Sale to admin menu
	 */
	public static function add_cpt_to_menu() {
		add_submenu_page( 'pmpro-membershiplevels', __( 'Sitewide Sales', 'paid-memberships-pro' ), __( 'Sitewide Sales', 'paid-memberships-pro' ), 'manage_options', 'edit.php?post_type=pmpro_sitewide_sale' );
	}

	/**
	 * [enqueue_scripts description]
	 *
	 * @return [type] [description]
	 */
	public static function enqueue_scripts() {
		wp_register_script( 'pmpro_sws_set_active_sitewide_sale', plugins_url( 'includes/js/pmpro-sws-set-active-sitewide-sale.js', PMPROSWS_BASENAME ), array( 'jquery' ), '1.0.4' );
		wp_enqueue_script( 'pmpro_sws_set_active_sitewide_sale' );
	}

	/**
	 * set_sitewide_sale_columns Assigning labels to WP_List_Table columns will add a checkbox to the full list page's Screen Options.
	 *
	 * @param [type] $columns [description]
	 */
	public static function set_sitewide_sale_columns( $columns ) {
		unset( $columns['date'] );
		$columns['sale_date']     = __( 'Sale Date', 'pmpro_sitewide_sale' );
		$columns['discount_code'] = __( 'Discount Code', 'pmpro_sitewide_sale' );
		$columns['landing_page']  = __( 'Landing Page', 'pmpro_sitewide_sale' );
		$columns['reports']       = __( 'Reports', 'pmpro_sitewide_sale' );
		$columns['set_active']    = __( 'Select Active Sale', 'pmpro_sitewide_sale' );

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
		switch ( $column ) {
			case 'sale_date':
				$start_date = date_i18n( get_option( 'date_format' ), ( new \DateTime( get_post_meta( $post_id, 'pmpro_sws_start_date', true ) ) )->format( 'U' ) );
				$end_date   = date_i18n( get_option( 'date_format' ), ( new \DateTime( get_post_meta( $post_id, 'pmpro_sws_end_date', true ) ) )->format( 'U' ) );
				echo $start_date;
				echo ' - ';
				echo $end_date;
				break;
			case 'discount_code':
				$discount_code = get_post_meta( $post_id, 'pmpro_sws_discount_code_id', true );
				if ( false !== $discount_code ) {
					global $wpdb;
					$code_name = $wpdb->get_results( $wpdb->prepare( "SELECT code FROM $wpdb->pmpro_discount_codes WHERE id=%s", $discount_code ) );
					if ( 0 < count( $code_name ) && ! empty( $code_name[0]->code ) ) {
						echo esc_html( $code_name[0]->code );
					}
				}
				break;
			case 'landing_page':
				$landing_page = get_post_meta( $post_id, 'pmpro_sws_landing_page_post_id', true );
				if ( false !== $landing_page ) {
					$title = get_the_title( $landing_page );
					if ( ! empty( $title ) ) {
						echo '<a href="' . esc_url( get_permalink( $landing_page ) ) . '">' . esc_html( $title ) . '</a>';
					}
				}
				break;
			case 'reports':
					echo '<a class="button button-primary" href="' . admin_url( 'admin.php?page=pmpro-reports&report=pmpro_sws_reports&pmpro_sws_sitewide_sale_id=' . $post_id ) . '">' . esc_html__( 'View Reports', 'pmpro-sitewide-sales' ) . '</a>';
				break;
			case 'set_active':
				$options = PMPro_SWS_Settings::get_options();
				if ( array_key_exists( 'active_sitewide_sale_id', $options ) && $post_id . '' === $options['active_sitewide_sale_id'] ) {
					echo '<button class="button button-primary pmpro_sws_column_set_active" id="pmpro_sws_column_set_active_' . $post_id . '">' . __( 'Remove Active', 'pmpro-sitewide-sales' ) . '</button>';
				} else {
					echo '<button class="button button-secondary pmpro_sws_column_set_active" id="pmpro_sws_column_set_active_' . $post_id . '">' . __( 'Set Active', 'pmpro-sitewide-sales' ) . '</button>';
				}
				break;
		}
	}

	/**
	 * [set_active_sitewide_sale description]
	 */
	public static function set_active_sitewide_sale() {
		$sitewide_sale_id = $_POST['sitewide_sale_id'];
		$options          = PMPro_SWS_Settings::get_options();

		if ( array_key_exists( 'active_sitewide_sale_id', $options ) && $sitewide_sale_id === $options['active_sitewide_sale_id'] ) {
			$options['active_sitewide_sale_id'] = false;
		} else {
			$options['active_sitewide_sale_id'] = $sitewide_sale_id;
		}

		PMPro_SWS_Settings::save_options( $options );
	}

	/**
	 * [remove_sitewide_sale_row_actions description]
	 */
	public static function remove_sitewide_sale_row_actions( $actions, $post ) {
		// Removes the "Quick Edit" action.
		if ( $post->post_type === 'pmpro_sitewide_sale' ) {
			unset( $actions['inline hide-if-no-js'] );
		}
		
		return $actions;
	}

	/**
	 * Make sure status is always publish.
	 * We must allow trash and auto-draft as well.
	 */
	public static function force_publish_status( $data, $postarr ) {
		if ( $data['post_type'] === 'pmpro_sitewide_sale'
		   && $data['post_status'] !== 'trash'
		   && $data['post_status'] !== 'auto-draft' ) {
			$data['post_status'] = 'publish';
		}

		return $data;
	}

	 /**
	  * Override wp list table if there are no sales yet.
	  */
	public static function override_list_table() {
		$current_screen = get_current_screen();

		if ( $current_screen->base == 'edit'
		  && $current_screen->post_type == 'pmpro_sitewide_sale'
		  && $current_screen->action == ''
		  && ! PMPro_SWS_Setup::has_sitewide_sales() ) {
			?>
			<div class="pmpro-new-install" style="display: none;">
				<h2><?php esc_html_e( 'Welcome to Sitewide Sales', 'pmpro-sitewide-sales' ); ?></h2>
				<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=pmpro_sitewide_sale' ) ); ?>" class="button-primary"><?php esc_html_e( 'Create a Sitewide Sale', 'pmpro-sitewide-sales' ); ?></a>
				<a href="<?php echo esc_url( 'https://www.paidmembershipspro.com/add-ons/sitewide-sale/' ); ?>" target="_blank" class="button"><?php esc_html_e( 'Read Sitewide Sale Docs', 'pmpro-sitewide-sales' ); ?></a>
			</div> <!-- end pmpro-new-install -->
			<?php
		}
	}
}
