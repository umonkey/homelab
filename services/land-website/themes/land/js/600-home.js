jQuery(function ($) {
    $(document).on('click', '.scrolldown', function (e) {
        e.preventDefault();

        $([document.documentElement, document.body]).animate({
            scrollTop: $('main').offset().top
        }, 500);
    });
});
