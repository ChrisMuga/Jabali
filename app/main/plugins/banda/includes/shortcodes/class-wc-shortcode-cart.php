<?php
/**
 * Cart Shortcode
 *
 * Used on the cart page, the cart shortcode displays the cart contents and interface for coupon codes and other cart bits and pieces.
 *
 * @author 		Jabali
 * @category 	Shortcodes
 * @package 	Banda/Shortcodes/Cart
 * @version     2.3.0
 */
class WC_Shortcode_Cart {

	/**
	 * Calculate shipping for the cart.
	 */
	public static function calculate_shipping() {
		try {
			WC()->shipping->reset_shipping();

			$country  = wc_clean( $_POST['calc_shipping_country'] );
			$state    = wc_clean( isset( $_POST['calc_shipping_state'] ) ? $_POST['calc_shipping_state'] : '' );
			$postcode = apply_filters( 'banda_shipping_calculator_enable_postcode', true ) ? wc_clean( $_POST['calc_shipping_postcode'] ) : '';
			$city     = apply_filters( 'banda_shipping_calculator_enable_city', false ) ? wc_clean( $_POST['calc_shipping_city'] ) : '';

			if ( $postcode && ! WC_Validation::is_postcode( $postcode, $country ) ) {
				throw new Exception( __( 'Please enter a valid postcode/ZIP.', 'banda' ) );
			} elseif ( $postcode ) {
				$postcode = wc_format_postcode( $postcode, $country );
			}

			if ( $country ) {
				WC()->customer->set_location( $country, $state, $postcode, $city );
				WC()->customer->set_shipping_location( $country, $state, $postcode, $city );
			} else {
				WC()->customer->set_to_base();
				WC()->customer->set_shipping_to_base();
			}

			WC()->customer->calculated_shipping( true );

			wc_add_notice( __( 'Shipping costs updated.', 'banda' ), 'notice' );

			do_action( 'banda_calculated_shipping' );

		} catch ( Exception $e ) {
			if ( ! empty( $e ) ) {
				wc_add_notice( $e->getMessage(), 'error' );
			}
		}
	}

	/**
	 * Output the cart shortcode.
	 */
	public static function output() {
		// Constants
		if ( ! defined( 'BANDA_CART' ) ) {
			define( 'BANDA_CART', true );
		}

		// Update Shipping
		if ( ! empty( $_POST['calc_shipping'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'banda-cart' ) ) {
			self::calculate_shipping();

			// Also calc totals before we check items so subtotals etc are up to date
			WC()->cart->calculate_totals();
		}

		// Check cart items are valid
		do_action( 'banda_check_cart_items' );

		// Calc totals
		WC()->cart->calculate_totals();

		if ( WC()->cart->is_empty() ) {
			wc_get_template( 'cart/cart-empty.php' );
		} else {
			wc_get_template( 'cart/cart.php' );
		}
	}
}
