<?php
/**
 * This file will create admin menu page.
 */

class MoveUpWPAdmin {

	public function __construct() {}

	public function create_admin_menu() {
		$capability = 'manage_options';
		$slug       = 'moveup-wp';

		add_menu_page( __( 'MoveUp', 'moveup-wp' ), __( 'MoveUp', 'moveup-wp' ), $capability, $slug,
			[ $this, 'page_callback' ], MOVEUP_WP_IMAGES . "icon.png", 3 );
	}

	/**
	 * Keep track of pending order whoose have moveon products
	 *
	 * @param $order_id
	 *
	 * @throws Exception
	 * @since    0.0.1
	 */
	public function moveup_wp_new_order_place_add_meta( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( $order ) {
			$moveup_product_count = 0;
			foreach ( $order->get_items() as $item_id => $item ) {
				$product_id        = $item->get_product_id();
				$is_moveup_product = get_post_meta( $product_id, MOVEUP_WP_META_PRODUCT_SOURCE_MOVEON, true );
				$store_slug        = get_post_meta( $product_id, MOVEUP_WP_META_STORE_SLUG, true );

				if ( $store_slug ) {
					wc_update_order_item_meta( $item->get_id(), MOVEUP_WP_ORDER_ITEM_STORE_SLUG, $store_slug );
				}

				if ( $is_moveup_product ) {
					$moveup_product_count ++;
					wc_update_order_item_meta( $item->get_id(), MOVEUP_WP_META_PRODUCT_SOURCE_MOVEON, 1 );
				}
			}

			if ( $moveup_product_count > 0 ) {
				update_post_meta( $order_id, MOVEUP_WP_ORDER_HAVE_MOVEON_PRODUCT, $moveup_product_count );
			}

		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 * @since    0.0.1
	 */
	public function admin_enqueue_scripts() {
		$page = isset( $_REQUEST['page'] ) ? sanitize_title($_REQUEST['page']) : '';
		global $pagenow;
		if ( $pagenow === 'admin.php' && $page === 'moveup-wp' ) {
			wp_enqueue_script( 'jquery-ui-sortable' );
			self::enqueue_semantic();
		}
	}

	public static function enqueue_semantic() {
		wp_dequeue_style( 'eopa-admin-css' );
		/*Stylesheet*/
		wp_enqueue_style( 'moveup-wp-stylesheet', MOVEUP_WP_CSS . 'style.css' );
	}

	public function admin_notices() {
		$errors              = [];
		$permalink_structure = get_option( 'permalink_structure' );
		if ( ! $permalink_structure ) {
			$errors[] = __( 'You are using Permalink structure as Plain. Please go to <a href="' . admin_url( 'options-permalink.php' ) . '" target="_blank">Permalink Settings</a> to change it.', 'moveup-wp' );
		}
		if ( ! is_ssl() ) {
			$errors[] = __( 'Your site is not using HTTPS. For more details, please read <a target="_blank" href="https://make.wordpress.org/support/user-manual/web-publishing/https-for-wordpress/">HTTPS for WordPress</a>', 'moveup-wp' );
		}
		if ( count( $errors ) ) {
			?>
            <div class="error">
                <h3><?php echo _n( 'MoveUp WP: you can not import products or fulfil orders unless below issue is resolved', 'MoveUp WP: you can not import products or fulfil orders unless below issues are resolved', count( $errors ), 'moveup-wp' ); ?></h3>
				<?php
				foreach ( $errors as $error ) {
					?>
                    <p><?php echo esc_html($error) ; ?></p>
					<?php
				}
				?>
            </div>
			<?php
		}
	}

	public function connect_to_moveup() {
		if ( isset( $_POST['moveup_wp_connect_redirect'] ) && isset( $_POST['_moveup_wp_nonce'] )
		     && wp_verify_nonce( $_POST['_moveup_wp_nonce'], 'moveup_wp_connect_redirect' ) ) {
			moveup_wp_handle_process();
		}
	}

	/**
	 *
	 */
	public function page_callback() {
        $app_name = "MoveUp";
        $scope    = "Read/Write";
        $moveup_url    = "https://moveup.click";
        ?>
        <div class="moveup-page">
            <div class="moveup-logo">
                <a href="<?php echo esc_url( $moveup_url ); ?>" target="_blank"> <img
                            style="max-width:none; width:345px; border:0; text-decoration:none; outline:none"
                            src="<?php echo esc_url( MOVEUP_WP_IMAGES ); ?>logo.png"/></a>
            </div>
            <div class="moveup-login-url" style="margin-top:-40px; line-height: 30px;">
                <h3><?php esc_html_e( 'You are All Set!', 'moveup-wp' ); ?></h3>
	            <?php esc_html_e( 'MoveUp WordPress Plugin is installed now complete sample 3 steps', 'moveup-wp' ); ?> <br>
				<div class="container">
					<div class="item">
						<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="#222" stroke="#222"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <title></title> <g id="Complete"> <g id="arrow-right"> <g> <polyline data-name="Right" fill="none" id="Right-2" points="16.4 7 21.5 12 16.4 17" stroke="#020202" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></polyline> <line fill="none" stroke="#020202" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" x1="2.5" x2="19.2" y1="12" y2="12"></line> </g> </g> </g> </g></svg>						<div class="filename">
							<p><?php esc_html_e('Click to register button to your account within seconds', 'moveup-wp'); ?></p>
						</div>
						<a href="<?php echo esc_url('https://app.moveup.click'); ?>" target="_blank" rel="noopener noreferrer">
							<button><?php esc_html_e('Register', 'moveup-wp'); ?></button>
						</a>
					</div>

					<div class="item">
						<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="#222" stroke="#222"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <title></title> <g id="Complete"> <g id="arrow-right"> <g> <polyline data-name="Right" fill="none" id="Right-2" points="16.4 7 21.5 12 16.4 17" stroke="#020202" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></polyline> <line fill="none" stroke="#020202" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" x1="2.5" x2="19.2" y1="12" y2="12"></line> </g> </g> </g> </g></svg>						<div class="filename">
							<p><?php esc_html_e('Install the Chrome Extension!!', 'moveup-wp'); ?></p>
						</div>
						<a href="<?php echo esc_url('https://chrome.google.com/webstore/detail/moveup/fojlhkeimnollhmjjjfaifebkpdgnbdj'); ?>" target="_blank" rel="noopener noreferrer">
							<button><?php esc_html_e('Install', 'moveup-wp'); ?></button>
						</a>
					</div>

					<div class="item">
						<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="#222" stroke="#222"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <title></title> <g id="Complete"> <g id="arrow-right"> <g> <polyline data-name="Right" fill="none" id="Right-2" points="16.4 7 21.5 12 16.4 17" stroke="#020202" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></polyline> <line fill="none" stroke="#020202" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" x1="2.5" x2="19.2" y1="12" y2="12"></line> </g> </g> </g> </g></svg>						<div class="filename">
							<p><?php esc_html_e( 'Click on the button below to connect to MoveUp', 'moveup-wp' ); ?></p>
						</div>
					</div>
				</div>
                <div class="wrap moveup-wp">
                    <form method="post" action="">
                        <?php wp_nonce_field( 'moveup_wp_connect_redirect', '_moveup_wp_nonce' ); ?>
                        <button type="submit"
                                class="cd-popup-trigger moveup-btn-login-me"
                                style="text-transform: none; font-size: 25px;"
                                name="moveup_wp_connect_redirect"
                                formtarget="_blank"
                        >
                            <?php esc_html_e( 'Connect', 'moveup-wp' ) ?>
                        </button>
                    </form>

                </div>
            </div>
        </div>
        <?php
    }

	/**
	 * fetch order list
	 */
	public function register_custom_api_routes( $controllers ) {
		$controllers['wc/v3']['custom'] = 'MoveUpCustomRestAPI';

		return $controllers;
	}

	/**
	 * Add support for webp
	 *
	 * @param $existing_mimes
	 *
	 * @return mixed
	 */
	function webp_upload_mimes( $existing_mimes ) {
		$existing_mimes['webp'] = 'image/webp';
		return $existing_mimes;
	}
}