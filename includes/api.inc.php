<?php

add_action( 'pre_get_posts', 'sermonz_start' ); 

global $sermonz_api_url;
function sermonz_start()
{
    global $sermonz_api;
    $sermonz_api_url = get_option('sermonz_api_url');

    $base_url = sermonz_get_page_uri();
    $sermonz_api = new SermonzApi($sermonz_api_url, $base_url);
}

class SermonzApi
{
    public $hostname;
    public $base_url;
    public $route;
    public $argument;

    public $content = "";
    public $title = "Sermon Library";
    public $show_back = false;
    public $active_search = null;
    public $filter_name = "";
    // public $debug = true;

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
        $this->initialise_page();
        
    }

    private function initialise_page()
    {
        $this->route = get_query_var('sermonz_route');
        $this->argument = get_query_var('sermonz_argument');
        
        $this->initialise_search();

        if ($this->debug) $this->content .= sprintf('<br/><pre>route: %s; argument: %s</pre>', $this->route, $this->argument);
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
                        $this->title = "Filter By Series";
                        $this->filter_name = "Series";
                        break;
                    case "speakers":
                        $this->load_speakers();
                        $this->title = "Filter By Speaker";
                        $this->filter_name = "Speaker";
                        break;
                    case "books":
                        $this->load_books();
                        $this->title = "Filter By Book";
                        $this->filter_name = "Book of Bible";
                        break;
                }
                break;
            case "sermon":
            default:
                if (!$this->series_name)
                {
                    $this->title = "Sermons";
                }
                $this->load_sermons();
                break;
        }
    }

    private function initialise_search()
    {

        $search = null;
        session_start();
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
        if ($series_id =  get_query_var('series_id')) {
            $search->series_id = (int)$series_id>0?(int)$series_id:null;
            $series = $this->load_series_item($search->series_id);
            $this->series_name = $search->series_name = $series->series_name;
        }
        if ($book =  get_query_var('book')) {
            $search->book = 
            (
                in_array($book, $this->testaments["Old Testament"]) ||
                in_array($book, $this->testaments["New Testament"])
            )?$book:"";
        }
        if ($speaker =  get_query_var('speaker')) {
            $search->speaker = $speaker;
        }
        if ($page = get_query_var('page')) 
        {
            $search->page_number = $page>0?$page:1;
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

    private function load_speakers()
    {
        $params = [
            order_by => get_query_var('order_by'),
            order_direction => get_query_var('order_direction')
        ];

        $url = "/speakers/";
        
        $result = $this->call_api($url, $params);
        if ($result instanceof SermonzError)
        {
            $this->content .= sprintf('<p class="error">%s</p>', $result->error);
            return;
        }
        if ($this->debug) $this->content .= sprintf('<br/><pre>result: %s</pre>', $result);

        $speakers = json_decode($result);

        if ($this->debug) $this->content .= sprintf('<br/><pre>%s</pre>', $speakers);
        if (!$speakers || !count($speakers)) {
            $this->content .= sprintf('<p>No speakers found</p>');
            return;
        }

        $this->title = "Speakers";
        $this->content .= '<div class="sermonz_filter_list"><br/>';
        foreach ($speakers as $speaker) 
        {
            $speaker_url = $this->build_url(array(
                "speaker"=>$speaker,
                "page"=>1
            ));
            $this->content .= sprintf
            (
                '<div class="sermonz_filter_row">
                    <p class="sermonz_filter_name"><b><a href="%s" class="sermonz_filter_href %s">%s</a></b></p>
                </div>',
                $speaker_url,
                $speaker==$this->active_search->speaker?" active":"",
                esc_html($speaker)
            );        
        }
        $this->content .= '</div>';
    }

    private function load_books()
    {
        $params = [
            order_by => get_query_var('order_by'),
            order_direction => get_query_var('order_direction')
        ];

        $url = "/books/";
        
        $result = $this->call_api($url, $params);
        if ($result instanceof SermonzError)
        {
            $this->content .= sprintf('<p class="error">%s</p>', $result->error);
            return;
        }
        if ($this->debug) $this->content .= sprintf('<br/><pre>result: %s</pre>', $result);

        $books = json_decode($result);

        if ($this->debug) $this->content .= sprintf('<br/><pre>%s</pre>', $books);
        if (!$books || !count($books)) {
            $this->content .= sprintf('<p>No books found</p>');
            return;
        }

        $this->title = "Books";
        $this->content .= '<div class="sermonz_filter_list">';
        foreach ($this->testaments as $testament=>$testament_books) 
        {
            $this->content.=sprintf('<h5>%s</h5>', $testament);
            
            foreach ($testament_books as $book) 
            {
                if (in_array($book, $books))
                {
                    $series_url = $this->build_url(array(
                        "book"=>$book,
                        "page"=>1
                    ));
                    
                    $this->content .= sprintf
                    (
                        '<div class="sermonz_filter_row">
                            <p class="sermonz_filter_name"><b><a href="%s" class="sermonz_filter_href %s">%s</a></b></p>
                        </div>',
                        $series_url,
                        $book==$this->active_search->book?" active":"",
                        esc_html($book)
                    );
                }
                else 
                {
                    $this->content .= sprintf
                    (
                        '<div class="sermonz_filter_row">
                            <p class="sermonz_filter_name disabled">%s</p>
                        </div>',
                        esc_html($book)
                    );
                }
            }
        }
        $this->content .= '</div>';
    }

    private function load_series()
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
        
        $result = $this->call_api($url, $params);
        if ($result instanceof SermonzError)
        {
            $this->content .= sprintf('<p class="error">%s</p>', $result->error);
            return;
        }

        $series = json_decode($result);
        if (!$series || !count($series)) {
            $this->content .= sprintf('<p>No series found</p>');
            return;
        }

        $this->title = "Series";
        $this->content .= '<div class="sermonz_series_list">';
        foreach ($series->series as $series_item) 
        {
            $series_url = $this->build_url
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
            $this->content .= sprintf
            (
                '<div class="sermonz_series_row %s">
                    <a href="%s"><img src="%s" border="0" alt="%s" /></a>
                    <p class="sermonz_series_name"><b><a href="%s">%s</a></b></p>
                    <p class="sermonz_date_range">%s</p>
                </div>',
                $series_item->series_id==$this->active_search->series_id?" active":"",
                $series_url,
                esc_attr($series_item->series_thumb),
                esc_html($series_item->series_name),
                $series_url,
                esc_html($series_item->series_name),
                esc_html($date_range)
            );
        }


        $this->content .= sprintf('<div class="sermonz_more_wrap">');

        $base_url = sermonz_get_page_uri();
        
        if ($series->page_number>1)
        {
            $page_number = $sermons->page_number-1;

            $more = sprintf('%s/filter/series/?page_number=%s', $base_url, $page_number);
            $this->content .= sprintf('<a href="%s" class="sermonz_previous">Load Previous</a>', $more);
        }
        if ($series->row_count > ($series->page_number*$series->page_size))
        {
            $page_number = $series->page_number+1;
            $more = sprintf('%s/filter/series/?page_number=%s', $base_url, $page_number);
            $this->content .= sprintf('<a href="%s" class="sermonz_more">Load More</a>', $more);
        }
        $this->content .= '</div>';
        $this->content .= '</div>';
    }

    private function load_series_item($id)
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

    private function call_api($endpoint, $data = array())
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

    public function clear_filter_and_build_url($argument = null)
    {
        if (!$argument) $argument = $this->argument;
        switch ($argument)
        {
            case "series":
                if (!$this->active_search->series_id) return false;
                return $this->build_url(array("series_id"=>null, "series_name"=>""));
                break;
            case "speakers":
                if (!$this->active_search->speaker) return false;
                return $this->build_url(array("speaker"=>""));
                break;
            case "books":
                if (!$this->active_search->book) return false;
                return $this->build_url(array("book"=>""));
                break;
        }
        return false;
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
            '%s?keywords=%s&series_id=%s&series_name=%s&speaker=%s&book=%s&page=%s&page_size=%s',
            $this->base_url,
            urlencode($tmp_search->keywords),
            urlencode($tmp_search->series_id>0?$tmp_search->series_id:null),
            urlencode($tmp_search->series_name),
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


    private function load_sermons()
    {
        $tmp_search = clone $this->active_search;
        $params = [
            order_by => get_query_var('order_by'),
            order_direction => get_query_var('order_direction')
        ];

        $url = "/sermons";
        
        $parameters = array();
        if ($this->active_search->keywords) 
        {
            $parameters["keywords"]=$tmp_search->keywords;
        }
        if ($this->active_search->series_id) 
        {
            $parameters["series_id"]=$tmp_search->series_id;
            if ($this->series_name) {
                // $series = $this->load_series_item($parameters["series_id"]);
                // $this->series_name = $series->series_name;
                $this->title = sprintf("%s", $this->series_name); //$series->series_name); 
            }
        }
        if ($this->active_search->speaker) 
        {
            $parameters["speaker"]=$tmp_search->speaker;
        }
        if ($this->active_search->book) 
        {
            $parameters["book"]=$tmp_search->book;
        }

        $parameters["page_number"]=(int)$tmp_search->page_number?(int)$tmp_search->page_number:1;
        $parameters["page_size"]=(int)$tmp_search->page_size>0?(int)$tmp_search->page_size:10;

        $result = $this->call_api($url, $parameters);
        if ($result instanceof SermonzError)
        {
            $this->content .= sprintf('<p class="error">%s</p>', $result->error);
            return;
        }

        $sermons = json_decode($result);
        if (!$sermons || !count($sermons)) {
            $this->content .= sprintf('<p>No sermons found</p>');
            return;
        }
        
        $this->content .= sprintf('<p class="sermonz_row_count">%s sermon%s</p>', $sermons->row_count, $sermons->row_count!=1?"s":"");
        $this->content .= '<div class="sermons_series_pages"><div class="sermonz_series_list">';
        foreach ($sermons->sermons as $sermon) 
        {
            $sermon_url = sprintf(
                '%s/sermon/%s',
                $this->base_url,
                $sermon->id
            );
            
            $date = date_format(date_create($sermon->sermon_date), "d M Y");
            
            $this->content .= sprintf
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
                esc_html($sermon->sermon_title),
                $sermon_url,
                esc_html($sermon->sermon_title),
                $sermon_url,
                esc_html($sermon->series_name),
                $sermon_url,
                esc_html($sermon->speaker),
                $sermon_url,
                esc_html($date),
                esc_html($sermon->passage)
            );
        }

        $this->content .= sprintf('<div class="sermonz_more_wrap">');

        if ($sermons->page_number>1)
        {
            $page_number = $sermons->page_number-1;
            $more = $this->build_url(array("page_number"=>$page_number?$page_number:1));
            $this->content .= sprintf('<a href="%s" class="sermonz_previous">Load Previous</a>', $more);
        }
        if ($sermons->row_count > ($sermons->page_number*$sermons->page_size))
        {
            $page_number = $sermons->page_number+1;
            $more = $this->build_url(array("page_number"=>$page_number));
            $this->content .= sprintf('<a href="%s" class="sermonz_more">Load More</a>', $more);
        }
        $this->content .= '</div>'; 
        $this->content .= '</div></div>'; 

    }

    private function load_sermon_item($id)
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

        $this->title = $series->name;
        $this->content = "<img src=\"".html_entities($series->series_thumb)."\" width=\"30\" />";
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
    public $series_name="";
    public $speaker="";
    public $book="";
    public $page_number=1;
    public $page_size=10;
}
