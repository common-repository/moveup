<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( is_file( MOVEUP_WP_INCLUDES . "/constant.php" ) ) {
	require_once MOVEUP_WP_INCLUDES . "/constant.php";
}

if ( is_file( MOVEUP_WP_API . "/custom_api.php" ) ) {
	require_once MOVEUP_WP_API . "/custom_api.php";
}

if ( is_file( MOVEUP_WP_INCLUDES . "/moveup_wp_admin.php" ) ) {
	require_once MOVEUP_WP_INCLUDES . "/moveup_wp_admin.php";
}

if ( is_file( MOVEUP_WP_INCLUDES . "/moveup_wp_loader.php" ) ) {
	require_once MOVEUP_WP_INCLUDES . "/moveup_wp_loader.php";
}

if ( is_file( MOVEUP_WP_INCLUDES . "/moveup_wp_core.php" ) ) {
	require_once MOVEUP_WP_INCLUDES . "/moveup_wp_core.php";
}

if ( is_file( MOVEUP_WP_INCLUDES . "/functions.php" ) ) {
	require_once MOVEUP_WP_INCLUDES . "/functions.php";
}

if ( is_file( MOVEUP_WP_INCLUDES . "/moveup_wp_logger.php" ) ) {
	require_once MOVEUP_WP_INCLUDES . "/moveup_wp_logger.php";
}