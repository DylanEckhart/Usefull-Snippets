<?php
// WooCommerce - Algemene voorwaarden openen in nieuw venster
function reialesa_woocommerce_checkout_terms_and_conditions() {
  remove_action( 'woocommerce_checkout_terms_and_conditions', 'wc_terms_and_conditions_page_content', 30 );
}
add_action( 'wp', 'reialesa_woocommerce_checkout_terms_and_conditions' );
