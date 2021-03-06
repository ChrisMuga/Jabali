<?php
/**
 * My Account page
 *
 * This template can be overridden by copying it to yourtheme/banda/myaccount/my-account.php.
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

wc_print_notices();

/**
 * My Account navigation.
 * @since 2.6.0
 */
do_action( 'banda_account_navigation' ); ?>

<div class="banda-MyAccount-content">
	<?php
		/**
		 * My Account content.
		 * @since 2.6.0
		 */
		do_action( 'banda_account_content' );
	?>
</div>
