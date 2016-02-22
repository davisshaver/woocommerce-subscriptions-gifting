<?php

class WCSG_Download_Handler {

	/**
	* Setup hooks & filters, when the class is initialised.
	*/
	public static function init() {
		add_filter( 'woocommerce_subscription_settings', __CLASS__ . '::register_download_settings' );
		add_filter( 'woocommerce_downloadable_file_permission_data', __CLASS__ . '::grant_recipient_download_permissions', 11 );
		add_filter( 'woocommerce_get_item_downloads', __CLASS__ . '::get_item_download_links', 15, 3 );
	}

	/**
	 * Gets the correct user's download links for a downloadable order item.
	 * If the request is from within an email, the links belonging to the email recipient are returned otherwise
	 * if the request is from the view subscription page use the current user id,
	 * otherwise the links for order's customer user are returned.
	 *
	 * @param array $files Downloadable files for the order item
	 * @param array $item Order line item.
	 * @param object $order
	 * @return array $files
	 */
	public static function get_item_download_links( $files, $item, $order ) {

		if ( ! empty( $order->recipient_user ) ) {
			$subscription_recipient = get_user_by( 'id', $order->recipient_user );
			$user_id                = ( wcs_is_subscription( $order ) && wcs_is_view_subscription_page() ) ? get_current_user_id() : $order->customer_user;
			$mailer                 = WC()->mailer();

			foreach ( $mailer->emails as $email ) {
				if ( isset( $email->wcsg_sending_recipient_email ) ) {
					$user_id = $order->recipient_user;
					break;
				}
			}

			$files = self::get_user_downloads_for_order_item( $order, $user_id, $item );
		}
		return $files;
	}

	/**
	 * Grants download permissions to the recipient rather than the purchaser by default. However if the
	 * purchaser can download setting is selected, permissions are granted to both recipient and purchaser.
	 *
	 * @param array $data download permission data inserted into the wp_woocommerce_downloadable_product_permissions table.
	 * @return array $data
	 */
	public static function grant_recipient_download_permissions( $data ) {

		$subscription = wcs_get_subscription( $data['order_id'] );

		if ( wcs_is_subscription( $subscription ) && isset( $subscription->recipient_user ) ) {

			$can_purchaser_download = ( 'yes' == get_option( 'woocommerce_subscriptions_gifting_downloadable_products', 'no' ) ) ? true : false;

			if ( $can_purchaser_download ) {
				remove_filter( 'woocommerce_downloadable_file_permission_data', __CLASS__ . '::grant_recipient_download_permissions', 11 );

				wc_downloadable_file_permission( $data['download_id'], $data['product_id'] , $subscription );

				add_filter( 'woocommerce_downloadable_file_permission_data', __CLASS__ . '::grant_recipient_download_permissions', 11 );
			}

			$recipient_id       = $subscription->recipient_user;
			$recipient          = get_user_by( 'id', $recipient_id );
			$data['user_id']    = $recipient_id;
			$data['user_email'] = $recipient->user_email;
		}
		return $data;
	}

	/**
	 * Adds additional gifting specific settings into Subscriptions settings
	 *
	 * @param array $settings Subscription's current set of settings.
	 * @return array $settings new settings with appended wcsg specific settings.
	 */
	public static function register_download_settings( $settings ) {
		$download_settings = array(
		array(
			'name'     => __( 'Gifting Subscriptions', 'woocommerce-subscriptions-gifting' ),
			'type'     => 'title',
			'id'       => 'woocommerce_subscriptions_gifting',
		),
		array(
			'name'     => __( 'Downloadable Products', 'woocommerce-subscriptions-gifting' ),
			'desc'     => __( 'Allow both purchaser and recipient to download subscription products.', 'woocommerce-subscriptions-gifting' ),
			'id'       => 'woocommerce_subscriptions_gifting_downloadable_products',
			'default'  => 'no',
			'type'     => 'checkbox',
			'desc_tip' => __( 'If you want both the recipient and purchaser of a subscription to have access to downloadable products.', 'woocommerce-subscriptions-gifting' ),
		),
		array( 'type' => 'sectionend', 'id' => 'woocommerce_subscriptions_gifting' ),
		);

		return array_merge( $settings, $download_settings );
	}

	/**
	 * Retrieves a user's download permissions for an order.
	 *
	 * @param  WC_Order $order
	 * @param  int $user_id
	 * @param  array $item
	 *
	 * @return array
	 */
	public static function get_user_downloads_for_order_item( $order, $user_id, $item ) {
		global $wpdb;

		$product_id = wcs_get_canonical_product_id( $item );

		$downloads = $wpdb->get_results( $wpdb->prepare("
			SELECT *
			FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions
			WHERE user_id = %s
			AND order_id = %s
			AND product_id = %s
		", $user_id, $order->id, $product_id ) );

		$files   = array();
		$product = wc_get_product( $product_id );

		foreach ( $downloads as $download ) {

			if ( $product->has_file( $download->download_id ) ) {
				$files[ $download->download_id ]                 = $product->get_file( $download->download_id );
				$files[ $download->download_id ]['download_url'] = add_query_arg(
					array(
						'download_file' => $product_id,
						'order'         => $download->order_key,
						'email'         => $download->user_email,
						'key'           => $download->download_id,
					),
					home_url( '/' )
				);
			}
		}
		return $files;
	}

	/**
	 * Retrieves all the user's download permissions for an order by checking
	 * for downloads stored on the subscriptions in the order.
	 *
	 * @param  WC_Order $order
	 * @param  int $user_id
	 *
	 * @return array
	 */
	public static function get_user_downloads_for_order( $order, $user_id ) {

		$subscriptions   = wcs_get_subscriptions_for_order( $order, array( 'order_type' => array( 'any' ) ) );
		$order_downloads = array();

		foreach ( $subscriptions as $subscription ) {
			foreach ( $subscription->get_items() as $subscription_item ) {
				$order_downloads = array_merge( $order_downloads, self::get_user_downloads_for_order_item( $subscription, $user_id, $subscription_item ) );
			}
		}

		return $order_downloads;
	}
}
WCSG_Download_Handler::init();
