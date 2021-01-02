<?php

class SermonzViewSearch
{
    private $_sermonz_controller;
    private $_content = "";
    private $_active_search;

    public function __construct($sermonz_controller)
    {
        $this->_sermonz_controller = $sermonz_controller;
        $this->_active_search = $this->_sermonz_controller->active_search;
        $this->_load_sermons_head();
        $this->_load_sermons_body();
    }

    public function get_content() 
    {
        return $this->_content;
    }

    private function _load_sermons_head()
    {
        
        $sermonz_content = "";
        $sermonz_content .= sprintf('<form action="%s" method="GET" class="sermonz_form">', $this->_sermonz_controller->base_url);
        $keyword_search_active = (isset($this->_active_search->keywords) && $this->_active_search->keywords)?"active":"";
        $sermonz_content .= sprintf(
            '<input type="hidden" name="speaker_id" value="%s" />',
            $this->_active_search->speaker_id
        );
        $sermonz_content .= sprintf(
            '<input type="hidden" name="series_id" value="%s" />',
            $this->_active_search->series_id
        );
        $sermonz_content .= sprintf(
            '<input type="hidden" name="book" value="%s" />',
            $this->_active_search->book
        );
        $sermonz_content .= sprintf(
            '<div class="sermonz_search_field_wrap %s"><label><span class="screen-reader-text">%s</span><input type="search" class="keywords" placeholder="%s" name="keywords" title="%s" value="%s" /></label>
            <a style="cursor:pointer" class="sermonz_clear_search"><span class="dashicons dashicons-no-alt"></span><span class="screen-reader-text">Clear Search</span></a></div>', 
            $keyword_search_active,
            esc_attr_x('Search talks library', 'sermon search text box'),
            esc_attr_x('Search talks', 'sermon search text box'),
            esc_attr_x('Search', 'sermon search text box'),
            esc_attr($this->_active_search->keywords)
        );


        $filter_html = '';
    
        $filters = array(
            "/filter/series"=>"Series",
            "/filter/books"=>"Book",
            "/filter/speakers"=>"Speaker"
        );
    
        foreach ($filters as $filter=>$label)
        {
            $classes = "sermonz_filter ";
            $active = false;
            $active_count = 0;
            switch($label) {
                case "Series":
                    if (isset($this->_active_search->series_id)&&isset($this->_active_search->series_name)&&$this->_active_search->series_name)
                    {
                        $classes.="active ";
                        $active = true;
                        $filter_val = $this->_active_search->series_name;
                    }
                    break;
                case "Book":
                    if (isset($this->_active_search->book)&&$this->_active_search->book)
                    {
                        $classes.="active ";
                        $active = true;
                        $filter_val = $this->_active_search->book;
                    }
                    break;
                case "Speaker":
                    if (isset($this->_active_search->speaker_id)&&isset($this->_active_search->speaker_name)&&$this->_active_search->speaker_name)
                    {
                        $classes.="active "; 
                        $active = true;
                        $filter_val = $this->_active_search->speaker_name;
                    }
                    break;
            }
            if ($active) $active_count++;
            $base_url = $this->_sermonz_controller->base_url;
            $clear_filter_url = $this->_sermonz_controller->clear_filter_and_build_url(str_replace("/filter/", "", $filter));
            $filter_html .= sprintf(
                '<div class="%s" ><a href="%s" id="%s" title="%s">%s</a> %s</div>',
                $classes,
                esc_attr_x($base_url.$filter, "sermonz href for filter"),
                esc_attr_x($filter, "sermonz title for filter"),
                strtolower($label),
                ($active)?esc_html($label).": ".esc_html($filter_val):"Filter by ".esc_html($label),
                ($active)?sprintf('<a href="%s" class="sermonz_clear_filter" title="%s"><span class="dashicons dashicons-no-alt"></span></a>', $clear_filter_url, esc_attr_x("Clear filter", "sermonz clear filter title")):""
            );
        }
        $sermonz_content .= sprintf(
            '<div class="sermonz_search_filters_wrap">
            <a class="sermonz_show_search %s"><span class="dashicons-before dashicons-search"></span><span class="screen-reader-text">Show Search</span></a>
            <div class="sermon_search_filters_wrap_a">%s</div></div>',
            $keyword_search_active,
            $filter_html
        );

        $sermonz_content .= "</form>";
        $this->_content .= $sermonz_content;
    }

    private function _load_sermons_body()
    {
        $tmp_search = clone $this->_active_search;
        $params = [
            'order_by' => get_query_var('order_by'),
            'order_direction' => get_query_var('order_direction')
        ];

        $url = "/sermons";
        
        $parameters = array();
        
        if ($this->_active_search->keywords) 
        {
            $parameters["search"]=$tmp_search->keywords;
        }
        if ($this->_active_search->series_id) 
        {
            $parameters["series_id"]=$tmp_search->series_id;
        }
        if ($this->_active_search->speaker_id) 
        {
            $parameters["speaker_id"]=$tmp_search->speaker_id;
        }
        if ($this->_active_search->book) 
        {
            $parameters["book"]=$tmp_search->book;
        }

        $parameters["page_number"]=(int)$tmp_search->page_number?(int)$tmp_search->page_number:1;
        $parameters["page_size"]=(int)$tmp_search->page_size>0?(int)$tmp_search->page_size:12;
 
        $result = $this->_sermonz_controller->call_api($url, $parameters);
        if ($result instanceof SermonzError)
        {
            $this->_content .= sprintf('<p class="error">%s</p>', $result->error);
            return;
        }

        $sermons = json_decode($result);
        if (!$sermons || !@count($sermons)) {
            $this->_content .= sprintf('<p>No talks found</p>');
            return;
        }
        
        $this->_content .= sprintf('<p class="sermonz_row_count">%s talk%s</p>', $sermons->row_count, $sermons->row_count!=1?"s":"");

        if ($sermons->page_number>1)
        {
            $page_number = $sermons->page_number-1;
            $more = $this->_sermonz_controller->build_url(array("page_number"=>$page_number?$page_number:1));
            $this->_content .= sprintf('<div class="sermonz_more_wrap"><a href="%s" class="sermonz_previous">Load Previous</a></div>', $more);
        }
        $this->_content .= '<div class="sermons_series_pages"><div class="sermonz_series_list">';
        foreach ($sermons->sermons as $sermon) 
        {
            $sermon_url = sprintf(
                '%s/talk/%s',
                $this->_sermonz_controller->base_url,
                $sermon->sermon_id
            );
            
            $date = date_format(date_create($sermon->sermon_date), "d M Y");
            
            $this->_content .= sprintf
            (
                '<div class="sermonz_sermon_row">
                    <a href="%s"><img src="%s" border="0" alt="%s" /></a>
                    <p class="sermonz_metadata sermonz_sermon_name"><b><a href="%s">%s</a></b></p>
                    <p class="sermonz_metadata sermonz_sermon_series"><a href="%s">%s</a></p>
                    <p class="sermonz_metadata sermonz_sermon_speaker "><a href="%s">%s</a></p>
                    <p class="sermonz_metadata sermonz_sermon_date secondary"><a href="%s">%s | %s</a></p>
                </div>',
                $sermon_url,
                esc_attr($sermon->series_thumb),
                esc_attr($sermon->sermon_title),
                $sermon_url,
                esc_html($sermon->sermon_title),
                $sermon_url,
                esc_html($sermon->series_name),
                $sermon_url,
                esc_html($sermon->speaker_name),
                $sermon_url,
                esc_html($date),
                esc_html($sermon->passage)
            );
        }

        $this->_content .= sprintf('<div class="sermonz_more_wrap">');
        if ($sermons->row_count > ($sermons->page_number*$sermons->page_size))
        {
            $page_number = $sermons->page_number+1;
            $more = $this->_sermonz_controller->build_url(array("page_number"=>$page_number));
            $this->_content .= sprintf('<a href="%s" class="sermonz_more">Load More</a>', $more);
        }
        $this->_content .= '</div>'; 
        $this->_content .= '</div></div><div class="sermonz_loading" style="display:none"><center>Loading...</center></div>'; 

    }

    private function _load_sermon_item($id)
    {
        $url = "/series/".(int)$id;
        $result = $this->call_api($url);
        if ($result instanceof SermonzError)
        {
            $this->_content .= sprintf('<p class="error">%s</p>', $result->error);
            return;
        }
        $series = json_decode($result);
        if (!$series) {
            $this->_content .= sprintf('<p class="error">Error: cannot load series with id, %s</p>', (int)$id);
            return;
        }

        $this->title = $series->name;
        $this->_content = "<img src=\"".html_entities($series->series_thumb)."\" width=\"30\" />";
    }
}