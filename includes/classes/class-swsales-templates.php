<?php
namespace Sitewide_Sales\includes\classes;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

/**
 * Handles template loading.
 */
class SWSales_Templates {

	/**
	 * Load templates and setup hooks.
	 */
	public static function init() {
	      require_once( SWSALES_DIR . '/templates/fancy-coupon/fancy-coupon.php' );
	}
    
    /**
     * Get a list of available banner and landing page templates.
     * Assumes banners and landing pages have the same list of templates.
     * @return array of templates
     */
    public static function get_templates() {
        // TODO: Move all of these into the templates folder.
        $templates = array(
            'gradient' => 'Gradient',
            'neon'     => 'Neon',
            'ocean'    => 'Ocean',
            'photo'    => 'Photo',
            'scroll'   => 'Scroll',
            'vintage'  => 'Vintage',
        );
        
        $templates = apply_filters( 'swsale_templates', $templates );
		
		asort( $templates );
        
        return $templates;
    }
}