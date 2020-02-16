

jQuery(function() 
{
    initialiseMoreLinks();
    jQuery("a.sermonz_show_search").click(function() {
        if (jQuery(".sermonz_search_field_wrap").hasClass("active"))
        {
            jQuery('.sermonz_search_field_wrap').removeClass("active");
            jQuery('.sermonz_show_search').removeClass("active");
        }
        else 
        {
            jQuery('.sermonz_search_field_wrap').addClass("active");
            jQuery('.sermonz_show_search').addClass("active");
            jQuery('.sermonz_form .keywords').focus();
        }
    });
    jQuery("a.sermonz_clear_search").click(function() {
        jQuery('.sermonz_search_field_wrap').removeClass("active");
        jQuery('.sermonz_show_search').removeClass("active");
        jQuery('.sermonz_form .keywords').val('');
        jQuery('.sermonz_form').submit();
    });
});

function sermonzSubmitForm()
{
    console.log("submitting form...");
    jQuery('.sermonz_form').submit(function(){
        jQuery.ajax({
          url: jQuery('.sermonz_form').attr('action'),
          type: 'POST',
          data : jQuery('.sermonz_form').serialize(),
          success: function(){
            console.log('form submitted.');
          }
        });
        return false;
    });
}

function initialiseMoreLinks() 
{
    jQuery('a.sermonz_more').click(function() 
    {
        jQuery(".sermonz_more_wrap").remove();
        var href=jQuery(this).attr("href");
        //jQuery('.sermonz_series_list').load(href+' .sermonz_series_list');
        jQuery(".sermonz_loading").show();
        jQuery.get(href, function(data)
        {
            jQuery(data).find(".sermonz_series_list").appendTo(".sermons_series_pages");
            jQuery(".sermonz_previous").remove();
            jQuery(".sermonz_loading").hide();
            initialiseMoreLinks();
        });
        
        return false;
    });
}