/**
 * Map loader.
 *
 * Loads maps specific scripts if there's a map on the page.
 **/

/* global ufw_map */

jQuery(function ($) {
    var maps = $('div.map[data-items]');
    if (maps.length > 0) {
        $('head').append($('<link rel="stylesheet" type="text/css" />').attr('href', '/css/maps.min.css'));

        $.getScript('/js/maps.min.js', function () {
            maps.each(function () {
                ufw_map($(this));
            });
        });
    }
});
