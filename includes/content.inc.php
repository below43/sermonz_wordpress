<?php

add_action( 'init', 'set_custom_content_filters' );
add_action( 'loop_start', 'set_custom_content_filters' );

function set_custom_content_filters() 
{

    add_filter('pre_get_document_title', 'sermonz_filter_head_title');
    add_filter('wpseo_title', 'sermonz_filter_wpseo_title'); 
    add_filter('the_title', 'sermonz_filter_h1_title', 1, 2 );
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
function sermonz_filter_head_title($title="") 
{
    if (get_the_ID() == get_option('sermonz_page')) 
    {
        sermonz_start();
        global $sermonz_controller;
        remove_action( 'wp_head', 'rel_canonical' );
        add_action( 'wp_head', 'new_rel_canonical' );
        if (is_array($title)) {
            array_unshift($title, $sermonz_controller->title);
            $sep = apply_filters( 'document_title_separator', '-' );
            return array(implode(" $sep ", $title));
        }
	}
}

function sermonz_filter_wpseo_title($title) 
{
    if (get_the_ID() == get_option('sermonz_page')) {
        global $sermonz_controller;
        if (!$sermonz_controller) {
            sermonz_start();
            remove_action( 'wpseo_head', 'rel_canonical' );
            // // add_action( 'wpseo_head', 'new_rel_canonical' );
            add_filter('wpseo_canonical', 'new_rel_canonical_filter');
        }
        if ($sermonz_controller->title) 
        {
            $sep = apply_filters( 'document_title_separator', '-' );
            return sprintf("%s %s %s", $sermonz_controller->title, $sep, $title);
        }
    }
	return $title;
}


function sermonz_filter_h1_title( $title, $id ) 
{
    global $sermonz_controller;
    if ($id == get_option('sermonz_page') && (get_the_ID() == get_option('sermonz_page')))
    {

        if (isset($sermonz_controller->title) && $sermonz_controller->title!=null && $sermonz_controller->title!=""); // && $sermonz_controller->title)
        { 
            return sprintf("%s<span class=\"colon\">:</span> <br/><small>%s</small>", $title, $sermonz_controller->title?$sermonz_controller->title:"&nbsp;");
        }
    }
    return $title;
}


function sermonz_filter_header_subtitle() 
{
    global $sermonz_controller;
    if (isset($sermonz_controller->title) && $sermonz_controller->title)
    { 
        return sprintf("%s", $sermonz_controller->title);
    }
}

function sermonz_filter_content($content) 
{
    global $sermonz_controller;
	if (get_the_ID() == get_option('sermonz_page')) 
	{
    	$sermonz_content = '<div class="sermonz">';
        $sermonz_content.=$sermonz_controller->content;
        $sermonz_content .= '<style src="/wp-includes/plugins/sermonz/sermonz.css"></style>';
        $sermonz_content .= sprintf('<style>%s</style>', esc_html( get_option('sermonz_css') ));	
    	$sermonz_content .= "</div><br style=\"clear:left\" />";	
    }
    return $sermonz_content.$content;
}

function new_rel_canonical() {
        global $sermonz_controller;
    
        $link = get_permalink();
        
        $active_filters = 0;
        if (isset($sermonz_controller->active_search->series_id) && $sermonz_controller->active_search->series_id) 
        {
            $active_filters++;
        }
        if (isset($sermonz_controller->active_search_id) && $sermonz_controller->active_search->speaker_id) 
        {
            $active_filters++;
        }
        if (isset($sermonz_controller->active_search->book) && $sermonz_controller->active_search->book) 
        {
            $active_filters++;
        }

        $keyword_search_active = (isset($sermonz_controller->active_search->keywords) && $sermonz_controller->active_search->keywords);
        
        if ($sermonz_controller->route=="filter")
        {
            //no canonical for this
        }
        else if ($sermonz_controller->route=="talk")
        {
            //no canonical for view sermon
        }
        else if ($active_filters==1 && !$keyword_search_active)
        {
            //no canonical for any single filter page
        }
        else if ($sermonz_controller->active_search->page>1 && !$keyword_search_active)
        {
            echo sprintf('%s<link rel="canonical" href="%s%s" />%s', "\n", $link, $sermonz_controller->active_search->page, "\n");
        }
        else 
        {
            echo sprintf('%s<link rel="canonical" href="%s" />%s', "\n", $link, "\n");
        }
}

function new_rel_canonical_filter($canonical) {
    global $sermonz_controller;
    
        $link = get_permalink();
        
        $active_filters = 0;
        if (isset($sermonz_controller->active_search->series_id) && $sermonz_controller->active_search->series_id) 
        {
            $active_filters++;
        }
        if (isset($sermonz_controller->active_search->speaker_id) && $sermonz_controller->active_search->speaker_id) 
        {
            $active_filters++;
        }
        if (isset($sermonz_controller->active_search->book) && $sermonz_controller->active_search->book) 
        {
            $active_filters++;
        }

        $keyword_search_active = (isset($sermonz_controller->active_search->keywords) && $sermonz_controller->active_search->keywords);
        
        if ($sermonz_controller->route=="filter")
        {
            return get_site_url().$_SERVER['REQUEST_URI'];
        }
        else if ($sermonz_controller->route=="talk")
        {
            return get_site_url().$_SERVER['REQUEST_URI'];
        }
        else if ($active_filters==1 && !$keyword_search_active)
        {
            $link = add_query_arg ('speaker_id', $_GET['speaker_id'], $link);
            $link = add_query_arg ('series_id', $_GET['series_id'], $link);
            $link = add_query_arg ('book', $_GET['book'], $link);
            $link = add_query_arg ('page_number', 1, $link);
            $link = add_query_arg ('page_size', 12, $link);
            return $link;
        }
        // else if ($sermonz_controller->active_search->page>1 && !$keyword_search_active)
        // {
        //     return sprintf('%s%s', $link, $sermonz_controller->active_search->page);
        // }
        else 
        {
            return $link;
        }
}