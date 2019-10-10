<?php
/**
 * Plugin Name: Sitewide Sales
 * Plugin URI: https://github.com/strangerstudios/sitewide-sales
 * Description: Create, manage, and view advanced reports for a sitewide or flash sale on membership (Black Friday or Cyber Monday).
 * Author: Stranger Studios
 * Author URI: https://www.strangerstudios.com
 * Version: .1
 * Plugin URI:
 * License: GNU GPLv2+
 * Text Domain: sitewide-sales
 *
 * @package sitewide-sales
 */
namespace Sitewide_Sales;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

define( 'SWSALES_VERSION', '0.1' );
define( 'SWSALES_DIR', dirname( __FILE__ ) );
define( 'SWSALES_BASENAME', plugin_basename( __FILE__ ) );

require 'autoload.php';

// Handles registering banners and displaying banners on frontend.
includes\classes\SWSales_Banners::init();

// Sets up shortcode [swsales] and landing page-related code.
includes\classes\SWSales_Landing_Pages::init();

// Handles displaying/saving metaboxes for Sitewide Sale CPT and
// returning from editing a discount code/landing page associated
// with Sitewide Sale.
includes\classes\SWSales_MetaBoxes::init();

// Sets up Sitewide Sale CPT and associated menu.
includes\classes\SWSales_Post_Types::init();

// Generates report pages and enqueues JS to track interaction
// with Sitewide Sale.
includes\classes\SWSales_Reports::init();

// Sets up pmpro_sitewide_sale option.
includes\classes\SWSales_Settings::init();

// Enqueues scripts and does other administrative things.
includes\classes\SWSales_Setup::init();

require_once SWSALES_DIR . '/modules/class-swsales-module-pmpro.php';
