<?php
function count_vacancies () {
    $count = wp_count_posts( 'vacatures' );
    $published_count = isset( $count->publish ) ? (int) $count->publish : 0;

    if ( $published_count <= 0 ) {
        return '';
    }

    return '<div class="vacancies-count">' . $published_count . '</div>';
}
add_shortcode( 'number_of_vacancies', 'count_vacancies' );