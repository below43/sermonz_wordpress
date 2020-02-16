<?php

require('sermonz.view.search.php');
require('sermonz.view.filter.php');
require('sermonz.view.sermon.php');

add_action('init', 'sermonz_start_session', 1);
function sermonz_start_session() {
    if(!session_id()) {
        session_start();
    }
}


// add_action( 'pre_get_posts', 'sermonz_start' ); 

function sermonz_start()
{
    if (get_the_ID() == get_option('sermonz_page')) 
    {
        global $sermonz_controller;
        $sermonz_api_url = get_option('sermonz_api_url');
        $base_url = sermonz_get_page_uri();
        $sermonz_controller = new SermonzController($sermonz_api_url, $base_url);
    }
}

class SermonzController
{
    public $hostname;
    public $base_url;
    public $route;
    public $argument;

    public $content = "";
    public $title = "";
    public $show_back = false;
    public $active_search = null;
    
    public $debug = false; //true;

    public $testaments = array
    (
        "Old Testament" => array
        (
            'Genesis','Exodus','Leviticus','Numbers','Deuteronomy','Joshua','Judges','Ruth','1 Samuel','2 Samuel','1 Kings','2 Kings','1 Chronicles','2 Chronicles','Ezra','Nehemiah','Esther','Job','Psalms','Proverbs','Ecclesiastes','Song of Solomon','Isaiah','Jeremiah','Lamentations','Ezekiel','Daniel','Hosea','Joel','Amos','Obadiah','Jonah','Micah','Nahum','Habakkuk','Zephaniah','Haggai','Zechariah','Malachi'
        ),
        "New Testament" => array
        (
            'Matthew','Mark','Luke','John','Acts','Romans','1 Corinthians','2 Corinthians','Galatians','Ephesians','Philippians','Colossians','1 Thessalonians','2 Thessalonians','1 Timothy','2 Timothy','Titus','Philemon','Hebrew','James','1 Peter','2 Peter','1 John','2 John','3 John','Jude','Revelation'
        )
    );

    public function __construct($hostname, $base_url)
    {
        $this->hostname = sprintf("https://%s", str_replace("http://", "", str_replace("https://", "", rtrim($hostname, "/"))));
        $this->base_url = $base_url;
        if (get_the_ID() == get_option('sermonz_page')) 
        {
            $this->_initialise_page();
        }
    }

    private function _initialise_page()
    {
        $this->route = get_query_var('sermonz_route');
        $this->argument = get_query_var('sermonz_argument');
        
        $this->_initialise_search();

        if ($this->debug) $this->content .= sprintf('<br/><pre>route: %s; argument: %s</pre>', $this->route, $this->argument);
        switch ($this->route) {
            case "filter":
                $this->_load_filter($this->argument);
                break;
            case "sermon":
                $this->_load_sermon($this->argument);
                break;
            default:
                $this->_load_sermons();
                break;
        }
    }

    private function _initialise_search()
    {
        $search = null;

        //if we have an active route, then use session, otherwise use GET only
        if ($this->route&&isset($_SESSION['sermonz_active_search']))
        {
            $search = unserialize($_SESSION['sermonz_active_search']);
        }
      
        if (!$search)
        {
            $search = new SermonzSearch();
        }

        if ($keywords = get_query_var('keywords')) {
            $search->keywords = $keywords;
        }
        if ($speaker =  get_query_var('speaker')) {
            $search->speaker = $speaker;
            $this->title = sprintf("Talks by %s",  $speaker);
        }
        if ($book =  get_query_var('book')) {
            $search->book = 
            (
                in_array($book, $this->testaments["Old Testament"]) ||
                in_array($book, $this->testaments["New Testament"])
            )?$book:"";
            $this->title = sprintf("Talks on %s", $book);
        }
        if ($series_id =  get_query_var('series_id')) {
            $search->series_id = (int)$series_id>0?(int)$series_id:null;
            $series = $this->_load_series_item($search->series_id);
            $this->series_name = $search->series_name = $series->series_name;
            $this->title = sprintf("%s", $this->series_name);
        }
        if ($page_number = get_query_var('page_number')) 
        {
            $search->page_number = $page_number>0?$page_number:1;
        }
        if ($page_size =  get_query_var('page_size'))
        {
            if ($page_size&&$page_size>0&&$page_size<101) 
            {
                $search->page_size = $page_size;
            }
            else 
            {
                $search->page_size = 10;
            }
        }

        $this->active_search = $search;
        $_SESSION['sermonz_active_search'] = serialize($search);
    }

    private function _load_filter($filter_arg)
    {
        $this->title = "Apply Filter";
        $filter = new SermonzViewFilter($this, $filter_arg);
        $this->content .= $filter->get_content();
        $this->title = $filter->get_title();
    }
    
    private function _load_sermons()
    {
        $sermons = new SermonzViewSearch($this);
        $this->content .= $sermons->get_content();
    }

    private function _load_sermon($filter_arg)
    {
        $sermon = new SermonzViewSermon($this, $filter_arg);
        $this->title = $sermon->get_title();
        $this->content .= $sermon->get_content();
    }

    private function _load_series_item($id)
    {
        $url = "/series/".(int)$id;
        $result = $this->call_api($url);
        if ($result instanceof SermonzError) 
        {
            $this->content .= sprintf('<p class="error">%s</p>', $result->error);
            return;
        }
        $series = json_decode($result);
        if (!$series) {
            $this->content .= sprintf('<p class="error">Error: cannot load series with id, %s</p>', (int)$id);
            return;
        } 
        return $series;
    }

    public function call_api($endpoint, $data = array())
    {
        if (!function_exists('curl_version'))
        {
            error_log("Error: curl not defined");
            return "Error: curl not defined";
        }
        $curl = curl_init();
        $full_url = $this->hostname.$endpoint;
        $url = sprintf('%s?%s', $full_url, http_build_query($data));   
        
        if ($this->debug) $this->content .= sprintf('<br/><pre>%s</pre>', $url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if($httpCode == 404) {
            $result = new SermonzError(sprintf("URL, %s, not found!", $url),$httpCode);
        }
        else if ($httpCode != 200) 
        {
            $result = new SermonzError(sprintf("An error occurred: %s", $result),$httpCode);
        }
        curl_close($curl);

        return $result;
    }

    public function build_url($params=array())
    {
        $tmp_search = clone $this->active_search;
        foreach ($params as $param=>$value)
        {
            if (property_exists('SermonzSearch', $param))
            { 
                $tmp_search->{$param} = $value;
            }
        }
        
        $url = sprintf
        (
            '%s?keywords=%s&series_id=%s&speaker=%s&book=%s&page_number=%s&page_size=%s',
            $this->base_url,
            urlencode($tmp_search->keywords),
            urlencode($tmp_search->series_id>0?$tmp_search->series_id:null),
            urlencode($tmp_search->speaker),
            urlencode
            (
                (
                    in_array($tmp_search->book, $this->testaments["Old Testament"]) ||
                    in_array($tmp_search->book, $this->testaments["New Testament"])
                )
                ?$tmp_search->book:null
            ),
            (int)$tmp_search->page_number>0?(int)$tmp_search->page_number:1,
            (int)$tmp_search->page_size>0&&(int)$tmp_search->page_size<100?(int)$tmp_search->page_size:10
        );
        return $url;
    }
    
    public function clear_filter_and_build_url($argument = null)
    {
        if (!$argument) $argument = $this->_sermonz_controller->argument;
        switch ($argument)
        {
            case "series":
                if (!$this->active_search->series_id) return false;
                return $this->build_url(array("page_number"=>1, "series_id"=>null));
                break;
            case "speakers":
                if (!$this->active_search->speaker) return false;
                return $this->build_url(array("page_number"=>1, "speaker"=>""));
                break;
            case "books":
                if (!$this->active_search->book) return false;
                return $this->build_url(array("page_number"=>1, "book"=>""));
                break;
        }
        return false;
    }
    
    public function single_filter_url($argument, $filter_value)
    {
        if (!$argument) $argument = $this->_sermonz_controller->argument;
        switch ($argument)
        {
            case "series":
                return $this->build_url(array("page_number"=>1, "series_id"=>$filter_value, "speaker"=>"", "book"=>""));
                break;
            case "speakers":
                return $this->build_url(array("page_number"=>1, "series_id"=>"", "speaker"=>$filter_value, "book"=>""));
                break;
            case "books":
                return $this->build_url(array("page_number"=>1, "series_id"=>"", "speaker"=>"", "book"=>$filter_value));
                break;
            case "keywords":
                return $this->build_url(array("page_number"=>1, "series_id"=>"", "speaker"=>"", "book"=>""));
                break;
        }
        return false;
    }
}

class SermonzError
{
    public function __construct($error, $code)
    {
        $this->error = $error;
        $this->code = $code;
    }
    public $error="";
    public $code=0;
}

class SermonzSearch
{
    public function __construct()
    {

    }
    public $keywords="";
    public $series_id=null;
    public $speaker="";
    public $book="";
    public $page_number=1;
    public $page_size=10;
}
