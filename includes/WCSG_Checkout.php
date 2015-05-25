<?php
	class WCSG_Checkout {

		//setup hooks
		public static function init() {
			add_filter( 'woocommerce_checkout_cart_item_quantity', __CLASS__ . '::add_gifting_option_checkout', 1, 2 );

			add_action( 'woocommerce_checkout_subscription_created', __CLASS__ . '::subscription_created', 1 , 3);

			add_filter( 'woocommerce_subscriptions_recurring_cart_key', __CLASS__ . '::add_recipient_email_recurring_cart_key', 1, 2 );

		}

		public static function add_gifting_option_checkout( $quantity, $cart_item ) {
			echo $quantity;
			if( is_checkout() ){
				error_log("Is the checkout page");

			}else {
				error_log("this is not the checkout page");
			}
			if( WC_Subscriptions_Product::is_subscription( $cart_item['data'] ) ) {
				if( !isset( $cart_item['wcsg_gift_recipients_email'] ) ) {
					echo WCS_Gifting::generate_gifting_html( $cart_item['product_id'], '');
				}else{
					echo WCS_Gifting::generate_gifting_html( 0, $cart_item['wcsg_gift_recipients_email'] );
				}
			}
		}

		public static function subscription_created( $subscription, $order, $recurring_cart ) {
			if ( isset( reset( $recurring_cart->cart_contents )['wcsg_gift_recipients_email'] ) ){
				$recipient_email = reset( $recurring_cart->cart_contents )['wcsg_gift_recipients_email'];

				$recipient_user_id = email_exists( $recipient_email );

				if ( empty( $recipient_user_id ) ) {
					//create a username for the new customer
					$username = explode( '@', $recipient_email );
					$username = sanitize_user( $username[0] );
					$counter = 1;
					$_username = $username;
					while ( username_exists( $username ) ) {
						$username = $_username . $counter;
						$counter++;
					}
					//create a password
					$password = wp_generate_password();

		    		$recipient_user_id = wc_create_new_customer( $recipient_email, $username, $password );
				}
				update_post_meta( $subscription->id, '_recipient_user', $recipient_user_id );

			}
		}

		public static function add_recipient_email_recurring_cart_key( $cart_key, $cart_item ) {
			if ( isset( $cart_item['wcsg_gift_recipients_email'] ) ) {
				$cart_key .= '_' . $cart_item['wcsg_gift_recipients_email'];
			}
			return $cart_key;

		}

	}
	WCSG_Checkout::init();
