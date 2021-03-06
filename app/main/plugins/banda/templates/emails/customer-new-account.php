<?php
/**
 * Customer new account email
 *
 * This template can be overridden by copying it to yourtheme/banda/emails/customer-new-account.php.
 *
 * HOWEVER, on occasion Banda will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://mtaandao.co.ke/docs/banda/document/template-structure/
 * @author 		Jabali
 * @package 	Banda/Templates/Emails
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<?php do_action( 'banda_email_header', $email_heading, $email ); ?>

	<p><?php printf( __( 'Thanks for creating an account on %s. Your username is <strong>%s</strong>', 'banda' ), esc_html( $blogname ), esc_html( $user_login ) ); ?></p>

<?php if ( 'yes' === get_option( 'banda_registration_generate_password' ) && $password_generated ) : ?>

	<p><?php printf( __( 'Your password has been automatically generated: <strong>%s</strong>', 'banda' ), esc_html( $user_pass ) ); ?></p>

<?php endif; ?>

	<p><?php printf( __( 'You can access your account area to view your orders and change your password here: %s.', 'banda' ), make_clickable( esc_url( wc_get_page_permalink( 'myaccount' ) ) ) ); ?></p>

<?php do_action( 'banda_email_footer', $email );
