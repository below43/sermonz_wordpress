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
    if (get_the_ID() == get_option('sermonz_page')) {
        sermonz_start();
        global $sermonz_controller;
		if ($sermonz_controller->title) array_unshift($title, $sermonz_controller->title);
        remove_action( 'wp_head', 'rel_canonical' );
        add_action( 'wp_head', 'new_rel_canonical' );
        $sep = apply_filters( 'document_title_separator', '-' );
    	return array(implode(" $sep ", $title));
	}
	return $title;
}

function sermonz_filter_h1_title( $title, $id ) 
{
    if ($id == get_option('sermonz_page')) {
        global $sermonz_controller;
        if (isset($sermonz_controller->title) && $sermonz_controller->title)
        { 
            return ($sermonz_controller->route=="sermon")?$sermonz_controller->title:$title.": ".$sermonz_controller->title;
        }
    }
    return $title;
}

function sermonz_filter_content($content) 
{
    global $sermonz_controller;
    $sermonz_content = '<div class="sermonz">';
	if (get_the_ID() == get_option('sermonz_page')) 
	{
        $sermonz_content.=$sermonz_controller->content;
        $sermonz_content .= '<style src="/wp-includes/plugins/sermonz/sermonz.css"></style>';
        $sermonz_content .= sprintf('<style>%s</style>', esc_html( get_option('sermonz_css') ));		
    }
    $sermonz_content .= "</div>";
    return $sermonz_content.$content;
}

function new_rel_canonical() {
        global $sermonz_controller;
    
        $link = get_permalink();
        
        if ($sermonz_controller->route=="filter")
        {
            //no canonical for this
        }
        else if ($sermonz_controller->route=="sermon")
        {
            //no canonical for view sermon
        }
        else if ($sermonz_controller->active_search->series_id &&
            !($sermonz_controller->active_search->passage || $sermonz_controller->active_search->speaker || $sermonz_controller->active_search->keywords || $sermonz_controller->active_search->page_size!=10)
        )
        {
            //no canonical for series
        }
        else if ($sermonz_controller->active_search->page_number>1 &&
            !($sermonz_controller->active_search->passage || $sermonz_controller->active_search->speaker || $sermonz_controller->active_search->keywords || $sermonz_controller->active_search->page_size!=10)
        )
        {
            echo sprintf('%s<link rel="canonical" href="%s%s" />%s', "\n", $link, $sermonz_controller->active_search->page_number, "\n");
        }
        else 
        {
            echo sprintf('%s<link rel="canonical" href="%s" />%s', "\n", $link, "\n");
        }
}