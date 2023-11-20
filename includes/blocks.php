<?php
/**
 * Add new block category for Sitewide Sales blocks.
 *
 * @since 1.0
 *
 * @param array $categories Array of block categories.
 * @return array Array of block categories.
 */
function swsales_block_categories( $categories ) {
    return array_merge(
        $categories,
        array(
            array(
                'slug'  => 'swsales',
                'title' => __( 'Sitewide Sales', 'sitewide-sales' ),
            ),
        )
    );
}
add_filter( 'block_categories_all', 'swsales_block_categories' );

function swsales_register_blocks() {
    register_block_type();
}

add_action( 'init', 'swsales_register_blocks' );