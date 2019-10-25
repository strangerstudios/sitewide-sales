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

		$menu_icon_svg = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4KPCEtLSBHZW5lcmF0b3I6IEFkb2JlIElsbHVzdHJhdG9yIDIzLjAuNiwgU1ZHIEV4cG9ydCBQbHVnLUluIC4gU1ZHIFZlcnNpb246IDYuMDAgQnVpbGQgMCkgIC0tPgo8c3ZnIHZlcnNpb249IjEuMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgeD0iMHB4IiB5PSIwcHgiCgkgdmlld0JveD0iMCAwIDE4IDE4IiBzdHlsZT0iZW5hYmxlLWJhY2tncm91bmQ6bmV3IDAgMCAxOCAxODsiIHhtbDpzcGFjZT0icHJlc2VydmUiPgo8c3R5bGUgdHlwZT0idGV4dC9jc3MiPgoJLnN0MHtmaWxsOiNDOUQzREU7fQoJLnN0MXtmaWxsOiM0NDczOEQ7fQoJLnN0MntjbGlwLXBhdGg6dXJsKCNTVkdJRF8yXyk7fQoJLnN0M3tmaWxsOiNENzVDMzY7fQo8L3N0eWxlPgo8ZyBpZD0iTGF5ZXJfMiI+Cgk8cGF0aCBjbGFzcz0ic3QwIiBkPSJNNC45MSwxMy4wM0w0LjY4LDEzYy0wLjIzLTAuMDItMC40NS0wLjA5LTAuNjUtMC4ybC0wLjItMC4xbDAuMjEtMC40bDAuMiwwLjFjMC4xNSwwLjA4LDAuMzIsMC4xMywwLjQ5LDAuMTUKCQlsMC4yMiwwLjAyTDQuOTEsMTMuMDN6Ii8+Cgk8cGF0aCBjbGFzcz0ic3QwIiBkPSJNMTYuMDMsMTMuMDJsLTAuMDItMC40NWwwLjIyLTAuMDFjMC4xNy0wLjAxLDAuMzQtMC4wNSwwLjUtMC4xMmwwLjItMC4wOWwwLjE4LDAuNDFsLTAuMiwwLjA5CgkJYy0wLjIxLDAuMDktMC40MywwLjE1LTAuNjYsMC4xNkwxNi4wMywxMy4wMnoiLz4KCTxyZWN0IHg9IjE0LjY3IiB5PSIxMi41NiIgY2xhc3M9InN0MCIgd2lkdGg9IjEuMDUiIGhlaWdodD0iMC40NSIvPgoJPHJlY3QgeD0iMTMuMzIiIHk9IjEyLjU2IiBjbGFzcz0ic3QwIiB3aWR0aD0iMS4wNSIgaGVpZ2h0PSIwLjQ1Ii8+Cgk8cmVjdCB4PSIxMS45NyIgeT0iMTIuNTYiIGNsYXNzPSJzdDAiIHdpZHRoPSIxLjA1IiBoZWlnaHQ9IjAuNDUiLz4KCTxyZWN0IHg9IjEwLjYyIiB5PSIxMi41NiIgY2xhc3M9InN0MCIgd2lkdGg9IjEuMDUiIGhlaWdodD0iMC40NSIvPgoJPHJlY3QgeD0iOS4yOCIgeT0iMTIuNTYiIGNsYXNzPSJzdDAiIHdpZHRoPSIxLjA1IiBoZWlnaHQ9IjAuNDUiLz4KCTxyZWN0IHg9IjcuOTMiIHk9IjEyLjU2IiBjbGFzcz0ic3QwIiB3aWR0aD0iMS4wNSIgaGVpZ2h0PSIwLjQ1Ii8+Cgk8cmVjdCB4PSI1LjIzIiB5PSIxMi41NiIgY2xhc3M9InN0MCIgd2lkdGg9IjEuMDUiIGhlaWdodD0iMC40NSIvPgoJPHBhdGggY2xhc3M9InN0MCIgZD0iTTE3LjQ0LDEyLjUxbC0wLjM1LTAuMjhsMC4xNC0wLjE3YzAuMTEtMC4xMywwLjE5LTAuMjksMC4yNS0wLjQ1bDAuMDctMC4yMWwwLjQzLDAuMTRsLTAuMDcsMC4yMQoJCWMtMC4wNywwLjIyLTAuMTgsMC40Mi0wLjMzLDAuNkwxNy40NCwxMi41MXoiLz4KCTxwYXRoIGNsYXNzPSJzdDAiIGQ9Ik0zLjUzLDEyLjQ0TDMuNCwxMi4yNWMtMC4xMy0wLjE5LTAuMjMtMC4zOS0wLjI5LTAuNjFsLTAuMDYtMC4yMmwwLjQzLTAuMTFsMC4wNiwwLjIyCgkJYzAuMDQsMC4xNywwLjEyLDAuMzIsMC4yMiwwLjQ2bDAuMTMsMC4xOEwzLjUzLDEyLjQ0eiIvPgoJPHJlY3QgeD0iMTcuNTUiIHk9IjEwLjExIiBjbGFzcz0ic3QwIiB3aWR0aD0iMC40NSIgaGVpZ2h0PSIxLjA1Ii8+Cgk8cmVjdCB4PSIzLjA1IiB5PSIxMC4wMiIgY2xhc3M9InN0MCIgd2lkdGg9IjAuNDUiIGhlaWdodD0iMS4wNSIvPgoJPHJlY3QgeD0iMTcuNTUiIHk9IjguNzYiIGNsYXNzPSJzdDAiIHdpZHRoPSIwLjQ1IiBoZWlnaHQ9IjEuMDUiLz4KCTxyZWN0IHg9IjE3LjU1IiB5PSI3LjQyIiBjbGFzcz0ic3QwIiB3aWR0aD0iMC40NSIgaGVpZ2h0PSIxLjA1Ii8+Cgk8cmVjdCB4PSIzLjA1IiB5PSI3LjMyIiBjbGFzcz0ic3QwIiB3aWR0aD0iMC40NSIgaGVpZ2h0PSIxLjA1Ii8+Cgk8cmVjdCB4PSIxNy41NSIgeT0iNi4wNyIgY2xhc3M9InN0MCIgd2lkdGg9IjAuNDUiIGhlaWdodD0iMS4wNSIvPgoJPHJlY3QgeD0iMy4wNSIgeT0iNS45NyIgY2xhc3M9InN0MCIgd2lkdGg9IjAuNDUiIGhlaWdodD0iMS4wNSIvPgoJPHJlY3QgeD0iMTcuNTUiIHk9IjQuNzIiIGNsYXNzPSJzdDAiIHdpZHRoPSIwLjQ1IiBoZWlnaHQ9IjEuMDUiLz4KCTxyZWN0IHg9IjMuMDUiIHk9IjQuNjMiIGNsYXNzPSJzdDAiIHdpZHRoPSIwLjQ1IiBoZWlnaHQ9IjEuMDUiLz4KCTxyZWN0IHg9IjE3LjU1IiB5PSIzLjM3IiBjbGFzcz0ic3QwIiB3aWR0aD0iMC40NSIgaGVpZ2h0PSIxLjA1Ii8+Cgk8cmVjdCB4PSIzLjA1IiB5PSIzLjI4IiBjbGFzcz0ic3QwIiB3aWR0aD0iMC40NSIgaGVpZ2h0PSIxLjA1Ii8+Cgk8cGF0aCBjbGFzcz0ic3QwIiBkPSJNMTcuNTUsMi45M1YyLjU4YzAtMC4xLTAuMDEtMC4xOS0wLjAzLTAuMjlsLTAuMDUtMC4yMmwwLjQ0LTAuMDlsMC4wNSwwLjIyQzE3Ljk5LDIuMzMsMTgsMi40NiwxOCwyLjU4djAuMDQKCQlMMTcuNTUsMi45M3oiLz4KCTxwYXRoIGNsYXNzPSJzdDAiIGQ9Ik0zLjQ5LDIuOThIMy4wNVYyLjU4YzAtMC4xNiwwLjAyLTAuMzMsMC4wNi0wLjQ4bDAuMDYtMC4yMkwzLjYsMkwzLjU0LDIuMjJDMy41MSwyLjMzLDMuNDksMi40NiwzLjQ5LDIuNTgKCQlDMy40OSwyLjU4LDMuNDksMi45OCwzLjQ5LDIuOTh6Ii8+Cgk8cGF0aCBjbGFzcz0ic3QwIiBkPSJNMTcuMzksMS44OWwtMC4xNC0wLjE4Yy0wLjExLTAuMTQtMC4yNC0wLjI1LTAuMzktMC4zNGwtMC4xOS0wLjExbDAuMjMtMC4zOUwxNy4wOSwxCgkJYzAuMiwwLjEyLDAuMzcsMC4yNiwwLjUxLDAuNDRsMC4xNCwwLjE4TDE3LjM5LDEuODl6Ii8+Cgk8cGF0aCBjbGFzcz0ic3QwIiBkPSJNMy43LDEuODJsLTAuMzMtMC4zbDAuMTUtMC4xN2MwLjE1LTAuMTcsMC4zMy0wLjMxLDAuNTQtMC40MWwwLjItMC4xbDAuMiwwLjRsLTAuMiwwLjEKCQlDNC4xLDEuNDIsMy45NiwxLjUzLDMuODUsMS42NkwzLjcsMS44MnoiLz4KCTxwYXRoIGNsYXNzPSJzdDAiIGQ9Ik0xNi40NywxLjIxTDE2LjI1LDEuMmMtMC4wMywwLTAuMDYsMC0wLjA5LDBoLTAuNzJWMC43NWgwLjcyYzAuMDQsMCwwLjA4LDAsMC4xMiwwbDAuMjIsMC4wMUwxNi40NywxLjIxeiIvPgoJPHJlY3QgeD0iMTQuMDkiIHk9IjAuNzUiIGNsYXNzPSJzdDAiIHdpZHRoPSIxLjA1IiBoZWlnaHQ9IjAuNDUiLz4KCTxyZWN0IHg9IjEyLjc1IiB5PSIwLjc1IiBjbGFzcz0ic3QwIiB3aWR0aD0iMS4wNSIgaGVpZ2h0PSIwLjQ1Ii8+Cgk8cmVjdCB4PSIxMS40IiB5PSIwLjc1IiBjbGFzcz0ic3QwIiB3aWR0aD0iMS4wNSIgaGVpZ2h0PSIwLjQ1Ii8+Cgk8cmVjdCB4PSIxMC4wNSIgeT0iMC43NSIgY2xhc3M9InN0MCIgd2lkdGg9IjEuMDUiIGhlaWdodD0iMC40NSIvPgoJPHJlY3QgeD0iOC43IiB5PSIwLjc1IiBjbGFzcz0ic3QwIiB3aWR0aD0iMS4wNSIgaGVpZ2h0PSIwLjQ1Ii8+Cgk8cmVjdCB4PSI3LjM1IiB5PSIwLjc1IiBjbGFzcz0ic3QwIiB3aWR0aD0iMS4wNSIgaGVpZ2h0PSIwLjQ1Ii8+Cgk8cmVjdCB4PSI2LjAxIiB5PSIwLjc1IiBjbGFzcz0ic3QwIiB3aWR0aD0iMS4wNSIgaGVpZ2h0PSIwLjQ1Ii8+Cgk8cG9seWdvbiBjbGFzcz0ic3QwIiBwb2ludHM9IjUuNywxLjE5IDQuNjYsMS4xOSA0LjY1LDAuNzUgNC44OCwwLjc1IDQuODgsMC45NyA0Ljg4LDAuNzUgNS43LDAuNzUgCSIvPgoJPHJlY3QgeD0iMy4wNSIgeT0iOC42OCIgY2xhc3M9InN0MCIgd2lkdGg9IjAuNDUiIGhlaWdodD0iMS4wNSIvPgoJPHJlY3QgeD0iNi41OCIgeT0iMTIuNTYiIGNsYXNzPSJzdDAiIHdpZHRoPSIxLjA1IiBoZWlnaHQ9IjAuNDUiLz4KPC9nPgo8ZyBpZD0iTGF5ZXJfMSI+Cgk8Zz4KCQk8Zz4KCQkJPGc+CgkJCQk8Y2lyY2xlIGNsYXNzPSJzdDEiIGN4PSI3LjkzIiBjeT0iNC4xMSIgcj0iMS4wNyIvPgoJCQkJPGNpcmNsZSBjbGFzcz0ic3QxIiBjeD0iMTMuMTMiIGN5PSI5LjU5IiByPSIxLjc3Ii8+CgkJCTwvZz4KCQkJPGc+CgkJCQk8Zz4KCQkJCQk8ZGVmcz4KCQkJCQkJPHBhdGggaWQ9IlNWR0lEXzFfIiBkPSJNMCwwLjc1aDE1LjQ0aDAuNzJjMC4wNCwwLDAuMDgsMCwwLjEyLDBsMC4yMiwwLjAxbDAuMzksMC4xMkwxNy4wOSwxYzAuMiwwLjEyLDAuMzcsMC4yNiwwLjUxLDAuNDQKCQkJCQkJCWwwLjE0LDAuMThsMC4xNywwLjM3bDAuMDUsMC4yMkMxNy45OSwyLjMzLDE4LDIuNDUsMTgsMi41OHYwLjA0djE0LjYzSDBWMC43NXoiLz4KCQkJCQk8L2RlZnM+CgkJCQkJPGNsaXBQYXRoIGlkPSJTVkdJRF8yXyI+CgkJCQkJCTx1c2UgeGxpbms6aHJlZj0iI1NWR0lEXzFfIiAgc3R5bGU9Im92ZXJmbG93OnZpc2libGU7Ii8+CgkJCQkJPC9jbGlwUGF0aD4KCQkJCQk8ZyBpZD0iQ2hlY2siIGNsYXNzPSJzdDIiPgoJCQkJCQk8cGF0aCBjbGFzcz0ic3QzIiBkPSJNMjMuMzQtMC44NWMtMC40NywwLjE3LTAuOTQsMC4zNi0xLjQxLDAuNTRjLTAuNDYsMC4xOS0wLjkzLDAuMzgtMS4zOSwwLjU4CgkJCQkJCQljLTAuNDYsMC4yLTAuOTIsMC40LTEuMzcsMC42MWMtMC4wMSwwLjAxLTAuMDIsMC4wMS0wLjA0LDAuMDJjLTAuMDEsMC4wMS0wLjAyLDAuMDEtMC4wNCwwLjAyCgkJCQkJCQljLTAuMDEsMC4wMS0wLjAyLDAuMDEtMC4wNCwwLjAycy0wLjAyLDAuMDEtMC4wNCwwLjAyYy0wLjM3LDAuMTctMC43NCwwLjM0LTEuMTEsMC41MkMxNy41NiwxLjY0LDE3LjIsMS44MiwxNi44NCwyCgkJCQkJCQljLTAuMzYsMC4xOC0wLjcxLDAuMzctMS4wNiwwLjU2Yy0wLjM1LDAuMTktMC43LDAuMzgtMS4wNCwwLjU3Yy0wLjEyLDAuMDctMC4yNCwwLjE0LTAuMzcsMC4yMQoJCQkJCQkJQzE0LjI0LDMuNDEsMTQuMTIsMy40OCwxNCwzLjU1Yy0wLjEyLDAuMDctMC4yNCwwLjE0LTAuMzYsMC4yMXMtMC4yNCwwLjE0LTAuMzYsMC4yMWMtMC4xLDAuMDYtMC4yMSwwLjEzLTAuMzEsMC4xOQoJCQkJCQkJYy0wLjEsMC4wNi0wLjIsMC4xMy0wLjMsMC4xOWMtMC4xLDAuMDYtMC4yLDAuMTMtMC4zLDAuMTljLTAuMSwwLjA2LTAuMiwwLjEzLTAuMywwLjE5Yy0wLjA3LDAuMDQtMC4xMywwLjA5LTAuMiwwLjEzCgkJCQkJCQljLTAuMDcsMC4wNC0wLjEzLDAuMDktMC4yLDAuMTNjLTAuMDcsMC4wNC0wLjEzLDAuMDktMC4yLDAuMTNjLTAuMDcsMC4wNC0wLjEzLDAuMDktMC4xOSwwLjEzCgkJCQkJCQljLTAuMDIsMC4wMi0wLjA0LDAuMDMtMC4wNywwLjA1Yy0wLjAyLDAuMDItMC4wNCwwLjAzLTAuMDcsMC4wNWMtMC4wMiwwLjAyLTAuMDQsMC4wMy0wLjA3LDAuMDUKCQkJCQkJCWMtMC4wMiwwLjAyLTAuMDQsMC4wMy0wLjA3LDAuMDVjLTAuMDgsMC4wNS0wLjE2LDAuMTEtMC4yNCwwLjE3Yy0wLjA4LDAuMDUtMC4xNiwwLjExLTAuMjQsMC4xNwoJCQkJCQkJYy0wLjA4LDAuMDYtMC4xNiwwLjExLTAuMjMsMC4xN2MtMC4wOCwwLjA2LTAuMTUsMC4xMS0wLjIzLDAuMTdDOS41Nyw2LjUsOS4wNyw2Ljg4LDguNTgsNy4yOEM4LjEsNy42Nyw3LjY0LDguMDcsNy4xOCw4LjQ4CgkJCQkJCQlDNi43NCw4Ljg5LDYuMyw5LjMxLDUuODgsOS43NWMtMC40MiwwLjQzLTAuODIsMC44Ny0xLjIxLDEuMzJjLTAuMDQsMC4wNC0wLjA3LDAuMDgtMC4xMSwwLjEyYy0wLjA0LDAuMDQtMC4wNywwLjA4LTAuMSwwLjEyCgkJCQkJCQljLTAuMDMsMC4wNC0wLjA3LDAuMDgtMC4xLDAuMTJjLTAuMDMsMC4wNC0wLjA3LDAuMDgtMC4xLDAuMTNjLTAuMDMsMC4wMy0wLjA1LDAuMDYtMC4wOCwwLjFjLTAuMDMsMC4wMy0wLjA1LDAuMDYtMC4wOCwwLjEKCQkJCQkJCWMtMC4wMywwLjAzLTAuMDUsMC4wNi0wLjA4LDAuMWMtMC4wMiwwLjAzLTAuMDUsMC4wNy0wLjA4LDAuMWMtMC4wMS0wLjAyLTAuMDMtMC4wMy0wLjA0LTAuMDUKCQkJCQkJCWMtMC4wMS0wLjAyLTAuMDMtMC4wMy0wLjA0LTAuMDVjLTAuMDEtMC4wMi0wLjAzLTAuMDMtMC4wNC0wLjA1Yy0wLjAxLTAuMDItMC4wMy0wLjAzLTAuMDQtMC4wNQoJCQkJCQkJYy0wLjEzLTAuMTYtMC4yNS0wLjMtMC4zNy0wLjQzYy0wLjEyLTAuMTMtMC4yMi0wLjI1LTAuMzMtMC4zN2MtMC4xLTAuMTItMC4yLTAuMjItMC4zLTAuMzNjLTAuMS0wLjEtMC4xOS0wLjItMC4yOC0wLjMKCQkJCQkJCWMtMC4wMi0wLjAyLTAuMDUtMC4wNS0wLjA3LTAuMDhjLTAuMDItMC4wMi0wLjA1LTAuMDUtMC4wNy0wLjA4Yy0wLjAyLTAuMDItMC4wNS0wLjA1LTAuMDctMC4wOAoJCQkJCQkJYy0wLjAyLTAuMDItMC4wNS0wLjA1LTAuMDctMC4wOGMtMC4xLTAuMTEtMC4yLTAuMjItMC4zLTAuMzRjLTAuMS0wLjEyLTAuMjEtMC4yNC0wLjMxLTAuMzdDMS41MSw5LjE5LDEuNCw5LjA2LDEuMjgsOC45MQoJCQkJCQkJQzEuMTcsOC43NiwxLjA1LDguNiwwLjkyLDguNDNDMC44Niw4LjM1LDAuOCw4LjI3LDAuNzQsOC4xOUMwLjY4LDguMTEsMC42Miw4LjAyLDAuNTYsNy45M0MwLjQ5LDcuODQsMC40Myw3Ljc1LDAuMzYsNy42NgoJCQkJCQkJQzAuMjksNy41NiwwLjIzLDcuNDYsMC4xNiw3LjM2YzAuMTQsMC40NSwwLjI3LDAuODcsMC40LDEuMjdDMC42OCw5LjAzLDAuOCw5LjQyLDAuOTIsOS43OGMwLjEyLDAuMzcsMC4yNCwwLjcyLDAuMzUsMS4wNgoJCQkJCQkJYzAuMTIsMC4zNCwwLjIzLDAuNjcsMC4zNCwwLjk4YzAuMDMsMC4wNywwLjA1LDAuMTUsMC4wOCwwLjIyYzAuMDMsMC4wNywwLjA1LDAuMTUsMC4wOCwwLjIyYzAuMDMsMC4wNywwLjA1LDAuMTQsMC4wOCwwLjIxCgkJCQkJCQljMC4wMywwLjA3LDAuMDUsMC4xNCwwLjA4LDAuMjFjMC4wMSwwLjAzLDAuMDIsMC4wNywwLjA0LDAuMWMwLjAxLDAuMDMsMC4wMiwwLjA3LDAuMDQsMC4xYzAuMDEsMC4wMywwLjAyLDAuMDcsMC4wNCwwLjEKCQkJCQkJCWMwLjAxLDAuMDMsMC4wMiwwLjA3LDAuMDQsMC4xYzAuMDEsMC4wMywwLjAyLDAuMDcsMC4wNCwwLjFjMC4wMSwwLjAzLDAuMDMsMC4wNywwLjA0LDAuMWMwLjAxLDAuMDMsMC4wMywwLjA3LDAuMDQsMC4xCgkJCQkJCQlzMC4wMywwLjA3LDAuMDQsMC4xYzAuMDEsMC4wMiwwLjAyLDAuMDUsMC4wMywwLjA3YzAuMDEsMC4wMiwwLjAyLDAuMDUsMC4wMywwLjA3YzAuMDEsMC4wMiwwLjAyLDAuMDUsMC4wMywwLjA3CgkJCQkJCQljMC4wMSwwLjAyLDAuMDIsMC4wNSwwLjAzLDAuMDdjMCwwLjAxLDAuMDEsMC4wMiwwLjAxLDAuMDNjMCwwLjAxLDAuMDEsMC4wMiwwLjAxLDAuMDNjMCwwLjAxLDAuMDEsMC4wMiwwLjAxLDAuMDMKCQkJCQkJCWMwLDAuMDEsMC4wMSwwLjAyLDAuMDEsMC4wM2MwLjAxLDAuMDMsMC4wMywwLjA3LDAuMDQsMC4xYzAuMDEsMC4wMywwLjAzLDAuMDcsMC4wNCwwLjFjMC4wMSwwLjAzLDAuMDMsMC4wNywwLjA0LDAuMQoJCQkJCQkJYzAuMDEsMC4wMywwLjAzLDAuMDcsMC4wNCwwLjFsLTAuMDEsMC4wM2wwLjAyLTAuMDFjMC4wOCwwLjIsMC4xNiwwLjQsMC4yNCwwLjYxYzAuMDksMC4yMSwwLjE3LDAuNDMsMC4yNiwwLjY1CgkJCQkJCQljMC4wOSwwLjIzLDAuMTksMC40NiwwLjI5LDAuN2MwLjEsMC4yNCwwLjIxLDAuNSwwLjMxLDAuNzZjMC4xOC0wLjMzLDAuMzctMC42NiwwLjU2LTAuOTljMC4xOS0wLjMyLDAuMzgtMC42NSwwLjU4LTAuOTYKCQkJCQkJCXMwLjQtMC42MywwLjYtMC45NGMwLjIxLTAuMzEsMC40MS0wLjYxLDAuNjItMC45MWMwLTAuMDEsMC4wMS0wLjAxLDAuMDEtMC4wMmMwLTAuMDEsMC4wMS0wLjAxLDAuMDEtMC4wMgoJCQkJCQkJYzAuMDEtMC4wMSwwLjAxLTAuMDEsMC4wMS0wLjAyYzAtMC4wMSwwLjAxLTAuMDEsMC4wMS0wLjAyYzAuMDQtMC4wNiwwLjA4LTAuMTIsMC4xMi0wLjE3czAuMDgtMC4xMSwwLjEyLTAuMTcKCQkJCQkJCWMwLjA0LTAuMDYsMC4wOC0wLjExLDAuMTItMC4xN2MwLjA0LTAuMDYsMC4wOC0wLjExLDAuMTItMC4xN2MwLjA0LTAuMDUsMC4wNy0wLjEsMC4xMS0wLjE1YzAuMDQtMC4wNSwwLjA3LTAuMSwwLjExLTAuMTQKCQkJCQkJCWMwLjA0LTAuMDUsMC4wNy0wLjEsMC4xMS0wLjE0YzAuMDQtMC4wNSwwLjA3LTAuMSwwLjExLTAuMTRjMC4wMS0wLjAxLDAuMDEtMC4wMiwwLjAyLTAuMDNjMC4wMS0wLjAxLDAuMDEtMC4wMiwwLjAyLTAuMDMKCQkJCQkJCWMwLjAxLTAuMDEsMC4wMS0wLjAyLDAuMDItMC4wM2MwLjAxLTAuMDEsMC4wMS0wLjAyLDAuMDItMC4wMmMwLjA0LTAuMDYsMC4wOS0wLjExLDAuMTMtMC4xN2MwLjA0LTAuMDYsMC4wOS0wLjExLDAuMTMtMC4xNwoJCQkJCQkJczAuMDktMC4xMSwwLjEzLTAuMTdzMC4wOS0wLjExLDAuMTMtMC4xN2MwLjA1LTAuMDYsMC4wOS0wLjExLDAuMTQtMC4xN2MwLjA1LTAuMDYsMC4wOS0wLjExLDAuMTQtMC4xNwoJCQkJCQkJYzAuMDUtMC4wNiwwLjA5LTAuMTEsMC4xNC0wLjE3czAuMDktMC4xMSwwLjE0LTAuMTdjMC4zMy0wLjM5LDAuNjYtMC43NywxLTEuMTRjMC4zNC0wLjM4LDAuNjgtMC43NCwxLjAzLTEuMQoJCQkJCQkJYzAuMzUtMC4zNiwwLjcxLTAuNzIsMS4wNy0xLjA2YzAuMzYtMC4zNSwwLjczLTAuNjksMS4xMS0xLjAyYzAuMDctMC4wNiwwLjE0LTAuMTMsMC4yMi0wLjE5YzAuMDctMC4wNiwwLjE0LTAuMTMsMC4yMi0wLjE5CgkJCQkJCQljMC4wNy0wLjA2LDAuMTUtMC4xMywwLjIyLTAuMTljMC4wNy0wLjA2LDAuMTUtMC4xMywwLjIyLTAuMTljMC4wMi0wLjAyLDAuMDQtMC4wNCwwLjA3LTAuMDZjMC4wMi0wLjAyLDAuMDQtMC4wNCwwLjA3LTAuMDYKCQkJCQkJCWMwLjAyLTAuMDIsMC4wNC0wLjA0LDAuMDctMC4wNmMwLjAyLTAuMDIsMC4wNC0wLjA0LDAuMDctMC4wNWMwLjA2LTAuMDUsMC4xMi0wLjEsMC4xNy0wLjE0YzAuMDYtMC4wNSwwLjEyLTAuMSwwLjE3LTAuMTQKCQkJCQkJCWMwLjA2LTAuMDUsMC4xMi0wLjA5LDAuMTgtMC4xNHMwLjEyLTAuMDksMC4xOC0wLjE0YzAuMDktMC4wNywwLjE4LTAuMTQsMC4yNy0wLjIxYzAuMDktMC4wNywwLjE4LTAuMTQsMC4yNy0wLjIxCgkJCQkJCQljMC4wOS0wLjA3LDAuMTgtMC4xNCwwLjI3LTAuMjFzMC4xOC0wLjE0LDAuMjgtMC4yMWMwLjEtMC4wOCwwLjIxLTAuMTYsMC4zMi0wLjIzYzAuMTEtMC4wOCwwLjIxLTAuMTUsMC4zMi0wLjIzCgkJCQkJCQljMC4xMS0wLjA4LDAuMjEtMC4xNSwwLjMyLTAuMjNzMC4yMS0wLjE1LDAuMzItMC4yMkMxNi44LDIuNzEsMTcsMi41NywxNy4yLDIuNDRjMC4yLTAuMTMsMC40MS0wLjI3LDAuNjEtMC40CgkJCQkJCQljMC4yMS0wLjEzLDAuNDEtMC4yNiwwLjYyLTAuMzljMC4yMS0wLjEzLDAuNDItMC4yNSwwLjYzLTAuMzdjMC40Ni0wLjI3LDAuOTMtMC41MywxLjM5LTAuNzdjMC40Ny0wLjI1LDAuOTQtMC40OCwxLjQxLTAuNwoJCQkJCQkJYzAuNDgtMC4yMiwwLjk2LTAuNDMsMS40My0wLjYzYzAuNDgtMC4yLDAuOTctMC4zOCwxLjQ1LTAuNTVDMjQuMjktMS4yLDIzLjgyLTEuMDMsMjMuMzQtMC44NXoiLz4KCQkJCQk8L2c+CgkJCQk8L2c+CgkJCTwvZz4KCQk8L2c+Cgk8L2c+CjwvZz4KPC9zdmc+Cg==';
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
		$args['menu_icon']			 = $menu_icon_svg;
		$args['show_in_nav_menus']   = true;
		$args['can_export']          = true;
		$args['has_archive']         = false;
		$args['rewrite']             = false;
		$args['exclude_from_search'] = true;
		$args['query_var']           = false;
		$args['capability_type']     = 'page';
		$args['show_in_rest']        = false;
		$args['rest_base']           = 'sitewide_sale';
		$args['supports']            = array( 'title', );
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
