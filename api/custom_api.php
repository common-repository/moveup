<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MoveUpCustomRestAPI {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Register the routes for fetch order list.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/fetch-order-list',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'fetch_order_list' ],
				'permission_callback' => [ $this, 'permission_check' ],
			],
		);

		register_rest_route(
			$this->namespace,
			'/fetch-order-items',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'fetch_order_items' ],
				'permission_callback' => [ $this, 'permission_check' ],
			],
		);

		register_rest_route(
			$this->namespace,
			'/order/(?P<id>\d+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'fetch_mvi_order_items' ],
				'permission_callback' => [ $this, 'permission_check' ],
			],
		);

		register_rest_route(
			$this->namespace,
			'/product-id-by-sku',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'get_product_id_by_sku' ],
				'permission_callback' => [ $this, 'permission_check' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/order-item/(?P<id>\d+)/detail',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_order_data_by_item_id' ],
				'permission_callback' => [ $this, 'permission_check' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/fulfill-item/(?P<id>\d+)',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'fulfill_order_item' ],
				'permission_callback' => [ $this, 'permission_check' ],
			]
		);
	}

	/**
	 * Get orders
	 *
	 * @param WP_REST_Request $params
	 *
	 * @return array
	 */
	public static function fetch_order_list( WP_REST_Request $params ) {
		$per_page = isset( $params["per_page"] ) ? absint( $params["per_page"]) : 2;
		$page     = isset( $params["page"] ) ? absint( $params["page"] ): 1;
		$store_slug = isset( $params["store_slug"] ) ? sanitize_title($params["store_slug"]) : null;
		$offset   = $per_page * ( $page - 1 );

		$args = [
			"status"   => "wc-processing",
			"type"     => "shop_order",
			"limit"    => $per_page,
			"offset"   => $offset,
			"meta_key" => MOVEUP_WP_ORDER_HAVE_MOVEON_PRODUCT,
		];

		$order_total = wc_get_orders( array_merge( $args, [ "paginate" => true ] ) )->total;
		$orders      = wc_get_orders( $args );
		$statuses    = wc_get_order_statuses();

		$orders_data = [];
		foreach ( $orders as $order ) {
			$items_data                  = [];
			$moveup_product_count = 0;

			foreach ( $order->get_items() as $item ) {
				$source_store_slug           = get_post_meta( $item->get_product_id(), MOVEUP_WP_META_STORE_SLUG, true );
				$product = $item->get_product();
				if ( ! $product ) {
					continue;
				}
				if(!empty($store_slug) && $source_store_slug !== $store_slug){
					continue;
				}

				$meta  = [];
				$metas = $item->get_meta_data();
				foreach ( $metas as $attribute_taxonomy => $term_slug ) {
					$taxonomy       = get_taxonomy( $term_slug->key );
					$attribute_name = $taxonomy ? $taxonomy->labels->singular_name : null;

					if ( $attribute_name ) {
						$meta[] = [
							"key"   => $attribute_name,
							"value" => $term_slug->value
						];
					}
				}
                $is_moveup_product           = get_post_meta( $item->get_product_id(), MOVEUP_WP_META_PRODUCT_SOURCE_MOVEON, true );
				$moveup_product_count       += $is_moveup_product ? 1 : 0;
				$parent_product              = wc_get_product( $item->get_product_id() );
				$source_title                = get_post_meta( $item->get_product_id(), MOVEUP_WP_META_PRODUCT_SOURCE_TITLE, true );
				$source_vid                  = get_post_meta( $item->get_product_id(), MOVEUP_WP_META_PRODUCT_SOURCE_VID, true );
				$product_source_url          = get_post_meta( $item->get_product_id(), MOVEUP_WP_META_PRODUCT_URL, true );
				$source_sku_data             = ( $product->get_type() === "variation" ) ? get_post_meta( $item->get_variation_id(), MOVEUP_WP_META_ORIGINAL_VARIATION_DATA, ) : get_post_meta( $item["product_id"], MOVEUP_WP_META_ORIGINAL_VARIATION_DATA, );

				$is_order_fulfillment        = wc_get_order_item_meta( $item->get_id(), MOVEUP_WP_ORDER_FULFILLMENT_STATUS, true );

				$items_data[] = [
					"order_item_id"      => $item->get_id(),
					"order_edit_link"    => admin_url( "post.php?post=" . absint( $order->data["id"] ) . "&action=edit" ),
					"order_id"           => $order->data["id"],
					"image"              => is_object( $product ) && ! is_null( $product->get_image_id() ) ? wp_get_attachment_image_url( $product->get_image_id(),
						"full" ) : wc_placeholder_img_src(),
					"order_item_name"    => $item->get_name(),
					"parent_name"        => $parent_product->get_name(),
					"quantity"           => $item->get_quantity(),
					"product_id"         => $item->get_product_id(),
					"variation_id"       => $item->get_variation_id(),
					"price"              => $item->get_subtotal(),
					"sku"                => $product->get_sku(),
					"product_type"       => $product->get_type(),
					"source_store_slug"  => $source_store_slug,
					"source_title"       => $source_title,
					"source_vid"         => $source_vid,
					"product_source_url" => $product_source_url,
					"source_sku_data"    => $source_sku_data,
					"total"              => $item->get_total(),
					"type"               => $item->get_type(),
					"meta_data"          => $meta,
					"is_moveon_product"  => $is_moveup_product,
					"order_fulfillment"  => $is_order_fulfillment
				];
			}

			$billing_address  = [
				"billing_first_name"       => $order->get_billing_first_name(),
				"billing_last_name"        => $order->get_billing_last_name(),
				"billing_formated_address" => $order->get_formatted_billing_address(),
				"billing_address_1"        => $order->get_billing_address_1(),
				"billing_address_2"        => $order->get_billing_address_2(),
				"billing_city"             => $order->get_billing_city(),
				"billing_state"            => $order->get_billing_state(),
				"billing_postcode"         => $order->get_billing_postcode(),
				"billing_country"          => $order->get_billing_country(),
				"billing_email"            => $order->get_billing_email(),
				"billing_phone"            => $order->get_billing_phone()
			];
			$shipping_address = [
				"shipping_first_name"       => $order->get_shipping_first_name(),
				"shipping_last_name"        => $order->get_shipping_last_name(),
				"shipping_formated_address" => $order->get_formatted_shipping_address(),
				"shipping_address_1"        => $order->get_shipping_address_1(),
				"shipping_address_2"        => $order->get_shipping_address_2(),
				"shipping_city"             => $order->get_shipping_city(),
				"shipping_state"            => $order->get_shipping_state(),
				"shipping_postcode"         => $order->get_shipping_postcode(),
				"shipping_country"          => $order->get_shipping_country(),
				"shipping_phone"            => $order->get_shipping_phone()
			];

			$currency_code = $order->get_currency();
			$status        = $order->data["status"];
			$status        = "wc-" === substr( $status, 0, 3 ) ? substr( $status, 3 ) : $status;
			$status_label  = isset( $statuses[ "wc-" . $status ] ) ? $statuses[ "wc-" . $status ] : $status;
			$orders_data[] = [

				"order_edit_link"      => admin_url( "post.php?post=" . absint( $order->data["id"] ) . "&action=edit" ),
				"order_status"         => $order->data["status"],
				"status_label"         => $status_label,
				"order_id"             => $order->data["id"],
				"date_created"         => $order->data["date_created"],
				"items"                => $items_data,
				"moveon_product_count" => $moveup_product_count,
				"currency"             => $currency_code,
				"customer_id"          => $order->get_customer_id(),
				"billing_address"      => $billing_address,
				"shipping_address"     => $shipping_address,
				"total"                => $order->get_total()
			];
		}

		$data = [
			"meta"   => [
				"total"        => $order_total,
				"current_page" => $page,
				"per_page"     => $per_page
			],
			"orders" => $orders_data
		];

		return [
			"code"    => 200,
			"data"    => $data,
			"message" => "orders"
		];
	}

	/**
	 * Get orders
	 *
	 * @param WP_REST_Request $params
	 *
	 * @return array
	 */
	public static function fetch_order_items( WP_REST_Request $params ) {
		$per_page     = isset( $params["per_page"] ) ? absint( $params["per_page"]) : 2;
		$page         = isset( $params["page"] ) ? absint( $params["page"] ): 1;
		$store_slug   = isset( $params["store_slug"] ) ? sanitize_title($params["store_slug"]) : null;
		$is_fulfilled = isset( $params["is_fulfilled"] ) ? absint($params["is_fulfilled"]) : null;
		global $wpdb;
		$offset                         = $per_page * ( $page - 1 );
		$table_order_items              = "{$wpdb->prefix}woocommerce_order_items";
		$table_order_items_meta         = "{$wpdb->prefix}woocommerce_order_itemmeta";
		$moveon_order_item_meta_key     = MOVEUP_WP_META_PRODUCT_SOURCE_MOVEON;
		$moveon_order_item_store_slug   = MOVEUP_WP_ORDER_ITEM_STORE_SLUG;
		$moveon_order_item_is_fulfilled = MOVEUP_WP_ORDER_FULFILLMENT_STATUS;

		$result_sql  = "SELECT {$table_order_items}.* FROM {$table_order_items}";
        $count_sql  = "SELECT count(*) FROM {$table_order_items}";

        $sql = " WHERE EXISTS( SELECT * FROM `{$table_order_items_meta}`";
        $sql .= " WHERE `{$table_order_items}`.`order_item_id` = `{$table_order_items_meta}`.`order_item_id`";
        $sql .= " AND `meta_key` = '{$moveon_order_item_meta_key}' AND `meta_value` = '1')";

		if ( !is_null( $store_slug ) ) {
            $sql .= " AND EXISTS( SELECT * FROM `{$table_order_items_meta}`";
            $sql .= " WHERE `{$table_order_items}`.`order_item_id` = `{$table_order_items_meta}`.`order_item_id`";
            $sql .= " AND `meta_key` = '{$moveon_order_item_store_slug}' AND `meta_value` = '{$store_slug}')";
        }

        if ( !is_null( $is_fulfilled ) ) {
            if ($is_fulfilled === 1) {
                $sql .= " AND EXISTS( SELECT * FROM `{$table_order_items_meta}`";
            } else {
                $sql .= " AND NOT EXISTS( SELECT * FROM `{$table_order_items_meta}`";
            }
            $sql .= " WHERE `{$table_order_items}`.`order_item_id` = `{$table_order_items_meta}`.`order_item_id`";
            $sql .= " AND `meta_key` = '{$moveon_order_item_is_fulfilled}' AND `meta_value` = '1')";
        }

        $paging = " LIMIT {$per_page} OFFSET {$offset}";

        $result_sql = $result_sql . $sql . " ORDER BY {$table_order_items}.order_item_id DESC" . $paging;
        $count_sql = $count_sql . $sql;

		$query = call_user_func_array( array(
			$wpdb,
			'prepare'
		), array_merge( array( $result_sql ), [] ) );

		$count               = $wpdb->get_var( $count_sql );
		$order_items_results = $wpdb->get_results( $wpdb->prepare( $query ), ARRAY_A );
		$orders_data         = [];
		foreach ( $order_items_results as $order_item ) {
			$order_data       = wc_get_order( $order_item["order_id"] );
			$item             = $order_data->get_item( $order_item["order_item_id"] );
			$product          = $item->get_product();
			$billing_address  = [
				"billing_first_name"       => $order_data->get_billing_first_name(),
				"billing_last_name"        => $order_data->get_billing_last_name(),
				"billing_formated_address" => $order_data->get_formatted_billing_address(),
				"billing_address_1"        => $order_data->get_billing_address_1(),
				"billing_address_2"        => $order_data->get_billing_address_2(),
				"billing_city"             => $order_data->get_billing_city(),
				"billing_state"            => $order_data->get_billing_state(),
				"billing_postcode"         => $order_data->get_billing_postcode(),
				"billing_country"          => $order_data->get_billing_country(),
				"billing_email"            => $order_data->get_billing_email(),
				"billing_phone"            => $order_data->get_billing_phone()
			];
			$shipping_address = [
				"shipping_first_name"       => $order_data->get_shipping_first_name(),
				"shipping_last_name"        => $order_data->get_shipping_last_name(),
				"shipping_formated_address" => $order_data->get_formatted_shipping_address(),
				"shipping_address_1"        => $order_data->get_shipping_address_1(),
				"shipping_address_2"        => $order_data->get_shipping_address_2(),
				"shipping_city"             => $order_data->get_shipping_city(),
				"shipping_state"            => $order_data->get_shipping_state(),
				"shipping_postcode"         => $order_data->get_shipping_postcode(),
				"shipping_country"          => $order_data->get_shipping_country(),
				"shipping_phone"            => $order_data->get_shipping_phone()
			];
			$meta             = [];
			$metas            = $item->get_meta_data();
			foreach ( $metas as $attribute_taxonomy => $term_slug ) {
				$taxonomy       = get_taxonomy( $term_slug->key );
				$attribute_name = $taxonomy ? $taxonomy->labels->singular_name : null;

				if ( $attribute_name ) {
					$meta[] = [
						"key"   => $attribute_name,
						"value" => $term_slug->value
					];
				}
			}

			$source_store_slug        = get_post_meta( $item["product_id"], MOVEUP_WP_META_STORE_SLUG, true );
			$source_title             = get_post_meta( $item["product_id"], MOVEUP_WP_META_PRODUCT_SOURCE_TITLE, true );
			$source_vid               = get_post_meta( $item["product_id"], MOVEUP_WP_META_PRODUCT_SOURCE_VID, true );
			$product_source_url       = get_post_meta( $item["product_id"], MOVEUP_WP_META_PRODUCT_URL, true );
			$source_sku_data          = ( $product->get_type() === "variation" ) ? get_post_meta( $item->get_variation_id(), MOVEUP_WP_META_ORIGINAL_VARIATION_DATA, ) : get_post_meta( $item["product_id"], MOVEUP_WP_META_ORIGINAL_VARIATION_DATA, );
			$is_moveup_product        = get_post_meta( $item["product_id"], MOVEUP_WP_META_PRODUCT_SOURCE_MOVEON, true );
			$is_order_fulfillment     = wc_get_order_item_meta( $order_item["order_item_id"], MOVEUP_WP_ORDER_FULFILLMENT_STATUS, true );

			$orders_data[] = [
				"order_id"               => $item["order_id"],
				"order_edit_link"        => admin_url( "post.php?post=" . absint( $order_data->get_id() ) . "&action=edit" ),
				"order_item_id"          => $order_item["order_item_id"],
				"order_item_name"        => $item["name"],
				"sku"                    => $product->get_sku(),
				"product_type"           => $product->get_type(),
				"image"                  => is_object( $product ) && ! is_null( $product->get_image_id() ) ? wp_get_attachment_image_url( $product->get_image_id(),
					"full" ) : wc_placeholder_img_src(),
				"variation_id"           => $item->get_variation_id(),
				"product_id"             => $item["product_id"],
				"quantity"               => $item["quantity"],
				"price"                  => $item["subtotal"],
				"source_store_slug"      => $source_store_slug,
				"source_title"           => $source_title,
				"source_vid"             => $source_vid,
				"product_source_url"     => $product_source_url,
				"source_sku_data"        => $source_sku_data,
				"total"                  => $item["total"],
				"product_source"         => $is_moveup_product,
				"variation_props"        => $meta,
				"order_status"           => $order_data->get_status(),
				"order_placement_status" => $order_data->get_status(),
				"currency"               => $order_data->get_currency(),
                "order_created_at"		 => $order_data->order_date,
				"customer_id"            => $order_data->get_customer_id(),
				"billing_address"        => $billing_address,
				"shipping_address"       => $shipping_address,
				"is_fulfilled"           => $is_order_fulfillment
			];
		}

		$data = [
			"meta"        => [
				"total"        => (int) $count,
				"current_page" => $page,
				"per_page"     => $per_page
			],
			"order_items" => $orders_data
		];

		return [
			"code"    => 200,
			"data"    => $data,
			"message" => "orders"
		];
	}

	/**
	 * Get moveon order items
	 */
	public static function fetch_mvi_order_items( WP_REST_Request $params ) {
		$store_slug = isset( $params["store_slug"] ) ? sanitize_title($params["store_slug"]) : null;
		$order_id   = absint( $params["id"]);
		$order      = wc_get_order( $order_id );

		global $wpdb;
		$table_order_items            = "{$wpdb->prefix}woocommerce_order_items";
		$table_order_items_meta       = "{$wpdb->prefix}woocommerce_order_itemmeta";
		$moveon_order_item_meta_key   = MOVEUP_WP_META_PRODUCT_SOURCE_MOVEON;
		$moveon_order_item_store_slug = MOVEUP_WP_ORDER_ITEM_STORE_SLUG;

		$result_sql = "SELECT {$table_order_items}.*";
		$count_sql  = "SELECT count(*) ";
		if ( is_null( $store_slug ) ) {
			$common_sql = "FROM {$table_order_items}
        JOIN {$table_order_items_meta} ON {$table_order_items_meta}.order_item_id = {$table_order_items}.order_item_id 
        WHERE {$table_order_items_meta}.meta_key = '{$moveon_order_item_meta_key}' AND {$table_order_items}.order_id = {$order_id}";
		} else {
			$common_sql = "FROM {$table_order_items}
        JOIN {$table_order_items_meta} ON {$table_order_items_meta}.order_item_id = {$table_order_items}.order_item_id 
        WHERE ({$table_order_items_meta}.meta_key = '{$moveon_order_item_meta_key}' OR {$table_order_items_meta}.meta_key = '{$moveon_order_item_store_slug}') AND {$table_order_items}.order_id = {$order_id} AND {$table_order_items_meta}.meta_value = '{$store_slug}'";
		}

		$query               = call_user_func_array( array(
			$wpdb,
			'prepare'
		), array_merge( array( $result_sql . $common_sql ), [] ) );
		$count               = $wpdb->get_var( $count_sql . $common_sql );
		$order_items_results = $wpdb->get_results( $wpdb->prepare( $query ), ARRAY_A );


		$order_items = [];
		foreach ( $order_items_results as $order_item ) {
			$order_data = wc_get_order( $order_item["order_id"] );
			$item       = $order_data->get_item( $order_item["order_item_id"] );
			$product    = $item->get_product();
			$meta       = [];
			$metas      = $item->get_meta_data();
			foreach ( $metas as $attribute_taxonomy => $term_slug ) {
				$taxonomy       = get_taxonomy( $term_slug->key );
				$attribute_name = $taxonomy ? $taxonomy->labels->singular_name : null;

				if ( $attribute_name ) {
					$meta[] = [
						"key"   => $attribute_name,
						"value" => $term_slug->value
					];
				}
			}

			$source_store_slug        = get_post_meta( $item["product_id"], MOVEUP_WP_META_STORE_SLUG, true );
			$source_title             = get_post_meta( $item["product_id"], MOVEUP_WP_META_PRODUCT_SOURCE_TITLE, true );
			$source_vid               = get_post_meta( $item["product_id"], MOVEUP_WP_META_PRODUCT_SOURCE_VID, true );
			$product_source_url       = get_post_meta( $item["product_id"], MOVEUP_WP_META_PRODUCT_URL, true );
			$source_sku_data          = ( $product->get_type() === "variation" ) ? get_post_meta( $item->get_variation_id(), MOVEUP_WP_META_ORIGINAL_VARIATION_DATA, ) : get_post_meta( $item["product_id"], MOVEUP_WP_META_ORIGINAL_VARIATION_DATA, );
			$is_moveup_product = get_post_meta( $item["product_id"], MOVEUP_WP_META_PRODUCT_SOURCE_MOVEON, true );
			$is_order_fulfillment     = wc_get_order_item_meta( $order_item["order_item_id"], MOVEUP_WP_ORDER_FULFILLMENT_STATUS, true );

			$order_items[] = [
				"order_id"           => $item["order_id"],
				"order_edit_link"    => admin_url( "post.php?post=" . absint( $order->data["id"] ) . "&action=edit" ),
				"order_item_id"      => $order_item["order_item_id"],
				"order_item_name"    => $item["name"],
				"source_title"       => $source_title,
				"source_vid"         => $source_vid,
				"product_source_url" => $product_source_url,
				"source_sku_data"    => $source_sku_data,
				"order_fulfillment"  => $is_order_fulfillment,
				"sku"                => $product->get_sku(),
				"product_type"       => $product->get_type(),
				"image"              => is_object( $product ) && ! is_null( $product->get_image_id() ) ? wp_get_attachment_image_url( $product->get_image_id(),
					"full" ) : wc_placeholder_img_src(),
				"variation_id"       => $item->get_variation_id(),
				"product_id"         => $item["product_id"],
				"quantity"           => $item["quantity"],
				"source_store_slug"  => $source_store_slug,
				"Price"              => $item["subtotal"],
				"total"              => $item["total"],
				"product_source"     => (int) $is_moveup_product,
				"variation_props"    => $meta,
			];
		}
		$shipping_address = [
			"shipping_first_name"       => $order->get_shipping_first_name(),
			"shipping_last_name"        => $order->get_shipping_last_name(),
			"shipping_formated_address" => $order->get_formatted_shipping_address(),
			"shipping_address_1"        => $order->get_shipping_address_1(),
			"shipping_address_2"        => $order->get_shipping_address_2(),
			"shipping_city"             => $order->get_shipping_city(),
			"shipping_state"            => $order->get_shipping_state(),
			"shipping_postcode"         => $order->get_shipping_postcode(),
			"shipping_country"          => $order->get_shipping_country(),
			"shipping_phone"            => $order->get_shipping_phone()
		];

		$billing_address = [
			"billing_first_name"       => $order_data->get_billing_first_name(),
			"billing_last_name"        => $order_data->get_billing_last_name(),
			"billing_formated_address" => $order_data->get_formatted_billing_address(),
			"billing_address_1"        => $order_data->get_billing_address_1(),
			"billing_address_2"        => $order_data->get_billing_address_2(),
			"billing_city"             => $order_data->get_billing_city(),
			"billing_state"            => $order_data->get_billing_state(),
			"billing_postcode"         => $order_data->get_billing_postcode(),
			"billing_country"          => $order_data->get_billing_country(),
			"billing_email"            => $order_data->get_billing_email(),
			"billing_phone"            => $order_data->get_billing_phone()
		];


		$data = [
			"meta"             => [
				"total"      => (int) $count,
				"store_slug" => $store_slug
			],
			"order_items"      => $order_items,
			"Shipping_address" => $shipping_address,
			"billing_address"  => $billing_address
		];

		return [
			"code"    => 200,
			"data"    => $data,
			"message" => "orders"
		];
	}

	/**
	 * Get order item id by order data
	 */
	public static function get_order_data_by_item_id( WP_REST_Request $params ) {
		$line_item_id     = absint($params["id"]);
		$order_id         = ( new WC_Order_Item_Data_Store )->get_order_id_by_order_item_id( $line_item_id );
		$order_data       = wc_get_order( $order_id );
		$item             = $order_data->get_item( $line_item_id );
		$product          = $item->get_product();
		$billing_address  = [
			"billing_first_name"       => $order_data->get_billing_first_name(),
			"billing_last_name"        => $order_data->get_billing_last_name(),
			"billing_formated_address" => $order_data->get_formatted_billing_address(),
			"billing_address_1"        => $order_data->get_billing_address_1(),
			"billing_address_2"        => $order_data->get_billing_address_2(),
			"billing_city"             => $order_data->get_billing_city(),
			"billing_state"            => $order_data->get_billing_state(),
			"billing_postcode"         => $order_data->get_billing_postcode(),
			"billing_country"          => $order_data->get_billing_country(),
			"billing_email"            => $order_data->get_billing_email(),
			"billing_phone"            => $order_data->get_billing_phone()
		];
		$shipping_address = [
			"shipping_first_name"       => $order_data->get_shipping_first_name(),
			"shipping_last_name"        => $order_data->get_shipping_last_name(),
			"shipping_formated_address" => $order_data->get_formatted_shipping_address(),
			"shipping_address_1"        => $order_data->get_shipping_address_1(),
			"shipping_address_2"        => $order_data->get_shipping_address_2(),
			"shipping_city"             => $order_data->get_shipping_city(),
			"shipping_state"            => $order_data->get_shipping_state(),
			"shipping_postcode"         => $order_data->get_shipping_postcode(),
			"shipping_country"          => $order_data->get_shipping_country(),
			"shipping_phone"            => $order_data->get_shipping_phone()
		];
		$meta             = [];
		$metas            = $item->get_meta_data();
		foreach ( $metas as $attribute_taxonomy => $term_slug ) {
			$taxonomy       = get_taxonomy( $term_slug->key );
			$attribute_name = $taxonomy ? $taxonomy->labels->singular_name : null;

			if ( $attribute_name ) {
				$meta[] = [
					"key"   => $attribute_name,
					"value" => $term_slug->value
				];
			}
		}

		$source_store_slug        = get_post_meta( $item["product_id"], MOVEUP_WP_META_STORE_SLUG, true );
		$source_title             = get_post_meta( $item["product_id"], MOVEUP_WP_META_PRODUCT_SOURCE_TITLE, true );
		$source_vid               = get_post_meta( $item["product_id"], MOVEUP_WP_META_PRODUCT_SOURCE_VID, true );
		$product_source_url       = get_post_meta( $item["product_id"], MOVEUP_WP_META_PRODUCT_URL, true );
		$source_sku_data          = ( $product->get_type() === "variation" ) ? get_post_meta( $item->get_variation_id(), MOVEUP_WP_META_ORIGINAL_VARIATION_DATA, ) : get_post_meta( $item["product_id"], MOVEUP_WP_META_ORIGINAL_VARIATION_DATA, );
		$is_moveup_product = get_post_meta( $item["product_id"], MOVEUP_WP_META_PRODUCT_SOURCE_MOVEON, true );

		$items_data[] = [
			"order_item_id"      => $line_item_id,
			"order_edit_link"    => admin_url( "post.php?post=" . absint( $order_data->get_id() ) . "&action=edit" ),
			"image"              => is_object( $product ) && ! is_null( $product->get_image_id() ) ? wp_get_attachment_image_url( $product->get_image_id(),
				"full" ) : wc_placeholder_img_src(),
			"order_item_name"    => $item["name"],
			"quantity"           => $item["quantity"],
			"product_id"         => $item["product_id"],
			"price"              => $item["subtotal"],
			"sku"                => $product->get_sku(),
			"product_type"       => $product->get_type(),
			"source_store_slug"  => $source_store_slug,
			"source_title"       => $source_title,
			"source_vid"         => $source_vid,
			"product_source_url" => $product_source_url,
			"source_sku_data"    => $source_sku_data,
			"total"              => $item["total"],
			"type"               => $item->get_type(),
			"meta_data"          => $meta,
			"is_moveon_product"  => $is_moveup_product
		];

		$orders_data = [
			"order_id"               => $item["order_id"],
			"order_edit_link"        => admin_url( "post.php?post=" . absint( $order_data->get_id() ) . "&action=edit" ),
			"sku"                    => $product->get_sku(),
			"product_type"           => $product->get_type(),
			"image"                  => is_object( $product ) && ! is_null( $product->get_image_id() ) ? wp_get_attachment_image_url( $product->get_image_id(),
				"full" ) : wc_placeholder_img_src(),
			"source_store_slug"      => $source_store_slug,
			"source_title"           => $source_title,
			"source_vid"             => $source_vid,
			"product_source_url"     => $product_source_url,
			"source_sku_data"        => $source_sku_data,
			"product_source"         => $is_moveup_product,
			"items"                  => $items_data,
			"order_status"           => $order_data->get_status(),
			"order_placement_status" => $order_data->get_status(),
			"currency"               => $order_data->get_currency(),
			"customer_id"            => $order_data->get_customer_id(),
			"billing_address"        => $billing_address,
			"shipping_address"       => $shipping_address
		];

		$data = [
			"order" => $orders_data
		];

		return [
			"code"    => 200,
			"data"    => $data,
			"message" => "orders"
		];
	}


	/**
	 * fulfillment order item
	 */
	public static function fulfill_order_item( WP_REST_Request $params ) {
		$item_id = absint( $params["id"]);
		wc_update_order_item_meta( $item_id, MOVEUP_WP_ORDER_FULFILLMENT_STATUS, true );

		return [
			"code"    => 200,
			"message" => "update item fulfillment"
		];
	}

	/**
	 * Get product id by sku
	 *
	 * @param WP_REST_Request $skus
	 *
	 * @return array|void
	 */
	public static function get_product_id_by_sku( WP_REST_Request $skus ) {
		$request_data = json_decode( $skus->get_body() );
		$request_data = json_decode( json_encode( $request_data ), true );
		if ( is_null( $request_data ) || empty( $request_data ) ) {
			return exit();
		} else {
			return self::get_product_ids( $request_data["skus"] );
		}

	}

	/**
	 * Get product ids
	 *
	 * @param $skus
	 *
	 * @return array
	 */
	public static function get_product_ids( $skus ) {
		global $wpdb;
		$sql         = "SELECT product_id,sku FROM `" . $wpdb->prefix . "wc_product_meta_lookup` WHERE `sku` IN(" . implode( ', ', array_fill( 0, count( $skus ), '%s' ) ) . ")";
		$query       = call_user_func_array( [ $wpdb, 'prepare' ], array_merge( [ $sql ], $skus ) );
		$product_ids = $wpdb->get_results( $wpdb->prepare( $query ), ARRAY_A );

		foreach ( $skus as $sku ) {
			$product_id = null;
			foreach ( $product_ids as $id ) {
				if ( $id["sku"] === $sku ) {
					$product_id = $id["product_id"];
				}
			}
			$result[] = [
				"sku"        => $sku,
				"product_id" => $product_id,
			];
		}

		return $result;
	}

	/**
	 * Woocommerce oAuth permission check
	 *
	 * @param $request
	 *
	 * @return bool|WP_Error
	 */
	public function permission_check( $request ) {
		$customer = null;
		if ( ! wc_rest_check_user_permissions( 'edit', $customer ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce' ), [ 'status' => rest_authorization_required_code() ] );
		}

		return true;
	}
}
