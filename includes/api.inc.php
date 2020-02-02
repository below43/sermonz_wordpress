<?php

add_action( 'pre_get_posts', 'sermonz_start' ); 

global $sermonz_api;
function sermonz_start()
{
    global $sermonz_api;
    $sermonz_hostname = get_option('sermonz_hostname');
    $sermonz_api = new SermonzApi($sermonz_hostname);
}

class SermonzApi
{
    public $hostname;
    public $route;
    public $argument;

    public $content = "";
    public $title = "Sermon Library";
    public $show_back = false;
    public $active_search = null;
    public $filter_name = "";

    public function __construct($hostname)
    {
        $this->hostname = "http://".str_replace("http://", "", str_replace("https://", "", rtrim($hostname, "/")));
        $this->initialise_page();
        
    }

    private function initialise_page()
    {
        $this->route = get_query_var('sermonz_route');
        $this->argument = get_query_var('sermonz_argument');
        $this->content .= "<br/><br/>route: ".$this->route."; argument: ".$this->argument;
        switch ($this->route) {
            case "search":
                // <li>search (optional): keyword search</li>
				// <li>speaker (optional): search for specific speaker</li>
				// <li>series_id (optional): search for specific series id</li>
				// <li>order_by (optional): sermon_date</li>
				// <li>order_direction (optional): asc, desc</li> 
                break;
            case "filter":
                $this->title = "Apply Filter";
                switch ($this->argument)
                {
                    case "series":
                        $this->load_series();
                        $this->filter_name = "Series";
                        break;
                    case "speakers":
                        $this->load_speakers();
                        $this->filter_name = "Speaker";
                        break;
                    case "books":
                        $this->load_books();
                        $this->filter_name = "Book of Bible";
                        break;
                }
                break;
            case "sermon":
                break;
        }

        $search = null;
        if (isset($_SESSION['sermonz_active_search']))
        {
            $search = unserialize($_SESSION['sermonz_active_search']);
        }
        
        if (!$search)
        {
            $search = new SermonzSearch();
        }

    }

    private function load_books()
    {

    }

    private function load_speakers()
    {

    }

    private function load_series()
    {
        // $this->route = get_query_var('sermonz_route');
        // $this->argument = get_query_var('sermonz_argument');
        $params = [
            order_by => get_query_var('order_by'),
            order_direction => get_query_var('order_direction')
        ];

        $url = "/series/";
        $result = $this->call_api($url, $params);
        $this->content .= $result;
        $series = json_decode($result);
        if (!$series || !count($series)) {
            $this->content .= sprintf('<p class="error">Error: cannot load series</p>');
            return;
        }

        $this->title = "Series";
        foreach ($series->series as $series_item) 
        {
            $this->content .= "<li>".json_encode($series_item);
        }
    }

    private function load_series_item($id)
    {
        $url = "/series/".(int)$id;
        $result = $this->call_api($url);
        $series = json_decode($result);
        if (!$series) {
            $this->content .= sprintf('<p class="error">Error: cannot load series with id, %s</p>', (int)$id);
            return;
        }

        $this->title = $series->name;
        $this->content = "<img src=\"".html_entities($series->series_thumb)."\" width=\"30\" />";
    }

    private function call_api($endpoint, $data = false)
    {
        if (!function_exists('curl_version'))
        {
            error_log("Error: curl not defined");
            return "Error: curl not defined";
        }
        $full_url = $this__hostname."/".ltrim($endpoint, "/");

        $curl = curl_init();

        $url = sprintf("%s?%s", $url, http_build_query($data));
        
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($curl);

        curl_close($curl);

        return $result;
    }

}

class SermonzSearch
{
    public function __construct()
    {

    }
    public $keywords="";
    public $series_id="";
    public $speaker="";
    public $book="";
}