<?php

if ( ! defined( 'SS_LICENSE_SERVER' ) ) {
    define( 'SS_LICENSE_SERVER', 'https://license.strangerstudios.com/' );
}

/**
 * Add a monthly check to see if license key is still active.
 */
function sws_license_activation() {
    sws_maybe_schedule_event( current_time( 'timestamp' ), 'monthly', 'sws_license_check_key' );
}
register_activation_hook( __FILE__, 'sws_license_activation' );

/**
 * Remove monthly check when plugin is deactivated.
 */
function sws_license_deactivation() {
	wp_clear_scheduled_hook( 'sws_license_check_key' );
}
register_deactivation_hook( __FILE__, 'sws_license_deactivation' );

/**
 * Check to see if customer has a valid license key.
 * 
 * @param string $key the value of the license key.
 * @param string $type the type of key, often used is 'license'.
 * @param bool $force skip the cache when checking license key.
 */
function sws_license_is_valid( $key = NULL, $type = NULL, $force = false ) {

    $sws_license_check = get_option( 'sws_license_check', false );

    if ( empty($force) && $sws_license_check !== false && $sws_license_check['enddate'] > current_time( 'timestamp' ) ) {

        if ( empty( $type ) ) {
            return true;
        } elseif ( $type == $sws_license_check['license'] ) {
            return true;
        } else {
            return false;
        }
    }

    // Get the key and site URL
    if ( empty( $key ) ) {
        $key = get_option( 'sws_license_key', '' );
    }

    if ( ! empty( $key ) ) {
        return sws_license_check_key( $key );
    } else {
        delete_option( 'sws_license_check' );
        add_option( 'sws_license_check', array( 'license' => false, 'enddate' => 0 ), NULL, 'no' );

        return false;
    }
}

/**
 * Check if license key is valid or not.
 * 
 * @param string $key the license key value we need to check.
 */
function sws_license_check_key( $key = NULL ) {

    if ( empty( $key ) ) {
        $key = get_option( 'sws_license_key' );
    }

    if ( ! empty( $key ) ) {
        $url = add_query_arg( array( 'license' => $key, 'domain' => site_url() ), SS_LICENSE_SERVER );

        $timeout = apply_filters( 'sws_license_check_key_timeout', 5 );
        $r = wp_remote_get( $url, array( 'timeout' => $timeout ) );

        if ( is_wp_error( $r ) ) {
           /// Handle errors here.
        } elseif ( ! empty( $r ) && wp_remote_retrieve_response_code( $r ) == 200 ) {

            $r = json_decode( $r['body'] );

            if ( $r->active == 1 ) {

				if ( ! empty( $r->enddate ) ) {
					$enddate = strtotime( $r->enddate, current_time( 'timestamp' ) );
                } else {
					$enddate = strtotime( '+1 Year', current_time( 'timestamp' ) );
                }

				delete_option('sws_license_check');
				add_option('sws_license_check', array( 'license' => $r->license, 'enddate' => $enddate), NULL, 'no');	
				return true;
			} elseif ( ! empty( $r->error ) ) {
				//invalid key
                /// Handle errors here.				
				delete_option('sws_license_check');
				add_option('sws_license_check', array('license'=>false, 'enddate'=>0), NULL, 'no');
                
			}
        }
    }
}
add_action( 'sws_license_check_key', 'sws_license_check_key' );

/**
 * Check if license prompt is paused.
 */
function sws_license_pause() {
    if ( ! empty($_REQUEST['sws_nag_paused'] ) && current_user_can( 'manage_options' ) ) {
		$sws_nag_paused = current_time( 'timestamp' ) + ( 3600*24*7 );
		update_option('sws_nag_paused', $sws_nag_paused, 'no');
		
		return;
    }
}
add_action( 'admin_init', 'sws_license_pause' );

/**
 * Add license nag to plugin admin page headers.
 */
function sws_license_nag() {
    static $sws_nagged;

    // Did we already nag the user?
    if ( ! empty( $sws_nagged ) ) {
        return;
    }

    $sws_nagged = true;

    // Blocked by constant
    if ( defined( 'SWS_LICENSE_NAG' ) && ! SWS_LICENSE_NAG ) {
        return;
    }

    if ( empty( $_REQUEST['post_type'] ) ) {
        return;
    }

    if ( $_REQUEST['post_type'] !== 'sitewide_sale') {
        return;        
    }

    // Don't load nag on license page.
    if ( ! empty( $_REQUEST['page'] ) && $_REQUEST['page'] === 'sitewide_sales_license' ) {
        return;
    }

    // Bail if they have a valid license already.
    if ( sws_license_is_valid() ) {
        return;
    }

    $sws_nag_paused = get_option( 'sws_nag_paused', 0 );

    if ( current_time( 'timestamp' ) < $sws_nag_paused && $sws_nag_paused < current_time( 'timestamp' ) * 3600*24*35 ) {
        return;
    }

    // Get the key to use later.
    $key = get_option( 'sws_license_key' );

    //okay, show nag
    ?>
	<div class="<?php if ( ! empty( $key ) ) { ?>error<?php } else { ?>notice notice-warning<?php } ?> fade">
		<p>
            <?php
                //only show the invalid part if they've entered a key
				if ( ! empty( $key ) ) { ?>
					<strong><?php _e( 'Your Sitewide Sales license key is invalid or expired.', 'sitewide-sales' );?></strong>
				<?php } else { ?>
					<strong><?php _e( 'Enter your Sitewide Sales license key.', 'sitewide-sales' ); ?></strong>
				<?php }
			?>
            <?php _e( 'A license key is required to receive automatic updates and support.', 'sitewide-sales' );?>
            <a href="<?php echo admin_url('edit.php?post_type=sitewide_sale&page=sitewide_sales_license');?>"><?php _e('More Info', 'sitewide-sales' );?></a>&nbsp;|&nbsp;<a href="<?php echo add_query_arg('sws_nag_paused', '1', $_SERVER['REQUEST_URI']);?>"><?php _e('Dismiss', 'sitewide-sales' );?></a>
        </p>
    </div>
    <?php
}
add_action( 'admin_notices', 'sws_license_nag' );