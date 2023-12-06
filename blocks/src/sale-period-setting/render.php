<?php
$attributes = isset( $block['attrs'] )
? $block['attrs']
: null;

// If there are no attributes to check, just return the block.
if ( ! isset( $attributes['sale_period_visibility'] ) ) {
return $block_content;
}

// If the block is visible, add custom classes as needed.
if ( is_visible( $attributes ) ) {
return $block_content;
} else {
return '';
}