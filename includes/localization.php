<?php
function swsales_load_textdomain() {
	load_plugin_textdomain( 'sitewide-sales', false, dirname( __DIR__) . '/languages/' );
}
add_action( 'init', 'swsales_load_textdomain', 1 );