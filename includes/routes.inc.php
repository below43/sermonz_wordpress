<?php

add_filter( 'rewrite_rules_array', 'sermonz_insert_rewrite_rules' );
add_filter( 'query_vars', 'sermonz_insert_query_vars' );
add_action( 'wp_loaded', 'sermonz_flush_rules' ); 

// flush_rules() if our rules are not yet included
function sermonz_flush_rules() 
{
    $rules = get_option( 'rewrite_rules' );
	$rule_regexp = sermonz_rule_regexp();
	if (!isset($rules[$rule_regexp] ) ) {
        global $wp_rewrite;
        $wp_rewrite->flush_rules();
    }
}

function sermonz_get_page_uri() 
{
	$id = get_option('sermonz_page');
	return rtrim(str_replace(home_url(), '', get_permalink($id)), '/');
}

function sermonz_rule_regexp() 
{
	$page = sermonz_get_page_uri();
	return '^'.ltrim($page, "/").'/(.*?)/(.*?)$';
}

// Adding a new rule
function sermonz_insert_rewrite_rules( $rules ) 
{
	$newrules = array();
	$rule_regexp = sermonz_rule_regexp();
	$id = get_option('sermonz_page');
	$newrules[$rule_regexp] = 'index.php?page_id='.$id.'&sermonz_route=$matches[1]&sermonz_argument=$matches[2]';
	return $newrules + $rules;
}

// Adding the get vars so that WP recognizes it
function sermonz_insert_query_vars( $vars ) 
{
	array_push($vars, 'sermonz_route');
	array_push($vars, 'sermonz_argument');
	array_push($vars, 'order_by');
	array_push($vars, 'order_direction');
	array_push($vars, 'series_id');
	array_push($vars, 'series_name');
	array_push($vars, 'speaker_id');
	array_push($vars, 'speaker_name');
	array_push($vars, 'book');
	array_push($vars, 'keywords');
	array_push($vars, 'page_number');
	array_push($vars, 'page_size');
    return $vars;
} 