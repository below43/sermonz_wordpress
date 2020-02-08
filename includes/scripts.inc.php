<?php

/*
wp_enqueue_script( $handle, $src, $deps, $ver, $in_footer);

$handle is the name for the script.
$src defines where the script is located.
$deps is an array that can handle any script that your new script depends on, such as jQuery.
$ver lets you list a version number.
$in_footer is a boolean parameter (true/false) that allows you to place your scripts in the footer of your HTML document rather then in the header, so that it does not delay the loading of the DOM tree.
Your enqueue function may look like this:

wp_enqueue_script( 'script', get_template_directory_uri() . '/js/script.js', array ( 'jquery' ), 1.1, true);
*/

add_action('wp_enqueue_scripts', 'sermonz_enqueue_scripts');
function sermonz_enqueue_scripts() {
    wp_register_style( 
        'sermonz', 
        plugins_url('/sermonz.css', __FILE__) 
    );
    wp_enqueue_style( 'sermonz' );
    wp_enqueue_script( 
        'sermonz', 
        plugins_url('/sermonz.js', __FILE__),
        array( 'jquery' ) 
    );
}
 
function sermonz_load_dashicons(){
    wp_enqueue_style('dashicons');
}
add_action('wp_enqueue_scripts', 'sermonz_load_dashicons');