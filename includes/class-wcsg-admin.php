<?php
class WCSG_Admin {

	/**
	 * Setup hooks & filters, when the class is initialised.
	 */
	public static function init() {

		add_filter( 'woocommerce_subscription_list_table_column_content', __CLASS__ . '::display_recipient_name_in_subscription_title', 1, 3 );

		add_filter( 'woocommerce_order_items_meta_get_formatted', __CLASS__ . '::remove_recipient_order_item_meta', 1, 1 );
	}

	/**
	 * Formats the subscription title in the admin subscriptions table to include the recipient's name.
	 *
	 * @param string $column_content The column content HTML elements
	 * @param WC_Subscription $subscription
	 * @param string $column The column name being rendered
	 */
	public static function display_recipient_name_in_subscription_title( $column_content, $subscription, $column ) {

		if ( 'order_title' == $column && WCS_Gifting::is_gifted_subscription( $subscription ) ) {

			$recipient_id   = $subscription->recipient_user;
			$recipient_user = get_userdata( $recipient_id );
			$recipient_name = '<a href="' . esc_url( get_edit_user_link( $recipient_id ) ) . '">';

			if ( ! empty( $recipient_user->first_name ) || ! empty( $recipient_user->last_name ) ) {
				$recipient_name .= ucfirst( $recipient_user->first_name ) . ( ( ! empty( $recipient_user->last_name ) ) ? ' ' . ucfirst( $recipient_user->last_name ) : '' );
			} else {
				$recipient_name .= ucfirst( $recipient_user->display_name );
			}
			$recipient_name .= '</a>';

			$purchaser_id   = $subscription->get_user_id();
			$purchaser_user = get_userdata( $purchaser_id );
			$purchaser_name = '<a href="' . esc_url( get_edit_user_link( $purchaser_id ) ) . '">';

			if ( ! empty( $purchaser_user->first_name ) || ! empty( $purchaser_user->last_name ) ) {
				$purchaser_name .= ucfirst( $purchaser_user->first_name ) . ( ( ! empty( $purchaser_user->last_name ) ) ? ' ' . ucfirst( $purchaser_user->last_name ) : '' );
			} else {
				$purchaser_name .= ucfirst( $purchaser_user->display_name );
			}
			$purchaser_name .= '</a>';

			// translators: $1: is subscription order number,$2: is recipient user's name, $3: is the purchaser user's name
			$column_content = sprintf( _x( '%1$s for %2$s purchased by %3$s', 'Subscription title on admin table. (e.g.: #211 for John Doe Purchased by: Jane Doe)', 'woocommerce-subscriptions-gifting' ), '<a href="' . esc_url( get_edit_post_link( $subscription->id ) ) . '">#<strong>' . esc_attr( $subscription->get_order_number() ) . '</strong></a>', $recipient_name, $purchaser_name );

			$column_content .= '</div>';
		}

		return $column_content;
	}

	/**
	 * Removes the recipient order item meta from the admin subscriptions table.
	 *
	 * @param array $formatted_meta formatted order item meta key, label and value
	 */
	public static function remove_recipient_order_item_meta( $formatted_meta ) {

		if ( is_admin() ) {
			$screen = get_current_screen();

			if ( 'edit-shop_subscription' == $screen->id ) {
				foreach ( $formatted_meta as $meta_id => $meta ) {
					if ( 'wcsg_recipient' == $meta['key'] ) {
						unset( $formatted_meta[ $meta_id ] );
					}
				}
			}
		}

		return $formatted_meta;
	}
}
WCSG_Admin::init();
