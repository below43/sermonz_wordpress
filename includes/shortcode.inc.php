<?php

//for the time being, we're not using the shortcode option

function sermonz_shortcode($atts = [], $content = null, $tag = '')
{
    $o = '<div class="wporg-box">Coming soon...</div>';
    // return output
    return $o;
}
 
function sermonz_shortcodes_init()
{
    add_shortcode('sermonz', 'sermonz_shortcode');
}
 
add_action('init', 'sermonz_shortcodes_init');