window.vk_photo_ready = function (res) {
    res = $.extend({
        link: null
    }, res);

    var l = $("#vk_link");

    l.attr("href", res.link);
    l.show();
};


jQuery(function ($) {
    var parse_selection = function (sel, scale) {
        if (sel == "")
            return null;

        sel = sel.split(",");

        var x = Math.round(sel[0] / scale),
            y = Math.round(sel[1] / scale),
            w = Math.round(sel[2] / scale),
            h = Math.round(sel[3] / scale);

        return [x, y, x + w, y + h];
    };

    var init = function (img) {
        var rw = parseInt(img.attr("data-width")),
            rh = parseInt(img.attr("data-height")),
            iw = img.width(),
            scale = rw / iw;

        var api,
            storage = img.attr("data-storage");

        if (!storage) {
            alert("img.data-storage not set");
            return;
        } else {
            var ctl = $(storage);
            if (ctl.length == 0) {
                alert(storage + " not found");
                return;
            } else {
                storage = ctl;
            }
        }

        img.Jcrop({
            setSelect: parse_selection(storage.val(), scale),

            onSelect: function (c) {
                var str = Math.round(c.x * scale) + "," + Math.round(c.y * scale) + "," + Math.round(c.w * scale) + "," + Math.round(c.h * scale);
                storage.val(str);
                console.log("selection update: " + str);
            },

            onRelease: function (c) {
                storage.val("");
            }
        }, function () { api = this; });
    };

    $("img.cropme").on("load", function () {
        init($(this));
    });

    $(document).on("click", ".variants a", function (e) {
        e.preventDefault();

        var src = $(this).attr("href"),
            i = $(this).closest(".bigphoto").find(".img");
        i.css("background-image", "url(" + src + ")");
    });

    $(document).on("keydown", function (e) {
        if (!e.ctrlKey && !e.altKey && !e.shiftKey) {
            var next;

            switch (e.keyCode) {
            case 37:  // left
                next = $("a.nav.left").attr("href");
                break;
            case 39:  // right
                next = $("a.nav.right").attr("href");
                break;
            }

            if (next)
                window.location.href = next;
        }
    });
});
