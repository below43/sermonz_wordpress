<?php

class SermonzViewSermon
{
    private $_sermonz_controller;
    private $_sermon_id;
    private $_content = "";
    private $_title = "Talk";
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
            global $wp_query;
            $wp_query->set_404();
            status_header( 404 );
            $this->_content .= sprintf('<p>No talk found</p>'.$result);
            return;
        }

        //generate the output

        $base_url = $this->_sermonz_controller->build_url();
        $url_base = get_site_url().$this->_sermonz_controller->base_url;
        $back = (strpos(wp_get_referer(), $url_base)!==false)?'Back to':'Browse'; //dashicons-arrow-left-alt2":"dashicons-screenoptions";
        $sermonz_header_link .= '<div class="sermonz_sermon_action_links">';
        $sermonz_header_link .= sprintf(
            '<div class="sermonz_back_link"><a href="%s"><span class="dashicons-before dashicons-arrow-left-alt2"></span> &nbsp;%s %s</a></div>',
            $base_url,
            $back,
            'talks library'
        ); 
        $current_url="https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $sermonz_header_link .= sprintf(
            '<div class="sermonz_download_link"><a href="%s"><span class="dashicons dashicons-download"></span> &nbsp;%s</a></div>',
            esc_attr($sermon->sermon_file), 
            'Download this talk'
        ); 
        $sermonz_header_link .= sprintf(
            '<div class="sermonz_share_link"><a href="%s"><span class="dashicons dashicons-share-alt2"></span> &nbsp;%s</a></div>',
            $current_url,
            'Share this talk'
        ); 
        $sermonz_header_link .= '</div>';


        // $this->_title = esc_html($sermon->sermon_title);
        $this->_title = sprintf("%s | %s",
            esc_html($sermon->passage),
            esc_html($sermon->speaker_name)
        );
        $sermon_url = sprintf(
            '%s/talk/%s',
            $this->_sermonz_controller->base_url,
            $sermon->sermon_id
        );
        
        $series_url = $this->_sermonz_controller->single_filter_url("series", $sermon->series_id);
        $speaker_url = $this->_sermonz_controller->single_filter_url("speakers", $sermon->speaker_id);
        $date = date_format(date_create($sermon->sermon_date), "d F Y");
        
        $passage_parts = explode(" ", $sermon->passage);
        
        if (in_array($passage_parts[0], $this->_sermonz_controller->testaments["Old Testament"])||
            in_array($passage_parts[0], $this->_sermonz_controller->testaments["New Testament"])) 
        {
            $passage = sprintf('<a href="https://www.biblegateway.com/passage/?search=%s&interface=print" target="_blank">%s &nbsp;<span class="dashicons dashicons-external"></span></a>',
                urlencode($sermon->passage),
                esc_html($sermon->passage)
            );
        }

        $this->_content .= str_replace("sermonz_sermon_action_links", "sermonz_sermon_action_links top",$sermonz_header_link);
        $this->_content .= sprintf
        (
            '<div class="sermonz_sermon_page">
                <h2 class="sermonz_metadata sermonz_metadata_title">%s</h2>
                <div class="sermonz_sermon_series_thumb"><img src="%s" border="0" alt="%s" /></div>
                <div class="sermonz_metadata_wrap">
                    <p class="sermonz_metadata sermonz_metadata_date"><span class="label">Date</span> %s</p>
                    <p class="sermonz_metadata sermonz_metadata_passage"><span class="label">Passage</span> %s</p>
                    <p class="sermonz_metadata sermonz_metadata_speaker"><span class="label">Speaker</span> <a href="%s">%s &nbsp;<span class="dashicons dashicons-screenoptions"></span></a></p>
                    <p class="sermonz_metadata sermonz_metadata_series"><span class="label">Series</span> <a href="%s">%s &nbsp;<span class="dashicons dashicons-screenoptions"></span></a></p>
                </div>
            </div>',
            esc_html($sermon->sermon_title),
            esc_attr($sermon->series_image),
            esc_html($sermon->series_name),
            esc_html($date),
            $passage,
            $speaker_url,
            esc_html($sermon->speaker_name),
            $series_url,
            esc_html($sermon->series_name)
        );
        $this->_content .= sprintf
        (
            '<div class="sermonz_sermon_player">
                <figure class="wp-block-audio"><audio controls="" src="%s" preload="metadata"></audio></figure>
            </div>',
            esc_attr($sermon->sermon_file)
        );
       
        $this->_content .= str_replace("sermonz_sermon_action_links", "sermonz_sermon_action_links bottom",$sermonz_header_link); 
    }
}