

jQuery(function() 
{
    initialiseMoreLinks();
});

function initialiseMoreLinks() 
{
    jQuery('a.sermonz_more').click(function() 
    {
        jQuery(".sermonz_more_wrap").remove();
        var href=jQuery(this).attr("href");
        //jQuery('.sermonz_series_list').load(href+' .sermonz_series_list');

        jQuery.get(href, function(data)
        {
            jQuery(data).find(".sermonz_series_list").appendTo(".sermons_series_pages");
            jQuery(".sermonz_previous").hide();
            initialiseMoreLinks();
        });
        
        return false;
    });
}