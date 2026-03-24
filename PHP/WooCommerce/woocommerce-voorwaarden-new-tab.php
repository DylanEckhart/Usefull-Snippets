<?php
// WooCommerce - Algemene voorwaarden openen in nieuw venster
// Deze snippet verwijdert de standaard weergave van de algemene voorwaarden op de checkout, zodat deze in een nieuw venster geopend kunnen worden.

function reialesa_woocommerce_checkout_terms_and_conditions() {
  remove_action( 'woocommerce_checkout_terms_and_conditions', 'wc_terms_and_conditions_page_content', 30 );
}
add_action( 'wp', 'reialesa_woocommerce_checkout_terms_and_conditions' );
