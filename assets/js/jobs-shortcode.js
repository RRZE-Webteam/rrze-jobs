/**
Shortcode script
*/
jQuery(document).ready(function($) {
    $('a.joblink').on('click', function (e) {
        e.preventDefault();
        $.ajax({
            type: 'POST',
            url: jobs_sc.ajaxurl,
            data: {
                action: 'rrze_jobs_ajax_function',
                jobid: $(this).data('jobid')
            },
            success: function (data, textStatus, XMLHttpRequest) {
                $close_link = '<p name="link-container" class="rrze-jobs-closelink-container"><a href="#" class="view-all rrze-jobs-closelink"><i class="fa fa-close" aria-hidden="true"></i> schließen</a></p>';
                $('div.rrze-jobs-single, a.rrze-jobs-closelink').remove();
                $('ul.rrze-jobs-list').after($close_link + $.parseJSON(data));
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                alert(errorThrown);
            }
        });
    });
    $('.entry-content').on('click', 'a.rrze-jobs-closelink', function (e) {
        e.preventDefault();
        $('div.rrze-jobs-single').remove();
        $(this).remove();
    });
});
