<?php
// Elementor - Post counter met shortcode
// Deze snippet maakt een WordPress-shortcode aan die het totale aantal gepubliceerde berichten van een specifiek posttype telt en op de website weergeeft.
// Gebruik de shortcode [post_counter]

function general_post_counter() {
    $post_type = 'vacatures'; // Pas hier de slug van je post type aan

    $count = wp_count_posts( $post_type );
    $published_count = isset( $count->publish ) ? (int) $count->publish : 0;

    if ( $published_count <= 0 ) {
        return '';
    }

    return '<div class="post-count">' . $published_count . '</div>';
}
add_shortcode( 'post_counter', 'general_post_counter' );
