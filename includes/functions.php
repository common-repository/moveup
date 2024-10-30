<?php
if ( file_exists( dirname( __FILE__ ) . '/class.plugin-modules.php' ) ) {
	include_once( dirname( __FILE__ ) . '/class.plugin-modules.php' );
}

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Handle oauth create and redirection
 *
 */

if ( ! function_exists( 'moveup_wp_handle_process' ) ) {
	function moveup_wp_handle_process() {
		$site_url          = get_site_url();
		$app_redirect_host = MOVEUP_APP_BASE_URL . "/website-manage/website";
        $hash              = generate_random_string(50);
		$api_cred_store    = MOVEUP_API_BASE_URL . "/api/v1/website/woo/store-credential/$hash";
		$return_url        = "{$app_redirect_host}?action=connect&&shop={$site_url}&&shop_slug=woocommerce&&hash={$hash}";
        $params            = [
            'app_name'     => 'MoveUp',
            'scope'        => 'read_write',
            'user_id'      => 1,
            'return_url'   => $return_url,
            'callback_url' => $api_cred_store
        ];
        $query_string = http_build_query( $params );

        $url = $site_url . '/wc-auth/v1/authorize' . '?' . $query_string;

		if ( wp_redirect( $url ) ) {
			exit;
		}
	}
}


if ( ! function_exists( 'woocommerce_version_check' ) ) {
	function woocommerce_version_check( $version = '3.0' ) {
		global $woocommerce;

		if ( version_compare( $woocommerce->version, $version, ">=" ) ) {
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'generate_random_string' ) ) {
    function generate_random_string($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
