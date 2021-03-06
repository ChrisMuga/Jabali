<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Free Shipping Method.
 *
 * A simple shipping method for free shipping.
 *
 * @class   WC_Shipping_Free_Shipping
 * @version 2.6.0
 * @package Banda/Classes/Shipping
 * @author  Jabali
 */
class WC_Shipping_Free_Shipping extends WC_Shipping_Method {

	/**
	 * Min amount to be valid.
	 *
	 * @var integer
	 */
	public $min_amount = 0;

	/**
	 * Requires option.
	 *
	 * @var string
	 */
	public $requires = '';

	/**
	 * Constructor.
	 *
	 * @param int $instance_id Shipping method instance.
	 */
	public function __construct( $instance_id = 0 ) {
		$this->id                 = 'free_shipping';
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = __( 'Free Shipping', 'banda' );
		$this->method_description = __( 'Free Shipping is a special method which can be triggered with coupons and minimum spends.', 'banda' );
		$this->supports           = array(
			'shipping-zones',
			'instance-settings',
			'instance-settings-modal',
		);

		$this->init();
	}

	/**
	 * Initialize free shipping.
	 */
	public function init() {
		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		$this->title      = $this->get_option( 'title' );
		$this->min_amount = $this->get_option( 'min_amount', 0 );
		$this->requires   = $this->get_option( 'requires' );

		// Actions.
		add_action( 'banda_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Init form fields.
	 */
	public function init_form_fields() {
		$this->instance_form_fields = array(
			'title' => array(
				'title'       => __( 'Title', 'banda' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'banda' ),
				'default'     => $this->method_title,
				'desc_tip'    => true,
			),
			'requires' => array(
				'title'   => __( 'Free Shipping Requires...', 'banda' ),
				'type'    => 'select',
				'class'   => 'wc-enhanced-select',
				'default' => '',
				'options' => array(
					''           => __( 'N/A', 'banda' ),
					'coupon'     => __( 'A valid free shipping coupon', 'banda' ),
					'min_amount' => __( 'A minimum order amount', 'banda' ),
					'either'     => __( 'A minimum order amount OR a coupon', 'banda' ),
					'both'       => __( 'A minimum order amount AND a coupon', 'banda' ),
				),
			),
			'min_amount' => array(
				'title'       => __( 'Minimum Order Amount', 'banda' ),
				'type'        => 'price',
				'placeholder' => wc_format_localized_price( 0 ),
				'description' => __( 'Users will need to spend this amount to get free shipping (if enabled above).', 'banda' ),
				'default'     => '0',
				'desc_tip'    => true,
			),
		);
	}

	/**
	 * Get setting form fields for instances of this shipping method within zones.
	 *
	 * @return array
	 */
	public function get_instance_form_fields() {
		wc_enqueue_js( "
			jQuery( function( $ ) {
				function wcFreeShippingShowHideMinAmountField( el ) {
					var form = $( el ).closest( 'form' );
					var minAmountField = $( '#banda_free_shipping_min_amount', form ).closest( 'tr' );
					if ( 'coupon' === $( el ).val() || '' === $( el ).val() ) {
						minAmountField.hide();
					} else {
						minAmountField.show();
					}
				}

				$( document.body ).on( 'change', '#banda_free_shipping_requires', function() {
					wcFreeShippingShowHideMinAmountField( this );
				});

				// Change while load.
				$( '#banda_free_shipping_requires' ).change();
				$( document.body ).on( 'wc_backbone_modal_loaded', function( evt, target ) {
					if ( 'wc-modal-shipping-method-settings' === target ) {
						wcFreeShippingShowHideMinAmountField( $( '#wc-backbone-modal-dialog #banda_free_shipping_requires', evt.currentTarget ) );
					}
				} );
			});
		" );

		return parent::get_instance_form_fields();
	}

	/**
	 * See if free shipping is available based on the package and cart.
	 *
	 * @param array $package Shipping package.
	 * @return bool
	 */
	public function is_available( $package ) {
		$has_coupon         = false;
		$has_met_min_amount = false;

		if ( in_array( $this->requires, array( 'coupon', 'either', 'both' ) ) ) {
			if ( $coupons = WC()->cart->get_coupons() ) {
				foreach ( $coupons as $code => $coupon ) {
					if ( $coupon->is_valid() && $coupon->enable_free_shipping() ) {
						$has_coupon = true;
						break;
					}
				}
			}
		}

		if ( in_array( $this->requires, array( 'min_amount', 'either', 'both' ) ) && isset( WC()->cart->cart_contents_total ) ) {
			$total = WC()->cart->get_displayed_subtotal();

			if ( 'incl' === WC()->cart->tax_display_cart ) {
				$total = $total - ( WC()->cart->get_cart_discount_total() + WC()->cart->get_cart_discount_tax_total() );
			} else {
				$total = $total - WC()->cart->get_cart_discount_total();
			}

			if ( $total >= $this->min_amount ) {
				$has_met_min_amount = true;
			}
		}

		switch ( $this->requires ) {
			case 'min_amount' :
				$is_available = $has_met_min_amount;
				break;
			case 'coupon' :
				$is_available = $has_coupon;
				break;
			case 'both' :
				$is_available = $has_met_min_amount && $has_coupon;
				break;
			case 'either' :
				$is_available = $has_met_min_amount || $has_coupon;
				break;
			default :
				$is_available = true;
				break;
		}

		return apply_filters( 'banda_shipping_' . $this->id . '_is_available', $is_available, $package );
	}

	/**
	 * Called to calculate shipping rates for this method. Rates can be added using the add_rate() method.
	 *
	 * @uses WC_Shipping_Method::add_rate()
	 *
	 * @param array $package Shipping package.
	 */
	public function calculate_shipping( $package = array() ) {
		$this->add_rate( array(
			'label'   => $this->title,
			'cost'    => 0,
			'taxes'   => false,
			'package' => $package,
		) );
	}
}
