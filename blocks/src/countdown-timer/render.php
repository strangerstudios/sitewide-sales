<?php
$anchor = preg_match( '/id="([^"]*)"/', $content, $anchor_match );
if ( ! empty( $anchor ) ) {
    $countdown_timer_id = ' id="' . esc_attr( $anchor_match[1] ) . '"';
} else {
    $countdown_timer_id = '';
}


$class = preg_match( '/class="([^"]*)"/', $content, $class_match );
if ( ! empty( $class ) ) {
    $countdown_timer_class = ' class="' . esc_attr( $class_match[1] ) . '"';
} else {
    $countdown_timer_class = '';
}


$has_inline_styles = array();


// Get text color if set and add to inline styles array.
if ( isset( $attributes['style']['color']['text'] ) && preg_match('/^#[a-f0-9]{6}$/i', $attributes['style']['color']['text'], $color_match ) ) {
    $has_inline_styles[] = 'color: ' . $color_match[0];
}


// Get background color if set and add to inline styles array.
if ( isset( $attributes['style']['color']['background'] ) && preg_match('/^#[a-f0-9]{6}$/i', $attributes['style']['color']['background'], $background_match ) ) {
    $has_inline_styles[] = 'background-color: ' . $background_match[0];
}


if ( ! empty( $has_inline_styles ) ) {
    $inline_styles = 'style="' . esc_attr( implode( ';', $has_inline_styles ) ) . '"';
} else {
    $inline_styles = '';
}


echo sprintf(
    '<div%1$s%2$s%3$s>' . do_shortcode( '[sitewide_sale_countdown]' ) . '</div>',
    $countdown_timer_id,
    $countdown_timer_class,
    $inline_styles,
);
