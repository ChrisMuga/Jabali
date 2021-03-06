<?php
/**
 * View Order
 *
 * Shows the details of a particular order on the account page.
 *
 * This template can be overridden by copying it to yourtheme/banda/myaccount/view-order.php.
 *
 * HOWEVER, on occasion Banda will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://mtaandao.co.ke/docs/banda/document/template-structure/
 * @author  Jabali
 * @package Banda/Templates
 * @version 2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<p><?php
	printf(
		__( 'Order #%1$s was placed on %2$s and is currently %3$s.', 'banda' ),
		'<mark class="order-number">' . $order->get_order_number() . '</mark>',
		'<mark class="order-date">' . date_i18n( get_option( 'date_format' ), strtotime( $order->order_date ) ) . '</mark>',
		'<mark class="order-status">' . wc_get_order_status_name( $order->get_status() ) . '</mark>'
	);
?></p>

<?php if ( $notes = $order->get_customer_order_notes() ) : ?>
	<h2><?php _e( 'Order Updates', 'banda' ); ?></h2>
	<ol class="banda-OrderUpdates commentlist notes">
		<?php foreach ( $notes as $note ) : ?>
		<li class="banda-OrderUpdate comment note">
			<div class="banda-OrderUpdate-inner comment_container">
				<div class="banda-OrderUpdate-text comment-text">
					<p class="banda-OrderUpdate-meta meta"><?php echo date_i18n( __( 'l jS \o\f F Y, h:ia', 'banda' ), strtotime( $note->comment_date ) ); ?></p>
					<div class="banda-OrderUpdate-description description">
						<?php echo wpautop( wptexturize( $note->comment_content ) ); ?>
					</div>
	  				<div class="clear"></div>
	  			</div>
				<div class="clear"></div>
			</div>
		</li>
		<?php endforeach; ?>
	</ol>
<?php endif; ?>

<?php do_action( 'banda_view_order', $order_id ); ?>
