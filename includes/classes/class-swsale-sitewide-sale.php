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
	 * Start date for the sale on format dd/mm/yyyy
	 *
	 * @var string
	 */
	protected $start_date = date( 'd-m-Y', current_time( 'timestamp' ) );

	/**
	 * End date for the sale on format dd/mm/yyyy
	 *
	 * @var string
	 */
	 protected $end_date = strtotime( '+1 week', current_time( 'timestamp' ) );

	/**
	 * Module which the sale is using
	 *
	 * @var string
	 */
	protected $sale_type = '';

	/**
	 * Landing page ID for the sale
	 *
	 * @var int
	 */
	protected $landing_page_post_id = 0;

	/**
	 * Template for the landing page
	 *
	 * @var string
	 */
	protected $landing_page_template = '';

	/**
	 * Text to display pre-sale
	 *
	 * @var string
	 */
	protected $pre_sale_content = '';

	/**
	 * Text to display during sale
	 *
	 * @var string
	 */
	protected $sale_content = '';

	/**
	 * Text to display post-sale
	 *
	 * @var string
	 */
	protected $post_sale_content = '';

	/**
	 * Banner for sale
	 *
	 * @var SWSales_Banner
	 */
	protected $banner;

	/**
	 * True if this is the active Sitewide Sale
	 *
	 * @var bool
	 */
	protected $is_active_sitewide_sale;

	/**
	 * Associative array conatining all post meta
	 *
	 * @var array()
	 */
	protected $post_meta;

	/**
	 * Constructor for the Sitewide Sale class.
	 */
	public function __construct() {
		$banner = new SWSales_Banner();
	}

	/**
	 * Set all information for Sitewide Sale from database
	 *
	 * @param int $sitewide_sale_id to load.
	 * @return bool $success
	 */
	public function load_sitewide_sale( $sitewide_sale_id ) {
		// Check if $sitewide_sale_id is a valid sitewide sale.
		if ( ! 'sitewide_sale' === get_post_type( $sitewide_sale_id ) ) {
			return false;
		}

		// TODO: Load all information.
		$this->raw_metadata = get_metadata( 'post', $sitewide_sale_id );
		$this->id           = $sitewide_sale_id;
		$this->name         = get_the_title( $sitewide_sale_id );
		$start_day          = $this->raw_metadata['swsales_start_day'];
		$start_month        = $this->raw_metadata['swsales_start_month'];
		$start_year         = $this->raw_metadata['swsales_start_year'];
		$this->start_date   = date_i18n( 'Y-m-d', strtotime( $start_month . '/' . $start_day . '/' . $start_year ) );
		$end_day            = $this->raw_metadata['swsales_end_day'];
		$end_month          = $this->raw_metadata['swsales_end_month'];
		$end_year           = $this->raw_metadata['swsales_end_year'];
		$this->end_date     = date_i18n( 'Y-m-d', strtotime( $end_month . '/' . $end_day . '/' . $end_year ) );
		$this->sale_type    = $this->raw_metadata['swsales_end_day'];

		return false;
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
	 * Returns the entire start date
	 *
	 * @return string
	 */
	public function get_start_date() {
		return $this->start_date;
	}

	/**
	 * Returns the 'day' element of the start date
	 *
	 * @return string
	 */
	public function get_start_day() {
		return substr( $this->start_date, 0, 2 );
	}

	/**
	 * Returns the 'month' element of the start date
	 *
	 * @return string
	 */
	public function get_start_month() {
		return substr( $this->start_date, 3, 2 );
	}

	/**
	 * Returns the 'month' element of the start date
	 *
	 * @return string
	 */
	public function get_start_year() {
		return substr( $this->start_date, 6, 4 );
	}

	/**
	 * Returns the entire end date
	 *
	 * @return string
	 */
	public function get_end_date() {
		return $this->end_date;
	}

	/**
	 * Returns the 'day' element of the end date
	 *
	 * @return string
	 */
	public function get_end_day() {
		return substr( $this->end_date, 0, 2 );
	}

	/**
	 * Returns the 'month' element of the end date
	 *
	 * @return string
	 */
	public function get_end_month() {
		return substr( $this->end_date, 3, 2 );
	}

	/**
	 * Returns the 'month' element of the end date
	 *
	 * @return string
	 */
	public function get_end_year() {
		return substr( $this->end_date, 6, 4 );
	}

	/**
	 * Returns 'past', 'current' or 'future' based
	 * on the sale start/end dates and the current date
	 *
	 * @return string
	 */
	public function get_time_period() {
		// TODO: Implement this.
		return '';
	}

	/**
	 * Returns the sale type (module)
	 *
	 * @return string
	 */
	public function get_sale_type() {
		return $this->sale_type;
	}

	/**
	 * Returns the ID of the sale landing page
	 * or 0 if it is not set.
	 *
	 * @return string
	 */
	public function get_landing_page_post_id() {
		return $this->landing_page_post_id;
	}

	/**
	 * Returns landing page template
	 *
	 * @return string
	 */
	public function get_landing_page_template() {
		return $this->landing_page_template;
	}

	/**
	 * Returns pre-sale content
	 *
	 * @return string
	 */
	public function get_pre_sale_content() {
		return $this->pre_sale_content;
	}

	/**
	 * Returns sale content
	 *
	 * @return string
	 */
	public function get_sale_content() {
		return $this->sale_content;
	}

	/**
	 * Returns post-sale content
	 *
	 * @return string
	 */
	public function get_post_sale_content() {
		return $this->post_sale_content;
	}

	/**
	 * Returns the appropriate sale content
	 * based on if the sale is prior, current,
	 * or future.
	 *
	 * @return string
	 */
	public function get_current_sale_content() {
		// TODO: Implement this.
		return '';
	}

	/**
	 * Returns banner for sale
	 *
	 * @return SWSales_Banner
	 */
	public function get_banner() {
		return $this->banner;
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
		if ( isset( $this->$raw_metadata[ $meta_key ] ) ) {
			return $this->$raw_metadata[ $meta_key ];
		}
		return $default;
	}

	/**
	 * ----------------
	 * HELPER FUNCTIONS
	 * ----------------
	 */
	public function is_running() {
		return ( is_active_sitewide_sale() && 'current' === get_current_sale_content() );
	}
}
