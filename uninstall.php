<?php

if ( ! function_exists( 'add_action' ) ) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}


if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();	
}


delete_option( 'no_comments_plugin_version' );

