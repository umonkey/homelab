jQuery(function ($) {
    $(document).on("change", "input[type=file].gallery", function (e) {
        var $this = $(this);

        var files = $this.get(0).files;
        if (files.length == 0)
            return;

        var fd = new FormData();

        for (var idx in files) {
            fd.append("files[]", files[idx]);
        }

        console && console.log("uploading {0} files to gallery.".format(files.length));

        $.ajax({
            url: "/admin/upload",
            type: "POST",
            data: fd,
            processData: false,
            contentType: false,
            dataType: "json"
        }).done(function (res) {
            res = $.extend({
                ids: [],
                message: null
            }, res);

            // Save to the control.
            var hctl = $this.closest(".form-group").find("input[name=gallery]");
            var value = hctl.val();
            if (value)
                value += ",";
            hctl.val(value + res.ids.join(","));

            // Render gallery preview.
            $.ajax({
                url: "/admin/gallery/preview",
                data: {files: hctl.val()},
                type: "GET",
                dataType: "json"
            }).done(function (res) {
                var fg = $this.closest(".gallery-group");
                fg.find("a.thumbnail").remove();
                fg.prepend(res.html);
            });
        }).fail(function (res) {
            alert("FAILURE");
        });
    });

    /**
     * Удаление элемента из галереи.
     *
     * Удаляет текущий объект, затем пересобирает список идентификаторов
     * и засовывает в ближайший input[name=gallery].
     **/
    $(document).on("click", ".thumbnail .delete", function (e) {
        e.preventDefault();

        var fg = $(this).closest(".form-group");

        $(this).closest(".thumbnail").remove();

        var ids = [];
        fg.find("a.thumbnail").each(function () {
            var id = $(this).attr("data-id");
            if (id)
                ids.push(id);
        });

        var ids = ids.join(",");
        fg.find("input[name=gallery]").val(ids);
    });
});
