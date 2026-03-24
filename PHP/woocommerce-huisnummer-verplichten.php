<?php
// WooCommerce - Huisnummer verplichten
add_action( 'woocommerce_after_checkout_validation', 'validate_checkout', 10, 2);
function validate_checkout( $data, $errors ){
	if (  ! preg_match('/[0-9]/', $data[ 'billing_address_1' ] ) ){
		$errors->add( 'address', 'Om je pakket te kunnen bezorgen, hebben we je huisnummer nodig. Vul dit alsjeblieft in bij het adres.' );
	}
}
