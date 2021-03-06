<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Email_Customer_Completed_Order' ) ) :

/**
 * Customer Completed Order Email.
 *
 * Order complete emails are sent to the customer when the order is marked complete and usual indicates that the order has been shipped.
 *
 * @class       WC_Email_Customer_Completed_Order
 * @version     2.0.0
 * @package     Banda/Classes/Emails
 * @author      Jabali
 * @extends     WC_Email
 */
class WC_Email_Customer_Completed_Order extends WC_Email {

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->id             = 'customer_completed_order';
		$this->customer_email = true;
		$this->title          = __( 'Completed order', 'banda' );
		$this->description    = __( 'Order complete emails are sent to customers when their orders are marked completed and usually indicate that their orders have been shipped.', 'banda' );

		$this->heading        = __( 'Your order is complete', 'banda' );
		$this->subject        = __( 'Your {site_title} order from {order_date} is complete', 'banda' );

		$this->template_html  = 'emails/customer-completed-order.php';
		$this->template_plain = 'emails/plain/customer-completed-order.php';

		// Triggers for this email
		add_action( 'banda_order_status_completed_notification', array( $this, 'trigger' ) );

		// Other settings
		$this->heading_downloadable = $this->get_option( 'heading_downloadable', __( 'Your order is complete - download your files', 'banda' ) );
		$this->subject_downloadable = $this->get_option( 'subject_downloadable', __( 'Your {site_title} order from {order_date} is complete - download your files', 'banda' ) );

		// Call parent constuctor
		parent::__construct();
	}

	/**
	 * Trigger.
	 *
	 * @param int $order_id
	 */
	public function trigger( $order_id ) {

		if ( $order_id ) {
			$this->object                  = wc_get_order( $order_id );
			$this->recipient               = $this->object->billing_email;

			$this->find['order-date']      = '{order_date}';
			$this->find['order-number']    = '{order_number}';

			$this->replace['order-date']   = date_i18n( wc_date_format(), strtotime( $this->object->order_date ) );
			$this->replace['order-number'] = $this->object->get_order_number();
		}

		if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
			return;
		}

		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
	}

	/**
	 * Get email subject.
	 *
	 * @access public
	 * @return string
	 */
	public function get_subject() {
		if ( ! empty( $this->object ) && $this->object->has_downloadable_item() ) {
			return apply_filters( 'banda_email_subject_customer_completed_order', $this->format_string( $this->subject_downloadable ), $this->object );
		} else {
			return apply_filters( 'banda_email_subject_customer_completed_order', $this->format_string( $this->subject ), $this->object );
		}
	}

	/**
	 * Get email heading.
	 *
	 * @access public
	 * @return string
	 */
	public function get_heading() {
		if ( ! empty( $this->object ) && $this->object->has_downloadable_item() ) {
			return apply_filters( 'banda_email_heading_customer_completed_order', $this->format_string( $this->heading_downloadable ), $this->object );
		} else {
			return apply_filters( 'banda_email_heading_customer_completed_order', $this->format_string( $this->heading ), $this->object );
		}
	}

	/**
	 * Get content html.
	 *
	 * @access public
	 * @return string
	 */
	public function get_content_html() {
		return wc_get_template_html( $this->template_html, array(
			'order'         => $this->object,
			'email_heading' => $this->get_heading(),
			'sent_to_admin' => false,
			'plain_text'    => false,
			'email'			=> $this
		) );
	}

	/**
	 * Get content plain.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		return wc_get_template_html( $this->template_plain, array(
			'order'         => $this->object,
			'email_heading' => $this->get_heading(),
			'sent_to_admin' => false,
			'plain_text'    => true,
			'email'			=> $this
		) );
	}

	/**
	 * Initialise settings form fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'         => __( 'Enable/Disable', 'banda' ),
				'type'          => 'checkbox',
				'label'         => __( 'Enable this email notification', 'banda' ),
				'default'       => 'yes'
			),
			'subject' => array(
				'title'         => __( 'Subject', 'banda' ),
				'type'          => 'text',
				'description'   => sprintf( __( 'Defaults to <code>%s</code>', 'banda' ), $this->subject ),
				'placeholder'   => '',
				'default'       => '',
				'desc_tip'      => true
			),
			'heading' => array(
				'title'         => __( 'Email Heading', 'banda' ),
				'type'          => 'text',
				'description'   => sprintf( __( 'Defaults to <code>%s</code>', 'banda' ), $this->heading ),
				'placeholder'   => '',
				'default'       => '',
				'desc_tip'      => true
			),
			'subject_downloadable' => array(
				'title'         => __( 'Subject (downloadable)', 'banda' ),
				'type'          => 'text',
				'description'   => sprintf( __( 'Defaults to <code>%s</code>', 'banda' ), $this->subject_downloadable ),
				'placeholder'   => '',
				'default'       => '',
				'desc_tip'      => true
			),
			'heading_downloadable' => array(
				'title'         => __( 'Email Heading (downloadable)', 'banda' ),
				'type'          => 'text',
				'description'   => sprintf( __( 'Defaults to <code>%s</code>', 'banda' ), $this->heading_downloadable ),
				'placeholder'   => '',
				'default'       => '',
				'desc_tip'      => true
			),
			'email_type' => array(
				'title'         => __( 'Email type', 'banda' ),
				'type'          => 'select',
				'description'   => __( 'Choose which format of email to send.', 'banda' ),
				'default'       => 'html',
				'class'         => 'email_type wc-enhanced-select',
				'options'       => $this->get_email_type_options(),
				'desc_tip'      => true
			)
		);
	}
}

endif;

return new WC_Email_Customer_Completed_Order();
