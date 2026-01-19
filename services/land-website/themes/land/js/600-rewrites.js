/**
 * URL Rewrite editor animation.
 **/
jQuery(function ($) {
    var tmr = null;

    $(document).on('input', '#rewrite_search', function (e) {
        var ctl = $(this);

        if (tmr) {
            clearTimeout(tmr);
            tmr = null;
        }

        tmr = setTimeout(function () {
            tmr = null;

            $.ajax({
                url: '/admin/rewrite',
                data: {query: ctl.val()},
                type: 'GET'
            }).done(function (res) {
                var table = $('<div>').append($.parseHTML(res)).find('#results');
                $('#results').replaceWith(table);
            });
        }, 200);
    });
});
