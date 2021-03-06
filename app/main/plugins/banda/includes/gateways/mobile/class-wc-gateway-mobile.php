<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Mobile Phone Gateway.
 *
 * Provides a Mobile Phone Payment Gateway.
 *
 * @class 		WC_Gateway_Mobile
 * @extends		WC_Payment_Gateway
 * @version		2.1.0
 * @package		Banda/Classes/Payment
 * @author 		Jabali
 */
class WC_Gateway_Mobile extends WC_Payment_Gateway {

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id                 = 'mobile';
		$this->icon               = apply_filters( 'banda_mobile_icon', '' );
		$this->method_title       = __( 'Mobile Payments', 'banda' );
		$this->method_description = __( 'Have your customers pay with MPesa (or by other means) upon delivery.', 'banda' );
		$this->has_fields         = false;

		// Load the settings
		$this->init_form_fields();
		$this->init_settings();

		// Get settings
		$this->title              = $this->get_option( 'title' );
		$this->description        = $this->get_option( 'description' );
		$this->instructions       = $this->get_option( 'instructions', $this->description );
		$this->enable_for_methods = $this->get_option( 'enable_for_methods', array() );
		$this->enable_for_virtual = $this->get_option( 'enable_for_virtual', 'yes' ) === 'yes' ? true : false;
		$this->mpesa_mode = $this->get_option( 'mpesa_mode', 'no' ) === 'yes' ? true : false;
		$this->mpesa_name         = $this->get_option( 'mpesa_name' );
		$this->mpesa_paybill         = $this->get_option( 'mpesa_paybill' );
		$this->mpesa_sag         = $this->get_option( 'mpesa_sag' );
		$this->mpesa_stamp         = $this->get_option( 'mpesa_stamp' );

		add_action( 'banda_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'banda_thankyou_mobile', array( $this, 'thankyou_page' ) );

		// Customer Emails
		add_action( 'banda_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$shipping_methods = array();

		if ( is_admin() ) {
			foreach ( WC()->shipping()->load_shipping_methods() as $method ) {
				$shipping_methods[ $method->id ] = $method->get_method_title();
			}
		}

		$this->form_fields = array(
			'enabled' => array(
				'title'       => __( 'Enable Mobile Payments', 'banda' ),
				'label'       => __( 'Enable Mobile Payments', 'banda' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no'
			),
			'title' => array(
				'title'       => __( 'Title', 'banda' ),
				'type'        => 'text',
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'banda' ),
				'default'     => __( 'Mobile Payments', 'banda' ),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __( 'Description', 'banda' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your website.', 'banda' ),
				'default'     => __( '<button style="background-color:#66ad45"><a href="../pay" style="none" ><img src="../pay/l.png" alt="" ></a></button>', 'banda' ),
				//'css'               => 'background: #21b68e;',
				'desc_tip'    => true,
			),
			'instructions' => array(
				'title'       => __( 'Instructions', 'banda' ),
				'type'        => 'textarea',
				'description' => __( 'Instructions that will be added to the thank you page.', 'banda' ),
				'default'     => __( '<img src="../../../pay/mpesa.png">', 'banda' ),
				'desc_tip'    => true,
			),
			'enable_for_methods' => array(
				'title'             => __( 'Enable for delivery methods', 'banda' ),
				'type'              => 'multiselect',
				'class'             => 'wc-enhanced-select',
				'css'               => 'width: 450px;',
				'default'           => '',
				'description'       => __( 'If Mobile Payments are only available for certain methods, set it up here. Leave blank to enable for all methods.', 'banda' ),
				'options'           => $shipping_methods,
				'desc_tip'          => true,
				'custom_attributes' => array(
					'data-placeholder' => __( 'Select delivery methods', 'banda' )
				)
			),
			'enable_for_virtual' => array(
				'title'             => __( 'Accept for virtual orders', 'banda' ),
				'label'             => __( 'Accept Mobile Payments if the order is virtual', 'banda' ),
				'type'              => 'checkbox',
				'default'           => 'yes'
			),
			'mpesa_mode' => array(
				'title'             => __( 'Lipa Na M-Pesa', 'banda' ),
				'label'             => __( 'Accept Payments via Lipa Na M-Pesa', 'banda' ),
				'type'              => 'checkbox',
				'default'           => 'no'
			),
			'mpesa_name' => array(
				'title'       => __( 'M-Pesa Merchant Name', 'banda' ),
				'type'        => 'text',
				'description' => __( 'Your business name as registered with Safaricom.', 'banda' ),
				'default'     => __( 'Mtaandao Digital Solutions', 'banda' ),
				'desc_tip'    => true,
			),
			'mpesa_paybill' => array(
			'title'       => __( 'M-Pesa Paybill Number', 'banda' ),
			'type'        => 'text',
			'description' => __( 'Your Paybill number, supplied by Safaricom.', 'banda' ),
			'default'     => __( '898998', 'banda' ),
			'desc_tip'    => true,
			),
			'mpesa_sag' => array(
			'title'       => __( 'M-Pesa SAG Passkey', 'banda' ),
			'type'        => 'text',
			'description' => __( 'Your Safaricom Access Gateway password issued on creation of the merchant account.', 'banda' ),
			'default'     => __( 'ZmRmZDYwYzIzZDQxZDc5ODYwMTIzYjUxNzNkZDMwMDRjNGRkZTY2ZDQ3ZTI0YjVjODc4ZTExNTNjMDA1YTcwNw==', 'banda' ),
			'desc_tip'    => true,
			),
			'mpesa_stamp' => array(
			'title'       => __( 'M-Pesa Timestamp', 'banda' ),
			'type'        => 'text',
			'description' => __( 'Your Safaricom Timestamp issued on creation of the merchant account.', 'banda' ),
			'default'     => __( '20160510161908', 'banda' ),
			'desc_tip'    => true,
			)
	   );
	}

	/**
	 * Check If The Gateway Is Available For Use.
	 *
	 * @return bool
	 */
	public function is_available() {
		$order          = null;
		$needs_shipping = false;

		// Test if shipping is needed first
		if ( WC()->cart && WC()->cart->needs_shipping() ) {
			$needs_shipping = true;
		} elseif ( is_page( wc_get_page_id( 'checkout' ) ) && 0 < get_query_var( 'order-pay' ) ) {
			$order_id = absint( get_query_var( 'order-pay' ) );
			$order    = wc_get_order( $order_id );

			// Test if order needs shipping.
			if ( 0 < sizeof( $order->get_items() ) ) {
				foreach ( $order->get_items() as $item ) {
					$_product = $order->get_product_from_item( $item );
					if ( $_product && $_product->needs_shipping() ) {
						$needs_shipping = true;
						break;
					}
				}
			}
		}

		$needs_shipping = apply_filters( 'banda_cart_needs_shipping', $needs_shipping );

		// Virtual order, with virtual disabled
		if ( ! $this->enable_for_virtual && ! $needs_shipping ) {
			return false;
		}

		// Check methods
		if ( ! empty( $this->enable_for_methods ) && $needs_shipping ) {

			// Only apply if all packages are being shipped via chosen methods, or order is virtual
			$chosen_shipping_methods_session = WC()->session->get( 'chosen_shipping_methods' );

			if ( isset( $chosen_shipping_methods_session ) ) {
				$chosen_shipping_methods = array_unique( $chosen_shipping_methods_session );
			} else {
				$chosen_shipping_methods = array();
			}

			$check_method = false;

			if ( is_object( $order ) ) {
				if ( $order->shipping_method ) {
					$check_method = $order->shipping_method;
				}

			} elseif ( empty( $chosen_shipping_methods ) || sizeof( $chosen_shipping_methods ) > 1 ) {
				$check_method = false;
			} elseif ( sizeof( $chosen_shipping_methods ) == 1 ) {
				$check_method = $chosen_shipping_methods[0];
			}

			if ( ! $check_method ) {
				return false;
			}

			$found = false;

			foreach ( $this->enable_for_methods as $method_id ) {
				if ( strpos( $check_method, $method_id ) === 0 ) {
					$found = true;
					break;
				}
			}

			if ( ! $found ) {
				return false;
			}
		}

		return parent::is_available();
	}


	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		// Mark as processing or on-hold (payment won't be taken until delivery)
		$order->update_status( apply_filters( 'banda_mobile_process_payment_order_status', $order->has_downloadable_item() ? 'on-hold' : 'processing', $order ), __( 'Pay via MPesa to the given Till/Paybill Number.', 'banda' ) );

		// Reduce stock levels
		$order->reduce_order_stock();

		// Remove cart
		WC()->cart->empty_cart();

		// Return thankyou redirect
		return array(
			'result' 	=> 'success',
			'redirect'	=> $this->get_return_url( $order )
		);
	}

	/**
	 * Output for the order received page.
	 */
	public function thankyou_page() {
		if ( $this->instructions ) {
			echo wpautop( wptexturize( $this->instructions ) );
		}
	}

	/**
	 * Add content to the WC emails.
	 *
	 * @access public
	 * @param WC_Order $order
	 * @param bool $sent_to_admin
	 * @param bool $plain_text
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $this->instructions && ! $sent_to_admin && 'mobile' === $order->payment_method ) {
			echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
		}
	}
}
