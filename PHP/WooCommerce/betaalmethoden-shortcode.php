<?php
// WooCommerce - Betaalmethoden iconen
// Deze code verzamelt de iconen van je actieve WooCommerce betaalmethoden en presenteert ze overzichtelijk naast elkaar in strak afgewerkte blokjes.

// Gebruik shortcode [payment_methods_icons]

add_shortcode( 'payment_methods_icons', 'render_payment_gateway_icons' );

function render_payment_gateway_icons() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return '';
    }

    $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
    if ( !empty ($available_gateways) ) {
        $html = '<div class="payment-methods-grid">';

        foreach ( $available_gateways as $gateway ) {
            $icon_html = $gateway->get_icon();
    
            if ( ! empty( $icon_html ) ) {
                $html .= '<div class="payment-method-card" title="' . esc_attr( $gateway->get_title() ) . '">' . $icon_html . '</div>';
            }
        }
    
        $html .= '</div>';
    }

    return $html;
}
