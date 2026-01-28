jQuery(function ($) {
    var conden = function (srcid, dstid) {
        var checked = $("input[name='answer[" + srcid + "]']:checked").val() == "yes";
        var ctl = $("input[name='answer[" + dstid + "]']");
        ctl.prop("disabled", !checked);
    };

    var setup = function (sel, b) {
        conden("1", "2");
        conden("3", "4");
        conden("6", "7");
        conden("6", "8");
    };

    $(document).on("change", "form.eval input", function (e) {
        setup();
    });

    setup();
});
