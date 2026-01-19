/**
 * Header bars reaction.
 **/
jQuery(function ($) {
    $(document).on('click', 'header .bars a', function (e) {
        e.preventDefault();

        $('header .menu').toggle();
    });
});
