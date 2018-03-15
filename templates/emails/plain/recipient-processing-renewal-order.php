<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

echo $email_heading . "\n\n";

echo __( 'Your subscription renewal order has been received and is now being processed. Your order details are shown below for your reference:', 'woocommerce-subscriptions-gifting' );

echo "\n\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";

if ( is_callable( array( 'WC_Emails', 'order_downloads' ) ) ) {
	echo wp_kses_post( WC_Emails::instance()->order_downloads( $order, $sent_to_admin, $plain_text, $email ) );
}

$subscriptions = wcs_get_subscriptions_for_renewal_order( $order );

foreach ( $subscriptions as $subscription ) {
	echo sprintf( __( 'Subscription #%s', 'woocommerce-subscriptions-gifting' ), esc_attr( $subscription->get_order_number() ) ) . "\n";
	echo "\n" . WC_Subscriptions_Email::email_order_items_table( $subscription, array(
		'show_download_links' => true,
		'show_sku'            => false,
		'show_purchase_note'  => true,
		'show_image'          => '',
		'image_size'          => '',
		'plain_text'          => true,
	) );
}

echo "\n";

foreach ( $subscriptions as $subscription ) {

	echo sprintf( __( 'Subscription #%s', 'woocommerce-subscriptions-gifting' ), $subscription->get_order_number() ) . "\n";

	echo sprintf( __( 'Start Date: %s', 'woocommerce-subscriptions-gifting' ), date_i18n( wc_date_format(), $subscription->get_time( 'date_created', 'site' ) ) ) . "\n";

	echo sprintf( __( 'End Date: %s', 'woocommerce-subscriptions-gifting' ), ( 0 < $subscription->get_time( 'end' ) ) ? date_i18n( wc_date_format(), $subscription->get_time( 'end', 'site' ) ) : _x( 'When Cancelled', 'Used as end date for an indefinite subscription', 'woocommerce-subscriptions-gifting' ) ) . "\n";

	$subscription_details = array(
		'recurring_amount'            => '',
		'subscription_period'         => $subscription->get_billing_period(),
		'subscription_interval'       => $subscription->get_billing_interval(),
		'initial_amount'              => '',
		'use_per_slash'               => false,
	);
	$subscription_details = apply_filters( 'woocommerce_subscription_price_string_details', $subscription_details, $subscription );
	echo sprintf( __( 'Period: %s', 'woocommerce-subscriptions-gifting' ), wp_kses_post( wcs_price_string( $subscription_details ) ) ) . "\n";

	echo "----------\n\n";
}

do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );
