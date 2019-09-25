<?php
namespace Sitewide_Sales\includes\classes;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

class SWSales_Sitewide_Sale {

	/**
	 * ID of SWSales_Sitewide_Sale
	 *
	 * @var int
	 */
	protected $id = 0;

	/**
	 * Name of sale
	 *
	 * @var string
	 */
	protected $name = '';

	/**
	 * Text on banner
	 *
	 * @var string
	 */
	protected $banner_text = 'Save during our Sitewide Sale!';

	/**
	 * Associative array conatining all post meta
	 *
	 * @var array()
	 */
	protected $post_meta = array();

	/**
	 * True if this is the active Sitewide Sale
	 *
	 * @var bool
	 */
	protected $is_active_sitewide_sale = false;

	/**
	 * Constructor for the Sitewide Sale class.
	 */
	public function __construct() {
		// Set default post meta.
		$default_post_meta = array(
			'swsales_start_day'             => $this->get_start_day(),
			'swsales_start_month'           => $this->get_start_month(),
			'swsales_start_year'            => $this->get_start_year(),
			'swsales_end_day'               => $this->get_end_day(),
			'swsales_end_month'             => $this->get_end_month(),
			'swsales_end_year'              => $this->get_end_year(),
			'swsales_sale_type'             => $this->get_sale_type(),
			'swsales_landing_page_post_id'  => $this->get_landing_page_post_id(),
			'swsales_landing_page_template' => $this->get_landing_page_template(),
			'swsales_pre_sale_content'      => $this->get_pre_sale_content(),
			'swsales_sale_content'          => $this->get_sale_content(),
			'swsales_post_sale_content'     => $this->get_post_sale_content(),
			'swsales_use_banner'            => $this->get_use_banner(),
			'swsales_banner_template'       => $this->get_banner_template(),
			'swsales_banner_title'          => $this->get_banner_title(),
			'swsales_link_text'             => $this->get_link_text(),
			'swsales_css_option'            => $this->get_css_option(),
			'swsales_hide_on_checkout'      => $this->get_hide_on_chechout(),
		);

		// Filter to add default post meta.
		$this->$post_meta = apply_filters( 'swsales_default_post_meta', $default_post_meta, $this->get_id() );
	}

	/**
	 * Set all information for Sitewide Sale from database
	 *
	 * @param int $sitewide_sale_id to load.
	 * @return bool $success
	 */
	public function load_sitewide_sale( $sitewide_sale_id ) {
		$raw_post = get_post( $sitewide_sale_id );

		// Check if $sitewide_sale_id is a valid sitewide sale.
		if ( null === $raw_post || 'sitewide_sale' !== $raw_post->post_type ) {
			return false;
		}

		// Load raw info from WP_Post object.
		$this->id          = $raw_post->ID;
		$this->name        = $raw_post->post_title;
		$this->banner_text = $raw_post->post_content;

		// Determine if this Sitewide Sale is active.
		$options                       = SWSales_Settings::get_options();
		$this->is_active_sitewide_sale = ( $options['active_sitewide_sale_id'] == $this->id ? true : false );

		// Merge post meta.
		$raw_post_meta = get_metadata( 'post', $raw_post->ID );
		foreach ( $raw_post_meta as $key => $value ) {
			$raw_post_meta[ $key ] = $value[0];
		}
		$this->post_meta = array_merge( $this->post_meta, $raw_post_meta );

		return true;
	}

	/**
	 * ----------------
	 * GETTER FUNCTIONS
	 * ----------------
	 */

	/**
	 * Returns ID of Sitewide sale
	 * or 0 if not set.
	 *
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Returns the sale name
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Returns the 'day' element of the start date
	 *
	 * @return string
	 */
	public function get_start_day() {
		if ( isset( $this->post_meta['swsales_start_day'] ) ) {
			return $this->post_meta['swsales_start_day'];
		} else {
			return date( 'd', current_time( 'timestamp' ) );
		}
	}

	/**
	 * Returns the 'month' element of the start date
	 *
	 * @return string
	 */
	public function get_start_month() {
		if ( isset( $this->post_meta['swsales_start_month'] ) ) {
			return $this->post_meta['swsales_start_month'];
		} else {
			return date( 'm', current_time( 'timestamp' ) );
		}
	}

	/**
	 * Returns the 'month' element of the start date
	 *
	 * @return string
	 */
	public function get_start_year() {
		if ( isset( $this->post_meta['swsales_start_year'] ) ) {
			return $this->post_meta['swsales_start_year'];
		} else {
			return date( 'Y', current_time( 'timestamp' ) );
		}
	}

	/**
	 * Returns the entire start date in yyyy-mm-dd format
	 *
	 * @param string $dateformatstring date_i18n format.
	 * @return string
	 */
	public function get_start_date( $dateformatstring = null ) {
		if ( null === $dateformatstring ) {
			$dateformatstring = get_option( 'date_format' );
		}
		$start_date = $this->get_start_day() . '-' . $this->get_start_month() . '-' . $this->get_start_year();
		return date_i18n( $dateformatstring, strtotime( $start_date ) );
	}

	/**
	 * Returns the 'day' element of the end date
	 *
	 * @return string
	 */
	public function get_end_day() {
		if ( isset( $this->post_meta['swsales_end_day'] ) ) {
			return $this->post_meta['swsales_end_day'];
		} else {
			return date( 'd', strtotime( '+1 week', current_time( 'timestamp' ) ) );
		}
	}

	/**
	 * Returns the 'month' element of the end date
	 *
	 * @return string
	 */
	public function get_end_month() {
		if ( isset( $this->post_meta['swsales_end_month'] ) ) {
			return $this->post_meta['swsales_end_month'];
		} else {
			return date( 'm', strtotime( '+1 week', current_time( 'timestamp' ) ) );
		}
	}

	/**
	 * Returns the 'month' element of the end date
	 *
	 * @return string
	 */
	public function get_end_year() {
		if ( isset( $this->post_meta['swsales_end_year'] ) ) {
			return $this->post_meta['swsales_end_year'];
		} else {
			return date( 'Y', strtotime( '+1 week', current_time( 'timestamp' ) ) );
		}
	}

	/**
	 * Returns the entire end date in yyyy-mm-dd format
	 *
	 * @param string $dateformatstring date_i18n format.
	 * @return string
	 */
	public function get_end_date( $dateformatstring = null ) {
		if ( null === $dateformatstring ) {
			$dateformatstring = get_option( 'date_format' );
		}
		$end_date = $this->get_end_day() . '-' . $this->get_end_month() . '-' . $this->get_end_year();
		return date_i18n( $dateformatstring, strtotime( $end_date ) );
	}

	/**
	 * Returns 'past', 'current' or 'future' based
	 * on the sale start/end dates and the current date.
	 * If start date is after end date, returns 'error'
	 *
	 * @return string
	 */
	public function get_time_period() {
		$current_date = date( 'Y-m-d', current_time( 'timestamp' ) );

		if ( $this->get_start_date( 'Y-m-d' ) > $this->get_end_date( 'Y-m-d' ) ) {
			return 'error';
		} elseif ( $current_date < $this->get_start_date( 'Y-m-d' ) ) {
			return 'pre-sale';
		} elseif ( $current_date > $this->get_end_date( 'Y-m-d' ) ) {
			return 'post-sale';
		}
		return 'sale';
	}

	/**
	 * Returns the sale type (module)
	 *
	 * @return string
	 */
	public function get_sale_type() {
		if ( isset( $this->post_meta['swsales_sale_type'] ) ) {
			return $this->post_meta['swsales_sale_type'];
		} else {
			return '';
		}
	}

	/**
	 * Returns the ID of the sale landing page
	 * or 0 if it is not set.
	 *
	 * @return int
	 */
	public function get_landing_page_post_id() {
		if ( isset( $this->post_meta['swsales_landing_page_post_id'] ) ) {
			return $this->post_meta['swsales_landing_page_post_id'];
		} else {
			return 0;
		}
	}

	/**
	 * Returns landing page template
	 *
	 * @return string
	 */
	public function get_landing_page_template() {
		if ( isset( $this->post_meta['swsales_landing_page_template'] ) ) {
			return $this->post_meta['swsales_landing_page_template'];
		} else {
			return '';
		}
	}

	/**
	 * Returns pre-sale content
	 *
	 * @return string
	 */
	public function get_pre_sale_content() {
		if ( isset( $this->post_meta['swsales_pre_sale_content'] ) ) {
			return $this->post_meta['swsales_pre_sale_content'];
		} else {
			return '';
		}
	}

	/**
	 * Returns sale content
	 *
	 * @return string
	 */
	public function get_sale_content() {
		if ( isset( $this->post_meta['swsales_sale_content'] ) ) {
			return $this->post_meta['swsales_sale_content'];
		} else {
			return '';
		}
	}

	/**
	 * Returns post-sale content
	 *
	 * @return string
	 */
	public function get_post_sale_content() {
		if ( isset( $this->post_meta['swsales_post_sale_content'] ) ) {
			return $this->post_meta['swsales_post_sale_content'];
		} else {
			return '';
		}
	}

	/**
	 * Returns the appropriate sale content
	 * based on passed time period
	 *
	 * @return string
	 */
	public function get_sale_content_for_time_period( $time_period ) {
		switch ( $time_period ) {
			case 'post-sale':
				return $this->get_post_sale_content();
			case 'sale':
				return $this->get_sale_content();
			case 'pre-sale':
				return $this->get_pre_sale_content();
			default:
				return '';
		}
	}

	/**
	 * Returns the appropriate sale content
	 * based on if the sale is prior, current,
	 * or future.
	 *
	 * @return string
	 */
	public function get_current_sale_content() {
		get_sale_content_for_time_period( $this->get_time_period() );
	}

	/**
	 * Returns the style of banner to use, or
	 * 'no' if no banner should be used
	 *
	 * @return string
	 */
	public function get_use_banner() {
		if ( isset( $this->post_meta['swsales_use_banner'] ) ) {
			return $this->post_meta['swsales_use_banner'];
		} else {
			return 'no';
		}
	}

	/**
	 * Returns banner template
	 *
	 * @return string
	 */
	public function get_banner_template() {
		if ( isset( $this->post_meta['swsales_banner_template'] ) ) {
			return $this->post_meta['swsales_banner_template'];
		} else {
			return '';
		}
	}

	/**
	 * Returns landing page template
	 *
	 * @return string
	 */
	public function get_banner_title() {
		if ( isset( $this->post_meta['swsales_banner_title'] ) ) {
			return $this->post_meta['swsales_banner_title'];
		} else {
			return 'Limited Time Offer';
		}
	}

	/**
	 * Returns landing page template
	 *
	 * @return string
	 */
	public function get_banner_text() {
		return $this->banner_text;
	}

	/**
	 * Returns link text
	 *
	 * @return string
	 */
	public function get_link_text() {
		if ( isset( $this->post_meta['swsales_link_text'] ) ) {
			return $this->post_meta['swsales_link_text'];
		} else {
			return 'Buy Now';
		}
	}

	/**
	 * Returns css option
	 *
	 * @return string
	 */
	public function get_css_option() {
		if ( isset( $this->post_meta['swsales_css_option'] ) ) {
			return $this->post_meta['swsales_css_option'];
		} else {
			return '';
		}
	}

	/**
	 * Returns if banner should be hidden on checkout
	 *
	 * @return bool
	 */
	public function get_hide_on_chechout() {
		if ( isset( $this->post_meta['swsales_hide_on_checkout'] ) ) {
			return $this->post_meta['swsales_hide_on_checkout'];
		} else {
			return true;
		}
	}

	/**
	 * Returns if this is the active Sitewide Sale
	 *
	 * @return bool
	 */
	public function is_active_sitewide_sale() {
		return $this->is_active_sitewide_sale;
	}

	/**
	 * Gets specific piece of post meta.
	 *
	 * @param  string $meta_key of data.
	 * @param  mixed  $default data to return if metadata not found.
	 * @return mixed metadata
	 */
	public function get_meta_value( $meta_key, $default = null ) {
		if ( isset( $this->$post_meta[ $meta_key ] ) ) {
			return $this->$post_meta[ $meta_key ];
		}
		return $default;
	}

	/**
	 * ----------------
	 * HELPER FUNCTIONS
	 * ----------------
	 */
	public function is_running() {
		return ( $this->is_active_sitewide_sale() && 'sale' === $this->get_current_sale_content() );
	}
}
