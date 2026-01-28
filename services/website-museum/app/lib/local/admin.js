jQuery(function ($) {
    $(document).on("click", "button.copy", function (e) {
        var data = $(this).attr("data-clipboard");
        if (data) {
            e.preventDefault();

            try {
                var t = document.createElement("textarea");
                t.style.position = "fixed";
                t.style.top = 0;
                t.style.left = 0;
                t.style.width = "2em";
                t.style.height = "2em";
                t.style.padding = 0;
                t.style.border = "none";
                t.style.outline = "none";
                t.style.boxShadow = "none";
                t.style.background = "transparent";
                t.value = data;
                document.body.appendChild(t);
                t.select();
                document.execCommand("copy");
                document.body.removeChild(t);
            } catch (e) {
                alert(e);
            }
        }
    });

    $(document).on("click", "a[data-filter]", function (e) {
        e.preventDefault();

        var f = $(this).attr("data-filter");
        $(".admphotos .item").hide();
        $(".admphotos .item." + f).show();

        $(this).blur();
        $(this).closest(".nav").find(".active").removeClass("active");
        $(this).closest("li").addClass("active");
    });

    $(document).on("click", ".admphotos a.delete", function (e) {
        e.preventDefault();

        var item = $(this).closest(".item");

        $.ajax({
            url: $(this).attr("href"),
            type: "POST",
            dataType: "json"
        }).done(function (res) {
            res = $.extend({
                status: "draft",
            }, res);

            if (res.status == "draft") {
                item.hide();
                item.removeClass("published").addClass("draft");
            } else if (res.status == "deleted") {
                item.remove();
            }

            update_photo_counts();
        }).fail(handle_ajax_failure);
    });

    var update_photo_counts = function () {
        var cpub = $(".admphotos .published").length;
        var chid = $(".admphotos .draft").length;
        if (chid)
            $("#chid").text(chid).show();
        else
            $("#chid").hide();
    };
    update_photo_counts();

    $(document).on("keydown", function (e) {
        // ctrlKey shiftKey altKey
        if (e.ctrlKey && e.altKey && e.keyCode == 69) {  // "e"
            if ("jsdata" in window && "edit_link" in window.jsdata) {
                window.location.href = window.jsdata["edit_link"];
            } else {
                alert("Не удалось найти ссылку на страницу редактирования.");
            }
        }
    });
});
