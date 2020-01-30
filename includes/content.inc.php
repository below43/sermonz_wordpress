<?php

add_filter('document_title_parts', 'sermonz_filter_head_title');


add_action( 'loop_start', 'set_custom_content_filters' );
function set_custom_content_filters() 
{
    add_filter('the_title', 'sermonz_filter_h1_title', 10, 2 );
    add_filter('the_content', 'sermonz_filter_content', 10);
}

add_action( 'loop_end', 'unset_custom_content_filters' );
function unset_custom_content_filters() 
{
	if ( has_filter( 'the_title', 'sermonz_filter_h1_title' ) ) 
	{
	  remove_filter( 'the_title', 'sermonz_filter_h1_title' );
	}
	if ( has_filter( 'the_content', 'sermonz_filter_content' ) ) 
	{
	  remove_filter( 'the_content', 'sermonz_filter_content' );
	}
}


function sermonz_filter_head_title($title) 
{
    $sep = apply_filters( 'document_title_separator', '-' );
    if (get_the_ID() == get_option('sermonz_page')) {
		array_unshift($title, "Custom Title");
    	return array(implode(" $sep ", $title));
	}
	return $title;
}

function sermonz_filter_h1_title( $title, $id ) 
{
    if ($id == get_option('sermonz_page')) {
        global $sermonz_api;
        return $sermonz_api->title;
    }
    return $title;
}

function sermonz_filter_content($content) 
{
	if (get_the_ID() == get_option('sermonz_page')) 
	{
		$base_url = sermonz_get_page_uri();
		$sermonz_content = <<< END
		<p>Recent Talks&nbsp;&nbsp;|&nbsp;&nbsp; 
		<a href="$base_url/sermons/search">Search</a>&nbsp;&nbsp;|&nbsp;&nbsp; 
		<a href="$base_url/filter/series">Series</a>&nbsp;&nbsp;|&nbsp;&nbsp; 
		<a href="$base_url/filter/books">Book</a>&nbsp;&nbsp;|&nbsp;&nbsp;
		<a href="$base_url/filter/speakers">Speaker</a></p> 
END;

        global $sermonz_api;
        $sermonz_content.=$sermonz_api->content;
		return $sermonz_content.$content;
	}
	return $content;
}