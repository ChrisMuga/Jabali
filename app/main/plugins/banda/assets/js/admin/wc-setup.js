/*global wc_setup_params */
jQuery( function( $ ) {

	var locale_info = $.parseJSON( wc_setup_params.locale_info );

	$( 'select[name="store_location"]' ).change( function() {
		var country_option      = $(this).val();
		var country             = country_option.split( ':' )[0];
		var country_locale_info = locale_info[ country ];
		var hide_if_set = [ 'thousand_sep', 'decimal_sep', 'num_decimals', 'currency_pos' ];

		if ( country_locale_info ) {
			$.each( country_locale_info, function( index, value) {
				$(':input[name="' + index + '"]').val( value ).change();

				if ( -1 !== $.inArray( index, hide_if_set ) ) {
					$(':input[name="' + index + '"]').closest('tr').hide();
				}
			} );
		} else {
			$(':input[name="currency_pos"]').closest('tr').show();
			$(':input[name="thousand_sep"]').closest('tr').show();
			$(':input[name="decimal_sep"]').closest('tr').show();
			$(':input[name="num_decimals"]').closest('tr').show();
		}
	} ).change();

	$( 'input[name="banda_calc_taxes"]' ).change( function() {
		if ( $(this).is( ':checked' ) ) {
			$(':input[name="banda_prices_include_tax"], :input[name="banda_import_tax_rates"]').closest('tr').show();
			$('tr.tax-rates').show();
		} else {
			$(':input[name="banda_prices_include_tax"], :input[name="banda_import_tax_rates"]').closest('tr').hide();
			$('tr.tax-rates').hide();
		}
	} ).change();

	$( '.button-next' ).on( 'click', function() {
		$('.wc-setup-content').block({
			message: null,
			overlayCSS: {
				background: '#fff',
				opacity: 0.6
			}
		});
		return true;
	} );

	$( '.wc-wizard-payment-gateways' ).on( 'change', '.wc-wizard-gateway-enable input', function() {
		if ( $( this ).is( ':checked' ) ) {
			$( this ).closest( 'li' ).addClass( 'checked' );
		} else {
			$( this ).closest( 'li' ).removeClass( 'checked' );
		}
	} );

	$( '.wc-wizard-payment-gateways' ).on( 'click', 'li.wc-wizard-gateway', function() {
		var $enabled = $( this ).find( '.wc-wizard-gateway-enable input' );

		$enabled.prop( 'checked', ! $enabled.prop( 'checked' ) ).change();
	} );

	$( '.wc-wizard-payment-gateways' ).on( 'click', 'li.wc-wizard-gateway table, li.wc-wizard-gateway a', function( e ) {
		e.stopPropagation();
	} );
} );
