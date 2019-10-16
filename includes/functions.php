<?php
/**
 * Helper functions, often wrapping some functionality burried in a class.
 */
 
 /**
  * What is the active sitewide sale id?
  */
 function swsales_active_sitewide_sale_id() {
     $options = \Sitewide_Sales\includes\classes\SWSales_Settings::get_options();
     return $options['active_sitewide_sale_id'];
 }
 
 /**
  * What is the template for this landing page?
  * Returns false if we're not even on a landing page.
  */
 function swsales_landing_page_template( $post_id = null ) {
     // Default to queried object.
     if ( empty( $post_id ) ) {
         $post_id = get_queried_object_id();
     }

     // Return false if no post.
     if ( empty( $post_id ) ) {
         return false;
     }
     
     // See if any Sitewide Sale CPTs have this post ID set as the Landing Page.
     $sitewide_sale_id = get_post_meta( $post_id, 'swsales_sitewide_sale_id', true );

     // Return false if not a landing page.
     if ( empty( $sitewide_sale_id ) ) {
         return false;
     }

     $template = get_post_meta( $sitewide_sale_id, 'swsales_landing_page_template', true );
     return $template;
 }

 /**
  * What is the template for the banner on this page?
  * Returns false if there is no sale or banner.
  */
 function swsales_banner_template( $post_id = null ) {
     // Default to queried object.
     if ( empty( $post_id ) ) {
         $post_id = get_queried_object_id();
     }

     // Return false if no post.
     if ( empty( $post_id ) ) {
         return false;
     }
     
     // Get active sitewide sale.
     $active_sitewide_sale_id = swsales_active_sitewide_sale_id();
     
     // Return false if no sale.
     if ( empty( $active_sitewide_sale_id ) ) {
         return false;
     }
     
     $template = get_post_meta( $active_sitewide_sale_id, 'swsales_banner_template', true );
     return $template;
 }