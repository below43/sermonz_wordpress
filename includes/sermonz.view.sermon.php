<?php

class SermonzViewSermon
{
    private $_sermonz_controller;
    private $_sermon_id;
    private $_content = "";
    private $_title = "Sermon";
    private $_active_search;

    public function __construct($sermonz_controller, $sermon_id)
    {
        $this->_sermonz_controller = $sermonz_controller;
        $this->_active_search = $this->_sermonz_controller->active_search;
        $this->_sermon_id = $sermon_id;
        $this->_load_head();
        $this->_load_body();
    }

    public function get_content() 
    {
        return $this->_content;
    }

    public function get_title() 
    {
        return $this->_title;
    }

    private function _load_head()
    {
    }

    private function _load_body()
    {
        $url = "/sermons/".$this->_sermon_id;
        //load the sermon
        $result = $this->_sermonz_controller->call_api($url, array());
        if ($result instanceof SermonzError)
        {
            $this->_content .= sprintf('<p class="error">%s</p>', $result->error);
            return;
        }
        $sermon = json_decode($result);
        if (!$sermon) {
            $this->_content .= sprintf('<p>No sermon found</p>');
            return;
        }

        //generate the output

        $base_url = $this->_sermonz_controller->build_url(array("page"=>1));
        $url_base = get_site_url().$this->_sermonz_controller->base_url;
        $back = (strpos(wp_get_referer(), $url_base)!==false)?'Back to':'Browse'; //dashicons-arrow-left-alt2":"dashicons-screenoptions";
        $sermonz_header_link .= sprintf(
            '<div class="sermonz_back_link"><a href="%s"><span class="dashicons-before dashicons-arrow-left-alt2"></span> &nbsp;%s %s</a></div>',
            $base_url,
            $back,
            'sermon library'
        ); 
        


        $this->_title = sprintf("%s | %s",
            esc_html($sermon->speaker),
            esc_html($sermon->passage)
        );
        $sermon_url = sprintf(
            '%s/sermon/%s',
            $this->_sermonz_controller->base_url,
            $sermon->sermon_id
        );
        
        $series_url = "";
        $speaker_url = "";
        $date = date_format(date_create($sermon->sermon_date), "d F Y");
        
        $passage_url = sprintf('https://www.biblegateway.com/passage/?search=%s&interface=print', urlencode($sermon->passage));


        $this->_content .= sprintf
        (
            '<div class="sermonz_sermon_page">
                <p class="sermonz_metadata sermonz_metadata_title">%s</p>
                <div class="sermonz_sermon_series_thumb"><img src="%s" border="0" alt="%s" /></div>
                <div class="sermonz_metadata_wrap">
                    <p class="sermonz_metadata sermonz_metadata_date"><span class="label">Date:</span> %s</p>
                    <p class="sermonz_metadata sermonz_metadata_passage"><span class="label">Passage:</span> <a href="%s" target="_blank">%s &nbsp;<span class="dashicons dashicons-external"></span></a></p>
                    <p class="sermonz_metadata sermonz_metadata_speaker"><span class="label">Speaker:</span> <a href="%s" target="_blank">%s &nbsp;<span class="dashicons dashicons-screenoptions"></span></a></p>
                    <p class="sermonz_metadata sermonz_metadata_series"><span class="label">Series:</span> <a href="%s" target="_blank">%s &nbsp;<span class="dashicons dashicons-screenoptions"></span></a></p>
                </div>
            </div>',
            esc_html($sermon->sermon_title),
            esc_attr($sermon->series_thumb),
            esc_html($sermon->series_name),
            esc_html($date),
            $passage_url,
            esc_html($sermon->passage),
            $speaker_url,
            esc_html($sermon->speaker),
            $series_url,
            esc_html($sermon->series_name)
        );
        $this->_content .= sprintf
        (
            '<div class="sermonz_sermon_player">
                <figure class="wp-block-audio"><audio controls="" src="%s?download_file=48644-Mark-9v30-50.mp3" preload="auto"></audio></figure>
            </div>',
            esc_attr($sermon->sermon_file)
        );
       
    
        $this->_content .= $sermonz_header_link;
    }
}