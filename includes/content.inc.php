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
        return $title.=": ".$sermonz_api->title;
    }
    return $title;
}

function sermonz_filter_content($content) 
{
    global $sermonz_api;
	if (get_the_ID() == get_option('sermonz_page')) 
	{
        $base_url = sermonz_get_page_uri();
        
        if ($sermonz_api->route=="filter")
        {
            $sermonz_content .= sprintf(
                '<div class="sermonz_filter_inner_head"><h2 class="entry_subtitle">Filter By %s</h2><a href="%s">%s</a></div>',
                $sermonz_api->filter_name,
                $base_url,
                '&lt; Go Back'
            );
        }
        else 
        {
            $sermonz_content .= sprintf('<form action="%s" class="sermonz_form">', $base_url);
            $sermonz_content .= sprintf(
                '<div class="sermonz_search_field_wrap"><label><span class="screen-reader-text">%s</span><input type="search" class="sermonz_search_field" placeholder="%s" value="%s" name="sermonz_search" title="%s" /></label></div>', 
                esc_attr_x('Search sermon library', 'sermon search text box'),
                esc_attr_x('Search sermons', 'sermon search text box'),
                esc_attr_x('Search', 'sermon search text box'),
                esc_attr($sermonz_api->active_search->keywords)
            );

            $sermonz_content .= get_filter_content();
    
            $sermonz_content .= "</form>";
        } 

        $sermonz_content.=$sermonz_api->content;

        $sermonz_content .= '<style src="/wp-includes/plugins/sermonz/sermonz.css"></style>';
        $sermonz_content .= sprintf('<style>%s</style>', esc_html( get_option('sermonz_css') ));

		return $sermonz_content.$content;
	}
	return $content;
}

function get_filter_content()
{
    $filter_html = "";

    $filters = array(
        "/filter/series"=>"Series",
        "/filter/books"=>"Book",
        "/filter/speakers"=>"Speaker"
    );

    foreach ($filters as $filter=>$label)
    {
        $classes = "filters ";
        
        switch($label) {
            case "Series":
                if (isset($sermonz_api->active_search->series_id))
                {
                    $classes.="active ";
                }
                break;
            case "Book":
                if (isset($sermonz_api->active_search->book))
                {
                    $classes.="active ";
                }
                break;
            case "Speaker":
                if (isset($sermonz_api->active_search->speaker))
                {
                    $classes.="active ";
                }
                break;
        }
        $base_url = sermonz_get_page_uri();
        $filter_html .= sprintf(
            '<a href="%s" id="%s" class="%s">%s</a>',
            esc_attr_x($base_url.$filter, "sermonz href for filter"),
            strtolower($label),
            $classes,
            esc_attr_x($label, "sermonz label for filter")
        );
    }
    return sprintf(
        '<div class="sermonz_search_filters_wrap"><label>%s</label><div class="sermon_search_filters_wrap_a">%s</div><a class="sermonz_show_search"><span class="screen-reader-text">Show Search</a></a></div>',
        esc_attr_x('Filter By:', 'sermon search filters'),
        $filter_html
    );
}

