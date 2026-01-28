jQuery(function ($) {
    var splashes = $(".splashes ul");
    if (splashes.length > 0) {
        splashes.bxSlider({
            auto: true,
            pager: false,
            controls: true,
            autoControls: false,
            speed: 200,
            pause: 5500
        });
    }
});
