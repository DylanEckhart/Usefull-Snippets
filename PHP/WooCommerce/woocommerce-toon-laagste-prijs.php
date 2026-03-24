<?php
// WooCommerce - Laagste prijs tonen van variabele producten
// Deze snippet toont bij variabele producten alleen de laagste prijs, met optioneel het label “Vanaf” als er prijsverschillen zijn.

add_filter( 'woocommerce_get_price_html', 'change_variable_products_price_display', 10, 2 );
function change_variable_products_price_display( $price, $product ) {

    if( ! $product->is_type('variable') ) return $price;

    $prices = $product->get_variation_prices( true );

    if ( empty( $prices['price'] ) )
        return apply_filters( 'woocommerce_variable_empty_price_html', '', $product );

    $min_price = current( $prices['price'] );
    $max_price = end( $prices['price'] );
    $prefix_html = '<span class="price-prefix">' . __('Vanaf: ') . '</span>';

    $prefix = $min_price !== $max_price ? $prefix_html : '';

    return apply_filters( 'woocommerce_variable_price_html', $prefix . wc_price( $min_price ) . $product->get_price_suffix(), $product );
}
