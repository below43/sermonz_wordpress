<?php

class SermonzViewFilter
{
    private $_sermonz_controller;
    private $_filter;
    private $_content = "";
    private $_title = "";
    private $_active_search;

    public function __construct($sermonz_controller, $filter)
    {
        $this->_sermonz_controller = $sermonz_controller;
        $this->_filter = $filter;
        $this->_active_search = $this->_sermonz_controller->active_search;
        $this->_load_filter_head();
        $this->_load_filter_body();
    }

    public function get_content() 
    {
        return $this->_content;
    }

    public function get_title() 
    {
        return $this->_title;
    }

    private function _load_filter_head()
    {
        $base_url = $this->_sermonz_controller->build_url(array("page"=>1));
        $sermonz_content .= '<div class="sermonz_filter_inner_head_wrap">';
        $sermonz_content .= sprintf(
            '<div class="sermonz_filter_inner_head"><a href="%s">%s</a></div>',
            $base_url,
            '<span class="dashicons-before dashicons-arrow-left-alt2"></span> Go Back'
        );

        $clear_url = $this->_sermonz_controller->clear_filter_and_build_url($this->_filter);
        if ($clear_url)
        {
            $sermonz_content .= sprintf(
                '<div class="sermonz_filter_inner_head right"><a href="%s">%s</a></div>',
                $clear_url,
                '<span class="dashicons-before dashicons-no"></span> Clear Filter'
            );
        }
        $sermonz_content .= '</div>';
        $this->_content .= $sermonz_content;
    }

    private function _load_filter_body()
    {
        
        switch ($this->_filter)
        {
            case "series":
                $this->_load_series();
                $this->_title = "Filter By Series";
                break;
            case "speakers":
                $this->_load_speakers();
                $this->_title = "Filter By Speaker";
                break;
            case "books":
                $this->_load_books();
                $this->_title = "Filter By Book";
                break;
        }
    }
    
    private function _load_speakers()
    {
        $params = [
            order_by => get_query_var('order_by'),
            order_direction => get_query_var('order_direction')
        ];

        $url = "/speakers/";
        
        $result = $this->_sermonz_controller->call_api($url, $params);
        if ($result instanceof SermonzError)
        {
            $this->_content .= sprintf('<p class="error">%s</p>', $result->error);
            return;
        }
        if ($this->debug) $this->_content .= sprintf('<br/><pre>result: %s</pre>', $result);

        $speakers = json_decode($result);

        if ($this->debug) $this->_content .= sprintf('<br/><pre>%s</pre>', $speakers);
        if (!$speakers || !count($speakers)) {
            $this->_content .= sprintf('<p>No speakers found</p>');
            return;
        }

        $this->title = "Speakers";
        $this->_content .= '<div class="sermonz_filter_list"><br/>';
        foreach ($speakers as $speaker) 
        {
            $speaker_url = $this->_sermonz_controller->build_url(array(
                "speaker"=>$speaker,
                "page"=>1
            ));
            $this->_content .= sprintf
            (
                '<div class="sermonz_filter_row">
                    <p class="sermonz_filter_name"><b><a href="%s" class="sermonz_filter_href %s">%s</a></b></p>
                </div>',
                $speaker_url,
                $speaker==$this->_active_search->speaker?" active":"",
                esc_html($speaker)
            );        
        }
        $this->_content .= '</div>';
    }

    private function _load_books()
    {
        $params = [
            order_by => get_query_var('order_by'),
            order_direction => get_query_var('order_direction')
        ];

        $url = "/books/";
        
        $result = $this->_sermonz_controller->call_api($url, $params);
        if ($result instanceof SermonzError)
        {
            $this->_content .= sprintf('<p class="error">%s</p>', $result->error);
            return;
        }
        if ($this->debug) $this->_content .= sprintf('<br/><pre>result: %s</pre>', $result);

        $books = json_decode($result);

        if ($this->debug) $this->_content .= sprintf('<br/><pre>%s</pre>', $books);
        if (!$books || !count($books)) {
            $this->_content .= sprintf('<p>No books found</p>');
            return;
        }

        $this->title = "Books";
        $this->_content .= '<div class="sermonz_filter_list">';
        foreach ($this->_sermonz_controller->testaments as $testament=>$testament_books) 
        {
            $this->_content.=sprintf('<h5>%s</h5>', $testament);
            
            foreach ($testament_books as $book) 
            {
                if (in_array($book, $books))
                {
                    $series_url = $this->_sermonz_controller->build_url(array(
                        "book"=>$book,
                        "page"=>1
                    ));
                    
                    $this->_content .= sprintf
                    (
                        '<div class="sermonz_filter_row">
                            <p class="sermonz_filter_name"><b><a href="%s" class="sermonz_filter_href %s">%s</a></b></p>
                        </div>',
                        $series_url,
                        $book==$this->_active_search->book?" active":"",
                        esc_html($book)
                    );
                }
                else 
                {
                    $this->_content .= sprintf
                    (
                        '<div class="sermonz_filter_row">
                            <p class="sermonz_filter_name disabled">%s</p>
                        </div>',
                        esc_html($book)
                    );
                }
            }
        }
        $this->_content .= '</div>';
    }

    private function _load_series()
    {
        // $this->route = get_query_var('sermonz_route');
        $page_number = get_query_var('page_number');
        if (!$page_number||$page_number<1) $page_number = 1;
        $params = [
            order_by => get_query_var('order_by'),
            order_direction => get_query_var('order_direction'),
            page_size => '100',
            page_number => $page_number
        ];

        $url = "/series/";
        
        $result = $this->_sermonz_controller->call_api($url, $params);
        if ($result instanceof SermonzError)
        {
            $this->_content .= sprintf('<p class="error">%s</p>', $result->error);
            return;
        }

        $series = json_decode($result);
        if (!$series || !count($series)) {
            $this->_content .= sprintf('<p>No series found</p>');
            return;
        }

        $this->title = "Series";
        $this->_content .= '<div class="sermonz_series_list">';
        foreach ($series->series as $series_item) 
        {
            $series_url = $this->_sermonz_controller->build_url
            (
                array
                (
                    "series_id"=>(int)$series_item->series_id,
                    "series_name"=>$series_item->series_name,
                    "page_number"=>1
                )
            );
            $from_date = date_format(date_create($series_item->first_sermon_date), "M Y");
            $to_date = date_format(date_create($series_item->last_sermon_date), "M Y");
            $date_range = $from_date;
            if ($from_date!=$to_date)
            {
                $date_range = sprintf("%s - %s", $from_date, $to_date);
            }
            $this->_content .= sprintf
            (
                '<div class="sermonz_series_row %s">
                    <a href="%s"><img src="%s" border="0" alt="%s" /></a>
                    <p class="sermonz_series_name"><b><a href="%s">%s</a></b></p>
                    <p class="sermonz_date_range">%s</p>
                </div>',
                $series_item->series_id==$this->_active_search->series_id?" active":"",
                $series_url,
                esc_attr($series_item->series_thumb),
                esc_html($series_item->series_name),
                $series_url,
                esc_html($series_item->series_name),
                esc_html($date_range)
            );
        }


        $this->_content .= sprintf('<div class="sermonz_more_wrap">');

        $base_url = sermonz_get_page_uri();
        
        if ($series->page_number>1)
        {
            $page_number = $sermons->page_number-1;

            $more = sprintf('%s/filter/series/?page_number=%s', $base_url, $page_number);
            $this->_content .= sprintf('<a href="%s" class="sermonz_previous">Load Previous</a>', $more);
        }
        if ($series->row_count > ($series->page_number*$series->page_size))
        {
            $page_number = $series->page_number+1;
            $more = sprintf('%s/filter/series/?page_number=%s', $base_url, $page_number);
            $this->_content .= sprintf('<a href="%s" class="sermonz_more">Load More</a>', $more);
        }
        $this->_content .= '</div>';
        $this->_content .= '</div>';
    }
}